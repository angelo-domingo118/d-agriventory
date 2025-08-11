<?php

use App\Models\Division;
use App\Models\Employee;
use App\Models\Position;
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
    public ?int $position_id;
    public ?int $division_id;

    public function mount(Employee $employee): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        $this->employee = $employee;
        $this->name = $employee->name;
        $this->position_id = $employee->position_id;
        $this->division_id = $employee->division_id;
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
            'position_id' => ['nullable', 'integer', Rule::exists('positions', 'id')],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
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