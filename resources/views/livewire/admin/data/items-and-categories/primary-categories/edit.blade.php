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

    public function mount(PrimaryCategory $category): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        $this->category = $category;
        $this->name = $category->name;
        $this->code = $category->code;
        $this->description = $category->description ?? '';
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
        $this->redirectRoute('admin.data.items-and-categories', ['currentTab' => 'primary-categories']);
    }
    
    public function delete(): void
    {
        if ($this->category->secondaryCategories()->exists()) {
            session()->flash('error', 'Cannot delete a primary category that has secondary categories linked to it.');
            return;
        }

        $this->category->delete();
        session()->flash('success', 'Primary category deleted successfully.');
        $this->redirectRoute('admin.data.items-and-categories', ['currentTab' => 'primary-categories']);
    }
}; ?>

<div>
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Edit Primary Category
        </h1>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            Update the details for this primary category.
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
                    <flux:input wire:model="name" label="Category Name" required />
                    <flux:input wire:model="code" label="Category Code" required />
                    <flux:textarea wire:model="description" label="Description" />
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-x-4">
                <a href="{{ route('admin.data.items-and-categories', ['currentTab' => 'primary-categories']) }}" class="text-sm text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100">Cancel</a>
                <flux:button type="submit" variant="primary">Save Changes</flux:button>
            </div>
        </div>
    </form>
</div> 