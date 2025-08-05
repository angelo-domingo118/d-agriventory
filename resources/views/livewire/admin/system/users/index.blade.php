<?php

use App\Models\User;
use App\Models\AdminUser;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;
    
    #[Validate('nullable|string|max:100')]
    public string $search = '';
    public bool $showFilters = false;
    public int $perPage = 10;
    
    public string $role = '';
    public string $sortField = 'name';
    public string $sortDirection = 'asc';
    
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }
    
    public function updatedSearch()
    {
        // Sanitize search input
        $this->search = htmlspecialchars($this->search, ENT_QUOTES, 'UTF-8');
        $this->resetPage();
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        
        $this->sortField = $field;
    }
    
    #[Computed]
    public function roles()
    {
        return AdminUser::select('role')
            ->distinct()
            ->pluck('role')
            ->toArray();
    }
    
    public function render(): mixed
    {
        $users = User::query()
            ->with(['adminUser', 'divisionInventoryManager'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('username', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->role, function ($query) {
                $query->whereHas('adminUser', function ($query) {
                    $query->where('role', $this->role);
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
            
        return view('livewire.admin.system.users.index', [
            'users' => $users,
        ]);
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-4">
        <!-- Breadcrumbs as Title -->
        <div>
            <flux:breadcrumbs class="text-2xl font-semibold">
                <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                <flux:breadcrumbs.item href="#" class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">System</flux:breadcrumbs.item>
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">User Management</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
    </div>

    <div x-data="tableResizer('users_column_widths', { name: 400, email: 350, role: 150, status: 150, actions: 120 })">
        <div class="flex items-center justify-between">
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
                            <flux:button variant="ghost" x-on:click="$dispatch('reset-column-widths')" class="w-full justify-center">
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
                <flux:button variant="outline" x-on:click="$wire.showFilters = !$wire.showFilters" class="!p-2">
                    <x-flux::icon.filter class="h-5 w-5" />
                    <span class="sr-only">Toggle Filters</span>
                </flux:button>
                @adminpermission('create_users')
                    <flux:button :href="route('admin.system.users.create')" variant="primary">Add User</flux:button>
                @endadminpermission
            </div>
        </div>
    
        <div x-show="$wire.showFilters" x-collapse class="mt-4">
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <div class="border-b border-stone-200 p-4 dark:border-stone-700">
                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">Filter Options</h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <flux:select wire:model.live="role" id="role" :label="__('Filter by Role')">
                                <option value="">{{ __('All Roles') }}</option>
                                @foreach($this->roles as $roleOption)
                                    <option value="{{ $roleOption }}">{{ ucfirst(str_replace('_', ' ', $roleOption)) }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
        <div class="mt-4 flex items-center justify-between">
            <div class="text-sm text-stone-600 dark:text-stone-400">
                 @if ($users->total() > 0)
                    <span>Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of <strong>{{ $users->total() }}</strong> results.</span>
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
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden rounded-lg shadow ring-1 ring-black ring-opacity-5 dark:ring-stone-700">
                        <table class="min-w-full divide-y divide-stone-300 dark:divide-stone-700 table-fixed">
                            <thead class="bg-stone-50 dark:bg-stone-800">
                                <tr class="divide-x divide-stone-200 dark:divide-stone-700">
                                    <th scope="col" :style="`width: ${columnWidths.name}px`" class="relative py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-stone-900 dark:text-stone-100 sm:pl-6">
                                        <div wire:click="sortBy('name')" class="flex items-center cursor-pointer">
                                            Name
                                            @if($sortField === 'name')
                                                @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                            @else
                                                <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400 dark:text-stone-500" />
                                            @endif
                                        </div>
                                        <div @mousedown="startResize($event, 'name')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.email}px`" class="relative px-3 py-3.5 text-left text-sm font-semibold text-stone-900 dark:text-stone-100">
                                        <div wire:click="sortBy('email')" class="flex items-center cursor-pointer">
                                            Email
                                            @if($sortField === 'email')
                                                @if($sortDirection === 'asc') <x-flux::icon.chevron-up class="ml-2 h-4 w-4" /> @else <x-flux::icon.chevron-down class="ml-2 h-4 w-4" /> @endif
                                            @else
                                                <x-flux::icon.chevrons-up-down class="ml-2 h-4 w-4 text-stone-400 dark:text-stone-500" />
                                            @endif
                                        </div>
                                        <div @mousedown="startResize($event, 'email')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.role}px`" class="relative px-3 py-3.5 text-left text-sm font-semibold text-stone-900 dark:text-stone-100">
                                        Role
                                        <div @mousedown="startResize($event, 'role')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.status}px`" class="relative px-3 py-3.5 text-left text-sm font-semibold text-stone-900 dark:text-stone-100">
                                        Status
                                        <div @mousedown="startResize($event, 'status')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.actions}px`" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-left text-sm font-semibold text-stone-900 dark:text-stone-100">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                                @forelse ($users as $user)
                                    <tr wire:key="{{ $user->id }}" class="divide-x divide-stone-200 dark:divide-stone-700">
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 flex-shrink-0">
                                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-stone-100 dark:bg-stone-700">
                                                        {{ $user->initials() }}
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="font-medium text-stone-900 dark:text-stone-100">{{ $user->name }}</div>
                                                    <div class="text-stone-500 dark:text-stone-400">{{ $user->username }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-stone-500 dark:text-stone-400">
                                            <div class="text-stone-900 dark:text-stone-100">{{ $user->email }}</div>
                                            <div class="text-stone-500 dark:text-stone-400">{{ $user->email_verified_at ? __('Verified') : __('Not Verified') }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-stone-500 dark:text-stone-400">
                                            @if ($user->adminUser)
                                                <span class="inline-flex rounded-full bg-blue-100 px-2 text-xs font-semibold leading-5 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                                    {{ ucfirst($user->adminUser->role) }}
                                                </span>
                                            @elseif ($user->divisionInventoryManager)
                                                <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                                    {{ __('Inventory Manager') }}
                                                </span>
                                            @else
                                                <span class="inline-flex rounded-full bg-stone-100 px-2 text-xs font-semibold leading-5 text-stone-800 dark:bg-stone-700 dark:text-stone-300">
                                                    {{ __('Regular User') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-stone-500 dark:text-stone-400">
                                            @if ($user->adminUser)
                                                <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 {{ $user->adminUser->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' }}">
                                                    {{ $user->adminUser->is_active ? __('Active') : __('Inactive') }}
                                                </span>
                                            @else
                                                <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                                    {{ __('Active') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <div class="flex justify-end space-x-2">
                                                @adminpermission('view_users')
                                                    <flux:button variant="ghost" :href="route('admin.system.users.show', $user)" wire:navigate icon="eye">
                                                        {{ __('View') }}
                                                    </flux:button>
                                                @endadminpermission
                                                
                                                @adminpermission('edit_users')
                                                    <flux:button variant="ghost" :href="route('admin.system.users.edit', $user)" wire:navigate icon="edit">
                                                        {{ __('Edit') }}
                                                    </flux:button>
                                                @endadminpermission
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="whitespace-nowrap px-3 py-12 text-center text-sm text-stone-500 dark:text-stone-400">
                                            {{ __('No users found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </div>
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
                    if (newWidth > 40) { // min width 40px
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