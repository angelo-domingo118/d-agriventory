<?php

use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
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

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('secondary_categories', 'name')->ignore($this->category->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('secondary_categories', 'code')->ignore($this->category->id)],
            'primary_category_id' => ['required', 'integer', Rule::exists('primary_categories', 'id')],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $this->category->update($validated);

        // Close the modal and refresh the parent component
        $this->dispatch('secondary-category-updated');
        Flux::modal('edit-secondary-category')->close();
    }

    public function delete(): void
    {
        DB::transaction(function () {
            // Check if there are items in this category
            if ($this->category->items()->exists()) {
                session()->flash('error', 'Cannot delete a category that has items linked to it.');
                return;
            }

            $this->category->delete();
            
            // Close the modal and refresh the parent component
            $this->dispatch('secondary-category-deleted');
            Flux::modal('edit-secondary-category')->close();
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