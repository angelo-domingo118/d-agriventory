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
    public string $view = 'table';
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

    // Column visibility properties
    public array $columns;
    public array $columnGroups = [
        'article' => [
            'label' => 'Article & Description',
            'columns' => [
                'brand_model' => 'Brand/Model',
                'specifications' => 'Specifications',
                'serials' => 'Serial # / ID Data'
            ]
        ],
        'par' => [
            'label' => 'PAR Details',
            'columns' => [
                'quantity' => 'Qty',
                'unit_cost' => 'Unit Cost',
                'codes' => 'Area/Building/Account Codes',
            ]
        ],
        'source' => [
            'label' => 'Document Source',
            'columns' => [
                'contract' => 'Contract/PO',
                'dates' => 'Prepared/Accepted Dates',
                'remarks' => 'Remarks'
            ]
        ],
        'issued' => [
            'label' => 'Issued To',
            'columns' => [
                'division' => 'Division'
            ]
        ]
    ];

    #[Computed]
    public function filtersActive(): bool
    {
        return $this->filterDateFrom || $this->filterDateTo || $this->filterEmployeeId || $this->filterSupplierId || $this->filterPriceMin || $this->filterPriceMax || $this->filterDateType !== 'prepared' || $this->filterArticle || $this->filterSerialNumber || $this->filterContract || $this->filterRemarks || $this->filterInventoryNumber || $this->filterAreaCode || $this->filterBuildingCode || $this->filterAccountCode;
    }

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('view_inventory')) {
            abort(403);
        }
        $this->view = session('par_view_mode', 'table');
        $this->density = session('par_density', 'spacious');

        $defaultColumns = [];
        foreach ($this->columnGroups as $group) {
            foreach (array_keys($group['columns']) as $key) {
                $defaultColumns[$key] = true;
            }
        }
        $this->columns = session('par_column_visibility', $defaultColumns);
    }

    public function updatedColumns($value, $key): void
    {
        session(['par_column_visibility' => $this->columns]);
    }

    public function setView(string $view): void
    {
        $this->view = $view;
        session(['par_view_mode' => $view]);
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

<div x-data="{
    showFilters: @entangle('showFilters'),
    defaultWidths: {
        article: 400,
        par_details: 220,
        doc_source: 300,
        issued_to: 200,
        actions: 120
    },
    columnWidths: {},
    resetColumnWidths() {
        this.columnWidths = { ...this.defaultWidths };
        localStorage.removeItem('par_column_widths');
    },
    resizingColumn: null,
    startX: 0,
    startWidth: 0,
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
            localStorage.setItem('par_column_widths', JSON.stringify(this.columnWidths));
            window.removeEventListener('mousemove', mouseMoveHandler);
            window.removeEventListener('mouseup', mouseUpHandler);
        };

        window.addEventListener('mousemove', mouseMoveHandler);
        window.addEventListener('mouseup', mouseUpHandler);
    }
}" x-init="
    const storedWidths = JSON.parse(localStorage.getItem('par_column_widths') || '{}');
    columnWidths = { ...defaultWidths, ...storedWidths };
