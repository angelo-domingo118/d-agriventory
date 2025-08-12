<?php

use App\Models\ItemSpecification;
use App\Models\ItemsCatalog;
use App\Services\ToastService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    public ?int $item_catalog_id = null;
    public string $brand = '';
    public string $model = '';
    public string $detailed_specifications = '';

    public function mount(?int $itemCatalogId = null): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }

        if ($itemCatalogId) {
            $this->item_catalog_id = $itemCatalogId;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'item_catalog_id' => ['required', 'integer', Rule::exists('items_catalog', 'id')],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'detailed_specifications' => ['nullable', 'string', 'max:2000'],
        ]);

        // Ensure at least one identifying field is provided
        if (empty($validated['brand']) && empty($validated['model']) && empty($validated['detailed_specifications'])) {
            $this->addError('brand', 'At least one of brand, model, or detailed specifications must be provided.');
            return;
        }

        ItemSpecification::create($validated);

        // Show success toast
        ToastService::created($this, 'Item specification');

        // Close the modal and refresh the parent component
        $this->dispatch('specification-created');
        Flux::modal('create-specification')->close();
        
        // Reset form
        $this->reset(['item_catalog_id', 'brand', 'model', 'detailed_specifications']);
    }

    public function cancel(): void
    {
        Flux::modal('create-specification')->close();
        $this->reset(['item_catalog_id', 'brand', 'model', 'detailed_specifications']);
    }

    #[On('item-created')]
    #[On('item-updated')]
    #[On('item-deleted')]
    public function refreshCatalogItems(): void
    {
        unset($this->catalogItems);
    }

    #[Computed]
    public function catalogItems()
    {
        return ItemsCatalog::query()
            ->with('secondaryCategory.primaryCategory')
            ->orderBy('name')
            ->get();
    }

    public function with(): array
    {
        return [
            'catalogItems' => $this->catalogItems,
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Create Item Specification</flux:heading>
        <flux:text class="mt-2">Add a new specification variant for a catalog item.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-4">
        <flux:select wire:model="item_catalog_id" label="Catalog Item" placeholder="Select a catalog item" required>
            @foreach($this->catalogItems->groupBy('secondaryCategory.primaryCategory.name') as $primaryName => $catalogGroup)
                <optgroup label="📁 {{ $primaryName }}">
                    @foreach($catalogGroup->groupBy('secondaryCategory.name') as $secondaryName => $items)
                        <option disabled style="font-weight: 600; color: #6b7280; padding-left: 8px;">
                            └─ {{ $secondaryName }}
                        </option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" style="padding-left: 24px;">
                                &nbsp;&nbsp;&nbsp;• {{ $item->name }}
                            </option>
                        @endforeach
                        @if(!$loop->last)
                            <option disabled style="border-top: 1px solid #e5e7eb; margin: 2px 0;">&nbsp;</option>
                        @endif
                    @endforeach
                </optgroup>
            @endforeach
        </flux:select>
        
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <flux:input wire:model="brand" label="Brand" placeholder="e.g., Dell, HP, Canon" />
            <flux:input wire:model="model" label="Model" placeholder="e.g., OptiPlex 7090, LaserJet Pro" />
        </div>
        
        <flux:textarea 
            wire:model="detailed_specifications" 
            label="Detailed Specifications" 
            placeholder="Enter detailed technical specifications, features, or characteristics"
            rows="4"
        />
        
        <div class="flex gap-2 pt-4 border-t border-stone-200 dark:border-stone-700">
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="cancel">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Create Specification</span>
                <span wire:loading>Creating...</span>
            </flux:button>
        </div>
    </form>
</div> 
