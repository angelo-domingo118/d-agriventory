<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\ConsumableItem;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;
    
    public $division;
    public string $search = '';
    public bool $showFilters = false;
    
    // View options
    public string $density = 'spacious';
    public string $textOverflow = 'nowrap';
    public int $perPage = 10;
    
    // Sorting properties
    public string $sortColumn = 'specification.itemCatalog.name';
    public string $sortDirection = 'asc';
    
    // Filter properties
    public string $filterStatus = '';
    public ?int $filterQuantityMin = null;
    public ?int $filterQuantityMax = null;
    public string $filterBrand = '';

    public function mount()
    {
        $user = auth()->user()->load('divisionInventoryManager.division');
        $this->division = $user->divisionInventoryManager->division;
        
        // Load saved settings
        $this->density = session('items_density', 'spacious');
        $this->textOverflow = session('items_text_overflow', 'nowrap');
        $this->perPage = session('items_per_page', 10);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        session(['items_per_page' => $this->perPage]);
        $this->resetPage();
    }

    public function setDensity(string $density): void
    {
        $this->density = $density;
        session(['items_density' => $density]);
    }

    public function setTextOverflow(string $textOverflow): void
    {
        $this->textOverflow = $textOverflow;
        session(['items_text_overflow' => $textOverflow]);
    }

    public function sortBy(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function resetSorting(): void
    {
        $this->sortColumn = 'specification.itemCatalog.name';
        $this->sortDirection = 'asc';
    }

    public function resetFilters()
    {
        $this->reset('filterStatus', 'filterQuantityMin', 'filterQuantityMax', 'filterBrand');
    }

    #[Computed]
    public function filtersActive(): bool
    {
        return $this->filterStatus || $this->filterQuantityMin || $this->filterQuantityMax || $this->filterBrand;
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        $filters = [
            $this->filterStatus,
            $this->filterQuantityMin,
            $this->filterQuantityMax,
            $this->filterBrand,
        ];

        return collect($filters)->filter()->count();
    }
    
    #[Computed]
    public function items()
    {
        return ConsumableItem::whereHas('record', function ($query) {
            $query->where('division_id', $this->division->id);
        })
        ->with(['specification.itemCatalog', 'record'])
        ->when($this->search, function ($query) {
            $query->whereHas('specification.itemCatalog', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })->orWhereHas('specification', function ($q) {
                $q->where('brand', 'like', '%' . $this->search . '%')
                  ->orWhere('model', 'like', '%' . $this->search . '%');
            })->orWhereHas('record', function ($q) {
                $q->where('record_number', 'like', '%' . $this->search . '%');
            });
        })
        ->when($this->filterBrand, function ($query, $search) {
            $query->whereHas('specification', function ($q) use ($search) {
                $q->where('brand', 'like', '%' . $search . '%');
            });
        })
        ->when($this->filterStatus, function ($query) {
            if ($this->filterStatus === 'out_of_stock') {
                $query->where('current_quantity', 0);
            } elseif ($this->filterStatus === 'low_stock') {
                $query->whereRaw('current_quantity <= initial_quantity * 0.2')
                      ->where('current_quantity', '>', 0);
            } elseif ($this->filterStatus === 'in_stock') {
                $query->whereRaw('current_quantity > initial_quantity * 0.2');
            }
        })
        ->when($this->filterQuantityMin, function ($query) {
            $query->where('current_quantity', '>=', $this->filterQuantityMin);
        })
        ->when($this->filterQuantityMax, function ($query) {
            $query->where('current_quantity', '<=', $this->filterQuantityMax);
        })
        ->orderBy('current_quantity', $this->sortDirection)
        ->paginate($this->perPage);
    }
    
    public function getItemStatus($item): array
    {
        if ($item->current_quantity == 0) {
            return ['status' => 'Out of Stock', 'color' => 'red'];
        } elseif ($item->current_quantity <= ($item->initial_quantity * 0.2)) {
            return ['status' => 'Low Stock', 'color' => 'amber'];
        } else {
            return ['status' => 'In Stock', 'color' => 'green'];
        }
    }

    public function create(): void
    {
        $this->redirect(route('inventory-manager.consumables.create'), navigate: true);
    }
}

?>

<div x-data="tableResizer('items_column_widths', { name: 300, brand_model: 200, record: 200, initial_stock: 120, current_stock: 120, status: 150, actions: 150 })">
    <div class="flex items-center justify-between">
        <!-- Breadcrumbs as Title -->
