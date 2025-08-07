<?php

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\ItemSpecification;
use App\Models\Supplier;
use App\Models\ItemsCatalog;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
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
    public ?Contract $editingContract = null;
    
    // Filters
    public ?int $supplier_id = null;

    public function mount()
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    #[Computed]
    public function contracts()
    {
        return Contract::with('supplier')->withCount('contractItems')
            ->when($this->search, function ($query, $search) {
                $query->where('contract_po_ib_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->when($this->supplier_id, fn($q) => $q->where('supplier_id', $this->supplier_id))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }
    
    public function resetFilters(): void
    {
        $this->reset('supplier_id');
    }

    public function editContract(Contract $contract): void
    {
        $this->editingContract = $contract;
        Flux::modal('edit-contract')->show();
    }

    #[Computed]
    public function suppliers()
    {
        return Supplier::orderBy('name')->get();
    }
    
    #[Computed]
    public function catalogItems()
    {
        return ItemsCatalog::orderBy('name')->get();
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
            'contracts' => $this->contracts,
            'suppliers' => $this->suppliers,
        ];
    }
}; ?>

<div x-data="tableResizer('contracts_widths', { contract_number: 300, supplier: 250, items: 150, date: 180, actions: 100 })">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Contracts
        </h1>
        <div class="flex items-center gap-x-2">
             <div x-data="{ open: false }" class="relative">
                <flux:button variant="outline" x-on:click="open = !open" class="!p-2">
                    <x-flux::icon.settings-2 class="h-5 w-5" />
                    <span class="sr-only">Toggle View Options</span>
                </flux:button>
                <div x-show="open" x-on:click.outside="open = false" x-transition class="absolute right-0 z-10 mt-2 w-72 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-stone-800 dark:ring-stone-700" style="display: none;">
                    <div class="px-3 py-2">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Items per Page</div>
                        <flux:select wire:model.live="perPage" id="perPage" class="mt-1">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </flux:select>
                    </div>
                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="mb-2 text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Column Layout</div>
                        <flux:button
                            variant="ghost"
                            x-on:click="$dispatch('reset-column-widths')"
                            class="w-full justify-center"
                        >
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
            <flux:button variant="outline" wire:click="$toggle('showFilters')" class="!p-2 @if($supplier_id) bg-primary-50 text-primary-600 dark:bg-primary-900/10 dark:text-primary-400 @endif">
                <x-flux::icon.filter class="h-5 w-5" />
                <span class="sr-only">Toggle Filters</span>
            </flux:button>
            <flux:button tag="a" href="{{ route('admin.data.suppliers-and-contracts.contracts.create', ['view' => 'table']) }}" wire:navigate variant="primary">New Contract</flux:button>
        </div>
    </div>
    
    <div x-show="$wire.showFilters" x-collapse class="mt-4">
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <div class="border-b border-stone-200 p-4 dark:border-stone-700">
                <h3 class="font-semibold text-stone-800 dark:text-stone-200">Filter Options</h3>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 gap-6">
                    <flux:select wire:model.live="supplier_id" label="Supplier">
                        <option value="">All Suppliers</option>
                        @foreach($this->suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
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
            @if ($this->contracts->total() > 0)
                <span>Showing {{ $this->contracts->firstItem() }} to {{ $this->contracts->lastItem() }} of <strong>{{ $this->contracts->total() }}</strong> results.</span>
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

    <div class="mt-4 flow-root">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700 table-fixed">
                        <thead class="bg-stone-50 dark:bg-stone-800">
                            <tr class="divide-x divide-stone-200 dark:divide-stone-700">
                                <th scope="col" :style="`width: ${columnWidths.contract_number}px`" class="relative px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                    <div wire:click="sort('contract_po_ib_number')" class="flex cursor-pointer items-center">
                                        Contract/PO/IB No.
                                        @if ($sortBy === 'contract_po_ib_number')
                                            <x-flux::icon.chevron-down class="ml-2 inline-block h-4 w-4 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }}" />
                                        @endif
                                    </div>
                                    <div @mousedown="startResize($event, 'contract_number')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                </th>
                                <th scope="col" :style="`width: ${columnWidths.supplier}px`" class="relative px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                    <div wire:click="sort('supplier_id')" class="flex cursor-pointer items-center">
                                        Supplier
                                        @if ($sortBy === 'supplier_id')
                                            <x-flux::icon.chevron-down class="ml-2 inline-block h-4 w-4 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }}" />
                                        @endif
                                    </div>
                                    <div @mousedown="startResize($event, 'supplier')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                </th>
                                <th scope="col" :style="`width: ${columnWidths.items}px`" class="relative px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                    <div class="flex items-center">
                                        Items
                                    </div>
                                    <div @mousedown="startResize($event, 'items')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                </th>
                                <th scope="col" :style="`width: ${columnWidths.date}px`" class="relative px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                    <div wire:click="sort('created_at')" class="flex cursor-pointer items-center">
                                        Date Added
                                        @if ($sortBy === 'created_at')
                                            <x-flux::icon.chevron-down class="ml-2 inline-block h-4 w-4 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }}" />
                                        @endif
                                    </div>
                                    <div @mousedown="startResize($event, 'date')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                </th>
                                <th scope="col" :style="`width: ${columnWidths.actions}px`" class="relative px-6 py-3">
                                    <span class="sr-only">Edit</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                             @forelse($this->contracts as $contract)
                                <tr wire:key="contract-{{ $contract->id }}" class="hover:bg-stone-50 dark:hover:bg-stone-800/50">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-stone-900 dark:text-stone-100">{{ $contract->contract_po_ib_number }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">{{ $contract->supplier->name }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">{{ $contract->contract_items_count }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">{{ $contract->created_at->format('M d, Y') }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <button wire:click="editContract({{ $contract->id }})" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 text-sm font-semibold text-stone-900 shadow-sm hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50">
                                            <x-flux::icon.edit class="mr-1.5 h-4 w-4" />
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <x-flux::icon.file-text class="h-12 w-12 text-stone-400" />
                                            <h3 class="mt-2 text-sm font-medium text-stone-900 dark:text-stone-100">No contracts found</h3>
                                            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                                                @if ($this->search || $this->supplier_id)
                                                    Try adjusting your search or filters.
                                                @else
                                                   Get started by creating a new contract.
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
        {{ $this->contracts->links() }}
    </div>

    <!-- Edit Contract Modal -->
    @if($editingContract)
        <x-admin.modal-form-wrapper name="edit-contract" maxWidth="4xl">
            <livewire:admin.data.suppliers-and-contracts.contracts.edit 
                :contract="$editingContract" 
                :key="'edit-contract-' . $editingContract->id" 
            />
        </x-admin.modal-form-wrapper>
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
    });
</script> 