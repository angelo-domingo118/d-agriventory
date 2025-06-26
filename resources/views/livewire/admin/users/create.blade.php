<?php

use App\Models\User;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $username = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $userType = 'regular';
    public string $adminRole = 'admin';
    public array $permissions = [];
    public ?int $division_id = null;
    
    public function mount(): void
    {
        // Initialize permissions array with all permissions set to false
        foreach (AdminUser::ALLOWED_PERMISSIONS as $permission) {
            $this->permissions[$permission] = false;
        }
    }
    
    public function getAvailableDivisionsProperty()
    {
        return \App\Models\Division::orderBy('name')->get();
    }
    
    public function create(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'username' => ['required', 'string', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'userType' => ['required', 'in:regular,admin,inventory_manager'],
            'adminRole' => ['required_if:userType,admin', 'in:admin,super_admin,editor,viewer'],
            'division_id' => ['required_if:userType,inventory_manager', 'nullable', 'exists:divisions,id'],
        ]);
        
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'password' => Hash::make($this->password),
            'email_verified_at' => now(), // Auto-verify admin-created users
        ]);
        
        if ($this->userType === 'admin') {
            // Create admin user
            $permissionsData = null;
            
            // For editors and viewers, store specific permissions
            if (!in_array($this->adminRole, ['admin', 'super_admin'])) {
                $permissionsData = json_encode($this->permissions);
            }
            
            AdminUser::create([
                'user_id' => $user->id,
                'role' => $this->adminRole,
                'permissions' => $permissionsData,
                'is_active' => true,
                'last_login_at' => null,
            ]);
        } elseif ($this->userType === 'inventory_manager') {
            // Create division inventory manager
            \App\Models\DivisionInventoryManager::create([
                'user_id' => $user->id,
                'division_id' => $this->division_id,
                'is_active' => true,
            ]);
        }
        
        // Log audit
        $user->auditLogs()->create([
            'action' => 'created',
            'auditable_id' => $user->id,
            'auditable_type' => User::class,
            'changes' => json_encode(['name' => $this->name, 'email' => $this->email, 'user_type' => $this->userType]),
        ]);
        
        $this->dispatch('user-created');
        
        $this->redirect(route('admin.users.show', $user), navigate: true);
    }
    
    public function toggleAllPermissions(): void
    {
        $allChecked = collect($this->permissions)->filter()->count() === count(AdminUser::ALLOWED_PERMISSIONS);
        
        foreach (AdminUser::ALLOWED_PERMISSIONS as $permission) {
            $this->permissions[$permission] = !$allChecked;
        }
    }
}; ?>

