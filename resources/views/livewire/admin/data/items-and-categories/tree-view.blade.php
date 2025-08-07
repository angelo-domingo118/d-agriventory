<?php

use App\Models\PrimaryCategory;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component {
    public string $search = '';
    public ?int $editingPrimaryCategoryId = null;
    public ?int $editingSecondaryCategoryId = null;
    public ?int $editingItemId = null;

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function editPrimaryCategory($id): void
    {
        $this->editingPrimaryCategoryId = $id;
        $this->dispatch('open-modal', 'edit-primary-category');
    }

    public function editSecondaryCategory($id): void
    {
        $this->editingSecondaryCategoryId = $id;
        $this->dispatch('open-modal', 'edit-secondary-category');
    }

    public function editItem($id): void
    {
        $this->editingItemId = $id;
        $this->dispatch('open-modal', 'edit-item');
    }

    #[On('primary-category-created')]
    #[On('primary-category-updated')]
    #[On('secondary-category-created')]
    #[On('secondary-category-updated')]
    #[On('item-created')]
    #[On('item-updated')]
    public function refreshData(): void
    {
        // Reset edit IDs
        $this->editingPrimaryCategoryId = null;
        $this->editingSecondaryCategoryId = null;
        $this->editingItemId = null;
        
        // Force refresh of computed properties
        unset($this->categories);
        unset($this->expandableIds);
        $this->dispatch('$refresh');
    }

    #[Computed]
    public function isSearching(): bool
    {
        return filled($this->search);
    }

    #[Computed]
    public function categories(): Collection
    {
        $search = $this->search;

        // If no search term, return all categories with their children and counts
        if (blank($search)) {
            return PrimaryCategory::with([
                'secondaryCategories.items' => fn($query) => $query->withCount('specifications')->orderBy('name'),
                'secondaryCategories' => fn($query) => $query->withCount('items')->orderBy('name'),
            ])
                ->withCount('secondaryCategories')
                ->orderBy('name')
                ->get();
        }

        $lowerSearch = strtolower($search);

        // Build the base query to find matching primary categories
        $query = PrimaryCategory::query()
            ->where(function ($q) use ($lowerSearch) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearch}%"])
                    ->orWhereHas('secondaryCategories', function ($sq) use ($lowerSearch) {
                        $sq->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearch}%"])
                            ->orWhereHas('items', fn($iq) => $iq->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearch}%"]));
                    });
            });

        // Eager load relationships with constraints to only include matching children
        $query->with([
            'secondaryCategories' => function ($sq) use ($lowerSearch) {
                $sq->where(function ($ssq) use ($lowerSearch) {
                    $ssq->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearch}%"])
                        ->orWhereHas('items', fn($iq) => $iq->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearch}%"]));
                })
                    ->withCount(['items' => fn($iq) => $iq->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearch}%"])])
                    ->orderBy('name');
            },
            'secondaryCategories.items' => function ($iq) use ($lowerSearch) {
                $iq->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearch}%"])
                    ->withCount('specifications')
                    ->orderBy('name');
            },
        ])->withCount([
            'secondaryCategories as secondary_categories_count' => function ($sq) use ($lowerSearch) {
                $sq->where(function ($ssq) use ($lowerSearch) {
                    $ssq->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearch}%"])
                        ->orWhereHas('items', fn($iq) => $iq->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearch}%"]));
                });
            },
        ]);

        $categories = $query->orderBy('name')->get();

        // If a primary category name matches the search, we need to load ALL its children,
        // overriding the constraints applied above.
        return $categories->map(function (PrimaryCategory $primary) use ($lowerSearch) {
            if (str_contains(strtolower($primary->name), $lowerSearch)) {
                // Reload relationships without search constraints
                $primary->load([
                    'secondaryCategories.items' => fn($query) => $query->withCount('specifications')->orderBy('name'),
                    'secondaryCategories' => fn($query) => $query->withCount('items')->orderBy('name'),
                ]);
                $primary->secondary_categories_count = $primary->secondaryCategories()->count();
                $primary->secondaryCategories->each(fn($sec) => $sec->items_count = $sec->items()->count());
            } else {
                // For primary categories that don't match, we need to handle secondary categories
                $primary->secondaryCategories->each(function ($secondary) use ($lowerSearch) {
                    if (str_contains(strtolower($secondary->name), $lowerSearch)) {
                        // If secondary name matches, load all its items
                        $secondary->load(['items' => fn($query) => $query->withCount('specifications')->orderBy('name')]);
                        $secondary->items_count = $secondary->items()->count();
                    }
                });
            }

            return $primary;
        });
    }

    #[Computed]
    public function expandableIds(): array
    {
        $ids = [];

        foreach ($this->categories() as $primary) {
            if ($primary->secondary_categories_count > 0) {
                $ids[] = 'primary-' . $primary->id;

                foreach ($primary->secondaryCategories as $secondary) {
                    if ($secondary->items_count > 0) {
                        $ids[] = 'secondary-' . $secondary->id;
                    }
                }
            }
        }

        return $ids;
    }
}; ?>

