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
        // Initialize charts on component mount
        $this->initializeCharts();
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
        
        // Generate checksum for change detection
        $currentChecksum = md5(serialize([$inventoryData, $categoryData]));
        
        // Emit events to initialize charts
        $this->dispatch('initializeLineChart', [
            'chartId' => 'line-chart-canvas',
            'data' => $inventoryData
        ]);
        
        $this->dispatch('initializeDoughnutChart', [
            'chartId' => 'donut-chart-canvas', 
            'data' => $categoryData
        ]);
        
        $this->chartDataChecksum = $currentChecksum;
    }
    
    private function updateChartsIfNeeded(): void
    {
        // Get current chart data
        $inventoryData = $this->inventoryValueOverTime;
        $categoryData = $this->categoryDistribution;
        
        // Generate checksum for change detection
        $currentChecksum = md5(serialize([$inventoryData, $categoryData]));
        
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
    public function categoryDistribution(): array
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
            
            return $data;
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
                ->join('item_specifications', 'items_catalog.id', '=', 'item_specifications.items_catalog_id')
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
                ->join('item_specifications', 'items_catalog.id', '=', 'item_specifications.items_catalog_id')
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
    public function supplierSpending(): array
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
            })->sortByDesc('total_spent')->values()->all();
        });
    }
}; ?>

