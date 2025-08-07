<?php

use App\Models\Position;
use Illuminate\Validation\Rule;
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

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('positions', 'title')->ignore($this->position->id)],
            'position_type' => ['required', 'string', Rule::in(['DIVISION_CHIEF', 'COORDINATOR', 'FOCAL_PERSON', 'OFFICER', 'SPECIALIST', 'OTHER'])],
        ]);

        $this->position->update($validated);

        // Close the modal and refresh the parent component
        $this->dispatch('position-updated');
        Flux::modal('edit-position')->close();
    }

    public function delete(): void
    {
        if ($this->position->employees()->exists()) {
            session()->flash('error', 'Cannot delete a position that has employees assigned to it.');
            return;
        }

        $this->position->delete();

        // Close the modal and refresh the parent component
        $this->dispatch('position-deleted');
        Flux::modal('edit-position')->close();
    }

    public function cancel(): void
    {
        Flux::modal('edit-position')->close();
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
            <flux:button type="button" variant="danger" wire:click="delete" wire:confirm="Are you sure you want to delete this position? This action cannot be undone.">
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