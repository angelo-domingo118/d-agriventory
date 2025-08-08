<?php

use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use App\Services\ToastService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    public SecondaryCategory $category;
    public string $name;
    public string $code;
    public ?int $primary_category_id;
    public string $description;

    public function mount(SecondaryCategory $category): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        $this->category = $category;
        $this->name = $category->name;
        $this->code = $category->code;
        $this->primary_category_id = $category->primary_category_id;
        $this->description = $category->description ?? '';
    }

    public function confirmDelete(): void
    {        
        // Show the delete confirmation modal
        Flux::modal('delete-secondary-category-confirmation')->show();
    }

    public function cancelDelete(): void
    {
        // Close the delete confirmation modal
        Flux::modal('delete-secondary-category-confirmation')->close();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('secondary_categories', 'name')->ignore($this->category->id)->whereNull('deleted_at')],
            'code' => ['required', 'string', 'max:50', Rule::unique('secondary_categories', 'code')->ignore($this->category->id)->whereNull('deleted_at')],
            'primary_category_id' => ['required', 'integer', Rule::exists('primary_categories', 'id')],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $this->category->update($validated);

        // Show success toast
        ToastService::updated($this, 'Secondary category');

        // Close the modal and refresh the parent component
        $this->dispatch('secondary-category-updated');
        Flux::modal('edit-secondary-category')->close();
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
                $message = 'Secondary category deleted successfully';
                if ($impact['has_associated_data']) {
                    $message .= ' along with ' . $impact['items'] . ' catalog ' . Str::plural('item', $impact['items']);
                }
                
                ToastService::success($this, $message . '.');
            } else {
                // Safe to delete normally
                $this->category->delete();
                ToastService::deleted($this, 'Secondary category');
            }

            // Close both modals and refresh the parent component
            Flux::modal('delete-secondary-category-confirmation')->close();
            Flux::modal('edit-secondary-category')->close();
            $this->dispatch('secondary-category-deleted');
            
        } catch (\Exception $e) {
            // Handle any errors during deletion
            ToastService::error($this, 'An error occurred while deleting the secondary category. Please try again.');
            Flux::modal('delete-secondary-category-confirmation')->close();
        }
    }

    public function cancel(): void
    {
        Flux::modal('edit-secondary-category')->close();
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

    #[Computed]
    public function primaryCategories()
    {
        return PrimaryCategory::orderBy('name')->get();
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Edit Secondary Category</flux:heading>
        <flux:text class="mt-2">Update the details for this secondary category.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-4">
        <flux:select wire:model="primary_category_id" label="Primary Category" placeholder="Select a primary category" required 
                         class="dark:[&>option]:bg-stone-800 dark:[&>option]:text-stone-100"
                         style="color-scheme: dark;">
                @foreach($this->primaryCategories as $pCat)
                    <option value="{{ $pCat->id }}" class="dark:bg-stone-800 dark:text-stone-100">{{ $pCat->name }}</option>
                @endforeach
            </flux:select>
        <flux:input wire:model="name" label="Category Name" placeholder="Enter category name" required />
        <flux:input wire:model="code" label="Category Code" placeholder="Enter unique code" required />
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