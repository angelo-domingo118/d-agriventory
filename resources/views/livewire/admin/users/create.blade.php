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
                <flux:button :href="route('admin.users.index')" wire:navigate variant="ghost">
                    {{ __('Back to Users') }}
                </flux:button>
            </div>
        </div>

        <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-700 shadow overflow-hidden sm:rounded-lg">
            <form wire:submit="create" class="space-y-6">
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

                        <!-- Password -->
                        <div class="sm:col-span-3">
                            <flux:input wire:model="password" id="password" type="password" :label="__('Password')" autocomplete="new-password" />
                            @error('password') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>

                        <!-- Password Confirmation -->
                        <div class="sm:col-span-3">
                            <flux:input wire:model="password_confirmation" id="password_confirmation" type="password" :label="__('Confirm Password')" autocomplete="new-password" />
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
                        {{ __('Create User') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div> 