<div>
    <x-tree.index
        :items="$this->categories"
        :expandable-ids="$this->expandableIds"
        :is-searching="$this->isSearching"
        empty-message="No Categories Found"
        create-modal-name="create-primary-category"
        create-text="Create Primary Category"
    >

    
    @foreach ($this->categories as $primary)
        <x-tree.item
            :id="'primary-'.$primary->id"
            :title="$primary->name"
            :subtitle="$primary->secondary_categories_count . ' secondary categories'"
            :edit-click="'editPrimaryCategory('.$primary->id.')'"
            add-modal-name="create-secondary-category"
            add-text="Add Secondary"
            :has-children="$primary->secondary_categories_count > 0"
            :search-terms="[$this->search]"
        >
            @forelse($primary->secondaryCategories as $secondary)
                <x-tree.item
                    :id="'secondary-'.$secondary->id"
                    :title="$secondary->name"
                    :subtitle="$secondary->items_count . ' items'"
                    :edit-click="'editSecondaryCategory('.$secondary->id.')'"
                    add-modal-name="create-item"
                    add-text="Add Item"
                    :level="1"
                    :has-children="$secondary->items_count > 0"
                    :search-terms="[$this->search]"
                >
                    @forelse($secondary->items as $item)
                        <x-tree.item
                            :id="'item-'.$item->id"
                            :title="$item->name"
                            :subtitle="$item->specifications_count . ' specifications'"
                            :edit-click="'editItem('.$item->id.')'"
                            :level="2"
                            :has-children="false"
                            :search-terms="[$this->search]"
                            icon="cube"
                        />
                    @empty
                        <p class="py-2 text-sm italic text-stone-500 dark:text-stone-400">No items in this category.</p>
                    @endforelse
                </x-tree.item>
            @empty
                <p class="text-sm italic text-stone-500 dark:text-stone-400">No secondary categories found.</p>
            @endforelse
        </x-tree.item>
    @endforeach
    </x-tree.index>

    <!-- Create Primary Category Modal -->
    <x-admin.modal-form-wrapper name="create-primary-category" maxWidth="lg">
        <livewire:admin.data.items-and-categories.primary-categories.create />
    </x-admin.modal-form-wrapper>

    <!-- Create Secondary Category Modal -->
    <x-admin.modal-form-wrapper name="create-secondary-category" maxWidth="lg">
        <livewire:admin.data.items-and-categories.secondary-categories.create />
    </x-admin.modal-form-wrapper>

    <!-- Create Item Modal -->
    <x-admin.modal-form-wrapper name="create-item" maxWidth="lg">
        <livewire:admin.data.items-and-categories.items-catalog.create />
    </x-admin.modal-form-wrapper>

    <!-- Edit Primary Category Modal -->
    @if($editingPrimaryCategoryId)
    <x-admin.modal-form-wrapper name="edit-primary-category" maxWidth="lg">
        <livewire:admin.data.items-and-categories.primary-categories.edit :primaryCategory="$editingPrimaryCategoryId" :key="'primary-'.$editingPrimaryCategoryId" />
    </x-admin.modal-form-wrapper>
    @endif

    <!-- Edit Secondary Category Modal -->
    @if($editingSecondaryCategoryId)
    <x-admin.modal-form-wrapper name="edit-secondary-category" maxWidth="lg">
        <livewire:admin.data.items-and-categories.secondary-categories.edit :secondaryCategory="$editingSecondaryCategoryId" :key="'secondary-'.$editingSecondaryCategoryId" />
    </x-admin.modal-form-wrapper>
    @endif

    <!-- Edit Item Modal -->
    @if($editingItemId)
    <x-admin.modal-form-wrapper name="edit-item" maxWidth="lg">
        <livewire:admin.data.items-and-categories.items-catalog.edit :itemsCatalog="$editingItemId" :key="'item-'.$editingItemId" />
    </x-admin.modal-form-wrapper>
    @endif
</div> 