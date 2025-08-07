<?php

use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use App\Services\ToastService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
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
        Flux::modal('delete-secondary-category-confirmation')->show();
    }

    public function cancelDelete(): void
    {
        Flux::modal('delete-secondary-category-confirmation')->close();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('secondary_categories', 'name')->ignore($this->category->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('secondary_categories', 'code')->ignore($this->category->id)],
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
        DB::transaction(function () {
            // Check if there are items in this category
            if ($this->category->items()->exists()) {
                ToastService::relationshipError($this);
                // Close the confirmation modal
                Flux::modal('delete-secondary-category-confirmation')->close();
                return;
            }

            $this->category->delete();
            
            // Show success toast
            ToastService::deleted($this, 'Secondary category');
            
            // Close both modals and refresh the parent component
            Flux::modal('delete-secondary-category-confirmation')->close();
            Flux::modal('edit-secondary-category')->close();
            $this->dispatch('secondary-category-deleted');
        });
    }

    public function cancel(): void
    {
        Flux::modal('edit-secondary-category')->close();
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
        <flux:input wire:model="name" label="Category Name" placeholder="Enter category name" required />
        <flux:input wire:model="code" label="Category Code" placeholder="Enter unique code" required />
        <flux:select wire:model="primary_category_id" label="Primary Category" placeholder="Select a primary category" required>
            <option value="">Select a primary category</option>
            @foreach($this->primaryCategories as $pCat)
                <option value="{{ $pCat->id }}">{{ $pCat->name }}</option>
            @endforeach
        </flux:select>
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
        name="delete-secondary-category-confirmation"
        title="Delete Secondary Category"
        item-type="secondary category"
        :item-name="$category->name"
        delete-action="delete"
        cancel-action="cancelDelete"
        message="Deleting this secondary category will also affect all associated items."
    />
</div> 