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
    public string $density = 'spacious';
    public string $textOverflow = 'nowrap';
    
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
    
    public function resetSorting(): void
    {
        $this->sortField = 'name';
        $this->sortDirection = 'asc';
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

<div x-data="{ 
    showFilters: @entangle('showFilters'),
    ...tableSettings('users_settings')
}">
    <div x-data="tableResizer('users_column_widths', { name: 400, email: 350, role: 150, status: 150, actions: 120 })">
        <div class="flex items-center justify-between mb-4">
            <!-- Breadcrumbs as Title -->
            <div>
                <flux:breadcrumbs class="text-2xl font-semibold">
                    <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">System</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">User Management</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            </div>
            
            <!-- Action Buttons -->
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
                'text_overflow' => match($textOverflow) {
                    'wrap' => 'break-words',
                    'scroll' => 'whitespace-nowrap',
                    default => 'whitespace-nowrap truncate',
                },
                'table_wrapper' => match($textOverflow) {
                    'scroll' => 'overflow-x-auto',
                    default => 'overflow-x-auto',
                },
            ];
        @endphp
    
        <div class="mt-4 flow-root">
            <div class="-mx-4 -my-2 {{ $densityClasses['table_wrapper'] }} sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden rounded-lg shadow ring-1 ring-black ring-opacity-5 dark:ring-stone-700">
                        <table class="min-w-full divide-y divide-stone-300 dark:divide-stone-700 table-fixed">
                            <thead class="bg-stone-50 dark:bg-stone-800">
                                <tr class="divide-x divide-stone-200 dark:divide-stone-700">
                                    <th scope="col" :style="`width: ${columnWidths.name}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
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
                                    <th scope="col" :style="`width: ${columnWidths.email}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
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
                                    <th scope="col" :style="`width: ${columnWidths.role}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                        Role
                                        <div @mousedown="startResize($event, 'role')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.status}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                        Status
                                        <div @mousedown="startResize($event, 'status')" class="absolute top-0 right-0 z-10 w-1.5 h-full cursor-col-resize select-none"></div>
                                    </th>
                                    <th scope="col" :style="`width: ${columnWidths.actions}px`" class="relative {{ $densityClasses['table_header'] }} text-left {{ $densityClasses['text_header'] }} font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                                @forelse ($users as $user)
                                    <tr wire:key="{{ $user->id }}" class="divide-x divide-stone-200 dark:divide-stone-700 hover:bg-stone-50 dark:hover:bg-stone-800/50">
                                        <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }}" title="{{ $user->name }} ({{ $user->username }})">
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
                                        <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400" title="{{ $user->email }}">
                                            <div class="text-stone-900 dark:text-stone-100">{{ $user->email }}</div>
                                            <div class="text-stone-500 dark:text-stone-400">{{ $user->email_verified_at ? __('Verified') : __('Not Verified') }}</div>
                                        </td>
                                        <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400" title="@if ($user->adminUser){{ ucfirst($user->adminUser->role) }}@elseif ($user->divisionInventoryManager){{ __('Inventory Manager') }}@else{{ __('Regular User') }}@endif">
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
                                        <td class="{{ $densityClasses['text_overflow'] }} {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400" title="@if ($user->adminUser){{ $user->adminUser->is_active ? __('Active') : __('Inactive') }}@else{{ __('Active') }}@endif">
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
                                        <td class="relative whitespace-nowrap {{ $densityClasses['table_cell'] }} text-right {{ $densityClasses['text_base'] }} font-medium">
                                            <div class="flex justify-end space-x-2">
                                                @adminpermission('view_users')
                                                    <button 
                                                        class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50"
                                                        :href="route('admin.system.users.show', $user)" 
                                                        wire:navigate>
                                                        <x-flux::icon.eye class="mr-1.5 h-4 w-4" />
                                                        {{ __('View') }}
                                                    </button>
                                                @endadminpermission
                                                
                                                @adminpermission('edit_users')
                                                    <button 
                                                        class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50"
                                                        :href="route('admin.system.users.edit', $user)" 
                                                        wire:navigate>
                                                        <x-flux::icon.pencil class="mr-1.5 h-4 w-4" />
                                                        {{ __('Edit') }}
                                                    </button>
                                                @endadminpermission
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="whitespace-nowrap {{ $densityClasses['table_cell'] }} text-center {{ $densityClasses['text_base'] }} text-stone-500 dark:text-stone-400">
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

        Alpine.data('tableSettings', (storageKey) => ({
            init() {
                const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
                if (saved.density) this.$wire.density = saved.density;
                if (saved.perPage) this.$wire.perPage = saved.perPage;
                if (saved.textOverflow) this.$wire.textOverflow = saved.textOverflow;
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