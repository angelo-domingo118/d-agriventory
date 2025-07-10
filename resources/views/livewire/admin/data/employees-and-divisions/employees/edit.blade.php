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
        $this->redirectRoute('admin.data.employees-and-divisions', ['currentTab' => 'employees']);
    }
    
    public function delete(): void
    {
        if (
            $this->employee->icsNumbers()->exists() ||
            $this->employee->parNumbers()->exists() ||
            $this->employee->assignedIdrNumbers()->exists() ||
            $this->employee->approvedIdrNumbers()->exists() ||
            $this->employee->icsTransfersFrom()->exists() ||
            $this->employee->icsTransfersTo()->exists() ||
            $this->employee->parTransfersFrom()->exists() ||
            $this->employee->parTransfersTo()->exists()
        ) {
            session()->flash('error', 'This employee cannot be deleted because they are associated with inventory records or transfers.');
            return;
        }
        
        $this->employee->delete();

        session()->flash('success', 'Employee deleted successfully.');
        $this->redirectRoute('admin.data.employees-and-divisions', ['currentTab' => 'employees']);
    }

    public function cancel(): void
    {
        $this->redirectRoute('admin.data.employees-and-divisions', ['currentTab' => 'employees']);
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
            Edit Employee
        </h1>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            Update employee details.
        </p>
    </div>

    <form wire:submit.prevent="save" class="mt-8">
        <div class="max-w-4xl">
            <div class="space-y-6 rounded-lg border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <h3 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Employee Details</h3>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input wire:model="name" label="Full Name" required />
                    <flux:input wire:model="employee_number" label="Employee Number" required />
                    <flux:select wire:model="position_id" label="Position" required>
                        <option value="">Select a position</option>
                        @foreach($this->positions as $position)
                            <option value="{{ $position->id }}">{{ $position->title }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="division_id" label="Division/Office" required>                        <option value="">Select a division</option>
                        @foreach($this->divisions as $division)
                            <option value="{{ $division->id }}">{{ $division->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            <div class="mt-8 flex items-center justify-between">
                <flux:button
                    type="button"
                    variant="danger"
                    wire:click="delete"
                    wire:confirm="Are you sure you want to delete this employee? This action cannot be undone."
                >
                    Delete Employee
                </flux:button>
                <div class="flex justify-end gap-x-4">
                    <flux:button
                        type="button"
                        wire:click="cancel"
                    >
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">Save Changes</flux:button>
                </div>
            </div>
        </div>
    </form>
</div> 