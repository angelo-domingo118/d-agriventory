<?php

use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public ?SecondaryCategory $editing = null;
    public bool $showCreateModal = false;
    
    // View and filter state
    public string $search = '';
    public bool $showFilters = false;
    public int $perPage = 10;

    // Filter properties
    public ?int $filterPrimaryCategory = null;

    // Sorting properties
    public string $sortColumn = 'name';
    public string $sortDirection = 'asc';

    public function mount()
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage inventory data.');
        }
    }
    
    public function sortBy(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function resetFilters()
    {
        $this->reset('filterPrimaryCategory');
    }
    
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }
    
    #[Computed]
    public function categories()
    {
        return SecondaryCategory::with('primaryCategory')
            ->when($this->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('primaryCategory', fn($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->when($this->filterPrimaryCategory, function(Builder $q) {
                $q->where('primary_category_id', $this->filterPrimaryCategory);
            })
            ->orderBy($this->sortColumn, $this->sortDirection)
            ->paginate($this->perPage);
    }

    #[Computed]
    public function primaryCategories()
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

    #[Computed]
    public function filtersActive(): bool
    {
        return $this->filterPrimaryCategory !== null;
    }

    public function with(): array
    {
        return [
            'categories' => $this->categories,
            'primaryCategories' => $this->primaryCategories,
        ];
    }
}; ?>

<div x-data="{ showFilters: @entangle('showFilters') }">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Secondary Categories
        </h1>
        <div class="flex items-center gap-x-2">
             <div x-data="{ open: false }" class="relative">
                <flux:button variant="outline" x-on:click="open = !open" class="!p-2">
                    <x-flux::icon.settings-2 class="h-5 w-5" />
                    <span class="sr-only">Toggle View Options</span>
                </flux:button>
                <div x-show="open" x-on:click.outside="open = false" x-transition class="absolute right-0 z-10 mt-2 w-72 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-stone-800 dark:ring-stone-700" style="display: none;">
                    <div class="px-3 py-2">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Items per Page</div>
                        <flux:select wire:model.live="perPage" id="perPage" class="mt-1">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </flux:select>
                    </div>
                </div>
            </div>
            <flux:button variant="outline" wire:click="$refresh" class="!p-2">
                <x-flux::icon.rotate-cw class="h-5 w-5" wire:loading.class="animate-spin" />
                <span class="sr-only">Refresh</span>
            </flux:button>
            <flux:button variant="outline" x-on:click="showFilters = !showFilters" class="!p-2 @if($this->filtersActive) bg-primary-50 text-primary-600 dark:bg-primary-900/10 dark:text-primary-400 @endif">
                <x-flux::icon.filter class="h-5 w-5" />
                <span class="sr-only">Toggle Filters</span>
            </flux:button>
            <flux:button :href="route('admin.data.items-and-categories.secondary-categories.create')" variant="primary">New Category</flux:button>
        </div>
    </div>

    <div x-show="showFilters" x-collapse class="mt-4">
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
             <div class="border-b border-stone-200 p-4 dark:border-stone-700">
                <h3 class="font-semibold text-stone-800 dark:text-stone-200">Filter Options</h3>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-4">
                    <div class="sm:col-span-4">
                        <flux:select wire:model.live="filterPrimaryCategory" label="Primary Category">
                            <option value="">Any Primary Category</option>
                            @foreach($this->primaryCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
            </div>
             @if($this->filtersActive)
                <div class="border-t border-stone-200 bg-stone-50 p-4 text-right dark:border-stone-700 dark:bg-stone-800/50">
                    <flux:button variant="ghost" wire:click="resetFilters">
                        Reset Filters
                    </flux:button>
                </div>
            @endif
        </div>
    </div>

     <div class="mt-4 flex items-center justify-between">
        <div class="text-sm text-stone-600 dark:text-stone-400">
            @if ($this->categories->total() > 0)
                <span>Showing {{ $this->categories->firstItem() }} to {{ $this->categories->lastItem() }} of <strong>{{ $this->categories->total() }}</strong> results.</span>
            @else
                <span>No results found.</span>
            @endif
        </div>
        <div class="w-full max-w-xs">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search anything..."
            >
                <x-slot:leading>
                    <x-flux::icon.search class="size-5 text-stone-400" />
                </x-slot:leading>
            </flux:input>
        </div>
    </div>

    <div class="mt-4 flow-root">
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
                <thead class="bg-stone-50 dark:bg-stone-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                            <div wire:click="sortBy('name')" class="flex cursor-pointer items-center">
                                Name
                                @if($sortColumn === 'name')
                                    @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                @else
                                    <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400" />
                                @endif
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                            <div wire:click="sortBy('code')" class="flex cursor-pointer items-center">
                                Code
                                @if($sortColumn === 'code')
                                    @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                @else
                                    <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400" />
                                @endif
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                             <div wire:click="sortBy('primary_category_id')" class="flex cursor-pointer items-center">
                                Primary Category
                                @if($sortColumn === 'primary_category_id')
                                    @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                @else
                                    <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400" />
                                @endif
                            </div>
                        </th>
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
                                <flux:button :href="route('admin.data.items-and-categories.secondary-categories.edit', $category)" variant="ghost" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-200">Edit</flux:button>
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
    </div>

    <div class="mt-4">
        {{ $categories->links() }}
    </div>

    <!-- Create/Edit Modal -->
    @if($editing)
        <flux:modal wire:model.live="showCreateModal" max-width="lg" @close="$set('showCreateModal', false)">
            <form wire:submit.prevent="save">
                <x-slot:title>
                    {{ $editing->exists ? 'Edit' : 'Create' }} Secondary Category
                </x-slot:title>

                <div class="space-y-4 p-6">
                    <flux:select wire:model="editing.primary_category_id" label="Primary Category" required>
                        <option value="">Select a primary category</option>
                        @foreach($this->primaryCategories as $pCat)
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