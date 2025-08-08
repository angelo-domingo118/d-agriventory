<?php

use App\Models\ItemsCatalog;
use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Flux\Flux;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    // View and filter state
    public string $search = '';
    public bool $showFilters = false;
    public int $perPage = 10;
    public string $density = 'spacious';
    public string $textOverflow = 'nowrap';

    // Filter properties
    public ?int $filterSecondaryCategory = null;
    public ?int $filterPrimaryCategory = null;
    public string $filterUnit = '';

    // Sorting properties
    public string $sortColumn = 'name';
    public string $sortDirection = 'asc';
    
    public ?ItemsCatalog $editingItem = null;

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage inventory data.');
        }
    }

    public function resetSorting(): void
    {
        $this->sortColumn = 'name';
        $this->sortDirection = 'asc';
    }

    #[Computed]
    public function filtersActive(): bool
    {
        return $this->filterSecondaryCategory || $this->filterPrimaryCategory || $this->filterUnit;
    }

    #[Computed]
    public function editingItemDeletionImpact(): array
    {
        if (!$this->editingItem) {
            return [
                'specifications' => 0,
                'contract_items' => 0,
                'consumable_items' => 0,
                'ics_numbers' => 0,
                'par_numbers' => 0,
                'idr_numbers' => 0,
                'risk_level' => 'safe',
                'risk_message' => '',
                'has_associated_data' => false,
            ];
        }

        return $this->editingItem->getDeletionImpact();
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

    public function resetFilters()
    {
        $this->reset('filterSecondaryCategory', 'filterPrimaryCategory', 'filterUnit');
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'filterPrimaryCategory', 'filterSecondaryCategory', 'filterUnit', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function editItem(ItemsCatalog $item): void
    {
        $this->editingItem = $item;
        Flux::modal('edit-item')->show();
    }
    
    #[Computed]
    public function items()
    {
        $query = ItemsCatalog::query()
            ->with('secondaryCategory.primaryCategory')
            ->select('items_catalog.*', 'secondary_categories.name as secondary_category_name', 'primary_categories.name as primary_category_name')
            ->join('secondary_categories', 'items_catalog.secondary_category_id', '=', 'secondary_categories.id')
            ->join('primary_categories', 'secondary_categories.primary_category_id', '=', 'primary_categories.id');

        $query->when($this->search, function (Builder $query, $search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('items_catalog.name', 'like', "%{$search}%")
                        ->orWhere('items_catalog.code', 'like', "%{$search}%")
                        ->orWhere('items_catalog.unit', 'like', "%{$search}%")
                        ->orWhere('secondary_categories.name', 'like', "%{$search}%")
                        ->orWhere('primary_categories.name', 'like', "%{$search}%");
                });
            })
            ->when($this->filterUnit, fn(Builder $q) => $q->where('items_catalog.unit', 'like', '%' . $this->filterUnit . '%'))
            ->when($this->filterSecondaryCategory, fn(Builder $q) => $q->where('items_catalog.secondary_category_id', $this->filterSecondaryCategory))
            ->when($this->filterPrimaryCategory, fn(Builder $q, $id) => $q->where('primary_categories.id', $id));

        $sortColumn = match ($this->sortColumn) {
            'name' => 'items_catalog.name',
            'code' => 'items_catalog.code',
            'unit' => 'items_catalog.unit',
            'category' => 'secondary_category_name',
            'primary_category' => 'primary_category_name',
            default => 'items_catalog.name',
        };

        return $query->orderBy($sortColumn, $this->sortDirection)
            ->orderBy('items_catalog.id', 'asc')
            ->paginate($this->perPage);
    }
    
    #[Computed]
    public function secondaryCategories()
    {
        return SecondaryCategory::with('primaryCategory')->orderBy('name')->get();
    }
    
    #[Computed]
    public function primaryCategories()
    {
        return PrimaryCategory::orderBy('name')->get();
    }


    #[On('item-created')]
    #[On('item-updated')]
    #[On('item-deleted')]
    public function refreshItems(): void
    {
        // Force refresh of computed property and reset to first page
        unset($this->items);
        $this->resetPage();
        $this->dispatch('$refresh');
        
        // Reset editing item
        $this->editingItem = null;
    }

    public function with(): array
    {
        return [
            'items' => $this->items,
            'secondaryCategories' => $this->secondaryCategories,
            'primaryCategories' => $this->primaryCategories,
        ];
    }
}; ?>

