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
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="pb-5 border-b border-gray-200 sm:flex sm:items-center sm:justify-between">
            <h3 class="text-2xl leading-6 font-semibold text-gray-900">
                {{ __('User Management') }}
            </h3>
            <div class="mt-3 sm:mt-0 sm:ml-4">
                @adminpermission('create_users')
                <a href="{{ route('admin.users.create') }}" wire:navigate
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ __('Add User') }}
                </a>
                @endadminpermission
            </div>
        </div>

        <div class="mt-4 bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex flex-col md:flex-row gap-4 mb-4">
                    <div class="w-full md:w-1/2">
                        <label for="search" class="block text-sm font-medium text-gray-700">{{ __('Search') }}</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input type="text" wire:model.live.debounce.300ms="search" id="search" 
                                   class="block w-full pr-10 border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                                   placeholder="{{ __('Search by name, email, or username') }}">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        @error('search') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="w-full md:w-1/2">
                        <label for="role" class="block text-sm font-medium text-gray-700">{{ __('Filter by Role') }}</label>
                        <select wire:model.live="role" id="role"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">{{ __('All Roles') }}</option>
                            @foreach($this->roles as $roleOption)
                                <option value="{{ $roleOption }}">{{ ucfirst($roleOption) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('name')">
                                    {{ __('Name') }}
                                    @if ($sortField === 'name')
                                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('email')">
                                    {{ __('Email') }}
                                    @if ($sortField === 'email')
                                        <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('Role') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('Status') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($users as $user)
                                <tr wire:key="{{ $user->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center bg-gray-100 rounded-full">
                                                {{ $user->initials() }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $user->name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $user->username }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $user->email }}</div>
                                        <div class="text-sm text-gray-500">
                                            {{ $user->email_verified_at ? __('Verified') : __('Not Verified') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($user->adminUser)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                {{ ucfirst($user->adminUser->role) }}
                                            </span>
                                        @elseif ($user->divisionInventoryManager)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                {{ __('Inventory Manager') }}
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                {{ __('Regular User') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($user->adminUser)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->adminUser->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $user->adminUser->is_active ? __('Active') : __('Inactive') }}
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                {{ __('Active') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            @adminpermission('view_users')
                                            <a href="{{ route('admin.users.show', $user) }}" wire:navigate class="text-indigo-600 hover:text-indigo-900">
                                                {{ __('View') }}
                                            </a>
                                            @endadminpermission
                                            
                                            @adminpermission('edit_users')
                                            <a href="{{ route('admin.users.edit', $user) }}" wire:navigate class="text-green-600 hover:text-green-900">
                                                {{ __('Edit') }}
                                            </a>
                                            @endadminpermission
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-gray-500">
                                        {{ __('No users found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</div> 