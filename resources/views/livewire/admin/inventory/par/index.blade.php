<?php

use App\Models\Employee;
use App\Models\ParNumber;
use App\Models\Supplier;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $showFilters = false;
    
    // New state properties
    public string $groupBy = 'none'; // 'none' or 'employee'
    public string $viewMode = 'table'; // 'table' or 'card'
    
    public string $density = 'spacious';
    public int $perPage = 10;

    // Filter properties
    public ?string $filterDateType = 'prepared';
    public ?string $filterDateFrom = null;
    public ?string $filterDateTo = null;
    public ?int $filterEmployeeId = null;
    public ?int $filterSupplierId = null;
    public ?int $filterPriceMin = null;
    public ?int $filterPriceMax = null;
    public string $filterArticle = '';
    public string $filterSerialNumber = '';
    public string $filterContract = '';
    public string $filterRemarks = '';
    public string $filterInventoryNumber = '';
    public string $filterAreaCode = '';
    public string $filterBuildingCode = '';
    public string $filterAccountCode = '';

    // Sorting properties
    public string $sortColumn = 'par_number.date_prepared';
    public string $sortDirection = 'desc';

    public array $openGroups = [];

    #[Computed]
    public function filtersActive(): bool
    {
        return $this->filterDateFrom || $this->filterDateTo || $this->filterEmployeeId || $this->filterSupplierId || $this->filterPriceMin || $this->filterPriceMax || $this->filterDateType !== 'prepared' || $this->filterArticle || $this->filterSerialNumber || $this->filterContract || $this->filterRemarks || $this->filterInventoryNumber || $this->filterAreaCode || $this->filterBuildingCode || $this->filterAccountCode;
    }
    
    #[Computed]
    public function activeFiltersCount(): int
    {
        $filters = [
            $this->filterDateFrom,
            $this->filterDateTo,
            $this->filterEmployeeId,
            $this->filterSupplierId,
            $this->filterPriceMin,
            $this->filterPriceMax,
            $this->filterArticle,
            $this->filterSerialNumber,
            $this->filterContract,
            $this->filterRemarks,
            $this->filterInventoryNumber,
            $this->filterAreaCode,
            $this->filterBuildingCode,
            $this->filterAccountCode,
        ];

        // Special case for date type, as 'prepared' is the default
        $count = $this->filterDateType !== 'prepared' ? 1 : 0;

        return $count + collect($filters)->filter()->count();
    }

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('view_inventory')) {
            abort(403);
        }
        $this->groupBy = session('par_group_by', 'none');
        $this->viewMode = session('par_view_mode', 'table');
        $this->density = session('par_density', 'spacious');
    }

    public function setGroupBy(string $groupBy): void
    {
        $this->groupBy = $groupBy;
        session(['par_group_by' => $groupBy]);
        $this->resetPage();
    }

    public function setViewMode(string $viewMode): void
    {
        $this->viewMode = $viewMode;
        session(['par_view_mode' => $viewMode]);
    }

    public function setDensity(string $density): void
    {
        $this->density = $density;
        session(['par_density' => $density]);
    }

    public function sortBy(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function resetSorting(): void
    {
        $this->sortColumn = 'par_number.date_prepared';
        $this->sortDirection = 'desc';
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function parNumbers()
    {
        $search = addcslashes($this->search, '%_');
        $lowerSearch = strtolower($search);

        $query = ParNumber::with([
            'assignedEmployee.division',
            'contractItem.itemSpecification.itemCatalog',
            'latestTransfer.toEmployee',
            'itemBatches',
        ])
            ->select('par_number.*');

        // Eager loading takes care of getting the data, but for sorting and searching
        // across relationships, we need to join the tables.
        $query
            ->leftJoin('employees', 'par_number.assigned_employee_id', '=', 'employees.id')
            ->leftJoin('divisions', 'employees.division_id', '=', 'divisions.id')
            ->leftJoin('contract_items', 'par_number.contract_item_id', '=', 'contract_items.id')
            ->leftJoin('contracts', 'contract_items.contract_id', '=', 'contracts.id')
            ->leftJoin('suppliers', 'contracts.supplier_id', '=', 'suppliers.id')
            ->leftJoin('item_specifications', 'contract_items.item_specification_id', '=', 'item_specifications.id')
            ->leftJoin('items_catalog', 'item_specifications.item_catalog_id', '=', 'items_catalog.id');

        $dbDriver = DB::connection()->getDriverName();
        $inventoryNumberExpression = $dbDriver === 'sqlite'
            ? "LOWER(par_number.inventory_code || '-' || par_number.par_number || '-' || STRFTIME('%m-%Y', par_number.date_acquired))"
            : "LOWER(CONCAT_WS('-', par_number.inventory_code, par_number.par_number, DATE_FORMAT(par_number.date_acquired, '%m-%Y')))";

        $query
            ->when($this->search, function ($query) use ($search, $lowerSearch, $inventoryNumberExpression) {
                $query->where(function ($q) use ($search, $lowerSearch, $inventoryNumberExpression) {
                    $q->where(DB::raw('LOWER(par_number.par_number)'), 'like', '%' . $lowerSearch . '%')
                        ->orWhere(DB::raw($inventoryNumberExpression), 'like', '%' . $lowerSearch . '%')
                        ->orWhere(DB::raw('LOWER(par_number.remarks)'), 'like', '%' . $lowerSearch . '%')
                        ->orWhere(DB::raw('LOWER(items_catalog.name)'), 'like', '%' . $lowerSearch . '%')
                        ->orWhere(DB::raw('LOWER(item_specifications.brand)'), 'like', '%' . $lowerSearch . '%')
                        ->orWhere(DB::raw('LOWER(item_specifications.model)'), 'like', '%' . $lowerSearch . '%')
                        ->orWhere(DB::raw('LOWER(item_specifications.detailed_specifications)'), 'like', '%' . $lowerSearch . '%')
                        ->orWhere(DB::raw('LOWER(employees.name)'), 'like', '%' . $lowerSearch . '%')
                        ->orWhere(DB::raw('LOWER(divisions.name)'), 'like', '%' . $lowerSearch . '%')
                        ->orWhere(DB::raw('LOWER(suppliers.name)'), 'like', '%' . $lowerSearch . '%')
                        ->orWhere(DB::raw('LOWER(contracts.contract_po_ib_number)'), 'like', '%' . $lowerSearch . '%')
                        ->orWhereHas('itemBatches', function ($subq) use ($lowerSearch) {
                            $subq->where(DB::raw('LOWER(identification_data)'), 'like', '%' . $lowerSearch . '%');
                        });
                });
            })
            ->when($this->filterArticle, function ($query, $search) {
                $lowerSearch = strtolower($search);
                $query->where(function ($q) use ($lowerSearch) {
                    $q->where(DB::raw('LOWER(items_catalog.name)'), 'like', '%' . $lowerSearch . '%')
                        ->orWhere(DB::raw('LOWER(item_specifications.brand)'), 'like', '%' . $lowerSearch . '%')
                        ->orWhere(DB::raw('LOWER(item_specifications.model)'), 'like', '%' . $lowerSearch . '%')
                        ->orWhere(DB::raw('LOWER(item_specifications.detailed_specifications)'), 'like', '%' . $lowerSearch . '%');
                });
            })
            ->when($this->filterSerialNumber, function ($query, $search) {
                $lowerSearch = strtolower($search);
                $query->whereHas('itemBatches', function ($subq) use ($lowerSearch) {
                    $subq->where(DB::raw('LOWER(identification_data)'), 'like', '%' . $lowerSearch . '%');
                });
            })
            ->when($this->filterContract, function ($query, $search) {
                $lowerSearch = strtolower($search);
                $query->where(DB::raw('LOWER(contracts.contract_po_ib_number)'), 'like', '%' . $lowerSearch . '%');
            })
            ->when($this->filterRemarks, function ($query, $search) {
                $lowerSearch = strtolower($search);
                $query->where(DB::raw('LOWER(par_number.remarks)'), 'like', '%' . $lowerSearch . '%');
            })
            ->when($this->filterInventoryNumber, function ($query, $search) use ($inventoryNumberExpression) {
                $lowerSearch = strtolower($search);
                $query->where(DB::raw($inventoryNumberExpression), 'like', '%' . $lowerSearch . '%');
            })
            ->when($this->filterAreaCode, fn($q, $v) => $q->where('par_number.area_code', 'like', "%{$v}%"))
            ->when($this->filterBuildingCode, fn($q, $v) => $q->where('par_number.building_code', 'like', "%{$v}%"))
            ->when($this->filterAccountCode, fn($q, $v) => $q->where('par_number.account_code', 'like', "%{$v}%"))
            ->when($this->filterDateFrom && $this->filterDateTo, function ($q) {
                $dateColumn = 'par_number.' . $this->filterDateType;
                $q->whereBetween($dateColumn, [$this->filterDateFrom, $this->filterDateTo]);
            })
            ->when($this->filterEmployeeId, fn($q) => $q->where('par_number.assigned_employee_id', $this->filterEmployeeId))
            ->when($this->filterSupplierId, function ($query) {
                $query->where('contracts.supplier_id', $this->filterSupplierId);
            })
            ->when($this->filterPriceMin, fn($q) => $q->where('contract_items.unit_price', '>=', $this->filterPriceMin))
            ->when($this->filterPriceMax, fn($q) => $q->where('contract_items.unit_price', '<=', $this->filterPriceMax));

        // Apply sorting
        if ($this->sortColumn && $this->sortDirection) {
            $query->orderBy($this->sortColumn, $this->sortDirection);
        }

        if ($this->groupBy === 'employee') {
            $items = $query->get();

            if (!empty($this->search)) {
                $this->openGroups = $items->pluck('assigned_employee_id')->map(fn($id) => $id ?: 'unassigned')->unique()->all();
            } else {
                $this->openGroups = [];
            }

            // Group by employee, handling unassigned items
            $grouped = $items->groupBy(function ($item) {
                return $item->assigned_employee_id ?: 'unassigned';
            });

            // Sort groups by employee name, 'unassigned' first
            $sortedGroups = $grouped->sortBy(function ($items, $key) {
                if ($key === 'unassigned') {
                    return -1;
                }
                // Check if assignedEmployee exists before accessing name
                return $items->first()->assignedEmployee->name ?? PHP_INT_MAX;
            }, SORT_REGULAR, $this->sortDirection === 'desc');

            $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage('page');

            return new \Illuminate\Pagination\LengthAwarePaginator(
                items: $sortedGroups->forPage($currentPage, $this->perPage),
                total: $sortedGroups->count(),
                perPage: $this->perPage,
                currentPage: $currentPage,
                options: [
                    'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                    'pageName' => 'page',
                ]
            );
        }

        return $query->paginate($this->perPage);
    }

    #[Computed]
    public function employees()
    {
        return Employee::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function suppliers()
    {
        return Supplier::orderBy('name')->get(['id', 'name']);
    }

    public function resetFilters()
    {
        $this->reset('filterDateType', 'filterDateFrom', 'filterDateTo', 'filterEmployeeId', 'filterSupplierId', 'filterPriceMin', 'filterPriceMax', 'filterArticle', 'filterSerialNumber', 'filterContract', 'filterRemarks', 'filterInventoryNumber', 'filterAreaCode', 'filterBuildingCode', 'filterAccountCode');
        $this->filterDateType = 'prepared';
    }

    public function with(): array
    {
        return [
            'parNumbers' => $this->parNumbers,
            'employees' => $this->employees,
            'suppliers' => $this->suppliers,
        ];
    }

    public function create(): void
    {
        $this->redirect(route('admin.inventory.par.create'), navigate: true);
    }
}; ?>

<div x-data="tableResizer('par_column_widths', { article: 400, par_details: 220, doc_source: 300, issued_to: 200, actions: 120 })">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            PAR Management
        </h1>
        <div class="flex items-center gap-x-2">
            <div x-data="{ open: false }" class="relative">
                <flux:button
                    variant="outline"
                    x-on:click="open = !open"
                    class="!p-2"
                >
                    <x-flux::icon.settings-2 class="h-5 w-5"/>
                    <span class="sr-only">Toggle View Options</span>
                </flux:button>

                <div
                    x-show="open"
                    x-on:click.outside="open = false"
                    x-transition
                    class="absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-stone-800 dark:ring-stone-700"
                    style="display: none;"
                >
                    <div class="px-3 py-2">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Group By</div>
                        <div class="mt-2 flex overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                             <button
                                 wire:click="setGroupBy('none')"
                                 class="flex-1 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $groupBy === 'none' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                             >
                                 By PAR Number
                             </button>
                             <button
                                 wire:click="setGroupBy('employee')"
                                 class="-ml-px flex-1 border-x border-stone-200 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-stone-700 {{ $groupBy === 'employee' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                             >
                                 By Employee
                             </button>
                        </div>
                    </div>

                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">View Style</div>
                        <div class="mt-2 flex overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                             <button
                                 wire:click="setViewMode('table')"
                                 class="flex-1 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $viewMode === 'table' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                             >
                                 Table
                             </button>
                             <button
                                 wire:click="setViewMode('card')"
                                 class="-ml-px flex-1 border-x border-stone-200 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-stone-700 {{ $viewMode === 'card' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                             >
                                 Card
                             </button>
                        </div>
                    </div>

                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Density</div>
                         <div class="mt-2 flex overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                             <button wire:click="setDensity('compact')" class="flex-1 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $density === 'compact' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}">
                                Compact
                            </button>
                            <button wire:click="setDensity('comfortable')" class="-ml-px flex-1 border-x border-stone-200 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-stone-700 {{ $density === 'comfortable' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}">
                                Comfortable
                            </button>
                             <button wire:click="setDensity('spacious')" class="-ml-px flex-1 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $density === 'spacious' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}">
                                Spacious
                            </button>
                        </div>
                    </div>
                    
                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Items per Page</div>
                        <div class="mt-2 flex overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                            @foreach ([5, 10, 25, 50] as $count)
                                <button
                                    wire:click="$set('perPage', {{ $count }})"
                                    class="@if(!$loop->first) -ml-px border-l border-stone-200 dark:border-stone-700 @endif flex-1 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $perPage == $count ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                                >
                                    {{ $count }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="mb-2 text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Table Customization</div>
                        <div class="mt-2 space-y-2">
                        <flux:button
                            variant="outline"
                            x-on:click="$dispatch('reset-column-widths')"
                            class="w-full justify-center"
                        >
                            <x-flux::icon.rotate-cw class="mr-2 h-4 w-4" />
                            Reset Column Widths
                        </flux:button>
                            <flux:button
                                variant="outline"
                                wire:click="resetSorting"
                                class="w-full justify-center"
                            >
                                <span class="flex items-center">
                                    <x-flux::icon.chevrons-up-down class="mr-2 h-4 w-4" />
                                    <span>Reset Sort Order</span>
                                </span>
                        </flux:button>
                        </div>
                    </div>
                </div>
            </div>
            <flux:button
                variant="outline"
                wire:click="$refresh"
                class="!p-2"
            >
                <x-flux::icon.rotate-cw class="h-5 w-5" wire:loading.class="animate-spin" />
                <span class="sr-only">Refresh</span>
            </flux:button>
            <flux:button
                variant="outline"
                x-on:click="$wire.showFilters = !$wire.showFilters"
                class="!p-2 @if($this->filtersActive) bg-primary-50 text-primary-600 dark:bg-primary-900/10 dark:text-primary-400 @endif"
            >
                <x-flux::icon.filter class="h-5 w-5"/>
                <span class="sr-only">Toggle Filters</span>
            </flux:button>
            @can('create_inventory')
                <flux:button variant="primary" wire:click="create">
                    Create PAR
                </flux:button>
            @endcan
        </div>
    </div>

    <div class="mt-4 flex items-start gap-x-6">
        <div class="min-w-0 flex-1 space-y-4">
            <div class="flex items-center justify-between">
        <div class="text-sm text-stone-600 dark:text-stone-400">
            @if ($this->groupBy === 'employee')
            @if ($this->parNumbers->total() > 0)
                    <span>Showing employee groups {{ $this->parNumbers->firstItem() }} to {{ $this->parNumbers->lastItem() }} of <strong>{{ $this->parNumbers->total() }}</strong>.</span>
                @else
                    <span>No results found.</span>
                @endif
            @elseif ($this->parNumbers->total() > 0)
                <span>Showing {{ $this->parNumbers->firstItem() }} to {{ $this->parNumbers->lastItem() }} of <strong>{{ $this->parNumbers->total() }}</strong> results.</span>
            @else
                <span>No results found.</span>
            @endif
        </div>
        <div class="w-full max-w-xs">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search anything..."
                icon="magnifying-glass"
                clearable
            />
        </div>
    </div>

    <div class="mt-6 flow-root">
        @php
            $densityClasses = [
                'table_header' => match($density) {
                    'compact' => 'py-2 pl-4 pr-3 sm:pl-6',
                    'comfortable' => 'py-2.5 pl-4 pr-3 sm:pl-6',
                    default => 'py-3.5 pl-4 pr-3 sm:pl-6',
                },
                'table_cell' => match($density) {
                    'compact' => 'py-2 pl-4 pr-3 sm:pl-6',
                    'comfortable' => 'py-3 pl-4 pr-3 sm:pl-6',
                    default => 'py-4 pl-4 pr-3 sm:pl-6',
                },
                'table_cell_px' => match($density) {
                    'compact' => 'px-2 py-2',
                    'comfortable' => 'px-3 py-3',
                    default => 'px-3 py-4',
                },
                'card_container' => match($density) {
                    'compact' => 'gap-4',
                    'comfortable' => 'gap-5',
                    default => 'gap-6',
                },
                'card_padding' => match($density) {
                    'compact' => 'p-3',
                    'comfortable' => 'p-4',
                    default => 'p-5',
                },
                 'card_footer_padding' => match($density) {
                    'compact' => 'p-3',
                    'comfortable' => 'p-3',
                    default => 'p-4',
                },
                // Font sizes
                'text_header' => match($density) {
                    'compact' => 'text-xs',
                    default => 'text-sm',
                },
                'text_base' => match($density) {
                    'compact' => 'text-xs',
                    default => 'text-sm',
                },
                'text_meta' => match($density) {
                    'compact' => 'text-xs',
                    default => 'text-xs',
                },
                // Visibility
                'show_secondary' => match($density) {
                    'compact' => false,
                    default => true,
                },
                'show_tertiary' => match($density) {
                    'compact' => false,
                    'comfortable' => false,
                    default => true,
                },
            ];
        @endphp

        @if($this->groupBy === 'employee')
            <div class="space-y-4" wire:key="search-{{ $this->search }}">
                @forelse ($this->parNumbers as $employeeId => $items)
                    @php
                        $employee = $items->first()->assignedEmployee;
                        $employeeName = $employeeId === 'unassigned' ? 'Unassigned Items' : ($employee->name ?? 'Unknown Employee');

                        // When a search is active, a group is open if it contains results.
                        // Otherwise, fall back to the default state (first few are open).
                        $isOpen = !empty($this->search)
                            ? in_array($employeeId, $this->openGroups)
                            : ($this->parNumbers->count() <= 3 || $employeeId === 'unassigned');
                    @endphp
                    <div x-data="{ open: {{ $isOpen ? 'true' : 'false' }} }" class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-black ring-opacity-5 dark:bg-stone-800 dark:ring-stone-700">
                        <div @click="open = !open" class="flex cursor-pointer items-center justify-between px-4 py-3 hover:bg-stone-50 dark:hover:bg-stone-800/50">
                            <div>
                                <h3 class="font-semibold text-stone-900 dark:text-stone-100 lg:text-lg">{!! \App\Helpers\TextHelper::highlight($employeeName, $this->search) !!}</h3>
                                @if($employee)
                                <p class="text-sm text-stone-500">{{ $employee->division->name ?? 'No division' }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-x-4">
                                <span class="hidden sm:inline-flex items-center rounded-full bg-stone-100 px-3 py-1 text-sm font-medium text-stone-600 dark:bg-stone-700 dark:text-stone-200">{{ $items->count() }} {{ \Illuminate\Support\Str::plural('item', $items->count()) }}</span>
                                <x-flux::icon.chevron-down class="h-6 w-6 transform transition-transform text-stone-500" ::class="{ '-rotate-180': open }" />
                            </div>
                        </div>
                        <div x-show="open" x-collapse style="display: none;">
                             @if ($this->viewMode === 'table')
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
                                        <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                                            @foreach($items as $par)
                                                <x-admin.inventory.par.table-row
                                                    :par="$par"
                                                    :densityClasses="$densityClasses"
                                                    :search="$this->search"
                                                    :filterArticle="$this->filterArticle"
                                                    :filterSerialNumber="$this->filterSerialNumber"
                                                    :filterContract="$this->filterContract"
                                                    :filterRemarks="$this->filterRemarks"
                                                    :filterInventoryNumber="$this->filterInventoryNumber"
                                                    :show-issued-to="true"
                                                />
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @elseif ($this->viewMode === 'card')
                                <div class="border-t border-stone-200 bg-stone-50 p-4 dark:border-stone-700 dark:bg-stone-800/50">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 {{ $densityClasses['card_container'] }}">
                                        @foreach($items as $par)
                                            <x-admin.inventory.par.card
                                                :par="$par"
                                                :densityClasses="$densityClasses"
                                                :search="$this->search"
                                                :filterArticle="$this->filterArticle"
                                                :filterSerialNumber="$this->filterSerialNumber"
                                            />
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-stone-300 p-12 text-center dark:border-stone-700">
                         <h3 class="text-lg font-medium text-stone-900 dark:text-stone-100">No Records Found</h3>
                        <p class="mt-1 {{ $densityClasses['text_base'] }} text-stone-500">No PAR records found matching your criteria.</p>
                    </div>
                @endforelse
            </div>
        @else
            @if ($this->viewMode === 'table')
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden rounded-lg shadow ring-1 ring-black ring-opacity-5 dark:ring-stone-700">
                        <table class="min-w-full divide-y divide-stone-300 dark:divide-stone-700 table-fixed">
                            <thead class="bg-stone-50 dark:bg-stone-800">
                                <tr>
                                    <th scope="col" :style="`width: ${columnWidths.article}px`" class="relative {{ $densityClasses['table_header'] }} border-r border-stone-300 dark:border-stone-700 text-left {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100">
                                        <div wire:click="sortBy('items_catalog.name')" class="flex items-center cursor-pointer">
                                            Article & Description
                                            @if($sortColumn === 'items_catalog.name')
                                                @if($sortDirection === 'asc')
                                                    <x-flux::icon.chevron-up class="ml-2 h-4 w-4" />
                                                @else
                                                    <x-flux::icon.chevron-down class="ml-2 h-4 w-4" />
                                                @endif
                                            @else
                                                <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400 dark:text-stone-500" />
                                            @endif
                                        </div>
                                        <div @mousedown="startResize($event, 'article')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.par_details}px`" class="relative {{ $densityClasses['table_header'] }} border-r border-stone-300 dark:border-stone-700 px-3 text-left {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100">
                                        <div wire:click="sortBy('par_number.par_number')" class="flex items-center cursor-pointer">
                                            PAR Details
                                            @if($sortColumn === 'par_number.par_number')
                                                @if($sortDirection === 'asc')
                                                    <x-flux::icon.chevron-up class="ml-2 h-4 w-4" />
                                                @else
                                                    <x-flux::icon.chevron-down class="ml-2 h-4 w-4" />
                                                @endif
                                            @else
                                                <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400 dark:text-stone-500" />
                                            @endif
                                        </div>
                                        <div @mousedown="startResize($event, 'par_details')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.doc_source}px`" class="relative hidden {{ $densityClasses['table_header'] }} border-r border-stone-300 dark:border-stone-700 px-3 text-left {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100 lg:table-cell">
                                        <div wire:click="sortBy('suppliers.name')" class="flex items-center cursor-pointer">
                                            Document Source
                                            @if($sortColumn === 'suppliers.name')
                                                @if($sortDirection === 'asc')
                                                    <x-flux::icon.chevron-up class="ml-2 h-4 w-4" />
                                                @else
                                                    <x-flux::icon.chevron-down class="ml-2 h-4 w-4" />
                                                @endif
                                            @else
                                                <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400 dark:text-stone-500" />
                                            @endif
                                        </div>
                                        <div @mousedown="startResize($event, 'doc_source')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.issued_to}px`" class="relative hidden {{ $densityClasses['table_header'] }} border-r border-stone-300 dark:border-stone-700 px-3 text-left {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100 sm:table-cell">
                                        <div wire:click="sortBy('employees.name')" class="flex items-center cursor-pointer">
                                            Issued To
                                            @if($sortColumn === 'employees.name')
                                                @if($sortDirection === 'asc')
                                                    <x-flux::icon.chevron-up class="ml-2 h-4 w-4" />
                                                @else
                                                    <x-flux::icon.chevron-down class="ml-2 h-4 w-4" />
                                                @endif
                                            @else
                                                <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400 dark:text-stone-500" />
                                            @endif
                                        </div>
                                        <div @mousedown="startResize($event, 'issued_to')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.actions}px`" class="relative {{ $densityClasses['table_header'] }} pl-3 pr-4 sm:pr-6 text-right {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                                 @forelse ($this->parNumbers as $par)
                                    <x-admin.inventory.par.table-row
                                        :par="$par"
                                        :densityClasses="$densityClasses"
                                        :search="$this->search"
                                        :filterArticle="$this->filterArticle"
                                        :filterSerialNumber="$this->filterSerialNumber"
                                        :filterContract="$this->filterContract"
                                        :filterRemarks="$this->filterRemarks"
                                        :filterInventoryNumber="$this->filterInventoryNumber"
                                        :show-issued-to="false"
                                    />
                                @empty
                                    <tr>
                                        <td colspan="5" class="{{ $densityClasses['table_cell'] }} px-6 py-12 text-center {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">
                                            No PAR records found matching your criteria.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @elseif ($this->viewMode === 'card')
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 {{ $densityClasses['card_container'] }}">
                @forelse ($this->parNumbers as $par)
                    <x-admin.inventory.par.card
                        :par="$par"
                        :densityClasses="$densityClasses"
                        :search="$this->search"
                        :filterArticle="$this->filterArticle"
                        :filterSerialNumber="$this->filterSerialNumber"
                    />
                @empty
                    <div class="sm:col-span-2 lg:col-span-3">
                        <div class="rounded-lg border border-dashed border-stone-300 p-12 text-center dark:border-stone-700">
                             <h3 class="text-lg font-medium text-stone-900 dark:text-stone-100">No Records Found</h3>
                            <p class="mt-1 {{ $densityClasses['text_base'] }} text-stone-500">No PAR records found matching your criteria.</p>
                        </div>
                    </div>
                @endforelse
            </div>
            @endif
        @endif
    </div>
            <div class="mt-4">
                {{ $this->parNumbers->links() }}
            </div>
        </div>

        <aside
            x-show="$wire.showFilters"
            x-transition
            x-cloak
            class="w-96 flex-shrink-0"
        >
            <div class="space-y-4 rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <div class="flex items-center justify-between border-b border-stone-200 p-4 dark:border-stone-700">
                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">Filter Options</h3>
                    @if ($this->activeFiltersCount() > 0)
                        <flux:button
                            variant="ghost"
                            wire:click="resetFilters"
                        >
                            Reset ({{ $this->activeFiltersCount() }})
                        </flux:button>
                    @endif
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <flux:input wire:model.live.debounce.300ms="filterArticle" label="Article / Description" placeholder="Search item name, brand, model..." clearable />
                        </div>
                        <div class="sm:col-span-2">
                            <flux:input wire:model.live.debounce.300ms="filterSerialNumber" label="Serial Number / ID" placeholder="Search serial numbers..." clearable />
                        </div>
                        <div class="sm:col-span-1">
                            <flux:input wire:model.live.debounce.300ms="filterInventoryNumber" label="Inventory Number" placeholder="e.g. PPE-123-07-2024" clearable />
                        </div>
                        <div class="sm:col-span-1">
                            <flux:input wire:model.live.debounce.300ms="filterContract" label="Contract / PO" placeholder="Search contract..." clearable />
                        </div>
                        <div class="sm:col-span-2">
                            <flux:input wire:model.live.debounce.300ms="filterRemarks" label="Remarks" placeholder="Search remarks..." clearable />
                        </div>
                        
                        <div class="sm:col-span-1">
                            <flux:input wire:model.live.debounce.300ms="filterAreaCode" label="Area Code" placeholder="e.g. RFO" clearable />
                        </div>
                        <div class="sm:col-span-1">
                            <flux:input wire:model.live.debounce.300ms="filterBuildingCode" label="Building Code" placeholder="e.g. Admin" clearable />
                        </div>
                        <div class="sm:col-span-2">
                            <flux:input wire:model.live.debounce.300ms="filterAccountCode" label="Account Code" placeholder="e.g. 10101010" clearable />
                        </div>

                        <div class="col-span-full"><hr class="border-stone-200 dark:border-stone-700" /></div>

                        <div class="sm:col-span-2">
                            <flux:select wire:model.live="filterEmployeeId" label="Issued To">
                                <option value="">Any Employee</option>
                                @foreach($this->employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                        <div class="sm:col-span-2">
                             <flux:select wire:model.live="filterSupplierId" label="Supplier">
                                <option value="">Any Supplier</option>
                                @foreach($this->suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>

                        <div class="col-span-full"><hr class="border-stone-200 dark:border-stone-700" /></div>

                        <div class="sm:col-span-2">
                            <flux:select wire:model.live="filterDateType" label="Date Type">
                                <option value="prepared">Prepared Date</option>
                                <option value="accepted">Accepted Date</option>
                                <option value="acquired">Acquired Date</option>
                            </flux:select>
                        </div>
                        <div class="sm:col-span-1">
                            <flux:input wire:model.live="filterDateFrom" type="date" label="Date From" />
                        </div>
                        <div class="sm:col-span-1">
                            <flux:input wire:model.live="filterDateTo" type="date" label="Date To" />
                        </div>

                        <div class="col-span-full"><hr class="border-stone-200 dark:border-stone-700" /></div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300">Unit Cost (₱)</label>
                            <div class="mt-1 grid grid-cols-2 gap-x-2">
                                <flux:input wire:model.live.debounce.500ms="filterPriceMin" type="number" placeholder="Min" />
                                <flux:input wire:model.live.debounce.500ms="filterPriceMax" type="number" placeholder="Max" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('tableResizer', (storageKey, defaultWidths) => ({
            columnWidths: {},
            resizingColumn: null,
            startX: 0,
            startWidth: 0,
            init() {
                const storedWidths = JSON.parse(localStorage.getItem(storageKey) || '{}');
                this.columnWidths = { ...defaultWidths, ...storedWidths };

                this.$root.addEventListener('reset-column-widths', () => {
                    this.columnWidths = { ...defaultWidths };
                    localStorage.removeItem(storageKey);
                });
            },
            startResize(event, column) {
                this.resizingColumn = column;
                this.startX = event.clientX;
                this.startWidth = this.columnWidths[column];
                event.preventDefault();

                const mouseMoveHandler = (e) => {
                    if (!this.resizingColumn) return;
                    const diffX = e.clientX - this.startX;
                    const newWidth = this.startWidth + diffX;
                    if (newWidth > 40) { // min width 40px
                        this.columnWidths[this.resizingColumn] = newWidth;
                    }
                };

                const mouseUpHandler = () => {
                    if (!this.resizingColumn) return;
                    this.resizingColumn = null;
                    localStorage.setItem(storageKey, JSON.stringify(this.columnWidths));
                    window.removeEventListener('mousemove', mouseMoveHandler);
                    window.removeEventListener('mouseup', mouseUpHandler);
                };

                window.addEventListener('mousemove', mouseMoveHandler);
                window.addEventListener('mouseup', mouseUpHandler);
            }
        }));
    });
</script> 