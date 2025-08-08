<?php

use App\Models\ItemsCatalog;
use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use App\Services\ToastService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Flux\Flux;

new class extends Component {
    public ItemsCatalog $item;
    public string $name;
    public string $code;
    public string $unit;
    public ?int $secondary_category_id;
    public string $description;

    public function mount(ItemsCatalog $item): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        $this->item = $item;
        $this->name = $item->name;
        $this->code = $item->code;
        $this->unit = $item->unit;
        $this->secondary_category_id = $item->secondary_category_id;
        $this->description = $item->description ?? '';
    }

    public function confirmDelete(): void
    {        
        // Show the delete confirmation modal
        Flux::modal('delete-item-confirmation')->show();
    }

    public function cancelDelete(): void
    {
        // Close the delete confirmation modal
        Flux::modal('delete-item-confirmation')->close();
    }

    #[Computed]
    public function secondaryCategories()
    {
        return Cache::remember('secondary-categories-with-primary', now()->addHour(), function () {
            return SecondaryCategory::with('primaryCategory')->orderBy('name')->get();
        });
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('items_catalog', 'name')->ignore($this->item->id)->whereNull('deleted_at')],
            'code' => ['required', 'string', 'max:50', Rule::unique('items_catalog', 'code')->ignore($this->item->id)->whereNull('deleted_at')],
            'unit' => ['required', 'string', 'max:50'],
            'secondary_category_id' => ['required', 'integer', Rule::exists('secondary_categories', 'id')],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->item->update($validated);
            
            // Show success toast
            ToastService::updated($this, 'Item');
            
            // Close the modal and refresh the parent component
            $this->dispatch('item-updated');
            Flux::modal('edit-item')->close();
        } catch (\Exception $e) {
            Log::error('Error updating item: ' . $e->getMessage());
            ToastService::error($this, 'There was an error updating the item. Please try again.');
        }
    }

    public function delete(): void
    {
        try {
            // Check if deletion should be blocked
            if ($this->item->isDeletionBlocked()) {
                ToastService::error($this, 'Cannot delete this item because it has active inventory records. Consider archiving instead.');
                Flux::modal('delete-item-confirmation')->close();
                return;
            }

            // Get impact assessment for detailed success message
            $impact = $this->item->getDeletionImpact();
            
            if (!$this->item->canBeDeletedSafely()) {
                // Use force delete to remove all associations
                $this->item->forceDeleteWithAssociations();
                
                // Show detailed success message about what was deleted
                $message = 'Item deleted successfully';
                if ($impact['has_associated_data']) {
                    $summary = $this->item->getDeletionSummary();
                    if ($summary) {
                        $message .= ' along with ' . $summary;
                    }
                }
                
                ToastService::success($this, $message . '.');
            } else {
                // Safe to delete normally
                $this->item->delete();
                ToastService::deleted($this, 'Item');
            }

            // Close both modals and refresh the parent component
            Flux::modal('delete-item-confirmation')->close();
            Flux::modal('edit-item')->close();
            $this->dispatch('item-deleted');
            
        } catch (\Exception $e) {
            Log::error('Error deleting item: ' . $e->getMessage());
            $errorMessage = 'An error occurred while deleting the item. Please try again.';
            if (config('app.debug')) {
                $errorMessage .= ' Error: ' . $e->getMessage();
            }
            ToastService::error($this, $errorMessage);
            Flux::modal('delete-item-confirmation')->close();
        }
    }

    public function cancel(): void
    {
        Flux::modal('edit-item')->close();
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
            'secondaryCategories' => $this->secondaryCategories,
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Edit Item</flux:heading>
        <flux:text class="mt-2">Update the details of this catalog item.</flux:text>
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
        <flux:textarea wire:model="description" label="Description" placeholder="Optional description for this item" rows="3" />
        
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