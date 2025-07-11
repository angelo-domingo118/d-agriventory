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
    public string $previousView = 'tree';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        
        $this->previousView = request()->query('view', 'tree');
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
            'position_id' => ['required', 'integer', Rule::exists('positions', 'id')],
            'division_id' => ['required', 'integer', Rule::exists('divisions', 'id')],
        ]);

        Employee::create($validated);

        session()->flash('success', 'Employee created successfully.');
        $this->redirect(route('admin.data.employees-and-divisions', ['currentTab' => 'employees', 'view' => $this->previousView]), navigate: true);
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
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                    Create New Employee
                </h1>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Add a new employee to the system.
                </p>
            </div>
            <div class="flex items-center gap-x-4">
                <x-action-message class="me-3" on="employee-created">
                    {{ __('Employee created successfully.') }}
                </x-action-message>
                <flux:button :href="route('admin.data.employees-and-divisions', ['currentTab' => 'employees', 'view' => $this->previousView])" variant="ghost" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Save Employee
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