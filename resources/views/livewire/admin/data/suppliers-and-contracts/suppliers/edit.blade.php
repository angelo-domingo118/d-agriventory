<?php

use App\Models\Supplier;
use App\Services\ToastService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    public Supplier $supplier;
    public string $name;

    public function mount(Supplier $supplier): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        $this->supplier = $supplier;
        $this->name = $supplier->name;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')->ignore($this->supplier->id)],
        ]);

        $this->supplier->update($validated);

        // Show success toast
        ToastService::updated($this, 'Supplier');

        // Close the modal and refresh the parent component
        $this->dispatch('supplier-updated');
        Flux::modal('edit-supplier')->close();
    }

    public function delete(): void
    {
        try {
            DB::transaction(function () {
                // Check if the supplier has any contracts
                if ($this->supplier->contracts()->exists()) {
                    ToastService::error($this, 'Cannot delete a supplier that has contracts.');
                    return;
                }

                $this->supplier->delete();
            });

            // Show success toast
            ToastService::deleted($this, 'Supplier');
            
            // Close the modal and refresh the parent component
            $this->dispatch('supplier-deleted');
            Flux::modal('edit-supplier')->close();
        } catch (\Exception $e) {
            // Handle any errors during deletion
            ToastService::error($this, 'An error occurred while deleting the supplier. Please try again.');
            Flux::modal('edit-supplier')->close();
        }
    }

    public function cancel(): void
    {
        Flux::modal('edit-supplier')->close();
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Edit Supplier</flux:heading>
        <flux:text class="mt-2">Update the details for this supplier.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-4">
        <flux:input wire:model="name" label="Supplier Name" placeholder="Enter supplier name" required />
        
        <div class="flex gap-2 pt-4 border-t border-stone-200 dark:border-stone-700">
            <flux:button type="button" variant="danger" wire:click="delete" wire:confirm="Are you sure you want to delete this supplier? This action cannot be undone.">
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