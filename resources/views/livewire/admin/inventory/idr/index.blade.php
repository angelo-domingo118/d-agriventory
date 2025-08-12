<?php

use App\Models\Employee;
use App\Models\IdrNumber;
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
    public string $groupBy = 'none';
    public string $view = 'table';
    public string $density = 'spacious';
    public string $textOverflow = 'nowrap';
    public string $fontSize = 'medium';
    public int $perPage = 10;

    // Filter properties
    public ?string $filterDateType = 'prepared';
    public ?string $filterDateFrom = null;
    public ?string $filterDateTo = null;
    public ?int $filterAssignedEmployeeId = null;
    public ?int $filterApprovingEmployeeId = null;
    public ?int $filterSupplierId = null;
    public ?int $filterPriceMin = null;
    public ?int $filterPriceMax = null;
    public string $filterArticle = '';
    public string $filterSerialNumber = '';
    public string $filterContract = '';
    public string $filterRemarks = '';
    public string $filterInventoryNumber = '';
    public string $filterOrs = '';

    // Sorting properties
    public string $sortColumn = 'idr_number.date_prepared';
    public string $sortDirection = 'desc';



    #[Computed]
    public function filtersActive(): bool
    {
        return $this->filterDateFrom || $this->filterDateTo || $this->filterAssignedEmployeeId || $this->filterApprovingEmployeeId || $this->filterSupplierId || $this->filterPriceMin || $this->filterPriceMax || $this->filterDateType !== 'prepared' || $this->filterArticle || $this->filterSerialNumber || $this->filterContract || $this->filterRemarks || $this->filterInventoryNumber || $this->filterOrs;
    }

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('view_inventory')) {
            abort(403);
        }
        $this->groupBy = session('idr_group_by', 'none');
        $this->view = session('idr_view_mode', 'table');
        $this->density = session('idr_density', 'spacious');
        $this->textOverflow = session('idr_text_overflow', 'nowrap');
        $this->fontSize = session('idr_font_size', 'medium');
        $this->perPage = session('idr_per_page', 10);
    }

    public function setGroupBy(string $groupBy): void
    {
        $this->groupBy = $groupBy;
        session(['idr_group_by' => $groupBy]);
        $this->resetPage();
    }



    public function setView(string $view): void
    {
        $this->view = $view;
        session(['idr_view_mode' => $view]);
    }

    public function setDensity(string $density): void
    {
        $this->density = $density;
        session(['idr_density' => $density]);
    }

    public function setTextOverflow(string $textOverflow): void
    {
        $this->textOverflow = $textOverflow;
        session(['idr_text_overflow' => $textOverflow]);
    }

    public function setFontSize(string $fontSize): void
    {
        $this->fontSize = $fontSize;
        session(['idr_font_size' => $fontSize]);
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
        $this->sortColumn = 'idr_number.date_prepared';
        $this->sortDirection = 'desc';
    }

    public function updatedPerPage(): void
    {
        session(['idr_per_page' => $this->perPage]);
        $this->resetPage();
    }

    #[Computed]
    public function idrNumbers()
    {
        $search = addcslashes($this->search, '%_');

        $query = IdrNumber::with([
            'assignedEmployee.division',
            'approvingEmployee',
            'receivedBy',
            'receivedFrom',
            'contractItem.itemSpecification.itemCatalog',
            'itemBatches',
        ])
            ->select('idr_number.*');

        $query
            ->leftJoin('employees as assigned_employees', 'idr_number.assigned_employee_id', '=', 'assigned_employees.id')
            ->leftJoin('employees as approving_employees', 'idr_number.approving_employee_id', '=', 'approving_employees.id')
            ->leftJoin('contract_items', 'idr_number.contract_item_id', '=', 'contract_items.id')
            ->leftJoin('contracts', 'contract_items.contract_id', '=', 'contracts.id')
            ->leftJoin('suppliers', 'contracts.supplier_id', '=', 'suppliers.id')
            ->leftJoin('item_specifications', 'contract_items.item_specification_id', '=', 'item_specifications.id')
            ->leftJoin('items_catalog', 'item_specifications.item_catalog_id', '=', 'items_catalog.id');

        $query
            ->when($this->search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('idr_number.number', 'like', '%' . $search . '%')
                        ->orWhere('idr_number.inventory_code', 'like', '%' . $search . '%')
                        ->orWhere('idr_number.ors', 'like', '%' . $search . '%')
                        ->orWhere('idr_number.remarks', 'like', '%' . $search . '%')
                        ->orWhere('items_catalog.name', 'like', '%' . $search . '%')
                        ->orWhere('item_specifications.brand', 'like', '%' . $search . '%')
                        ->orWhere('item_specifications.model', 'like', '%' . $search . '%')
                        ->orWhere('assigned_employees.name', 'like', '%' . $search . '%')
                        ->orWhere('approving_employees.name', 'like', '%' . $search . '%')
                        ->orWhere('suppliers.name', 'like', '%' . $search . '%')
                        ->orWhere('contracts.contract_po_ib_number', 'like', '%' . $search . '%')
                        ->orWhereHas('itemBatches', function ($subq) use ($search) {
                            $subq->where('identification_data', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($this->filterArticle, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('items_catalog.name', 'like', '%' . $search . '%')
                        ->orWhere('item_specifications.brand', 'like', '%' . $search . '%')
                        ->orWhere('item_specifications.model', 'like', '%' . $search . '%');
                });
            })
            ->when($this->filterSerialNumber, function ($query, $search) {
                $query->whereHas('itemBatches', function ($subq) use ($search) {
                    $subq->where('identification_data', 'like', '%' . $search . '%');
                });
            })
            ->when($this->filterContract, function ($query, $search) {
                $query->where('contracts.contract_po_ib_number', 'like', '%' . $search . '%');
            })
            ->when($this->filterRemarks, function ($query, $search) {
                $query->where('idr_number.remarks', 'like', '%' . $search . '%');
            })
             ->when($this->filterInventoryNumber, function ($query, $search) {
                $query->where('idr_number.inventory_code', 'like', '%' . $search . '%');
            })
            ->when($this->filterOrs, function ($query, $search) {
                $query->where('idr_number.ors', 'like', '%' . $search . '%');
            })
            ->when($this->filterDateFrom && $this->filterDateTo, function ($q) {
                $dateColumn = $this->filterDateType === 'accepted' ? 'idr_number.date_accepted' : 'idr_number.date_prepared';
                $q->whereBetween($dateColumn, [$this->filterDateFrom, $this->filterDateTo]);
            })
            ->when($this->filterAssignedEmployeeId, fn($q) => $q->where('idr_number.assigned_employee_id', $this->filterAssignedEmployeeId))
            ->when($this->filterApprovingEmployeeId, fn($q) => $q->where('idr_number.approving_employee_id', $this->filterApprovingEmployeeId))
            ->when($this->filterSupplierId, fn($q) => $q->where('contracts.supplier_id', $this->filterSupplierId))
            ->when($this->filterPriceMin, fn($q) => $q->where('contract_items.unit_price', '>=', $this->filterPriceMin))
            ->when($this->filterPriceMax, fn($q) => $q->where('contract_items.unit_price', '<=', $this->filterPriceMax));

        if ($this->sortColumn && $this->sortDirection) {
            $query->orderBy($this->sortColumn, $this->sortDirection);
        }

        // Handle grouping
        if ($this->groupBy === 'supplier') {
            $items = $query->get();

            // Group by supplier
            $grouped = $items->groupBy(function ($item) {
                return $item->contractItem->contract->supplier->name ?? 'Unknown Supplier';
            });

            // Sort groups by supplier name
            $sortedGroups = $grouped->sortKeys();

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

        if ($this->groupBy === 'contract') {
            $items = $query->get();

            // Group by contract
            $grouped = $items->groupBy(function ($item) {
                return $item->contractItem->contract->contract_po_ib_number ?? 'Unknown Contract';
            });

            // Sort groups by contract number
            $sortedGroups = $grouped->sortKeys();

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

        if ($this->groupBy === 'date') {
            $items = $query->get();

            // Group by date prepared
            $grouped = $items->groupBy(function ($item) {
                return $item->date_prepared->format('F Y');
            });

            // Sort groups by date (newest first)
            $sortedGroups = $grouped->sortKeysDesc();

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
        $this->reset('filterDateType', 'filterDateFrom', 'filterDateTo', 'filterAssignedEmployeeId', 'filterApprovingEmployeeId', 'filterSupplierId', 'filterPriceMin', 'filterPriceMax', 'filterArticle', 'filterSerialNumber', 'filterContract', 'filterRemarks', 'filterInventoryNumber', 'filterOrs');
        $this->filterDateType = 'prepared';
    }

    public function create(): void
    {
        $this->redirect(route('admin.inventory.idr.create'), navigate: true);
    }
    
    public function with(): array
    {
        return [
            'idrNumbers' => $this->idrNumbers,
            'employees' => $this->employees,
            'suppliers' => $this->suppliers,
        ];
    }
}; ?>

<div x-data="{
    showFilters: @entangle('showFilters'),
    defaultWidths: {
        article: 400,
        idr_details: 220,
        doc_source: 300,
        personnel: 250,
        actions: 120
    },
    columnWidths: {},
    resetColumnWidths() {
        this.columnWidths = { ...this.defaultWidths };
        localStorage.removeItem('idr_column_widths');
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
            localStorage.setItem('idr_column_widths', JSON.stringify(this.columnWidths));
            window.removeEventListener('mousemove', mouseMoveHandler);
            window.removeEventListener('mouseup', mouseUpHandler);
        };

        window.addEventListener('mousemove', mouseMoveHandler);
        window.addEventListener('mouseup', mouseUpHandler);
    }
}" x-init="
    const storedWidths = JSON.parse(localStorage.getItem('idr_column_widths') || '{}');
    columnWidths = { ...defaultWidths, ...storedWidths };
">
    <div class="flex items-center justify-between">
        <!-- Breadcrumbs as Title -->
        <div>
            <flux:breadcrumbs class="text-2xl font-semibold">
                <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Inventory</flux:breadcrumbs.item>
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">IDR Management</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
        <div class="flex items-center gap-x-2">
            <div x-data="{ open: false }" class="relative">
                 <flux:button variant="outline" x-on:click="open = !open" class="!p-2">
                    <x-flux::icon.settings-2 class="h-5 w-5"/>
                    <span class="sr-only">Toggle View Options</span>
                </flux:button>
                <div x-show="open" x-on:click.outside="open = false" x-transition class="absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-stone-800 dark:ring-stone-700" style="display: none;">
                    <div class="px-3 py-2">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Group By</div>
                        <div class="mt-2 grid grid-cols-2 gap-1 rounded-md border border-stone-200 dark:border-stone-700 p-1">
                            <button wire:click="setGroupBy('none')" class="px-2 py-1 text-center text-xs focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded {{ $groupBy === 'none' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}">None</button>
                            <button wire:click="setGroupBy('supplier')" class="px-2 py-1 text-center text-xs focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded {{ $groupBy === 'supplier' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}">Supplier</button>
                            <button wire:click="setGroupBy('contract')" class="px-2 py-1 text-center text-xs focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded {{ $groupBy === 'contract' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}">Contract</button>
                            <button wire:click="setGroupBy('date')" class="px-2 py-1 text-center text-xs focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded {{ $groupBy === 'date' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}">Date</button>
                        </div>
                    </div>
                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">View Mode</div>
                        <div class="mt-2 flex overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                             <button wire:click="setView('table')" class="flex-1 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $view === 'table' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}">Table</button>
                             <button disabled title="Coming soon" class="-ml-px flex-1 border-x border-stone-200 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-stone-700 text-stone-400 dark:text-stone-500 opacity-60 cursor-not-allowed">Card</button>
                             <button disabled title="Coming soon" class="-ml-px flex-1 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 text-stone-400 dark:text-stone-500 opacity-60 cursor-not-allowed">Compact</button>
                        </div>
                    </div>
                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Density</div>
                         <div class="mt-2 flex overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                             <button wire:click="setDensity('compact')" class="flex-1 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $density === 'compact' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}">Compact</button>
                            <button wire:click="setDensity('comfortable')" class="-ml-px flex-1 border-x border-stone-200 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-stone-700 {{ $density === 'comfortable' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}">Comfortable</button>
                             <button wire:click="setDensity('spacious')" class="-ml-px flex-1 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $density === 'spacious' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}">Spacious</button>
                        </div>
                    </div>

                    <!-- Text Overflow -->
                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Text Overflow</div>
                        <div class="mt-2 flex overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                            <button 
                                wire:click="setTextOverflow('nowrap')" 
                                class="flex-1 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $textOverflow === 'nowrap' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                No Wrap
                            </button>
                            <button 
                                wire:click="setTextOverflow('wrap')" 
                                class="-ml-px flex-1 border-x border-stone-200 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-stone-700 {{ $textOverflow === 'wrap' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Wrap Text
                            </button>
                            <button 
                                wire:click="setTextOverflow('scroll')" 
                                class="-ml-px flex-1 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $textOverflow === 'scroll' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Scroll
                            </button>
                        </div>
                    </div>
                    
                    <!-- Font Size -->
                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Font Size</div>
                        <div class="mt-2 grid grid-cols-2 gap-1 overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                            <button 
                                wire:click="setFontSize('small')" 
                                class="px-2 py-1.5 text-center text-xs focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-tl-sm {{ $fontSize === 'small' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Small
                            </button>
                            <button 
                                wire:click="setFontSize('medium')" 
                                class="px-2 py-1.5 text-center text-xs focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-tr-sm {{ $fontSize === 'medium' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Medium
                            </button>
                            <button 
                                wire:click="setFontSize('large')" 
                                class="px-2 py-1.5 text-center text-xs focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-bl-sm {{ $fontSize === 'large' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Large
                            </button>
                            <button 
                                wire:click="setFontSize('xl')" 
                                class="px-2 py-1.5 text-center text-xs focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-br-sm {{ $fontSize === 'xl' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Extra Large
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
                                x-on:click="resetColumnWidths()"
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
             <flux:button variant="outline" wire:click="$refresh" class="!p-2">
                <x-flux::icon.rotate-cw class="h-5 w-5" wire:loading.class="animate-spin" />
                <span class="sr-only">Refresh</span>
            </flux:button>
             <flux:button variant="outline" x-on:click="showFilters = !showFilters" class="!p-2 @if($this->filtersActive) bg-primary-50 text-primary-600 dark:bg-primary-900/10 dark:text-primary-400 @endif">
                <x-flux::icon.filter class="h-5 w-5"/>
                <span class="sr-only">Toggle Filters</span>
            </flux:button>
            @can('create_inventory')
                <flux:button variant="primary" wire:click="create">
                    Create IDR
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
                        <flux:input wire:model.live.debounce.300ms="filterSerialNumber" label="Serial Number / Batch Data" placeholder="Search serial numbers..." />
                    </div>
                    <div class="sm:col-span-2">
                        <flux:input wire:model.live.debounce.300ms="filterInventoryNumber" label="Inventory Code" placeholder="e.g. 2024-07-001" />
                    </div>
                    <div class="sm:col-span-2">
                        <flux:input wire:model.live.debounce.300ms="filterOrs" label="ORS Number" placeholder="Search ORS number..." />
                    </div>
                    <div class="sm:col-span-2">
                        <flux:input wire:model.live.debounce.300ms="filterContract" label="Contract / PO Number" placeholder="Search contract number..." />
                    </div>
                    <div class="sm:col-span-6">
                        <flux:input wire:model.live.debounce.300ms="filterRemarks" label="Remarks" placeholder="Search remarks..." />
                    </div>

                    <div class="col-span-full"><hr class="border-stone-200 dark:border-stone-700" /></div>

                    <div class="sm:col-span-3">
                        <flux:select wire:model.live="filterAssignedEmployeeId" label="Assigned To (Stock Officer)">
                            <option value="">Any Employee</option>
                            @foreach($this->employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                     <div class="sm:col-span-3">
                        <flux:select wire:model.live="filterApprovingEmployeeId" label="Approving Official">
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
                        <flux:select wire:model.live="filterDateType" label="Date Type">
                            <option value="prepared">Prepared Date</option>
                            <option value="accepted">Accepted Date</option>
                        </flux:select>
                    </div>
                    <div class="sm:col-span-1">
                        <flux:input wire:model.live="filterDateFrom" type="date" label="Date From" />
                    </div>
                    <div class="sm:col-span-1">
                        <flux:input wire:model.live="filterDateTo" type="date" label="Date To" />
                    </div>
                     <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300">Unit Cost (₱)</label>
                        <div class="mt-1 grid grid-cols-2 gap-x-2">
                            <flux:input wire:model.live.debounce.500ms="filterPriceMin" type="number" placeholder="Min" />
                            <flux:input wire:model.live.debounce.500ms="filterPriceMax" type="number" placeholder="Max" />
                        </div>
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
            @if ($this->idrNumbers->total() > 0)
                <span>Showing {{ $this->idrNumbers->firstItem() }} to {{ $this->idrNumbers->lastItem() }} of <strong>{{ $this->idrNumbers->total() }}</strong> results.</span>
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
                // Font sizes (fontSize setting overrides density)
                'text_header' => match($fontSize) {
                    'small' => 'text-xs',
                    'large' => 'text-base',
                    'xl' => 'text-lg',
                    default => match($density) {
                        'compact' => 'text-xs',
                        default => 'text-sm',
                    },
                },
                'text_base' => match($fontSize) {
                    'small' => 'text-xs',
                    'large' => 'text-base',
                    'xl' => 'text-lg',
                    default => match($density) {
                        'compact' => 'text-xs',
                        default => 'text-sm',
                    },
                },
                'text_meta' => match($fontSize) {
                    'small' => 'text-xs',
                    'large' => 'text-sm',
                    'xl' => 'text-base',
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
                'text_overflow' => match($textOverflow) {
                    'wrap' => 'break-words',
                    'scroll' => 'whitespace-nowrap',
                    default => 'whitespace-nowrap truncate',
                },
                'table_wrapper' => match($textOverflow) {
                    'scroll' => 'overflow-x-auto',
                    default => 'overflow-x-auto',
                },
            ];
        @endphp

        @if ($view === 'table')
            @if($this->groupBy !== 'none')
                <div class="space-y-4" wire:key="search-{{ $this->search }}">
                    @forelse ($this->idrNumbers as $groupKey => $items)
                        @php
                            $groupName = match($this->groupBy) {
                                'supplier' => $groupKey,
                                'contract' => "Contract: " . $groupKey,
                                'date' => $groupKey,
                                default => $groupKey
                            };
                        @endphp
                        <div x-data="{ open: true }" class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-black ring-opacity-5 dark:bg-stone-800 dark:ring-stone-700">
                            <div @click="open = !open" class="flex cursor-pointer items-center justify-between px-4 py-3 hover:bg-stone-50 dark:hover:bg-stone-800/50">
                                <div>
                                    <h3 class="font-semibold text-stone-900 dark:text-stone-100 lg:text-lg">{!! \App\Helpers\TextHelper::highlight($groupName, $this->search) !!}</h3>
                                </div>
                                <div class="flex items-center gap-x-4">
                                    <span class="hidden sm:inline-flex items-center rounded-full bg-stone-100 px-3 py-1 text-sm font-medium text-stone-600 dark:bg-stone-700 dark:text-stone-200">{{ $items->count() }} {{ \Illuminate\Support\Str::plural('item', $items->count()) }}</span>
                                    <x-flux::icon.chevron-down class="h-6 w-6 transform transition-transform text-stone-500" ::class="{ '-rotate-180': open }" />
                                </div>
                            </div>
                            <div x-show="open" x-collapse style="display: none;">
                                <div class="{{ $densityClasses['table_wrapper'] }}">
                                    <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
                                        <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                                            @foreach($items as $idrNumber)
                                                <tr wire:key="idr-grouped-{{ $groupKey }}-{{ $idrNumber->id }}" class="hover:bg-stone-50 dark:hover:bg-stone-800/50">
                                                    <td class="w-full max-w-md {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} sm:w-auto sm:max-w-none border-r border-stone-300 dark:border-stone-700">
                                                        <div class="space-y-2">
                                                            <div>
                                                                <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($idrNumber->contractItem->itemSpecification->itemCatalog->name, [$this->search, $this->filterArticle]) !!}</div>
                                                                @if ($densityClasses['show_secondary'])
                                                                    <div class="{{ $densityClasses['text_meta'] }} text-stone-500">{!! \App\Helpers\TextHelper::highlight(collect([$idrNumber->contractItem->itemSpecification->brand, $idrNumber->contractItem->itemSpecification->model])->filter()->join(' / '), [$this->search, $this->filterArticle]) !!}</div>
                                                                @endif
                                                            </div>
                                                            @if ($densityClasses['show_tertiary'])
                                                                <div class="{{ $densityClasses['text_meta'] }}"><p class="text-stone-600 dark:text-stone-300 break-words">{!! \App\Helpers\TextHelper::highlight($idrNumber->contractItem->itemSpecification->detailed_specifications, [$this->search, $this->filterArticle]) !!}</p></div>
                                                            @endif
                                                            <div class="{{ $densityClasses['text_meta'] }}">
                                                                <p class="font-semibold uppercase text-stone-500 dark:text-stone-400">Batches:</p>
                                                                @if($idrNumber->itemBatches->isNotEmpty())
                                                                    <ul class="mt-1 space-y-1">
                                                                        @foreach($idrNumber->itemBatches as $batch)
                                                                            <li class="text-stone-600 dark:text-stone-400">{!! \App\Helpers\TextHelper::highlight($batch->identification_data, [$this->search, $this->filterSerialNumber]) ?: 'No batch data' !!}</li>
                                                                        @endforeach
                                                                    </ul>
                                                                @else
                                                                    <p class="italic text-stone-500">No batches recorded.</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="{{ $densityClasses['table_cell_px'] }} align-top {{ $densityClasses['text_base'] }} border-r border-stone-300 dark:border-stone-700">
                                                        <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($idrNumber->number, $this->search) !!}</div>
                                                        @if($densityClasses['show_secondary'])
                                                        <div class="mt-1 space-y-1 text-stone-600 dark:text-stone-400">
                                                           <div><span class="font-medium">Qty:</span> {{ $idrNumber->quantity }} {{ $idrNumber->contractItem?->itemSpecification?->itemCatalog?->unit ?? 'unit' }}(s)</div>
                                                           <div><span class="font-medium">Cost:</span> ₱{{ number_format($idrNumber->contractItem?->unit_price ?? 0, 2) }}</div>
                                                           <div><span class="font-medium">Inv. Code:</span> {!! \App\Helpers\TextHelper::highlight($idrNumber->inventory_code, [$this->search, $this->filterInventoryNumber]) !!}</div>
                                                           <div><span class="font-medium">ORS:</span> {!! \App\Helpers\TextHelper::highlight($idrNumber->ors, [$this->search, $this->filterOrs]) !!}</div>
                                                        </div>
                                                        @endif
                                                    </td>
                                                    <td class="hidden {{ $densityClasses['table_cell_px'] }} align-top {{ $densityClasses['text_base'] }} lg:table-cell border-r border-stone-300 dark:border-stone-700">
                                                        <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($idrNumber->contractItem->contract->supplier->name ?? 'N/A', $this->search) !!}</div>
                                                        @if($densityClasses['show_secondary'])
                                                        <div class="mt-1 space-y-1 text-stone-600 dark:text-stone-400">
                                                            <div><span class="font-medium">Contract:</span> {!! \App\Helpers\TextHelper::highlight($idrNumber->contractItem->contract->contract_po_ib_number, [$this->search, $this->filterContract]) !!}</div>
                                                            @if($densityClasses['show_tertiary'])
                                                                <div><span class="font-medium">Prepared:</span> {{ $idrNumber->date_prepared->format('M d, Y') }}</div>
                                                                <div><span class="font-medium">Accepted:</span> {{ $idrNumber->date_accepted->format('M d, Y') }}</div>
                                                            @endif
                                                        </div>
                                                        @endif
                                                         @if($idrNumber->remarks)
                                                            <div class="mt-2 text-xs text-stone-500 italic">"{!! \App\Helpers\TextHelper::highlight($idrNumber->remarks, [$this->search, $this->filterRemarks]) !!}"</div>
                                                        @endif
                                                    </td>
                                                    <td class="hidden {{ $densityClasses['table_cell_px'] }} align-top {{ $densityClasses['text_base'] }} sm:table-cell border-r border-stone-300 dark:border-stone-700">
                                                        <div><span class="font-medium">Assigned:</span> {!! \App\Helpers\TextHelper::highlight($idrNumber->assignedEmployee->name, $this->search) !!}</div>
                                                        <div><span class="font-medium">Approved:</span> {!! \App\Helpers\TextHelper::highlight($idrNumber->approvingEmployee->name, $this->search) !!}</div>
                                                        <div><span class="font-medium">Received By:</span> {!! \App\Helpers\TextHelper::highlight($idrNumber->receivedBy->name, $this->search) !!}</div>
                                                        <div><span class="font-medium">Received From:</span> {!! \App\Helpers\TextHelper::highlight($idrNumber->receivedFrom->name, $this->search) !!}</div>
                                                    </td>
                                                    <td class="{{ $densityClasses['table_cell'] }} pl-3 pr-4 text-right align-top {{ $densityClasses['text_base'] }} font-medium sm:pr-6">
                                                        <div class="flex items-center justify-end gap-x-2">
                                                            <a href="{{ route('admin.inventory.idr.show', $idrNumber) }}" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                                                                <x-flux::icon.eye class="mr-1.5 h-4 w-4" />
                                                                View
                                                            </a>
                                                            <a href="{{ route('admin.inventory.idr.edit', $idrNumber) }}" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                                                                <x-flux::icon.edit class="mr-1.5 h-4 w-4" />
                                                                Edit
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <p class="text-stone-500 dark:text-stone-400">No IDR records found.</p>
                        </div>
                    @endforelse
                </div>
            @else
                <div class="-mx-4 -my-2 {{ $densityClasses['table_wrapper'] }} sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <div class="overflow-hidden rounded-lg shadow ring-1 ring-black ring-opacity-5 dark:ring-stone-700">
                            <table class="min-w-full divide-y divide-stone-300 dark:divide-stone-700 table-fixed">
                            <thead class="bg-stone-50 dark:bg-stone-800">
                                <tr>
                                    <th scope="col" :style="`width: ${columnWidths.article}px`" class="relative {{ $densityClasses['table_header'] }} border-r border-stone-300 dark:border-stone-700 text-left {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100">
                                        <div wire:click="sortBy('items_catalog.name')" class="flex items-center cursor-pointer">Article & Description</div>
                                        <div @mousedown="startResize($event, 'article')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.idr_details}px`" class="relative {{ $densityClasses['table_header'] }} border-r border-stone-300 dark:border-stone-700 px-3 text-left {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100">
                                        <div wire:click="sortBy('idr_number.number')" class="flex items-center cursor-pointer">IDR Details</div>
                                        <div @mousedown="startResize($event, 'idr_details')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.doc_source}px`" class="relative hidden {{ $densityClasses['table_header'] }} border-r border-stone-300 dark:border-stone-700 px-3 text-left {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100 lg:table-cell">
                                        <div wire:click="sortBy('suppliers.name')" class="flex items-center cursor-pointer">Document Source</div>
                                        <div @mousedown="startResize($event, 'doc_source')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.personnel}px`" class="relative hidden {{ $densityClasses['table_header'] }} border-r border-stone-300 dark:border-stone-700 px-3 text-left {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100 sm:table-cell">
                                        <div wire:click="sortBy('assigned_employees.name')" class="flex items-center cursor-pointer">Personnel</div>
                                         <div @mousedown="startResize($event, 'personnel')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.actions}px`" class="relative {{ $densityClasses['table_header'] }} pl-3 pr-4 sm:pr-6 text-right {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                                 @forelse ($this->idrNumbers as $idrNumber)
                                    <tr wire:key="idr-{{ $idrNumber->id }}" class="hover:bg-stone-50 dark:hover:bg-stone-800/50">
                                        <td class="w-full max-w-md {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} sm:w-auto sm:max-w-none border-r border-stone-300 dark:border-stone-700">
                                            <div class="space-y-2">
                                                <div>
                                                    <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($idrNumber->contractItem->itemSpecification->itemCatalog->name, [$this->search, $this->filterArticle]) !!}</div>
                                                    @if ($densityClasses['show_secondary'])
                                                        <div class="{{ $densityClasses['text_meta'] }} text-stone-500">{!! \App\Helpers\TextHelper::highlight(collect([$idrNumber->contractItem->itemSpecification->brand, $idrNumber->contractItem->itemSpecification->model])->filter()->join(' / '), [$this->search, $this->filterArticle]) !!}</div>
                                                    @endif
                                                </div>
                                                @if ($densityClasses['show_tertiary'])
                                                    <div class="{{ $densityClasses['text_meta'] }}"><p class="text-stone-600 dark:text-stone-300 break-words">{!! \App\Helpers\TextHelper::highlight($idrNumber->contractItem->itemSpecification->detailed_specifications, [$this->search, $this->filterArticle]) !!}</p></div>
                                                @endif
                                                <div class="{{ $densityClasses['text_meta'] }}">
                                                    <p class="font-semibold uppercase text-stone-500 dark:text-stone-400">Batches:</p>
                                                    @if($idrNumber->itemBatches->isNotEmpty())
                                                        <ul class="mt-1 space-y-1">
                                                            @foreach($idrNumber->itemBatches as $batch)
                                                                <li class="text-stone-600 dark:text-stone-400">{!! \App\Helpers\TextHelper::highlight($batch->identification_data, [$this->search, $this->filterSerialNumber]) ?: 'No batch data' !!}</li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <p class="italic text-stone-500">No batches recorded.</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="{{ $densityClasses['table_cell_px'] }} align-top {{ $densityClasses['text_base'] }} border-r border-stone-300 dark:border-stone-700">
                                            <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($idrNumber->number, $this->search) !!}</div>
                                            @if($densityClasses['show_secondary'])
                                            <div class="mt-1 space-y-1 text-stone-600 dark:text-stone-400">
                                               <div><span class="font-medium">Qty:</span> {{ $idrNumber->quantity }} {{ $idrNumber->contractItem?->itemSpecification?->itemCatalog?->unit ?? 'unit' }}(s)</div>
                                               <div><span class="font-medium">Cost:</span> ₱{{ number_format($idrNumber->contractItem?->unit_price ?? 0, 2) }}</div>
                                               <div><span class="font-medium">Inv. Code:</span> {!! \App\Helpers\TextHelper::highlight($idrNumber->inventory_code, [$this->search, $this->filterInventoryNumber]) !!}</div>
                                               <div><span class="font-medium">ORS:</span> {!! \App\Helpers\TextHelper::highlight($idrNumber->ors, [$this->search, $this->filterOrs]) !!}</div>
                                            </div>
                                            @endif
                                        </td>
                                        <td class="hidden {{ $densityClasses['table_cell_px'] }} align-top {{ $densityClasses['text_base'] }} lg:table-cell border-r border-stone-300 dark:border-stone-700">
                                            <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($idrNumber->contractItem->contract->supplier->name ?? 'N/A', $this->search) !!}</div>
                                            @if($densityClasses['show_secondary'])
                                            <div class="mt-1 space-y-1 text-stone-600 dark:text-stone-400">
                                                <div><span class="font-medium">Contract:</span> {!! \App\Helpers\TextHelper::highlight($idrNumber->contractItem->contract->contract_po_ib_number, [$this->search, $this->filterContract]) !!}</div>
                                                @if($densityClasses['show_tertiary'])
                                                    <div><span class="font-medium">Prepared:</span> {{ $idrNumber->date_prepared->format('M d, Y') }}</div>
                                                    <div><span class="font-medium">Accepted:</span> {{ $idrNumber->date_accepted->format('M d, Y') }}</div>
                                                @endif
                                            </div>
                                            @endif
                                             @if($idrNumber->remarks)
                                                <div class="mt-2 text-xs text-stone-500 italic">"{!! \App\Helpers\TextHelper::highlight($idrNumber->remarks, [$this->search, $this->filterRemarks]) !!}"</div>
                                            @endif
                                        </td>
                                        <td class="hidden {{ $densityClasses['table_cell_px'] }} align-top {{ $densityClasses['text_base'] }} sm:table-cell border-r border-stone-300 dark:border-stone-700">
                                            <div><span class="font-medium">Assigned:</span> {!! \App\Helpers\TextHelper::highlight($idrNumber->assignedEmployee->name, $this->search) !!}</div>
                                            <div><span class="font-medium">Approved:</span> {!! \App\Helpers\TextHelper::highlight($idrNumber->approvingEmployee->name, $this->search) !!}</div>
                                            <div><span class="font-medium">Received By:</span> {!! \App\Helpers\TextHelper::highlight($idrNumber->receivedBy->name, $this->search) !!}</div>
                                            <div><span class="font-medium">Received From:</span> {!! \App\Helpers\TextHelper::highlight($idrNumber->receivedFrom->name, $this->search) !!}</div>
                                        </td>
                                        <td class="{{ $densityClasses['table_cell'] }} pl-3 pr-4 text-right align-top {{ $densityClasses['text_base'] }} font-medium sm:pr-6">
                                            <div class="flex items-center justify-end gap-x-2">
                                                <a href="{{ route('admin.inventory.idr.show', $idrNumber) }}" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                                                    <x-flux::icon.eye class="mr-1.5 h-4 w-4" />
                                                    View
                                                </a>
                                                <a href="{{ route('admin.inventory.idr.edit', $idrNumber) }}" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                                                    <x-flux::icon.edit class="mr-1.5 h-4 w-4" />
                                                    Edit
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="{{ $densityClasses['table_cell'] }} px-6 py-12 text-center {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">No IDR records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        @else
            <div>Card/Compact view not implemented yet.</div>
        @endif
    </div>
    <div class="mt-4">
        {{ $this->idrNumbers->links() }}
    </div>
</div> 