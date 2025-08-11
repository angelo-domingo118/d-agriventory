<?php

use App\Models\Supplier;
use App\Services\ToastService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
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

    public function confirmDelete(): void
    {        
        // Show the delete confirmation modal
        Flux::modal('delete-supplier-confirmation')->show();
    }

    public function cancelDelete(): void
    {
        // Close the delete confirmation modal
        Flux::modal('delete-supplier-confirmation')->close();
    }

    public function delete(): void
    {
        try {
            // Check if deletion should be blocked
            if ($this->supplier->contracts()->exists()) {
                ToastService::error($this, 'Cannot delete this supplier because it has active contracts. Remove contracts first.');
                Flux::modal('delete-supplier-confirmation')->close();
                return;
            }

            // Safe to delete normally
            $this->supplier->delete();
            ToastService::deleted($this, 'Supplier');

            // Close both modals and refresh the parent component
            Flux::modal('delete-supplier-confirmation')->close();
            Flux::modal('edit-supplier')->close();
            $this->dispatch('supplier-deleted');
            
        } catch (\Exception $e) {
            ToastService::error($this, 'An error occurred while deleting the supplier. Please try again.');
            Flux::modal('delete-supplier-confirmation')->close();
        }
    }

    #[On('call-delete')]
    public function handleDelete(): void
    {
        $this->delete();
    }

    #[On('call-cancel-delete')]
    public function handleCancelDelete(): void
    {
        $this->cancelDelete();
    }

    public function cancel(): void
    {
        Flux::modal('edit-supplier')->close();
    }
}; ?>

<div class="mx-auto max-w-lg">
    <div>
        <flux:heading size="lg">Edit Supplier</flux:heading>
        <flux:text class="mt-2">Update the details for this supplier.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-4">
        <flux:input wire:model="name" label="Supplier Name" placeholder="Enter supplier name" required />
        
        <div class="flex gap-2 pt-4 border-t border-stone-200 dark:border-stone-700">
            <flux:button type="button" variant="danger" wire:click="confirmDelete">
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