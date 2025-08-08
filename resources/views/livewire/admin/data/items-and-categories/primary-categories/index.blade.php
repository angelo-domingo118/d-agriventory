<?php

use App\Models\PrimaryCategory;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Flux\Flux;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;
    public string $sortColumn = 'name';
    public string $sortDirection = 'asc';
    public string $density = 'spacious';
    public ?PrimaryCategory $editingCategory = null;

    public function mount()
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage inventory data.');
        }
    }

    public function resetSorting(): void
    {
        $this->sortColumn = 'name';
        $this->sortDirection = 'asc';
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
        $this->search = '';
        $this->sortColumn = 'name';
        $this->sortDirection = 'asc';
        $this->perPage = 10;
        $this->resetPage();
    }
    
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function editCategory(PrimaryCategory $category): void
    {
        $this->editingCategory = $category;
        Flux::modal('edit-primary-category')->show();
    }

    #[On('primary-category-created')]
    #[On('primary-category-updated')]
    #[On('primary-category-deleted')]
    public function refreshCategories(): void
    {
        // Force refresh of computed property and reset to first page
        unset($this->categories);
        $this->resetPage();
        $this->dispatch('$refresh');
        
        // Reset editing category
        $this->editingCategory = null;
    }
    
    #[Computed]
    public function categories()
    {
        return PrimaryCategory::query()
            ->when($this->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            })
            ->orderBy($this->sortColumn, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'categories' => $this->categories,
        ];
    }
}; ?>

