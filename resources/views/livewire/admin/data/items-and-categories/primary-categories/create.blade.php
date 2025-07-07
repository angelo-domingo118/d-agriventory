<?php

use App\Models\PrimaryCategory;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public PrimaryCategory $category;

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        $this->category = new PrimaryCategory();
    }

    public function save(): void
    {
        $this->validate([
            'category.name' => ['required', 'string', 'max:255', Rule::unique('primary_categories', 'name')],
            'category.description' => ['nullable', 'string', 'max:500'],
        ]);

        $this->category->save();

        session()->flash('success', 'Primary category created successfully.');
        $this->redirectRoute('admin.data.items-and-categories', ['currentTab' => 'primary-categories']);
    }
}; ?>

<div>
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Create Primary Category
        </h1>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            Create a new primary category for items.
        </p>
    </div>

    <div class="mb-4 flex items-center gap-x-4">
        <a href="{{ route('admin.data.items-and-categories', ['currentTab' => 'primary-categories']) }}"
            class="flex items-center gap-x-2 text-sm text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100">
            <x-flux::icon.arrow-left class="h-4 w-4" />
            Back
        </a>
    </div>

    <form wire:submit.prevent="save" class="mt-8">
        <div class="max-w-4xl">
            <div class="space-y-6 rounded-lg border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <h3 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Category Details</h3>
                <div class="grid grid-cols-1 gap-6">
                    <flux:input wire:model="category.name" label="Category Name" required />
                    <flux:textarea wire:model="category.description" label="Description" />
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-x-4">
                <a href="{{ route('admin.data.items-and-categories', ['currentTab' => 'primary-categories']) }}" class="text-sm text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100">Cancel</a>
                <flux:button type="submit" variant="primary">Create Category</flux:button>
            </div>
        </div>
    </form>
</div> 