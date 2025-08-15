<?php

use App\Enums\User\Role;
use App\Models\User;
use App\Models\AdminUser;
use App\Models\Division;
use App\Models\DivisionInventoryManager;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    public User $user;
    public string $name = '';
    public string $username = '';
    public ?string $password = null;
    public ?string $password_confirmation = null;
    public string $userType = Role::REGULAR->value;
    public ?int $divisionId = null;
    public $divisions = [];

    public function mount(User $user): void
    {
        $this->user = $user->load(['adminUser', 'divisionInventoryManager']);
        $this->name = $user->name;
        $this->username = $user->username;
        $this->divisions = Division::all(['id', 'name']);

        if ($user->adminUser) {
            $this->userType = Role::ADMIN->value;
        } elseif ($user->divisionInventoryManager) {
            $this->userType = Role::INVENTORY_MANAGER->value;
            $this->divisionId = $user->divisionInventoryManager->division_id;
        } else {
            $this->userType = Role::REGULAR->value;
        }
    }

    public function update(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class)->ignore($this->user->id)],
            'userType' => ['required', 'string', Rule::in(Role::values())],
            'divisionId' => ['required_if:userType,' . Role::INVENTORY_MANAGER->value, 'nullable', 'exists:divisions,id'],
        ];

        if ($this->password) {
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }

        $validated = $this->validate($rules);

        $this->user->update([
            'name' => $validated['name'],
            'username' => $validated['username'],
        ]);

        if ($this->password) {
            $this->user->update([
                'password' => Hash::make($this->password),
            ]);
        }

        // Handle user type change
        if ($this->userType !== Role::ADMIN->value && $this->user->adminUser) {
            $this->user->adminUser->delete();
        }

        if ($this->userType !== Role::INVENTORY_MANAGER->value && $this->user->divisionInventoryManager) {
            $this->user->divisionInventoryManager->delete();
        }

        if ($this->userType === Role::ADMIN->value) {
            AdminUser::updateOrCreate(['user_id' => $this->user->id]);
        } elseif ($this->userType === Role::INVENTORY_MANAGER->value) {
            DivisionInventoryManager::updateOrCreate(['user_id' => $this->user->id], ['division_id' => $validated['divisionId']]);
        }

        session()->flash('message', __('User updated successfully.'));
        $this->redirectRoute('admin.system.users.show', ['user' => $this->user->id], navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-4">
        <!-- Breadcrumbs as Title -->
        <div>
            <flux:breadcrumbs class="text-2xl font-semibold">
                <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">System</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('admin.system.users.index')" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">User Management</flux:breadcrumbs.item>
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Edit User</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
        <div>
            <flux:button :href="route('admin.system.users.index')" wire:navigate variant="ghost">
                {{ __('Back to Users') }}
            </flux:button>
        </div>
    </div>

    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <form wire:submit="update" class="mx-auto max-w-xl">
            {{-- Name --}}
            <div class="mt-6">
                <flux:input wire:model="name" id="name" label="Name" required />
            </div>

            {{-- Username --}}
            <div class="mt-6">
                <flux:input wire:model="username" id="username" label="Username" required />
            </div>



            {{-- Password --}}
            <div class="mt-6">
                <flux:input wire:model="password" id="password" type="password" label="New Password"
                    hint="Leave blank to keep current password" />
            </div>

            {{-- Password Confirmation --}}
            <div class="mt-6">
                <flux:input wire:model.blur="password_confirmation" id="password_confirmation" type="password"
                    label="Confirm New Password" />
            </div>

            {{-- User Type --}}
            <div class="mt-6">
                <flux:select wire:model.live="userType" id="userType" label="User Type" required>
                    <option value="{{ Role::REGULAR->value }}">{{ __('Regular') }}</option>
                    <option value="{{ Role::ADMIN->value }}">{{ __('Admin') }}</option>
                    <option value="{{ Role::INVENTORY_MANAGER->value }}">{{ __('Inventory Manager') }}</option>
                </flux:select>
            </div>

            {{-- Division Selection --}}
            @if ($userType === Role::INVENTORY_MANAGER->value)
                <div class="mt-6">
                    <flux:select wire:model.live="divisionId" id="divisionId" label="Division"
                        :required="$userType === '{{ Role::INVENTORY_MANAGER->value }}'">
                        <option value="">{{ __('Select a division') }}</option>
                        @foreach ($divisions as $division)
                            <option value="{{ $division->id }}">{{ $division->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
            @endif

            <div class="mt-8">
                <flux:button variant="primary" type="submit">
                    {{ __('Update User') }}
                </flux:button>
                <flux:button :href="route('admin.system.users.index')" wire:navigate variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
            </div>
        </form>
    </div>
</div> 