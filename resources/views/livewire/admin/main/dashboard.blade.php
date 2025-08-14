<?php

use App\Models\AuditLog;
use App\Models\ConsumableItem;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Division;
use App\Models\Employee;
use App\Models\IcsNumber;
use App\Models\IcsTransfer;
use App\Models\IdrNumber;
use App\Models\ItemsCatalog;
use App\Models\ParNumber;
use App\Models\ParTransfer;
use App\Models\PrimaryCategory;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $tab = 'overview';

    public bool $showAllAlerts = false;

    // Chart data tracking for change detection
    public ?string $chartDataChecksum = null;

    public function toggleAlerts(): void
    {
        $this->showAllAlerts = ! $this->showAllAlerts;
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function openCreateItemModal(): void
    {
        \Flux\Flux::modal('dashboard-create-item')->show();
    }

    public function refreshAlerts(): void
    {
        // Clear cache to force refresh of alert data
        Cache::forget('admin.dashboard.alerts');
        Cache::forget('admin.dashboard.stats');
        
        // Add a small delay to show the loading animation
        usleep(500000); // 0.5 seconds
        
        // Dispatch browser event for toast notification
        $this->dispatch('alert-refreshed');
    }
    
    public function mount(): void
    {
        // Charts will be initialized on client-side after DOM is ready
        // This prevents the timing issue where events are dispatched before canvas elements exist
    }
    
    public function updated(): void
    {
        // Check if chart data has changed and update if necessary
        $this->updateChartsIfNeeded();
    }
    
    private function initializeCharts(): void
    {
        // Get current chart data
        $inventoryData = $this->inventoryValueOverTime;
        $categoryData = $this->categoryDistribution;
        $systemBreakdownData = $this->inventorySystemBreakdown;
        $topSuppliersData = $this->topSuppliersSpending;
        
        // Generate checksum for change detection
        $currentChecksum = md5(serialize([
            $inventoryData, 
            $categoryData, 
            $systemBreakdownData, 
            $topSuppliersData,
        ]));
        
        // Emit events to initialize charts
        $this->dispatch('initializeLineChart', [
            'chartId' => 'line-chart-canvas',
            'data' => $inventoryData,
        ]);
        
        $this->dispatch('initializeDoughnutChart', [
            'chartId' => 'donut-chart-canvas', 
            'data' => $categoryData,
        ]);
        
        $this->dispatch('initializeInventorySystemChart', [
            'chartId' => 'inventory-system-chart-canvas',
            'data' => $systemBreakdownData,
        ]);
        
        $this->dispatch('initializeTopSuppliersChart', [
            'chartId' => 'top-suppliers-chart-canvas',
            'data' => $topSuppliersData,
        ]);
        
        $this->chartDataChecksum = $currentChecksum;
    }
    
    public function initializeChartsFromClient(): void
    {
        // This method is called from the client-side after DOM is ready
        $this->initializeCharts();
    }

    private function updateChartsIfNeeded(): void
    {
        // Get current chart data
        $inventoryData = $this->inventoryValueOverTime;
        $categoryData = $this->categoryDistribution;
        $systemBreakdownData = $this->inventorySystemBreakdown;
        $topSuppliersData = $this->topSuppliersSpending;
        
        // Generate checksum for change detection
        $currentChecksum = md5(serialize([
            $inventoryData, 
            $categoryData, 
            $systemBreakdownData, 
            $topSuppliersData,
        ]));
        
        // Only update if data has changed
        if ($this->chartDataChecksum !== $currentChecksum) {
            $this->dispatch('updateLineChart', [
                'chartId' => 'line-chart-canvas',
                'data' => $inventoryData,
            ]);
            
            $this->dispatch('updateDoughnutChart', [
                'chartId' => 'donut-chart-canvas',
                'data' => $categoryData,
            ]);
            
            $this->dispatch('updateInventorySystemChart', [
                'chartId' => 'inventory-system-chart-canvas',
                'data' => $systemBreakdownData,
            ]);
            
            $this->dispatch('updateTopSuppliersChart', [
                'chartId' => 'top-suppliers-chart-canvas',
                'data' => $topSuppliersData,
            ]);
            
            $this->chartDataChecksum = $currentChecksum;
        }
    }

    #[Computed]
    public function stats(): array
    {
        return Cache::remember('admin.dashboard.stats', now()->addMinutes(5), function () {
            $icsQuantity = IcsNumber::sum('quantity');
            $parQuantity = ParNumber::sum('quantity');
            $idrQuantity = IdrNumber::sum('quantity');
            $consumableQuantity = ConsumableItem::sum('current_quantity');

            // Calculate total value
            $icsValue = IcsNumber::calculateTotalValue();
            $parValue = ParNumber::calculateTotalValue();
            $idrValue = IdrNumber::calculateTotalValue();
            $consumableValue = ConsumableItem::calculateTotalValue();

            $totalValue = $icsValue + $parValue + $idrValue + $consumableValue;

            // Calculate expiring soon based on ICS estimated useful life
            $driver = DB::connection()->getDriverName();
            $expiringSoonRaw = $driver === 'sqlite' 
                ? "date(date_prepared, '+' || estimated_useful_life || ' years') BETWEEN ? AND ?" 
                : 'DATE_ADD(date_prepared, INTERVAL estimated_useful_life YEAR) BETWEEN ? AND ?';
            $expiringSoon = IcsNumber::whereRaw($expiringSoonRaw, [now()->toDateString(), now()->addDays(30)->toDateString()])->count();

            return [
                'total_items' => $icsQuantity + $parQuantity + $idrQuantity + $consumableQuantity,
                'total_value' => $totalValue,
                'active_users' => User::count(),
                'pending_actions' => IcsTransfer::count() + ParTransfer::count(),
                'expiring_soon' => $expiringSoon,
                'total_divisions' => Division::count(),
            ];
        });
    }

    #[Computed]
    public function alerts(): array
    {
        return Cache::remember('admin.dashboard.alerts', now()->addMinutes(5), function () {
            $lowStockItemsQuery = ConsumableItem::where('current_quantity', '<', 10);

            $lowStockCount = $lowStockItemsQuery->count();

            $lowStockDivisionsCount = (clone $lowStockItemsQuery)->join('consumable_records', 'consumable_items.consumable_record_id', '=', 'consumable_records.id')->distinct('consumable_records.division_id')->count();

            $driver = DB::connection()->getDriverName();
            $expiringSoonRaw = $driver === 'sqlite' 
                ? "date(date_prepared, '+' || estimated_useful_life || ' years') BETWEEN ? AND ?" 
                : 'DATE_ADD(date_prepared, INTERVAL estimated_useful_life YEAR) BETWEEN ? AND ?';
            $expiringSoon = IcsNumber::whereRaw($expiringSoonRaw, [now()->toDateString(), now()->addDays(30)->toDateString()])->count();
            
            $uncategorizedItems = ItemsCatalog::whereNull('secondary_category_id')->count();
            $inactiveSuppliers = Supplier::whereDoesntHave('contracts', function ($query) {
                $query->where('created_at', '>=', now()->subYear());
            })->count();
            $unmanagedDivisions = Division::whereDoesntHave('inventoryManagers')->count();

            $itemsMissingSpecs = ItemsCatalog::doesntHave('specifications')->count();
            $unassignedEmployees = Employee::whereNull('division_id')->count();
            $emptyContracts = Contract::doesntHave('contractItems')->count();

            return [
                'low_stock' => $lowStockCount,
                'low_stock_divisions' => $lowStockDivisionsCount,
                'pending_transfers' => IcsTransfer::count() + ParTransfer::count(),
                'expiring_soon' => $expiringSoon,
                'uncategorized_items' => $uncategorizedItems,
                'inactive_suppliers' => $inactiveSuppliers,
                'unmanaged_divisions' => $unmanagedDivisions,
                'items_missing_specs' => $itemsMissingSpecs,
                'unassigned_employees' => $unassignedEmployees,
                'empty_contracts' => $emptyContracts,
            ];
        });
    }

    #[Computed]
    public function secondaryStats(): array
    {
        return [
            [
                'label' => 'ICS Records',
                'value' => number_format(IcsNumber::count()),
                'unit' => 'Slips',
                'change' => '',
                'icon' => 'flux::icon.document-text',
                'color' => 'text-sky-500',
            ],
            [
                'label' => 'PAR Records',
                'value' => number_format(ParNumber::count()),
                'unit' => 'Slips',
                'change' => '',
                'icon' => 'flux::icon.document-text',
                'color' => 'text-lime-500',
            ],
            [
                'label' => 'IDR Records',
                'value' => number_format(IdrNumber::count()),
                'unit' => 'Slips',
                'change' => '',
                'icon' => 'flux::icon.document-text',
                'color' => 'text-amber-500',
            ],
            [
                'label' => 'Asset Transfers',
                'value' => IcsTransfer::count() + ParTransfer::count(),
                'unit' => 'Transfers',
                'change' => '',
                'icon' => 'flux::icon.arrows-right-left',
                'color' => 'text-purple-500',
            ],
        ];
    }

    #[Computed]
    public function quickActions(): array
    {
        return [
            ['label' => 'Add New Item', 'icon' => 'flux::icon.plus-circle', 'action' => 'openCreateItemModal', 'type' => 'modal'],
            ['label' => 'Manage Inventory', 'icon' => 'flux::icon.package', 'route' => 'admin.inventory.ics.index', 'type' => 'route'],
            ['label' => 'Manage Divisions', 'icon' => 'flux::icon.building-2', 'route' => 'admin.data.employees-and-divisions.divisions.index', 'type' => 'route'],
            ['label' => 'Manage Suppliers', 'icon' => 'flux::icon.truck', 'route' => 'admin.data.suppliers-and-contracts.suppliers.index', 'type' => 'route'],
            ['label' => 'View Reports', 'icon' => 'flux::icon.chart-bar', 'route' => 'admin.main.reports.index', 'type' => 'route'],
            ['label' => 'Manage Users', 'icon' => 'flux::icon.users', 'route' => 'admin.system.users.index', 'type' => 'route'],
        ];
    }

    #[Computed]
    public function inventoryValueOverTime(): array
    {
        return Cache::remember('admin.dashboard.inventory_value_over_time', now()->addMinutes(10), function () {
            $months = [];
            $currentDate = now();
            
            // Get data for the last 12 months
            for ($i = 11; $i >= 0; $i--) {
                $targetDate = $currentDate->copy()->subMonths($i);
                $monthKey = $targetDate->format('Y-m');
                $monthLabel = $targetDate->format('M Y');
                
                // Calculate ICS value for this month
                $icsValue = IcsNumber::join('contract_items', 'ics_number.contract_item_id', '=', 'contract_items.id')
                    ->where('ics_number.created_at', '<=', $targetDate->endOfMonth())
                    ->sum(DB::raw('ics_number.quantity * contract_items.unit_price'));
                
                // Calculate PAR value for this month
                $parValue = ParNumber::join('contract_items', 'par_number.contract_item_id', '=', 'contract_items.id')
                    ->where('par_number.created_at', '<=', $targetDate->endOfMonth())
                    ->sum(DB::raw('par_number.quantity * contract_items.unit_price'));
                
                // Calculate IDR value for this month
                $idrValue = IdrNumber::join('contract_items', 'idr_number.contract_item_id', '=', 'contract_items.id')
                    ->where('idr_number.created_at', '<=', $targetDate->endOfMonth())
                    ->sum(DB::raw('idr_number.quantity * contract_items.unit_price'));
                
                // Calculate Consumable value for this month
                $avgPrices = ContractItem::query()
                    ->select('item_specification_id', DB::raw('AVG(unit_price) as average_price'))
                    ->groupBy('item_specification_id');

                $consumableValue = DB::table('consumable_items')
                    ->joinSub($avgPrices, 'avg_prices', function ($join) {
                        $join->on('consumable_items.item_specification_id', '=', 'avg_prices.item_specification_id');
                    })
                    ->join('consumable_records', 'consumable_items.consumable_record_id', '=', 'consumable_records.id')
                    ->where('consumable_records.created_at', '<=', $targetDate->endOfMonth())
                    ->sum(DB::raw('consumable_items.current_quantity * avg_prices.average_price'));
                
                $totalValue = $icsValue + $parValue + $idrValue + $consumableValue;
                
                $months[] = [
                    'month' => $monthLabel,
                    'value' => round($totalValue / 1000000, 2), // Convert to millions
                    'ics' => round($icsValue / 1000000, 2),
                    'par' => round($parValue / 1000000, 2),
                    'idr' => round($idrValue / 1000000, 2),
                    'consumables' => round($consumableValue / 1000000, 2),
                ];
            }
            
            return $months;
        });
    }

    #[Computed]
    public function inventorySystemBreakdown(): array
    {
        return Cache::remember('admin.dashboard.inventory_system_breakdown', now()->addMinutes(10), function () {
            // Get ICS data
            $icsQuantity = IcsNumber::sum('quantity');
            $icsValue = IcsNumber::calculateTotalValue();
            
            // Get PAR data
            $parQuantity = ParNumber::sum('quantity');
            $parValue = ParNumber::calculateTotalValue();
            
            // Get IDR data
            $idrQuantity = IdrNumber::sum('quantity');
            $idrValue = IdrNumber::calculateTotalValue();
            
            // Get Consumables data
            $consumableQuantity = ConsumableItem::sum('current_quantity');
            $consumableValue = ConsumableItem::calculateTotalValue();
            
            return [
                [
                    'system' => 'ICS',
                    'name' => 'Inventory Custodian Slip',
                    'quantity' => $icsQuantity,
                    'value' => $icsValue,
                    'color' => '#10B981', // Green
                ],
                [
                    'system' => 'PAR',
                    'name' => 'Property Acknowledgment Receipt',
                    'quantity' => $parQuantity,
                    'value' => $parValue,
                    'color' => '#F59E0B', // Yellow
                ],
                [
                    'system' => 'IDR',
                    'name' => 'Inventory Delivery Receipt',
                    'quantity' => $idrQuantity,
                    'value' => $idrValue,
                    'color' => '#8B5CF6', // Purple
                ],
                [
                    'system' => 'Consumables',
                    'name' => 'Consumable Items',
                    'quantity' => $consumableQuantity,
                    'value' => $consumableValue,
                    'color' => '#EF4444', // Red
                ],
            ];
        });
    }

    #[Computed]
    public function categoryDistribution()
    {
        return Cache::remember('admin.dashboard.category_distribution', now()->addMinutes(10), function () {
            $categories = PrimaryCategory::all();
            $data = [];
            $totalItems = 0;
            
            foreach ($categories as $category) {
                // Count items across all inventory types for this category
                $icsCount = DB::table('ics_number')
                    ->join('contract_items', 'ics_number.contract_item_id', '=', 'contract_items.id')
                    ->join('item_specifications', 'contract_items.item_specification_id', '=', 'item_specifications.id')
                    ->join('items_catalog', 'item_specifications.item_catalog_id', '=', 'items_catalog.id')
                    ->join('secondary_categories', 'items_catalog.secondary_category_id', '=', 'secondary_categories.id')
                    ->where('secondary_categories.primary_category_id', $category->id)
                    ->sum('ics_number.quantity');
                
                $parCount = DB::table('par_number')
                    ->join('contract_items', 'par_number.contract_item_id', '=', 'contract_items.id')
                    ->join('item_specifications', 'contract_items.item_specification_id', '=', 'item_specifications.id')
                    ->join('items_catalog', 'item_specifications.item_catalog_id', '=', 'items_catalog.id')
                    ->join('secondary_categories', 'items_catalog.secondary_category_id', '=', 'secondary_categories.id')
                    ->where('secondary_categories.primary_category_id', $category->id)
                    ->sum('par_number.quantity');
                
                $idrCount = DB::table('idr_number')
                    ->join('contract_items', 'idr_number.contract_item_id', '=', 'contract_items.id')
                    ->join('item_specifications', 'contract_items.item_specification_id', '=', 'item_specifications.id')
                    ->join('items_catalog', 'item_specifications.item_catalog_id', '=', 'items_catalog.id')
                    ->join('secondary_categories', 'items_catalog.secondary_category_id', '=', 'secondary_categories.id')
                    ->where('secondary_categories.primary_category_id', $category->id)
                    ->sum('idr_number.quantity');
                
                $consumableCount = DB::table('consumable_items')
                    ->join('item_specifications', 'consumable_items.item_specification_id', '=', 'item_specifications.id')
                    ->join('items_catalog', 'item_specifications.item_catalog_id', '=', 'items_catalog.id')
                    ->join('secondary_categories', 'items_catalog.secondary_category_id', '=', 'secondary_categories.id')
                    ->where('secondary_categories.primary_category_id', $category->id)
                    ->sum('consumable_items.current_quantity');
                
                $categoryTotal = $icsCount + $parCount + $idrCount + $consumableCount;
                $totalItems += $categoryTotal;
                
                if ($categoryTotal > 0) {
                    $data[] = [
                        'name' => $category->name,
                        'count' => $categoryTotal,
                        'code' => $category->code,
                    ];
                }
            }
            
            // Calculate percentages
            foreach ($data as &$item) {
                $item['percentage'] = $totalItems > 0 ? round(($item['count'] / $totalItems) * 100, 1) : 0;
            }
            
            // Sort by count descending
            usort($data, fn ($a, $b) => $b['count'] <=> $a['count']);
            
            return collect($data);
        });
    }

    #[Computed]
    public function divisionInventory(): array
    {
        return Cache::remember('admin.dashboard.division_inventory', now()->addMinutes(5), function () {
            $divisions = Division::all()->keyBy('id');

            $icsCounts = IcsNumber::select('employees.division_id', DB::raw('sum(ics_number.quantity) as total'))
                ->join('employees', 'ics_number.assigned_employee_id', '=', 'employees.id')
                ->whereNotNull('employees.division_id')
                ->groupBy('employees.division_id')
                ->pluck('total', 'employees.division_id');

            $parCounts = ParNumber::select('employees.division_id', DB::raw('sum(par_number.quantity) as total'))
                ->join('employees', 'par_number.assigned_employee_id', '=', 'employees.id')
                ->whereNotNull('employees.division_id')
                ->groupBy('employees.division_id')
                ->pluck('total', 'employees.division_id');
            
            $idrCounts = IdrNumber::select('employees.division_id', DB::raw('sum(idr_number.quantity) as total'))
                ->join('employees', 'idr_number.assigned_employee_id', '=', 'employees.id')
                ->whereNotNull('employees.division_id')
                ->groupBy('employees.division_id')
                ->pluck('total', 'employees.division_id');

            $consumableCounts = ConsumableItem::select('consumable_records.division_id', DB::raw('sum(consumable_items.current_quantity) as total'))
                ->join('consumable_records', 'consumable_items.consumable_record_id', '=', 'consumable_records.id')
                ->groupBy('consumable_records.division_id')
                ->pluck('total', 'consumable_records.division_id');
                
            $lowStockCounts = ConsumableItem::where('current_quantity', '<', 10)
                ->join('consumable_records', 'consumable_items.consumable_record_id', '=', 'consumable_records.id')
                ->select('consumable_records.division_id', DB::raw('count(*) as total'))
                ->groupBy('consumable_records.division_id')
                ->pluck('total', 'consumable_records.division_id');

            return $divisions->map(function ($division) use ($icsCounts, $parCounts, $idrCounts, $consumableCounts, $lowStockCounts) {
                $ics = $icsCounts->get($division->id, 0);
                $par = $parCounts->get($division->id, 0);
                $idr = $idrCounts->get($division->id, 0);
                $consumables = $consumableCounts->get($division->id, 0);
                
                return [
                    'name' => $division->name,
                    'total_items' => $ics + $par + $idr + $consumables,
                    'ics' => $ics,
                    'par' => $par,
                    'idr' => $idr,
                    'consumables' => $consumables,
                    'low_stock' => $lowStockCounts->get($division->id, 0),
                ];
            })->sortBy('name')->values()->all();
        });
    }

    #[Computed]
    public function categoryInventory(): array
    {
        return Cache::remember('admin.dashboard.category_inventory', now()->addMinutes(5), function () {
            // Query for non-consumable items
            $nonConsumables = DB::table('primary_categories')
                ->join('secondary_categories', 'primary_categories.id', '=', 'secondary_categories.primary_category_id')
                ->join('items_catalog', 'secondary_categories.id', '=', 'items_catalog.secondary_category_id')
                ->join('item_specifications', 'items_catalog.id', '=', 'item_specifications.item_catalog_id')
                ->join('contract_items', 'item_specifications.id', '=', 'contract_items.item_specification_id')
                ->leftJoin(DB::raw('(SELECT contract_item_id, SUM(quantity) as qty FROM ics_number GROUP BY contract_item_id) as ics'), 'ics.contract_item_id', '=', 'contract_items.id')
                ->leftJoin(DB::raw('(SELECT contract_item_id, SUM(quantity) as qty FROM par_number GROUP BY contract_item_id) as par'), 'par.contract_item_id', '=', 'contract_items.id')
                ->leftJoin(DB::raw('(SELECT contract_item_id, SUM(quantity) as qty FROM idr_number GROUP BY contract_item_id) as idr'), 'idr.contract_item_id', '=', 'contract_items.id')
                ->select(
                    'primary_categories.id',
                    'primary_categories.name',
                    DB::raw('SUM(COALESCE(ics.qty, 0) + COALESCE(par.qty, 0) + COALESCE(idr.qty, 0)) as total_items'),
                    DB::raw('SUM((COALESCE(ics.qty, 0) + COALESCE(par.qty, 0) + COALESCE(idr.qty, 0)) * contract_items.unit_price) as total_value')
                )
                ->groupBy('primary_categories.id', 'primary_categories.name')
                ->get()
                ->keyBy('id');

            // Query for consumable items
            $avgPrices = DB::table('contract_items')
                ->select('item_specification_id', DB::raw('AVG(unit_price) as avg_price'))
                ->groupBy('item_specification_id');

            $consumables = DB::table('primary_categories')
                ->join('secondary_categories', 'primary_categories.id', '=', 'secondary_categories.primary_category_id')
                ->join('items_catalog', 'secondary_categories.id', '=', 'items_catalog.secondary_category_id')
                ->join('item_specifications', 'items_catalog.id', '=', 'item_specifications.item_catalog_id')
                ->join('consumable_items', 'item_specifications.id', '=', 'consumable_items.item_specification_id')
                ->leftJoinSub($avgPrices, 'avg_prices', function ($join) {
                    $join->on('item_specifications.id', '=', 'avg_prices.item_specification_id');
                })
                ->select(
                    'primary_categories.id',
                    DB::raw('SUM(consumable_items.current_quantity) as total_items'),
                    DB::raw('SUM(consumable_items.current_quantity * COALESCE(avg_prices.avg_price, 0)) as total_value')
                )
                ->groupBy('primary_categories.id')
                ->get()
                ->keyBy('id');

            // Combine results
            return PrimaryCategory::all()->map(function ($primaryCategory) use ($nonConsumables, $consumables) {
                $ncData = $nonConsumables->get($primaryCategory->id);
                $cData = $consumables->get($primaryCategory->id);

                $totalItems = ($ncData->total_items ?? 0) + ($cData->total_items ?? 0);
                $totalValue = ($ncData->total_value ?? 0) + ($cData->total_value ?? 0);

                return [
                    'name' => $primaryCategory->name,
                    'total_items' => $totalItems,
                    'total_value' => $totalValue,
                ];
            })->sortBy('name')->values()->all();
        });
    }

    #[Computed]
    public function topSuppliersSpending(): array
    {
        return Cache::remember('admin.dashboard.top_suppliers_spending', now()->addMinutes(10), function () {
            return Supplier::with(['contracts.contractItems' => function ($query) {
                $query->withCount(['icsNumbers as items_count' => function ($q) {
                    $q->select(DB::raw('sum(quantity)'));
                }, 'parNumbers as items_count_par' => function ($q) {
                    $q->select(DB::raw('sum(quantity)'));
                }, 'idrNumbers as items_count_idr' => function ($q) {
                    $q->select(DB::raw('sum(quantity)'));
                }]);
            }])->get()->map(function ($supplier) {
                $totalSpent = 0;
                $totalItems = 0;

                foreach ($supplier->contracts as $contract) {
                    foreach ($contract->contractItems as $item) {
                        $quantity = $item->items_count + $item->items_count_par + $item->items_count_idr;
                        $totalSpent += $quantity * $item->unit_price;
                        $totalItems += $quantity;
                    }
                }

                return [
                    'name' => $supplier->name,
                    'total_spent' => $totalSpent,
                    'total_items' => $totalItems,
                    'contracts_count' => $supplier->contracts->count(),
                ];
            })->sortByDesc('total_spent')->take(10)->values()->toArray();
        });
    }

    #[Computed]
    public function supplierSpending()
    {
        return Cache::remember('admin.dashboard.supplier_spending', now()->addMinutes(5), function () {
            return Supplier::with(['contracts.contractItems' => function ($query) {
                $query->withCount(['icsNumbers as items_count' => function ($q) {
                    $q->select(DB::raw('sum(quantity)'));
                }, 'parNumbers as items_count_par' => function ($q) {
                    $q->select(DB::raw('sum(quantity)'));
                }, 'idrNumbers as items_count_idr' => function ($q) {
                    $q->select(DB::raw('sum(quantity)'));
                }]);
            }])->get()->map(function ($supplier) {
                $totalSpent = 0;
                $totalItems = 0;

                foreach ($supplier->contracts as $contract) {
                    foreach ($contract->contractItems as $item) {
                        $quantity = $item->items_count + $item->items_count_par + $item->items_count_idr;
                        $totalSpent += $quantity * $item->unit_price;
                        $totalItems += $quantity;
                    }
                }

                return [
                    'name' => $supplier->name,
                    'total_spent' => $totalSpent,
                    'total_items' => $totalItems,
                    'contracts_count' => $supplier->contracts->count(),
                ];
            })->sortByDesc('total_spent')->values();
        });
    }

    #[Computed]
    public function recentActivity(): array
    {
        return Cache::remember('admin.dashboard.recent_activity', now()->addMinutes(2), function () {
            return AuditLog::with('user')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'user_name' => $log->user?->name ?? 'System',
                        'action' => $log->action_type,
                        'table' => $log->table_name,
                        'description' => $log->description,
                        'created_at' => $log->created_at,
                        'time_ago' => $log->created_at?->diffForHumans(),
                    ];
                })
                ->toArray();
        });
    }

    #[Computed]
    public function userManagement(): array
    {
        return Cache::remember('admin.dashboard.user_management', now()->addMinutes(5), function () {
            $totalUsers = User::count();
            $adminUsers = User::whereHas('adminUser')->count();
            $inventoryManagers = User::whereHas('divisionInventoryManager')->count();
            $regularUsers = $totalUsers - $adminUsers - $inventoryManagers;
            $verifiedUsers = User::whereNotNull('email_verified_at')->count();
            $unverifiedUsers = $totalUsers - $verifiedUsers;
            
            // Recent user registrations (last 30 days)
            $recentRegistrations = User::where('created_at', '>=', now()->subDays(30))->count();
            
            // Recent admin logins (last 7 days)
            $recentAdminLogins = User::whereHas('adminUser', function ($query) {
                $query->where('last_login_at', '>=', now()->subDays(7));
            })->count();
            
            // Active admin users (logged in within 30 days)
            $activeAdmins = User::whereHas('adminUser', function ($query) {
                $query->where('last_login_at', '>=', now()->subDays(30));
            })->count();

            // Role distribution
            $roleDistribution = [
                ['role' => 'Admin Users', 'count' => $adminUsers, 'color' => 'text-red-500'],
                ['role' => 'Inventory Managers', 'count' => $inventoryManagers, 'color' => 'text-blue-500'],
                ['role' => 'Regular Users', 'count' => $regularUsers, 'color' => 'text-green-500'],
            ];

            return [
                'total_users' => $totalUsers,
                'admin_users' => $adminUsers,
                'inventory_managers' => $inventoryManagers,
                'regular_users' => $regularUsers,
                'verified_users' => $verifiedUsers,
                'unverified_users' => $unverifiedUsers,
                'recent_registrations' => $recentRegistrations,
                'recent_admin_logins' => $recentAdminLogins,
                'active_admins' => $activeAdmins,
                'role_distribution' => $roleDistribution,
            ];
        });
    }
}; ?>

