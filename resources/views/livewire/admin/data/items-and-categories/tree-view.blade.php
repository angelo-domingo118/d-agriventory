<?php

use App\Models\PrimaryCategory;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public string $search = '';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
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

<x-tree.index
    :items="$this->categories"
    :expandable-ids="$this->expandableIds"
    :is-searching="$this->isSearching"
    empty-message="No Categories Found"
    create-url="{{ route('admin.data.items-and-categories.primary-categories.create') }}"
    create-text="Create Primary Category"
>
    @foreach ($this->categories as $primary)
        <x-tree.item
            :id="'primary-'.$primary->id"
            :title="$primary->name"
            :subtitle="$primary->secondary_categories_count . ' secondary categories'"
            :edit-url="route('admin.data.items-and-categories.primary-categories.edit', $primary)"
            :add-url="route('admin.data.items-and-categories.secondary-categories.create', ['primary_category_id' => $primary->id])"
            add-text="Add Secondary"
            :has-children="$primary->secondary_categories_count > 0"
            :search-terms="[$this->search]"
        >
            @forelse($primary->secondaryCategories as $secondary)
                <x-tree.item
                    :id="'secondary-'.$secondary->id"
                    :title="$secondary->name"
                    :subtitle="$secondary->items_count . ' items'"
                    :edit-url="route('admin.data.items-and-categories.secondary-categories.edit', $secondary)"
                    :add-url="route('admin.data.items-and-categories.items-catalog.create', ['secondary_category_id' => $secondary->id])"
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
                            :edit-url="route('admin.data.items-and-categories.items-catalog.edit', $item)"
                            :level="2"
                            :has-children="false"
                            :search-terms="[$this->search]"
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