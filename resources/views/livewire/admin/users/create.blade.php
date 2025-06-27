<?php

use App\Models\Division;
use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Validation\Rule;

new #[Layout('components.layouts.app')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $username = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $userType = 'admin';
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
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'username' => ['required', 'string', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'userType' => ['required', 'in:admin,inventory_manager'],
            'divisionId' => ['required_if:userType,inventory_manager', 'nullable', 'exists:divisions,id'],
        ]);

        $user = User::create($validated);

        if ($this->userType === 'admin') {
            $user->adminUser()->create([
                'role' => 'admin',
                'permissions' => null, // Admins have all permissions by default
            ]);
        }
        
        if ($this->userType === 'inventory_manager' && $this->divisionId) {
            $user->divisionInventoryManager()->create([
                'division_id' => $this->divisionId,
            ]);
        }

        $this->redirectRoute('admin.users.index', navigate: true);
    }
}; ?>

<div>
    <x-auth-header title="Create New User" />
    <form wire:submit="store" class="mx-auto max-w-xl">
        {{-- Name --}}
        <div class="mt-6">
            <flux:input wire:model="name" id="name" label="Name" required />
        </div>

        {{-- Username --}}
        <div class="mt-6">
            <flux:input wire:model="username" id="username" label="Username" required />
        </div>

        {{-- Email --}}
        <div class="mt-6">
            <flux:input wire:model="email" id="email" type="email" label="Email" required />
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
                <option value="admin">{{ __('Admin') }}</option>
                <option value="inventory_manager">{{ __('Division Inventory Manager') }}</option>
            </flux:select>
        </div>

        {{-- Division Selection --}}
        @if ($userType === 'inventory_manager')
        <div class="mt-6">
            <flux:select wire:model.live="divisionId" id="divisionId" label="Division"
                :required="$userType === 'inventory_manager'">
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