<div>
            <flux:breadcrumbs class="text-2xl font-semibold">
                <flux:breadcrumbs.item :href="route('inventory-manager.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Inventory</flux:breadcrumbs.item>
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Items</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
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
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Density</div>
                        <div class="mt-2 flex overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                             <button
                                 wire:click="setDensity('compact')"
                                 class="flex-1 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $density === 'compact' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                             >
                                 Compact
                             </button>
                             <button
                                 wire:click="setDensity('comfortable')"
                                 class="-ml-px flex-1 border-x border-stone-200 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-stone-700 {{ $density === 'comfortable' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                             >
                                 Comfortable
                             </button>
                             <button
                                 wire:click="setDensity('spacious')"
                                 class="-ml-px flex-1 px-3 py-1 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $density === 'spacious' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                             >
                                 Spacious
                             </button>
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
            <flux:button variant="primary" wire:click="create">
                Add New Item
            </flux:button>
            </div>
        </div>
        
    <div class="mt-4 flex items-start gap-x-6">
        <div class="min-w-0 flex-1 space-y-4">
            <div class="flex items-center justify-between">
                <div class="text-sm text-stone-600 dark:text-stone-400">
                    @if ($this->items->total() > 0)
                        <span>Showing {{ $this->items->firstItem() }} to {{ $this->items->lastItem() }} of <strong>{{ $this->items->total() }}</strong> results.</span>
                    @else
                        <span>No results found.</span>
                    @endif
                </div>
                <div class="w-full max-w-xs">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search anything..."
                        clearable
                        icon="magnifying-glass"
                    ></flux:input>
                </div>
            </div>

            <div class="flow-root">
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
                        'text_header' => match($density) {
                            'compact' => 'text-xs',
                            default => 'text-sm',
                        },
                        'text_base' => match($density) {
                            'compact' => 'text-xs',
                            default => 'text-sm',
                        },
                        'text_overflow' => match($textOverflow) {
                            'wrap' => 'break-words',
                            'scroll' => 'whitespace-nowrap',
                            default => 'whitespace-nowrap truncate',
                        },
                        'table_wrapper' => match($textOverflow) {
                            'scroll' => 'overflow-x-auto',
                            default => 'overflow-hidden',
                        },
                    ];
                @endphp

                <div class="-mx-4 -my-2 {{ $densityClasses['table_wrapper'] }} sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <div class="overflow-hidden rounded-lg shadow ring-1 ring-black ring-opacity-5 dark:ring-stone-700">
                            <table class="min-w-full divide-y divide-stone-300 dark:divide-stone-700 table-fixed">
                                <thead class="bg-stone-50 dark:bg-stone-800">
                                    <tr>
                                        <th scope="col" :style="`width: ${columnWidths.name}px`" class="relative {{ $densityClasses['table_header'] }} border-r border-stone-300 dark:border-stone-700 text-left {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100">
                                            <div wire:click="sortBy('specification.itemCatalog.name')" class="flex items-center cursor-pointer">
                                                Name
                                                @if($sortColumn === 'specification.itemCatalog.name')
                                                    @if($sortDirection === 'asc')
                                                        <x-flux::icon.chevron-up class="ml-2 h-4 w-4" />
                                                    @else
                                                        <x-flux::icon.chevron-down class="ml-2 h-4 w-4" />
                                                    @endif
                                                @else
                                                    <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400 dark:text-stone-500" />
                                                @endif
                                            </div>
                                            <div @mousedown="startResize($event, 'name')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                        </th>
                                        <th scope="col" :style="`width: ${columnWidths.brand_model}px`" class="relative {{ $densityClasses['table_header'] }} border-r border-stone-300 dark:border-stone-700 px-3 text-left {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100">
                                            Brand/Model
                                            <div @mousedown="startResize($event, 'brand_model')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                        </th>
                                        <th scope="col" :style="`width: ${columnWidths.record}px`" class="relative {{ $densityClasses['table_header'] }} border-r border-stone-300 dark:border-stone-700 px-3 text-left {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100">
                                            Record
                                            <div @mousedown="startResize($event, 'record')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                        </th>
                                        <th scope="col" :style="`width: ${columnWidths.initial_stock}px`" class="relative {{ $densityClasses['table_header'] }} border-r border-stone-300 dark:border-stone-700 px-3 text-left {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100">
                                            <div wire:click="sortBy('initial_quantity')" class="flex items-center cursor-pointer">
                                                Initial Stock
                                                @if($sortColumn === 'initial_quantity')
                                                    @if($sortDirection === 'asc')
                                                        <x-flux::icon.chevron-up class="ml-2 h-4 w-4" />
                                                    @else
                                                        <x-flux::icon.chevron-down class="ml-2 h-4 w-4" />
                                                    @endif
                                                @else
                                                    <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400 dark:text-stone-500" />
                                                @endif
                                            </div>
                                            <div @mousedown="startResize($event, 'initial_stock')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                        </th>
                                        <th scope="col" :style="`width: ${columnWidths.current_stock}px`" class="relative {{ $densityClasses['table_header'] }} border-r border-stone-300 dark:border-stone-700 px-3 text-left {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100">
                                            <div wire:click="sortBy('current_quantity')" class="flex items-center cursor-pointer">
                                                Current Stock
                                                @if($sortColumn === 'current_quantity')
                                                    @if($sortDirection === 'asc')
                                                        <x-flux::icon.chevron-up class="ml-2 h-4 w-4" />
                                                    @else
                                                        <x-flux::icon.chevron-down class="ml-2 h-4 w-4" />
                                                    @endif
                                                @else
                                                    <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400 dark:text-stone-500" />
                                                @endif
                                            </div>
                                            <div @mousedown="startResize($event, 'current_stock')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                        </th>
                                        <th scope="col" :style="`width: ${columnWidths.status}px`" class="relative {{ $densityClasses['table_header'] }} border-r border-stone-300 dark:border-stone-700 px-3 text-left {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100">
                                            Status
                                            <div @mousedown="startResize($event, 'status')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                            </th>
                                        <th scope="col" :style="`width: ${columnWidths.actions}px`" class="relative {{ $densityClasses['table_header'] }} pl-3 pr-4 sm:pr-6 text-right {{ $densityClasses['text_header'] }} font-semibold text-stone-900 dark:text-stone-100">
                                Actions
                            </th>
                        </tr>
                    </thead>
                                <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                                     @forelse ($this->items as $item)
                            @php $status = $this->getItemStatus($item); @endphp
                                        <tr class="hover:bg-stone-50 dark:hover:bg-stone-800/50">
                                            <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} font-medium text-stone-900 dark:text-stone-100">
                                                <div>{{ $item->specification->itemCatalog->name }}</div>
                                                <div class="text-stone-500 dark:text-stone-400">{{ $item->specification->itemCatalog->unit }}</div>
                                </td>
                                            <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell_px'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">
                                                <div>{{ $item->specification->brand ?? 'Generic' }}</div>
                                    @if($item->specification->model)
                                                    <div class="text-stone-500 dark:text-stone-400">{{ $item->specification->model }}</div>
                                    @endif
                                </td>
                                            <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell_px'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">
                                                <div>{{ $item->record->record_number }}</div>
                                                <div class="text-stone-500 dark:text-stone-400">{{ $item->record->date_received->format('M d, Y') }}</div>
                                </td>
                                            <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell_px'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">
                                    {{ number_format($item->initial_quantity) }}
                                </td>
                                            <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell_px'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">
                                    {{ number_format($item->current_quantity) }}
                                </td>
                                            <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell_px'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">
                                                <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 
                                                    @if($status['color'] === 'green') bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300
                                                    @elseif($status['color'] === 'amber') bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300  
                                                    @else bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300 @endif">
                                        {{ $status['status'] }}
                                    </span>
                                </td>
                                            <td class="relative whitespace-nowrap {{ $densityClasses['table_cell'] }} text-right {{ $densityClasses['text_base'] }} font-medium">
                                                <button 
                                                    class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50"
                                            :href="route('inventory-manager.consumables.show', $item->record)" 
                                            wire:navigate>
                                                    <x-flux::icon.eye class="mr-1.5 h-4 w-4" />
                                            View Record
                                                </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                            <td colspan="7" class="{{ $densityClasses['table_cell'] }} px-6 py-12 text-center {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">
                                                No items found matching your criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                {{ $this->items->links() }}
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
                            <flux:input wire:model.live.debounce.300ms="filterBrand" label="Brand" placeholder="Search brand..." clearable />
                        </div>

                        <div class="col-span-full"><hr class="border-stone-200 dark:border-stone-700" /></div>

                        <div class="sm:col-span-2">
                            <flux:select wire:model.live="filterStatus" label="Status">
                                <option value="">Any Status</option>
                                <option value="in_stock">In Stock</option>
                                <option value="low_stock">Low Stock</option>
                                <option value="out_of_stock">Out of Stock</option>
                            </flux:select>
                        </div>

                        <div class="col-span-full"><hr class="border-stone-200 dark:border-stone-700" /></div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300">Current Quantity Range</label>
                            <div class="mt-1 grid grid-cols-2 gap-x-2">
                                <flux:input wire:model.live="filterQuantityMin" type="number" placeholder="Min" />
                                <flux:input wire:model.live="filterQuantityMax" type="number" placeholder="Max" />
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