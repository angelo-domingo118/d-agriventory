<?php

use App\Models\Division;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public ?Employee $editing = null;
    public bool $showCreateModal = false;
    public string $search = '';

    // For creating/editing user credentials
    public string $username = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $create_user_account = false;

    public function mount()
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function getEmployeesProperty()
    {
        return Employee::with(['user', 'position', 'division'])
            ->when($this->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%")
                    ->orWhereHas('position', fn ($q) => $q->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('division', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate(10);
    }

    public function getPositionsProperty()
    {
        return Position::orderBy('title')->get();
    }

    public function getDivisionsProperty()
    {
        return Division::orderBy('name')->get();
    }

    public function newEmployee(): void
    {
        $this->editing = new Employee();
        $this->username = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->create_user_account = false;
        $this->showCreateModal = true;
    }

    public function edit(Employee $employee): void
    {
        $this->editing = $employee;
        $this->username = $employee->user?->username ?? '';
        $this->email = $employee->user?->email ?? '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->create_user_account = $employee->user_id !== null;
        $this->showCreateModal = true;
    }

    public function save(): void
    {
        $rules = [
            'editing.name' => ['required', 'string', 'max:255'],
            'editing.employee_number' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_number')->ignore($this->editing->id)],
            'editing.position_id' => ['required', 'integer', Rule::exists('positions', 'id')],
            'editing.division_id' => ['required', 'integer', Rule::exists('divisions', 'id')],
            'create_user_account' => ['boolean'],
        ];

        if ($this->create_user_account) {
            $userId = $this->editing->user_id;
            $rules = array_merge($rules, [
                'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
                'password' => [$userId ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            ]);
        }

        $this->validate($rules);

        DB::transaction(function () {
            $user = null;
            if ($this->create_user_account) {
                $userData = [
                    'name' => $this->editing->name,
                    'username' => $this->username,
                    'email' => $this->email,
                ];
                if ($this->password) {
                    $userData['password'] = Hash::make($this->password);
                }

                $user = User::updateOrCreate(
                    ['id' => $this->editing->user_id],
                    $userData
                );
                $this->editing->user_id = $user->id;
            } elseif ($this->editing->user_id) {
                // If checkbox is unchecked, disassociate user but don't delete
                $this->editing->user_id = null;
            }

            $this->editing->save();
        });

        $this->showCreateModal = false;
        $this->dispatch('employee-saved');
        session()->flash('success', 'Employee saved successfully.');
    }

    public function with(): array
    {
        return [
            'employees' => $this->employees,
            'positions' => $this->positions,
            'divisions' => $this->divisions,
        ];
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-stone-700 dark:text-stone-200">Employees</h2>
        <div class="flex items-center gap-x-4">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search employees..." />
            <flux:button wire:click="newEmployee" variant="primary">New Employee</flux:button>
        </div>
    </div>

    <div class="mt-4 overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
        <table class="min-w-full divide-y divide-stone-200 dark:divide-stone-700">
            <thead class="bg-stone-50 dark:bg-stone-800">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Employee #</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Position</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-500 dark:text-stone-400">Division</th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Edit</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-200 bg-white dark:divide-stone-800 dark:bg-stone-900">
                @forelse($employees as $employee)
                    <tr wire:key="employee-{{ $employee->id }}">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-stone-900 dark:text-stone-100">{{ $employee->name }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">{{ $employee->employee_number }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">{{ $employee->position?->title ?? 'N/A' }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-stone-500 dark:text-stone-400">{{ $employee->division?->name ?? 'N/A' }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <flux:button wire:click="edit({{ $employee->id }})" variant="ghost" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-200">Edit</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-stone-500 dark:text-stone-400">
                            No employees found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $employees->links() }}
    </div>

    <!-- Create/Edit Modal -->
    @if($editing)
        <flux:modal :show="$showCreateModal" max-width="2xl" @close="$set('showCreateModal', false)">
            <form wire:submit.prevent="save">
                <x-slot:title>
                    {{ $editing->exists ? 'Edit' : 'Create' }} Employee
                </x-slot:title>

                <div class="space-y-6 p-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <flux:input wire:model="editing.name" label="Full Name" required />
                        <flux:input wire:model="editing.employee_number" label="Employee Number" required />
                        <flux:select wire:model="editing.position_id" label="Position" required>
                            <option value="">Select a position</option>
                            @foreach($positions as $position)
                                <option value="{{ $position->id }}">{{ $position->title }}</option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model="editing.division_id" label="Division/Office" required>
                            <option value="">Select a division</option>
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div class="relative">
                      <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-stone-300 dark:border-stone-600"></div>
                      </div>
                      <div class="relative flex justify-center">
                        <span class="bg-white px-3 text-base font-semibold leading-6 text-stone-900 dark:bg-stone-800 dark:text-stone-100">User Account</span>
                      </div>
                    </div>
                    <div x-data="{ showCredentials: @entangle('create_user_account').live }" class="space-y-4">
                        <flux:checkbox x-model="showCredentials" label="Create or link a user account for this employee" />

                        <div x-show="showCredentials" x-collapse.duration.300ms>
                            <div class="grid grid-cols-1 gap-6 rounded-md border border-stone-200 bg-stone-50 p-4 dark:border-stone-700 dark:bg-stone-800/50 sm:grid-cols-2">
                                <flux:input wire:model="username" label="Username" />
                                <flux:input wire:model="email" label="Email Address" type="email" />
                                <flux:input wire:model="password" label="Password" type="password" hint="{{ $editing->exists && $editing->user_id ? 'Leave blank to keep current password' : '' }}" />
                                <flux:input wire:model="password_confirmation" label="Confirm Password" type="password" />
                            </div>
                        </div>
                    </div>
                </div>

                <x-slot:footer>
                    <div class="flex justify-end gap-x-4">
                        <flux:button variant="ghost" @click="$set('showCreateModal', false)">Cancel</flux:button>
                        <flux:button type="submit" variant="primary">Save</flux:button>
                    </div>
                </x-slot:footer>
            </form>
        </flux:modal>
    @endif
</div> 