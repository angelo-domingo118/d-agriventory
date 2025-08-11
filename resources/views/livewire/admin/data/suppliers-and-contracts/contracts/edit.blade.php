<?php

use App\Models\Contract;
use App\Models\Supplier;
use App\Services\ToastService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    public Contract $contract;
    public string $contract_po_ib_number;
    public ?int $supplier_id;

    public function mount(Contract $contract): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        
        $this->contract = $contract;
        $this->contract_po_ib_number = $contract->contract_po_ib_number;
        $this->supplier_id = $contract->supplier_id;
    }

    public function save(): void
    {
        $this->validate([
            'contract_po_ib_number' => ['required', 'string', 'max:255', Rule::unique('contracts', 'contract_po_ib_number')->ignore($this->contract->id)],
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
        ]);

        $this->contract->update([
            'contract_po_ib_number' => $this->contract_po_ib_number,
            'supplier_id' => $this->supplier_id,
        ]);

        // Show success toast
        ToastService::updated($this, 'Contract');

        // Close the modal and refresh the parent component
        $this->dispatch('contract-updated');
        Flux::modal('edit-contract')->close();
    }

    public function confirmDeleteContract(): void
    {        
        // Show the delete confirmation modal
        Flux::modal('delete-contract-confirmation')->show();
    }

    public function cancelDeleteContract(): void
    {
        // Close the delete confirmation modal
        Flux::modal('delete-contract-confirmation')->close();
    }

    public function deleteContract(): void
    {
        try {
            DB::transaction(function () {
                $this->contract->contractItems()->delete();
                $this->contract->delete();
            });

            // Show success toast
            ToastService::deleted($this, 'Contract');

            // Close both modals and refresh the parent component
            Flux::modal('delete-contract-confirmation')->close();
            Flux::modal('edit-contract')->close();
            $this->dispatch('contract-deleted');
        } catch (\Exception $e) {
            // Handle any errors during deletion
            ToastService::error($this, 'An error occurred while deleting the contract. Please try again.');
            Flux::modal('delete-contract-confirmation')->close();
        }
    }

    #[On('call-delete-contract')]
    public function handleDeleteContract(): void
    {
        $this->deleteContract();
    }

    #[On('call-cancel-delete-contract')]
    public function handleCancelDeleteContract(): void
    {
        $this->cancelDeleteContract();
    }

    public function cancel(): void
    {
        Flux::modal('edit-contract')->close();
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

<div class="mx-auto max-w-lg">
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
            <flux:button type="button" variant="danger" wire:click="confirmDeleteContract">
                Delete
            </flux:button>
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="cancel">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Save Changes</span>
                <span wire:loading>Saving...</span>
            </flux:button>
        </div>
    </form>
</div> 