">
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
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">View Mode</div>
                        <div class="mt-2 flex overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                             <button
                                 wire:click="setView('table')"
                                 class="flex-1 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $view === 'table' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                             >
                                 Table
                             </button>
                             <button
                                 wire:click="setView('card')"
                                 class="-ml-px flex-1 border-x border-stone-200 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-stone-700 {{ $view === 'card' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                             >
                                 Card
                             </button>
                             <button
                                 wire:click="setView('compact')"
                                 class="-ml-px flex-1 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $view === 'compact' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                             >
                                 Compact
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
                        <label for="perPage" class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Items per Page</label>
                        <flux:select wire:model.live="perPage" id="perPage" class="mt-1">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </flux:select>
                    </div>

                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Visible Columns</div>
                        <div class="mt-2 space-y-2">
                             @foreach ($this->columnGroups as $group)
                                <div class="">
                                     <div class="mb-1 text-xs font-medium text-stone-600 dark:text-stone-300">{{ $group['label'] }}</div>
                                     <div class="space-y-1">
                                        @foreach ($group['columns'] as $key => $label)
                                            <flux:checkbox
                                                wire:model.live="columns.{{ $key }}"
                                                label="{{ $label }}"
                                                id="column-{{ $key }}"
                                            />
                                        @endforeach
                                     </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="mb-2 text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Column Layout</div>
                        <flux:button
                            variant="ghost"
                            x-on:click="resetColumnWidths()"
                            class="w-full justify-center"
                        >
                            <x-flux::icon.rotate-cw class="mr-2 h-4 w-4" />
                            Reset Column Widths
                        </flux:button>
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
                x-on:click="showFilters = !showFilters"
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

    <div x-show="showFilters" x-collapse class="mt-4">
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <div class="border-b border-stone-200 p-4 dark:border-stone-700">
                <h3 class="font-semibold text-stone-800 dark:text-stone-200">Filter Options</h3>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <flux:input wire:model.live.debounce.300ms="filterArticle" label="Article / Description" placeholder="Search item name, brand, model..." />
                    </div>
                    <div class="sm:col-span-3">
                        <flux:input wire:model.live.debounce.300ms="filterSerialNumber" label="Serial Number / ID" placeholder="Search serial numbers..." />
                    </div>
                    <div class="sm:col-span-3">
                        <flux:input wire:model.live.debounce.300ms="filterInventoryNumber" label="Inventory Number" placeholder="e.g. PPE-123-07-2024" />
                    </div>
                    <div class="sm:col-span-3">
                        <flux:input wire:model.live.debounce.300ms="filterContract" label="Contract / PO Number" placeholder="Search contract number..." />
                    </div>
                    <div class="sm:col-span-2">
                        <flux:input wire:model.live.debounce.300ms="filterAreaCode" label="Area Code" placeholder="e.g. RFO" />
                    </div>
                    <div class="sm:col-span-2">
                        <flux:input wire:model.live.debounce.300ms="filterBuildingCode" label="Building Code" placeholder="e.g. Admin" />
                    </div>
                     <div class="sm:col-span-2">
                        <flux:input wire:model.live.debounce.300ms="filterAccountCode" label="Account Code" placeholder="e.g. 10101010" />
                    </div>
                    <div class="sm:col-span-6">
                        <flux:input wire:model.live.debounce.300ms="filterRemarks" label="Remarks" placeholder="Search remarks..." />
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
                    <div class="sm:col-span-2">
                         <label class="block text-sm font-medium text-stone-700 dark:text-stone-300">Unit Cost (₱)</label>
                        <div class="mt-1 grid grid-cols-2 gap-x-2">
                            <flux:input wire:model.live.debounce.500ms="filterPriceMin" type="number" placeholder="Min" />
                            <flux:input wire:model.live.debounce.500ms="filterPriceMax" type="number" placeholder="Max" />
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <flux:select wire:model.live="filterDateType" label="Date Type">
                            <option value="prepared">Prepared Date</option>
                            <option value="accepted">Accepted Date</option>
                            <option value="acquired">Acquired Date</option>
                        </flux:select>
                    </div>
                    <div class="sm:col-span-2">
                        <flux:input wire:model.live="filterDateFrom" type="date" label="Date From" />
                    </div>
                    <div class="sm:col-span-2">
                        <flux:input wire:model.live="filterDateTo" type="date" label="Date To" />
                    </div>
                </div>
            </div>
            <div class="border-t border-stone-200 bg-stone-50 p-4 text-right dark:border-stone-700 dark:bg-stone-800/50">
                <flux:button variant="ghost" wire:click="resetFilters">
                    Reset Filters
                </flux:button>
            </div>
        </div>
    </div>

    <div class="mt-4 flex items-center justify-between">
        <div class="text-sm text-stone-600 dark:text-stone-400">
            @if ($this->parNumbers->total() > 0)
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

        @if ($view === 'table')
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
                                    <tr wire:key="par-{{ $par->id }}" class="hover:bg-stone-50 dark:hover:bg-stone-800/50">
                                        <td class="w-full max-w-md {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} sm:w-auto sm:max-w-none border-r border-stone-300 dark:border-stone-700">
                                            <div class="space-y-2">
                                                <div>
                                                    @if ($par->contractItem?->itemSpecification?->itemCatalog?->name)
                                                        <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($par->contractItem->itemSpecification->itemCatalog->name, [$this->search, $this->filterArticle]) !!}</div>
                                                    @else
                                                        <div class="font-semibold text-stone-900 dark:text-stone-100 italic">Item name not available</div>
                                                    @endif
                                                    @php $spec = $par->contractItem?->itemSpecification; @endphp
                                                    @if ($this->columns['brand_model'] && $densityClasses['show_secondary'] && $spec)
                                                        @if ($spec->brand || $spec->model)
                                                            <div class="{{ $densityClasses['text_meta'] }} text-stone-500">
                                                                {!! \App\Helpers\TextHelper::highlight(collect([$spec->brand, $spec->model])->filter()->join(' / '), [$this->search, $this->filterArticle]) !!}
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>

                                                @if ($this->columns['specifications'] && $densityClasses['show_tertiary'] && $spec?->detailed_specifications)
                                                    <div class="{{ $densityClasses['text_meta'] }}">
                                                        <div class="grid grid-cols-[auto_1fr] gap-x-2">
                                                            <span class="font-semibold uppercase text-stone-500 dark:text-stone-400">Description:</span>
                                                            <p class="text-stone-600 dark:text-stone-300 break-words">
                                                                {!! \App\Helpers\TextHelper::highlight($spec->detailed_specifications, [$this->search, $this->filterArticle]) !!}
                                                            </p>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if($this->columns['serials'])
                                                    <div class="{{ $densityClasses['text_meta'] }}">
                                                        <p class="font-semibold uppercase text-stone-500 dark:text-stone-400">Serial Number(s) / ID Data:</p>
                                                        @if($par->itemBatches->isNotEmpty() && $par->itemBatches->pluck('identification_data')->filter()->isNotEmpty())
                                                            <ul class="mt-1 space-y-2">
                                                                @foreach($par->itemBatches as $batch)
                                                                    @if($batch->identification_data)
                                                                    <li wire:key="batch-{{ $batch->id }}" class="text-stone-600 dark:text-stone-300">
                                                                        @if($par->itemBatches->count() > 1) <span class="font-medium">Batch #{{$loop->iteration}}:</span> @endif
                                                                        {!! \App\Helpers\TextHelper::highlight($batch->identification_data, [$this->search, $this->filterSerialNumber]) !!}
                                                                    </li>
                                                                    @endif
                                                                @endforeach
                                                            </ul>
                                                        @else
                                                            <p class="{{ $densityClasses['text_meta'] }} italic text-stone-500">No identification data recorded.</p>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>

                                            @if(!$densityClasses['show_secondary'])
                                                <div class="mt-1 text-stone-600 dark:text-stone-400 sm:hidden">
                                                    <p>{!! \App\Helpers\TextHelper::highlight($par->assignedEmployee?->name ?? 'Unassigned', $this->search) !!}</p>
                                                    @if($par->assignedEmployee)
                                                        <p>
                                                            @php $divisionName = $par->assignedEmployee->division?->name; @endphp
                                                            @if($divisionName)
                                                                {!! \App\Helpers\TextHelper::highlight($divisionName, $this->search) !!}
                                                            @else
                                                                <span class="italic text-stone-500">No division assigned</span>
                                                            @endif
                                                        </p>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>

                                        <td class="{{ $densityClasses['table_cell_px'] }} align-top {{ $densityClasses['text_base'] }} border-r border-stone-300 dark:border-stone-700">
                                            <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($par->par_number, $this->search) !!}</div>
                                            @if($densityClasses['show_secondary'])
                                            <div class="mt-1 space-y-1 text-stone-600 dark:text-stone-400">
                                                 <div>
                                                     <span class="font-medium">Inventory No:</span>
                                                    @if($par->date_acquired && $par->inventory_code)
                                                        @php $inventoryNumber = $par->inventory_code . '-' . $par->par_number . '-' . $par->date_acquired->format('m-Y'); @endphp
                                                        {!! \App\Helpers\TextHelper::highlight($inventoryNumber, [$this->search, $this->filterInventoryNumber]) !!}
                                                    @else
                                                        <span class="italic text-stone-500">Awaiting acceptance</span>
                                                    @endif
                                                 </div>
                                               @if($this->columns['quantity'])
                                                    <div><span class="font-medium">Quantity:</span> {{ $par->quantity }} {{ $par->contractItem?->itemSpecification?->itemCatalog?->unit ?? 'unit' }}(s)</div>
                                               @endif
                                               @if($this->columns['unit_cost'])<div><span class="font-medium">Unit Cost:</span> ₱{{ number_format($par->contractItem?->unit_price ?? 0, 2) }}</div>@endif
                                               @if($this->columns['codes'] && $densityClasses['show_tertiary'])
                                                    <div><span class="font-medium">Area Code:</span> {{ $par->area_code ?? 'N/A' }}</div>
                                                    <div><span class="font-medium">Building Code:</span> {{ $par->building_code ?? 'N/A' }}</div>
                                                    <div><span class="font-medium">Account Code:</span> {{ $par->account_code ?? 'N/A' }}</div>
                                               @endif
                                            </div>
                                            @endif
                                        </td>

                                        <td class="hidden {{ $densityClasses['table_cell_px'] }} align-top {{ $densityClasses['text_base'] }} lg:table-cell border-r border-stone-300 dark:border-stone-700">
                                            <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($par->contractItem->contract->supplier->name ?? 'Supplier Not Set', $this->search) !!}</div>
                                            @if($densityClasses['show_secondary'])
                                            <div class="mt-1 space-y-1 text-stone-600 dark:text-stone-400">
                                                @if($this->columns['contract'])
                                                    <div>
                                                        <span class="font-medium">Contract/PO:</span>
                                                        @if($par->contractItem?->contract?->contract_po_ib_number)
                                                            {!! \App\Helpers\TextHelper::highlight($par->contractItem->contract->contract_po_ib_number, [$this->search, $this->filterContract]) !!}
                                                        @else
                                                            <span class="italic text-stone-500">Not available</span>
                                                        @endif
                                                    </div>
                                                @endif
                                                @if($this->columns['dates'] && $densityClasses['show_tertiary'])
                                                    <div>
                                                        <span class="font-medium">Prepared:</span>
                                                        @if($par->date_prepared)
                                                            {{ $par->date_prepared->format('M d, Y') }}
                                                        @else
                                                            <span class="italic text-stone-500">Not set</span>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <span class="font-medium">Accepted:</span>
                                                        @if($par->date_accepted)
                                                            {{ $par->date_accepted->format('M d, Y') }}
                                                        @else
                                                            <span class="italic text-stone-500">Not set</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                            @endif
                                            @if($this->columns['remarks'] && $par->remarks)
                                                <div class="mt-2 text-xs text-stone-500 italic">"{!! \App\Helpers\TextHelper::highlight($par->remarks, [$this->search, $this->filterRemarks]) !!}"</div>
                                            @endif
                                        </td>

                                        <td class="hidden {{ $densityClasses['table_cell_px'] }} align-top {{ $densityClasses['text_base'] }} sm:table-cell border-r border-stone-300 dark:border-stone-700">
                                            <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($par->assignedEmployee?->name ?? 'Unassigned', $this->search) !!}</div>
                                             @if($this->columns['division'] && $par->assignedEmployee)
                                                @php $divisionName = $par->assignedEmployee->division?->name; @endphp
                                                <div class="text-stone-600 dark:text-stone-400">
                                                    @if($divisionName)
                                                        {!! \App\Helpers\TextHelper::highlight($divisionName, $this->search) !!}
                                                    @else
                                                        <span class="italic text-stone-500">No division assigned</span>
                                                    @endif
                                                </div>
                                             @endif
                                        </td>

                                        <td class="{{ $densityClasses['table_cell'] }} pl-3 pr-4 text-right align-top {{ $densityClasses['text_base'] }} font-medium sm:pr-6">
                                            <div class="flex items-center justify-end gap-x-2">
                                                <a href="{{ route('admin.inventory.par.show', $par) }}" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                                                    View<span class="sr-only">, {{ $par->par_number }}</span>
                                                </a>
                                                <a href="{{ route('admin.inventory.par.edit', $par) }}" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                                                    Edit<span class="sr-only">, {{ $par->par_number }}</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
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
        @elseif($view === 'card')
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 {{ $densityClasses['card_container'] }}">
                @forelse ($this->parNumbers as $par)
                    <div wire:key="par-card-{{ $par->id }}" class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-black ring-opacity-5 dark:bg-stone-800 dark:ring-stone-700">
                        <div class="{{ $densityClasses['card_padding'] }}">
                            <div class="flex items-start justify-between">
                                <div class="max-w-xs">
                                    <p class="truncate {{ $densityClasses['text_base'] }} font-semibold text-stone-900 dark:text-stone-100">
                                        @if ($par->contractItem?->itemSpecification?->itemCatalog?->name)
                                            {!! \App\Helpers\TextHelper::highlight($par->contractItem->itemSpecification->itemCatalog->name, [$this->search, $this->filterArticle]) !!}
                                        @else
                                            <span class="italic">Item name not available</span>
                                        @endif
                                    </p>
                                     @php $spec = $par->contractItem?->itemSpecification; @endphp
                                     @if ($densityClasses['show_secondary'] && $spec && ($spec->brand || $spec->model))
                                        <p class="{{ $densityClasses['text_meta'] }} text-stone-500">
                                            {!! \App\Helpers\TextHelper::highlight(collect([$spec->brand, $spec->model])->filter()->join(' / '), [$this->search, $this->filterArticle]) !!}
                                        </p>
                                    @endif
                                </div>
                                <div class="ml-4 flex-shrink-0">
                                    <span class="inline-flex items-center rounded-full bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-500/20">{!! \App\Helpers\TextHelper::highlight($par->par_number, $this->search) !!}</span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <p class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Issued To</p>
                                <p class="{{ $densityClasses['text_base'] }} font-medium text-stone-800 dark:text-stone-200">{!! \App\Helpers\TextHelper::highlight($par->assignedEmployee?->name ?? 'Unassigned', $this->search) !!}</p>
                                @if($densityClasses['show_secondary'])
                                    @if($par->assignedEmployee)
                                        @php $divisionName = $par->assignedEmployee->division?->name; @endphp
                                        <p class="{{ $densityClasses['text_base'] }} text-stone-600 dark:text-stone-400">
                                            @if($divisionName)
                                                {!! \App\Helpers\TextHelper::highlight($divisionName, $this->search) !!}
                                            @else
                                                <span class="italic text-stone-500">No division assigned</span>
                                            @endif
                                        </p>
                                    @endif
                                @endif
                            </div>

                             <div class="mt-4">
                                <p class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Serial Number(s) / ID Data</p>
                                 @if($par->itemBatches->isNotEmpty() && $par->itemBatches->pluck('identification_data')->filter()->isNotEmpty())
                                    <ul class="mt-1 space-y-2 {{ $densityClasses['text_meta'] }}">
                                        @foreach($par->itemBatches as $batch)
                                            @if($batch->identification_data)
                                            <li wire:key="card-batch-{{ $batch->id }}" class="text-stone-600 dark:text-stone-300">
                                                @if($par->itemBatches->count() > 1) <span class="font-medium">Batch #{{$loop->iteration}}:</span> @endif
                                                {!! \App\Helpers\TextHelper::highlight($batch->identification_data, [$this->search, $this->filterSerialNumber]) !!}
                                            </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="{{ $densityClasses['text_meta'] }} italic text-stone-500">No identification data recorded.</p>
                                @endif
                            </div>

                            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 {{ $densityClasses['text_base'] }}">
                                <div>
                                    <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Quantity</dt>
                                    <dd class="font-medium text-stone-800 dark:text-stone-200">{{ $par->quantity }} {{ $par->contractItem?->itemSpecification?->itemCatalog?->unit ?? 'unit' }}(s)</dd>
                                </div>
                                <div>
                                    <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Batches</dt>
                                    <dd class="font-medium text-stone-800 dark:text-stone-200">{{ $par->itemBatches->count() }}</dd>
                                </div>
                                <div>
                                    <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Unit Cost</dt>
                                    <dd class="font-medium text-stone-800 dark:text-stone-200">₱{{ number_format($par->contractItem?->unit_price ?? 0, 2) }}</dd>
                                </div>
                                @if($densityClasses['show_secondary'])
                                <div class="col-span-2">
                                    <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Supplier</dt>
                                    <dd class="font-medium text-stone-800 dark:text-stone-200">{!! \App\Helpers\TextHelper::highlight($par->contractItem->contract->supplier->name ?? 'Supplier Not Set', $this->search) !!}</dd>
                                </div>
                                @endif
                            </dl>
                        </div>
                        <div class="border-t border-stone-200 bg-stone-50 {{ $densityClasses['card_footer_padding'] }} dark:border-stone-700 dark:bg-stone-800/50">
                             <div class="flex items-center justify-end gap-x-2">
                                <a href="{{ route('admin.inventory.par.show', $par) }}" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                                    View
                                </a>
                                <a href="{{ route('admin.inventory.par.edit', $par) }}" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                                    Edit
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="sm:col-span-2 lg:col-span-3">
                        <div class="rounded-lg border border-dashed border-stone-300 p-12 text-center dark:border-stone-700">
                             <h3 class="text-lg font-medium text-stone-900 dark:text-stone-100">No Records Found</h3>
                            <p class="mt-1 {{ $densityClasses['text_base'] }} text-stone-500">No PAR records found matching your criteria.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        @elseif($view === 'compact')
             <div class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-black ring-opacity-5 dark:bg-stone-800 dark:ring-stone-700">
                <ul role="list" class="divide-y divide-stone-200 dark:divide-stone-700">
                    @forelse ($this->parNumbers as $par)
                        <li wire:key="par-compact-{{ $par->id }}" class="flex items-center justify-between gap-x-6 px-4 py-3 hover:bg-stone-50 dark:hover:bg-stone-800/50">
                            <div class="flex min-w-0 gap-x-4">
                                <div class="min-w-0 flex-auto">
                                    <p class="truncate text-sm font-semibold leading-6 text-stone-900 dark:text-stone-100">
                                        @if ($par->contractItem?->itemSpecification?->itemCatalog?->name)
                                            {!! \App\Helpers\TextHelper::highlight($par->contractItem->itemSpecification->itemCatalog->name, [$this->search, $this->filterArticle]) !!}
                                        @else
                                            <span class="italic">Item name not available</span>
                                        @endif
                                    </p>
                                    <p class="mt-1 flex flex-wrap items-center text-xs leading-5 text-stone-500">
                                        <span>{!! \App\Helpers\TextHelper::highlight($par->par_number, $this->search) !!}</span>
                                         @if($par->date_acquired)
                                             <span class="mx-1 text-stone-400 dark:text-stone-600">·</span>
                                            @php $inventoryNumber = $par->inventory_code . '-' . $par->par_number . '-' . $par->date_acquired->format('m-Y'); @endphp
                                            <span class="truncate">Inv No: {!! \App\Helpers\TextHelper::highlight($inventoryNumber, [$this->search, $this->filterInventoryNumber]) !!}</span>
                                         @endif
                                        <span class="mx-1 text-stone-400 dark:text-stone-600">·</span>
                                        <span>{{ $par->quantity }} {{ $par->contractItem?->itemSpecification?->itemCatalog?->unit ?? 'unit' }}(s)</span>
                                        @if ($par->contractItem?->unit_price > 0)
                                            <span class="mx-1 text-stone-400 dark:text-stone-600">·</span>
                                            <span>@ ₱{{ number_format($par->contractItem->unit_price, 2) }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="hidden shrink-0 sm:flex sm:flex-col sm:items-end">
                                <p class="text-sm leading-6 text-stone-900 dark:text-white">
                                    {{ $par->assignedEmployee?->name ?? 'Unassigned' }}
                                </p>
                                <p class="mt-1 text-xs leading-5 text-stone-500">
                                    @if($par->assignedEmployee)
                                        @php $divisionName = $par->assignedEmployee->division?->name; @endphp
                                        @if($divisionName)
                                            {!! \App\Helpers\TextHelper::highlight($divisionName, $this->search) !!}
                                        @else
                                            <span class="italic text-stone-500">No division assigned</span>
                                        @endif
                                    @else
                                        &nbsp;
                                   @endif
                                </p>
                            </div>
                            <div class="flex flex-none items-center gap-x-4">
                               <div class="flex items-center justify-end gap-x-2">
                                    <a href="{{ route('admin.inventory.par.show', $par) }}" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2 py-1 text-xs font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                                        View
                                    </a>
                                    <a href="{{ route('admin.inventory.par.edit', $par) }}" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2 py-1 text-xs font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                                        Edit
                                    </a>
                                </div>
                            </div>
                        </li>
                    @empty
                         <li>
                            <div class="p-12 text-center text-sm text-stone-500 dark:text-stone-400">
                                No PAR records found matching your criteria.
                            </div>
                        </li>
                    @endforelse
                </ul>
            </div>
        @endif
    </div>
    <div class="mt-4">
        {{ $this->parNumbers->links() }}
    </div>
</div> 