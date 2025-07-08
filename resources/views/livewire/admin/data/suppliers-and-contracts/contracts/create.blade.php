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
    public ?int $supplier_id = null;
    public string $contract_po_ib_number = '';
    public array $items = [];

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }

        $this->addItem();
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
        $this->items[] = ['item_catalog_id' => '', 'unit_price' => 0, 'detailed_specifications' => '', 'item_type' => 'ICS'];
    }
    
    public function removeItem(int $index): void
    {
        array_splice($this->items, $index, 1);
    }

    public function save(): void
    {
        $rules = [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'contract_po_ib_number' => ['required', 'string', 'max:255', Rule::unique('contracts')],
            'items.*.item_catalog_id' => ['required', 'integer', Rule::exists('items_catalog', 'id')],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.detailed_specifications' => ['nullable', 'string'],
            'items.*.item_type' => ['required', 'string', Rule::in(['ICS', 'PAR', 'IDR'])],
        ];
        
        $validated = $this->validate($rules);

        \Illuminate\Support\Facades\DB::transaction(function() use ($validated) {
            $contract = Contract::create([
                'supplier_id' => $validated['supplier_id'],
                'contract_po_ib_number' => $validated['contract_po_ib_number'],
            ]);
            
            foreach($this->items as $itemData) {
                $catalogItem = ItemsCatalog::find($itemData['item_catalog_id']);
                if (!$catalogItem) continue;
                $specDetails = $itemData['detailed_specifications'] ?? "Standard specifications for {$catalogItem->name}";

                $specification = ItemSpecification::firstOrCreate(
                    ['item_catalog_id' => $catalogItem->id, 'detailed_specifications' => $specDetails]
                );

                $contract->contractItems()->create([
                    'item_specification_id' => $specification->id,
                    'unit_price' => $itemData['unit_price'],
                    'item_type' => $itemData['item_type'],
                ]);
            }
        });
        
        session()->flash('success', 'Contract created successfully.');
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
            Create Contract
        </h1>
    </div>

    <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
        <div class="p-6">
            <form wire:submit.prevent="save" class="space-y-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <flux:select wire:model="supplier_id" label="Supplier" required>
                        <option value="">Select a supplier</option>
                        @foreach($this->suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </flux:select>
                    
                    <flux:input wire:model="contract_po_ib_number" label="Contract/PO/IB Number" required />
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-stone-900 dark:text-stone-100">Items</h3>
                    @foreach($items as $index => $item)
                        <div wire:key="item-{{ $index }}" class="rounded-md border border-stone-200 p-4 dark:border-stone-700">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                                <div class="col-span-1 sm:col-span-2">
                                    <flux:select wire:model="items.{{ $index }}.item_catalog_id" label="Item" required>
                                        <option value="">Select an item</option>
                                        @foreach($this->catalogItems as $catalogItem)
                                            <option value="{{ $catalogItem->id }}">{{ $catalogItem->name }}</option>
                                        @endforeach
                                    </flux:select>
                                </div>
                                <flux:select wire:model="items.{{ $index }}.item_type" label="Item Type" required>
                                    <option value="ICS">ICS</option>
                                    <option value="PAR">PAR</option>
                                    <option value="IDR">IDR</option>
                                </flux:select>
                                <flux:input type="number" wire:model="items.{{ $index }}.unit_price" label="Unit Price" min="0" step="0.01" required />
                                <div class="flex items-end">
                                    <flux:button type="button" variant="danger" wire:click="removeItem({{ $index }})">
                                        <x-flux::icon.trash class="h-5 w-5" />
                                    </flux:button>
                                </div>
                                <div class="col-span-1 sm:col-span-2 md:col-span-4">
                                    <flux:textarea wire:model="items.{{ $index }}.detailed_specifications" label="Detailed Specifications" placeholder="Enter any specific details for this item..."></flux:textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div>
                    <flux:button type="button" wire:click="addItem">Add Item</flux:button>
                </div>

                <div class="flex justify-end gap-x-4">
                    <flux:button tag="a" href="{{ route('admin.data.suppliers-and-contracts.contracts.index') }}" wire:navigate variant="ghost">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Create Contract</flux:button>
                </div>
            </form>
        </div>
    </div>
</div> 