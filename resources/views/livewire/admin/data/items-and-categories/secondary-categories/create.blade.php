<?php

use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $name = '';
    public string $code = '';
    public ?int $primary_category_id = null;
    public string $description = '';
    public string $previousView = 'tree';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        
        $this->primary_category_id = request()->query('primary_category_id');
        $this->previousView = request()->query('view', 'tree');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('secondary_categories', 'name')],
            'code' => ['required', 'string', 'max:50', Rule::unique('secondary_categories', 'code')],
            'primary_category_id' => ['required', 'integer', Rule::exists('primary_categories', 'id')],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        SecondaryCategory::create($validated);

        session()->flash('success', 'Secondary category created successfully.');
        $this->redirect(route('admin.data.items-and-categories', ['currentTab' => 'secondary', 'view' => $this->previousView]), navigate: true);
    }

    #[Computed]
    public function primaryCategories()
    {
        return PrimaryCategory::orderBy('name')->get();
    }
}; ?>

<form wire:submit="save">
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                    Create New Secondary Category
                </h1>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Create a new secondary category and assign it to a primary category.
                </p>
            </div>
            <div class="flex items-center gap-x-4">
                <x-action-message class="me-3" on="secondary-category-created">
                    {{ __('Secondary category created successfully.') }}
                </x-action-message>
                <flux:button :href="route('admin.data.items-and-categories', ['currentTab' => 'secondary', 'view' => $this->previousView])" variant="ghost" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Save Category
                </flux:button>
            </div>
        </div>
    </div>

    <div class="mt-8">
        <div class="grid grid-cols-1 gap-8">
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">Category Details</h3>
                </div>
                <div class="p-6">
                    <div class="max-w-2xl">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <flux:input wire:model="name" label="Category Name" required />
                            <flux:input wire:model="code" label="Category Code" required />
                            <div class="sm:col-span-2">
                                <flux:select wire:model="primary_category_id" label="Primary Category" required>
                                    <option value="">Select a primary category</option>
                                    @foreach($this->primaryCategories as $pCat)
                                        <option value="{{ $pCat->id }}">{{ $pCat->name }}</option>
                                    @endforeach
                                </flux:select>
                            </div>
                            <div class="sm:col-span-2">
                                <flux:textarea wire:model="description" label="Description" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form> 