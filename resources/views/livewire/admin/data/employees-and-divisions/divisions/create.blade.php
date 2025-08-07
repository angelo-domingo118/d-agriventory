<?php

use App\Models\Division;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    public string $name = '';
    public string $code = '';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('divisions', 'name')],
            'code' => ['required', 'string', 'max:50', Rule::unique('divisions', 'code')],
        ]);

        Division::create($validated);

        // Close the modal and refresh the parent component
        $this->dispatch('division-created');
        Flux::modal('create-division')->close();
        
        // Reset form
        $this->reset(['name', 'code']);
    }

    public function cancel(): void
    {
        Flux::modal('create-division')->close();
        $this->reset(['name', 'code']);
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Create Division</flux:heading>
        <flux:text class="mt-2">Add a new division or office.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-4">
        <flux:input wire:model="name" label="Division Name" placeholder="Enter division name" required />
        <flux:input wire:model="code" label="Division Code" placeholder="Enter unique code (e.g., IT, HR, FINANCE)" required />
        
        <div class="flex gap-2 pt-4 border-t border-stone-200 dark:border-stone-700">
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="cancel">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Create Division</span>
                <span wire:loading>Creating...</span>
            </flux:button>
        </div>
    </form>
</div>