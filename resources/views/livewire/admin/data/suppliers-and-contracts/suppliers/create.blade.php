<?php

use App\Models\Supplier;
use App\Services\ToastService;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    public string $name = '';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')],
        ]);

        Supplier::create($validated);

        // Show success toast
        ToastService::created($this, 'Supplier');

        // Close the modal and refresh the parent component
        $this->dispatch('supplier-created');
        Flux::modal('create-supplier')->close();
        
        // Reset form
        $this->reset('name');
    }

    public function cancel(): void
    {
        Flux::modal('create-supplier')->close();
        $this->reset('name');
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Create Supplier</flux:heading>
        <flux:text class="mt-2">Add a new supplier to the system.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-4">
        <flux:input wire:model="name" label="Supplier Name" placeholder="Enter supplier company name" required />
        
        <div class="flex gap-2 pt-4 border-t border-stone-200 dark:border-stone-700">
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="cancel">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Create Supplier</span>
                <span wire:loading>Creating...</span>
            </flux:button>
        </div>
    </form>
</div>