<div x-data="{ 
    ...tableResizer('primary_categories_widths', { name: 600, code: 300, actions: 120 }),
    ...tableSettings('primary_categories_settings')
}">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Primary Categories
        </h1>
        <div class="flex items-center gap-x-2">
            <div x-data="{ open: false }" class="relative">
                <flux:button
                    variant="outline"
                    x-on:click="open = !open"
                    class="!p-2"
                >
                    <x-flux::icon.settings-2 class="h-5 w-5"/>
                    <span class="sr-only">Toggle View Options</span>
                </flux:button>

                <div
                    x-show="open"
                    x-on:click.outside="open = false"
                    x-transition
                    class="absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-stone-800 dark:ring-stone-700"
                    style="display: none;"
                >
                    <!-- Density -->
                    <div class="px-3 py-2">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Density</div>
                        <div class="mt-2 flex overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                            <button 
                                @click="updateSetting('density', 'compact')" 
                                class="flex-1 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $density === 'compact' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Compact
                            </button>
                            <button 
                                @click="updateSetting('density', 'comfortable')" 
                                class="-ml-px flex-1 border-x border-stone-200 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-stone-700 {{ $density === 'comfortable' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Comfortable
                            </button>
                            <button 
                                @click="updateSetting('density', 'spacious')" 
                                class="-ml-px flex-1 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $density === 'spacious' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Spacious
                            </button>
                        </div>
                    </div>

                    <!-- Items per Page -->
                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Items per Page</div>
                        <div class="mt-2 flex overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                            @foreach ([5, 10, 25, 50] as $count)
                                <button
                                    @click="updateSetting('perPage', {{ $count }})"
                                    class="@if(!$loop->first) -ml-px border-l border-stone-200 dark:border-stone-700 @endif flex-1 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $perPage == $count ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                                >
                                    {{ $count }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Table Customization -->
                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="mb-2 text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Table Customization</div>
                        <div class="space-y-2">
                            <flux:button
                                variant="outline"
                                x-on:click="$dispatch('reset-column-widths')"
                                class="w-full justify-center"
                            >
                                <x-flux::icon.rotate-cw class="mr-2 h-4 w-4" />
                                Reset Column Widths
                            </flux:button>
                            <flux:button
                                variant="outline"
                                wire:click="resetSorting"
                                class="w-full justify-center"
                            >
                                <div class="flex items-center">
                                    <x-flux::icon.chevrons-up-down class="mr-2 h-4 w-4" />
                                    Reset Sort Order
                                </div>
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
            <flux:button variant="outline" wire:click="$refresh" class="!p-2">
                <x-flux::icon.rotate-cw class="h-5 w-5" wire:loading.class="animate-spin" />
                <span class="sr-only">Refresh</span>
            </flux:button>
            <flux:modal.trigger name="create-primary-category">
                <flux:button variant="primary">New Primary Category</flux:button>
            </flux:modal.trigger>
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
                icon="magnifying-glass"
                clearable
            />
        </div>
    </div>

    @php
        $densityClasses = [
            'table_header' => match($density) {
                'compact' => 'py-2 px-4',
                'comfortable' => 'py-2.5 px-4',
                default => 'py-3 px-4',
            },
            'table_cell' => match($density) {
                'compact' => 'py-2 px-4',
                'comfortable' => 'py-3 px-4',
                default => 'py-4 px-4',
            },
            'text_header' => match($density) {
                'compact' => 'text-xs',
                default => 'text-xs',
            },
            'text_base' => match($density) {
                'compact' => 'text-xs',
                default => 'text-sm',
            },
        ];
    @endphp


    <div class="mt-4 flow-root">
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700 table-fixed">
                <thead class="bg-stone-50 dark:bg-stone-800">
                    <tr class="divide-x divide-stone-200 dark:divide-stone-700">
                        <th scope="col" :style="`width: ${columnWidths.name}px`" class="relative w-full {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                            <div wire:click="sortBy('name')" class="flex cursor-pointer items-center">
                                Name
                                @if($sortColumn === 'name')
                                    @if($sortDirection === 'asc')
                                        <x-flux::icon.chevron-up class="ml-2 h-4 w-4" />
                                    @else
                                        <x-flux::icon.chevron-down class="ml-2 h-4 w-4" />
                                    @endif
                                @else
                                    <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400" />
                                @endif
                            </div>
                             <div @mousedown="startResize($event, 'name')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                        </th>
                        <th scope="col" :style="`width: ${columnWidths.code}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                            <div wire:click="sortBy('code')" class="flex cursor-pointer items-center">
                                Code
                                @if($sortColumn === 'code')
                                     @if($sortDirection === 'asc')
                                        <x-flux::icon.chevron-up class="ml-2 h-4 w-4" />
                                    @else
                                        <x-flux::icon.chevron-down class="ml-2 h-4 w-4" />
                                    @endif
                                @else
                                    <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400" />
                                @endif
                            </div>
                            <div @mousedown="startResize($event, 'code')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                        </th>
                        <th scope="col" :style="`width: ${columnWidths.actions}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                    @forelse($categories as $category)
                        <tr wire:key="category-{{ $category->id }}" class="hover:bg-stone-50 dark:hover:bg-stone-800/50">
                            <td class="whitespace-nowrap {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} font-medium text-stone-900 dark:text-stone-100">{{ $category->name }}</td>
                            <td class="whitespace-nowrap {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">{{ $category->code }}</td>
                            <td class="whitespace-nowrap {{ $densityClasses['table_cell'] }} text-right {{ $densityClasses['text_base'] }} font-medium">
                                <button wire:click="editCategory({{ $category->id }})" wire:loading.attr="disabled" wire:target="editCategory({{ $category->id }})" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50">
                                   <x-flux::icon.edit class="mr-1.5 h-4 w-4" wire:loading.remove wire:target="editCategory({{ $category->id }})" />
                                   <x-flux::icon.rotate-cw class="mr-1.5 h-4 w-4 animate-spin" wire:loading wire:target="editCategory({{ $category->id }})" />
                                   <span wire:loading.remove wire:target="editCategory({{ $category->id }})">Edit</span>
                                   <span wire:loading wire:target="editCategory({{ $category->id }})">Loading...</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="{{ $densityClasses['table_cell'] }} text-center {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">
                                No primary categories found.
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

    <!-- Create Primary Category Modal -->
    <x-admin.modal-form-wrapper name="create-primary-category" maxWidth="lg">
        <livewire:admin.data.items-and-categories.primary-categories.create />
    </x-admin.modal-form-wrapper>

    <!-- Edit Primary Category Modal -->
    @if($editingCategory)
        <x-admin.modal-form-wrapper name="edit-primary-category" maxWidth="lg">
            <livewire:admin.data.items-and-categories.primary-categories.edit 
                :category="$editingCategory" 
                :key="'edit-category-' . $editingCategory->id" 
            />
        </x-admin.modal-form-wrapper>

        <!-- Delete Confirmation Modal -->
        <x-admin.delete-confirmation-modal 
            name="delete-primary-category-confirmation"
            title="Delete Primary Category"
            item-type="primary category"
            :item-name="$editingCategory->name"
            delete-action="$dispatch('call-delete')"
            cancel-action="$dispatch('call-cancel-delete')"
            message="Deleting this primary category will also affect all associated secondary categories and items."
        />
    @endif
</div> 
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('tableResizer', (storageKey, defaultWidths) => ({
            columnWidths: {},
            resizingColumn: null,
            startX: 0,
            startWidth: 0,
            init() {
                const storedWidths = JSON.parse(localStorage.getItem(storageKey) || '{}');
                this.columnWidths = { ...defaultWidths, ...storedWidths };
                
                this.$root.addEventListener('reset-column-widths', () => {
                    this.columnWidths = { ...defaultWidths };
                    localStorage.removeItem(storageKey);
                });
            },
            startResize(event, column) {
                this.resizingColumn = column;
                this.startX = event.clientX;
                this.startWidth = this.columnWidths[column];
                event.preventDefault();

                const mouseMoveHandler = (e) => {
                    if (!this.resizingColumn) return;
                    const diffX = e.clientX - this.startX;
                    const newWidth = this.startWidth + diffX;
                    if (newWidth > 60) {
                        this.columnWidths[this.resizingColumn] = newWidth;
                    }
                };

                const mouseUpHandler = () => {
                    if (!this.resizingColumn) return;
                    this.resizingColumn = null;
                    localStorage.setItem(storageKey, JSON.stringify(this.columnWidths));
                    window.removeEventListener('mousemove', mouseMoveHandler);
                    window.removeEventListener('mouseup', mouseUpHandler);
                };

                window.addEventListener('mousemove', mouseMoveHandler);
                window.addEventListener('mouseup', mouseUpHandler);
            }
        }));

        Alpine.data('tableSettings', (storageKey) => ({
            init() {
                const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
                if (saved.density) this.$wire.density = saved.density;
                if (saved.perPage) this.$wire.perPage = saved.perPage;
            },
            updateSetting(key, value) {
                this.$wire[key] = value;
                const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
                saved[key] = value;
                localStorage.setItem(storageKey, JSON.stringify(saved));
            }
        }));
    });
</script> 