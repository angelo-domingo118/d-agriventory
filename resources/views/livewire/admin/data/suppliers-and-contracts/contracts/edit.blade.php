<?php

use App\Models\Contract;
use App\Models\ItemSpecification;
use App\Models\Supplier;
use App\Models\ItemsCatalog;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public Contract $contract;
    public array $items = [];

    public function mount(Contract $contract): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }

        $this->contract = $contract;
        $this->items = $contract->contractItems->map(function ($item) {
            return [
                'id' => $item->id,
                'item_catalog_id' => $item->itemSpecification?->catalogItem?->id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'detailed_specifications' => $item->itemSpecification?->detailed_specifications,
            ];
        })->all();
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
    
    public function addItem(): void
    {
        $this->items[] = ['item_catalog_id' => '', 'quantity' => 1, 'unit_price' => 0, 'detailed_specifications' => ''];
    }
    
    public function removeItem(int $index): void
    {
        array_splice($this->items, $index, 1);
    }

    public function save(): void
    {
        $rules = [
            'contract.supplier_id' => ['required', 'exists:suppliers,id'],
            'contract.contract_po_ib_number' => ['required', 'string', 'max:255', Rule::unique('contracts')->ignore($this->contract->id)],
            'items.*.item_catalog_id' => ['required', 'integer', Rule::exists('items_catalog', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.detailed_specifications' => ['nullable', 'string'],
        ];
        
        $this->validate($rules);

        \Illuminate\Support\Facades\DB::transaction(function() {
            $this->contract->save();
            
            $contractItemIds = [];

            foreach($this->items as $itemData) {
                if (empty($itemData['item_catalog_id'])) continue;
                
                $catalogItem = ItemsCatalog::find($itemData['item_catalog_id']);
                if (!$catalogItem) continue;

                $specDetails = $itemData['detailed_specifications'] ?? "Standard specifications for {$catalogItem->name}";

                $specification = ItemSpecification::firstOrCreate(
                    ['items_catalog_id' => $catalogItem->id, 'detailed_specifications' => $specDetails]
                );

                $contractItem = $this->contract->contractItems()->updateOrCreate(
                    ['item_specification_id' => $specification->id],
                    [
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                    ]
                );
                $contractItemIds[] = $contractItem->id;
            }
            
            // Delete items that were removed
            $this->contract->contractItems()->whereNotIn('id', $contractItemIds)->delete();
        });
        
        session()->flash('success', 'Contract updated successfully.');
        $this->redirectRoute('admin.data.suppliers-and-contracts.contracts.index');
    }

    public function deleteContract(): void
    {
        $this->contract->delete();
        session()->flash('success', 'Contract deleted successfully.');
        $this->redirectRoute('admin.data.suppliers-and-contracts.contracts.index');
    }

    public function with(): array
    {
        return [
            'suppliers' => $this->suppliers,
            'catalogItems' => $this->catalogItems,
        ];
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Edit Contract
        </h1>
    </div>

    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
        <div class="p-6">
            <form wire:submit.prevent="save" class="space-y-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <flux:select wire:model="contract.supplier_id" label="Supplier" required>
                        <option value="">Select a supplier</option>
                        @foreach($this->suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </flux:select>
                    
                    <flux:input wire:model="contract.contract_po_ib_number" label="Contract/PO/IB Number" required />
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-stone-900 dark:text-stone-100">Items</h3>
                    @foreach($items as $index => $item)
                        <div wire:key="item-{{ $index }}" class="rounded-md border border-stone-200 p-4 dark:border-stone-700">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 md:grid-cols-5">
                                <div class="col-span-1 sm:col-span-3 md:col-span-2">
                                    <flux:select wire:model="items.{{ $index }}.item_catalog_id" label="Item" required>
                                        <option value="">Select an item</option>
                                        @foreach($this->catalogItems as $catalogItem)
                                            <option value="{{ $catalogItem->id }}">{{ $catalogItem->name }}</option>
                                        @endforeach
                                    </flux:select>
                                </div>
                                <flux:input type="number" wire:model="items.{{ $index }}.quantity" label="Quantity" min="1" required />
                                <flux:input type="number" wire:model="items.{{ $index }}.unit_price" label="Unit Price" min="0" step="0.01" required />
                                <div class="flex items-end">
                                    <flux:button type="button" variant="danger" wire:click="removeItem({{ $index }})">
                                        <x-flux::icon.trash class="h-5 w-5" />
                                    </flux:button>
                                </div>
                                <div class="col-span-1 sm:col-span-3 md:col-span-5">
                                    <flux:textarea wire:model="items.{{ $index }}.detailed_specifications" label="Detailed Specifications" placeholder="Enter any specific details for this item..."></flux:textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div>
                    <flux:button type="button" wire:click="addItem">Add Item</flux:button>
                </div>

                <div class="flex justify-between">
                    <flux:button 
                        type="button" 
                        variant="danger" 
                        wire:click="deleteContract" 
                        wire:confirm="Are you sure you want to delete this contract? This action cannot be undone."
                    >
                        Delete Contract
                    </flux:button>
                    <div class="flex justify-end gap-x-4">
                        <flux:button tag="a" href="{{ route('admin.data.suppliers-and-contracts.contracts.index') }}" wire:navigate variant="ghost">Cancel</flux:button>
                        <flux:button type="submit" variant="primary">Save Changes</flux:button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div> 