<?php

use App\Models\Division;
use App\Services\ToastService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    public Division $division;
    public string $name = '';
    public string $code = '';

    public function mount(Division $division): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }

        $this->division = $division;
        $this->name = $division->name;
        $this->code = $division->code;
    }

    public function confirmDelete(): void
    {        
        // Show the delete confirmation modal
        Flux::modal('delete-division-confirmation')->show();
    }

    public function cancelDelete(): void
    {
        // Close the delete confirmation modal
        Flux::modal('delete-division-confirmation')->close();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('divisions', 'name')->ignore($this->division->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('divisions', 'code')->ignore($this->division->id)],
        ]);

        try {
            $this->division->update($validated);
            
            // Show success toast
            ToastService::updated($this, 'Division');
            
            // Close the modal and refresh the parent component
            $this->dispatch('division-updated');
            Flux::modal('edit-division')->close();
        } catch (\Exception $e) {
            Log::error('Error updating division: ' . $e->getMessage());
            ToastService::error($this, 'There was an error updating the division. Please try again.');
        }
    }

    public function delete(): void
    {
        try {
            // Check if deletion should be blocked
            if ($this->division->isDeletionBlocked()) {
                ToastService::error($this, 'Cannot delete this division because it has employees assigned.');
                Flux::modal('delete-division-confirmation')->close();
                return;
            }

            // Safe to delete normally
            $this->division->delete();
            ToastService::deleted($this, 'Division');

            // Close both modals and refresh the parent component
            Flux::modal('delete-division-confirmation')->close();
            Flux::modal('edit-division')->close();
            $this->dispatch('division-deleted');
            
        } catch (\Exception $e) {
            Log::error('Error deleting division: ' . $e->getMessage());
            $errorMessage = 'An error occurred while deleting the division. Please try again.';
            if (config('app.debug')) {
                $errorMessage .= ' Error: ' . $e->getMessage();
            }
            ToastService::error($this, $errorMessage);
            Flux::modal('delete-division-confirmation')->close();
        }
    }

    public function cancel(): void
    {
        Flux::modal('edit-division')->close();
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
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Edit Division</flux:heading>
        <flux:text class="mt-2">Update division details.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-4">
        <flux:input wire:model="name" label="Division Name" placeholder="Enter division name" required />
        <flux:input wire:model="code" label="Division Code" placeholder="Enter unique code (e.g., IT, HR, FINANCE)" required />
        
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