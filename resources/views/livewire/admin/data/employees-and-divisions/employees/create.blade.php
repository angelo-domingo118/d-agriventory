<?php

use App\Models\Division;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    public string $name = '';
    public string $employee_number = '';
    public ?int $position_id = null;
    public ?int $division_id = null;

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
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

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'employee_number' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_number')],
            'position_id' => ['nullable', 'integer', Rule::exists('positions', 'id')],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
        ]);

        Employee::create($validated);

        // Close the modal and refresh the parent component
        $this->dispatch('employee-created');
        Flux::modal('create-employee')->close();
        
        // Reset form
        $this->reset(['name', 'employee_number', 'position_id', 'division_id']);
    }

    public function cancel(): void
    {
        Flux::modal('create-employee')->close();
        $this->reset(['name', 'employee_number', 'position_id', 'division_id']);
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
        <flux:heading size="lg">Create Employee</flux:heading>
        <flux:text class="mt-2">Add a new employee to the records.</flux:text>
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
            <flux:spacer />
            <flux:button type="button" variant="ghost" wire:click="cancel">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove">Create Employee</span>
                <span wire:loading>Creating...</span>
            </flux:button>
        </div>
    </form>
</div>