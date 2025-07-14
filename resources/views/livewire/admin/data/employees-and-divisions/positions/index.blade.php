<?php

use App\Models\Position;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public ?Position $editing = null;
    public bool $showCreateModal = false;
    
    // View and filter state
    public string $search = '';
    public bool $showFilters = false;
    public int $perPage = 10;
    
    // Sorting properties
    public string $sortColumn = 'title';
    public string $sortDirection = 'asc';

    public function mount()
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
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
        // No filters to reset yet
    }
    
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function positions()
    {
        return Position::query()
            ->when($this->search, fn($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->orderBy($this->sortColumn, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function newPosition(): void
    {
        $this->editing = new Position();
        $this->showCreateModal = true;
    }

    public function edit(Position $position): void
    {
        $this->editing = $position;
        $this->showCreateModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'editing.title' => ['required', 'string', 'max:255', Rule::unique('positions', 'title')->ignore($this->editing->id)],
        ]);

        $this->editing->save();

        $this->showCreateModal = false;
        $this->dispatch('position-saved');
        session()->flash('success', 'Position saved successfully.');
    }

    public function with(): array
    {
        return [
            'positions' => $this->positions,
        ];
    }
}; ?>

<div x-data="{ showFilters: @entangle('showFilters') }">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Positions
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
            <flux:button variant="outline" x-on:click="showFilters = !showFilters" class="!p-2">
                <x-flux::icon.filter class="h-5 w-5" />
                <span class="sr-only">Toggle Filters</span>
            </flux:button>
            <a href="{{ route('admin.data.employees-and-divisions.positions.create', ['view' => 'table']) }}" wire:navigate>
                <flux:button as="span" variant="primary">New Position</flux:button>
            </a>
        </div>
    </div>
    
    <div x-show="showFilters" x-collapse class="mt-4">
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <div class="p-4">
                <p class="text-sm text-center text-stone-500 dark:text-stone-400">No filters available for this view yet.</p>
            </div>
            <div class="border-t border-stone-200 bg-stone-50 p-4 text-right dark:border-stone-700 dark:bg-stone-800/50">
                <flux:button variant="ghost" wire:click="resetFilters">
                    Reset Filters
                </flux:button>
            </div>
        </div>
    </div>

    <div class="mt-4 flex items-center justify-between">
        <div class="text-sm text-stone-600 dark:text-stone-400">
            @if ($this->positions->total() > 0)
                <span>Showing {{ $this->positions->firstItem() }} to {{ $this->positions->lastItem() }} of <strong>{{ $this->positions->total() }}</strong> results.</span>
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


    <div class="mt-4 flow-root">
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
                <thead class="bg-stone-50 dark:bg-stone-800">
                    <tr class="divide-x divide-stone-200 dark:divide-stone-700">
                        <th scope="col" class="w-full px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                            <div wire:click="sortBy('title')" class="flex cursor-pointer items-center">
                                Name
                                @if($sortColumn === 'title')
                                    @if($sortDirection === 'asc')
                                        <x-flux::icon.chevron-up class="ml-2 h-4 w-4" />
                                    @else
                                        <x-flux::icon.chevron-down class="ml-2 h-4 w-4" />
                                    @endif
                                @else
                                    <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400" />
                                @endif
                            </div>
                        </th>
                        <th scope="col" class="relative px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                    @forelse($positions as $position)
                        <tr wire:key="position-{{ $position->id }}" class="hover:bg-stone-50 dark:hover:bg-stone-800/50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-stone-900 dark:text-stone-100">{{ $position->title }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <a href="{{ route('admin.data.employees-and-divisions.positions.edit', ['position' => $position, 'view' => 'table']) }}" wire:navigate class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 text-sm font-semibold text-stone-900 shadow-sm hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50">
                                   Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">
                                No positions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>


    <div class="mt-4">
        {{ $positions->links() }}
    </div>

    <!-- Create/Edit Modal -->
    @if($editing)
        <flux:modal :show="$showCreateModal" max-width="lg" @close="$set('showCreateModal', false)">
            <form wire:submit.prevent="save">
                <x-slot:title>
                    {{ $editing->exists ? 'Edit' : 'Create' }} Position
                </x-slot:title>

                <div class="space-y-4 p-6">
                    <flux:input wire:model="editing.title" label="Title" required />
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