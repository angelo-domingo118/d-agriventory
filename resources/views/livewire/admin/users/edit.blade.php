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
            <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">{{ __('Edit User') }}</h1>
            <div class="flex space-x-2">
                <flux:button :href="route('admin.users.show', $user)" wire:navigate variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button :href="route('admin.users.index')" wire:navigate variant="ghost">
                    {{ __('Back to Users') }}
                </flux:button>
            </div>
        </div>

        <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-700 shadow overflow-hidden sm:rounded-lg">
            <form wire:submit="update" class="space-y-6">
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <!-- Name -->
                        <div class="sm:col-span-3">
                            <flux:input wire:model="name" id="name" :label="__('Name')" autocomplete="name" />
                            @error('name') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>

                        <!-- Username -->
                        <div class="sm:col-span-3">
                            <flux:input wire:model="username" id="username" :label="__('Username')" autocomplete="username" />
                            @error('username') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>

                        <!-- Email -->
                        <div class="sm:col-span-4">
                            <flux:input wire:model="email" id="email" type="email" :label="__('Email')" autocomplete="email" />
                            @error('email') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>

                        <!-- Password (optional on edit) -->
                        <div class="sm:col-span-3">
                            <flux:input wire:model="password" id="password" type="password" :label="__('New Password (optional)')" autocomplete="new-password" />
                            @error('password') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>

                        <!-- Password Confirmation -->
                        <div class="sm:col-span-3">
                            <flux:input wire:model="password_confirmation" id="password_confirmation" type="password" :label="__('Confirm New Password')" autocomplete="new-password" />
                        </div>

                        <!-- User Type -->
                        <div class="sm:col-span-6">
                            <flux:radio.group wire:model.live="userType" :label="__('User Type')">
                                <flux:radio value="regular" :label="__('Regular User')" />
                                <flux:radio value="admin" :label="__('Admin User')" />
                                <flux:radio value="inventory_manager" :label="__('Division Inventory Manager')" />
                            </flux:radio.group>
                            @error('userType') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>

                        @if ($userType === 'admin')
                        <!-- Admin Role -->
                        <div class="sm:col-span-4">
                            <flux:select wire:model.live="adminRole" id="adminRole" :label="__('Admin Role')">
                                <option value="admin">{{ __('Admin (Full Access)') }}</option>
                                <option value="super_admin">{{ __('Super Admin (Full Access)') }}</option>
                                <option value="editor">{{ __('Editor (Custom Permissions)') }}</option>
                                <option value="viewer">{{ __('Viewer (Custom Permissions)') }}</option>
                            </flux:select>
                            @error('adminRole') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                        
                        <!-- Active Status -->
                        <div class="sm:col-span-4">
                            <flux:checkbox wire:model="is_active" id="is_active" :label="__('Active Account')" />
                            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                                {{ __('Inactive accounts cannot log in to the system.') }}
                            </p>
                        </div>

                        @if (!in_array($adminRole, ['admin', 'super_admin']))
                        <!-- Permissions -->
                        <div class="sm:col-span-6">
                            <div class="flex justify-between items-center">
                                <label class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('Permissions') }}</label>
                                <flux:button type="button" wire:click="toggleAllPermissions" variant="ghost">
                                    {{ collect($permissions)->filter()->count() === count(AdminUser::ALLOWED_PERMISSIONS) 
                                        ? __('Uncheck All') 
                                        : __('Check All') }}
                                </flux:button>
                            </div>
                            
                            <x-admin.permissions-manager :permissions="$permissions" />
                        </div>
                        @endif
                        @endif

                        @if ($userType === 'inventory_manager')
                        <!-- Division Selection -->
                        <div class="sm:col-span-4">
                            <flux:select wire:model="division_id" id="division_id" :label="__('Division')">
                                <option value="">{{ __('-- Select Division --') }}</option>
                                @foreach($this->availableDivisions as $division)
                                    <option value="{{ $division->id }}">{{ $division->name }} ({{ $division->code }})</option>
                                @endforeach
                            </flux:select>
                            @error('division_id') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                                {{ __('Assign this user as the inventory manager for the selected division.') }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="px-4 py-3 bg-stone-50 dark:bg-stone-800 text-right sm:px-6">
                    <flux:button type="submit" variant="primary">
                        {{ __('Save Changes') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div> 