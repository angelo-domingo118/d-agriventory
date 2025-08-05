<?php

use App\Models\ConsumableItem;
use App\Models\Division;
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
    public ?int $filterDivisionId = null;
    public string $filterArticle = '';

    // Sorting properties
    public string $sortColumn = 'division_name';
    public string $sortDirection = 'asc';

    // Column visibility properties
    public array $columns;
    public array $columnGroups = [
        'details' => [
            'label' => 'Division Details',
            'columns' => [
                'division_code' => 'Division Code',
                'total_items' => 'Total Items',
            ],
        ],
        'quantity' => [
            'label' => 'Quantity Summary',
            'columns' => [
                'total_initial' => 'Total Initial',
                'total_current' => 'Total Available',
                'utilization_rate' => 'Utilization Rate',
            ],
        ],
    ];

    #[Computed]
    public function filtersActive(): bool
    {
        return $this->filterDivisionId || $this->filterArticle;
    }

    public function mount(): void
    {
        if (! auth()->user()->hasAdminPermission('view_inventory') && ! auth()->user()->isDivisionInventoryManager()) {
            abort(403);
        }
        $this->view = session('consumables_view_mode', 'table');
        $this->density = session('consumables_density', 'spacious');

        $defaultColumns = [];
        foreach ($this->columnGroups as $group) {
            foreach (array_keys($group['columns']) as $key) {
                $defaultColumns[$key] = true;
            }
        }
        $this->columns = session('consumables_column_visibility', $defaultColumns);
    }

    public function updatedColumns($value, $key): void
    {
        session(['consumables_column_visibility' => $this->columns]);
    }

    public function setView(string $view): void
    {
        $this->view = $view;
        session(['consumables_view_mode' => $view]);
    }

    public function setDensity(string $density): void
    {
        $this->density = $density;
        session(['consumables_density' => $density]);
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
    public function consumables()
    {
        $search = addcslashes($this->search, '%_');
        $lowerSearch = strtolower($search);
        
        $user = auth()->user();

        $query = Division::query()
            ->leftJoin('consumable_records', 'divisions.id', '=', 'consumable_records.division_id')
            ->leftJoin('consumable_items', 'consumable_records.id', '=', 'consumable_items.consumable_record_id')
            ->leftJoin('item_specifications', 'consumable_items.item_specification_id', '=', 'item_specifications.id')
            ->leftJoin('items_catalog', 'item_specifications.item_catalog_id', '=', 'items_catalog.id')
            ->select(
                'divisions.id as division_id',
                'divisions.name as division_name',
                'divisions.code as division_code',
                DB::raw('COUNT(DISTINCT CASE WHEN consumable_items.id IS NOT NULL THEN CONCAT(item_specifications.id, "-", items_catalog.id) END) as total_items'),
                DB::raw('COALESCE(SUM(consumable_items.initial_quantity), 0) as total_initial_quantity'),
                DB::raw('COALESCE(SUM(consumable_items.current_quantity), 0) as total_current_quantity'),
                DB::raw('CASE 
                    WHEN COALESCE(SUM(consumable_items.initial_quantity), 0) > 0 
                    THEN ROUND((1 - COALESCE(SUM(consumable_items.current_quantity), 0) / COALESCE(SUM(consumable_items.initial_quantity), 1)) * 100, 2)
                    ELSE 0 
                END as utilization_rate')
            )
            ->groupBy('divisions.id', 'divisions.name', 'divisions.code');

        $query->when($this->search, function ($query) use ($lowerSearch) {
            $query->where(function ($q) use ($lowerSearch) {
                $q->where(DB::raw('LOWER(divisions.name)'), 'like', '%'.$lowerSearch.'%')
                    ->orWhere(DB::raw('LOWER(divisions.code)'), 'like', '%'.$lowerSearch.'%')
                    ->orWhereExists(function ($subquery) use ($lowerSearch) {
                        $subquery->select(DB::raw(1))
                            ->from('consumable_records')
                            ->join('consumable_items', 'consumable_records.id', '=', 'consumable_items.consumable_record_id')
                            ->join('item_specifications', 'consumable_items.item_specification_id', '=', 'item_specifications.id')
                            ->join('items_catalog', 'item_specifications.item_catalog_id', '=', 'items_catalog.id')
                            ->whereColumn('consumable_records.division_id', 'divisions.id')
                            ->where(function ($itemQuery) use ($lowerSearch) {
                                $itemQuery->where(DB::raw('LOWER(items_catalog.name)'), 'like', '%'.$lowerSearch.'%')
                                    ->orWhere(DB::raw('LOWER(item_specifications.brand)'), 'like', '%'.$lowerSearch.'%')
                                    ->orWhere(DB::raw('LOWER(item_specifications.model)'), 'like', '%'.$lowerSearch.'%');
                            });
                    });
            });
        });

        $query->when($this->filterArticle, function ($query) use ($lowerSearch) {
            $query->whereExists(function ($subquery) use ($lowerSearch) {
                $subquery->select(DB::raw(1))
                    ->from('consumable_records')
                    ->join('consumable_items', 'consumable_records.id', '=', 'consumable_items.consumable_record_id')
                    ->join('item_specifications', 'consumable_items.item_specification_id', '=', 'item_specifications.id')
                    ->join('items_catalog', 'item_specifications.item_catalog_id', '=', 'items_catalog.id')
                    ->whereColumn('consumable_records.division_id', 'divisions.id')
                    ->where(function ($itemQuery) use ($lowerSearch) {
                        $itemQuery->where(DB::raw('LOWER(items_catalog.name)'), 'like', '%'.$lowerSearch.'%')
                            ->orWhere(DB::raw('LOWER(item_specifications.brand)'), 'like', '%'.$lowerSearch.'%')
                            ->orWhere(DB::raw('LOWER(item_specifications.model)'), 'like', '%'.$lowerSearch.'%');
                    });
            });
        });

        $query->when($this->filterDivisionId, fn ($q) => $q->where('divisions.id', $this->filterDivisionId));

        if ($user->isDivisionInventoryManager()) {
            $query->where('divisions.id', $user->divisionInventoryManager->division_id);
        }

        if ($this->sortColumn && $this->sortDirection) {
            $query->orderBy($this->sortColumn, $this->sortDirection);
        }

        return $query->paginate($this->perPage);
    }

    #[Computed]
    public function divisions()
    {
        return Division::orderBy('name')->get(['id', 'name']);
    }

    public function resetFilters()
    {
        $this->reset('filterDivisionId', 'filterArticle');
    }

    public function with(): array
    {
        return [
            'consumables' => $this->consumables,
            'divisions' => $this->divisions,
        ];
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-4">
        <!-- Breadcrumbs as Title -->
        <div>
            <flux:breadcrumbs class="text-2xl font-semibold">
                <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Inventory</flux:breadcrumbs.item>
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Consumables</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
    </div>

<div x-data="{
    showFilters: @entangle('showFilters'),
    defaultWidths: {
        division: 350,
        details: 200,
        quantity: 300,
        actions: 120
    },
    columnWidths: {},
    resetColumnWidths() {
        this.columnWidths = { ...this.defaultWidths };
        localStorage.removeItem('consumables_column_widths');
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
            localStorage.setItem('consumables_column_widths', JSON.stringify(this.columnWidths));
            window.removeEventListener('mousemove', mouseMoveHandler);
            window.removeEventListener('mouseup', mouseUpHandler);
        };

        window.addEventListener('mousemove', mouseMoveHandler);
        window.addEventListener('mouseup', mouseUpHandler);
    }
}" x-init="
    const storedWidths = JSON.parse(localStorage.getItem('consumables_column_widths') || '{}');
    columnWidths = { ...defaultWidths, ...storedWidths };
">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Consumables by Division
        </h1>
        <div class="flex items-center gap-x-2">
            <div x-data="{ open: false }" class="relative">
                <flux:button variant="outline" x-on:click="open = !open" class="!p-2">
                    <x-flux::icon.settings-2 class="h-5 w-5" />
                    <span class="sr-only">Toggle View Options</span>
                </flux:button>

                <div x-show="open" x-on:click.outside="open = false" x-transition class="absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-stone-800 dark:ring-stone-700" style="display: none;">
                    <div class="px-3 py-2">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">View Mode</div>
                        <div class="mt-2 flex overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                            <button wire:click="setView('table')" class="flex-1 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $view === 'table' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}">
                                Table
                            </button>
                            <button wire:click="setView('card')" class="-ml-px flex-1 border-x border-stone-200 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-stone-700 {{ $view === 'card' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}">
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
                                            <flux:checkbox wire:model.live="columns.{{ $key }}" label="{{ $label }}" id="column-{{ $key }}" />
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="mb-2 text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Column Layout</div>
                        <flux:button variant="ghost" x-on:click="resetColumnWidths()" class="w-full justify-center">
                            <x-flux::icon.rotate-cw class="mr-2 h-4 w-4" />
                            Reset Column Widths
                        </flux:button>
                    </div>
                </div>
            </div>
            <flux:button variant="outline" wire:click="$refresh" class="!p-2">
                <x-flux::icon.rotate-cw class="h-5 w-5" wire:loading.class="animate-spin" />
                <span class="sr-only">Refresh</span>
            </flux:button>
            <flux:button variant="outline" x-on:click="showFilters = !showFilters" class="!p-2 @if($this->filtersActive) bg-primary-50 text-primary-600 dark:bg-primary-900/10 dark:text-primary-400 @endif">
                <x-flux::icon.filter class="h-5 w-5" />
                <span class="sr-only">Toggle Filters</span>
            </flux:button>
        </div>
    </div>

    <div x-show="showFilters" x-collapse class="mt-4">
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <div class="border-b border-stone-200 p-4 dark:border-stone-700">
                <h3 class="font-semibold text-stone-800 dark:text-stone-200">Filter Options</h3>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="sm:col-span-2 lg:col-span-4">
                        <flux:input wire:model.live.debounce.300ms="filterArticle" label="Items / Description" placeholder="Search for items in divisions..." />
                    </div>
                    @if(auth()->user()->isAdmin())
                    <div class="lg:col-span-2">
                        <flux:select wire:model.live="filterDivisionId" label="Division">
                            <option value="">Any Division</option>
                            @foreach($this->divisions as $division)
                            <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    @endif
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
            @if ($this->consumables->total() > 0)
                <span>Showing {{ $this->consumables->firstItem() }} to {{ $this->consumables->lastItem() }} of <strong>{{ $this->consumables->total() }}</strong> divisions.</span>
            @else
                <span>No divisions found.</span>
            @endif
        </div>
        <div class="w-full max-w-xs">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search divisions..."
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
                                <th scope="col" :style="`width: ${columnWidths.division}px`" class="relative {{ $densityClasses['table_header'] }} border-r border-stone-300 dark:border-stone-700 text-left {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100">
                                    <div wire:click="sortBy('division_name')" class="flex items-center cursor-pointer">
                                        Division
                                        @if($sortColumn === 'division_name')
                                        @if($sortDirection === 'asc')
                                        <x-flux::icon.chevron-up class="ml-2 h-4 w-4" />
                                        @else
                                        <x-flux::icon.chevron-down class="ml-2 h-4 w-4" />
                                        @endif
                                        @else
                                        <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400 dark:text-stone-500" />
                                        @endif
                                    </div>
                                    <div @mousedown="startResize($event, 'division')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                </th>
                                <th scope="col" :style="`width: ${columnWidths.details}px`" class="relative {{ $densityClasses['table_header'] }} border-r border-stone-300 dark:border-stone-700 px-3 text-left {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100">
                                    <div wire:click="sortBy('total_items')" class="flex items-center cursor-pointer">
                                        Details
                                        @if($sortColumn === 'total_items')
                                        @if($sortDirection === 'asc')
                                        <x-flux::icon.chevron-up class="ml-2 h-4 w-4" />
                                        @else
                                        <x-flux::icon.chevron-down class="ml-2 h-4 w-4" />
                                        @endif
                                        @else
                                        <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400 dark:text-stone-500" />
                                        @endif
                                    </div>
                                    <div @mousedown="startResize($event, 'details')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                </th>
                                <th scope="col" :style="`width: ${columnWidths.quantity}px`" class="relative hidden {{ $densityClasses['table_header'] }} border-r border-stone-300 dark:border-stone-700 px-3 text-left {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100 lg:table-cell">
                                    <div wire:click="sortBy('total_current_quantity')" class="flex items-center cursor-pointer">
                                        Inventory Summary
                                        @if($sortColumn === 'total_current_quantity')
                                        @if($sortDirection === 'asc')
                                        <x-flux::icon.chevron-up class="ml-2 h-4 w-4" />
                                        @else
                                        <x-flux::icon.chevron-down class="ml-2 h-4 w-4" />
                                        @endif
                                        @else
                                        <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400 dark:text-stone-500" />
                                        @endif
                                    </div>
                                    <div @mousedown="startResize($event, 'quantity')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                </th>
                                <th scope="col" :style="`width: ${columnWidths.actions}px`" class="relative {{ $densityClasses['table_header'] }} pl-3 pr-4 sm:pr-6 text-right {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                            @forelse ($this->consumables as $division)
                            <tr wire:key="division-{{ $division->division_id }}" class="hover:bg-stone-50 dark:hover:bg-stone-800/50">
                                <td class="w-full max-w-md {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} sm:w-auto sm:max-w-none border-r border-stone-300 dark:border-stone-700">
                                    <div class="space-y-2">
                                        <div>
                                            <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($division->division_name, $this->search) !!}</div>
                                            @if ($this->columns['division_code'] && $densityClasses['show_secondary'] && $division->division_code)
                                            <div class="{{ $densityClasses['text_meta'] }} text-stone-500">
                                                Code: {!! \App\Helpers\TextHelper::highlight($division->division_code, $this->search) !!}
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td class="{{ $densityClasses['table_cell_px'] }} align-top {{ $densityClasses['text_base'] }} border-r border-stone-300 dark:border-stone-700">
                                    @if ($this->columns['total_items'])
                                    <div class="font-semibold text-stone-900 dark:text-stone-100">{{ $division->total_items }} Items</div>
                                    @endif
                                    @if ($this->columns['utilization_rate'] && $densityClasses['show_secondary'])
                                    <div class="mt-1 {{ $densityClasses['text_meta'] }} text-stone-600 dark:text-stone-400">
                                        <span class="font-medium">Utilization:</span> {{ $division->utilization_rate }}%
                                    </div>
                                    @endif
                                </td>

                                <td class="hidden {{ $densityClasses['table_cell_px'] }} align-top {{ $densityClasses['text_base'] }} lg:table-cell border-r border-stone-300 dark:border-stone-700">
                                    <div class="font-semibold text-stone-900 dark:text-stone-100">{{ number_format($division->total_current_quantity) }} Available</div>
                                    @if($densityClasses['show_secondary'])
                                    <div class="mt-1 space-y-1 text-stone-600 dark:text-stone-400">
                                        @if($this->columns['total_initial'])
                                        <div><span class="font-medium">Initial:</span> {{ number_format($division->total_initial_quantity) }}</div>
                                        @endif
                                        @if($this->columns['total_current'])
                                        <div><span class="font-medium">Consumed:</span> {{ number_format($division->total_initial_quantity - $division->total_current_quantity) }}</div>
                                        @endif
                                    </div>
                                    @endif
                                </td>

                                <td class="{{ $densityClasses['table_cell'] }} pl-3 pr-4 text-right align-top {{ $densityClasses['text_base'] }} font-medium sm:pr-6">
                                    <div class="flex items-center justify-end gap-x-2">
                                        <flux:button 
                                            variant="ghost" 
                                            :href="route('admin.inventory.consumables.details', ['filterDivisionId' => $division->division_id])" 
                                            wire:navigate 
                                            class="!p-2.5" 
                                            title="View Division Items"
                                        >
                                            <x-flux::icon.eye class="mr-1.5 h-4 w-4" />
                                            View Items
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="{{ $densityClasses['table_cell'] }} px-6 py-12 text-center {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">
                                    No divisions found matching your criteria.
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
            @forelse ($this->consumables as $division)
            <div wire:key="division-card-{{ $division->division_id }}" class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-black ring-opacity-5 dark:bg-stone-800 dark:ring-stone-700">
                <div class="{{ $densityClasses['card_padding'] }}">
                    <div class="flex items-start justify-between">
                        <div class="max-w-xs">
                            <p class="truncate {{ $densityClasses['text_base'] }} font-semibold text-stone-900 dark:text-stone-100">
                                {!! \App\Helpers\TextHelper::highlight($division->division_name, $this->search) !!}
                            </p>
                            @if ($densityClasses['show_secondary'] && $division->division_code)
                            <p class="{{ $densityClasses['text_meta'] }} text-stone-500">
                                Code: {!! \App\Helpers\TextHelper::highlight($division->division_code, $this->search) !!}
                            </p>
                            @endif
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                {{ $division->utilization_rate }}% Used
                            </span>
                        </div>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 {{ $densityClasses['text_base'] }}">
                        <div>
                            <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Items</dt>
                            <dd class="font-medium text-stone-800 dark:text-stone-200">{{ $division->total_items }}</dd>
                        </div>
                        <div>
                            <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Available</dt>
                            <dd class="font-medium text-stone-800 dark:text-stone-200">{{ number_format($division->total_current_quantity) }}</dd>
                        </div>
                        <div>
                            <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Initial</dt>
                            <dd class="font-medium text-stone-800 dark:text-stone-200">{{ number_format($division->total_initial_quantity) }}</dd>
                        </div>
                        <div>
                            <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Consumed</dt>
                            <dd class="font-medium text-stone-800 dark:text-stone-200">{{ number_format($division->total_initial_quantity - $division->total_current_quantity) }}</dd>
                        </div>
                    </dl>
                </div>
                <div class="border-t border-stone-200 bg-stone-50 {{ $densityClasses['card_footer_padding'] }} dark:border-stone-700 dark:bg-stone-800/50">
                    <div class="flex items-center justify-end gap-x-2">
                        <flux:button 
                            variant="ghost" 
                            :href="route('admin.inventory.consumables.details', ['filterDivisionId' => $division->division_id])" 
                            wire:navigate 
                            class="!p-2.5" 
                            title="View Division Items"
                        >
                            <x-flux::icon.eye class="mr-1.5 h-4 w-4" />
                            View Items
                        </flux:button>
                    </div>
                </div>
            </div>
            @empty
            <div class="sm:col-span-2 lg:col-span-3">
                <div class="rounded-lg border border-dashed border-stone-300 p-12 text-center dark:border-stone-700">
                    <h3 class="text-lg font-medium text-stone-900 dark:text-stone-100">No Records Found</h3>
                    <p class="mt-1 {{ $densityClasses['text_base'] }} text-stone-500">No divisions found matching your criteria.</p>
                </div>
            </div>
            @endforelse
        </div>
        @endif
    </div>
    <div class="mt-4">
        {{ $this->consumables->links() }}
    </div>
</div>