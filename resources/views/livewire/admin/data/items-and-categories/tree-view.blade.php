<?php

use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use App\Models\ItemsCatalog;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    public string $search = '';
    public ?PrimaryCategory $editingPrimaryCategory = null;
    public ?SecondaryCategory $editingSecondaryCategory = null;
    public ?ItemsCatalog $editingItem = null;
    public ?PrimaryCategory $creatingForPrimary = null;
    public ?SecondaryCategory $creatingForSecondary = null;

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function createSecondaryCategory(int $primaryCategoryId): void
    {
        $this->creatingForPrimary = PrimaryCategory::findOrFail($primaryCategoryId);
        Flux::modal('tree-create-secondary-category')->show();
    }

    public function createItem(int $secondaryCategoryId): void
    {
        $this->creatingForSecondary = SecondaryCategory::findOrFail($secondaryCategoryId);
        Flux::modal('tree-create-item')->show();
    }
    
    public function editPrimaryCategory(int $id): void
    {
        $this->editingPrimaryCategory = PrimaryCategory::findOrFail($id);
        Flux::modal('tree-edit-primary-category')->show();
    }

    public function editSecondaryCategory(int $id): void
    {
        $this->editingSecondaryCategory = SecondaryCategory::findOrFail($id);
        Flux::modal('tree-edit-secondary-category')->show();
    }

    public function editItem(int $id): void
    {
        $this->editingItem = ItemsCatalog::findOrFail($id);
        Flux::modal('tree-edit-item')->show();
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
        $this->editingPrimaryCategory = null;
        $this->editingSecondaryCategory = null;
        $this->editingItem = null;
        
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
        create-modal-name="tree-create-primary-category"
        create-text="New Primary Category"
    >

    
    @foreach ($this->categories as $primary)
        <x-tree.item
            :id="'primary-'.$primary->id"
            :title="$primary->name"
            :subtitle="$primary->secondary_categories_count . ' secondary categories'"
            :edit-click="'editPrimaryCategory('.$primary->id.')'"
            :add-click="'createSecondaryCategory('.$primary->id.')'"
            add-text="New Secondary Category"
            :has-children="$primary->secondary_categories_count > 0"
            :search-terms="[$this->search]"
        >
            @forelse($primary->secondaryCategories as $secondary)
                <x-tree.item
                    :id="'secondary-'.$secondary->id"
                    :title="$secondary->name"
                    :subtitle="$secondary->items_count . ' items'"
                    :edit-click="'editSecondaryCategory('.$secondary->id.')'"
                    :add-click="'createItem('.$secondary->id.')'"
                    add-text="New Item"
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
    <x-admin.modal-form-wrapper name="tree-create-primary-category" maxWidth="lg">
        <livewire:admin.data.items-and-categories.primary-categories.create />
    </x-admin.modal-form-wrapper>

    <!-- Create Secondary Category Modal -->
    @if($creatingForPrimary)
        <x-admin.modal-form-wrapper name="tree-create-secondary-category" maxWidth="lg">
            <livewire:admin.data.items-and-categories.secondary-categories.create 
                :primaryCategoryId="$creatingForPrimary->id" 
                :key="'create-secondary-for-'.$creatingForPrimary->id" 
            />
        </x-admin.modal-form-wrapper>
    @endif

    <!-- Create Item Modal -->
    @if($creatingForSecondary)
        <x-admin.modal-form-wrapper name="tree-create-item" maxWidth="lg">
            <livewire:admin.data.items-and-categories.items-catalog.create 
                :secondaryCategoryId="$creatingForSecondary->id" 
                :key="'create-item-for-'.$creatingForSecondary->id" 
            />
        </x-admin.modal-form-wrapper>
    @endif

    <!-- Edit Primary Category Modal -->
    @if($editingPrimaryCategory)
    <x-admin.modal-form-wrapper name="tree-edit-primary-category" maxWidth="lg">
        <livewire:admin.data.items-and-categories.primary-categories.edit :category="$editingPrimaryCategory" :key="'primary-'.$editingPrimaryCategory->id" />
    </x-admin.modal-form-wrapper>
    @endif

    <!-- Edit Secondary Category Modal -->
    @if($editingSecondaryCategory)
    <x-admin.modal-form-wrapper name="tree-edit-secondary-category" maxWidth="lg">
        <livewire:admin.data.items-and-categories.secondary-categories.edit :category="$editingSecondaryCategory" :key="'secondary-'.$editingSecondaryCategory->id" />
    </x-admin.modal-form-wrapper>
    @endif

    <!-- Edit Item Modal -->
    @if($editingItem)
    <x-admin.modal-form-wrapper name="tree-edit-item" maxWidth="lg">
        <livewire:admin.data.items-and-categories.items-catalog.edit :item="$editingItem" :key="'item-'.$editingItem->id" />
    </x-admin.modal-form-wrapper>
    @endif
</div> 