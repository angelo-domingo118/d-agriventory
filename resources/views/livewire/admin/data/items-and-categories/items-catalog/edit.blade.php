<?php

use App\Models\ItemsCatalog;
use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Log;

new #[Layout('components.layouts.app')] class extends Component {
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

    #[Computed]
    public function primaryCategories(): Collection
    {
        return PrimaryCategory::orderBy('name')->get();
    }

    #[Computed]
    public function secondaryCategories(): Collection
    {
        return SecondaryCategory::orderBy('name')->get();
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
            $this->redirectRoute('admin.data.items-and-categories', ['currentTab' => 'items-catalog']);
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
            $this->redirectRoute('admin.data.items-and-categories', ['currentTab' => 'items-catalog']);
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
            'primaryCategories' => $this->primaryCategories,
            'secondaryCategories' => $this->secondaryCategories,
        ];
    }
}; ?>

<div>
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Edit Catalog Item
        </h1>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            Update the details for this catalog item.
        </p>
    </div>

    <div class="mb-4 flex items-center gap-x-4">
        <a href="{{ route('admin.data.items-and-categories', ['currentTab' => 'items-catalog']) }}"
            class="flex items-center gap-x-2 text-sm text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100">
            <x-flux::icon.arrow-left class="h-4 w-4" />
            Back
        </a>
    </div>

    <form wire:submit.prevent="save" class="mt-8">
        <div class="max-w-4xl">
            <div class="space-y-6 rounded-lg border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <h3 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Item Details</h3>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input wire:model="name" label="Item Name" required />
                    <flux:input wire:model="code" label="Item Code" required />
                    <div class="sm:col-span-2">
                        <flux:textarea wire:model="description" label="Description" />
                    </div>
                    <flux:select wire:model="secondary_category_id" label="Secondary Category" required>
                        <option value="">Select a category</option>
                        @foreach($this->secondaryCategories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-x-4">
                <a href="{{ route('admin.data.items-and-categories', ['currentTab' => 'items']) }}" class="text-sm text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100">Cancel</a>
                <flux:button type="submit" variant="primary">Save Changes</flux:button>
            </div>
        </div>
    </form>
</div> 