<?php

use App\Models\ItemSpecification;
use App\Models\ItemsCatalog;
use App\Services\ToastService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Flux\Flux;

new class extends Component {
    public ItemSpecification $specification;
    public ?int $item_catalog_id;
    public string $brand;
    public string $model;
    public string $detailed_specifications;

    public function mount(ItemSpecification $specification): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        
        $this->specification = $specification;
        $this->item_catalog_id = $specification->item_catalog_id;
        $this->brand = $specification->brand ?? '';
        $this->model = $specification->model ?? '';
        $this->detailed_specifications = $specification->detailed_specifications ?? '';
    }

    public function confirmDelete(): void
    {        
        // Show the delete confirmation modal
        Flux::modal('delete-specification-confirmation')->show();
    }

    public function cancelDelete(): void
    {
        // Close the delete confirmation modal
        Flux::modal('delete-specification-confirmation')->close();
    }

    #[Computed]
    public function catalogItems()
    {
        return Cache::remember('catalog-items-with-categories', now()->addHour(), function () {
            return ItemsCatalog::with('secondaryCategory.primaryCategory')->orderBy('name')->get();
        });
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

        try {
            $this->specification->update($validated);
            
            // Show success toast
            ToastService::updated($this, 'Item specification');
            
            // Close the modal and refresh the parent component
            $this->dispatch('specification-updated');
            Flux::modal('edit-specification')->close();
        } catch (\Exception $e) {
            Log::error('Error updating specification: ' . $e->getMessage());
            ToastService::error($this, 'There was an error updating the specification. Please try again.');
        }
    }

    public function delete(): void
    {
        try {
            // Check if deletion should be blocked
            if ($this->specification->isDeletionBlocked()) {
                ToastService::error($this, 'Cannot delete this specification because it has active inventory records. Consider archiving instead.');
                Flux::modal('delete-specification-confirmation')->close();
                return;
            }

            // Get impact assessment for detailed success message
            $impact = $this->specification->getDeletionImpact();
            
            if (!$this->specification->canBeDeletedSafely()) {
                // Use force delete to remove all associations
                $this->specification->forceDeleteWithAssociations();
                
                // Show detailed success message about what was deleted
                $message = 'Item specification deleted successfully';
                if ($impact['has_associated_data']) {
                    $summary = $this->specification->getDeletionSummary();
                    if ($summary) {
                        $message .= ' along with ' . $summary;
                    }
                }
                
                ToastService::success($this, $message . '.');
            } else {
                // Safe to delete normally
                $this->specification->delete();
                ToastService::deleted($this, 'Item specification');
            }

            // Close both modals and refresh the parent component
            Flux::modal('delete-specification-confirmation')->close();
            Flux::modal('edit-specification')->close();
            $this->dispatch('specification-deleted');
            
        } catch (\Exception $e) {
            Log::error('Error deleting specification: ' . $e->getMessage());
            $errorMessage = 'An error occurred while deleting the specification. Please try again.';
            if (config('app.debug')) {
                $errorMessage .= ' Error: ' . $e->getMessage();
            }
            ToastService::error($this, $errorMessage);
            Flux::modal('delete-specification-confirmation')->close();
        }
    }

    public function cancel(): void
    {
        Flux::modal('edit-specification')->close();
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

    public function with(): array
    {
        return [
            'catalogItems' => $this->catalogItems,
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Edit Item Specification</flux:heading>
        <flux:text class="mt-2">Update the details for this item specification.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-4">
        <flux:select wire:model="item_catalog_id" label="Catalog Item" placeholder="Select a catalog item" required>
            <option value="">Select a catalog item</option>
            @foreach($this->catalogItems->groupBy('secondaryCategory.primaryCategory.name') as $primaryName => $catalogGroup)
                <optgroup label="{{ $primaryName }}">
                    @foreach($catalogGroup->groupBy('secondaryCategory.name') as $secondaryName => $items)
                        <optgroup label="— {{ $secondaryName }}">
                            @foreach($items as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
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
