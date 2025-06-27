<?php

use App\Models\User;
use App\Models\AdminUser;
use App\Models\Division;
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
    public ?int $divisionId = null;
    public $divisions = [];

    public function mount(User $user): void
    {
        $this->user = $user->load(['adminUser', 'divisionInventoryManager']);
        $this->name = $user->name;
        $this->email = $user->email;
        $this->username = $user->username;
        $this->divisions = Division::all(['id', 'name']);

        if ($user->adminUser) {
            $this->userType = 'admin';
        } elseif ($user->divisionInventoryManager) {
            $this->userType = 'inventory_manager';
            $this->divisionId = $user->divisionInventoryManager->division_id;
        } else {
            $this->userType = 'regular';
        }
    }

    public function update(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user->id)],
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class)->ignore($this->user->id)],
            'divisionId' => ['required_if:userType,inventory_manager', 'nullable', 'exists:divisions,id'],
        ];

        if ($this->password) {
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }

        $validated = $this->validate($rules);

        $this->user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
        ]);

        if ($this->password) {
            $this->user->update([
                'password' => Hash::make($this->password),
            ]);
        }

        if ($this->userType === 'inventory_manager') {
            $this->user->divisionInventoryManager()->update([
                'division_id' => $this->divisionId,
            ]);
        }

        session()->flash('message', __('User updated successfully.'));
        $this->redirectRoute('admin.users.show', ['user' => $this->user->id], navigate: true);
    }
}; ?>

<div>
    <x-auth-header title="Edit User" />
    <form wire:submit="update" class="mx-auto max-w-xl">
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
            <flux:input wire:model="password" id="password" type="password" label="New Password"
                hint="Leave blank to keep current password" />
        </div>

        {{-- Password Confirmation --}}
        <div class="mt-6">
            <flux:input wire:model.blur="password_confirmation" id="password_confirmation" type="password"
                label="Confirm New Password" />
        </div>

        {{-- User Type (Read-only) --}}
        <div class="mt-6">
            <flux:input id="userType" label="User Type" :value="ucfirst(str_replace('_', ' ', $userType))" disabled />
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
                {{ __('Update User') }}
            </flux:button>
            <flux:button :href="route('admin.users.index')" wire:navigate variant="ghost">
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</div> 