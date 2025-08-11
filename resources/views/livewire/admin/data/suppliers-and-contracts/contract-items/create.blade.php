<?php

use App\Livewire\Traits\HasItemSpecifications;
use App\Models\ContractItem;
use App\Models\Contract;
use App\Services\ToastService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    use HasItemSpecifications;

    public ?int $contract_id = null;
    public ?int $item_specification_id = null;
    public string $unit_price = '';
    public string $item_type = 'ICS';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function save(): void
    {
        $this->validate([
            'contract_id' => ['required', 'integer', Rule::exists('contracts', 'id')],
            'item_specification_id' => ['required', 'integer', Rule::exists('item_specifications', 'id')],
            'unit_price' => ['required', 'numeric', 'min:0.01'],
            'item_type' => ['required', 'string', 'in:ICS,PAR,IDR'],
        ]);

        ContractItem::create([
            'contract_id' => $this->contract_id,
            'item_specification_id' => $this->item_specification_id,
            'unit_price' => $this->unit_price,
            'item_type' => $this->item_type,
        ]);

        // Show success toast
        ToastService::created($this, 'Contract Item');

        // Close the modal and refresh the parent component
        $this->dispatch('contract-item-created');
        Flux::modal('create-contract-item')->close();
        
        // Reset form
        $this->reset(['contract_id', 'item_specification_id', 'unit_price', 'item_type']);
    }

    public function cancel(): void
    {
        Flux::modal('create-contract-item')->close();
        $this->reset(['contract_id', 'item_specification_id', 'unit_price', 'item_type']);
    }

    #[Computed]
    public function contracts()
    {
        return Contract::with('supplier')->orderBy('contract_po_ib_number')->get();
    }

    public function with(): array
    {
        return [
            'contracts' => $this->contracts,
            'specifications' => $this->itemSpecifications,
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Create Contract Item</flux:heading>
        <flux:text class="mt-2">Add a new item to a contract.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-6">

        <!-- Contract Item Details -->
        <div class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:select wire:model="contract_id" label="Contract" required>
                    <option value="">Select a contract</option>
                    @foreach($contracts as $contract)
                        <option value="{{ $contract->id }}">{{ $contract->contract_po_ib_number }} - {{ $contract->supplier->name }}</option>
                    @endforeach
                </flux:select>
                
                <flux:select wire:model="item_specification_id" label="Item Specification" required>
                    <option value="">Select an item specification</option>
                    @foreach($specifications as $spec)
                        <option value="{{ $spec['id'] }}">{{ $spec['label'] }}</option>
                    @endforeach
                </flux:select>
            </div>
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input type="number" wire:model="unit_price" label="Unit Price (₱)" min="0.01" step="0.01" required />
                
                <flux:select wire:model="item_type" label="Item Type" required>
                    <option value="ICS">ICS - Inventory Custodian Slip</option>
                    <option value="PAR">PAR - Property Acknowledgment Receipt</option>
                    <option value="IDR">IDR - Inventory Delivery Receipt</option>
                </flux:select>
            </div>
        </div>

        <!-- Modal Actions -->
        <div class="flex gap-2 pt-4 border-t border-stone-200 dark:border-stone-700">
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="cancel">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Create Contract Item</span>
                <span wire:loading>Creating...</span>
            </flux:button>
        </div>
    </form>
</div>
