<?php

use App\Models\PrimaryCategory;
use App\Services\ToastService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
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
        // Show the delete confirmation modal
        Flux::modal('delete-primary-category-confirmation')->show();
    }

    public function cancelDelete(): void
    {
        // Close the delete confirmation modal
        Flux::modal('delete-primary-category-confirmation')->close();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('primary_categories', 'name')->ignore($this->category->id)->whereNull('deleted_at')],
            'code' => ['required', 'string', 'max:50', Rule::unique('primary_categories', 'code')->ignore($this->category->id)->whereNull('deleted_at')],
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
        try {
            // Check if there are associations and force delete them
            if (!$this->category->canBeDeletedSafely()) {
                $impact = $this->category->getDeletionImpact();
                
                // Use force delete to remove all associations
                $this->category->forceDeleteWithAssociations();
                
                // Show specific success message about what was deleted
                $message = 'Primary category deleted successfully';
                if ($impact['has_associated_data']) {
                    $parts = [];
                    if ($impact['secondary_categories'] > 0) {
                        $parts[] = $impact['secondary_categories'] . ' secondary ' . Str::plural('category', $impact['secondary_categories']);
                    }
                    if ($impact['items'] > 0) {
                        $parts[] = $impact['items'] . ' catalog ' . Str::plural('item', $impact['items']);
                    }
                    $message .= ' along with ' . implode(' and ', $parts);
                }
                
                ToastService::success($this, $message . '.');
            } else {
                // Safe to delete normally
                $this->category->delete();
                ToastService::deleted($this, 'Primary category');
            }

            // Close both modals and refresh the parent component
            Flux::modal('delete-primary-category-confirmation')->close();
            Flux::modal('edit-primary-category')->close();
            $this->dispatch('primary-category-deleted');
            
        } catch (\Exception $e) {
            // Handle any errors during deletion
            ToastService::error($this, 'An error occurred while deleting the primary category. Please try again.');
            Flux::modal('delete-primary-category-confirmation')->close();
        }
    }

    public function cancel(): void
    {
        Flux::modal('edit-primary-category')->close();
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


</div> 