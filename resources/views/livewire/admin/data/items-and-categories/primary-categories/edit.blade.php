<?php

use App\Models\PrimaryCategory;
use App\Services\ToastService;
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

    public function confirmDelete(): void
    {
        // Close the edit modal first to prevent stacking
        Flux::modal('edit-primary-category')->close();
        
        // Then show the delete confirmation modal
        Flux::modal('delete-primary-category-confirmation')->show();
    }

    public function cancelDelete(): void
    {
        // Close the delete confirmation modal
        Flux::modal('delete-primary-category-confirmation')->close();
        
        // Re-open the edit modal so user can continue editing
        Flux::modal('edit-primary-category')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('primary_categories', 'name')->ignore($this->category->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('primary_categories', 'code')->ignore($this->category->id)],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $this->category->update($validated);

        // Show success toast
        ToastService::updated($this, 'Primary category');

        // Close the modal and refresh the parent component
        $this->dispatch('primary-category-updated');
        Flux::modal('edit-primary-category')->close();
    }

    public function delete(): void
    {
        if ($this->category->secondaryCategories()->exists()) {
            // Show error toast
            ToastService::relationshipError($this);
            // Close the confirmation modal
            Flux::modal('delete-primary-category-confirmation')->close();
            return;
        }

        $this->category->delete();

        // Show success toast
        ToastService::deleted($this, 'Primary category');

        // Close both modals and refresh the parent component
        Flux::modal('delete-primary-category-confirmation')->close();
        Flux::modal('edit-primary-category')->close();
        $this->dispatch('primary-category-deleted');
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

    <!-- Delete Confirmation Modal -->
    <x-admin.delete-confirmation-modal 
        name="delete-primary-category-confirmation"
        title="Delete Primary Category"
        item-type="primary category"
        :item-name="$category->name"
        delete-action="delete"
        cancel-action="cancelDelete"
        message="Deleting this primary category will also affect all associated secondary categories and items."
    />
</div> 