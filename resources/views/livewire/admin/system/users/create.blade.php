<?php

use App\Enums\User\Role;
use App\Models\Division;
use App\Models\User;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Validation\Rule;

new #[Layout('components.layouts.app')] class extends Component {
    public string $name = '';
    public string $username = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $userType = Role::ADMIN->value;
    public ?int $divisionId = null;

    public $divisions = [];

    public function mount(): void
    {
        $this->divisions = Division::all(['id', 'name']);
    }

    public function store(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'userType' => ['required', Rule::in(Role::values())],
            'divisionId' => ['required_if:userType,' . Role::INVENTORY_MANAGER->value, 'nullable', 'exists:divisions,id'],
        ]);

        $user = User::create($validated);

        if ($this->userType === Role::ADMIN->value) {
            $user->adminUser()->create([
                'role' => 'admin',
                'permissions' => null, // Admins have all permissions by default
            ]);
        }
        
        if ($this->userType === Role::INVENTORY_MANAGER->value && $this->divisionId) {
            $user->divisionInventoryManager()->create([
                'division_id' => $this->divisionId,
            ]);
        }

        $this->redirectRoute('admin.system.users.index', navigate: true);
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
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Create New User</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
    </div>

    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <form wire:submit="store" class="mx-auto max-w-xl">
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
                <flux:input wire:model="password" id="password" type="password" label="Password" required />
            </div>

            {{-- Password Confirmation --}}
            <div class="mt-6">
                <flux:input wire:model.blur="password_confirmation" id="password_confirmation" type="password"
                    label="Confirm Password" required />
            </div>

            {{-- User Type Selection --}}
            <div class="mt-6">
                <flux:select wire:model.live="userType" id="userType" label="User Type" required>
                    <option value="{{ Role::ADMIN->value }}">{{ __('Admin') }}</option>
                    <option value="{{ Role::INVENTORY_MANAGER->value }}">{{ __('Division Inventory Manager') }}</option>
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
                    {{ __('Create User') }}
                </flux:button>
            </div>
        </form>
    </div>
</div> 