<?php

use App\Models\Division;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public ?Employee $editing = null;
    public bool $showCreateModal = false;
    
    // View and filter state
    public string $search = '';
    public bool $showFilters = false;
    public int $perPage = 10;
    
    // Filter properties
    public ?int $filterPosition = null;
    public ?int $filterDivision = null;

    // Sorting properties
    public string $sortColumn = 'name';
    public string $sortDirection = 'asc';

    // For creating/editing user credentials
    public string $username = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $create_user_account = false;

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
        $this->reset('filterPosition', 'filterDivision');
    }
    
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function filtersActive(): bool
    {
        return $this->filterPosition || $this->filterDivision;
    }

    #[Computed]
    public function employees()
    {
        return Employee::with(['user', 'position', 'division'])
            ->when($this->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%")
                    ->orWhereHas('position', fn ($q) => $q->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('division', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->when($this->filterPosition, fn(Builder $q) => $q->where('position_id', $this->filterPosition))
            ->when($this->filterDivision, fn(Builder $q) => $q->where('division_id', $this->filterDivision))
            ->orderBy($this->sortColumn, $this->sortDirection)
            ->paginate($this->perPage);
    }

    #[Computed]
    public function positions()
    {
        return Position::orderBy('title')->get();
    }

    #[Computed]
    public function divisions()
    {
        return Division::orderBy('name')->get();
    }

    public function newEmployee(): void
    {
        $this->editing = new Employee();
        $this->username = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->create_user_account = false;
        $this->showCreateModal = true;
    }

    public function edit(Employee $employee): void
    {
        $this->editing = $employee;
        $this->username = $employee->user?->username ?? '';
        $this->email = $employee->user?->email ?? '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->create_user_account = $employee->user_id !== null;
        $this->showCreateModal = true;
    }

    public function save(): void
    {
        $rules = [
            'editing.name' => ['required', 'string', 'max:255'],
            'editing.employee_number' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_number')->ignore($this->editing->id)],
            'editing.position_id' => ['required', 'integer', Rule::exists('positions', 'id')],
            'editing.division_id' => ['required', 'integer', Rule::exists('divisions', 'id')],
            'create_user_account' => ['boolean'],
        ];

        if ($this->create_user_account) {
            $userId = $this->editing->user_id;
            $rules = array_merge($rules, [
                'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
                'password' => [$userId ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            ]);
        }

        $this->validate($rules);

        DB::transaction(function () {
            $user = null;
            if ($this->create_user_account) {
                $userData = [
                    'name' => $this->editing->name,
                    'username' => $this->username,
                    'email' => $this->email,
                ];
                if ($this->password) {
                    $userData['password'] = Hash::make($this->password);
                }

                $user = User::updateOrCreate(
                    ['id' => $this->editing->user_id],
                    $userData
                );
                $this->editing->user_id = $user->id;
            } elseif ($this->editing->user_id) {
                // If checkbox is unchecked, disassociate user but don't delete
                $this->editing->user_id = null;
            }

            $this->editing->save();
        });

        $this->showCreateModal = false;
        $this->dispatch('employee-saved');
        session()->flash('success', 'Employee saved successfully.');
    }

    public function with(): array
    {
        return [
            'employees' => $this->employees,
            'positions' => $this->positions,
            'divisions' => $this->divisions,
        ];
    }
}; ?>

<div x-data="tableResizer('employees_widths', { name: 350, position: 250, division: 250, user_account: 150, actions: 100 })">
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
                    <div class="border-t border-stone-200 px-3 py-2 dark:border-stone-700">
                        <div class="mb-2 text-xs font-semibold uppercase text-stone-500 dark:text-stone-400">Column Layout</div>
                        <flux:button
                            variant="ghost"
                            x-on:click="$dispatch('reset-column-widths')"
                            class="w-full justify-center"
                        >
                            <x-flux::icon.rotate-cw class="mr-2 h-4 w-4" />
                            Reset Column Widths
                        </flux:button>
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
            <flux:button :href="route('admin.data.employees-and-divisions.employees.create')" variant="primary">New Employee</flux:button>
        </div>
    </div>
    
    <div x-show="$wire.showFilters" x-collapse class="mt-4">
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
             <div class="border-b border-stone-200 p-4 dark:border-stone-700">
                <h3 class="font-semibold text-stone-800 dark:text-stone-200">Filter Options</h3>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <flux:select wire:model.live="filterPosition" label="Position">
                            <option value="">Any Position</option>
                            @foreach($this->positions as $position)
                                <option value="{{ $position->id }}">{{ $position->title }}</option>
                            @endforeach
                        </flux:select>
                    </div>
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
            >
                <x-slot:leading>
                    <x-flux::icon.search class="size-5 text-stone-400" />
                </x-slot:leading>
            </flux:input>
        </div>
    </div>

    <div class="mt-4 flow-root">
        <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700 table-fixed">
                <thead class="bg-stone-50 dark:bg-stone-800">
                    <tr class="divide-x divide-stone-200 dark:divide-stone-700">
                        <th scope="col" :style="`width: ${columnWidths.name}px`" class="relative px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
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
                        <th scope="col" :style="`width: ${columnWidths.position}px`" class="relative px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                             <div wire:click="sortBy('position_id')" class="flex cursor-pointer items-center">
                                Position
                                @if($sortColumn === 'position_id')
                                     @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                @else
                                    <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400" />
                                @endif
                            </div>
                            <div @mousedown="startResize($event, 'position')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                        </th>
                        <th scope="col" :style="`width: ${columnWidths.division}px`" class="relative px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
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
                        <th scope="col" :style="`width: ${columnWidths.user_account}px`" class="relative px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                            <div class="flex items-center">
                                Has User Account
                            </div>
                            <div @mousedown="startResize($event, 'user_account')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                        </th>
                        <th scope="col" :style="`width: ${columnWidths.actions}px`" class="relative px-6 py-3">
                            <span class="sr-only">Edit</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                    @forelse($employees as $employee)
                        <tr wire:key="employee-{{ $employee->id }}" class="hover:bg-stone-50 dark:hover:bg-stone-800/50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-stone-900 dark:text-stone-100">
                                <div class="font-semibold">{{ $employee->name }}</div>
                                <div class="text-xs text-stone-500 dark:text-stone-400">{{ $employee->employee_number }}</div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">
                                {{ $employee->position?->title }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">
                                {{ $employee->division?->name }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">
                                @if($employee->user)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                        <x-flux::icon.check-circle class="-ml-0.5 mr-1.5 h-4 w-4" />
                                        Yes
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium text-stone-800 dark:bg-stone-700 dark:text-stone-200">
                                        <x-flux::icon.x-circle class="-ml-0.5 mr-1.5 h-4 w-4" />
                                        No
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <flux:button :href="route('admin.data.employees-and-divisions.employees.edit', $employee)" variant="ghost" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-200">Edit</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">
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

    <!-- Create/Edit Modal -->
    @if($editing)
        <flux:modal :show="$showCreateModal" max-width="2xl" @close="$set('showCreateModal', false)">
            <form wire:submit.prevent="save">
                <x-slot:title>
                    {{ $editing->exists ? 'Edit' : 'Create' }} Employee
                </x-slot:title>

                <div class="space-y-6 p-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <flux:input wire:model="editing.name" label="Full Name" required />
                        <flux:input wire:model="editing.employee_number" label="Employee Number" required />
                        <flux:select wire:model="editing.position_id" label="Position" required>
                            <option value="">Select a position</option>
                            @foreach($this->positions as $position)
                                <option value="{{ $position->id }}">{{ $position->title }}</option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model="editing.division_id" label="Division/Office" required>
                            <option value="">Select a division</option>
                            @foreach($this->divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div class="relative">
                      <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-stone-300 dark:border-stone-600"></div>
                      </div>
                      <div class="relative flex justify-center">
                        <span class="bg-white px-3 text-base font-semibold leading-6 text-stone-900 dark:bg-stone-800 dark:text-stone-100">User Account</span>
                      </div>
                    </div>
                    <div x-data="{ showCredentials: @entangle('create_user_account').live }" class="space-y-4">
                        <flux:checkbox x-model="showCredentials" label="Create or link a user account for this employee" />

                        <div x-show="showCredentials" x-collapse.duration.300ms>
                            <div class="grid grid-cols-1 gap-6 rounded-md border border-stone-200 bg-stone-50 p-4 dark:border-stone-700 dark:bg-stone-800/50 sm:grid-cols-2">
                                <flux:input wire:model="username" label="Username" />
                                <flux:input wire:model="email" label="Email Address" type="email" />
                                <flux:input wire:model="password" label="Password" type="password" hint="{{ $editing->exists && $editing->user_id ? 'Leave blank to keep current password' : '' }}" />
                                <flux:input wire:model="password_confirmation" label="Confirm Password" type="password" />
                            </div>
                        </div>
                    </div>
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
    });
</script> 