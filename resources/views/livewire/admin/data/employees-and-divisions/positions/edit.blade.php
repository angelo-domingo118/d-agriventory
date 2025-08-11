<?php

use App\Models\Position;
use App\Services\ToastService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    public Position $position;
    public string $title;
    public string $position_type;

    public function mount(Position $position): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        $this->position = $position;
        $this->title = $position->title;
        $this->position_type = $position->position_type ?? '';
    }

    public function confirmDelete(): void
    {        
        // Show the delete confirmation modal
        Flux::modal('delete-position-confirmation')->show();
    }

    public function cancelDelete(): void
    {
        // Close the delete confirmation modal
        Flux::modal('delete-position-confirmation')->close();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('positions', 'title')->ignore($this->position->id)],
            'position_type' => ['required', 'string', Rule::in(['DIVISION_CHIEF', 'COORDINATOR', 'FOCAL_PERSON', 'OFFICER', 'SPECIALIST', 'OTHER'])],
        ]);

        try {
            $this->position->update($validated);
            
            // Show success toast
            ToastService::updated($this, 'Position');
            
            // Close the modal and refresh the parent component
            $this->dispatch('position-updated');
            Flux::modal('edit-position')->close();
        } catch (\Exception $e) {
            Log::error('Error updating position: ' . $e->getMessage());
            ToastService::error($this, 'There was an error updating the position. Please try again.');
        }
    }

    public function delete(): void
    {
        try {
            // Check if deletion should be blocked
            if ($this->position->isDeletionBlocked()) {
                ToastService::error($this, 'Cannot delete this position because it has employees assigned.');
                Flux::modal('delete-position-confirmation')->close();
                return;
            }

            // Safe to delete normally
            $this->position->delete();
            ToastService::deleted($this, 'Position');

            // Close both modals and refresh the parent component
            Flux::modal('delete-position-confirmation')->close();
            Flux::modal('edit-position')->close();
            $this->dispatch('position-deleted');
            
        } catch (\Exception $e) {
            Log::error('Error deleting position: ' . $e->getMessage());
            $errorMessage = 'An error occurred while deleting the position. Please try again.';
            if (config('app.debug')) {
                $errorMessage .= ' Error: ' . $e->getMessage();
            }
            ToastService::error($this, $errorMessage);
            Flux::modal('delete-position-confirmation')->close();
        }
    }

    public function cancel(): void
    {
        Flux::modal('edit-position')->close();
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
        <flux:heading size="lg">Edit Position</flux:heading>
        <flux:text class="mt-2">Update position details.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-4">
        <flux:input wire:model="title" label="Position Title" placeholder="Enter position title (e.g., IT Officer, Chief Accountant)" required />
        <flux:select wire:model="position_type" label="Position Type" placeholder="Select position type" required>
            <option value="">Select position type</option>
            <option value="DIVISION_CHIEF">Division Chief</option>
            <option value="COORDINATOR">Coordinator</option>
            <option value="FOCAL_PERSON">Focal Person</option>
            <option value="OFFICER">Officer</option>
            <option value="SPECIALIST">Specialist</option>
            <option value="OTHER">Other</option>
        </flux:select>
        
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