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
    public string $previousView = 'tree';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        
        $this->previousView = request()->query('view', 'tree');
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
            $this->redirect(route('admin.data.items-and-categories', ['currentTab' => 'items', 'view' => $this->previousView]), navigate: true);
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

<form wire:submit="save">
    <!-- Breadcrumbs -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <flux:breadcrumbs class="text-2xl font-semibold">
                <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Data</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('admin.data.items-and-categories', ['currentTab' => 'items'])" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Items & Categories</flux:breadcrumbs.item>
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Create Item</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
    </div>

    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                    Create New Catalog Item
                </h1>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Add a new item to the master catalog.
                </p>
            </div>
            <div class="flex items-center gap-x-4">
                <x-action-message class="me-3" on="item-created">
                    {{ __('Item created successfully.') }}
                </x-action-message>
                <flux:button :href="route('admin.data.items-and-categories', ['currentTab' => 'items', 'view' => $previousView])" variant="ghost" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Save Item
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