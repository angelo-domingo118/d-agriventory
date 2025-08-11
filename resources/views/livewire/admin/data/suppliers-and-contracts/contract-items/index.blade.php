<?php

use App\Models\ContractItem;
use App\Models\Contract;
use App\Models\ItemSpecification;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Flux\Flux;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    // View state
    public string $search = '';
    public int $perPage = 10;
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';
    public bool $showFilters = false;
    public string $density = 'spacious';
    public string $textOverflow = 'nowrap';
    public ?ContractItem $editingContractItem = null;
    
    // Filters
    public ?int $contract_id = null;
    public ?string $item_type = null;

    public function mount()
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function resetSorting(): void
    {
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
    }

    #[Computed]
    public function contractItems()
    {
        return ContractItem::with(['contract.supplier', 'itemSpecification.itemCatalog'])
            ->when($this->search, function ($query, $search) {
                $query->whereHas('contract', fn ($q) => $q->where('contract_po_ib_number', 'like', "%{$search}%"))
                    ->orWhereHas('contract.supplier', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('itemSpecification.itemCatalog', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhere('item_type', 'like', "%{$search}%");
            })
            ->when($this->contract_id, fn($q) => $q->where('contract_id', $this->contract_id))
            ->when($this->item_type, fn($q) => $q->where('item_type', $this->item_type))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    #[Computed]
    public function editingContractItemDeletionImpact(): array
    {
        if (!$this->editingContractItem) {
            return [
                'ics_numbers' => 0,
                'par_numbers' => 0,
                'idr_numbers' => 0,
                'has_associated_data' => false,
            ];
        }

        $icsCount = $this->editingContractItem->icsNumbers()->count();
        $parCount = $this->editingContractItem->parNumbers()->count();
        $idrCount = $this->editingContractItem->idrNumbers()->count();
        
        return [
            'ics_numbers' => $icsCount,
            'par_numbers' => $parCount,
            'idr_numbers' => $idrCount,
            'has_associated_data' => $icsCount > 0 || $parCount > 0 || $idrCount > 0,
        ];
    }
    
    public function resetFilters(): void
    {
        $this->reset('contract_id', 'item_type');
    }

    public function editContractItem(ContractItem $contractItem): void
    {
        $this->editingContractItem = $contractItem;
        Flux::modal('edit-contract-item')->show();
    }

    #[On('contract-item-created')]
    #[On('contract-item-updated')]
    #[On('contract-item-deleted')]
    public function refreshContractItems(): void
    {
        // Force refresh of computed property and reset to first page
        unset($this->contractItems);
        $this->resetPage();
        $this->dispatch('$refresh');
        
        // Reset editing contract item
        $this->editingContractItem = null;
    }

    #[Computed]
    public function contracts()
    {
        return Contract::with('supplier')->orderBy('contract_po_ib_number')->get();
    }

    public function sort($field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function with(): array
    {
        return [
            'contractItems' => $this->contractItems,
            'contracts' => $this->contracts,
        ];
    }
}; ?>

<div x-data="{ 
    ...tableResizer('contract_items_widths', { contract: 250, item: 300, type: 100, price: 150, date: 180, actions: 100 }),
    ...tableSettings('contract_items_settings')
}">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Contract Items
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
            <flux:button variant="outline" wire:click="$toggle('showFilters')" class="!p-2 @if($contract_id || $item_type) bg-primary-50 text-primary-600 dark:bg-primary-900/10 dark:text-primary-400 @endif">
                <x-flux::icon.filter class="h-5 w-5" />
                <span class="sr-only">Toggle Filters</span>
            </flux:button>
            <flux:modal.trigger name="create-contract-item">
                <flux:button variant="primary">New Contract Item</flux:button>
            </flux:modal.trigger>
        </div>
    </div>
    
    <div x-show="$wire.showFilters" x-collapse class="mt-4">
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <div class="border-b border-stone-200 p-4 dark:border-stone-700">
                <h3 class="font-semibold text-stone-800 dark:text-stone-200">Filter Options</h3>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <flux:select wire:model.live="contract_id" label="Contract">
                        <option value="">All Contracts</option>
                        @foreach($this->contracts as $contract)
                            <option value="{{ $contract->id }}">{{ $contract->contract_po_ib_number }} - {{ $contract->supplier->name }}</option>
                        @endforeach
                    </flux:select>
                    
                    <flux:select wire:model.live="item_type" label="Item Type">
                        <option value="">All Types</option>
                        <option value="ICS">ICS</option>
                        <option value="PAR">PAR</option>
                        <option value="IDR">IDR</option>
                    </flux:select>
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
            @if ($this->contractItems->total() > 0)
                <span>Showing {{ $this->contractItems->firstItem() }} to {{ $this->contractItems->lastItem() }} of <strong>{{ $this->contractItems->total() }}</strong> results.</span>
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
                default => 'overflow-x-auto',
            },
        ];
    @endphp

    <div class="mt-4 flow-root">
        <div class="{{ $densityClasses['table_wrapper'] }}">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700 table-fixed">
                        <thead class="bg-stone-50 dark:bg-stone-800">
                            <tr class="divide-x divide-stone-200 dark:divide-stone-700">
                                <th scope="col" :style="`width: ${columnWidths.contract}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                    <div wire:click="sort('contract_id')" class="flex cursor-pointer items-center">
                                        Contract
                                        @if ($sortBy === 'contract_id')
                                            <x-flux::icon.chevron-down class="ml-2 inline-block h-4 w-4 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }}" />
                                        @endif
                                    </div>
                                    <div @mousedown="startResize($event, 'contract')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                </th>
                                <th scope="col" :style="`width: ${columnWidths.item}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                    <div wire:click="sort('item_specification_id')" class="flex cursor-pointer items-center">
                                        Item
                                        @if ($sortBy === 'item_specification_id')
                                            <x-flux::icon.chevron-down class="ml-2 inline-block h-4 w-4 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }}" />
                                        @endif
                                    </div>
                                    <div @mousedown="startResize($event, 'item')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                </th>
                                <th scope="col" :style="`width: ${columnWidths.type}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                    <div wire:click="sort('item_type')" class="flex cursor-pointer items-center">
                                        Type
                                        @if ($sortBy === 'item_type')
                                            <x-flux::icon.chevron-down class="ml-2 inline-block h-4 w-4 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }}" />
                                        @endif
                                    </div>
                                    <div @mousedown="startResize($event, 'type')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                </th>
                                <th scope="col" :style="`width: ${columnWidths.price}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                    <div wire:click="sort('unit_price')" class="flex cursor-pointer items-center">
                                        Unit Price
                                        @if ($sortBy === 'unit_price')
                                            <x-flux::icon.chevron-down class="ml-2 inline-block h-4 w-4 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }}" />
                                        @endif
                                    </div>
                                    <div @mousedown="startResize($event, 'price')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                </th>
                                <th scope="col" :style="`width: ${columnWidths.date}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                    <div wire:click="sort('created_at')" class="flex cursor-pointer items-center">
                                        Date Added
                                        @if ($sortBy === 'created_at')
                                            <x-flux::icon.chevron-down class="ml-2 inline-block h-4 w-4 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }}" />
                                        @endif
                                    </div>
                                    <div @mousedown="startResize($event, 'date')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                </th>
                                <th scope="col" :style="`width: ${columnWidths.actions}px`" class="relative {{ $densityClasses['table_header'] }}">
                                    <span class="sr-only">Edit</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                             @forelse($this->contractItems as $contractItem)
                                <tr wire:key="contract-item-{{ $contractItem->id }}" class="hover:bg-stone-50 dark:hover:bg-stone-800/50">
                                    <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} font-medium text-stone-900 dark:text-stone-100" title="{{ $contractItem->contract->contract_po_ib_number }} - {{ $contractItem->contract->supplier->name }}">
                                        <div class="space-y-1">
                                            <div>{{ $contractItem->contract->contract_po_ib_number }}</div>
                                            <div class="text-xs text-stone-500 dark:text-stone-400">{{ $contractItem->contract->supplier->name }}</div>
                                        </div>
                                    </td>
                                    <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400" title="{{ $contractItem->itemSpecification->itemCatalog->name }}">
                                        <div class="space-y-1">
                                            <div>{{ $contractItem->itemSpecification->itemCatalog->name }}</div>
                                            @if($contractItem->itemSpecification->brand || $contractItem->itemSpecification->model)
                                                <div class="text-xs text-stone-500 dark:text-stone-400">
                                                    {{ $contractItem->itemSpecification->brand }}{{ $contractItem->itemSpecification->brand && $contractItem->itemSpecification->model ? ' - ' : '' }}{{ $contractItem->itemSpecification->model }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $contractItem->item_type === 'ICS' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/10 dark:text-blue-400' : ($contractItem->item_type === 'PAR' ? 'bg-green-50 text-green-700 dark:bg-green-900/10 dark:text-green-400' : 'bg-purple-50 text-purple-700 dark:bg-purple-900/10 dark:text-purple-400') }}">
                                            {{ $contractItem->item_type }}
                                        </span>
                                    </td>
                                    <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400" title="₱{{ number_format($contractItem->unit_price, 2) }}">₱{{ number_format($contractItem->unit_price, 2) }}</td>
                                    <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400" title="{{ $contractItem->created_at->format('M d, Y') }}">{{ $contractItem->created_at->format('M d, Y') }}</td>
                                    <td class="whitespace-nowrap {{ $densityClasses['table_cell'] }} text-right {{ $densityClasses['text_base'] }} font-medium">
                                        <button wire:click="editContractItem({{ $contractItem->id }})" wire:loading.attr="disabled" wire:target="editContractItem({{ $contractItem->id }})" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50">
                                           <x-flux::icon.pencil class="mr-1.5 h-4 w-4" wire:loading.remove wire:target="editContractItem({{ $contractItem->id }})" />
                                           <x-flux::icon.rotate-cw class="mr-1.5 h-4 w-4 animate-spin" wire:loading wire:target="editContractItem({{ $contractItem->id }})" />
                                           <span wire:loading.remove wire:target="editContractItem({{ $contractItem->id }})">Edit</span>
                                           <span wire:loading wire:target="editContractItem({{ $contractItem->id }})">Loading...</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="{{ $densityClasses['table_cell'] }} py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <x-flux::icon.package class="h-12 w-12 text-stone-400" />
                                            <h3 class="mt-2 {{ $densityClasses['text_base'] }} font-medium text-stone-900 dark:text-stone-100">No contract items found</h3>
                                            <p class="mt-1 {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">
                                                @if ($this->search || $this->contract_id || $this->item_type)
                                                    Try adjusting your search or filters.
                                                @else
                                                   Get started by creating a new contract item.
                                                @endif
                                            </p>
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
    <div class="mt-4">
        {{ $this->contractItems->links() }}
    </div>

    <!-- Create Contract Item Modal -->
    <x-admin.modal-form-wrapper name="create-contract-item" maxWidth="4xl">
        <livewire:admin.data.suppliers-and-contracts.contract-items.create />
    </x-admin.modal-form-wrapper>

    <!-- Edit Contract Item Modal -->
    @if($editingContractItem)
        <x-admin.modal-form-wrapper name="edit-contract-item" maxWidth="4xl">
            <livewire:admin.data.suppliers-and-contracts.contract-items.edit 
                :contractItem="$editingContractItem" 
                :key="'edit-contract-item-' . $editingContractItem->id" 
            />
        </x-admin.modal-form-wrapper>

        <!-- Enhanced Delete Confirmation Modal -->
        <x-admin.enhanced-delete-modal 
            name="delete-contract-item-confirmation"
            title="Delete Contract Item"
            entity-type="contract item"
            :entity-name="$editingContractItem->itemSpecification->itemCatalog->name"
            :association-counts="[
                'ICS numbers' => $this->editingContractItemDeletionImpact['ics_numbers'],
                'PAR numbers' => $this->editingContractItemDeletionImpact['par_numbers'],
                'IDR numbers' => $this->editingContractItemDeletionImpact['idr_numbers']
            ]"
            :has-associated-data="$this->editingContractItemDeletionImpact['has_associated_data']"
            :block-deletion="$this->editingContractItemDeletionImpact['has_associated_data']"
            delete-action="$dispatch('call-delete-contract-item')"
            cancel-action="$dispatch('call-cancel-delete-contract-item')"
        />
    @endif
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
