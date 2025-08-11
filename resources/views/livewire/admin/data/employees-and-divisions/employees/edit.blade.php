<?php

use App\Models\Division;
use App\Models\Employee;
use App\Services\ToastService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Flux\Flux;

new class extends Component {
    public Employee $employee;
    public string $name;
    public ?int $division_id;
    public string $position_title;
    public string $position_code;
    public string $position_type;
    public string $position_description;

    public function mount(Employee $employee): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        $this->employee = $employee;
        $this->name = $employee->name;
        $this->division_id = $employee->division_id;
        $this->position_title = $employee->position_title ?? '';
        $this->position_code = $employee->position_code ?? '';
        $this->position_type = $employee->position_type ?? '';
        $this->position_description = $employee->position_description ?? '';
    }

    public function confirmDelete(): void
    {        
        // Show the delete confirmation modal
        Flux::modal('delete-employee-confirmation')->show();
    }

    public function cancelDelete(): void
    {
        // Close the delete confirmation modal
        Flux::modal('delete-employee-confirmation')->close();
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

        try {
            $this->employee->update($validated);
            
            // Show success toast
            ToastService::updated($this, 'Employee');
            
            // Close the modal and refresh the parent component
            $this->dispatch('employee-updated');
            Flux::modal('edit-employee')->close();
        } catch (\Exception $e) {
            Log::error('Error updating employee: ' . $e->getMessage());
            ToastService::error($this, 'There was an error updating the employee. Please try again.');
        }
    }

    public function delete(): void
    {
        try {
            // Check if deletion should be blocked
            if ($this->employee->isDeletionBlocked()) {
                ToastService::error($this, 'Cannot delete this employee because they have active inventory assignments.');
                Flux::modal('delete-employee-confirmation')->close();
                return;
            }

            // Safe to delete normally
            $this->employee->delete();
            ToastService::deleted($this, 'Employee');

            // Close both modals and refresh the parent component
            Flux::modal('delete-employee-confirmation')->close();
            Flux::modal('edit-employee')->close();
            $this->dispatch('employee-deleted');
            
        } catch (\Exception $e) {
            Log::error('Error deleting employee: ' . $e->getMessage());
            $errorMessage = 'An error occurred while deleting the employee. Please try again.';
            if (config('app.debug')) {
                $errorMessage .= ' Error: ' . $e->getMessage();
            }
            ToastService::error($this, $errorMessage);
            Flux::modal('delete-employee-confirmation')->close();
        }
    }

    public function cancel(): void
    {
        Flux::modal('edit-employee')->close();
    }

    #[On('call-delete')]
    public function handleDelete(): void
    {
        $this->delete();
    }

    #[On('call-cancel-delete')]
    public function handleCancelDelete(): void
    {
        $this->cancelDelete();
    }

    #[Computed]
    public function divisions()
    {
        return Division::orderBy('name')->get();
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
        <flux:heading size="lg">Edit Employee</flux:heading>
        <flux:text class="mt-2">Update employee details.</flux:text>
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
            <flux:button type="button" variant="danger" wire:click="confirmDelete">
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