<div x-data="{ 
    showFilters: @entangle('showFilters'),
    ...tableSettings('items_catalog_settings')
}">
    <div x-data="tableResizer('items_catalog_widths', { name: 300, code: 150, unit: 100, secondary_category: 200, primary_category: 200, actions: 100 })">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                Items Catalog
            </h1>
            <div class="flex items-center gap-x-2">
                <div x-data="{ open: false }" class="relative">
                    <flux:button variant="outline" x-on:click="open = !open" class="!p-2">
                        <x-flux::icon.settings-2 class="h-5 w-5" />
                        <span class="sr-only">Toggle View Options</span>
                    </flux:button>
                    <div x-show="open" x-on:click.outside="open = false" x-transition class="absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-stone-800 dark:ring-stone-700" style="display: none;">
                        <!-- Density -->
                        <div class="px-3 py-2">
                            <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Density</div>
                            <div class="mt-2 flex overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                                <button 
                                    @click="updateSetting('density', 'compact')" 
                                    class="flex-1 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $density === 'compact' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                                >
                                    Compact
                                </button>
                                <button 
                                    @click="updateSetting('density', 'comfortable')" 
                                    class="-ml-px flex-1 border-x border-stone-200 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-stone-700 {{ $density === 'comfortable' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                                >
                                    Comfortable
                                </button>
                                <button 
                                    @click="updateSetting('density', 'spacious')" 
                                    class="-ml-px flex-1 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $density === 'spacious' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
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
                                @click="updateSetting('textOverflow', 'nowrap')" 
                                class="flex-1 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $textOverflow === 'nowrap' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                No Wrap
                            </button>
                            <button 
                                @click="updateSetting('textOverflow', 'wrap')" 
                                class="-ml-px flex-1 border-x border-stone-200 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-stone-700 {{ $textOverflow === 'wrap' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Wrap Text
                            </button>
                            <button 
                                @click="updateSetting('textOverflow', 'scroll')" 
                                class="-ml-px flex-1 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $textOverflow === 'scroll' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Scroll
                            </button>
                        </div>
                    </div>

                    <!-- Items per Page -->
                        <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                            <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Items per Page</div>
                            <div class="mt-2 flex overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                                @foreach ([5, 10, 25, 50] as $count)
                                    <button
                                        @click="updateSetting('perPage', {{ $count }})"
                                        class="@if(!$loop->first) -ml-px border-l border-stone-200 dark:border-stone-700 @endif flex-1 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $perPage == $count ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                                    >
                                        {{ $count }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Table Customization -->
                        <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                            <div class="mb-2 text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Table Customization</div>
                            <div class="space-y-2">
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
                                    <div class="flex items-center">
                                        <x-flux::icon.chevrons-up-down class="mr-2 h-4 w-4" />
                                        Reset Sort Order
                                    </div>
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </div>
                <flux:button variant="outline" wire:click="$refresh" class="!p-2">
                    <x-flux::icon.rotate-cw class="h-5 w-5" wire:loading.class="animate-spin" />
                    <span class="sr-only">Refresh</span>
                </flux:button>
                <flux:button variant="outline" x-on:click="$wire.showFilters = !$wire.showFilters" class="!p-2 @if($this->filtersActive) bg-primary-50 text-primary-600 dark:bg-primary-900/10 dark:text-primary-400 @endif">
                    <x-flux::icon.filter class="h-5 w-5" />
                    <span class="sr-only">Toggle Filters</span>
                </flux:button>
                <flux:modal.trigger name="create-item">
                <flux:button variant="primary">New Item</flux:button>
            </flux:modal.trigger>
            </div>
        </div>
        
        <div x-show="$wire.showFilters" x-collapse class="mt-4">
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <div class="border-b border-stone-200 p-4 dark:border-stone-700">
                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">Filter Options</h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-4">
                        <div class="sm:col-span-2">
                            <flux:select wire:model.live="filterPrimaryCategory" label="Primary Category">
                                <option value="">Any Primary Category</option>
                                @foreach($this->primaryCategories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                        <div class="sm:col-span-2">
                            <flux:select wire:model.live="filterSecondaryCategory" label="Secondary Category">
                                <option value="">Any Secondary Category</option>
                                @foreach($this->secondaryCategories->groupBy('primaryCategory.name') as $primaryName => $secondaryGroup)
                                    <optgroup label="{{ $primaryName }}" class="font-semibold text-stone-600 dark:text-stone-300 bg-stone-50 dark:bg-stone-700">
                                        @foreach($secondaryGroup as $sCat)
                                            <option value="{{ $sCat->id }}" class="bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100">{{ $sCat->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </flux:select>
                        </div>
                        <div class="sm:col-span-4">
                            <flux:input wire:model.live.debounce.300ms="filterUnit" label="Unit of Measure" placeholder="Search units..." />
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
                    icon="magnifying-glass"
                    clearable
                />
            </div>
        </div>

        @php
            $densityClasses = [
                'table_header' => match($density) {
                    'compact' => 'py-2 px-4',
                    'comfortable' => 'py-2.5 px-4',
                    default => 'py-3 px-4',
                },
                'table_cell' => match($density) {
                    'compact' => 'py-2 px-4',
                    'comfortable' => 'py-3 px-4',
                    default => 'py-4 px-4',
                },
                'text_header' => match($density) {
                    'compact' => 'text-xs',
                    default => 'text-xs',
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

        <div class="mt-4 flow-root">
            <div class="{{ $densityClasses['table_wrapper'] }} rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700 table-fixed">
                    <thead class="bg-stone-50 dark:bg-stone-800">
                        <tr class="divide-x divide-stone-200 dark:divide-stone-700">
                            <th scope="col" :style="`width: ${columnWidths.name}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                <div wire:click="sortBy('name')" class="flex cursor-pointer items-center">
                                    Name
                                    @if($sortColumn === 'name')
                                        @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                    @else
                                        <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400" />
                                    @endif
                                </div>
                                <div @mousedown="startResize($event, 'name')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                            </th>
                            <th scope="col" :style="`width: ${columnWidths.code}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                <div wire:click="sortBy('code')" class="flex cursor-pointer items-center">
                                    Code
                                    @if($sortColumn === 'code')
                                        @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                    @else
                                        <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400" />
                                    @endif
                                </div>
                                <div @mousedown="startResize($event, 'code')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                            </th>
                            <th scope="col" :style="`width: ${columnWidths.unit}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                 <div wire:click="sortBy('unit')" class="flex cursor-pointer items-center">
                                    Unit
                                    @if($sortColumn === 'unit')
                                         @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                    @else
                                        <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400" />
                                    @endif
                                </div>
                                <div @mousedown="startResize($event, 'unit')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                            </th>
                            <th scope="col" :style="`width: ${columnWidths.secondary_category}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                <div wire:click="sortBy('category')" class="flex cursor-pointer items-center">
                                    Category
                                    @if($sortColumn === 'category')
                                        @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                    @else
                                        <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400" />
                                    @endif
                                </div>
                                <div @mousedown="startResize($event, 'secondary_category')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                            </th>
                            <th scope="col" :style="`width: ${columnWidths.primary_category}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                <div wire:click="sortBy('primary_category')" class="flex cursor-pointer items-center">
                                    Primary Category
                                    @if($sortColumn === 'primary_category')
                                        @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                    @else
                                        <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400" />
                                    @endif
                                </div>
                                <div @mousedown="startResize($event, 'primary_category')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                            </th>
                            <th scope="col" :style="`width: ${columnWidths.actions}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                        @forelse($items as $item)
                            <tr wire:key="item-{{ $item->id }}" class="hover:bg-stone-50 dark:hover:bg-stone-800/50">
                                <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} font-medium text-stone-900 dark:text-stone-100" title="{{ $item->name }}">{{ $item->name }}</td>
                                <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400" title="{{ $item->code }}">{{ $item->code }}</td>
                                <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400" title="{{ $item->unit }}">{{ $item->unit }}</td>
                                <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400" title="{{ $item->secondaryCategory?->name }}">{{ $item->secondaryCategory?->name }}</td>
                                <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400" title="{{ $item->secondaryCategory?->primaryCategory?->name }}">{{ $item->secondaryCategory?->primaryCategory?->name }}</td>
                                <td class="whitespace-nowrap {{ $densityClasses['table_cell'] }} text-right {{ $densityClasses['text_base'] }} font-medium">
                                                                    <button wire:click="editItem({{ $item->id }})" wire:loading.attr="disabled" wire:target="editItem({{ $item->id }})" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50">
                                   <x-flux::icon.pencil class="mr-1.5 h-4 w-4" wire:loading.remove wire:target="editItem({{ $item->id }})" />
                                   <x-flux::icon.rotate-cw class="mr-1.5 h-4 w-4 animate-spin" wire:loading wire:target="editItem({{ $item->id }})" />
                                   <span wire:loading.remove wire:target="editItem({{ $item->id }})">Edit</span>
                                   <span wire:loading wire:target="editItem({{ $item->id }})">Loading...</span>
                                </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="{{ $densityClasses['table_cell'] }} px-6 py-12 text-center {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">
                                    No items found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>


        <div class="mt-4">
            {{ $items->links() }}
        </div>

    <!-- Create Item Modal -->
    <x-admin.modal-form-wrapper name="create-item" maxWidth="lg">
        <livewire:admin.data.items-and-categories.items-catalog.create />
    </x-admin.modal-form-wrapper>

    <!-- Edit Item Modal -->
    @if($editingItem)
        <x-admin.modal-form-wrapper name="edit-item" maxWidth="lg">
            <livewire:admin.data.items-and-categories.items-catalog.edit 
                :item="$editingItem" 
                :key="'edit-item-' . $editingItem->id" 
            />
        </x-admin.modal-form-wrapper>

        <!-- Enhanced Delete Confirmation Modal -->
        <x-admin.enhanced-delete-modal 
            name="delete-item-confirmation"
            title="Delete Catalog Item"
            entity-type="catalog item"
            :entity-name="$editingItem->name"
            :association-counts="[
                'specifications' => $this->editingItemDeletionImpact['specifications'],
                'contract items' => $this->editingItemDeletionImpact['contract_items'],
                'consumable items' => $this->editingItemDeletionImpact['consumable_items'],
                'ICS records' => $this->editingItemDeletionImpact['ics_numbers'],
                'PAR records' => $this->editingItemDeletionImpact['par_numbers'],
                'IDR records' => $this->editingItemDeletionImpact['idr_numbers']
            ]"
            :has-associated-data="$this->editingItemDeletionImpact['has_associated_data']"
            :risk-level="$this->editingItemDeletionImpact['risk_level']"
            :risk-message="$this->editingItemDeletionImpact['risk_message']"
            :block-deletion="$this->editingItemDeletionImpact['risk_level'] === 'high'"
            delete-action="$dispatch('call-delete')"
            cancel-action="$dispatch('call-cancel-delete')"
            max-width="xl"
        />
    @endif
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
                    if (newWidth > 60) {
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

        Alpine.data('tableSettings', (storageKey) => ({
            init() {
                const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
                if (saved.density) this.$wire.density = saved.density;
                if (saved.perPage) this.$wire.perPage = saved.perPage;
                if (saved.textOverflow) this.$wire.textOverflow = saved.textOverflow;
            },
            updateSetting(key, value) {
                this.$wire[key] = value;
                const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
                saved[key] = value;
                localStorage.setItem(storageKey, JSON.stringify(saved));
            }
        }));
    });
</script>