<?php

use App\Models\ItemsCatalog;
use App\Models\SecondaryCategory;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    public string $name = '';
    public string $code = '';
    public string $unit = '';
    public ?int $secondary_category_id = null;

    public function mount(?int $secondaryCategoryId = null): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }

        if ($secondaryCategoryId) {
            $this->secondary_category_id = $secondaryCategoryId;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('items_catalog', 'name')],
            'code' => ['required', 'string', 'max:50', Rule::unique('items_catalog', 'code')],
            'unit' => ['required', 'string', 'max:50'],
            'secondary_category_id' => ['required', 'integer', Rule::exists('secondary_categories', 'id')],
        ]);

        ItemsCatalog::create($validated);

        // Close the modal and refresh the parent component
        $this->dispatch('item-created');
        Flux::modal('create-item')->close();
        
        // Reset form
        $this->reset(['name', 'code', 'unit', 'secondary_category_id']);
    }

    public function cancel(): void
    {
        Flux::modal('create-item')->close();
        $this->reset(['name', 'code', 'unit', 'secondary_category_id']);
    }

    #[Computed]
    public function secondaryCategories()
    {
        return SecondaryCategory::with('primaryCategory')->orderBy('name')->get();
    }

    public function with(): array
    {
        return [
            'secondaryCategories' => $this->secondaryCategories,
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Create Item</flux:heading>
        <flux:text class="mt-2">Add a new item to the catalog.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-4">
        <flux:select wire:model="secondary_category_id" label="Secondary Category" placeholder="Select a category" required>
            <option value="">Select a category</option>
            @foreach($this->secondaryCategories->groupBy('primaryCategory.name') as $primaryName => $secondaryGroup)
                <optgroup label="{{ $primaryName }}">
                    @foreach($secondaryGroup as $sCat)
                        <option value="{{ $sCat->id }}">{{ $sCat->name }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </flux:select>
        <flux:input wire:model="name" label="Item Name" placeholder="Enter item name" required />
        <flux:input wire:model="code" label="Item Code" placeholder="Enter unique item code" required />
        <flux:input wire:model="unit" label="Unit of Measure" placeholder="e.g., piece, box, ream, kilogram" required />
        
        <div class="flex gap-2 pt-4 border-t border-stone-200 dark:border-stone-700">
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="cancel">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Create Item</span>
                <span wire:loading>Creating...</span>
            </flux:button>
        </div>
    </form>
</div> 