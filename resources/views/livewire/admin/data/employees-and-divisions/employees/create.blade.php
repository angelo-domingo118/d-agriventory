<?php

use App\Models\Division;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
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

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'employee_number' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_number')],
            'position_id' => ['required', 'integer', Rule::exists('positions', 'id')],
            'division_id' => ['required', 'integer', Rule::exists('divisions', 'id')],
        ]);

        Employee::create($validated);

        session()->flash('success', 'Employee created successfully.');
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
            Create Employee
        </h1>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            Add a new employee to the organization's records.
        </p>
    </div>

    <form wire:submit.prevent="save" class="space-y-8">
        <x-admin.section>
            <x-slot:title>Employee Information</x-slot:title>
            <x-slot:description>
                Add the employee's basic details.
            </x-slot:description>
            <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                <div class="sm:col-span-4">
                    <flux:input wire:model="name" label="Full Name" required />
                </div>

                <div class="sm:col-span-4">
                    <flux:input wire:model="employee_number" label="Employee Number" required />
                </div>

                <div class="sm:col-span-3">
                    <flux:select wire:model="position_id" label="Position" :options="$this->positions"
                        placeholder="Select a position" option-value="id" option-label="title" required />
                </div>

                <div class="sm:col-span-3">
                    <flux:select wire:model="division_id" label="Division" :options="$this->divisions"
                        placeholder="Select a division" option-value="id" option-label="name" required />
                </div>
            </div>
        </x-admin.section>

        <div class="mt-8 flex justify-end gap-x-4">
            <a href="{{ route('admin.data.employees-and-divisions') }}"
                class="text-sm text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100">Cancel</a>
            <flux:button type="submit" variant="primary">Create Employee</flux:button>
        </div>
    </form>
</div> 