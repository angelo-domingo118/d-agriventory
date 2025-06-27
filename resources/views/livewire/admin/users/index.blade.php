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
    
    public string $role = '';
    public string $sortField = 'name';
    public string $sortDirection = 'asc';
    
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
            ->paginate(10);
            
        return view('livewire.admin.users.index', [
            'users' => $users,
        ]);
    }
}; ?>

<div>
    <x-admin.layout heading="User Management">
        <x-slot name="header">
            @adminpermission('create_users')
                <flux:button :href="route('admin.users.create')" wire:navigate variant="primary">
                    {{ __('Add User') }}
                </flux:button>
            @endadminpermission
        </x-slot>

        <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-700 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white dark:bg-stone-900 border-b border-stone-200 dark:border-stone-700">
                <div class="flex flex-col md:flex-row gap-4 mb-4">
                    <div class="w-full md:w-1/2">
                        <flux:input wire:model.live.debounce.300ms="search" id="search" 
                                   :label="__('Search')"
                                   icon="magnifying-glass"
                                   placeholder="{{ __('Search by name, email, or username') }}" />
                        @error('search') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="w-full md:w-1/2">
                        <flux:select wire:model.live="role" id="role" :label="__('Filter by Role')">
                            <option value="">{{ __('All Roles') }}</option>
                            @foreach($this->roles as $roleOption)
                                <option value="{{ $roleOption }}">{{ ucfirst(str_replace('_', ' ', $roleOption)) }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
                        <thead class="bg-stone-50 dark:bg-stone-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider cursor-pointer" wire:click="sortBy('name')">
                                    {{ __('Name') }}
                                    @if ($sortField === 'name')
                                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider cursor-pointer" wire:click="sortBy('email')">
                                    {{ __('Email') }}
                                    @if ($sortField === 'email')
                                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                                    {{ __('Role') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                                    {{ __('Status') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-stone-900 divide-y divide-stone-200 dark:divide-stone-700">
                            @forelse ($users as $user)
                                <tr wire:key="{{ $user->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center bg-stone-100 dark:bg-stone-700 rounded-full">
                                                {{ $user->initials() }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-stone-900 dark:text-stone-100">
                                                    {{ $user->name }}
                                                </div>
                                                <div class="text-sm text-stone-500 dark:text-stone-400">
                                                    {{ $user->username }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-stone-900 dark:text-stone-100">{{ $user->email }}</div>
                                        <div class="text-sm text-stone-500 dark:text-stone-400">
                                            {{ $user->email_verified_at ? __('Verified') : __('Not Verified') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($user->adminUser)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">
                                                {{ ucfirst($user->adminUser->role) }}
                                            </span>
                                        @elseif ($user->divisionInventoryManager)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300">
                                                {{ __('Inventory Manager') }}
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-stone-100 dark:bg-stone-700 text-stone-800 dark:text-stone-300">
                                                {{ __('Regular User') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($user->adminUser)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->adminUser->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' }}">
                                                {{ $user->adminUser->is_active ? __('Active') : __('Inactive') }}
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300">
                                                {{ __('Active') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            @adminpermission('view_users')
                                                <flux:button variant="ghost" :href="route('admin.users.show', $user)" wire:navigate>
                                                    {{ __('View') }}
                                                </flux:button>
                                            @endadminpermission
                                            
                                            @adminpermission('edit_users')
                                                <flux:button variant="ghost" :href="route('admin.users.edit', $user)" wire:navigate>
                                                    {{ __('Edit') }}
                                                </flux:button>
                                            @endadminpermission
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-stone-500 dark:text-stone-400">
                                        {{ __('No users found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-white dark:bg-stone-900">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </x-admin.layout>
</div> 