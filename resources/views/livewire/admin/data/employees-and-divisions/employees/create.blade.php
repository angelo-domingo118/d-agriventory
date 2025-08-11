<?php

use App\Models\Division;
use App\Models\Employee;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    public string $name = '';
    public ?int $division_id = null;
    public string $position_title = '';
    public string $position_code = '';
    public string $position_type = '';
    public string $position_description = '';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
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
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
            'position_title' => ['nullable', 'string', 'max:255'],
            'position_code' => ['nullable', 'string', 'max:50'],
            'position_type' => ['nullable', 'string', 'in:DIVISION_CHIEF,COORDINATOR,FOCAL_PERSON,OFFICER,SPECIALIST,OTHER'],
            'position_description' => ['nullable', 'string'],
        ]);

        Employee::create($validated);

        // Close the modal and refresh the parent component
        $this->dispatch('employee-created');
        Flux::modal('create-employee')->close();
        
        // Reset form
        $this->reset(['name', 'division_id', 'position_title', 'position_code', 'position_type', 'position_description']);
    }

    public function cancel(): void
    {
        Flux::modal('create-employee')->close();
        $this->reset(['name', 'division_id', 'position_title', 'position_code', 'position_type', 'position_description']);
    }

    public function with(): array
    {
        return [
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
        
        <flux:select wire:model="division_id" label="Division (Optional)" placeholder="Select a division">
            <option value="">Select a division</option>
            @foreach ($this->divisions as $division)
                <option value="{{ $division->id }}">{{ $division->name }}</option>
            @endforeach
        </flux:select>

        <!-- Position Information -->
        <div class="space-y-4 p-4 bg-stone-50 dark:bg-stone-900/50 rounded-lg border border-stone-200 dark:border-stone-700">
            <flux:heading size="sm">Position Information (Optional)</flux:heading>
            
            <flux:input wire:model="position_title" label="Position Title" placeholder="e.g., IT Officer, Chief Accountant" />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="position_code" label="Position Code" placeholder="e.g., IT-OFF, CHIEF-ACC" />
                <flux:select wire:model="position_type" label="Position Type" placeholder="Select position type">
                    <option value="">Select position type</option>
                    <option value="DIVISION_CHIEF">Division Chief</option>
                    <option value="COORDINATOR">Coordinator</option>
                    <option value="FOCAL_PERSON">Focal Person</option>
                    <option value="OFFICER">Officer</option>
                    <option value="SPECIALIST">Specialist</option>
                    <option value="OTHER">Other</option>
                </flux:select>
            </div>
            
            <flux:textarea wire:model="position_description" label="Position Description" placeholder="Brief description of responsibilities and duties..." rows="3" />
        </div>
        
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