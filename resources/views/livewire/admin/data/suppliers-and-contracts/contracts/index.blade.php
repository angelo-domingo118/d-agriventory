<?php

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\ItemSpecification;
use App\Models\Supplier;
use App\Models\ItemsCatalog;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public ?Contract $editing = null;
    public bool $showCreateModal = false;
    public string $search = '';

    public array $items = [];

    public function mount()
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function getContractsProperty()
    {
        return Contract::with('supplier')->withCount('contractItems')
            ->when($this->search, function ($query, $search) {
                $query->where('contract_po_ib_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function getSuppliersProperty()
    {
        return Supplier::orderBy('name')->get();
    }
    
    public function getCatalogItemsProperty()
    {
        return ItemsCatalog::orderBy('name')->get();
    }

    public function newContract(): void
    {
        $this->editing = new Contract();
        $this->items = [['item_catalog_id' => '', 'quantity' => 1, 'unit_price' => 0]];
        $this->showCreateModal = true;
    }

    public function edit(Contract $contract): void
    {
        $this->editing = $contract;
        $this->items = $contract->contractItems->map(function ($item) {
            return [
                'id' => $item->id,
                'item_catalog_id' => $item->itemSpecification?->catalogItem?->id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'detailed_specifications' => $item->itemSpecification?->detailed_specifications,
            ];
        })->all();

        $this->showCreateModal = true;
    }
    
    public function addItem(): void
    {
        $this->items[] = ['item_catalog_id' => '', 'quantity' => 1, 'unit_price' => 0];
    }
    
    public function removeItem(int $index): void
    {
        array_splice($this->items, $index, 1);
    }

    public function save(): void
    {
        // This is a simplified save method. A real implementation would handle item specifications correctly.
        $rules = [
            'editing.supplier_id' => ['required', 'exists:suppliers,id'],
            'editing.contract_po_ib_number' => ['required', 'string', 'max:255', Rule::unique('contracts')->ignore($this->editing->id)],
            'items.*.item_catalog_id' => ['required', 'integer', Rule::exists('items_catalog', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
        $this->validate($rules);

        \Illuminate\Support\Facades\DB::transaction(function() {
            $this->editing->save();
            
            $contractItemIds = [];

            foreach($this->items as $itemData) {
                if (empty($itemData['item_catalog_id'])) continue;
                
                $catalogItem = ItemsCatalog::find($itemData['item_catalog_id']);
                if (!$catalogItem) continue;

                $specDetails = $itemData['detailed_specifications'] ?? "Standard specifications for {$catalogItem->name}";

                $specification = ItemSpecification::firstOrCreate(
                    ['items_catalog_id' => $catalogItem->id, 'detailed_specifications' => $specDetails]
                );

                $contractItem = $this->editing->contractItems()->updateOrCreate(
                    [
                        'item_specification_id' => $specification->id,
                    ],
                    [
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                    ]
                );
                $contractItemIds[] = $contractItem->id;
            }

            // Delete contract items that are no longer in the form
            $this->editing->contractItems()->whereNotIn('id', $contractItemIds)->delete();
        });

        $this->showCreateModal = false;
        $this->dispatch('contract-saved');
        session()->flash('success', 'Contract saved successfully.');
    }

    public function with(): array
    {
        return [
            'contracts' => $this->contracts,
            'suppliers' => $this->suppliers,
            'catalogItems' => $this->catalogItems,
        ];
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-stone-700 dark:text-stone-200">Contracts</h2>
        <div class="flex items-center gap-x-4">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search contracts..." />
            <flux:button wire:click="newContract" variant="primary">New Contract</flux:button>
        </div>
    </div>

    <div class="mt-4 overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
        <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
            <thead class="bg-stone-50 dark:bg-stone-800">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Contract/PO/IB Number</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Supplier</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Date Created</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Items</th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Edit</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                @forelse($contracts as $contract)
                    <tr wire:key="contract-{{ $contract->id }}">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-stone-900 dark:text-stone-100">{{ $contract->contract_po_ib_number }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">{{ $contract->supplier->name }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">{{ $contract->created_at->format('M d, Y') }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">{{ $contract->contract_items_count }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <flux:button wire:click="edit({{ $contract->id }})" variant="ghost" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-200">Edit</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">
                            No contracts found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $contracts->links() }}
    </div>

    <!-- Create/Edit Modal -->
    @if($editing)
        <flux:modal :show="$showCreateModal" max-width="4xl" @close="$set('showCreateModal', false)">
            <form wire:submit.prevent="save">
                <x-slot:title>
                    {{ $editing->exists ? 'Edit' : 'Create' }} Contract
                </x-slot:title>

                <div class="space-y-6 p-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <flux:input wire:model="editing.contract_po_ib_number" label="Contract/PO/IB Number" required />
                        <flux:select wire:model="editing.supplier_id" label="Supplier" required>
                            <option value="">Select a supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="relative">
                      <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-stone-300 dark:border-stone-600"></div>
                      </div>
                      <div class="relative flex justify-center">
                        <span class="bg-white px-3 text-base font-semibold leading-6 text-stone-900 dark:bg-stone-800 dark:text-stone-100">Contract Items</span>
                      </div>
                    </div>
                    
                    <div class="space-y-4">
                        @foreach($items as $index => $item)
                            <div wire:key="item-{{$index}}" class="flex items-end gap-x-4 rounded-md border p-4 dark:border-stone-700">
                                <div class="grid flex-grow grid-cols-1 gap-4 sm:grid-cols-3">
                                    <flux:select wire:model="items.{{$index}}.item_catalog_id" label="Item">
                                        <option value="">Select Item</option>
                                        @foreach($catalogItems as $catalogItem)
                                            <option value="{{$catalogItem->id}}">{{$catalogItem->name}}</option>
                                        @endforeach
                                    </flux:select>
                                    <flux:input type="number" wire:model="items.{{$index}}.quantity" label="Quantity" min="1" />
                                    <flux:input type="number" wire:model="items.{{$index}}.unit_price" label="Unit Price" min="0" step="0.01" />
                                </div>
                                <div class="flex-shrink-0">
                                    <flux:button type="button" wire:click="removeItem({{$index}})" variant="danger" class="!p-2">
                                        <x-flux::icon.x-mark class="h-5 w-5" />
                                    </flux:button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div>
                        <flux:button type="button" wire:click="addItem" variant="outline">Add Item</flux:button>
                    </div>

                </div>

                <x-slot:footer>
                    <div class="flex justify-end gap-x-4">
                        <flux:button variant="ghost" @click="$set('showCreateModal', false)">Cancel</flux:button>
                        <flux:button type="submit" variant="primary">Save</flux:button>
                    </div>
                </x-slot:footer>
            </form>
        </flux:modal>
    @endif
</div> 