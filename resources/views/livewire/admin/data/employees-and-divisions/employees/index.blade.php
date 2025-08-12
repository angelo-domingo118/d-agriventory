<?php

use App\Models\Division;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Flux\Flux;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;



    // View and filter state
    public string $search = '';
    public bool $showFilters = false;
    public int $perPage = 10;
    public string $density = 'spacious';
    public string $textOverflow = 'nowrap';
    public string $fontSize = 'medium';

    // Filter properties
    public ?int $filterDivision = null;

    // Sorting properties
    public string $sortColumn = 'name';
    public string $sortDirection = 'asc';
    public ?Employee $editingEmployee = null;

    public function mount()
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
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
        $this->reset('filterDivision');
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function editEmployee(Employee $employee): void
    {
        $this->editingEmployee = $employee;
        Flux::modal('edit-employee')->show();
    }

    #[Computed]
    public function filtersActive(): bool
    {
        return $this->filterDivision !== null;
    }

    #[Computed]
    public function editingEmployeeDeletionImpact(): array
    {
        if (!$this->editingEmployee) {
            return [
                'ics_numbers' => 0,
                'par_numbers' => 0,
                'assigned_idr_numbers' => 0,
                'approved_idr_numbers' => 0,
                'ics_transfers_from' => 0,
                'ics_transfers_to' => 0,
                'par_transfers_from' => 0,
                'par_transfers_to' => 0,
                'total_assignments' => 0,
                'total_transfers' => 0,
                'risk_level' => 'safe',
                'risk_message' => '',
                'has_associated_data' => false,
            ];
        }

        return $this->editingEmployee->getDeletionImpact();
    }

    #[Computed]
    public function employees()
    {
        return Employee::with(['division'])
            ->withCount(['icsNumbers', 'parNumbers', 'assignedIdrNumbers'])
            ->when($this->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhereHas('division', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->when($this->filterDivision, fn(Builder $q) => $q->where('division_id', $this->filterDivision))
            ->orderBy($this->sortColumn, $this->sortDirection)
            ->paginate($this->perPage);
    }

    #[Computed]
    public function divisions()
    {
        return Division::orderBy('name')->get();
    }

    #[On('employee-created')]
    #[On('employee-updated')]
    #[On('employee-deleted')]
    public function refreshEmployees(): void
    {
        // Force refresh of computed property and reset to first page
        unset($this->employees);
        $this->resetPage();
        $this->dispatch('$refresh');
        
        // Reset editing employee
        $this->editingEmployee = null;
    }

    public function with(): array
    {
        return [
            'employees' => $this->employees,
            'divisions' => $this->divisions,
        ];
    }
}; ?>

<div x-data="{ 
    ...tableResizer('employees_widths', { name: 300, position: 200, division: 200, inventory: 150, actions: 100 }),
    ...tableSettings('employees_settings')
}">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Employees
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

                    <!-- Text Overflow -->
                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Text Overflow</div>
                        <div class="mt-2 flex overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                            <button 
                                @click="updateSetting('textOverflow', 'nowrap')" 
                                class="flex-1 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $textOverflow === 'nowrap' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                No Wrap
                            </button>
                            <button 
                                @click="updateSetting('textOverflow', 'wrap')" 
                                class="-ml-px flex-1 border-x border-stone-200 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-stone-700 {{ $textOverflow === 'wrap' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Wrap Text
                            </button>
                            <button 
                                @click="updateSetting('textOverflow', 'scroll')" 
                                class="-ml-px flex-1 px-3 py-1.5 text-center text-sm focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $textOverflow === 'scroll' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Scroll
                            </button>
                        </div>
                    </div>
                    
                    <!-- Font Size -->
                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Font Size</div>
                        <div class="mt-2 grid grid-cols-2 gap-1 overflow-hidden rounded-md border border-stone-200 dark:border-stone-700">
                            <button 
                                @click="updateSetting('fontSize', 'small')" 
                                class="px-2 py-1.5 text-center text-xs focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-tl-sm {{ $fontSize === 'small' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Small
                            </button>
                            <button 
                                @click="updateSetting('fontSize', 'medium')" 
                                class="px-2 py-1.5 text-center text-xs focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-tr-sm {{ $fontSize === 'medium' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Medium
                            </button>
                            <button 
                                @click="updateSetting('fontSize', 'large')" 
                                class="px-2 py-1.5 text-center text-xs focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-bl-sm {{ $fontSize === 'large' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Large
                            </button>
                            <button 
                                @click="updateSetting('fontSize', 'xl')" 
                                class="px-2 py-1.5 text-center text-xs focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-br-sm {{ $fontSize === 'xl' ? 'bg-stone-100 dark:bg-stone-700' : 'hover:bg-stone-50 dark:hover:bg-stone-900/50' }}"
                            >
                                Extra Large
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
            <flux:button variant="outline" x-on:click="$wire.showFilters = !$wire.showFilters" class="!p-2 @if($this->filtersActive) bg-primary-50 text-primary-600 dark:bg-primary-900/10 dark:text-primary-400 @endif">
                <x-flux::icon.filter class="h-5 w-5" />
                <span class="sr-only">Toggle Filters</span>
            </flux:button>
            <flux:modal.trigger name="create-employee">
                <flux:button variant="primary">New Employee</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <div x-show="$wire.showFilters" x-collapse class="mt-4">
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
             <div class="border-b border-stone-200 p-4 dark:border-stone-700">
                <h3 class="font-semibold text-stone-800 dark:text-stone-200">Filter Options</h3>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-1">
                    <div>
                        <flux:select wire:model.live="filterDivision" label="Division">
                            <option value="">Any Division</option>
                             @foreach($this->divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
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
            @if ($this->employees->total() > 0)
                <span>Showing {{ $this->employees->firstItem() }} to {{ $this->employees->lastItem() }} of <strong>{{ $this->employees->total() }}</strong> results.</span>
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
            'text_header' => match($fontSize) {
                'small' => 'text-xs',
                'large' => 'text-base',
                'xl' => 'text-lg',
                default => match($density) {
                    'compact' => 'text-xs',
                    default => 'text-sm',
                },
            },
            'text_base' => match($fontSize) {
                'small' => 'text-xs',
                'large' => 'text-base',
                'xl' => 'text-lg',
                default => match($density) {
                    'compact' => 'text-xs',
                    default => 'text-sm',
                },
            },
            'text_overflow' => match($textOverflow) {
                'wrap' => 'break-words',
                'scroll' => 'whitespace-nowrap',
                default => 'whitespace-nowrap truncate',
            },
            'table_wrapper' => match($textOverflow) {
                'scroll' => 'overflow-x-auto',
                default => 'overflow-hidden',
            },
        ];
    @endphp

    <div class="mt-4 flow-root">
        <div class="{{ $densityClasses['table_wrapper'] }} rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700 table-fixed">
                <thead class="bg-stone-50 dark:bg-stone-800">
                    <tr class="divide-x divide-stone-200 dark:divide-stone-700">
                        <th scope="col" :style="`width: ${columnWidths.name}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                            <div wire:click="sortBy('name')" class="flex cursor-pointer items-center">
                                Name
                                @if($sortColumn === 'name')
                                    @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                @else
                                    <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400" />
                                @endif
                            </div>
                            <div @mousedown="startResize($event, 'name')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                        </th>
                        <th scope="col" :style="`width: ${columnWidths.position}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                             <div wire:click="sortBy('position')" class="flex cursor-pointer items-center">
                                Position
                                @if($sortColumn === 'position')
                                     @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                @else
                                    <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400" />
                                @endif
                            </div>
                            <div @mousedown="startResize($event, 'position')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                        </th>
                        <th scope="col" :style="`width: ${columnWidths.division}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                            <div wire:click="sortBy('division_id')" class="flex cursor-pointer items-center">
                                Division
                                @if($sortColumn === 'division_id')
                                     @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                @else
                                    <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400" />
                                @endif
                            </div>
                            <div @mousedown="startResize($event, 'division')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                        </th>
                        <th scope="col" :style="`width: ${columnWidths.inventory}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                            Inventory Items
                            <div @mousedown="startResize($event, 'inventory')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                        </th>
                        <th scope="col" :style="`width: ${columnWidths.actions}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                    @forelse($employees as $employee)
                        <tr wire:key="employee-{{ $employee->id }}" class="hover:bg-stone-50 dark:hover:bg-stone-800/50">
                            <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} font-medium text-stone-900 dark:text-stone-100" title="{{ $employee->name }}">{{ $employee->name }}</td>
                            <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400" title="{{ $employee->position }}">
                                @if($employee->position)
                                    {{ $employee->position }}
                                @else
                                    <span class="text-stone-400">-</span>
                                @endif
                            </td>
                            <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400" title="{{ $employee->division?->name }}">{{ $employee->division?->name }}</td>
                            <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">
                                <div class="flex flex-wrap gap-1">
                                    @if($employee->ics_numbers_count > 0)
                                        <span class="inline-flex items-center rounded-md bg-purple-50 px-1.5 py-0.5 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-400/10 dark:text-purple-400 dark:ring-purple-400/20" title="{{ $employee->ics_numbers_count }} ICS items">
                                            ICS: {{ $employee->ics_numbers_count }}
                                        </span>
                                    @endif
                                    @if($employee->par_numbers_count > 0)
                                        <span class="inline-flex items-center rounded-md bg-green-50 px-1.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-700/10 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20" title="{{ $employee->par_numbers_count }} PAR items">
                                            PAR: {{ $employee->par_numbers_count }}
                                        </span>
                                    @endif
                                    @if($employee->assigned_idr_numbers_count > 0)
                                        <span class="inline-flex items-center rounded-md bg-orange-50 px-1.5 py-0.5 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-700/10 dark:bg-orange-400/10 dark:text-orange-400 dark:ring-orange-400/20" title="{{ $employee->assigned_idr_numbers_count }} IDR items">
                                            IDR: {{ $employee->assigned_idr_numbers_count }}
                                        </span>
                                    @endif
                                    @if($employee->ics_numbers_count + $employee->par_numbers_count + $employee->assigned_idr_numbers_count === 0)
                                        <span class="inline-flex items-center rounded-md bg-stone-50 px-1.5 py-0.5 text-xs font-medium text-stone-600 ring-1 ring-inset ring-stone-500/10 dark:bg-stone-400/10 dark:text-stone-400 dark:ring-stone-400/20">
                                            No Items
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="whitespace-nowrap {{ $densityClasses['table_cell'] }} text-right {{ $densityClasses['text_base'] }} font-medium">
                                <button wire:click="editEmployee({{ $employee->id }})" wire:loading.attr="disabled" wire:target="editEmployee({{ $employee->id }})" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50">
                                   <x-flux::icon.pencil class="mr-1.5 h-4 w-4" wire:loading.remove wire:target="editEmployee({{ $employee->id }})" />
                                   <x-flux::icon.rotate-cw class="mr-1.5 h-4 w-4 animate-spin" wire:loading wire:target="editEmployee({{ $employee->id }})" />
                                   <span wire:loading.remove wire:target="editEmployee({{ $employee->id }})">Edit</span>
                                   <span wire:loading wire:target="editEmployee({{ $employee->id }})">Loading...</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="{{ $densityClasses['table_cell'] }} px-6 py-12 text-center {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">
                                No employees found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $employees->links() }}
    </div>

    <!-- Create Employee Modal -->
    <x-admin.modal-form-wrapper name="create-employee" maxWidth="xl">
        <livewire:admin.data.employees-and-divisions.employees.create />
    </x-admin.modal-form-wrapper>

    <!-- Edit Employee Modal -->
    @if($editingEmployee)
        <x-admin.modal-form-wrapper name="edit-employee" maxWidth="xl">
            <livewire:admin.data.employees-and-divisions.employees.edit 
                :employee="$editingEmployee" 
                :key="'edit-employee-' . $editingEmployee->id" 
            />
        </x-admin.modal-form-wrapper>

        <!-- Enhanced Delete Confirmation Modal -->
        <x-admin.enhanced-delete-modal 
            name="delete-employee-confirmation"
            title="Delete Employee"
            entity-type="employee"
            :entity-name="$editingEmployee->name"
            :association-counts="[
                'ICS assignments' => $this->editingEmployeeDeletionImpact['ics_numbers'],
                'PAR assignments' => $this->editingEmployeeDeletionImpact['par_numbers'],
                'assigned IDR numbers' => $this->editingEmployeeDeletionImpact['assigned_idr_numbers'],
                'approved IDR numbers' => $this->editingEmployeeDeletionImpact['approved_idr_numbers'],
                'ICS transfers (from)' => $this->editingEmployeeDeletionImpact['ics_transfers_from'],
                'ICS transfers (to)' => $this->editingEmployeeDeletionImpact['ics_transfers_to'],
                'PAR transfers (from)' => $this->editingEmployeeDeletionImpact['par_transfers_from'],
                'PAR transfers (to)' => $this->editingEmployeeDeletionImpact['par_transfers_to']
            ]"
            :has-associated-data="$this->editingEmployeeDeletionImpact['has_associated_data']"
            :risk-level="$this->editingEmployeeDeletionImpact['risk_level']"
            :risk-message="$this->editingEmployeeDeletionImpact['risk_message']"
            :block-deletion="$this->editingEmployeeDeletionImpact['risk_level'] === 'high'"
            delete-action="$dispatch('call-delete')"
            cancel-action="$dispatch('call-cancel-delete')"
            max-width="xl"
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
                if (saved.textOverflow) this.$wire.textOverflow = saved.textOverflow;
                if (saved.fontSize) this.$wire.fontSize = saved.fontSize;
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