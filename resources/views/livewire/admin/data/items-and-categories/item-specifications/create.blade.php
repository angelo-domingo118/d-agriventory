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
        <flux:select wire:model="item_catalog_id" label="Catalog Item" placeholder="Select a catalog item" required 
                     class="dark:[&>option]:bg-stone-800 dark:[&>option]:text-stone-100 dark:[&>optgroup]:bg-stone-800 dark:[&>optgroup]:text-stone-100"
                     style="color-scheme: dark;">
            @foreach($this->catalogItems->groupBy('secondaryCategory.primaryCategory.name') as $primaryName => $catalogGroup)
                <optgroup label="{{ $primaryName }}" class="dark:bg-stone-800 dark:text-stone-100">
                    @foreach($catalogGroup->groupBy('secondaryCategory.name') as $secondaryName => $items)
                        <optgroup label="— {{ $secondaryName }}" class="dark:bg-stone-800 dark:text-stone-100 ml-4">
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" class="dark:bg-stone-800 dark:text-stone-100 ml-8">{{ $item->name }}</option>
                            @endforeach
                        </optgroup>
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
        
        <div class="rounded-md bg-blue-50 p-4 dark:bg-blue-900/20">
            <div class="flex">
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        Information
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <p>At least one field (brand, model, or detailed specifications) must be provided to create a meaningful specification.</p>
                    </div>
                </div>
            </div>
        </div>
        
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
