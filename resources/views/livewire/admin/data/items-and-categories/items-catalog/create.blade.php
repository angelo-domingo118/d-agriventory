<?php

use App\Models\ItemsCatalog;
use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Log;

new #[Layout('components.layouts.app')] class extends Component {
    public string $name = '';
    public string $code = '';
    public string $unit = '';
    public ?int $secondary_category_id = null;

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('items_catalog', 'name')->where(fn ($query) => $query->whereNull('deleted_at'))],
            'code' => ['required', 'string', 'max:50', Rule::unique('items_catalog', 'code')->where(fn ($query) => $query->whereNull('deleted_at'))],
            'unit' => ['required', 'string', 'max:50'],
            'secondary_category_id' => ['required', 'integer', Rule::exists('secondary_categories', 'id')],
        ]);

        try {
            ItemsCatalog::create($validated);
            session()->flash('success', 'Item created successfully.');
            $this->redirectRoute('admin.data.items-and-categories', ['currentTab' => 'items']);
        } catch (\Exception $e) {
            Log::error('Error creating item: ' . $e->getMessage());
            session()->flash('error', 'There was an error creating the item. Please try again.');
        }
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

<div>
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Create Catalog Item
        </h1>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            Add a new item to the master catalog.
        </p>
    </div>

    <form wire:submit.prevent="save" class="mt-8">
        <div class="max-w-2xl">
            <div class="space-y-6">
                <flux:input wire:model="name" label="Item Name" required />
                <flux:input wire:model="code" label="Item Code" required />
                <flux:input wire:model="unit" label="Unit of Measure" required />
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

            <div class="mt-8 flex justify-end gap-x-4">
                <a href="{{ route('admin.data.items-and-categories', ['currentTab' => 'items']) }}" class="text-sm text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100">Cancel</a>
                <flux:button type="submit" variant="primary">Create Item</flux:button>
            </div>
        </div>
    </form>
</div> 