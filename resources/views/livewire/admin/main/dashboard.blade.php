<?php

use App\Models\User;
use App\Models\ItemsCatalog;
use App\Models\AuditLog;
use App\Models\ConsumableItem;
use App\Models\ContractItem;
use App\Models\IcsNumber;
use App\Models\ParNumber;
use App\Models\IdrNumber;
use App\Models\Division;
use App\Models\IcsTransfer;
use App\Models\ParTransfer;
use App\Models\PrimaryCategory;
use App\Models\Supplier;
use App\Models\Contract;
use App\Models\Employee;
use Illuminate\Support\Facades\Cache;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.app')] class extends Component {
    public string $tab = 'overview';
    public bool $showAllAlerts = true;

    // Chart data tracking for change detection
    public ?string $chartDataChecksum = null;

    public function toggleAlerts(): void
    {
        $this->showAllAlerts = !$this->showAllAlerts;
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
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
            $topSuppliersData
        ]));
        
        // Emit events to initialize charts
        $this->dispatch('initializeLineChart', [
            'chartId' => 'line-chart-canvas',
            'data' => $inventoryData
        ]);
        
        $this->dispatch('initializeDoughnutChart', [
            'chartId' => 'donut-chart-canvas', 
            'data' => $categoryData
        ]);
        
        $this->dispatch('initializeInventorySystemChart', [
            'chartId' => 'inventory-system-chart-canvas',
            'data' => $systemBreakdownData
        ]);
        
        $this->dispatch('initializeTopSuppliersChart', [
            'chartId' => 'top-suppliers-chart-canvas',
            'data' => $topSuppliersData
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
            $topSuppliersData
        ]));
        
        // Only update if data has changed
        if ($this->chartDataChecksum !== $currentChecksum) {
            $this->dispatch('updateLineChart', [
                'chartId' => 'line-chart-canvas',
                'data' => $inventoryData
            ]);
            
            $this->dispatch('updateDoughnutChart', [
                'chartId' => 'donut-chart-canvas',
                'data' => $categoryData
            ]);
            
            $this->dispatch('updateInventorySystemChart', [
                'chartId' => 'inventory-system-chart-canvas',
                'data' => $systemBreakdownData
            ]);
            
            $this->dispatch('updateTopSuppliersChart', [
                'chartId' => 'top-suppliers-chart-canvas',
                'data' => $topSuppliersData
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
                : "DATE_ADD(date_prepared, INTERVAL estimated_useful_life YEAR) BETWEEN ? AND ?";
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
                : "DATE_ADD(date_prepared, INTERVAL estimated_useful_life YEAR) BETWEEN ? AND ?";
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
            ['label' => 'Add New Item', 'icon' => 'flux::icon.plus-circle', 'route' => 'admin.data.items-and-categories.items-catalog.create'],
            ['label' => 'Manage Inventory', 'icon' => 'flux::icon.package', 'route' => 'admin.inventory.ics.index'],
            ['label' => 'Manage Divisions', 'icon' => 'flux::icon.building-2', 'route' => 'admin.data.employees-and-divisions.divisions.index'],
            ['label' => 'Manage Suppliers', 'icon' => 'flux::icon.truck', 'route' => 'admin.data.suppliers-and-contracts.suppliers.index'],
            ['label' => 'View Reports', 'icon' => 'flux::icon.chart-bar', 'route' => 'admin.main.reports.index'],
            ['label' => 'Manage Users', 'icon' => 'flux::icon.users', 'route' => 'admin.system.users.index'],
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
            usort($data, fn($a, $b) => $b['count'] <=> $a['count']);
            
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
            $recentAdminLogins = User::whereHas('adminUser', function($query) {
                $query->where('last_login_at', '>=', now()->subDays(7));
            })->count();
            
            // Active admin users (logged in within 30 days)
            $activeAdmins = User::whereHas('adminUser', function($query) {
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
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-stone-900 dark:text-stone-100">D'Agriventory</h1>
            <p class="text-sm sm:text-base text-stone-500 dark:text-stone-400">Agricultural Inventory Management System</p>
        </div>
    </div>

    <!-- Inventory Alerts -->
    <div 
        class="bg-white dark:bg-stone-800/50 rounded-lg shadow-sm border border-stone-200 dark:border-stone-700/60 overflow-hidden"
        x-data="{ expanded: @js($showAllAlerts) }"
        x-init="$watch('$wire.showAllAlerts', value => expanded = value)"
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
                                <x-flux::icon.exclamation-triangle class="h-5 w-5 text-amber-500" />
                                <div class="absolute -top-2 -right-2 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold">{{ $totalActiveAlerts }}</div>
                            </div>
                        @else
                            <x-flux::icon.check-circle class="h-5 w-5 text-green-500" />
                        @endif
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-semibold text-stone-900 dark:text-stone-100">
                            System Alerts
                        </h2>
                        <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400">
                            @if($totalActiveAlerts > 0)
                                {{ $totalActiveAlerts }} {{ Str::plural('issue', $totalActiveAlerts) }} requiring attention
                            @else
                                All systems operating normally
                            @endif
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
                            <button class="inline-flex items-center px-2 py-1 text-xs font-medium text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100 hover:bg-stone-200 dark:hover:bg-stone-700 rounded transition-colors">
                                <x-flux::icon.arrow-path class="mr-1 h-3 w-3" />
                                Refresh
                            </button>
                        </div>
                    </div>
            </div>
    </div>
    @endif

    <!-- Quick Actions -->
    <div>
        <h2 class="text-base sm:text-lg font-semibold text-stone-900 dark:text-stone-100 mb-3 sm:mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 gap-2 sm:gap-3 md:gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @foreach($this->quickActions as $action)
                <a href="{{ route($action['route']) }}" class="flex flex-col items-center justify-center p-3 sm:p-4 bg-white dark:bg-stone-800 rounded-lg shadow-sm hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors border border-stone-200 dark:border-stone-700 min-h-[80px] sm:min-h-[100px]">
                    <div class="p-2 sm:p-3 bg-stone-100 dark:bg-stone-700 rounded-full">
                         <x-dynamic-component :component="$action['icon']" class="h-4 w-4 sm:h-5 sm:w-5 lg:h-6 lg:w-6 text-stone-600 dark:text-stone-300" />
                    </div>
                    <span class="mt-1 sm:mt-2 text-xs sm:text-sm font-medium text-center text-stone-700 dark:text-stone-300 leading-tight">{{ $action['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-3 sm:gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <x-dashboard.stat-card title="Total Items" :value="number_format($this->stats['total_items'])" change="+12.5%" change-type="increase">
            <x-slot:icon>
                <x-flux::icon.box class="h-6 w-6 text-blue-600 dark:text-blue-400" />
            </x-slot:icon>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card title="Total Value" value="₱{{ number_format($this->stats['total_value'] / 1000000, 2) }}M" change="+8.2%" change-type="increase">
            <x-slot:icon>
                <x-flux::icon.receipt-percent class="h-6 w-6 text-green-600 dark:text-green-400" />
            </x-slot:icon>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card title="Active Users" :value="$this->stats['active_users']" subtitle="System users">
            <x-slot:icon>
                <x-flux::icon.users class="h-6 w-6 text-purple-600 dark:text-purple-400" />
            </x-slot:icon>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card title="Pending Actions" :value="$this->stats['pending_actions']" subtitle="Needs attention">
            <x-slot:icon>
                <x-flux::icon.exclamation-triangle class="h-6 w-6 text-amber-600 dark:text-amber-400" />
            </x-slot:icon>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card title="Expiring Soon" :value="$this->stats['expiring_soon']" subtitle="Within 30 days">
            <x-slot:icon>
                <x-flux::icon.clock class="h-6 w-6 text-red-600 dark:text-red-400" />
            </x-slot:icon>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card title="Total Divisions" :value="$this->stats['total_divisions']" subtitle="Offices/Units">
            <x-slot:icon>
                <x-flux::icon.building-office class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
            </x-slot:icon>
        </x-dashboard.stat-card>
    </div>
    
    <!-- Secondary Stats -->
    <div class="grid grid-cols-1 gap-3 sm:gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($this->secondaryStats as $stat)
            <div class="relative p-3 sm:p-4 bg-white dark:bg-stone-900 rounded-lg shadow-sm border border-stone-200 dark:border-stone-800 flex items-start space-x-3 sm:space-x-4">
                <div class="flex-shrink-0">
                    <div class="flex items-center justify-center h-10 w-10 sm:h-12 sm:w-12 rounded-lg bg-stone-100 dark:bg-stone-800">
                        <x-dynamic-component :component="$stat['icon']" :class="'h-5 w-5 sm:h-6 sm:w-6 ' . $stat['color']" />
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-xs sm:text-sm font-medium text-stone-500 dark:text-stone-400 truncate">{{ $stat['label'] }}</h4>
                    <p class="text-lg sm:text-2xl font-bold text-stone-900 dark:text-stone-100 mt-1">{{ $stat['value'] }} <span class="text-xs sm:text-sm font-normal">{{ $stat['unit'] }}</span></p>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">
                        <span class="{{ str_starts_with($stat['change'], '+') ? 'text-green-500' : 'text-red-500' }}">{{ $stat['change'] }}</span> vs. last period
                    </p>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Analytics Placeholders -->
    <div>
        <h2 class="text-base sm:text-lg font-semibold text-stone-900 dark:text-stone-100 mb-3 sm:mb-4">Analytics & Reports</h2>
        <div class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-2">
            <!-- Chart 1: Inventory Value Over Time -->
            <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm p-4 sm:p-6 border border-stone-200 dark:border-stone-700">
                <h3 class="text-sm sm:text-base font-semibold text-stone-700 dark:text-stone-300 mb-3 sm:mb-4">Inventory Value Over Time</h3>
                <div class="relative">
                    <!-- Line Chart Container with wire:ignore to prevent Livewire from morphing -->
                    <div wire:ignore class="h-48 sm:h-64 relative">
                        <canvas id="line-chart-canvas" class="w-full h-full"></canvas>
                    </div>
                        <!-- Chart Legend -->
                        <div class="flex flex-wrap justify-center mt-2 sm:mt-3 gap-2 sm:gap-4 text-xs">
                            <div class="flex items-center">
                                <div class="w-2 h-2 sm:w-3 sm:h-3 bg-blue-500 rounded-full mr-1"></div>
                                <span class="text-stone-600 dark:text-stone-400">Total Value</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-2 h-2 sm:w-3 sm:h-3 bg-green-500 rounded-full mr-1"></div>
                                <span class="text-stone-600 dark:text-stone-400">ICS</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-2 h-2 sm:w-3 sm:h-3 bg-yellow-500 rounded-full mr-1"></div>
                                <span class="text-stone-600 dark:text-stone-400">PAR</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-2 h-2 sm:w-3 sm:h-3 bg-purple-500 rounded-full mr-1"></div>
                                <span class="text-stone-600 dark:text-stone-400">IDR</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-2 h-2 sm:w-3 sm:h-3 bg-red-500 rounded-full mr-1"></div>
                                <span class="text-stone-600 dark:text-stone-400">Consumables</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart 2: Item Distribution by Category -->
            <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm p-4 sm:p-6 border border-stone-200 dark:border-stone-700">
                <h3 class="text-sm sm:text-base font-semibold text-stone-700 dark:text-stone-300 mb-3 sm:mb-4">Item Distribution by Category</h3>
                <div class="flex flex-col lg:flex-row items-center justify-center lg:space-x-6">
                    <!-- Donut Chart Container with wire:ignore to prevent Livewire from morphing -->
                    <div wire:ignore class="relative mb-4 lg:mb-0">
                        <canvas id="donut-chart-canvas" width="160" height="160" class="sm:w-[200px] sm:h-[200px]"></canvas>
                    </div>
                    <!-- Legend -->
                    <div class="w-full lg:w-auto lg:ml-6 space-y-1 sm:space-y-2">
                        @foreach($this->categoryDistribution as $index => $category)
                            <div class="flex items-center text-xs sm:text-sm">
                                <div class="w-2 h-2 sm:w-3 sm:h-3 rounded-full mr-2 flex-shrink-0" style="background-color: {{ ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16'][$index % 8] }}"></div>
                                <span class="text-stone-700 dark:text-stone-300 truncate">{{ $category['name'] }}</span>
                                <span class="ml-auto text-stone-500 dark:text-stone-400 flex-shrink-0">{{ $category['percentage'] }}%</span>
                            </div>
                        @endforeach
                        <div class="mt-3 sm:mt-4 text-center border-t border-stone-200 dark:border-stone-600 pt-2">
                            <div class="text-base sm:text-lg font-bold text-stone-800 dark:text-stone-200">{{ $this->categoryDistribution->sum('count') }}</div>
                            <div class="text-xs text-stone-500 dark:text-stone-400">Total Items</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart 3: Inventory System Breakdown -->
            <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm p-4 sm:p-6 border border-stone-200 dark:border-stone-700">
                <h3 class="text-sm sm:text-base font-semibold text-stone-700 dark:text-stone-300 mb-3 sm:mb-4">Inventory System Breakdown</h3>
                <div class="relative">
                    <!-- Bar Chart Container with wire:ignore to prevent Livewire from morphing -->
                    <div wire:ignore class="h-48 sm:h-64 relative">
                        <canvas id="inventory-system-chart-canvas" class="w-full h-full"></canvas>
                    </div>
                    <!-- Chart Legend -->
                    <div class="flex flex-wrap justify-center mt-2 sm:mt-3 gap-2 sm:gap-4 text-xs">
                        @foreach($this->inventorySystemBreakdown as $system)
                            <div class="flex items-center">
                                <div class="w-2 h-2 sm:w-3 sm:h-3 rounded-full mr-1" style="background-color: {{ $system['color'] }}"></div>
                                <span class="text-stone-600 dark:text-stone-400">{{ $system['system'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Chart 4: Top Suppliers Spending -->
            <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm p-4 sm:p-6 border border-stone-200 dark:border-stone-700">
                <h3 class="text-sm sm:text-base font-semibold text-stone-700 dark:text-stone-300 mb-3 sm:mb-4">Top Suppliers Spending</h3>
                <div class="relative">
                    <!-- Horizontal Bar Chart Container with wire:ignore to prevent Livewire from morphing -->
                    <div wire:ignore class="h-48 sm:h-64 relative">
                        <canvas id="top-suppliers-chart-canvas" class="w-full h-full"></canvas>
                    </div>
                    <div class="mt-2 sm:mt-3 text-xs text-center text-stone-500 dark:text-stone-400">
                        Top 10 suppliers by total spending
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
                
                window.initializeChart(data[0].chartId, 'line', lineChartData);
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
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                };
                
                window.initializeChart(data[0].chartId, 'doughnut', doughnutChartData);
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
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                };
                
                window.updateChart(data[0].chartId, newData);
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
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value, index, values) {
                                    return value.toLocaleString();
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
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + (value / 1000000).toFixed(1) + 'M';
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
    <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm">
        <div class="p-3 sm:p-4 lg:p-6">
            <h3 class="text-base sm:text-lg font-medium leading-6 text-stone-900 dark:text-stone-100 mb-4 sm:mb-6">
                Division Inventory Overview
            </h3>
            <div class="overflow-x-auto -mx-3 sm:-mx-4 lg:-mx-6">
                <div class="inline-block min-w-full px-3 sm:px-4 lg:px-6">
                    <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
                    <thead class="bg-stone-50 dark:bg-stone-900">
                        <tr>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider">Division</th>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider">Total</th>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider hidden sm:table-cell">ICS</th>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider hidden sm:table-cell">PAR</th>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider hidden md:table-cell">IDR</th>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider hidden md:table-cell">Consumables</th>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-stone-800 divide-y divide-stone-200 dark:divide-stone-700">
                        @forelse($this->divisionInventory as $division)
                            <tr class="hover:bg-stone-50 dark:hover:bg-stone-700">
                                <td class="px-3 sm:px-6 py-3 sm:py-4">
                                    <div class="text-xs sm:text-sm font-medium text-stone-900 dark:text-stone-100">{{ $division['name'] }}</div>
                                    <div class="text-xs text-stone-500 dark:text-stone-400 sm:hidden mt-1">
                                        ICS: {{ number_format($division['ics']) }} | PAR: {{ number_format($division['par']) }}
                                    </div>
                                </td>
                                <td class="px-3 sm:px-6 py-3 sm:py-4">
                                    <div class="text-xs sm:text-sm text-stone-900 dark:text-stone-100 font-semibold">{{ number_format($division['total_items']) }}</div>
                                </td>
                                <td class="px-3 sm:px-6 py-3 sm:py-4 hidden sm:table-cell">
                                    <div class="text-xs sm:text-sm text-stone-600 dark:text-stone-400">{{ number_format($division['ics']) }}</div>
                                </td>
                                <td class="px-3 sm:px-6 py-3 sm:py-4 hidden sm:table-cell">
                                    <div class="text-xs sm:text-sm text-stone-600 dark:text-stone-400">{{ number_format($division['par']) }}</div>
                                </td>
                                <td class="px-3 sm:px-6 py-3 sm:py-4 hidden md:table-cell">
                                    <div class="text-xs sm:text-sm text-stone-600 dark:text-stone-400">{{ number_format($division['idr']) }}</div>
                                </td>
                                <td class="px-3 sm:px-6 py-3 sm:py-4 hidden md:table-cell">
                                    <div class="text-xs sm:text-sm text-stone-600 dark:text-stone-400">{{ number_format($division['consumables']) }}</div>
                                </td>
                                <td class="px-3 sm:px-6 py-3 sm:py-4">
                                    @if($division['low_stock'] > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            <x-flux::icon.x-mark class="h-3 w-3 mr-1" />
                                            {{ $division['low_stock'] }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            <x-flux::icon.check class="h-3 w-3 mr-1" />
                                            OK
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 sm:px-6 py-4 text-center text-stone-500 dark:text-stone-400 text-xs sm:text-sm">
                                    No divisions found
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
    <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm">
        <div class="p-4 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-stone-900 dark:text-stone-100 mb-6">
                Inventory by Category
            </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
                        <thead class="bg-stone-50 dark:bg-stone-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider">Total Items</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider">Total Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider">Percentage</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-stone-800 divide-y divide-stone-200 dark:divide-stone-700">
                            @forelse($this->categoryInventory as $category)
                                <tr class="hover:bg-stone-50 dark:hover:bg-stone-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-stone-900 dark:text-stone-100">{{ $category['name'] }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-stone-900 dark:text-stone-100 font-semibold">{{ number_format($category['total_items']) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-stone-600 dark:text-stone-400">₱{{ number_format($category['total_value'], 2) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $totalItems = collect($this->categoryInventory)->sum('total_items');
                                            $percentage = $totalItems > 0 ? round(($category['total_items'] / $totalItems) * 100, 1) : 0;
                                        @endphp
                                        <div class="flex items-center">
                                            <div class="w-16 bg-stone-200 dark:bg-stone-700 rounded-full h-2 mr-3">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
            </div>
                                            <span class="text-sm text-stone-600 dark:text-stone-400">{{ $percentage }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-stone-500 dark:text-stone-400">
                                        No categories found
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
    <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm">
        <div class="p-4 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-stone-900 dark:text-stone-100 mb-6">
                Spending by Supplier
            </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
                        <thead class="bg-stone-50 dark:bg-stone-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider">Supplier</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider">Total Spent</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider">Items Purchased</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider">Contracts</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider">Avg per Item</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-stone-800 divide-y divide-stone-200 dark:divide-stone-700">
                            @forelse($this->supplierSpending as $supplier)
                                <tr class="hover:bg-stone-50 dark:hover:bg-stone-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-stone-200 dark:bg-stone-700 flex items-center justify-center">
                                                    <x-flux::icon.truck class="h-5 w-5 text-stone-600 dark:text-stone-400" />
            </div>
        </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-stone-900 dark:text-stone-100">{{ $supplier['name'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-stone-900 dark:text-stone-100 font-semibold">₱{{ number_format($supplier['total_spent'], 2) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-stone-600 dark:text-stone-400">{{ number_format($supplier['total_items']) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ $supplier['contracts_count'] }} contracts
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-stone-600 dark:text-stone-400">
                                            @if($supplier['total_items'] > 0)
                                                ₱{{ number_format($supplier['total_spent'] / $supplier['total_items'], 2) }}
                                            @else
                                                -
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-stone-500 dark:text-stone-400">
                                        No suppliers found
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
    
    @if ($tab === 'recent-activity')
    <!-- Recent Activity Feed -->
    <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm">
        <div class="p-4 sm:p-6">
            <h3 class="text-lg font-medium leading-6 text-stone-900 dark:text-stone-100 mb-6">
                Recent Activity
            </h3>
            <div class="flow-root">
                <ul role="list" class="-mb-8">
                    @forelse($this->recentActivity as $activity)
                        <li>
                            <div class="relative pb-8">
                                @if(!$loop->last)
                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-stone-200 dark:bg-stone-700" aria-hidden="true"></span>
                                @endif
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-stone-800 
                                            @if($activity['action'] === 'created') bg-green-500
                                            @elseif($activity['action'] === 'updated') bg-blue-500
                                            @elseif($activity['action'] === 'deleted') bg-red-500
                                            @else bg-stone-500
                                            @endif">
                                            @if($activity['action'] === 'created')
                                                <x-flux::icon.plus-circle class="h-4 w-4 text-white" />
                                            @elseif($activity['action'] === 'updated')
                                                <x-flux::icon.edit class="h-4 w-4 text-white" />
                                            @elseif($activity['action'] === 'deleted')
                                                <x-flux::icon.x-mark class="h-4 w-4 text-white" />
                                            @else
                                                <x-flux::icon.settings-2 class="h-4 w-4 text-white" />
                                            @endif
                                        </span>
            </div>
                                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p class="text-sm text-stone-600 dark:text-stone-400">
                                                <span class="font-medium text-stone-900 dark:text-stone-100">{{ $activity['user_name'] }}</span>
                                                {{ $activity['action'] }} 
                                                <span class="font-medium text-stone-900 dark:text-stone-100">{{ $activity['table'] }}</span>
                                                @if($activity['description'])
                                                    - {{ $activity['description'] }}
                                                @endif
                                            </p>
                                        </div>
                                        <div class="text-right text-sm whitespace-nowrap text-stone-500 dark:text-stone-400">
                                            <time datetime="{{ $activity['created_at'] }}">{{ $activity['time_ago'] }}</time>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-center py-8">
                            <div class="flex flex-col items-center">
                                <x-flux::icon.clock-history class="h-12 w-12 text-stone-400 dark:text-stone-600 mb-4" />
                                <p class="text-stone-500 dark:text-stone-400">No recent activity found</p>
                            </div>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
    @endif

    @if ($tab === 'user-management')
    <!-- User Management Overview -->
    <div class="space-y-4 sm:space-y-6">
        <!-- User Statistics Cards -->
        <div class="grid grid-cols-1 gap-3 sm:gap-4 lg:gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="bg-white dark:bg-stone-800 overflow-hidden shadow-sm rounded-lg border border-stone-200 dark:border-stone-700">
                <div class="p-3 sm:p-4 lg:p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-flux::icon.users class="h-5 w-5 sm:h-6 sm:w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="ml-3 sm:ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-xs sm:text-sm font-medium text-stone-500 dark:text-stone-400 truncate">Total Users</dt>
                                <dd class="text-base sm:text-lg font-medium text-stone-900 dark:text-stone-100">{{ number_format($this->userManagement['total_users']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-stone-800 overflow-hidden shadow-sm rounded-lg border border-stone-200 dark:border-stone-700">
                <div class="p-3 sm:p-4 lg:p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-flux::icon.settings-2 class="h-5 w-5 sm:h-6 sm:w-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div class="ml-3 sm:ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-xs sm:text-sm font-medium text-stone-500 dark:text-stone-400 truncate">Admin Users</dt>
                                <dd class="text-base sm:text-lg font-medium text-stone-900 dark:text-stone-100">{{ number_format($this->userManagement['admin_users']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-stone-800 overflow-hidden shadow-sm rounded-lg border border-stone-200 dark:border-stone-700">
                <div class="p-3 sm:p-4 lg:p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-flux::icon.check class="h-5 w-5 sm:h-6 sm:w-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div class="ml-3 sm:ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-xs sm:text-sm font-medium text-stone-500 dark:text-stone-400 truncate">Verified Users</dt>
                                <dd class="text-base sm:text-lg font-medium text-stone-900 dark:text-stone-100">{{ number_format($this->userManagement['verified_users']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-stone-800 overflow-hidden shadow-sm rounded-lg border border-stone-200 dark:border-stone-700">
                <div class="p-3 sm:p-4 lg:p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-flux::icon.plus-circle class="h-5 w-5 sm:h-6 sm:w-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div class="ml-3 sm:ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-xs sm:text-sm font-medium text-stone-500 dark:text-stone-400 truncate">Recent Registrations</dt>
                                <dd class="text-base sm:text-lg font-medium text-stone-900 dark:text-stone-100">{{ number_format($this->userManagement['recent_registrations']) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-2">
            <!-- Role Distribution -->
            <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm border border-stone-200 dark:border-stone-700">
                <div class="p-4 sm:p-6">
                    <h3 class="text-base sm:text-lg font-medium text-stone-900 dark:text-stone-100 mb-3 sm:mb-4">Role Distribution</h3>
                    <div class="space-y-4">
                        @foreach($this->userManagement['role_distribution'] as $role)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-4 h-4 rounded-full {{ $role['color'] }} bg-current mr-3"></div>
                                    <span class="text-sm font-medium text-stone-700 dark:text-stone-300">{{ $role['role'] }}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-stone-600 dark:text-stone-400">{{ number_format($role['count']) }}</span>
                                    <div class="w-16 bg-stone-200 dark:bg-stone-700 rounded-full h-2">
                                        @php
                                            $percentage = $this->userManagement['total_users'] > 0 ? ($role['count'] / $this->userManagement['total_users']) * 100 : 0;
                                        @endphp
                                        <div class="h-2 rounded-full {{ $role['color'] }} bg-current" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- User Activity Stats -->
            <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm border border-stone-200 dark:border-stone-700">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-stone-900 dark:text-stone-100 mb-4">User Activity</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-3 border-b border-stone-200 dark:border-stone-700">
                            <div class="flex items-center">
                                <x-flux::icon.clock-history class="h-5 w-5 text-stone-400 mr-3" />
                                <span class="text-sm font-medium text-stone-700 dark:text-stone-300">Recent Admin Logins (7 days)</span>
                            </div>
                            <span class="text-sm font-semibold text-stone-900 dark:text-stone-100">{{ number_format($this->userManagement['recent_admin_logins']) }}</span>
                        </div>
                        
                        <div class="flex items-center justify-between py-3 border-b border-stone-200 dark:border-stone-700">
                            <div class="flex items-center">
                                <x-flux::icon.users class="h-5 w-5 text-stone-400 mr-3" />
                                <span class="text-sm font-medium text-stone-700 dark:text-stone-300">Active Admins (30 days)</span>
                            </div>
                            <span class="text-sm font-semibold text-stone-900 dark:text-stone-100">{{ number_format($this->userManagement['active_admins']) }}</span>
                        </div>
                        
                        <div class="flex items-center justify-between py-3 border-b border-stone-200 dark:border-stone-700">
                            <div class="flex items-center">
                                <x-flux::icon.package class="h-5 w-5 text-stone-400 mr-3" />
                                <span class="text-sm font-medium text-stone-700 dark:text-stone-300">Inventory Managers</span>
                            </div>
                            <span class="text-sm font-semibold text-stone-900 dark:text-stone-100">{{ number_format($this->userManagement['inventory_managers']) }}</span>
                        </div>
                        
                        <div class="flex items-center justify-between py-3">
                            <div class="flex items-center">
                                <x-flux::icon.x-mark class="h-5 w-5 text-amber-500 mr-3" />
                                <span class="text-sm font-medium text-stone-700 dark:text-stone-300">Unverified Users</span>
                            </div>
                            <span class="text-sm font-semibold text-amber-600 dark:text-amber-400">{{ number_format($this->userManagement['unverified_users']) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm border border-stone-200 dark:border-stone-700">
            <div class="p-4 sm:p-6">
                <h3 class="text-base sm:text-lg font-medium text-stone-900 dark:text-stone-100 mb-3 sm:mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 gap-3 sm:gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <a href="{{ route('admin.system.users.index') }}" wire:navigate class="flex items-center justify-center p-3 sm:p-4 border-2 border-dashed border-stone-300 dark:border-stone-600 rounded-lg hover:border-stone-400 dark:hover:border-stone-500 transition-colors min-h-[80px] sm:min-h-[100px]">
                        <div class="text-center">
                            <x-flux::icon.users class="h-6 w-6 sm:h-8 sm:w-8 text-stone-400 mx-auto mb-1 sm:mb-2" />
                            <p class="text-xs sm:text-sm font-medium text-stone-600 dark:text-stone-400 leading-tight">View All Users</p>
                        </div>
                    </a>
                    
                    <a href="{{ route('admin.system.users.create') }}" wire:navigate class="flex items-center justify-center p-3 sm:p-4 border-2 border-dashed border-stone-300 dark:border-stone-600 rounded-lg hover:border-stone-400 dark:hover:border-stone-500 transition-colors min-h-[80px] sm:min-h-[100px]">
                        <div class="text-center">
                            <x-flux::icon.plus-circle class="h-6 w-6 sm:h-8 sm:w-8 text-stone-400 mx-auto mb-1 sm:mb-2" />
                            <p class="text-xs sm:text-sm font-medium text-stone-600 dark:text-stone-400 leading-tight">Add New User</p>
                        </div>
                    </a>
                    
                    <a href="{{ route('admin.system.audit-logs.index') }}" wire:navigate class="flex items-center justify-center p-3 sm:p-4 border-2 border-dashed border-stone-300 dark:border-stone-600 rounded-lg hover:border-stone-400 dark:hover:border-stone-500 transition-colors min-h-[80px] sm:min-h-[100px]">
                        <div class="text-center">
                            <x-flux::icon.document-text class="h-6 w-6 sm:h-8 sm:w-8 text-stone-400 mx-auto mb-1 sm:mb-2" />
                            <p class="text-xs sm:text-sm font-medium text-stone-600 dark:text-stone-400 leading-tight">View Audit Logs</p>
                        </div>
                    </a>
                    
                    <a href="{{ route('admin.data.employees-and-divisions') }}" wire:navigate class="flex items-center justify-center p-3 sm:p-4 border-2 border-dashed border-stone-300 dark:border-stone-600 rounded-lg hover:border-stone-400 dark:hover:border-stone-500 transition-colors min-h-[80px] sm:min-h-[100px]">
                        <div class="text-center">
                            <x-flux::icon.building-2 class="h-6 w-6 sm:h-8 sm:w-8 text-stone-400 mx-auto mb-1 sm:mb-2" />
                            <p class="text-xs sm:text-sm font-medium text-stone-600 dark:text-stone-400 leading-tight">Manage Divisions</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div> 