<div class="w-full mx-auto space-y-4 sm:space-y-6" wire:poll.15s x-init="setTimeout(() => $wire.initializeChartsFromClient(), 100)">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0 mb-2">
        <div class="relative">
            <h1 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-stone-900 via-stone-700 to-green-700 dark:from-stone-100 dark:via-stone-300 dark:to-green-400 bg-clip-text text-transparent">D'Agriventory</h1>
            <p class="text-sm sm:text-base text-stone-600 dark:text-stone-400 mt-1">
                Agricultural Inventory Management System
            </p>
        </div>
        <div class="flex items-center space-x-4">
            <div class="hidden sm:flex items-center px-4 py-2 bg-green-50 dark:bg-green-900/20 rounded-full border border-green-200 dark:border-green-800">
                <x-flux::icon.check-circle class="h-4 w-4 text-green-600 dark:text-green-400 mr-2" />
                <span class="text-sm font-medium text-green-700 dark:text-green-300">System Online</span>
            </div>
        </div>
    </div>

    <!-- Inventory Alerts -->
    <div 
        class="bg-white dark:bg-stone-800/50 rounded-lg shadow-sm border border-stone-200 dark:border-stone-700/60 overflow-hidden transition-all duration-300"
        x-data="{ 
            expanded: @js($showAllAlerts),
            refreshed: false 
        }"
        x-init="$watch('$wire.showAllAlerts', value => expanded = value)"
        x-on:alert-refreshed.window="refreshed = true; setTimeout(() => refreshed = false, 600)"
        x-bind:class="refreshed ? 'ring-2 ring-blue-500/20 shadow-lg' : ''"
    >
        @php
            $criticalAlerts = collect([
                ['key' => 'low_stock', 'title' => 'Low Stock Alert', 'description' => $this->alerts['low_stock'] . ' consumable items are running low across ' . $this->alerts['low_stock_divisions'] . ' divisions', 'icon' => 'flux::icon.x-mark', 'color' => 'red', 'severity' => 'critical', 'count' => $this->alerts['low_stock'], 'route' => null],
                ['key' => 'expiring_soon', 'title' => 'Items Expiring Soon', 'description' => $this->alerts['expiring_soon'] . ' items have useful life expiring within 30 days', 'icon' => 'flux::icon.clock-history', 'color' => 'orange', 'severity' => 'high', 'count' => $this->alerts['expiring_soon'], 'route' => null],
                ['key' => 'pending_transfers', 'title' => 'Pending Transfers', 'description' => $this->alerts['pending_transfers'] . ' transfer requests awaiting approval (ICS, PAR, IDR)', 'icon' => 'flux::icon.clock-history', 'color' => 'amber', 'severity' => 'medium', 'count' => $this->alerts['pending_transfers'], 'route' => null],
            ])->filter(fn($alert) => $alert['count'] > 0);
            
            $warningAlerts = collect([
                ['key' => 'uncategorized_items', 'title' => 'Uncategorized Items', 'description' => ($this->alerts['uncategorized_items'] ?? 0) . ' items are missing category information', 'icon' => 'flux::icon.tag', 'color' => 'sky', 'severity' => 'low', 'count' => $this->alerts['uncategorized_items'] ?? 0, 'route' => 'admin.data.items-and-categories'],
                ['key' => 'inactive_suppliers', 'title' => 'Inactive Suppliers', 'description' => ($this->alerts['inactive_suppliers'] ?? 0) . ' suppliers have had no activity in the last year', 'icon' => 'flux::icon.truck', 'color' => 'teal', 'severity' => 'low', 'count' => $this->alerts['inactive_suppliers'] ?? 0, 'route' => 'admin.data.suppliers-and-contracts.suppliers.index'],
                ['key' => 'unmanaged_divisions', 'title' => 'Unmanaged Divisions', 'description' => ($this->alerts['unmanaged_divisions'] ?? 0) . ' divisions do not have an assigned inventory manager', 'icon' => 'flux::icon.user-minus', 'color' => 'purple', 'severity' => 'medium', 'count' => $this->alerts['unmanaged_divisions'] ?? 0, 'route' => 'admin.data.employees-and-divisions.divisions.index'],
                ['key' => 'items_missing_specs', 'title' => 'Items Missing Specs', 'description' => ($this->alerts['items_missing_specs'] ?? 0) . ' items in the catalog are missing specifications', 'icon' => 'flux::icon.puzzle-piece', 'color' => 'violet', 'severity' => 'low', 'count' => $this->alerts['items_missing_specs'] ?? 0, 'route' => 'admin.data.items-and-categories'],
                ['key' => 'unassigned_employees', 'title' => 'Unassigned Employees', 'description' => ($this->alerts['unassigned_employees'] ?? 0) . ' employees are not yet assigned to a division', 'icon' => 'flux::icon.user-circle', 'color' => 'pink', 'severity' => 'low', 'count' => $this->alerts['unassigned_employees'] ?? 0, 'route' => 'admin.data.employees-and-divisions'],
                ['key' => 'empty_contracts', 'title' => 'Empty Contracts', 'description' => ($this->alerts['empty_contracts'] ?? 0) . ' contracts have no items associated with them', 'icon' => 'flux::icon.document-minus', 'color' => 'cyan', 'severity' => 'low', 'count' => $this->alerts['empty_contracts'] ?? 0, 'route' => 'admin.data.suppliers-and-contracts.contracts.index'],
            ])->filter(fn($alert) => $alert['count'] > 0);
            
            $allAlerts = $criticalAlerts->merge($warningAlerts);
            $totalActiveAlerts = $allAlerts->count();
        @endphp

        <!-- Alert Header -->
        <div class="p-3 sm:p-4 border-b border-stone-200 dark:border-stone-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        @if($totalActiveAlerts > 0)
                            <div class="relative">
                                <div wire:loading.remove wire:target="refreshAlerts">
                                    <x-flux::icon.exclamation-triangle class="h-5 w-5 text-amber-500" />
                                    <div class="absolute -top-2 -right-2 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold">{{ $totalActiveAlerts }}</div>
                                </div>
                                <div wire:loading wire:target="refreshAlerts" class="relative">
                                    <div class="h-5 w-5 rounded-full bg-amber-200 dark:bg-amber-800 animate-pulse"></div>
                                    <div class="absolute -top-2 -right-2 h-4 w-4 bg-gray-300 dark:bg-gray-600 rounded-full animate-pulse"></div>
                                </div>
                            </div>
                        @else
                            <div wire:loading.remove wire:target="refreshAlerts">
                                <x-flux::icon.check-circle class="h-5 w-5 text-green-500" />
                            </div>
                            <div wire:loading wire:target="refreshAlerts">
                                <div class="h-5 w-5 rounded-full bg-green-200 dark:bg-green-800 animate-pulse"></div>
                            </div>
                        @endif
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-semibold text-stone-900 dark:text-stone-100">
                            System Alerts
                        </h2>
                        <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400">
                            <span wire:loading.remove wire:target="refreshAlerts">
                                @if($totalActiveAlerts > 0)
                                    {{ $totalActiveAlerts }} {{ Str::plural('issue', $totalActiveAlerts) }} requiring attention
                                @else
                                    All systems operating normally
                                @endif
                            </span>
                            <span wire:loading wire:target="refreshAlerts" class="text-blue-500 dark:text-blue-400">
                                Checking system status...
                            </span>
                        </p>
                    </div>
                </div>
                
                @if($totalActiveAlerts > 0)
                    <button 
                        @click="expanded = !expanded; $wire.call('toggleAlerts')"
                        class="flex items-center px-3 py-1.5 text-xs sm:text-sm font-medium text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100 hover:bg-stone-100 dark:hover:bg-stone-700 rounded-md transition-colors"
                    >
                        <span x-text="expanded ? 'Collapse' : 'View Details'">{{ $showAllAlerts ? 'Collapse' : 'View Details' }}</span>
                        <x-flux::icon.chevron-down class="ml-1 h-4 w-4 transform transition-transform" x-bind:class="expanded ? 'rotate-180' : ''" />
                    </button>
                @endif
            </div>
        </div>

        @if($totalActiveAlerts == 0)
            <!-- No Alerts State -->
            <div class="p-6 sm:p-8 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/20 mb-4">
                    <x-flux::icon.check-circle class="h-6 w-6 text-green-600 dark:text-green-400" />
                </div>
                <h3 class="text-sm font-medium text-stone-900 dark:text-stone-100 mb-1">All Clear!</h3>
                <p class="text-sm text-stone-500 dark:text-stone-400">Your inventory system is running smoothly with no urgent issues.</p>
            </div>
        @else
            <!-- Critical Alerts Summary (Always Visible) -->
            @if($criticalAlerts->isNotEmpty())
                <div class="p-3 sm:p-4 bg-red-50 dark:bg-red-950/20 border-b border-red-200 dark:border-red-800">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-red-100 dark:bg-red-900/40">
                                <x-flux::icon.exclamation-triangle class="h-4 w-4 text-red-600 dark:text-red-400" />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-red-800 dark:text-red-200">Critical Issues</h3>
                            <div class="flex flex-wrap gap-2 mt-1">
                                @foreach($criticalAlerts as $alert)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300">
                                        {{ $alert['count'] }} {{ $alert['title'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Detailed Alerts (Collapsible) -->
            <div 
                x-show="expanded"
                x-collapse.duration.300ms
                class="divide-y divide-stone-200 dark:divide-stone-700"
            >
                @foreach($allAlerts as $alert)
                    <div class="p-3 sm:p-4 hover:bg-stone-50 dark:hover:bg-stone-700/50 transition-colors">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 mt-0.5">
                                @php
                                    $severityColors = [
                                        'critical' => 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400',
                                        'high' => 'bg-orange-100 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400',
                                        'medium' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400',
                                        'low' => 'bg-stone-100 dark:bg-stone-700 text-stone-600 dark:text-stone-400'
                                    ];
                                    $colorClass = $severityColors[$alert['severity']] ?? $severityColors['low'];
                                @endphp
                                <div class="flex items-center justify-center h-8 w-8 rounded-full {{ $colorClass }}">
                                    <x-dynamic-component :component="$alert['icon']" class="h-4 w-4" />
                                </div>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-semibold text-stone-900 dark:text-stone-100">
                                        {{ $alert['title'] }}
                                    </h4>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-stone-100 dark:bg-stone-700 text-stone-700 dark:text-stone-300">
                                        {{ $alert['count'] }}
                                    </span>
                                </div>
                                <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-400 mt-1 leading-relaxed">
                                    {{ $alert['description'] }}
                                </p>
                                
                                @if($alert['route'])
                                    <div class="mt-2">
                                        <a 
                                            href="{{ route($alert['route']) }}" 
                                            wire:navigate 
                                            class="inline-flex items-center text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 hover:underline"
                                        >
                                            Resolve Issue
                                            <x-flux::icon.arrow-up-right class="ml-1 h-3 w-3" />
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Action Footer (when expanded) -->
            <div 
                x-show="expanded && @js($totalActiveAlerts > 0)"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="p-3 sm:p-4 bg-stone-50 dark:bg-stone-900/50 border-t border-stone-200 dark:border-stone-700"
            >
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
                        <div class="text-xs sm:text-sm text-stone-600 dark:text-stone-400">
                            Last updated: {{ now()->format('M j, Y \a\t g:i A') }}
                        </div>
                        <div class="flex space-x-2">
                            <button 
                                wire:click="refreshAlerts"
                                wire:loading.attr="disabled"
                                x-data="{ refreshing: false }"
                                x-on:click="refreshing = true; setTimeout(() => refreshing = false, 1500)"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed hover:shadow-sm focus:ring-2 focus:ring-blue-500/20"
                                title="Refresh system alerts"
                            >
                                <div wire:loading.remove wire:target="refreshAlerts">
                                    <x-flux::icon.arrow-path class="mr-1 h-3 w-3" x-bind:class="refreshing ? 'animate-spin' : ''" />
                                </div>
                                <div wire:loading wire:target="refreshAlerts">
                                    <svg class="mr-1 h-3 w-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <span wire:loading.remove wire:target="refreshAlerts">Refresh</span>
                                <span wire:loading wire:target="refreshAlerts">Refreshing...</span>
                            </button>
                        </div>
                    </div>
            </div>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="bg-white dark:bg-stone-800/90 backdrop-blur-sm rounded-lg shadow-lg border border-stone-200 dark:border-stone-700/70 hover:shadow-xl transition-all duration-300">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="flex items-center justify-between mb-4 sm:mb-6">
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-stone-900 dark:text-stone-100 flex items-center">
                        <div class="w-2 h-6 bg-gradient-to-b from-green-500 to-green-600 rounded-full mr-3"></div>
                        Quick Actions
                    </h2>
                    <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400 mt-1 ml-5">Frequently used features and shortcuts</p>
                </div>
                <div class="flex items-center px-3 py-1.5 bg-stone-100 dark:bg-stone-700/50 rounded-full text-xs text-stone-500 dark:text-stone-400">
                    <x-flux::icon.arrows-trending-up class="h-4 w-4 mr-1" />
                    <span class="hidden sm:inline font-medium">Fast Access</span>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-3 sm:gap-4 md:gap-5 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                @foreach($this->quickActions as $index => $action)
                    @php
                        $colors = [
                            'emerald' => ['bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-950/30 dark:to-emerald-900/20', 'text-emerald-700 dark:text-emerald-400', 'hover:from-emerald-100 hover:to-emerald-200 dark:hover:from-emerald-900/40 dark:hover:to-emerald-800/30', 'border-emerald-300/50 dark:border-emerald-700/50', 'hover:border-emerald-400/70 dark:hover:border-emerald-600/70', 'shadow-emerald-200/50 dark:shadow-emerald-900/30'],
                            'blue' => ['bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/30 dark:to-blue-900/20', 'text-blue-700 dark:text-blue-400', 'hover:from-blue-100 hover:to-blue-200 dark:hover:from-blue-900/40 dark:hover:to-blue-800/30', 'border-blue-300/50 dark:border-blue-700/50', 'hover:border-blue-400/70 dark:hover:border-blue-600/70', 'shadow-blue-200/50 dark:shadow-blue-900/30'],
                            'violet' => ['bg-gradient-to-br from-violet-50 to-violet-100 dark:from-violet-950/30 dark:to-violet-900/20', 'text-violet-700 dark:text-violet-400', 'hover:from-violet-100 hover:to-violet-200 dark:hover:from-violet-900/40 dark:hover:to-violet-800/30', 'border-violet-300/50 dark:border-violet-700/50', 'hover:border-violet-400/70 dark:hover:border-violet-600/70', 'shadow-violet-200/50 dark:shadow-violet-900/30'],
                            'amber' => ['bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-950/30 dark:to-amber-900/20', 'text-amber-700 dark:text-amber-400', 'hover:from-amber-100 hover:to-amber-200 dark:hover:from-amber-900/40 dark:hover:to-amber-800/30', 'border-amber-300/50 dark:border-amber-700/50', 'hover:border-amber-400/70 dark:hover:border-amber-600/70', 'shadow-amber-200/50 dark:shadow-amber-900/30'],
                            'rose' => ['bg-gradient-to-br from-rose-50 to-rose-100 dark:from-rose-950/30 dark:to-rose-900/20', 'text-rose-700 dark:text-rose-400', 'hover:from-rose-100 hover:to-rose-200 dark:hover:from-rose-900/40 dark:hover:to-rose-800/30', 'border-rose-300/50 dark:border-rose-700/50', 'hover:border-rose-400/70 dark:hover:border-rose-600/70', 'shadow-rose-200/50 dark:shadow-rose-900/30'],
                            'indigo' => ['bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-950/30 dark:to-indigo-900/20', 'text-indigo-700 dark:text-indigo-400', 'hover:from-indigo-100 hover:to-indigo-200 dark:hover:from-indigo-900/40 dark:hover:to-indigo-800/30', 'border-indigo-300/50 dark:border-indigo-700/50', 'hover:border-indigo-400/70 dark:hover:border-indigo-600/70', 'shadow-indigo-200/50 dark:shadow-indigo-900/30'],
                        ];
                        $colorKeys = array_keys($colors);
                        $colorSet = $colors[$colorKeys[$index % count($colorKeys)]];
                    @endphp
                    
                    @if($action['type'] === 'modal')
                        <button 
                           wire:click="{{ $action['action'] }}" 
                           class="group relative flex flex-col items-center justify-center p-4 sm:p-5 {{ $colorSet[0] }} rounded-xl border {{ $colorSet[3] }} {{ $colorSet[2] }} {{ $colorSet[4] }} transition-all duration-300 min-h-[90px] sm:min-h-[110px] hover:shadow-lg hover:shadow-{{ $colorSet[5] ?? 'stone-200/50' }} hover:scale-[1.02] hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 dark:focus:ring-offset-stone-800 backdrop-blur-sm"
                           title="{{ $action['label'] }}">
                            
                            <!-- Action Icon -->
                            <div class="flex items-center justify-center w-12 h-12 sm:w-14 sm:h-14 mb-2 sm:mb-3 rounded-full bg-white/80 dark:bg-stone-800/80 backdrop-blur-sm shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-110">
                                <x-dynamic-component :component="$action['icon']" class="h-6 w-6 sm:h-7 sm:w-7 {{ $colorSet[1] }} group-hover:scale-110 transition-transform duration-300" />
                            </div>
                            
                            <!-- Action Label -->
                            <span class="text-xs sm:text-sm font-bold text-center {{ $colorSet[1] }} leading-tight transition-all duration-300 group-hover:scale-105">
                                {{ $action['label'] }}
                            </span>
                            
                            <!-- Shimmer Effect -->
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-transparent via-white/20 to-transparent opacity-0 group-hover:opacity-100 group-hover:animate-pulse transition-opacity duration-500 pointer-events-none"></div>
                        </button>
                    @else
                        <a href="{{ route($action['route']) }}" 
                           wire:navigate 
                           class="group relative flex flex-col items-center justify-center p-4 sm:p-5 {{ $colorSet[0] }} rounded-xl border {{ $colorSet[3] }} {{ $colorSet[2] }} {{ $colorSet[4] }} transition-all duration-300 min-h-[90px] sm:min-h-[110px] hover:shadow-lg hover:shadow-{{ $colorSet[5] ?? 'stone-200/50' }} hover:scale-[1.02] hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 dark:focus:ring-offset-stone-800 backdrop-blur-sm"
                           title="{{ $action['label'] }}">
                            
                            <!-- Action Icon -->
                            <div class="flex items-center justify-center w-12 h-12 sm:w-14 sm:h-14 mb-2 sm:mb-3 rounded-full bg-white/80 dark:bg-stone-800/80 backdrop-blur-sm shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-110">
                                <x-dynamic-component :component="$action['icon']" class="h-6 w-6 sm:h-7 sm:w-7 {{ $colorSet[1] }} group-hover:scale-110 transition-transform duration-300" />
                            </div>
                            
                            <!-- Action Label -->
                            <span class="text-xs sm:text-sm font-bold text-center {{ $colorSet[1] }} leading-tight transition-all duration-300 group-hover:scale-105">
                                {{ $action['label'] }}
                            </span>
                            
                            <!-- Shimmer Effect -->
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-transparent via-white/20 to-transparent opacity-0 group-hover:opacity-100 group-hover:animate-pulse transition-opacity duration-500 pointer-events-none"></div>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-4 sm:gap-5 md:gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <!-- Enhanced stat cards with better shadows and hover effects -->
        <div class="group bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 p-4 sm:p-5 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Total Items</p>
                    <p class="text-xl sm:text-2xl font-bold text-stone-900 dark:text-stone-100 mt-1">{{ number_format($this->stats['total_items']) }}</p>
                    <div class="flex items-center mt-1">
                        <span class="text-xs text-green-600 dark:text-green-400 font-medium">+12.5%</span>
                        <span class="text-xs text-stone-500 dark:text-stone-400 ml-1">vs last month</span>
                    </div>
                </div>
                <div class="flex-shrink-0 p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl group-hover:scale-110 transition-transform duration-300">
                    <x-flux::icon.box class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </div>

        <div class="group bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 p-4 sm:p-5 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Total Value</p>
                    <p class="text-xl sm:text-2xl font-bold text-stone-900 dark:text-stone-100 mt-1">₱{{ number_format($this->stats['total_value'] / 1000000, 2) }}M</p>
                    <div class="flex items-center mt-1">
                        <span class="text-xs text-green-600 dark:text-green-400 font-medium">+8.2%</span>
                        <span class="text-xs text-stone-500 dark:text-stone-400 ml-1">vs last month</span>
                    </div>
                </div>
                <div class="flex-shrink-0 p-3 bg-green-100 dark:bg-green-900/30 rounded-xl group-hover:scale-110 transition-transform duration-300">
                    <x-flux::icon.receipt-percent class="h-6 w-6 text-green-600 dark:text-green-400" />
                </div>
            </div>
        </div>

        <div class="group bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 p-4 sm:p-5 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Active Users</p>
                    <p class="text-xl sm:text-2xl font-bold text-stone-900 dark:text-stone-100 mt-1">{{ $this->stats['active_users'] }}</p>
                    <div class="flex items-center mt-1">
                        <span class="text-xs text-stone-500 dark:text-stone-400">System users</span>
                    </div>
                </div>
                <div class="flex-shrink-0 p-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl group-hover:scale-110 transition-transform duration-300">
                    <x-flux::icon.users class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                </div>
            </div>
        </div>

        <div class="group bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 p-4 sm:p-5 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Pending Actions</p>
                    <p class="text-xl sm:text-2xl font-bold text-stone-900 dark:text-stone-100 mt-1">{{ $this->stats['pending_actions'] }}</p>
                    <div class="flex items-center mt-1">
                        <span class="text-xs text-amber-600 dark:text-amber-400">Needs attention</span>
                    </div>
                </div>
                <div class="flex-shrink-0 p-3 bg-amber-100 dark:bg-amber-900/30 rounded-xl group-hover:scale-110 transition-transform duration-300">
                    <x-flux::icon.exclamation-triangle class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                </div>
            </div>
        </div>

        <div class="group bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 p-4 sm:p-5 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Expiring Soon</p>
                    <p class="text-xl sm:text-2xl font-bold text-stone-900 dark:text-stone-100 mt-1">{{ $this->stats['expiring_soon'] }}</p>
                    <div class="flex items-center mt-1">
                        <span class="text-xs text-red-600 dark:text-red-400">Within 30 days</span>
                    </div>
                </div>
                <div class="flex-shrink-0 p-3 bg-red-100 dark:bg-red-900/30 rounded-xl group-hover:scale-110 transition-transform duration-300">
                    <x-flux::icon.clock class="h-6 w-6 text-red-600 dark:text-red-400" />
                </div>
            </div>
        </div>

        <div class="group bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 p-4 sm:p-5 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Total Divisions</p>
                    <p class="text-xl sm:text-2xl font-bold text-stone-900 dark:text-stone-100 mt-1">{{ $this->stats['total_divisions'] }}</p>
                    <div class="flex items-center mt-1">
                        <span class="text-xs text-stone-500 dark:text-stone-400">Offices/Units</span>
                    </div>
                </div>
                <div class="flex-shrink-0 p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl group-hover:scale-110 transition-transform duration-300">
                    <x-flux::icon.building-office class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                </div>
            </div>
        </div>
    </div>
    
    <!-- Secondary Stats -->
    <div class="grid grid-cols-1 gap-4 sm:gap-5 md:gap-6 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($this->secondaryStats as $stat)
            <div class="group relative p-4 sm:p-5 bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 flex items-start space-x-4 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                <div class="flex-shrink-0">
                    <div class="flex items-center justify-center h-12 w-12 sm:h-14 sm:w-14 rounded-xl bg-gradient-to-br from-stone-100 to-stone-200 dark:from-stone-700 dark:to-stone-800 group-hover:scale-110 transition-transform duration-300">
                        <x-dynamic-component :component="$stat['icon']" :class="'h-6 w-6 sm:h-7 sm:w-7 ' . $stat['color']" />
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-xs sm:text-sm font-semibold text-stone-500 dark:text-stone-400 uppercase tracking-wider truncate">{{ $stat['label'] }}</h4>
                    <p class="text-lg sm:text-2xl font-bold text-stone-900 dark:text-stone-100 mt-1">{{ $stat['value'] }} <span class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ $stat['unit'] }}</span></p>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1 flex items-center">
                        @if($stat['change'])
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ str_starts_with($stat['change'], '+') ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                                {{ $stat['change'] }}
                            </span>
                            <span class="ml-2">vs. last period</span>
                        @else
                            <span>Current period</span>
                        @endif
                    </p>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Analytics & Reports -->
    <div class="bg-white dark:bg-stone-800/90 backdrop-blur-sm rounded-lg shadow-lg border border-stone-200 dark:border-stone-700/70 hover:shadow-xl transition-all duration-300">
        <div class="p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4 sm:mb-6">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-stone-900 dark:text-stone-100 flex items-center">
                        <div class="w-2 h-7 bg-gradient-to-b from-blue-500 to-blue-600 rounded-full mr-3"></div>
                        Analytics & Reports
                    </h2>
                    <p class="text-sm text-stone-500 dark:text-stone-400 mt-1 ml-5">Real-time insights and data visualization</p>
                </div>
                <div class="flex items-center px-3 py-1.5 bg-stone-100 dark:bg-stone-700/50 rounded-full text-xs text-stone-500 dark:text-stone-400">
                    <x-flux::icon.chart-bar class="h-4 w-4 mr-1" />
                    <span class="hidden sm:inline font-medium">Live Data</span>
                </div>
            </div>
            
            <div class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-2">
                <!-- Chart 1: Inventory Value Over Time -->
                <div class="group bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 p-3 sm:p-4 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm sm:text-base font-bold text-stone-900 dark:text-stone-100 flex items-center">
                            <div class="w-2 h-4 bg-gradient-to-b from-blue-500 to-blue-600 rounded-full mr-2"></div>
                            Inventory Value Over Time
                        </h3>
                        <div class="flex items-center px-2 py-1 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                            <x-flux::icon.arrows-trending-up class="h-3 w-3 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                    <div class="relative">
                        <!-- Line Chart Container with wire:ignore to prevent Livewire from morphing -->
                        <div wire:ignore class="h-56 sm:h-72 lg:h-80 xl:h-96 relative bg-white/50 dark:bg-stone-900/30 rounded-lg p-2 backdrop-blur-sm border border-stone-200/30 dark:border-stone-700/30">
                            <canvas id="line-chart-canvas" class="w-full h-full"></canvas>
                        </div>
                        <!-- Compact Chart Legend -->
                        <div class="flex flex-wrap justify-center mt-3 gap-2 p-2 bg-stone-50/80 dark:bg-stone-800/50 rounded-lg backdrop-blur-sm">
                            <div class="flex items-center px-2 py-1 bg-white dark:bg-stone-700 rounded-full shadow-sm">
                                <div class="w-2 h-2 bg-blue-500 rounded-full mr-1.5"></div>
                                <span class="text-xs font-medium text-stone-700 dark:text-stone-300">Total</span>
                            </div>
                            <div class="flex items-center px-2 py-1 bg-white dark:bg-stone-700 rounded-full shadow-sm">
                                <div class="w-2 h-2 bg-green-500 rounded-full mr-1.5"></div>
                                <span class="text-xs font-medium text-stone-700 dark:text-stone-300">ICS</span>
                            </div>
                            <div class="flex items-center px-2 py-1 bg-white dark:bg-stone-700 rounded-full shadow-sm">
                                <div class="w-2 h-2 bg-yellow-500 rounded-full mr-1.5"></div>
                                <span class="text-xs font-medium text-stone-700 dark:text-stone-300">PAR</span>
                            </div>
                            <div class="flex items-center px-2 py-1 bg-white dark:bg-stone-700 rounded-full shadow-sm">
                                <div class="w-2 h-2 bg-purple-500 rounded-full mr-1.5"></div>
                                <span class="text-xs font-medium text-stone-700 dark:text-stone-300">IDR</span>
                            </div>
                            <div class="flex items-center px-2 py-1 bg-white dark:bg-stone-700 rounded-full shadow-sm">
                                <div class="w-2 h-2 bg-red-500 rounded-full mr-1.5"></div>
                                <span class="text-xs font-medium text-stone-700 dark:text-stone-300">Consumables</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart 2: Item Distribution by Category -->
                <div class="group bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 p-3 sm:p-4 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm sm:text-base font-bold text-stone-900 dark:text-stone-100 flex items-center">
                            <div class="w-2 h-4 bg-gradient-to-b from-emerald-500 to-emerald-600 rounded-full mr-2"></div>
                            Category Distribution
                        </h3>
                        <div class="flex items-center px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 rounded-full">
                            <x-flux::icon.chart-bar class="h-3 w-3 text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </div>
                    <div class="h-64 sm:h-80 lg:h-96 xl:h-[28rem] flex items-stretch">
                        <div class="flex w-full h-full">
                            <!-- Large Donut Chart Container with wire:ignore to prevent Livewire from morphing -->
                            <div wire:ignore class="flex-shrink-0 flex items-center justify-center bg-white/50 dark:bg-stone-900/30 rounded-lg p-2 backdrop-blur-sm border border-stone-200/30 dark:border-stone-700/30" style="width: 65%; height: 100%;">
                                <canvas id="donut-chart-canvas" class="w-full h-full"></canvas>
                            </div>
                            <!-- Compact Vertical Legend - Fixed Height, No Scroll -->
                            <div class="flex-1 ml-2 flex flex-col h-full bg-stone-50/80 dark:bg-stone-800/50 rounded-lg p-2 backdrop-blur-sm">
                                <!-- Summary Stats -->
                                <div class="text-center border-b border-stone-200 dark:border-stone-600 pb-2 mb-2 bg-gradient-to-r from-blue-50 to-emerald-50 dark:from-blue-900/20 dark:to-emerald-900/20 rounded-md p-2 flex-shrink-0">
                                    <div class="text-base sm:text-lg font-bold text-stone-900 dark:text-stone-100">{{ number_format($this->categoryDistribution->sum('count')) }}</div>
                                    <div class="text-xs font-medium text-stone-600 dark:text-stone-400">Total Items</div>
                                </div>
                                <!-- Category List - Flexible to fill remaining space -->
                                <div class="flex-1 flex flex-col justify-center space-y-1">
                                    @foreach($this->categoryDistribution->take(6) as $index => $category)
                                        <div class="flex items-center justify-between bg-white dark:bg-stone-700 rounded-md p-2 shadow-sm">
                                            <div class="flex items-center min-w-0 flex-1">
                                                <div class="w-2.5 h-2.5 rounded-full mr-2 flex-shrink-0" style="background-color: {{ ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'][$index % 6] }}"></div>
                                                <span class="text-stone-800 dark:text-stone-200 font-medium truncate text-xs">{{ Str::limit($category['name'], 11) }}</span>
                                            </div>
                                            <div class="flex flex-col items-end ml-2">
                                                <span class="text-stone-600 dark:text-stone-400 font-bold text-xs">{{ $category['percentage'] }}%</span>
                                                <span class="text-stone-500 dark:text-stone-500 text-xs">{{ number_format($category['count']) }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                    @if($this->categoryDistribution->count() > 6)
                                        <div class="text-center text-xs text-stone-500 dark:text-stone-400 py-1.5 bg-white dark:bg-stone-700 rounded-md border border-stone-200 dark:border-stone-600">
                                            <span class="font-medium">{{ $this->categoryDistribution->count() - 6 }} more categories</span>
                                            <div class="text-xs text-stone-400 dark:text-stone-500 mt-0.5">
                                                {{ number_format($this->categoryDistribution->skip(6)->sum('count')) }} items
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart 3: Inventory System Breakdown -->
                <div class="group bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 p-3 sm:p-4 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm sm:text-base font-bold text-stone-900 dark:text-stone-100 flex items-center">
                            <div class="w-2 h-4 bg-gradient-to-b from-purple-500 to-purple-600 rounded-full mr-2"></div>
                            System Breakdown
                        </h3>
                        <div class="flex items-center px-2 py-1 bg-purple-100 dark:bg-purple-900/30 rounded-full">
                            <x-flux::icon.squares-2x2 class="h-3 w-3 text-purple-600 dark:text-purple-400" />
                        </div>
                    </div>
                    <div class="relative">
                        <!-- Bar Chart Container with wire:ignore to prevent Livewire from morphing -->
                        <div wire:ignore class="h-56 sm:h-72 lg:h-80 xl:h-96 relative bg-white/50 dark:bg-stone-900/30 rounded-lg p-2 backdrop-blur-sm border border-stone-200/30 dark:border-stone-700/30">
                            <canvas id="inventory-system-chart-canvas" class="w-full h-full"></canvas>
                        </div>
                        <!-- Compact Chart Legend -->
                        <div class="flex flex-wrap justify-center mt-3 gap-2 p-2 bg-stone-50/80 dark:bg-stone-800/50 rounded-lg backdrop-blur-sm">
                            @foreach($this->inventorySystemBreakdown as $system)
                                <div class="flex items-center px-2 py-1 bg-white dark:bg-stone-700 rounded-full shadow-sm">
                                    <div class="w-2 h-2 rounded-full mr-1.5" style="background-color: {{ $system['color'] }}"></div>
                                    <span class="text-xs font-medium text-stone-700 dark:text-stone-300">{{ $system['system'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Chart 4: Top Suppliers Spending -->
                <div class="group bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 p-3 sm:p-4 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm sm:text-base font-bold text-stone-900 dark:text-stone-100 flex items-center">
                            <div class="w-2 h-4 bg-gradient-to-b from-amber-500 to-amber-600 rounded-full mr-2"></div>
                            Top Suppliers
                        </h3>
                        <div class="flex items-center px-2 py-1 bg-amber-100 dark:bg-amber-900/30 rounded-full">
                            <x-flux::icon.truck class="h-3 w-3 text-amber-600 dark:text-amber-400" />
                        </div>
                    </div>
                    <div class="relative">
                        <!-- Horizontal Bar Chart Container with wire:ignore to prevent Livewire from morphing -->
                        <div wire:ignore class="h-56 sm:h-72 lg:h-80 xl:h-96 relative bg-white/50 dark:bg-stone-900/30 rounded-lg p-2 backdrop-blur-sm border border-stone-200/30 dark:border-stone-700/30">
                            <canvas id="top-suppliers-chart-canvas" class="w-full h-full"></canvas>
                        </div>
                        <div class="mt-3 text-center">
                            <div class="inline-flex items-center px-2 py-1 bg-gradient-to-r from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 rounded-full">
                                <x-flux::icon.star class="h-3 w-3 text-amber-600 dark:text-amber-400 mr-1" />
                                <span class="text-xs font-medium text-amber-700 dark:text-amber-300">Top 10 by spending</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Initialization Script -->
    <script>
        document.addEventListener('livewire:init', () => {
            // Line Chart Event Handlers
            Livewire.on('initializeLineChart', (data) => {
                const chartData = data[0].data;
                
                const datasets = [{
                    label: 'Total Value',
                    data: chartData.map(d => d.value),
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4
                }, {
                    label: 'ICS',
                    data: chartData.map(d => d.ics),
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                }, {
                    label: 'PAR',
                    data: chartData.map(d => d.par),
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                }, {
                    label: 'IDR',
                    data: chartData.map(d => d.idr),
                    borderColor: '#8B5CF6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                }, {
                    label: 'Consumables',
                    data: chartData.map(d => d.consumables),
                    borderColor: '#EF4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                }];
                
                const lineChartData = {
                    labels: chartData.map(d => d.month),
                    datasets: datasets
                };
                
                const lineChartOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: 10
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: 10
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                };
                
                window.initializeChart(data[0].chartId, 'line', lineChartData, lineChartOptions);
            });
            
            // Donut Chart Event Handlers  
            Livewire.on('initializeDoughnutChart', (data) => {
                const categoryData = data[0].data;
                const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16'];
                
                const doughnutChartData = {
                    labels: categoryData.map(d => d.name),
                    datasets: [{
                        data: categoryData.map(d => d.count),
                        backgroundColor: colors.slice(0, categoryData.length),
                        borderWidth: 3,
                        borderColor: '#ffffff'
                    }]
                };
                
                const doughnutChartOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const percentage = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percent = ((value / percentage) * 100).toFixed(1);
                                    return `${label}: ${value.toLocaleString()} items (${percent}%)`;
                                }
                            }
                        }
                    },
                    cutout: '65%',
                    elements: {
                        arc: {
                            borderWidth: 3,
                            borderColor: '#ffffff',
                            hoverBorderWidth: 4
                        }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                };
                
                window.initializeChart(data[0].chartId, 'doughnut', doughnutChartData, doughnutChartOptions);
            });
            
            // Update Event Handlers
            Livewire.on('updateLineChart', (data) => {
                const chartData = data[0].data;
                
                const datasets = [{
                    label: 'Total Value',
                    data: chartData.map(d => d.value),
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4
                }, {
                    label: 'ICS',
                    data: chartData.map(d => d.ics),
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                }, {
                    label: 'PAR',
                    data: chartData.map(d => d.par),
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                }, {
                    label: 'IDR',
                    data: chartData.map(d => d.idr),
                    borderColor: '#8B5CF6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                }, {
                    label: 'Consumables',
                    data: chartData.map(d => d.consumables),
                    borderColor: '#EF4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                }];
                
                const newData = {
                    labels: chartData.map(d => d.month),
                    datasets: datasets
                };
                
                window.updateChart(data[0].chartId, newData);
            });
            
            Livewire.on('updateDoughnutChart', (data) => {
                const categoryData = data[0].data;
                const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16'];
                
                const newData = {
                    labels: categoryData.map(d => d.name),
                    datasets: [{
                        data: categoryData.map(d => d.count),
                        backgroundColor: colors.slice(0, categoryData.length),
                        borderWidth: 3,
                        borderColor: '#ffffff'
                    }]
                };
                
                const doughnutUpdateOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const percentage = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percent = ((value / percentage) * 100).toFixed(1);
                                    return `${label}: ${value.toLocaleString()} items (${percent}%)`;
                                }
                            }
                        }
                    },
                    cutout: '65%',
                    elements: {
                        arc: {
                            borderWidth: 3,
                            borderColor: '#ffffff',
                            hoverBorderWidth: 4
                        }
                    }
                };
                
                window.updateChart(data[0].chartId, newData, doughnutUpdateOptions);
            });

            // Inventory System Breakdown Chart Event Handlers
            Livewire.on('initializeInventorySystemChart', (data) => {
                const systemData = data[0].data;
                
                const barChartData = {
                    labels: ['Quantity', 'Value (₱M)'],
                    datasets: systemData.map(system => ({
                        label: system.system,
                        data: [system.quantity, (system.value / 1000000).toFixed(2)],
                        backgroundColor: system.color,
                        borderColor: system.color,
                        borderWidth: 1
                    }))
                };
                
                const options = {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value, index, values) {
                                    return value.toLocaleString();
                                },
                                font: {
                                    size: 11
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                };
                
                window.initializeChart(data[0].chartId, 'bar', barChartData, options);
            });
            
            Livewire.on('updateInventorySystemChart', (data) => {
                const systemData = data[0].data;
                
                const newData = {
                    labels: ['Quantity', 'Value (₱M)'],
                    datasets: systemData.map(system => ({
                        label: system.system,
                        data: [system.quantity, (system.value / 1000000).toFixed(2)],
                        backgroundColor: system.color,
                        borderColor: system.color,
                        borderWidth: 1
                    }))
                };
                
                window.updateChart(data[0].chartId, newData);
            });

            // Top Suppliers Chart Event Handlers
            Livewire.on('initializeTopSuppliersChart', (data) => {
                const suppliersData = data[0].data;
                const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#06B6D4'];
                
                const horizontalBarData = {
                    labels: suppliersData.map(supplier => supplier.name.length > 20 ? supplier.name.substring(0, 20) + '...' : supplier.name),
                    datasets: [{
                        label: 'Total Spent (₱)',
                        data: suppliersData.map(supplier => supplier.total_spent),
                        backgroundColor: colors.slice(0, suppliersData.length),
                        borderColor: colors.slice(0, suppliersData.length),
                        borderWidth: 1
                    }]
                };
                
                const options = {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    interaction: {
                        intersect: false,
                        mode: 'y'
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + (value / 1000000).toFixed(1) + 'M';
                                },
                                font: {
                                    size: 10
                                }
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 10
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                };
                
                window.initializeChart(data[0].chartId, 'bar', horizontalBarData, options);
            });
            
            Livewire.on('updateTopSuppliersChart', (data) => {
                const suppliersData = data[0].data;
                const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#06B6D4'];
                
                const newData = {
                    labels: suppliersData.map(supplier => supplier.name.length > 20 ? supplier.name.substring(0, 20) + '...' : supplier.name),
                    datasets: [{
                        label: 'Total Spent (₱)',
                        data: suppliersData.map(supplier => supplier.total_spent),
                        backgroundColor: colors.slice(0, suppliersData.length),
                        borderColor: colors.slice(0, suppliersData.length),
                        borderWidth: 1
                    }]
                };
                
                window.updateChart(data[0].chartId, newData);
            });


        });
    </script>

    <!-- Toast Notification -->
    <div 
        x-data="{ 
            show: false, 
            message: '', 
            type: 'success',
            showToast(msg, toastType = 'success') {
                this.message = msg;
                this.type = toastType;
                this.show = true;
                setTimeout(() => this.show = false, 3000);
            }
        }"
        x-on:alert-refreshed.window="showToast('System alerts refreshed successfully!', 'success')"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed top-4 right-4 z-50 max-w-sm w-full bg-white dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-lg shadow-lg p-4"
        x-cloak
    >
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <div x-show="type === 'success'" class="flex items-center justify-center h-8 w-8 rounded-full bg-green-100 dark:bg-green-900/40">
                    <x-flux::icon.check class="h-4 w-4 text-green-600 dark:text-green-400" />
                </div>
                <div x-show="type === 'error'" class="flex items-center justify-center h-8 w-8 rounded-full bg-red-100 dark:bg-red-900/40">
                    <x-flux::icon.x-mark class="h-4 w-4 text-red-600 dark:text-red-400" />
                </div>
            </div>
            <div class="ml-3 w-0 flex-1 pt-0.5">
                <p class="text-sm font-medium text-stone-900 dark:text-stone-100" x-text="message"></p>
            </div>
            <div class="ml-4 flex-shrink-0 flex">
                <button 
                    @click="show = false"
                    class="bg-white dark:bg-stone-800 rounded-md inline-flex text-stone-400 hover:text-stone-500 focus:outline-none"
                >
                    <x-flux::icon.x-mark class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-stone-200 dark:border-stone-700">
        <nav class="flex -mb-px overflow-x-auto scrollbar-hide" aria-label="Tabs">
            <a href="#" wire:click.prevent="setTab('overview')" class="whitespace-nowrap py-3 sm:py-4 px-2 sm:px-3 border-b-2 font-medium text-xs sm:text-sm {{ $tab === 'overview' ? 'text-accent dark:text-accent-content border-accent' : 'text-stone-500 hover:text-stone-700 hover:border-stone-300 dark:text-stone-400 dark:hover:text-stone-200 dark:hover:border-stone-600 border-transparent' }} mr-4 sm:mr-8" @if($tab === 'overview') aria-current="page" @endif>
                Overview
            </a>
            <a href="#" wire:click.prevent="setTab('item-categories')" class="whitespace-nowrap py-3 sm:py-4 px-2 sm:px-3 border-b-2 font-medium text-xs sm:text-sm {{ $tab === 'item-categories' ? 'text-accent dark:text-accent-content border-accent' : 'text-stone-500 hover:text-stone-700 hover:border-stone-300 dark:text-stone-400 dark:hover:text-stone-200 dark:hover:border-stone-600 border-transparent' }} mr-4 sm:mr-8" @if($tab === 'item-categories') aria-current="page" @endif>
                Categories
            </a>
            <a href="#" wire:click.prevent="setTab('suppliers')" class="whitespace-nowrap py-3 sm:py-4 px-2 sm:px-3 border-b-2 font-medium text-xs sm:text-sm {{ $tab === 'suppliers' ? 'text-accent dark:text-accent-content border-accent' : 'text-stone-500 hover:text-stone-700 hover:border-stone-300 dark:text-stone-400 dark:hover:text-stone-200 dark:hover:border-stone-600 border-transparent' }} mr-4 sm:mr-8" @if($tab === 'suppliers') aria-current="page" @endif>
                Suppliers
            </a>
            <a href="#" wire:click.prevent="setTab('recent-activity')" class="whitespace-nowrap py-3 sm:py-4 px-2 sm:px-3 border-b-2 font-medium text-xs sm:text-sm {{ $tab === 'recent-activity' ? 'text-accent dark:text-accent-content border-accent' : 'text-stone-500 hover:text-stone-700 hover:border-stone-300 dark:text-stone-400 dark:hover:text-stone-200 dark:hover:border-stone-600 border-transparent' }} mr-4 sm:mr-8" @if($tab === 'recent-activity') aria-current="page" @endif>
                Activity
            </a>
             <a href="#" wire:click.prevent="setTab('user-management')" class="whitespace-nowrap py-3 sm:py-4 px-2 sm:px-3 border-b-2 font-medium text-xs sm:text-sm {{ $tab === 'user-management' ? 'text-accent dark:text-accent-content border-accent' : 'text-stone-500 hover:text-stone-700 hover:border-stone-300 dark:text-stone-400 dark:hover:text-stone-200 dark:hover:border-stone-600 border-transparent' }}" @if($tab === 'user-management') aria-current="page" @endif>
                Users
            </a>
        </nav>
    </div>

    @if ($tab === 'overview')
    <!-- Division Inventory Overview -->
    <div class="bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 backdrop-blur-sm">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg sm:text-xl font-bold text-stone-900 dark:text-stone-100 flex items-center">
                        <div class="w-2 h-6 bg-gradient-to-b from-blue-500 to-blue-600 rounded-full mr-3"></div>
                Division Inventory Overview
            </h3>
                    <p class="text-sm text-stone-500 dark:text-stone-400 mt-1 ml-5">Real-time inventory distribution across divisions</p>
                </div>
                <div class="flex items-center px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 rounded-full text-xs text-blue-700 dark:text-blue-300">
                    <x-flux::icon.building-2 class="h-4 w-4 mr-1" />
                    <span class="hidden sm:inline font-medium">{{ count($this->divisionInventory) }} Divisions</span>
                </div>
            </div>
            
            <div class="overflow-hidden rounded-lg border border-stone-200/50 dark:border-stone-700/50 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200/50 dark:divide-stone-700/50">
                        <thead class="bg-gradient-to-r from-stone-50 to-stone-100 dark:from-stone-900 dark:to-stone-800/50">
                            <tr>
                                <th class="px-4 sm:px-6 py-4 text-left text-xs font-bold text-stone-600 dark:text-stone-300 uppercase tracking-wider">
                                    <div class="flex items-center space-x-1">
                                        <x-flux::icon.building-2 class="h-4 w-4" />
                                        <span>Division</span>
                                    </div>
                                </th>
                                <th class="px-4 sm:px-6 py-4 text-left text-xs font-bold text-stone-600 dark:text-stone-300 uppercase tracking-wider">
                                    <div class="flex items-center space-x-1">
                                        <x-flux::icon.package class="h-4 w-4" />
                                        <span>Total</span>
                                    </div>
                                </th>
                                <th class="px-4 sm:px-6 py-4 text-left text-xs font-bold text-stone-600 dark:text-stone-300 uppercase tracking-wider hidden sm:table-cell">
                                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 rounded-md text-green-700 dark:text-green-400 text-xs font-semibold">ICS</span>
                                </th>
                                <th class="px-4 sm:px-6 py-4 text-left text-xs font-bold text-stone-600 dark:text-stone-300 uppercase tracking-wider hidden sm:table-cell">
                                    <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 rounded-md text-yellow-700 dark:text-yellow-400 text-xs font-semibold">PAR</span>
                                </th>
                                <th class="px-4 sm:px-6 py-4 text-left text-xs font-bold text-stone-600 dark:text-stone-300 uppercase tracking-wider hidden md:table-cell">
                                    <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 rounded-md text-purple-700 dark:text-purple-400 text-xs font-semibold">IDR</span>
                                </th>
                                <th class="px-4 sm:px-6 py-4 text-left text-xs font-bold text-stone-600 dark:text-stone-300 uppercase tracking-wider hidden md:table-cell">
                                    <span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 rounded-md text-red-700 dark:text-red-400 text-xs font-semibold">Consumables</span>
                                </th>
                                <th class="px-4 sm:px-6 py-4 text-left text-xs font-bold text-stone-600 dark:text-stone-300 uppercase tracking-wider">
                                    <div class="flex items-center space-x-1">
                                        <x-flux::icon.shield-check class="h-4 w-4" />
                                        <span>Status</span>
                                    </div>
                                </th>
                        </tr>
                    </thead>
                        <tbody class="bg-white/50 dark:bg-stone-800/50 backdrop-blur-sm divide-y divide-stone-200/30 dark:divide-stone-700/30">
                        @forelse($this->divisionInventory as $division)
                                <tr class="group hover:bg-gradient-to-r hover:from-blue-50/50 hover:to-indigo-50/50 dark:hover:from-blue-950/20 dark:hover:to-indigo-950/20 transition-all duration-200 hover:shadow-sm">
                                    <td class="px-4 sm:px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/40 dark:to-indigo-900/40 flex items-center justify-center group-hover:scale-105 transition-transform duration-200">
                                                    <x-flux::icon.building-2 class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                                </div>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-bold text-stone-900 dark:text-stone-100 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors">{{ $division['name'] }}</div>
                                    <div class="text-xs text-stone-500 dark:text-stone-400 sm:hidden mt-1">
                                                    <div class="flex flex-wrap gap-2">
                                                        <span class="px-1.5 py-0.5 bg-green-100 dark:bg-green-900/30 rounded text-green-700 dark:text-green-400">ICS: {{ number_format($division['ics']) }}</span>
                                                        <span class="px-1.5 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 rounded text-yellow-700 dark:text-yellow-400">PAR: {{ number_format($division['par']) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                    </div>
                                </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <div class="flex items-center">
                                            <span class="text-lg font-bold text-stone-900 dark:text-stone-100">{{ number_format($division['total_items']) }}</span>
                                            <span class="ml-2 text-xs text-stone-500 dark:text-stone-400">items</span>
                                        </div>
                                </td>
                                    <td class="px-4 sm:px-6 py-4 hidden sm:table-cell">
                                        <div class="flex items-center">
                                            <div class="flex-1">
                                                <div class="text-sm font-semibold text-green-700 dark:text-green-400">{{ number_format($division['ics']) }}</div>
                                                @php $icsPercentage = $division['total_items'] > 0 ? ($division['ics'] / $division['total_items']) * 100 : 0; @endphp
                                                <div class="w-full bg-stone-200 dark:bg-stone-700 rounded-full h-1.5 mt-1">
                                                    <div class="bg-gradient-to-r from-green-400 to-green-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $icsPercentage }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                </td>
                                    <td class="px-4 sm:px-6 py-4 hidden sm:table-cell">
                                        <div class="flex items-center">
                                            <div class="flex-1">
                                                <div class="text-sm font-semibold text-yellow-700 dark:text-yellow-400">{{ number_format($division['par']) }}</div>
                                                @php $parPercentage = $division['total_items'] > 0 ? ($division['par'] / $division['total_items']) * 100 : 0; @endphp
                                                <div class="w-full bg-stone-200 dark:bg-stone-700 rounded-full h-1.5 mt-1">
                                                    <div class="bg-gradient-to-r from-yellow-400 to-yellow-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $parPercentage }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                </td>
                                    <td class="px-4 sm:px-6 py-4 hidden md:table-cell">
                                        <div class="flex items-center">
                                            <div class="flex-1">
                                                <div class="text-sm font-semibold text-purple-700 dark:text-purple-400">{{ number_format($division['idr']) }}</div>
                                                @php $idrPercentage = $division['total_items'] > 0 ? ($division['idr'] / $division['total_items']) * 100 : 0; @endphp
                                                <div class="w-full bg-stone-200 dark:bg-stone-700 rounded-full h-1.5 mt-1">
                                                    <div class="bg-gradient-to-r from-purple-400 to-purple-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $idrPercentage }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                </td>
                                    <td class="px-4 sm:px-6 py-4 hidden md:table-cell">
                                        <div class="flex items-center">
                                            <div class="flex-1">
                                                <div class="text-sm font-semibold text-red-700 dark:text-red-400">{{ number_format($division['consumables']) }}</div>
                                                @php $consumablePercentage = $division['total_items'] > 0 ? ($division['consumables'] / $division['total_items']) * 100 : 0; @endphp
                                                <div class="w-full bg-stone-200 dark:bg-stone-700 rounded-full h-1.5 mt-1">
                                                    <div class="bg-gradient-to-r from-red-400 to-red-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $consumablePercentage }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                </td>
                                    <td class="px-4 sm:px-6 py-4">
                                    @if($division['low_stock'] > 0)
                                            <div class="flex flex-col items-start">
                                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-gradient-to-r from-red-100 to-red-200 text-red-800 dark:from-red-900/40 dark:to-red-800/40 dark:text-red-200 shadow-sm border border-red-200/50 dark:border-red-800/50">
                                                    <x-flux::icon.exclamation-triangle class="h-3 w-3 mr-1.5" />
                                                    {{ $division['low_stock'] }} Low Stock
                                        </span>
                                                <span class="text-xs text-red-600 dark:text-red-400 mt-1 font-medium">Needs attention</span>
                                            </div>
                                    @else
                                            <div class="flex flex-col items-start">
                                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-gradient-to-r from-green-100 to-green-200 text-green-800 dark:from-green-900/40 dark:to-green-800/40 dark:text-green-200 shadow-sm border border-green-200/50 dark:border-green-800/50">
                                                    <x-flux::icon.check-circle class="h-3 w-3 mr-1.5" />
                                                    All Good
                                        </span>
                                                <span class="text-xs text-green-600 dark:text-green-400 mt-1 font-medium">Optimal levels</span>
                                            </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-16 h-16 bg-gradient-to-br from-stone-100 to-stone-200 dark:from-stone-700 dark:to-stone-800 rounded-full flex items-center justify-center mb-4">
                                                <x-flux::icon.building-2 class="h-8 w-8 text-stone-400 dark:text-stone-500" />
                                            </div>
                                            <h3 class="text-sm font-semibold text-stone-700 dark:text-stone-300 mb-2">No divisions found</h3>
                                            <p class="text-xs text-stone-500 dark:text-stone-400 max-w-sm">Create your first division to start organizing your inventory across different departments.</p>
                                        </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if ($tab === 'item-categories')
    <!-- Item Categories Overview -->
    <div class="space-y-4 sm:space-y-6">
        <!-- Category Statistics Cards -->
        <div class="grid grid-cols-1 gap-3 sm:gap-4 lg:gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($this->categoryDistribution->take(3) as $category)
                <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm p-4 sm:p-6 border border-stone-200 dark:border-stone-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-6 h-6 sm:w-8 sm:h-8 rounded-full" style="background-color: {{ ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16'][$loop->index % 8] }}"></div>
                        </div>
                        <div class="ml-3 sm:ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-xs sm:text-sm font-medium text-stone-500 dark:text-stone-400 truncate">{{ $category['name'] }}</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-lg sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">{{ number_format($category['count']) }}</div>
                                    <div class="ml-2 flex items-baseline text-xs sm:text-sm font-semibold text-stone-600 dark:text-stone-400">
                                        {{ $category['percentage'] }}%
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Category Inventory Table -->
        <div class="bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 backdrop-blur-sm">
            <div class="p-4 sm:p-6 lg:p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg sm:text-xl font-bold text-stone-900 dark:text-stone-100 flex items-center">
                            <div class="w-2 h-6 bg-gradient-to-b from-emerald-500 to-emerald-600 rounded-full mr-3"></div>
                Inventory by Category
            </h3>
                        <p class="text-sm text-stone-500 dark:text-stone-400 mt-1 ml-5">Detailed breakdown of items across all categories</p>
                    </div>
                    <div class="flex items-center px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900/30 rounded-full text-xs text-emerald-700 dark:text-emerald-300">
                        <x-flux::icon.tag class="h-4 w-4 mr-1" />
                        <span class="hidden sm:inline font-medium">{{ count($this->categoryInventory) }} Categories</span>
                    </div>
                </div>
                
                <div class="overflow-hidden rounded-lg border border-stone-200/50 dark:border-stone-700/50 shadow-sm">
                <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200/50 dark:divide-stone-700/50">
                            <thead class="bg-gradient-to-r from-emerald-50 to-green-100 dark:from-emerald-900/20 dark:to-green-900/20">
                                <tr>
                                    <th class="px-4 sm:px-6 py-4 text-left text-xs font-bold text-stone-600 dark:text-stone-300 uppercase tracking-wider">
                                        <div class="flex items-center space-x-1">
                                            <x-flux::icon.folder-open class="h-4 w-4" />
                                            <span>Category</span>
                                        </div>
                                    </th>
                                    <th class="px-4 sm:px-6 py-4 text-left text-xs font-bold text-stone-600 dark:text-stone-300 uppercase tracking-wider">
                                        <div class="flex items-center space-x-1">
                                            <x-flux::icon.package class="h-4 w-4" />
                                            <span>Total Items</span>
                                        </div>
                                    </th>
                                    <th class="px-4 sm:px-6 py-4 text-left text-xs font-bold text-stone-600 dark:text-stone-300 uppercase tracking-wider">
                                        <div class="flex items-center space-x-1">
                                            <x-flux::icon.receipt-percent class="h-4 w-4" />
                                            <span>Total Value</span>
                                        </div>
                                    </th>
                                    <th class="px-4 sm:px-6 py-4 text-left text-xs font-bold text-stone-600 dark:text-stone-300 uppercase tracking-wider">
                                        <div class="flex items-center space-x-1">
                                            <x-flux::icon.chart-bar class="h-4 w-4" />
                                            <span>Distribution</span>
                                        </div>
                                    </th>
                            </tr>
                        </thead>
                            <tbody class="bg-white/50 dark:bg-stone-800/50 backdrop-blur-sm divide-y divide-stone-200/30 dark:divide-stone-700/30">
                                @forelse($this->categoryInventory as $index => $category)
                                        @php
                                            $totalItems = collect($this->categoryInventory)->sum('total_items');
                                        $totalValue = collect($this->categoryInventory)->sum('total_value');
                                        $itemPercentage = $totalItems > 0 ? round(($category['total_items'] / $totalItems) * 100, 1) : 0;
                                        $valuePercentage = $totalValue > 0 ? round(($category['total_value'] / $totalValue) * 100, 1) : 0;
                                        $colors = ['emerald', 'blue', 'amber', 'red', 'purple', 'pink', 'cyan', 'indigo'];
                                        $color = $colors[$index % count($colors)];
                                        $colorClasses = [
                                            'emerald' => ['bg' => 'from-emerald-100 to-emerald-200 dark:from-emerald-900/40 dark:to-emerald-800/40', 'text' => 'text-emerald-700 dark:text-emerald-300', 'progress' => 'from-emerald-400 to-emerald-600', 'ring' => 'bg-emerald-500'],
                                            'blue' => ['bg' => 'from-blue-100 to-blue-200 dark:from-blue-900/40 dark:to-blue-800/40', 'text' => 'text-blue-700 dark:text-blue-300', 'progress' => 'from-blue-400 to-blue-600', 'ring' => 'bg-blue-500'],
                                            'amber' => ['bg' => 'from-amber-100 to-amber-200 dark:from-amber-900/40 dark:to-amber-800/40', 'text' => 'text-amber-700 dark:text-amber-300', 'progress' => 'from-amber-400 to-amber-600', 'ring' => 'bg-amber-500'],
                                            'red' => ['bg' => 'from-red-100 to-red-200 dark:from-red-900/40 dark:to-red-800/40', 'text' => 'text-red-700 dark:text-red-300', 'progress' => 'from-red-400 to-red-600', 'ring' => 'bg-red-500'],
                                            'purple' => ['bg' => 'from-purple-100 to-purple-200 dark:from-purple-900/40 dark:to-purple-800/40', 'text' => 'text-purple-700 dark:text-purple-300', 'progress' => 'from-purple-400 to-purple-600', 'ring' => 'bg-purple-500'],
                                            'pink' => ['bg' => 'from-pink-100 to-pink-200 dark:from-pink-900/40 dark:to-pink-800/40', 'text' => 'text-pink-700 dark:text-pink-300', 'progress' => 'from-pink-400 to-pink-600', 'ring' => 'bg-pink-500'],
                                            'cyan' => ['bg' => 'from-cyan-100 to-cyan-200 dark:from-cyan-900/40 dark:to-cyan-800/40', 'text' => 'text-cyan-700 dark:text-cyan-300', 'progress' => 'from-cyan-400 to-cyan-600', 'ring' => 'bg-cyan-500'],
                                            'indigo' => ['bg' => 'from-indigo-100 to-indigo-200 dark:from-indigo-900/40 dark:to-indigo-800/40', 'text' => 'text-indigo-700 dark:text-indigo-300', 'progress' => 'from-indigo-400 to-indigo-600', 'ring' => 'bg-indigo-500']
                                        ];
                                        $colorSet = $colorClasses[$color];
                                        @endphp
                                    <tr class="group hover:bg-gradient-to-r hover:from-emerald-50/30 hover:to-green-50/30 dark:hover:from-emerald-950/10 dark:hover:to-green-950/10 transition-all duration-200 hover:shadow-sm">
                                        <td class="px-4 sm:px-6 py-4">
                                        <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-lg bg-gradient-to-br {{ $colorSet['bg'] }} flex items-center justify-center group-hover:scale-105 transition-transform duration-200 shadow-sm">
                                                        <div class="w-3 h-3 rounded-full {{ $colorSet['ring'] }}"></div>
            </div>
                                                </div>
                                                <div class="ml-3">
                                                    <div class="text-sm font-bold text-stone-900 dark:text-stone-100 {{ $colorSet['text'] }} transition-colors">{{ $category['name'] }}</div>
                                                    <div class="text-xs text-stone-500 dark:text-stone-400">Category {{ $index + 1 }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <div class="flex flex-col">
                                                <div class="flex items-baseline">
                                                    <span class="text-lg font-bold text-stone-900 dark:text-stone-100">{{ number_format($category['total_items']) }}</span>
                                                    <span class="ml-2 text-xs text-stone-500 dark:text-stone-400">items</span>
                                                </div>
                                                <div class="flex items-center mt-1">
                                                    <div class="w-20 bg-stone-200 dark:bg-stone-700 rounded-full h-1.5 mr-2">
                                                        <div class="bg-gradient-to-r {{ $colorSet['progress'] }} h-1.5 rounded-full transition-all duration-500" style="width: {{ min($itemPercentage, 100) }}%"></div>
                                                    </div>
                                                    <span class="text-xs font-medium {{ $colorSet['text'] }}">{{ $itemPercentage }}%</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <div class="flex flex-col">
                                                <div class="flex items-baseline">
                                                    <span class="text-sm font-bold text-stone-900 dark:text-stone-100">₱{{ number_format($category['total_value'] / 1000, 0) }}K</span>
                                                    <span class="ml-1 text-xs text-stone-500 dark:text-stone-400">PHP</span>
                                                </div>
                                                <div class="text-xs text-stone-500 dark:text-stone-400">₱{{ number_format($category['total_value'], 2) }}</div>
                                            </div>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <div class="space-y-2">
                                                <div class="flex items-center justify-between text-xs">
                                                    <span class="text-stone-600 dark:text-stone-400 font-medium">Items</span>
                                                    <span class="font-semibold {{ $colorSet['text'] }}">{{ $itemPercentage }}%</span>
                                                </div>
                                                <div class="w-full bg-stone-200 dark:bg-stone-700 rounded-full h-2 shadow-inner">
                                                    <div class="bg-gradient-to-r {{ $colorSet['progress'] }} h-2 rounded-full transition-all duration-500 shadow-sm" style="width: {{ min($itemPercentage, 100) }}%"></div>
                                                </div>
                                                <div class="flex items-center justify-between text-xs">
                                                    <span class="text-stone-600 dark:text-stone-400 font-medium">Value</span>
                                                    <span class="font-semibold {{ $colorSet['text'] }}">{{ $valuePercentage }}%</span>
                                                </div>
                                                <div class="w-full bg-stone-200 dark:bg-stone-700 rounded-full h-2 shadow-inner">
                                                    <div class="bg-gradient-to-r {{ $colorSet['progress'] }} h-2 rounded-full transition-all duration-500 shadow-sm opacity-75" style="width: {{ min($valuePercentage, 100) }}%"></div>
                                                </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                        <td colspan="4" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center">
                                                <div class="w-16 h-16 bg-gradient-to-br from-stone-100 to-stone-200 dark:from-stone-700 dark:to-stone-800 rounded-full flex items-center justify-center mb-4">
                                                    <x-flux::icon.tag class="h-8 w-8 text-stone-400 dark:text-stone-500" />
                                                </div>
                                                <h3 class="text-sm font-semibold text-stone-700 dark:text-stone-300 mb-2">No categories found</h3>
                                                <p class="text-xs text-stone-500 dark:text-stone-400 max-w-sm">Start by creating item categories to organize your inventory effectively.</p>
                                            </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if ($tab === 'suppliers')
    <!-- Supplier Spending Overview -->
    <div class="space-y-6">
        <!-- Top Suppliers Cards -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($this->supplierSpending->take(3) as $supplier)
                <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm p-6 border border-stone-200 dark:border-stone-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-lg bg-green-100 dark:bg-green-900">
                                <x-flux::icon.truck class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-stone-500 dark:text-stone-400 truncate">{{ $supplier['name'] }}</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-stone-900 dark:text-stone-100">₱{{ number_format($supplier['total_spent'] / 1000000, 1) }}M</div>
                                    <div class="ml-2 flex items-baseline text-sm font-semibold text-stone-600 dark:text-stone-400">
                                        {{ number_format($supplier['total_items']) }} items
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Supplier Spending Table -->
        <div class="bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 backdrop-blur-sm">
            <div class="p-4 sm:p-6 lg:p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg sm:text-xl font-bold text-stone-900 dark:text-stone-100 flex items-center">
                            <div class="w-2 h-6 bg-gradient-to-b from-amber-500 to-amber-600 rounded-full mr-3"></div>
                Spending by Supplier
            </h3>
                        <p class="text-sm text-stone-500 dark:text-stone-400 mt-1 ml-5">Comprehensive analysis of supplier transactions and performance</p>
                    </div>
                    <div class="flex items-center px-3 py-1.5 bg-amber-100 dark:bg-amber-900/30 rounded-full text-xs text-amber-700 dark:text-amber-300">
                        <x-flux::icon.truck class="h-4 w-4 mr-1" />
                        <span class="hidden sm:inline font-medium">{{ count($this->supplierSpending) }} Suppliers</span>
                    </div>
                </div>
                
                <div class="overflow-hidden rounded-lg border border-stone-200/50 dark:border-stone-700/50 shadow-sm">
                <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200/50 dark:divide-stone-700/50">
                            <thead class="bg-gradient-to-r from-amber-50 to-orange-100 dark:from-amber-900/20 dark:to-orange-900/20">
                                <tr>
                                    <th class="px-4 sm:px-6 py-4 text-left text-xs font-bold text-stone-600 dark:text-stone-300 uppercase tracking-wider">
                                        <div class="flex items-center space-x-1">
                                            <x-flux::icon.building-office class="h-4 w-4" />
                                            <span>Supplier</span>
                                        </div>
                                    </th>
                                    <th class="px-4 sm:px-6 py-4 text-left text-xs font-bold text-stone-600 dark:text-stone-300 uppercase tracking-wider">
                                        <div class="flex items-center space-x-1">
                                            <x-flux::icon.receipt-percent class="h-4 w-4" />
                                            <span>Total Spent</span>
                                        </div>
                                    </th>
                                    <th class="px-4 sm:px-6 py-4 text-left text-xs font-bold text-stone-600 dark:text-stone-300 uppercase tracking-wider">
                                        <div class="flex items-center space-x-1">
                                            <x-flux::icon.box class="h-4 w-4" />
                                            <span>Items Purchased</span>
                                        </div>
                                    </th>
                                    <th class="px-4 sm:px-6 py-4 text-left text-xs font-bold text-stone-600 dark:text-stone-300 uppercase tracking-wider">
                                        <div class="flex items-center space-x-1">
                                            <x-flux::icon.document-text class="h-4 w-4" />
                                            <span>Contracts</span>
                                        </div>
                                    </th>
                                    <th class="px-4 sm:px-6 py-4 text-left text-xs font-bold text-stone-600 dark:text-stone-300 uppercase tracking-wider">
                                        <div class="flex items-center space-x-1">
                                            <x-flux::icon.calculator class="h-4 w-4" />
                                            <span>Avg per Item</span>
                                        </div>
                                    </th>
                            </tr>
                        </thead>
                            <tbody class="bg-white/50 dark:bg-stone-800/50 backdrop-blur-sm divide-y divide-stone-200/30 dark:divide-stone-700/30">
                                @forelse($this->supplierSpending as $index => $supplier)
                                    @php
                                        $maxSpent = collect($this->supplierSpending)->max('total_spent');
                                        $spendingPercentage = $maxSpent > 0 ? ($supplier['total_spent'] / $maxSpent) * 100 : 0;
                                        $supplierInitials = collect(explode(' ', $supplier['name']))->map(fn($word) => strtoupper(substr($word, 0, 1)))->take(2)->join('');
                                        $colors = ['amber', 'orange', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan'];
                                        $color = $colors[$index % count($colors)];
                                        $colorClasses = [
                                            'amber' => ['bg' => 'from-amber-100 to-amber-200 dark:from-amber-900/40 dark:to-amber-800/40', 'text' => 'text-amber-700 dark:text-amber-300', 'ring' => 'ring-amber-300 dark:ring-amber-600'],
                                            'orange' => ['bg' => 'from-orange-100 to-orange-200 dark:from-orange-900/40 dark:to-orange-800/40', 'text' => 'text-orange-700 dark:text-orange-300', 'ring' => 'ring-orange-300 dark:ring-orange-600'],
                                            'yellow' => ['bg' => 'from-yellow-100 to-yellow-200 dark:from-yellow-900/40 dark:to-yellow-800/40', 'text' => 'text-yellow-700 dark:text-yellow-300', 'ring' => 'ring-yellow-300 dark:ring-yellow-600'],
                                            'lime' => ['bg' => 'from-lime-100 to-lime-200 dark:from-lime-900/40 dark:to-lime-800/40', 'text' => 'text-lime-700 dark:text-lime-300', 'ring' => 'ring-lime-300 dark:ring-lime-600'],
                                            'green' => ['bg' => 'from-green-100 to-green-200 dark:from-green-900/40 dark:to-green-800/40', 'text' => 'text-green-700 dark:text-green-300', 'ring' => 'ring-green-300 dark:ring-green-600'],
                                            'emerald' => ['bg' => 'from-emerald-100 to-emerald-200 dark:from-emerald-900/40 dark:to-emerald-800/40', 'text' => 'text-emerald-700 dark:text-emerald-300', 'ring' => 'ring-emerald-300 dark:ring-emerald-600'],
                                            'teal' => ['bg' => 'from-teal-100 to-teal-200 dark:from-teal-900/40 dark:to-teal-800/40', 'text' => 'text-teal-700 dark:text-teal-300', 'ring' => 'ring-teal-300 dark:ring-teal-600'],
                                            'cyan' => ['bg' => 'from-cyan-100 to-cyan-200 dark:from-cyan-900/40 dark:to-cyan-800/40', 'text' => 'text-cyan-700 dark:text-cyan-300', 'ring' => 'ring-cyan-300 dark:ring-cyan-600']
                                        ];
                                        $colorSet = $colorClasses[$color];
                                    @endphp
                                    <tr class="group hover:bg-gradient-to-r hover:from-amber-50/30 hover:to-orange-50/30 dark:hover:from-amber-950/10 dark:hover:to-orange-950/10 transition-all duration-200 hover:shadow-sm">
                                        <td class="px-4 sm:px-6 py-4">
                                        <div class="flex items-center">
                                                <div class="flex-shrink-0 h-12 w-12">
                                                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br {{ $colorSet['bg'] }} flex items-center justify-center group-hover:scale-105 transition-all duration-200 ring-1 {{ $colorSet['ring'] }} shadow-md">
                                                        <span class="text-sm font-bold {{ $colorSet['text'] }}">{{ $supplierInitials }}</span>
            </div>
        </div>
                                            <div class="ml-4">
                                                    <div class="text-sm font-bold text-stone-900 dark:text-stone-100 group-hover:{{ $colorSet['text'] }} transition-colors">{{ $supplier['name'] }}</div>
                                                    <div class="text-xs text-stone-500 dark:text-stone-400">
                                                        @if($index === 0)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-gradient-to-r from-yellow-100 to-yellow-200 text-yellow-800 dark:from-yellow-900/40 dark:to-yellow-800/40 dark:text-yellow-200">
                                                                <x-flux::icon.star class="h-2.5 w-2.5 mr-1" />
                                                                Top Supplier
                                                            </span>
                                                        @else
                                                            Supplier #{{ $index + 1 }}
                                                        @endif
                                                    </div>
                                            </div>
                                        </div>
                                    </td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <div class="flex flex-col">
                                                <div class="flex items-baseline">
                                                    <span class="text-lg font-bold text-stone-900 dark:text-stone-100">₱{{ number_format($supplier['total_spent'] / 1000, 0) }}K</span>
                                                    <span class="ml-1 text-xs text-stone-500 dark:text-stone-400">PHP</span>
                                                </div>
                                                <div class="text-xs text-stone-500 dark:text-stone-400 mb-1">₱{{ number_format($supplier['total_spent'], 2) }}</div>
                                                <div class="w-20 bg-stone-200 dark:bg-stone-700 rounded-full h-1.5">
                                                    <div class="bg-gradient-to-r from-amber-400 to-amber-600 h-1.5 rounded-full transition-all duration-500" style="width: {{ $spendingPercentage }}%"></div>
                                                </div>
                                            </div>
                                    </td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-semibold text-stone-900 dark:text-stone-100">{{ number_format($supplier['total_items']) }}</span>
                                                    <span class="text-xs text-stone-500 dark:text-stone-400">items purchased</span>
                                                </div>
                                                <div class="ml-3 p-1.5 bg-gradient-to-br from-stone-100 to-stone-200 dark:from-stone-700 dark:to-stone-800 rounded-lg">
                                                    <x-flux::icon.box class="h-4 w-4 text-stone-600 dark:text-stone-400" />
                                                </div>
                                            </div>
                                    </td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 dark:from-blue-900/40 dark:to-blue-800/40 dark:text-blue-200 shadow-sm border border-blue-200/50 dark:border-blue-800/50">
                                                    <x-flux::icon.document-text class="h-3 w-3 mr-1" />
                                                    {{ $supplier['contracts_count'] }}
                                        </span>
                                                <span class="text-xs text-stone-500 dark:text-stone-400">active</span>
                                            </div>
                                    </td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <div class="flex flex-col">
                                            @if($supplier['total_items'] > 0)
                                                    @php $avgPrice = $supplier['total_spent'] / $supplier['total_items']; @endphp
                                                    <div class="flex items-baseline">
                                                        <span class="text-sm font-semibold text-stone-900 dark:text-stone-100">₱{{ number_format($avgPrice, 0) }}</span>
                                                        <span class="ml-1 text-xs text-stone-500 dark:text-stone-400">avg</span>
                                                    </div>
                                                    <div class="text-xs text-stone-500 dark:text-stone-400">per item</div>
                                                    @if($avgPrice > 10000)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 mt-1">
                                                            High value
                                                        </span>
                                                    @elseif($avgPrice > 1000)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 mt-1">
                                                            Medium value
                                                        </span>
                                            @else
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 mt-1">
                                                            Low value
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-sm text-stone-400 dark:text-stone-500">-</span>
                                                    <span class="text-xs text-stone-400 dark:text-stone-500">No items</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                        <td colspan="5" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center">
                                                <div class="w-16 h-16 bg-gradient-to-br from-stone-100 to-stone-200 dark:from-stone-700 dark:to-stone-800 rounded-full flex items-center justify-center mb-4">
                                                    <x-flux::icon.truck class="h-8 w-8 text-stone-400 dark:text-stone-500" />
                                                </div>
                                                <h3 class="text-sm font-semibold text-stone-700 dark:text-stone-300 mb-2">No suppliers found</h3>
                                                <p class="text-xs text-stone-500 dark:text-stone-400 max-w-sm">Add suppliers to your system to start tracking spending and contract relationships.</p>
                                            </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    @if ($tab === 'recent-activity')
    <!-- Recent Activity Feed -->
    <div class="bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 backdrop-blur-sm">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg sm:text-xl font-bold text-stone-900 dark:text-stone-100 flex items-center">
                        <div class="w-2 h-6 bg-gradient-to-b from-violet-500 to-violet-600 rounded-full mr-3"></div>
                Recent Activity
            </h3>
                    <p class="text-sm text-stone-500 dark:text-stone-400 mt-1 ml-5">Live system activity and audit trail</p>
                </div>
                <div class="flex items-center px-3 py-1.5 bg-violet-100 dark:bg-violet-900/30 rounded-full text-xs text-violet-700 dark:text-violet-300">
                    <x-flux::icon.clock-history class="h-4 w-4 mr-1" />
                    <span class="hidden sm:inline font-medium">{{ count($this->recentActivity) }} Activities</span>
                </div>
            </div>
            
            <div class="bg-white/60 dark:bg-stone-800/60 rounded-lg border border-stone-200/40 dark:border-stone-700/40 backdrop-blur-sm p-4 sm:p-6">
            <div class="flow-root">
                <ul role="list" class="-mb-8">
                    @forelse($this->recentActivity as $activity)
                            <li class="group hover:bg-violet-50/50 dark:hover:bg-violet-950/10 rounded-lg px-3 py-2 transition-colors duration-200">
                                <div class="relative pb-8 last:pb-0">
                                @if(!$loop->last)
                                        <span class="absolute top-6 left-5 -ml-px h-full w-0.5 bg-gradient-to-b from-stone-300 to-stone-200 dark:from-stone-600 dark:to-stone-700" aria-hidden="true"></span>
                                @endif
                                    <div class="relative flex items-start space-x-4">
                                        <div class="flex-shrink-0">
                                            @php
                                                $actionColors = [
                                                    'created' => ['bg' => 'from-green-500 to-green-600', 'ring' => 'ring-green-200 dark:ring-green-800/50', 'icon' => 'plus-circle'],
                                                    'updated' => ['bg' => 'from-blue-500 to-blue-600', 'ring' => 'ring-blue-200 dark:ring-blue-800/50', 'icon' => 'edit'],
                                                    'deleted' => ['bg' => 'from-red-500 to-red-600', 'ring' => 'ring-red-200 dark:ring-red-800/50', 'icon' => 'x-mark'],
                                                    'default' => ['bg' => 'from-stone-500 to-stone-600', 'ring' => 'ring-stone-200 dark:ring-stone-800/50', 'icon' => 'settings-2']
                                                ];
                                                $actionConfig = $actionColors[$activity['action']] ?? $actionColors['default'];
                                            @endphp
                                            <div class="flex items-center justify-center h-10 w-10 rounded-full bg-gradient-to-br {{ $actionConfig['bg'] }} ring-4 {{ $actionConfig['ring'] }} shadow-lg group-hover:scale-110 transition-transform duration-200">
                                                <x-dynamic-component :component="'flux::icon.' . $actionConfig['icon']" class="h-5 w-5 text-white" />
            </div>
                                        </div>
                                        <div class="min-w-0 flex-1 pt-1">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <p class="text-sm font-medium text-stone-900 dark:text-stone-100 leading-relaxed">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gradient-to-r from-violet-100 to-purple-100 text-violet-800 dark:from-violet-900/40 dark:to-purple-900/40 dark:text-violet-200 mr-2">
                                                            {{ $activity['user_name'] }}
                                                        </span>
                                                        <span class="text-stone-600 dark:text-stone-400">
                                                {{ $activity['action'] }} 
                                                        </span>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-stone-100 text-stone-700 dark:bg-stone-700 dark:text-stone-300 ml-1">
                                                            {{ $activity['table'] }}
                                                        </span>
                                                    </p>
                                                @if($activity['description'])
                                                        <p class="text-xs text-stone-500 dark:text-stone-400 mt-2 pl-1 border-l-2 border-stone-200 dark:border-stone-700 bg-stone-50/50 dark:bg-stone-800/50 p-2 rounded-r">
                                                            {{ $activity['description'] }}
                                            </p>
                                                    @endif
                                        </div>
                                                <div class="flex flex-col items-end ml-4">
                                                    <div class="flex items-center text-xs text-stone-500 dark:text-stone-400 mb-1">
                                                        <x-flux::icon.clock class="h-3 w-3 mr-1" />
                                                        <time datetime="{{ $activity['created_at'] }}" class="whitespace-nowrap font-medium">
                                                            {{ $activity['time_ago'] }}
                                                        </time>
                                                    </div>
                                                    @php
                                                        $actionBadgeClasses = [
                                                            'created' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                                            'updated' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                                            'deleted' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                                            'default' => 'bg-stone-100 text-stone-700 dark:bg-stone-700 dark:text-stone-300'
                                                        ];
                                                        $badgeClass = $actionBadgeClasses[$activity['action']] ?? $actionBadgeClasses['default'];
                                                    @endphp
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badgeClass }}">
                                                        {{ ucfirst($activity['action']) }}
                                                    </span>
                                                </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @empty
                            <li class="text-center py-12">
                            <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gradient-to-br from-stone-100 to-stone-200 dark:from-stone-700 dark:to-stone-800 rounded-full flex items-center justify-center mb-4">
                                        <x-flux::icon.clock-history class="h-8 w-8 text-stone-400 dark:text-stone-500" />
                                    </div>
                                    <h3 class="text-sm font-semibold text-stone-700 dark:text-stone-300 mb-2">No recent activity</h3>
                                    <p class="text-xs text-stone-500 dark:text-stone-400 max-w-sm">System activity will appear here as users interact with the application.</p>
                            </div>
                        </li>
                    @endforelse
                </ul>
            </div>
                
                @if(count($this->recentActivity) > 0)
                    <div class="mt-6 pt-4 border-t border-stone-200 dark:border-stone-700">
                        <div class="flex items-center justify-between">
                            <div class="text-xs text-stone-500 dark:text-stone-400">
                                Showing {{ count($this->recentActivity) }} most recent activities
                            </div>
                            <a href="{{ route('admin.system.audit-logs.index') }}" wire:navigate class="inline-flex items-center text-xs font-medium text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 hover:underline transition-colors">
                                View all activity
                                <x-flux::icon.arrow-up-right class="ml-1 h-3 w-3" />
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if ($tab === 'user-management')
    <!-- User Management Overview -->
    <div class="space-y-6 sm:space-y-8">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg sm:text-xl font-bold text-stone-900 dark:text-stone-100 flex items-center">
                    <div class="w-2 h-6 bg-gradient-to-b from-indigo-500 to-indigo-600 rounded-full mr-3"></div>
                    User Management Overview
                </h3>
                <p class="text-sm text-stone-500 dark:text-stone-400 mt-1 ml-5">Comprehensive user analytics and management tools</p>
            </div>
            <div class="flex items-center px-3 py-1.5 bg-indigo-100 dark:bg-indigo-900/30 rounded-full text-xs text-indigo-700 dark:text-indigo-300">
                <x-flux::icon.users class="h-4 w-4 mr-1" />
                <span class="hidden sm:inline font-medium">{{ $this->userManagement['total_users'] }} Total Users</span>
            </div>
        </div>
        
        <!-- Enhanced User Statistics Cards -->
        <div class="grid grid-cols-1 gap-4 sm:gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div class="group bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-950/30 dark:to-indigo-950/30 rounded-xl shadow-lg border border-blue-200/50 dark:border-blue-800/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm">
                <div class="p-4 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <x-flux::icon.users class="h-6 w-6 text-white" />
                        </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <dt class="text-xs sm:text-sm font-bold text-blue-700 dark:text-blue-300 uppercase tracking-wider">Total Users</dt>
                            <dd class="text-2xl sm:text-3xl font-bold text-blue-900 dark:text-blue-100 mt-1">{{ number_format($this->userManagement['total_users']) }}</dd>
                            <div class="flex items-center mt-2">
                                <span class="text-xs text-blue-600 dark:text-blue-400 font-medium">System wide</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="group bg-gradient-to-br from-red-50 to-pink-100 dark:from-red-950/30 dark:to-pink-950/30 rounded-xl shadow-lg border border-red-200/50 dark:border-red-800/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm">
                <div class="p-4 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-xl bg-gradient-to-br from-red-500 to-red-600 shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <x-flux::icon.shield-check class="h-6 w-6 text-white" />
                        </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <dt class="text-xs sm:text-sm font-bold text-red-700 dark:text-red-300 uppercase tracking-wider">Admin Users</dt>
                            <dd class="text-2xl sm:text-3xl font-bold text-red-900 dark:text-red-100 mt-1">{{ number_format($this->userManagement['admin_users']) }}</dd>
                            <div class="flex items-center mt-2">
                                <span class="text-xs text-red-600 dark:text-red-400 font-medium">Full access</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="group bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-950/30 dark:to-emerald-950/30 rounded-xl shadow-lg border border-green-200/50 dark:border-green-800/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm">
                <div class="p-4 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <x-flux::icon.check-circle class="h-6 w-6 text-white" />
                        </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <dt class="text-xs sm:text-sm font-bold text-green-700 dark:text-green-300 uppercase tracking-wider">Verified Users</dt>
                            <dd class="text-2xl sm:text-3xl font-bold text-green-900 dark:text-green-100 mt-1">{{ number_format($this->userManagement['verified_users']) }}</dd>
                            <div class="flex items-center mt-2">
                                @php $verificationRate = $this->userManagement['total_users'] > 0 ? round(($this->userManagement['verified_users'] / $this->userManagement['total_users']) * 100) : 0; @endphp
                                <span class="text-xs text-green-600 dark:text-green-400 font-medium">{{ $verificationRate }}% verified</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="group bg-gradient-to-br from-purple-50 to-violet-100 dark:from-purple-950/30 dark:to-violet-950/30 rounded-xl shadow-lg border border-purple-200/50 dark:border-purple-800/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 backdrop-blur-sm">
                <div class="p-4 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <x-flux::icon.plus-circle class="h-6 w-6 text-white" />
                        </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <dt class="text-xs sm:text-sm font-bold text-purple-700 dark:text-purple-300 uppercase tracking-wider">Recent Registrations</dt>
                            <dd class="text-2xl sm:text-3xl font-bold text-purple-900 dark:text-purple-100 mt-1">{{ number_format($this->userManagement['recent_registrations']) }}</dd>
                            <div class="flex items-center mt-2">
                                <span class="text-xs text-purple-600 dark:text-purple-400 font-medium">Last 30 days</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Enhanced Role Distribution -->
            <div class="bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 backdrop-blur-sm">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-stone-900 dark:text-stone-100 flex items-center">
                            <div class="w-2 h-5 bg-gradient-to-b from-indigo-500 to-indigo-600 rounded-full mr-2"></div>
                            Role Distribution
                        </h3>
                        <div class="flex items-center px-2 py-1 bg-indigo-100 dark:bg-indigo-900/30 rounded-full">
                            <x-flux::icon.user-group class="h-3 w-3 text-indigo-600 dark:text-indigo-400" />
                                </div>
                    </div>
                    <div class="space-y-4">
                        @foreach($this->userManagement['role_distribution'] as $index => $role)
                            @php
                                $percentage = $this->userManagement['total_users'] > 0 ? round(($role['count'] / $this->userManagement['total_users']) * 100, 1) : 0;
                                $colors = [
                                    'text-red-500' => ['bg' => 'from-red-400 to-red-600', 'dot' => 'bg-red-500', 'badge' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
                                    'text-blue-500' => ['bg' => 'from-blue-400 to-blue-600', 'dot' => 'bg-blue-500', 'badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'],
                                    'text-green-500' => ['bg' => 'from-green-400 to-green-600', 'dot' => 'bg-green-500', 'badge' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400']
                                ];
                                $colorSet = $colors[$role['color']] ?? $colors['text-blue-500'];
                                        @endphp
                            <div class="group p-4 rounded-lg border border-stone-200/50 dark:border-stone-700/50 hover:bg-gradient-to-r hover:from-indigo-50/30 hover:to-purple-50/30 dark:hover:from-indigo-950/10 dark:hover:to-purple-950/10 transition-all duration-200">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center">
                                        <div class="w-4 h-4 rounded-full {{ $colorSet['dot'] }} mr-3 shadow-sm"></div>
                                        <span class="text-sm font-bold text-stone-800 dark:text-stone-200">{{ $role['role'] }}</span>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold {{ $colorSet['badge'] }}">
                                            {{ number_format($role['count']) }}
                                        </span>
                                        <span class="text-xs font-semibold text-stone-600 dark:text-stone-400">{{ $percentage }}%</span>
                                    </div>
                                </div>
                                <div class="w-full bg-stone-200 dark:bg-stone-700 rounded-full h-2.5 shadow-inner">
                                    <div class="bg-gradient-to-r {{ $colorSet['bg'] }} h-2.5 rounded-full transition-all duration-500 shadow-sm" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Enhanced User Activity Stats -->
            <div class="bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 backdrop-blur-sm">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-stone-900 dark:text-stone-100 flex items-center">
                            <div class="w-2 h-5 bg-gradient-to-b from-emerald-500 to-emerald-600 rounded-full mr-2"></div>
                            User Activity
                        </h3>
                        <div class="flex items-center px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 rounded-full">
                            <x-flux::icon.chart-bar class="h-3 w-3 text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="group p-4 rounded-lg border border-stone-200/50 dark:border-stone-700/50 hover:bg-gradient-to-r hover:from-emerald-50/30 hover:to-green-50/30 dark:hover:from-emerald-950/10 dark:hover:to-green-950/10 transition-all duration-200">
                            <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                    <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900/40 dark:to-blue-800/40">
                                        <x-flux::icon.clock-history class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-bold text-stone-900 dark:text-stone-100">Recent Admin Logins</p>
                                        <p class="text-xs text-stone-500 dark:text-stone-400">Last 7 days</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($this->userManagement['recent_admin_logins']) }}</span>
                                    <p class="text-xs text-stone-500 dark:text-stone-400">logins</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="group p-4 rounded-lg border border-stone-200/50 dark:border-stone-700/50 hover:bg-gradient-to-r hover:from-emerald-50/30 hover:to-green-50/30 dark:hover:from-emerald-950/10 dark:hover:to-green-950/10 transition-all duration-200">
                            <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                    <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-gradient-to-br from-green-100 to-green-200 dark:from-green-900/40 dark:to-green-800/40">
                                        <x-flux::icon.users class="h-5 w-5 text-green-600 dark:text-green-400" />
                            </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-bold text-stone-900 dark:text-stone-100">Active Admins</p>
                                        <p class="text-xs text-stone-500 dark:text-stone-400">Last 30 days</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xl font-bold text-green-600 dark:text-green-400">{{ number_format($this->userManagement['active_admins']) }}</span>
                                    <p class="text-xs text-stone-500 dark:text-stone-400">active</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="group p-4 rounded-lg border border-stone-200/50 dark:border-stone-700/50 hover:bg-gradient-to-r hover:from-emerald-50/30 hover:to-green-50/30 dark:hover:from-emerald-950/10 dark:hover:to-green-950/10 transition-all duration-200">
                            <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                    <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-gradient-to-br from-purple-100 to-purple-200 dark:from-purple-900/40 dark:to-purple-800/40">
                                        <x-flux::icon.package class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                            </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-bold text-stone-900 dark:text-stone-100">Inventory Managers</p>
                                        <p class="text-xs text-stone-500 dark:text-stone-400">All time</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($this->userManagement['inventory_managers']) }}</span>
                                    <p class="text-xs text-stone-500 dark:text-stone-400">managers</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="group p-4 rounded-lg border border-stone-200/50 dark:border-stone-700/50 hover:bg-gradient-to-r hover:from-amber-50/30 hover:to-yellow-50/30 dark:hover:from-amber-950/10 dark:hover:to-yellow-950/10 transition-all duration-200">
                            <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                    <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-gradient-to-br from-amber-100 to-amber-200 dark:from-amber-900/40 dark:to-amber-800/40">
                                        <x-flux::icon.exclamation-triangle class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                            </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-bold text-stone-900 dark:text-stone-100">Unverified Users</p>
                                        <p class="text-xs text-stone-500 dark:text-stone-400">Require attention</p>
                        </div>
                    </div>
                                <div class="text-right">
                                    <span class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($this->userManagement['unverified_users']) }}</span>
                                    <p class="text-xs text-amber-500 dark:text-amber-400">pending</p>
                </div>
            </div>
        </div>
                        </div>
                        </div>
                        </div>
        </div>

        <!-- Enhanced Quick Actions -->
        <div class="bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 backdrop-blur-sm">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-stone-900 dark:text-stone-100 flex items-center">
                            <div class="w-2 h-5 bg-gradient-to-b from-violet-500 to-violet-600 rounded-full mr-2"></div>
                            Quick Actions
                        </h3>
                        <p class="text-sm text-stone-500 dark:text-stone-400 mt-1 ml-4">User management shortcuts and tools</p>
                        </div>
                    <div class="flex items-center px-2 py-1 bg-violet-100 dark:bg-violet-900/30 rounded-full">
                        <x-flux::icon.arrows-trending-up class="h-3 w-3 text-violet-600 dark:text-violet-400" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @php
                        $quickActions = [
                            ['route' => 'admin.system.users.index', 'icon' => 'users', 'label' => 'View All Users', 'color' => 'blue'],
                            ['route' => 'admin.system.users.create', 'icon' => 'plus-circle', 'label' => 'Add New User', 'color' => 'green'],
                            ['route' => 'admin.system.audit-logs.index', 'icon' => 'document-text', 'label' => 'View Audit Logs', 'color' => 'purple'],
                            ['route' => 'admin.data.employees-and-divisions', 'icon' => 'building-2', 'label' => 'Manage Divisions', 'color' => 'amber']
                        ];
                        $colorClasses = [
                            'blue' => 'from-blue-50 to-blue-100 dark:from-blue-950/30 dark:to-blue-900/20 text-blue-700 dark:text-blue-300 hover:from-blue-100 hover:to-blue-200 dark:hover:from-blue-900/40 dark:hover:to-blue-800/30 border-blue-200/50 dark:border-blue-700/50 hover:border-blue-300/70 dark:hover:border-blue-600/70',
                            'green' => 'from-green-50 to-green-100 dark:from-green-950/30 dark:to-green-900/20 text-green-700 dark:text-green-300 hover:from-green-100 hover:to-green-200 dark:hover:from-green-900/40 dark:hover:to-green-800/30 border-green-200/50 dark:border-green-700/50 hover:border-green-300/70 dark:hover:border-green-600/70',
                            'purple' => 'from-purple-50 to-purple-100 dark:from-purple-950/30 dark:to-purple-900/20 text-purple-700 dark:text-purple-300 hover:from-purple-100 hover:to-purple-200 dark:hover:from-purple-900/40 dark:hover:to-purple-800/30 border-purple-200/50 dark:border-purple-700/50 hover:border-purple-300/70 dark:hover:border-purple-600/70',
                            'amber' => 'from-amber-50 to-amber-100 dark:from-amber-950/30 dark:to-amber-900/20 text-amber-700 dark:text-amber-300 hover:from-amber-100 hover:to-amber-200 dark:hover:from-amber-900/40 dark:hover:to-amber-800/30 border-amber-200/50 dark:border-amber-700/50 hover:border-amber-300/70 dark:hover:border-amber-600/70'
                        ];
                    @endphp
                    @foreach($quickActions as $action)
                        <a href="{{ route($action['route']) }}" wire:navigate class="group flex flex-col items-center justify-center p-4 sm:p-6 bg-gradient-to-br {{ $colorClasses[$action['color']] }} rounded-xl border transition-all duration-300 min-h-[120px] hover:shadow-lg hover:scale-105 hover:-translate-y-1 backdrop-blur-sm">
                            <div class="flex items-center justify-center w-12 h-12 sm:w-14 sm:h-14 mb-3 rounded-full bg-white/80 dark:bg-stone-800/80 backdrop-blur-sm shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-110">
                                <x-dynamic-component :component="'flux::icon.' . $action['icon']" class="h-6 w-6 sm:h-7 sm:w-7 {{ 'text-' . $action['color'] . '-600' }} dark:{{ 'text-' . $action['color'] . '-400' }} group-hover:scale-110 transition-transform duration-300" />
                            </div>
                            <p class="text-xs sm:text-sm font-bold text-center leading-tight transition-all duration-300 group-hover:scale-105">
                                {{ $action['label'] }}
                            </p>
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-transparent via-white/20 to-transparent opacity-0 group-hover:opacity-100 group-hover:animate-pulse transition-opacity duration-500 pointer-events-none"></div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Create Item Modal -->
    <x-admin.modal-form-wrapper name="dashboard-create-item" maxWidth="lg">
        <livewire:admin.data.items-and-categories.items-catalog.create />
    </x-admin.modal-form-wrapper>
</div> 