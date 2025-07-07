<?php

use App\Models\ItemsCatalog;
use App\Models\SecondaryCategory;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public ?ItemsCatalog $editing = null;
    public bool $showCreateModal = false;
    public string $search = '';

    public function mount()
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage inventory data.');
        }
    }

    public function getItemsProperty()
    {
        return ItemsCatalog::with('secondaryCategory.primaryCategory')
            ->when($this->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('secondaryCategory', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->orderBy('name')
            ->paginate(10);
    }

    public function getSecondaryCategoriesProperty()
    {
        return SecondaryCategory::with('primaryCategory')->orderBy('name')->get();
    }

    public function newItem(): void
    {
        $this->editing = new ItemsCatalog();
        $this->showCreateModal = true;
    }

    public function edit(ItemsCatalog $item): void
    {
        $this->editing = $item;
        $this->showCreateModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'editing.name' => ['required', 'string', 'max:255', Rule::unique('items_catalog', 'name')->ignore($this->editing->id)],
            'editing.code' => ['required', 'string', 'max:50', Rule::unique('items_catalog', 'code')->ignore($this->editing->id)],
            'editing.unit' => ['required', 'string', 'max:50'],
            'editing.secondary_category_id' => ['required', 'integer', Rule::exists('secondary_categories', 'id')],
        ]);

        try {
            $this->editing->save();

            $this->showCreateModal = false;
            $this->dispatch('item-saved');
            session()->flash('success', 'Item saved successfully.');
        } catch (\Exception $e) {
            Log::error('Error saving item: ' . $e->getMessage());
            session()->flash('error', 'There was an error saving the item. Please try again.');
        }
    }

    public function with(): array
    {
        return [
            'items' => $this->items,
            'secondaryCategories' => $this->secondaryCategories,
        ];
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-stone-700 dark:text-stone-200">Items Catalog</h2>
        <div class="flex items-center gap-x-4">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search items..." />
            <flux:button wire:click="newItem" variant="primary">New Item</flux:button>
        </div>
    </div>

    <div class="mt-4 overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
        <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
            <thead class="bg-stone-50 dark:bg-stone-800">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Code</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Unit</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Category</th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Edit</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                @forelse($items as $item)
                    <tr wire:key="item-{{ $item->id }}">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-stone-900 dark:text-stone-100">{{ $item->name }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">{{ $item->code }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">{{ $item->unit }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">
                            {{ $item->secondaryCategory?->primaryCategory?->name }} &rarr; {{ $item->secondaryCategory?->name }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <flux:button wire:click="edit({{ $item->id }})" variant="ghost" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-200">Edit</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">
                            No items found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>

    <!-- Create/Edit Modal -->
    @if($editing)
        <flux:modal :show="$showCreateModal" max-width="lg" @close="$set('showCreateModal', false)">
            <form wire:submit.prevent="save">
                <x-slot:title>
                    {{ $editing->exists ? 'Edit' : 'Create' }} Item
                </x-slot:title>

                <div class="space-y-4 p-6">
                    <flux:select wire:model="editing.secondary_category_id" label="Secondary Category" required>
                        <option value="">Select a category</option>
                        @foreach($secondaryCategories->groupBy('primaryCategory.name') as $primaryName => $secondaryGroup)
                            <optgroup label="{{ $primaryName }}">
                                @foreach($secondaryGroup as $sCat)
                                    <option value="{{ $sCat->id }}">{{ $sCat->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="editing.name" label="Item Name" required />
                    <flux:input wire:model="editing.code" label="Item Code" required />
                    <flux:input wire:model="editing.unit" label="Unit of Measure" placeholder="e.g., piece, box, ream" required />
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