<?php

use App\Models\Contract;
use App\Models\Supplier;
use App\Services\ToastService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    public string $contract_po_ib_number = '';
    public ?int $supplier_id = null;

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function save(): void
    {
        $this->validate([
            'contract_po_ib_number' => ['required', 'string', 'max:255', Rule::unique('contracts', 'contract_po_ib_number')],
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
        ]);

        Contract::create([
            'contract_po_ib_number' => $this->contract_po_ib_number,
            'supplier_id' => $this->supplier_id,
        ]);

        // Show success toast
        ToastService::created($this, 'Contract');

        // Close the modal and refresh the parent component
        $this->dispatch('contract-created');
        Flux::modal('create-contract')->close();
        
        // Reset form
        $this->reset(['contract_po_ib_number', 'supplier_id']);
    }

    public function cancel(): void
    {
        Flux::modal('create-contract')->close();
        $this->reset(['contract_po_ib_number', 'supplier_id']);
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
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Create Contract</flux:heading>
        <flux:text class="mt-2">Add a new contract to the system. After creating the contract, you can add contract items separately.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-6">
        <!-- Contract Details -->
        <div class="space-y-4">
            <flux:select wire:model="supplier_id" label="Supplier" required>
                <option value="">Select a supplier</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </flux:select>
            
            <flux:input wire:model="contract_po_ib_number" label="Contract/PO/IB Number" placeholder="Enter contract/purchase order/inspection board number" required />
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