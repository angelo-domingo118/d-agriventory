<?php

use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use App\Services\ToastService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    public string $name = '';
    public string $code = '';
    public ?int $primary_category_id = null;
    public string $description = '';

    public function mount(?int $primaryCategoryId = null): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }

        if ($primaryCategoryId) {
            $this->primary_category_id = $primaryCategoryId;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('secondary_categories', 'name')->whereNull('deleted_at')],
            'code' => ['required', 'string', 'max:50', Rule::unique('secondary_categories', 'code')->whereNull('deleted_at')],
            'primary_category_id' => ['required', 'integer', Rule::exists('primary_categories', 'id')],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        SecondaryCategory::create($validated);

        // Show success toast
        ToastService::created($this, 'Secondary category');

        // Close the modal and refresh the parent component
        $this->dispatch('secondary-category-created');
        Flux::modal('create-secondary-category')->close();
        
        // Reset form
        $this->reset(['name', 'code', 'primary_category_id', 'description']);
    }

    public function cancel(): void
    {
        Flux::modal('create-secondary-category')->close();
        $this->reset(['name', 'code', 'primary_category_id', 'description']);
    }

    #[Computed]
    public function primaryCategories()
    {
        return PrimaryCategory::orderBy('name')->get();
    }

    public function with(): array
    {
        return [
            'primaryCategories' => $this->primaryCategories,
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Create Secondary Category</flux:heading>
        <flux:text class="mt-2">Create a new secondary category for organizing items.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-4">
        <flux:select wire:model="primary_category_id" label="Primary Category" placeholder="Select a primary category" required>
            <option value="">Select a primary category</option>
            @foreach($this->primaryCategories as $pCat)
                <option value="{{ $pCat->id }}">{{ $pCat->name }}</option>
            @endforeach
        </flux:select>
        <flux:input wire:model="name" label="Category Name" placeholder="Enter category name" required />
        <flux:input wire:model="code" label="Category Code" placeholder="Enter unique code (e.g., LAPTOP, PRINTER)" required />
        <flux:textarea wire:model="description" label="Description" placeholder="Optional description for this category" rows="3" />
        
        <div class="flex gap-2 pt-4 border-t border-stone-200 dark:border-stone-700">
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