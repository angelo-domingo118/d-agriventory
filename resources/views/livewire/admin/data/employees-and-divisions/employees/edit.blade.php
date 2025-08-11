<?php

use App\Models\Division;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
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
            'position_id' => ['nullable', 'integer', Rule::exists('positions', 'id')],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
        ]);

        $this->employee->update($validated);

        // Close the modal and refresh the parent component
        $this->dispatch('employee-updated');
        Flux::modal('edit-employee')->close();
    }

    public function delete(): void
    {
        // Check if the employee has any inventory items assigned
        if ($this->employee->icsNumbers()->exists() || $this->employee->parNumbers()->exists() || $this->employee->assignedIdrNumbers()->exists() || $this->employee->approvedIdrNumbers()->exists()) {
            session()->flash('error', 'Cannot delete an employee that has inventory items assigned.');
            return;
        }

        $this->employee->delete();

        // Close the modal and refresh the parent component
        $this->dispatch('employee-deleted');
        Flux::modal('edit-employee')->close();
    }

    public function cancel(): void
    {
        Flux::modal('edit-employee')->close();
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

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Edit Employee</flux:heading>
        <flux:text class="mt-2">Update employee details.</flux:text>
    </div>

    <form wire:submit="save" class="space-y-4">
        <flux:input wire:model="name" label="Full Name" placeholder="Enter employee's full name" required />
        <flux:input wire:model="employee_number" label="Employee Number" placeholder="Enter unique employee number" required />
        <flux:select wire:model="position_id" label="Position (Optional)" placeholder="Select a position">
            <option value="">Select a position</option>
            @foreach ($this->positions as $position)
                <option value="{{ $position->id }}">{{ $position->title }}</option>
            @endforeach
        </flux:select>
        <flux:select wire:model="division_id" label="Division (Optional)" placeholder="Select a division">
            <option value="">Select a division</option>
            @foreach ($this->divisions as $division)
                <option value="{{ $division->id }}">{{ $division->name }}</option>
            @endforeach
        </flux:select>
        
        <div class="flex gap-2 pt-4 border-t border-stone-200 dark:border-stone-700">
            <flux:button type="button" variant="danger" wire:click="delete" wire:confirm="Are you sure you want to delete this employee? This action cannot be undone.">
                Delete
            </flux:button>
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="cancel">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Save Changes</span>
                <span wire:loading>Saving...</span>
            </flux:button>
        </div>
    </form>
</div> 