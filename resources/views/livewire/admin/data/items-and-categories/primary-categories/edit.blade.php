<?php

use App\Models\PrimaryCategory;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public PrimaryCategory $category;
    public string $name;
    public string $code;
    public string $description;
    public string $previousView = 'tree';

    public function mount(PrimaryCategory $category): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        $this->category = $category;
        $this->name = $category->name;
        $this->code = $category->code;
        $this->description = $category->description ?? '';
        
        $this->previousView = request()->query('view', 'tree');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('primary_categories', 'name')->ignore($this->category->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('primary_categories', 'code')->ignore($this->category->id)],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $this->category->update($validated);

        session()->flash('success', 'Primary category updated successfully.');
        $this->redirect(route('admin.data.items-and-categories', ['currentTab' => 'primary', 'view' => $this->previousView]), navigate: true);
    }

    public function delete(): void
    {
        if ($this->category->secondaryCategories()->exists()) {
            session()->flash('error', 'Cannot delete a primary category that has secondary categories linked to it.');
            return;
        }

        $this->category->delete();

        session()->flash('success', 'Primary category deleted successfully.');
        $this->redirect(route('admin.data.items-and-categories', ['currentTab' => 'primary', 'view' => $this->previousView]), navigate: true);
    }
}; ?>

<form wire:submit="save">
    <!-- Breadcrumbs -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <flux:breadcrumbs class="text-2xl font-semibold">
                <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Data</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('admin.data.items-and-categories', ['currentTab' => 'primary'])" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Items & Categories</flux:breadcrumbs.item>
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Edit Primary Category</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
    </div>

    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                    Edit Primary Category
                </h1>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Update the details for this primary category.
                </p>
            </div>
            <div class="flex items-center gap-x-4">
                <flux:button type="button" variant="danger" wire:click="delete" wire:confirm="Are you sure you want to delete this category? This action cannot be undone.">
                    Delete
                </flux:button>
                <flux:button :href="route('admin.data.items-and-categories', ['currentTab' => 'primary', 'view' => $previousView])" variant="ghost" wire:navigate>
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
                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">Category Details</h3>
                </div>
                <div class="p-6">
                    <div class="max-w-2xl">
                        <div class="space-y-6">
                           <flux:input wire:model="name" label="Category Name" required />
                           <flux:input wire:model="code" label="Category Code" required />
                           <flux:textarea wire:model="description" label="Description" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form> 