<div>
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="mb-5 flex justify-between items-center">
            <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">{{ __('Create New User') }}</h1>
            <div>
                <a href="{{ route('admin.users.index') }}" wire:navigate
                   class="inline-flex items-center px-4 py-2 border border-stone-300 dark:border-stone-700 rounded-md shadow-sm text-sm font-medium text-stone-700 dark:text-stone-200 bg-white dark:bg-stone-800 hover:bg-stone-50 dark:hover:bg-stone-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    {{ __('Back to Users') }}
                </a>
            </div>
        </div>

        <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-700 shadow overflow-hidden sm:rounded-lg">
            <form wire:submit="create" class="space-y-6">
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <!-- Name -->
                        <div class="sm:col-span-3">
                            <label for="name" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('Name') }}</label>
                            <div class="mt-1">
                                <input type="text" wire:model="name" id="name" autocomplete="name"
                                       class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-stone-300 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 rounded-md">
                            </div>
                            @error('name') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>

                        <!-- Username -->
                        <div class="sm:col-span-3">
                            <label for="username" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('Username') }}</label>
                            <div class="mt-1">
                                <input type="text" wire:model="username" id="username" autocomplete="username"
                                       class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-stone-300 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 rounded-md">
                            </div>
                            @error('username') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>

                        <!-- Email -->
                        <div class="sm:col-span-4">
                            <label for="email" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('Email') }}</label>
                            <div class="mt-1">
                                <input type="email" wire:model="email" id="email" autocomplete="email"
                                       class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-stone-300 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 rounded-md">
                            </div>
                            @error('email') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>

                        <!-- Password -->
                        <div class="sm:col-span-3">
                            <label for="password" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('Password') }}</label>
                            <div class="mt-1">
                                <input type="password" wire:model="password" id="password" autocomplete="new-password"
                                       class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-stone-300 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 rounded-md">
                            </div>
                            @error('password') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>

                        <!-- Password Confirmation -->
                        <div class="sm:col-span-3">
                            <label for="password_confirmation" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('Confirm Password') }}</label>
                            <div class="mt-1">
                                <input type="password" wire:model="password_confirmation" id="password_confirmation" autocomplete="new-password"
                                       class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-stone-300 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 rounded-md">
                            </div>
                        </div>

                        <!-- User Type -->
                        <div class="sm:col-span-6">
                            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('User Type') }}</label>
                            <div class="mt-2 space-y-4">
                                <div class="flex items-center">
                                    <input wire:model.live="userType" id="userType-regular" type="radio" value="regular"
                                           class="focus:ring-green-500 h-4 w-4 text-green-600 border-stone-300">
                                    <label for="userType-regular" class="ml-3 block text-sm font-medium text-stone-700 dark:text-stone-300">
                                        {{ __('Regular User') }}
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input wire:model.live="userType" id="userType-admin" type="radio" value="admin"
                                           class="focus:ring-green-500 h-4 w-4 text-green-600 border-stone-300">
                                    <label for="userType-admin" class="ml-3 block text-sm font-medium text-stone-700 dark:text-stone-300">
                                        {{ __('Admin User') }}
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input wire:model.live="userType" id="userType-inventory-manager" type="radio" value="inventory_manager"
                                           class="focus:ring-green-500 h-4 w-4 text-green-600 border-stone-300">
                                    <label for="userType-inventory-manager" class="ml-3 block text-sm font-medium text-stone-700 dark:text-stone-300">
                                        {{ __('Division Inventory Manager') }}
                                    </label>
                                </div>
                            </div>
                            @error('userType') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>

                        @if ($userType === 'admin')
                        <!-- Admin Role -->
                        <div class="sm:col-span-4">
                            <label for="adminRole" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('Admin Role') }}</label>
                            <div class="mt-1">
                                <select wire:model.live="adminRole" id="adminRole"
                                        class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-stone-300 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 rounded-md">
                                    <option value="admin">{{ __('Admin (Full Access)') }}</option>
                                    <option value="super_admin">{{ __('Super Admin (Full Access)') }}</option>
                                    <option value="editor">{{ __('Editor (Custom Permissions)') }}</option>
                                    <option value="viewer">{{ __('Viewer (Custom Permissions)') }}</option>
                                </select>
                            </div>
                            @error('adminRole') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>

                        @if (!in_array($adminRole, ['admin', 'super_admin']))
                        <!-- Permissions -->
                        <div class="sm:col-span-6">
                            <div class="flex justify-between items-center">
                                <label class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('Permissions') }}</label>
                                <button type="button" wire:click="toggleAllPermissions"
                                        class="text-sm text-green-600 hover:text-green-900">
                                    {{ collect($permissions)->filter()->count() === count(AdminUser::ALLOWED_PERMISSIONS) 
                                        ? __('Uncheck All') 
                                        : __('Check All') }}
                                </button>
                            </div>
                            
                            <x-admin.permissions-manager :permissions="$permissions" />
                        </div>
                        @endif
                        @endif

                        @if ($userType === 'inventory_manager')
                        <!-- Division Selection -->
                        <div class="sm:col-span-4">
                            <label for="division_id" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('Division') }}</label>
                            <div class="mt-1">
                                <select wire:model="division_id" id="division_id"
                                        class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-stone-300 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 rounded-md">
                                    <option value="">{{ __('-- Select Division --') }}</option>
                                    @foreach($this->availableDivisions as $division)
                                        <option value="{{ $division->id }}">{{ $division->name }} ({{ $division->code }})</option>
                                    @endforeach
                                </select>
                                @error('division_id') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                            </div>
                            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                                {{ __('Assign this user as the inventory manager for the selected division.') }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="px-4 py-3 bg-stone-50 dark:bg-stone-800 text-right sm:px-6">
                    <button type="submit"
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        {{ __('Create User') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div> 