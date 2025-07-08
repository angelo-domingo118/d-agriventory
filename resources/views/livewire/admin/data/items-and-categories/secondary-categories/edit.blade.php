<?php

use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public SecondaryCategory $category;
    public string $name;
    public string $code;
    public ?int $primary_category_id;
    public string $description;

    public function mount(SecondaryCategory $category): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        $this->category = $category;
        $this->name = $category->name;
        $this->code = $category->code;
        $this->primary_category_id = $category->primary_category_id;
        $this->description = $category->description ?? '';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('secondary_categories', 'name')->ignore($this->category->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('secondary_categories', 'code')->ignore($this->category->id)],
            'primary_category_id' => ['required', 'integer', Rule::exists('primary_categories', 'id')],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $this->category->update($validated);

        session()->flash('success', 'Secondary category updated successfully.');
        $this->redirectRoute('admin.data.items-and-categories', ['currentTab' => 'secondary-categories']);
    }

    public function delete(): void
    {
        DB::transaction(function () {
            if ($this->category->items()->exists()) {
                session()->flash('error', 'Cannot delete a category that has items linked to it.');
                return;
            }

            $this->category->delete();
            session()->flash('success', 'Secondary category deleted successfully.');
            $this->redirectRoute('admin.data.items-and-categories', ['currentTab' => 'secondary-categories']);
        });
    }

    #[Computed]
    public function primaryCategories()
    {
        return PrimaryCategory::orderBy('name')->get();
    }
}; ?>

<div>
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Edit Secondary Category
        </h1>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            Update the details for this secondary category.
        </p>
    </div>

    <div class="mb-4 flex items-center gap-x-4">
        <a href="{{ route('admin.data.items-and-categories', ['currentTab' => 'secondary-categories']) }}"
            class="flex items-center gap-x-2 text-sm text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100">
            <x-flux::icon.arrow-left class="h-4 w-4" />
            Back
        </a>
    </div>

    <form wire:submit.prevent="save" class="mt-8">
        <div class="max-w-4xl">
            <div class="space-y-6 rounded-lg border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <h3 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Category Details</h3>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input wire:model="name" label="Category Name" required />
                    <flux:input wire:model="code" label="Category Code" required />
                    <flux:select wire:model="primary_category_id" label="Primary Category" required>
                        <option value="">Select a primary category</option>
                        @foreach($this->primaryCategories as $pCat)
                            <option value="{{ $pCat->id }}">{{ $pCat->name }}</option>
                        @endforeach
                    </flux:select>
                    <div class="sm:col-span-2">
                        <flux:textarea wire:model="description" label="Description" />
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-x-4">
                <a href="{{ route('admin.data.items-and-categories', ['currentTab' => 'secondary-categories']) }}" class="text-sm text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100">Cancel</a>
                <flux:button type="submit" variant="primary">Save Changes</flux:button>
            </div>
        </div>
    </form>
</div> 