<div class="w-full mx-auto space-y-6" wire:poll.15s>
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-stone-900 dark:text-stone-100">D'Agriventory</h1>
            <p class="text-stone-500 dark:text-stone-400">Agricultural Inventory Management System</p>
        </div>
    </div>

    <!-- Inventory Alerts -->
    <div class="bg-white dark:bg-stone-800/50 rounded-lg p-4 shadow-sm border border-stone-200 dark:border-stone-700/60">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100 flex items-center">
                <x-flux::icon.bell class="h-5 w-5 mr-2" />
                Inventory Alerts
                <span class="ml-2 inline-flex items-center justify-center min-w-[24px] h-6 px-1.5 text-xs font-bold text-white bg-red-500 rounded-full">{{ collect($this->alerts)->filter(fn($val) => $val > 0)->count() }}</span>
            </h2>
            <button wire:click="toggleAlerts" class="text-sm font-medium text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:hover:text-stone-200">
                {{ $showAllAlerts ? 'Dismiss' : 'Show Alerts' }}
            </button>
        </div>
    @if($showAllAlerts)
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="p-4 bg-stone-50 dark:bg-stone-900 rounded-lg shadow-sm border border-red-200 dark:border-red-900/50">
                <div class="flex items-start">
                    <x-flux::icon.exclamation-triangle class="h-6 w-6 text-red-500 mr-3 flex-shrink-0" />
                    <div>
                        <h3 class="font-semibold text-red-500">Low Stock Alert</h3>
                        <p class="text-sm text-stone-600 dark:text-stone-400 mt-1">{{ $this->alerts['low_stock'] }} consumable items are running low across {{ $this->alerts['low_stock_divisions'] }} divisions</p>
                        <a href="#" class="text-sm font-medium text-red-600 dark:text-red-400 hover:underline mt-2 inline-block">View Items</a>
                    </div>
                </div>
            </div>
            <div class="p-4 bg-stone-50 dark:bg-stone-900 rounded-lg shadow-sm border border-amber-200 dark:border-amber-800/50">
                <div class="flex items-start">
                    <x-flux::icon.clock class="h-6 w-6 text-amber-500 mr-3 flex-shrink-0" />
                    <div>
                        <h3 class="font-semibold text-amber-500">Pending Transfers</h3>
                        <p class="text-sm text-stone-600 dark:text-stone-400 mt-1">{{ $this->alerts['pending_transfers'] }} transfer requests awaiting approval (ICS, PAR, IDR)</p>
                        <a href="#" class="text-sm font-medium text-green-600 dark:text-green-400 hover:underline mt-2 inline-block">Review</a>
                    </div>
                </div>
            </div>
            @if ($this->alerts['expiring_soon'] > 0)
                <div class="p-4 bg-stone-50 dark:bg-stone-900 rounded-lg shadow-sm border border-orange-200 dark:border-orange-900/50">
                    <div class="flex items-start">
                        <x-flux::icon.calendar-days class="h-6 w-6 text-orange-500 mr-3 flex-shrink-0" />
                        <div>
                            <h3 class="font-semibold text-orange-500">Items Expiring Soon</h3>
                            <p class="text-sm text-stone-600 dark:text-stone-400 mt-1">{{ $this->alerts['expiring_soon'] }} items have useful life expiring within 30 days</p>
                            <a href="#" class="text-sm font-medium text-orange-600 dark:text-orange-400 hover:underline mt-2 inline-block">Details</a>
                        </div>
                    </div>
                </div>
            @endif

            @if (($this->alerts['uncategorized_items'] ?? 0) > 0)
                <div class="p-4 bg-stone-50 dark:bg-stone-900 rounded-lg shadow-sm border border-sky-200 dark:border-sky-900/50">
                    <div class="flex items-start">
                        <x-flux::icon.tag class="h-6 w-6 text-sky-500 mr-3 flex-shrink-0" />
                        <div>
                            <h3 class="font-semibold text-sky-500">Uncategorized Items</h3>
                            <p class="text-sm text-stone-600 dark:text-stone-400 mt-1">{{ $this->alerts['uncategorized_items'] ?? 0 }} items are missing category information.</p>
                            <a href="{{ route('admin.data.items-and-categories') }}" wire:navigate class="text-sm font-medium text-green-600 dark:text-green-400 hover:underline mt-2 inline-block">Categorize Items</a>
                        </div>
                    </div>
                </div>
            @endif

            @if (($this->alerts['inactive_suppliers'] ?? 0) > 0)
                <div class="p-4 bg-stone-50 dark:bg-stone-900 rounded-lg shadow-sm border border-teal-200 dark:border-teal-900/50">
                    <div class="flex items-start">
                        <x-flux::icon.truck class="h-6 w-6 text-teal-500 mr-3 flex-shrink-0" />
                        <div>
                            <h3 class="font-semibold text-teal-500">Inactive Suppliers</h3>
                            <p class="text-sm text-stone-600 dark:text-stone-400 mt-1">{{ $this->alerts['inactive_suppliers'] ?? 0 }} suppliers have had no activity in the last year.</p>
                            <a href="{{ route('admin.data.suppliers-and-contracts.suppliers.index') }}" wire:navigate class="text-sm font-medium text-green-600 dark:text-green-400 hover:underline mt-2 inline-block">Review Suppliers</a>
                        </div>
                    </div>
                </div>
            @endif

            @if (($this->alerts['unmanaged_divisions'] ?? 0) > 0)
                <div class="p-4 bg-stone-50 dark:bg-stone-900 rounded-lg shadow-sm border border-purple-200 dark:border-purple-900/50">
                    <div class="flex items-start">
                        <x-flux::icon.user-minus class="h-6 w-6 text-purple-500 mr-3 flex-shrink-0" />
                        <div>
                            <h3 class="font-semibold text-purple-500">Unmanaged Divisions</h3>
                            <p class="text-sm text-stone-600 dark:text-stone-400 mt-1">{{ $this->alerts['unmanaged_divisions'] ?? 0 }} divisions do not have an assigned inventory manager.</p>
                            <a href="{{ route('admin.data.employees-and-divisions.divisions.index') }}" wire:navigate class="text-sm font-medium text-purple-600 dark:text-purple-400 hover:underline mt-2 inline-block">Assign Managers</a>
                        </div>
                    </div>
                </div>
            @endif

            @if (($this->alerts['items_missing_specs'] ?? 0) > 0)
                <div class="p-4 bg-stone-50 dark:bg-stone-900 rounded-lg shadow-sm border border-violet-200 dark:border-violet-900/50">
                    <div class="flex items-start">
                        <x-flux::icon.puzzle-piece class="h-6 w-6 text-violet-500 mr-3 flex-shrink-0" />
                        <div>
                            <h3 class="font-semibold text-violet-500">Items Missing Specs</h3>
                            <p class="text-sm text-stone-600 dark:text-stone-400 mt-1">{{ $this->alerts['items_missing_specs'] }} items in the catalog are missing specifications.</p>
                            <a href="{{ route('admin.data.items-and-categories') }}" wire:navigate class="text-sm font-medium text-violet-600 dark:text-violet-400 hover:underline mt-2 inline-block">Add Details</a>
                        </div>
                    </div>
                </div>
            @endif

            @if (($this->alerts['unassigned_employees'] ?? 0) > 0)
                <div class="p-4 bg-stone-50 dark:bg-stone-900 rounded-lg shadow-sm border border-pink-200 dark:border-pink-900/50">
                    <div class="flex items-start">
                        <x-flux::icon.user-circle class="h-6 w-6 text-pink-500 mr-3 flex-shrink-0" />
                        <div>
                            <h3 class="font-semibold text-pink-500">Unassigned Employees</h3>
                            <p class="text-sm text-stone-600 dark:text-stone-400 mt-1">{{ $this->alerts['unassigned_employees'] }} employees are not yet assigned to a division.</p>
                             <a href="{{ route('admin.data.employees-and-divisions') }}" wire:navigate class="text-sm font-medium text-pink-600 dark:text-pink-400 hover:underline mt-2 inline-block">Assign Division</a>
                        </div>
                    </div>
                </div>
            @endif

            @if (($this->alerts['empty_contracts'] ?? 0) > 0)
                 <div class="p-4 bg-stone-50 dark:bg-stone-900 rounded-lg shadow-sm border border-cyan-200 dark:border-cyan-900/50">
                    <div class="flex items-start">
                        <x-flux::icon.document-minus class="h-6 w-6 text-cyan-500 mr-3 flex-shrink-0" />
                        <div>
                            <h3 class="font-semibold text-cyan-500">Empty Contracts</h3>
                            <p class="text-sm text-stone-600 dark:text-stone-400 mt-1">{{ $this->alerts['empty_contracts'] }} contracts have no items associated with them.</p>
                             <a href="{{ route('admin.data.suppliers-and-contracts.contracts.index') }}" wire:navigate class="text-sm font-medium text-cyan-600 dark:text-cyan-400 hover:underline mt-2 inline-block">Review Contracts</a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
    </div>

    <!-- Quick Actions -->
    <div>
        <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100 mb-2">Quick Actions</h2>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
            @foreach($this->quickActions as $action)
                <a href="{{ route($action['route']) }}" class="flex flex-col items-center justify-center p-4 bg-white dark:bg-stone-800 rounded-lg shadow-sm hover:bg-stone-50 dark:hover:bg-stone-700 transition-colors border border-stone-200 dark:border-stone-700">
                    <div class="p-3 bg-stone-100 dark:bg-stone-700 rounded-full">
                         <x-dynamic-component :component="$action['icon']" class="h-6 w-6 text-stone-600 dark:text-stone-300" />
                    </div>
                    <span class="mt-2 text-sm font-medium text-center text-stone-700 dark:text-stone-300">{{ $action['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
        <x-dashboard.stat-card title="Total Items" :value="number_format($this->stats['total_items'])" change="+12.5%" change-type="increase">
            <x-slot:icon>
                <x-flux::icon.cube class="h-8 w-8 text-stone-500" />
            </x-slot:icon>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card title="Total Value" value="₱{{ number_format($this->stats['total_value'] / 1000000, 2) }}M" change="+8.2%" change-type="increase">
            <x-slot:icon>
                <x-flux::icon.banknotes class="h-8 w-8 text-stone-500" />
            </x-slot:icon>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card title="Active Users" :value="$this->stats['active_users']" subtitle="System users">
            <x-slot:icon>
                <x-flux::icon.users class="h-8 w-8 text-stone-500" />
            </x-slot:icon>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card title="Pending Actions" :value="$this->stats['pending_actions']" subtitle="Needs attention">
            <x-slot:icon>
                <x-flux::icon.exclamation-triangle class="h-8 w-8 text-amber-500" />
            </x-slot:icon>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card title="Expiring Soon" :value="$this->stats['expiring_soon']" subtitle="Within 30 days">
            <x-slot:icon>
                <x-flux::icon.calendar-days class="h-8 w-8 text-stone-500" />
            </x-slot:icon>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card title="Total Divisions" :value="$this->stats['total_divisions']" subtitle="Offices/Units">
            <x-slot:icon>
                <x-flux::icon.building-2 class="h-8 w-8 text-stone-500" />
            </x-slot:icon>
        </x-dashboard.stat-card>
    </div>
    
    <!-- Secondary Stats -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($this->secondaryStats as $stat)
            <div class="relative p-4 bg-white dark:bg-stone-900 rounded-lg shadow-sm border border-stone-200 dark:border-stone-800 flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <div class="flex items-center justify-center h-12 w-12 rounded-lg bg-stone-100 dark:bg-stone-800">
                        <x-dynamic-component :component="$stat['icon']" :class="'h-6 w-6 ' . $stat['color']" />
                    </div>
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-stone-500 dark:text-stone-400">{{ $stat['label'] }}</h4>
                    <p class="text-2xl font-bold text-stone-900 dark:text-stone-100 mt-1">{{ $stat['value'] }} <span class="text-sm font-normal">{{ $stat['unit'] }}</span></p>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">
                        <span class="{{ str_starts_with($stat['change'], '+') ? 'text-green-500' : 'text-red-500' }}">{{ $stat['change'] }}</span> vs. last period
                    </p>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Analytics Placeholders -->
    <div>
        <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100 mb-2">Analytics & Reports</h2>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Chart 1: Inventory Value Over Time -->
            <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm p-6 border border-stone-200 dark:border-stone-700">
                <h3 class="text-base font-semibold text-stone-700 dark:text-stone-300 mb-4">Inventory Value Over Time</h3>
                <div class="relative">
                    <!-- Line Chart Container with wire:ignore to prevent Livewire from morphing -->
                    <div wire:ignore class="h-64 relative">
                        <canvas id="line-chart-canvas" class="w-full h-full"></canvas>
                    </div>
                        <!-- Chart Legend -->
                        <div class="flex flex-wrap justify-center mt-3 gap-4 text-xs">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-blue-500 rounded-full mr-1"></div>
                                <span class="text-stone-600 dark:text-stone-400">Total Value</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-500 rounded-full mr-1"></div>
                                <span class="text-stone-600 dark:text-stone-400">ICS</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-yellow-500 rounded-full mr-1"></div>
                                <span class="text-stone-600 dark:text-stone-400">PAR</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-purple-500 rounded-full mr-1"></div>
                                <span class="text-stone-600 dark:text-stone-400">IDR</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-red-500 rounded-full mr-1"></div>
                                <span class="text-stone-600 dark:text-stone-400">Consumables</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart 2: Item Distribution by Category -->
            <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm p-6 border border-stone-200 dark:border-stone-700">
                <h3 class="text-base font-semibold text-stone-700 dark:text-stone-300 mb-4">Item Distribution by Category</h3>
                <div class="flex items-center justify-center">
                    <!-- Donut Chart Container with wire:ignore to prevent Livewire from morphing -->
                    <div wire:ignore class="relative">
                        <canvas id="donut-chart-canvas" width="200" height="200"></canvas>
                    </div>
                    <!-- Legend -->
                    <div class="ml-6 space-y-2">
                        @foreach($this->categoryDistribution as $index => $category)
                            <div class="flex items-center text-sm">
                                <div class="w-3 h-3 rounded-full mr-2" style="background-color: {{ ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16'][$index % 8] }}"></div>
                                <span class="text-stone-700 dark:text-stone-300">{{ $category['name'] }}</span>
                                <span class="ml-auto text-stone-500 dark:text-stone-400">{{ $category['percentage'] }}%</span>
                            </div>
                        @endforeach
                        <div class="mt-4 text-center border-t pt-2">
                            <div class="text-lg font-bold text-stone-800 dark:text-stone-200">{{ array_sum(array_column($this->categoryDistribution, 'count')) }}</div>
                            <div class="text-xs text-stone-500 dark:text-stone-400">Total Items</div>
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
        });
    </script>

    <!-- Tabs -->
    <div class="border-b border-stone-200 dark:border-stone-700">
        <nav class="flex -mb-px space-x-8" aria-label="Tabs">
            <a href="#" wire:click.prevent="setTab('overview')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $tab === 'overview' ? 'text-accent dark:text-accent-content border-accent' : 'text-stone-500 hover:text-stone-700 hover:border-stone-300 dark:text-stone-400 dark:hover:text-stone-200 dark:hover:border-stone-600 border-transparent' }}" @if($tab === 'overview') aria-current="page" @endif>
                Overview
            </a>
            <a href="#" wire:click.prevent="setTab('item-categories')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $tab === 'item-categories' ? 'text-accent dark:text-accent-content border-accent' : 'text-stone-500 hover:text-stone-700 hover:border-stone-300 dark:text-stone-400 dark:hover:text-stone-200 dark:hover:border-stone-600 border-transparent' }}" @if($tab === 'item-categories') aria-current="page" @endif>
                Item Categories
            </a>
            <a href="#" wire:click.prevent="setTab('suppliers')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $tab === 'suppliers' ? 'text-accent dark:text-accent-content border-accent' : 'text-stone-500 hover:text-stone-700 hover:border-stone-300 dark:text-stone-400 dark:hover:text-stone-200 dark:hover:border-stone-600 border-transparent' }}" @if($tab === 'suppliers') aria-current="page" @endif>
                Suppliers
            </a>
            <a href="#" wire:click.prevent="setTab('recent-activity')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $tab === 'recent-activity' ? 'text-accent dark:text-accent-content border-accent' : 'text-stone-500 hover:text-stone-700 hover:border-stone-300 dark:text-stone-400 dark:hover:text-stone-200 dark:hover:border-stone-600 border-transparent' }}" @if($tab === 'recent-activity') aria-current="page" @endif>
                Recent Activity
            </a>
             <a href="#" wire:click.prevent="setTab('user-management')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $tab === 'user-management' ? 'text-accent dark:text-accent-content border-accent' : 'text-stone-500 hover:text-stone-700 hover:border-stone-300 dark:text-stone-400 dark:hover:text-stone-200 dark:hover:border-stone-600 border-transparent' }}" @if($tab === 'user-management') aria-current="page" @endif>
                User Management
            </a>
        </nav>
    </div>

    @if ($tab === 'overview')
    <!-- TODO: [TICKET-XYZ] Implement overview tab content. -->
    <!-- Division Inventory Overview Placeholder -->
    <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm">
        <div class="p-4 sm:p-6">
            <h3 class="text-lg font-medium leading-6 text-stone-900 dark:text-stone-100">
                Division Inventory Overview
            </h3>
            <div class="mt-4 flex items-center justify-center h-64 bg-stone-100 dark:bg-stone-700/50 rounded-md">
                <p class="text-stone-500 dark:text-stone-400">Overview content will be displayed here</p>
            </div>
        </div>
    </div>
    @endif

    @if ($tab === 'item-categories')
    <!-- TODO: [TICKET-XYZ] Implement item categories tab content. -->
    <!-- Item Categories Overview Placeholder -->
    <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm">
        <div class="p-4 sm:p-6">
            <h3 class="text-lg font-medium leading-6 text-stone-900 dark:text-stone-100">
                Inventory by Category
            </h3>
            <div class="mt-4 flex items-center justify-center h-64 bg-stone-100 dark:bg-stone-700/50 rounded-md">
                <p class="text-stone-500 dark:text-stone-400">Item categories content will be displayed here</p>
            </div>
        </div>
    </div>
    @endif

    @if ($tab === 'suppliers')
    <!-- TODO: [TICKET-XYZ] Implement suppliers tab content. -->
    <!-- Supplier Spending Overview Placeholder -->
    <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm">
        <div class="p-4 sm:p-6">
            <h3 class="text-lg font-medium leading-6 text-stone-900 dark:text-stone-100">
                Spending by Supplier
            </h3>
            <div class="mt-4 flex items-center justify-center h-64 bg-stone-100 dark:bg-stone-700/50 rounded-md">
                <p class="text-stone-500 dark:text-stone-400">Supplier spending content will be displayed here</p>
            </div>
        </div>
    </div>
    @endif
    
    @if ($tab === 'recent-activity')
    <!-- TODO: [TICKET-XYZ] Implement recent activity feed. -->
    <!-- Recent Activity Placeholder -->
    <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm">
        <div class="p-4 sm:p-6">
            <h3 class="text-lg font-medium leading-6 text-stone-900 dark:text-stone-100">
                Recent Activity
            </h3>
            <div class="mt-4 flex items-center justify-center h-64 bg-stone-100 dark:bg-stone-700/50 rounded-md">
                <p class="text-stone-500 dark:text-stone-400">Recent activity feed will be displayed here</p>
            </div>
        </div>
    </div>
    @endif

    @if ($tab === 'user-management')
    <!-- TODO: [TICKET-XYZ] Implement user management overview. -->
    <!-- User Management Placeholder -->
    <div class="bg-white dark:bg-stone-800 rounded-lg shadow-sm">
        <div class="p-4 sm:p-6">
            <h3 class="text-lg font-medium leading-6 text-stone-900 dark:text-stone-100">
                User Management Overview
            </h3>
            <div class="mt-4 flex items-center justify-center h-64 bg-stone-100 dark:bg-stone-700/50 rounded-md">
                <p class="text-stone-500 dark:text-stone-400">User management stats will be displayed here</p>
            </div>
        </div>
    </div>
    @endif
</div> 