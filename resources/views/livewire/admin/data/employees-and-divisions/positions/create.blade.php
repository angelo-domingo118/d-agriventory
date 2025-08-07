<?php

use App\Models\Position;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    public string $title = '';
    public string $position_type = '';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('positions', 'title')],
            'position_type' => ['required', 'string', 'in:DIVISION_CHIEF,COORDINATOR,FOCAL_PERSON,OFFICER,SPECIALIST,OTHER'],
        ]);

        Position::create($validated);

        // Close the modal and refresh the parent component
        $this->dispatch('position-created');
        Flux::modal('create-position')->close();
        
        // Reset form
        $this->reset(['title', 'position_type']);
    }

    public function cancel(): void
    {
        Flux::modal('create-position')->close();
        $this->reset(['title', 'position_type']);
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Create Position</flux:heading>
        <flux:text class="mt-2">Add a new position title.</flux:text>
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
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="cancel">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Create Position</span>
                <span wire:loading>Creating...</span>
            </flux:button>
        </div>
    </form>
</div> 