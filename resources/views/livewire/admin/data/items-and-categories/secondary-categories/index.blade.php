<?php

use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public ?SecondaryCategory $editing = null;
    public bool $showCreateModal = false;

    public function mount()
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage inventory data.');
        }
    }

    public function getCategoriesProperty()
    {
        return SecondaryCategory::with('primaryCategory')->orderBy('name')->paginate(10);
    }

    public function getPrimaryCategoriesProperty()
    {
        return PrimaryCategory::orderBy('name')->get();
    }

    public function newCategory(): void
    {
        $this->editing = new SecondaryCategory();
        $this->showCreateModal = true;
    }

    public function edit(SecondaryCategory $category): void
    {
        $this->editing = $category;
        $this->showCreateModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'editing.name' => ['required', 'string', 'max:255', Rule::unique('secondary_categories', 'name')->ignore($this->editing->id)],
            'editing.code' => ['required', 'string', 'max:50', Rule::unique('secondary_categories', 'code')->ignore($this->editing->id)],
            'editing.primary_category_id' => ['required', 'integer', Rule::exists('primary_categories', 'id')],
        ]);

        $this->editing->save();

        $this->showCreateModal = false;
        $this->dispatch('category-saved');
        session()->flash('success', 'Secondary category saved successfully.');
    }

    public function with(): array
    {
        return [
            'categories' => $this->categories,
            'primaryCategories' => $this->primaryCategories,
        ];
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-stone-700 dark:text-stone-200">Secondary Categories</h2>
        <flux:button wire:click="newCategory" variant="primary">New Category</flux:button>
    </div>

    <div class="mt-4 overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
        <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
            <thead class="bg-stone-50 dark:bg-stone-800">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Code</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Primary Category</th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Edit</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                @forelse($categories as $category)
                    <tr wire:key="category-{{ $category->id }}">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-stone-900 dark:text-stone-100">{{ $category->name }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">{{ $category->code }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">{{ $category->primaryCategory?->name }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <flux:button wire:click="edit({{ $category->id }})" variant="ghost" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-200">Edit</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">
                            No secondary categories found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $categories->links() }}
    </div>

    <!-- Create/Edit Modal -->
    @if($editing)
        <flux:modal :show="$showCreateModal" max-width="lg" @close="$set('showCreateModal', false)">
            <form wire:submit.prevent="save">
                <x-slot:title>
                    {{ $editing->exists ? 'Edit' : 'Create' }} Secondary Category
                </x-slot:title>

                <div class="space-y-4 p-6">
                    <flux:select wire:model="editing.primary_category_id" label="Primary Category" required>
                        <option value="">Select a primary category</option>
                        @foreach($primaryCategories as $pCat)
                            <option value="{{ $pCat->id }}">{{ $pCat->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="editing.name" label="Name" required />
                    <flux:input wire:model="editing.code" label="Code" required />
                </div>

                <x-slot:footer>
                    <div class="flex justify-end gap-x-4">
                        <flux:button variant="ghost" @click="$set('showCreateModal', false)">Cancel</flux:button>
                        <flux:button type="submit" variant="primary">Save</flux:button>
                    </div>
                </x-slot:footer>
            </form>
        </flux:modal>
    @endif
</div> 