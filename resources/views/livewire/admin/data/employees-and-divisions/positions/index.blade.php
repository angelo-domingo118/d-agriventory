<?php

use App\Models\Position;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Flux\Flux;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;


    
    // View state
    public string $search = '';
    public int $perPage = 10;
    public string $density = 'spacious';
    
    // Sorting properties
    public string $sortColumn = 'title';
    public string $sortDirection = 'asc';
    public ?Position $editingPosition = null;

    public function mount()
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function resetSorting(): void
    {
        $this->sortColumn = 'title';
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


    
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function editPosition(Position $position): void
    {
        $this->editingPosition = $position;
        Flux::modal('edit-position')->show();
    }

    #[Computed]
    public function positions()
    {
        return Position::query()
            ->when($this->search, fn($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->orderBy($this->sortColumn, $this->sortDirection)
            ->paginate($this->perPage);
    }

    #[On('position-created')]
    #[On('position-updated')]
    #[On('position-deleted')]
    public function refreshPositions(): void
    {
        // Force refresh of computed property and reset to first page
        unset($this->positions);
        $this->resetPage();
        $this->dispatch('$refresh');
        
        // Reset editing position
        $this->editingPosition = null;
    }

    public function with(): array
    {
        return [
            'positions' => $this->positions,
        ];
    }
}; ?>

<div x-data="{ 
    ...tableSettings('positions_settings')
}">
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
                <div x-show="open" x-on:click.outside="open = false" x-transition class="absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-stone-800 dark:ring-stone-700" style="display: none;">
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
            <flux:modal.trigger name="create-position">
                <flux:button variant="primary">New Position</flux:button>
            </flux:modal.trigger>
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
            <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
                <thead class="bg-stone-50 dark:bg-stone-800">
                    <tr class="divide-x divide-stone-200 dark:divide-stone-700">
                        <th scope="col" class="w-full {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
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
                        <th scope="col" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                    @forelse($positions as $position)
                        <tr wire:key="position-{{ $position->id }}" class="hover:bg-stone-50 dark:hover:bg-stone-800/50">
                            <td class="whitespace-nowrap {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} font-medium text-stone-900 dark:text-stone-100">{{ $position->title }}</td>
                            <td class="whitespace-nowrap {{ $densityClasses['table_cell'] }} text-right {{ $densityClasses['text_base'] }} font-medium">
                                <button wire:click="editPosition({{ $position->id }})" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50">
                                   <x-flux::icon.edit class="mr-1.5 h-4 w-4" />
                                   Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="{{ $densityClasses['table_cell'] }} px-6 py-12 text-center {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">
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

    <!-- Create Position Modal -->
    <x-admin.modal-form-wrapper name="create-position" maxWidth="lg">
        <livewire:admin.data.employees-and-divisions.positions.create />
    </x-admin.modal-form-wrapper>

    <!-- Edit Position Modal -->
    @if($editingPosition)
        <x-admin.modal-form-wrapper name="edit-position" maxWidth="lg">
            <livewire:admin.data.employees-and-divisions.positions.edit 
                :position="$editingPosition" 
                :key="'edit-position-' . $editingPosition->id" 
            />
        </x-admin.modal-form-wrapper>
    @endif
</div> 