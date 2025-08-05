<?php

use App\Models\Division;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public Employee $employee;
    public string $name;
    public string $employee_number;
    public ?int $position_id;
    public ?int $division_id;
    public string $previousView = 'tree';

    public function mount(Employee $employee): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        $this->employee = $employee;
        $this->name = $employee->name;
        $this->employee_number = $employee->employee_number;
        $this->position_id = $employee->position_id;
        $this->division_id = $employee->division_id;
        
        $this->previousView = request()->query('view', 'tree');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'employee_number' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_number')->ignore($this->employee->id)],
            'position_id' => ['required', 'integer', Rule::exists('positions', 'id')],
            'division_id' => ['required', 'integer', Rule::exists('divisions', 'id')],
        ]);

        $this->employee->update($validated);

        session()->flash('success', 'Employee updated successfully.');
        $this->redirect(route('admin.data.employees-and-divisions', ['currentTab' => 'employees', 'view' => $this->previousView]), navigate: true);
    }

    public function delete(): void
    {
        // Check if the employee has any inventory items assigned
        if ($this->employee->icsNumbers()->exists() || $this->employee->parNumbers()->exists() || $this->employee->assignedIdrNumbers()->exists() || $this->employee->approvedIdrNumbers()->exists()) {
            session()->flash('error', 'Cannot delete an employee that has inventory items assigned.');
            return;
        }

        $this->employee->delete();

        session()->flash('success', 'Employee deleted successfully.');
        $this->redirect(route('admin.data.employees-and-divisions', ['currentTab' => 'employees', 'view' => $this->previousView]), navigate: true);
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

<form wire:submit="save">
    <!-- Breadcrumbs -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <flux:breadcrumbs class="text-2xl font-semibold">
                <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Data</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('admin.data.employees-and-divisions', ['currentTab' => 'employees'])" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Employees & Divisions</flux:breadcrumbs.item>
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Edit Employee</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
    </div>

    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                    Edit Employee
                </h1>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Update employee details.
                </p>
            </div>
            <div class="flex items-center gap-x-4">
                <flux:button type="button" variant="danger" wire:click="delete" wire:confirm="Are you sure you want to delete this employee? This action cannot be undone.">
                    Delete
                </flux:button>
                <flux:button :href="route('admin.data.employees-and-divisions', ['currentTab' => 'employees', 'view' => $this->previousView])" variant="ghost" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Save Changes
                </flux:button>
            </div>
        </div>
    </div>

    <div class="mt-8">
        <div class="grid grid-cols-1 gap-8">
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">Employee Details</h3>
                </div>
                <div class="p-6">
                    <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                        <div class="sm:col-span-4">
                            <flux:input wire:model="name" label="Full Name" required />
                        </div>
                        <div class="sm:col-span-4">
                            <flux:input wire:model="employee_number" label="Employee Number" required />
                        </div>
                        <div class="sm:col-span-3">
                            <flux:select wire:model="position_id" label="Position" placeholder="Select a position" required>
                                <option value="" disabled>Select a position</option>
                                @foreach ($this->positions as $position)
                                    <option value="{{ $position->id }}">{{ $position->title }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                        <div class="sm:col-span-3">
                            <flux:select wire:model="division_id" label="Division" placeholder="Select a division" required>
                                <option value="" disabled>Select a division</option>
                                @foreach ($this->divisions as $division)
                                    <option value="{{ $division->id }}">{{ $division->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form> 