<?php

use App\Models\PrimaryCategory;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    public string $name = '';
    public string $code = '';
    public string $description = '';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('primary_categories', 'name')],
            'code' => ['required', 'string', 'max:50', Rule::unique('primary_categories', 'code')],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        PrimaryCategory::create($validated);

        // Close the modal and refresh the parent component
        $this->dispatch('primary-category-created');
        Flux::modal('create-primary-category')->close();
        
        // Reset form
        $this->reset(['name', 'code', 'description']);
    }

    public function cancel(): void
    {
        Flux::modal('create-primary-category')->close();
        $this->reset(['name', 'code', 'description']);
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Create Primary Category</flux:heading>
        <flux:text class="mt-2">Create a new primary category for items.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-4">
        <flux:input wire:model="name" label="Category Name" required />
        <flux:input wire:model="code" label="Category Code" required />
        <flux:textarea wire:model="description" label="Description" rows="3" />
        
        <div class="flex gap-2 pt-4">
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="cancel">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Create Category</span>
                <span wire:loading>Creating...</span>
            </flux:button>
        </div>
    </form>
</div> 