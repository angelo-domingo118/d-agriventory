<?php

use App\Models\PrimaryCategory;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    public PrimaryCategory $category;
    public string $name;
    public string $code;
    public string $description;

    public function mount(PrimaryCategory $category): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        $this->category = $category;
        $this->name = $category->name;
        $this->code = $category->code;
        $this->description = $category->description ?? '';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('primary_categories', 'name')->ignore($this->category->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('primary_categories', 'code')->ignore($this->category->id)],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $this->category->update($validated);

        // Close the modal and refresh the parent component
        $this->dispatch('primary-category-updated');
        Flux::modal('edit-primary-category')->close();
    }

    public function delete(): void
    {
        if ($this->category->secondaryCategories()->exists()) {
            // Show error message but don't close modal
            session()->flash('error', 'Cannot delete a primary category that has secondary categories linked to it.');
            return;
        }

        $this->category->delete();

        // Close the modal and refresh the parent component
        $this->dispatch('primary-category-deleted');
        Flux::modal('edit-primary-category')->close();
    }

    public function cancel(): void
    {
        Flux::modal('edit-primary-category')->close();
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Edit Primary Category</flux:heading>
        <flux:text class="mt-2">Update the details for this primary category.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-4">
        <flux:input wire:model="name" label="Category Name" placeholder="Enter category name" required />
        <flux:input wire:model="code" label="Category Code" placeholder="Enter unique code (e.g., ELEC, COMP)" required />
        <flux:textarea wire:model="description" label="Description" placeholder="Optional description for this category" rows="3" />
        
        <div class="flex gap-2 pt-4 border-t border-stone-200 dark:border-stone-700">
            <flux:button type="button" variant="danger" wire:click="delete" wire:confirm="Are you sure you want to delete this category? This action cannot be undone.">
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