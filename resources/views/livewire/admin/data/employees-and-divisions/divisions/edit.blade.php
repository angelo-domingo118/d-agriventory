<?php

use App\Models\Division;
use Illuminate\Validation\Rule;
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

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('divisions', 'name')->ignore($this->division->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('divisions', 'code')->ignore($this->division->id)],
        ]);

        $this->division->update($validated);

        // Close the modal and refresh the parent component
        $this->dispatch('division-updated');
        Flux::modal('edit-division')->close();
    }

    public function delete(): void
    {
        if ($this->division->employees()->exists()) {
            session()->flash('error', 'Cannot delete a division that has employees.');
            return;
        }

        $this->division->delete();

        // Close the modal and refresh the parent component
        $this->dispatch('division-deleted');
        Flux::modal('edit-division')->close();
    }

    public function cancel(): void
    {
        Flux::modal('edit-division')->close();
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
            <flux:button type="button" variant="danger" wire:click="delete" wire:confirm="Are you sure you want to delete this division? This action cannot be undone.">
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