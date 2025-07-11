<?php

use App\Models\ItemsCatalog;
use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

new #[Layout('components.layouts.app')] class extends Component {
    public ItemsCatalog $item;
    public string $name;
    public string $code;
    public string $unit;
    public ?int $secondary_category_id;
    public string $description;
    public string $previousView = 'tree';

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
        
        $this->previousView = request()->query('view', 'tree');
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
            'name' => ['required', 'string', 'max:255', Rule::unique('items_catalog', 'name')->ignore($this->item->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('items_catalog', 'code')->ignore($this->item->id)],
            'unit' => ['required', 'string', 'max:50'],
            'secondary_category_id' => ['required', 'integer', Rule::exists('secondary_categories', 'id')],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->item->update($validated);
            session()->flash('success', 'Item updated successfully.');
            $this->redirect(route('admin.data.items-and-categories', ['currentTab' => 'items', 'view' => $this->previousView]), navigate: true);
        } catch (\Exception $e) {
            Log::error('Error updating item: ' . $e->getMessage());
            session()->flash('error', 'There was an error updating the item. Please try again.');
        }
    }

    public function delete(): void
    {
        try {
            $isUsed = $this->item->specifications()
                ->where(function ($query) {
                    $query->has('contractItems')
                          ->orHas('consumableItems');
                })->exists();

            if ($isUsed) {
                session()->flash('error', 'This item cannot be deleted because it is associated with other records.');
                return;
            }

            $this->item->delete();
            session()->flash('success', 'Item deleted successfully.');
            $this->redirect(route('admin.data.items-and-categories', ['currentTab' => 'items', 'view' => $this->previousView]), navigate: true);
        } catch (\Exception $e) {
            Log::error('Error deleting item: ' . $e->getMessage());
            $errorMessage = 'There was an error deleting the item. It might be in use in contracts or inventory records.';
            if (config('app.debug')) {
                $errorMessage .= ' ' . $e->getMessage();
            }
            session()->flash('error', $errorMessage);
        }
    }

    public function with(): array
    {
        return [
            'secondaryCategories' => $this->secondaryCategories,
        ];
    }
}; ?>

<form wire:submit="save">
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                    Edit Catalog Item
                </h1>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Update the details of this catalog item.
                </p>
            </div>
            <div class="flex items-center gap-x-4">
                <flux:button type="button" variant="danger" wire:click="delete" wire:confirm="Are you sure you want to delete this item? This action cannot be undone.">
                    Delete
                </flux:button>
                <flux:button :href="route('admin.data.items-and-categories', ['currentTab' => 'items', 'view' => $this->previousView])" variant="ghost" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Save Changes
                </flux:button>
            </div>
        </div>
    </div>

    <div class="mt-8">
        <div class="grid grid-cols-1 gap-8">
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">Item Details</h3>
                </div>
                <div class="p-6">
                    <div class="max-w-2xl">
                        <div class="space-y-6">
                            <flux:input wire:model="name" label="Item Name" required />
                            <flux:input wire:model="code" label="Item Code" required />
                            <flux:input wire:model="unit" label="Unit of Measure" required />
                             <flux:textarea wire:model="description" label="Description" />
                            <flux:select wire:model="secondary_category_id" label="Secondary Category" required>
                                <option value="">Select a category</option>
                                @foreach($this->secondaryCategories->groupBy('primaryCategory.name') as $primaryName => $secondaryGroup)
                                    <optgroup label="{{ $primaryName }}">
                                        @foreach($secondaryGroup as $sCat)
                                            <option value="{{ $sCat->id }}">{{ $sCat->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form> 