<?php

use App\Models\Division;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

new #[Layout('components.layouts.app')] class extends Component {
    public Employee $employee;
    public string $username = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $create_user_account = false;

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        $this->employee = new Employee();
    }

    public function save(): void
    {
        $rules = [
            'employee.name' => ['required', 'string', 'max:255'],
            'employee.employee_number' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_number')],
            'employee.position_id' => ['required', 'integer', Rule::exists('positions', 'id')],
            'employee.division_id' => ['required', 'integer', Rule::exists('divisions', 'id')],
            'create_user_account' => ['boolean'],
        ];

        if ($this->create_user_account) {
            $rules = array_merge($rules, [
                'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);
        }

        $this->validate($rules);

        DB::transaction(function () {
            if ($this->create_user_account) {
                $user = User::create([
                    'name' => $this->employee->name,
                    'username' => $this->username,
                    'email' => $this->email,
                    'password' => Hash::make($this->password),
                ]);
                $this->employee->user_id = $user->id;
            }
            $this->employee->save();
        });

        session()->flash('success', 'Employee created successfully.');
        $this->redirectRoute('admin.data.employees-and-divisions.index', ['currentTab' => 'employees']);
    }

    #[Computed]
    public function positions()
    {
        return Position::orderBy('title')->get();
    }

    #[Computed]
    public function divisions()
    {
        return Division::orderBy('name')->get();
    }

    public function with(): array
    {
        return [
            'positions' => $this->positions,
            'divisions' => $this->divisions,
        ];
    }
}; ?>

<div>
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Create Employee
        </h1>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            Add a new employee and optionally create a user account for them.
        </p>
    </div>

    <div class="mb-4 flex items-center gap-x-4">
        <a href="{{ route('admin.data.employees-and-divisions') }}"
            class="flex items-center gap-x-2 text-sm text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100">
            <x-flux::icon.arrow-left class="h-4 w-4" />
            Back
        </a>
    </div>
    <div class="space-y-8">
        <x-admin.section>
            <x-slot:title>Employee Information</x-slot:title>
            <x-slot:description>
                Add the employee's basic details.
            </x-slot:description>
            <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                <div class="sm:col-span-4">
                    <flux:input wire:model="employee.name" label="Full Name" required />
                </div>

                <div class="sm:col-span-4">
                    <flux:input wire:model="employee.employee_number" label="Employee Number" required />
                </div>

                <div class="sm:col-span-3">
                    <flux:select wire:model="employee.position_id" label="Position" :options="$this->positions"
                        placeholder="Select a position" option-value="id" option-label="title" required />
                </div>

                <div class="sm:col-span-3">
                    <flux:select wire:model="employee.division_id" label="Division" :options="$this->divisions"
                        placeholder="Select a division" option-value="id" option-label="name" required />
                </div>
            </div>
        </x-admin.section>

        <x-admin.section>
            <x-slot:title>User Account</x-slot:title>
            <x-slot:description>
                Optionally create a user account for this employee to log in to the
                system.
            </x-slot:description>
            <div class="max-w-2xl space-y-6">
                <flux:checkbox wire:model.live="create_user_account" label="Create user account for this employee" />

                @if ($create_user_account)
                    <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                        <div class="sm:col-span-4">
                            <flux:input wire:model="username" label="Username" required />
                        </div>

                        <div class="sm:col-span-4">
                            <flux:input wire:model="email" type="email" label="Email address" required />
                        </div>

                        <div class="sm:col-span-4">
                            <flux:input wire:model="password" type="password" label="Password"
                                hint="Minimum 8 characters required." viewable />
                        </div>
                        <div class="sm:col-span-4">
                            <flux:input wire:model="password_confirmation" type="password" label="Confirm Password"
                                viewable />
                        </div>
                    </div>
                @endif
            </div>
        </x-admin.section>

        <div class="mt-8 flex justify-end gap-x-4">
            <a href="{{ route('admin.data.employees-and-divisions') }}"
                class="text-sm text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100">Cancel</a>
            <flux:button type="submit" variant="primary">Create Employee</flux:button>
        </div>
    </div>
</div> 