<?php

use App\Livewire\Traits\HasItemSpecifications;
use App\Models\Contract;
use App\Models\Supplier;
use App\Services\ToastService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    use HasItemSpecifications;

    public string $contract_po_ib_number = '';
    public ?int $supplier_id = null;
    public array $items = [];

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'item_specification_id' => null,
            'unit_price' => '',
            'item_type' => 'ICS',
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(): void
    {
        $this->validate([
            'contract_po_ib_number' => ['required', 'string', 'max:255', Rule::unique('contracts', 'contract_po_ib_number')],
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'items' => ['array', 'min:1'],
            'items.*.item_specification_id' => ['required', 'integer', Rule::exists('item_specifications', 'id')],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01'],
            'items.*.item_type' => ['required', 'string', 'in:ICS,PAR,IDR'],
        ]);

        DB::transaction(function () {
            $contract = Contract::create([
                'contract_po_ib_number' => $this->contract_po_ib_number,
                'supplier_id' => $this->supplier_id,
            ]);

            foreach ($this->items as $item) {
                $contract->contractItems()->create([
                    'item_specification_id' => $item['item_specification_id'],
                    'unit_price' => $item['unit_price'],
                    'item_type' => $item['item_type'],
                ]);
            }
        });

        // Show success toast
        ToastService::created($this, 'Contract');

        // Close the modal and refresh the parent component
        $this->dispatch('contract-created');
        Flux::modal('create-contract')->close();
        
        // Reset form
        $this->reset(['contract_po_ib_number', 'supplier_id', 'items']);
    }

    public function cancel(): void
    {
        Flux::modal('create-contract')->close();
        $this->reset(['contract_po_ib_number', 'supplier_id', 'items']);
    }

    #[Computed]
    public function suppliers()
    {
        return Supplier::orderBy('name')->get();
    }

    public function with(): array
    {
        return [
            'suppliers' => $this->suppliers,
            'specifications' => $this->itemSpecifications,
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Create Contract</flux:heading>
        <flux:text class="mt-2">Add a new contract to the system.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-6">

        <!-- Contract Details -->
        <div class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:select wire:model="supplier_id" label="Supplier" required>
                    <option value="">Select a supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </flux:select>
                
                <flux:input wire:model="contract_po_ib_number" label="Contract/PO/IB Number" required />
            </div>
        </div>

        <!-- Contract Items -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-stone-900 dark:text-stone-100">Contract Items</h3>
                <flux:button type="button" wire:click="addItem" variant="outline">Add Item</flux:button>
            </div>
            
            <div class="space-y-3">
                @foreach($items as $index => $item)
                    <div wire:key="item-{{ $index }}" class="rounded-md border border-stone-200 bg-stone-50 p-4 dark:border-stone-700 dark:bg-stone-800/50">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-5">
                            <div class="col-span-1 sm:col-span-2">
                                <flux:select wire:model="items.{{ $index }}.item_specification_id" label="Item Specification" required>
                                    <option value="">Select an item specification</option>
                                    @foreach($specifications as $spec)
                                        <option value="{{ $spec['id'] }}">{{ $spec['label'] }}</option>
                                    @endforeach
                                </flux:select>
                            </div>
                            <flux:input type="number" wire:model="items.{{ $index }}.unit_price" label="Unit Price" min="0.01" step="0.01" required />
                            <flux:select wire:model="items.{{ $index }}.item_type" label="Item Type" required>
                                <option value="ICS">ICS</option>
                                <option value="PAR">PAR</option>
                                <option value="IDR">IDR</option>
                            </flux:select>
                            <div class="flex items-end">
                                <flux:button type="button" variant="danger" wire:click="removeItem({{ $index }})">
                                    <x-flux::icon.trash class="h-5 w-5" />
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @endforeach
                
                @if(empty($items))
                    <div class="text-center py-6 text-stone-500 dark:text-stone-400">
                        <p>No items added yet. Click "Add Item" to get started.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Modal Actions -->
        <div class="flex gap-2 pt-4 border-t border-stone-200 dark:border-stone-700">
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="cancel">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Create Contract</span>
                <span wire:loading>Creating...</span>
            </flux:button>
        </div>
    </form>
</div> 