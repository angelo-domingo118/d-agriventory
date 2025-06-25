<?php

use App\Models\User;
use App\Models\AdminUser;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    public User $user;
    public string $name = '';
    public string $email = '';
    public string $username = '';
    public ?string $password = null;
    public ?string $password_confirmation = null;
    public string $userType = 'regular';
    public string $adminRole = 'admin';
    public array $permissions = [];
    public bool $is_active = true;
    public ?int $division_id = null;
    
    public function mount(User $user): void
    {
        $this->user = $user->load(['adminUser', 'divisionInventoryManager']);
        $this->name = $user->name;
        $this->email = $user->email;
        $this->username = $user->username;
        
        if ($user->adminUser) {
            $this->userType = 'admin';
            $this->adminRole = $user->adminUser->role;
            $this->is_active = $user->adminUser->is_active;
            
            // Initialize permissions array
            foreach (AdminUser::ALLOWED_PERMISSIONS as $permission) {
                $this->permissions[$permission] = false;
            }
            
            // Load existing permissions if they exist
            if ($user->adminUser->permissions) {
                $existingPermissions = is_array($user->adminUser->permissions) 
                    ? $user->adminUser->permissions 
                    : json_decode($user->adminUser->permissions, true) ?? [];
                foreach ($existingPermissions as $permission => $value) {
                    if (in_array($permission, AdminUser::ALLOWED_PERMISSIONS)) {
                        $this->permissions[$permission] = (bool) $value;
                    }
                }
            }
        } elseif ($user->divisionInventoryManager) {
            $this->userType = 'inventory_manager';
            $this->division_id = $user->divisionInventoryManager->division_id;
        } else {
            $this->userType = 'regular';
        }
    }
    
    public function getAvailableDivisionsProperty()
    {
        return \App\Models\Division::orderBy('name')->get();
    }
    
    public function update(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user->id)],
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class)->ignore($this->user->id)],
            'userType' => ['required', 'in:regular,admin,inventory_manager'],
            'adminRole' => ['required_if:userType,admin', 'in:admin,super_admin,editor,viewer'],
            'is_active' => ['boolean'],
            'division_id' => ['required_if:userType,inventory_manager', 'nullable', 'exists:divisions,id'],
        ];
        
        if ($this->password) {
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }
        
        $this->validate($rules);
        
        // Detect changes for audit log
        $changes = [];
        
        // Compare base user attributes
        $oldAttributes = [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'username' => $this->user->username,
        ];
        
        $newAttributes = [
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
        ];
        
        // Find changes in basic attributes
        foreach (array_diff_assoc($newAttributes, $oldAttributes) as $key => $value) {
            $changes[$key] = [
                'old' => $oldAttributes[$key] ?? null,
                'new' => $value
            ];
        }
        
        // Update user model
        $this->user->update($newAttributes);
        
        // Update password if provided
        if ($this->password) {
            $this->user->update([
                'password' => Hash::make($this->password),
            ]);
            $changes['password'] = ['changed' => true];
        }
        
        // Handle user type change
        $oldUserType = $this->user->adminUser ? 'admin' : ($this->user->divisionInventoryManager ? 'inventory_manager' : 'regular');
        if ($oldUserType !== $this->userType) {
            $changes['user_type'] = ['old' => $oldUserType, 'new' => $this->userType];
            
            // Remove old user type associations
            if ($oldUserType === 'admin' && $this->user->adminUser) {
                $this->user->adminUser->delete();
                $changes['admin_removed'] = true;
            } elseif ($oldUserType === 'inventory_manager' && $this->user->divisionInventoryManager) {
                $this->user->divisionInventoryManager->delete();
                $changes['inventory_manager_removed'] = true;
            }
            
            // Add new user type associations
            if ($this->userType === 'admin') {
                // Create admin user
                $permissionsData = null;
                
                // For editors and viewers, store specific permissions
                if (!in_array($this->adminRole, ['admin', 'super_admin'])) {
                    $permissionsData = json_encode($this->permissions);
                }
                
                AdminUser::create([
                    'user_id' => $this->user->id,
                    'role' => $this->adminRole,
                    'permissions' => $permissionsData,
                    'is_active' => $this->is_active,
                    'last_login_at' => null,
                ]);
                $changes['admin_added'] = ['role' => $this->adminRole];
            } elseif ($this->userType === 'inventory_manager') {
                // Create division inventory manager
                \App\Models\DivisionInventoryManager::create([
                    'user_id' => $this->user->id,
                    'division_id' => $this->division_id,
                    'is_active' => true,
                ]);
                $changes['inventory_manager_added'] = ['division_id' => $this->division_id];
            }
        } elseif ($this->userType === 'admin' && $this->user->adminUser) {
            // Update existing admin user
            $permissionsData = null;
            
            // Check if role changed
            if ($this->user->adminUser->role !== $this->adminRole) {
                $changes['admin_role'] = ['old' => $this->user->adminUser->role, 'new' => $this->adminRole];
            }
            
            // Check if active status changed
            if ($this->user->adminUser->is_active !== $this->is_active) {
                $changes['admin_status'] = ['old' => $this->user->adminUser->is_active ? 'active' : 'inactive', 
                                          'new' => $this->is_active ? 'active' : 'inactive'];
            }
            
            // For editors and viewers, store specific permissions
            if (!in_array($this->adminRole, ['admin', 'super_admin'])) {
                $permissionsData = json_encode($this->permissions);
                $changes['permissions_updated'] = true;
            }
            
            $this->user->adminUser->update([
                'role' => $this->adminRole,
                'permissions' => $permissionsData,
                'is_active' => $this->is_active,
            ]);
        } elseif ($this->userType === 'inventory_manager' && $this->user->divisionInventoryManager) {
            // Update existing division inventory manager
            
            // Check if division changed
            if ($this->user->divisionInventoryManager->division_id != $this->division_id) {
                $changes['division_changed'] = [
                    'old' => $this->user->divisionInventoryManager->division_id,
                    'new' => $this->division_id
                ];
            }
            
            $this->user->divisionInventoryManager->update([
                'division_id' => $this->division_id,
            ]);
        }
        
        // Log audit if there are changes
        if (!empty($changes)) {
            $this->user->auditLogs()->create([
                'action' => 'updated',
                'auditable_id' => $this->user->id,
                'auditable_type' => User::class,
                'changes' => json_encode($changes),
            ]);
        }
        
        // Reload user to get updated relationships
        $this->user = $this->user->fresh(['adminUser', 'divisionInventoryManager']);
        
        // Show success message
        session()->flash('message', __('User updated successfully.'));
        
        // Redirect to user details page
        $this->redirect(route('admin.users.show', $this->user), navigate: true);
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
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('Edit User') }}</h1>
            <div class="flex space-x-2">
                <a href="{{ route('admin.users.show', $user) }}" wire:navigate
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ __('Cancel') }}
                </a>
                <a href="{{ route('admin.users.index') }}" wire:navigate
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ __('Back to Users') }}
                </a>
            </div>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <form wire:submit="update" class="space-y-6">
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <!-- Name -->
                        <div class="sm:col-span-3">
                            <label for="name" class="block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                            <div class="mt-1">
                                <input type="text" wire:model="name" id="name" autocomplete="name"
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                            @error('name') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>

                        <!-- Username -->
                        <div class="sm:col-span-3">
                            <label for="username" class="block text-sm font-medium text-gray-700">{{ __('Username') }}</label>
                            <div class="mt-1">
                                <input type="text" wire:model="username" id="username" autocomplete="username"
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                            @error('username') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>

                        <!-- Email -->
                        <div class="sm:col-span-4">
                            <label for="email" class="block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
                            <div class="mt-1">
                                <input type="email" wire:model="email" id="email" autocomplete="email"
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                            @error('email') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>

                        <!-- Password (optional on edit) -->
                        <div class="sm:col-span-3">
                            <label for="password" class="block text-sm font-medium text-gray-700">{{ __('New Password (optional)') }}</label>
                            <div class="mt-1">
                                <input type="password" wire:model="password" id="password" autocomplete="new-password"
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                            @error('password') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>

                        <!-- Password Confirmation -->
                        <div class="sm:col-span-3">
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">{{ __('Confirm New Password') }}</label>
                            <div class="mt-1">
                                <input type="password" wire:model="password_confirmation" id="password_confirmation" autocomplete="new-password"
                                       class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>

                        <!-- User Type -->
                        <div class="sm:col-span-6">
                            <label class="block text-sm font-medium text-gray-700">{{ __('User Type') }}</label>
                            <div class="mt-2 space-y-4">
                                <div class="flex items-center">
                                    <input wire:model="userType" id="userType-regular" type="radio" value="regular"
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                    <label for="userType-regular" class="ml-3 block text-sm font-medium text-gray-700">
                                        {{ __('Regular User') }}
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input wire:model="userType" id="userType-admin" type="radio" value="admin"
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                    <label for="userType-admin" class="ml-3 block text-sm font-medium text-gray-700">
                                        {{ __('Admin User') }}
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input wire:model="userType" id="userType-inventory-manager" type="radio" value="inventory_manager"
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                    <label for="userType-inventory-manager" class="ml-3 block text-sm font-medium text-gray-700">
                                        {{ __('Division Inventory Manager') }}
                                    </label>
                                </div>
                            </div>
                            @error('userType') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>

                        @if ($userType === 'admin')
                        <!-- Admin Role -->
                        <div class="sm:col-span-4">
                            <label for="adminRole" class="block text-sm font-medium text-gray-700">{{ __('Admin Role') }}</label>
                            <div class="mt-1">
                                <select wire:model.live="adminRole" id="adminRole"
                                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    <option value="admin">{{ __('Admin (Full Access)') }}</option>
                                    <option value="super_admin">{{ __('Super Admin (Full Access)') }}</option>
                                    <option value="editor">{{ __('Editor (Custom Permissions)') }}</option>
                                    <option value="viewer">{{ __('Viewer (Custom Permissions)') }}</option>
                                </select>
                            </div>
                            @error('adminRole') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                        
                        <!-- Active Status -->
                        <div class="sm:col-span-4">
                            <div class="flex items-center">
                                <input wire:model="is_active" id="is_active" type="checkbox" 
                                       class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                <label for="is_active" class="ml-3 block text-sm font-medium text-gray-700">
                                    {{ __('Active Account') }}
                                </label>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ __('Inactive accounts cannot log in to the system.') }}
                            </p>
                        </div>

                        @if (!in_array($adminRole, ['admin', 'super_admin']))
                        <!-- Permissions -->
                        <div class="sm:col-span-6">
                            <div class="flex justify-between items-center">
                                <label class="block text-sm font-medium text-gray-700">{{ __('Permissions') }}</label>
                                <button type="button" wire:click="toggleAllPermissions"
                                        class="text-sm text-indigo-600 hover:text-indigo-900">
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
                            <label for="division_id" class="block text-sm font-medium text-gray-700">{{ __('Division') }}</label>
                            <div class="mt-1">
                                <select wire:model="division_id" id="division_id"
                                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    <option value="">{{ __('-- Select Division --') }}</option>
                                    @foreach($this->availableDivisions as $division)
                                        <option value="{{ $division->id }}">{{ $division->name }} ({{ $division->code }})</option>
                                    @endforeach
                                </select>
                                @error('division_id') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ __('Assign this user as the inventory manager for the selected division.') }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                    <button type="submit"
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('Save Changes') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div> 