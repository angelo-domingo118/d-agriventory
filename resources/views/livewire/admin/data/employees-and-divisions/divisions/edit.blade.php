<?php

use App\Models\Division;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public Division $division;

    public function mount(Division $division): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }

        $this->division = $division;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'division.name' => ['required', 'string', 'max:255', Rule::unique('divisions', 'name')->ignore($this->division->id)],
            'division.code' => ['required', 'string', 'max:50', Rule::unique('divisions', 'code')->ignore($this->division->id)],
        ]);

        $this->division->save();

        session()->flash('success', 'Division updated successfully.');
        
        $this->redirectRoute('admin.data.employees-and-divisions.divisions.index');
    }

    public function delete(): void
    {
        if ($this->division->employees()->exists()) {
            session()->flash('error', 'Cannot delete a division that has employees.');
            return;
        }

        $this->division->delete();

        session()->flash('success', 'Division deleted successfully.');

        $this->redirectRoute('admin.data.employees-and-divisions.divisions.index');
    }
}; ?>

<div>
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Edit Division
        </h1>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            Update the details for this division.
        </p>
    </div>

    <form wire:submit.prevent="save" class="mt-8">
        <div class="max-w-2xl">
             <div class="space-y-6">
                <flux:input wire:model="division.name" label="Name" required />
                <flux:input wire:model="division.code" label="Code" required />
            </div>

            <div class="mt-8 flex items-center justify-between">
                <flux:button
                    type="button"
                    variant="danger"
                    wire:click="delete"
                    wire:confirm="Are you sure you want to delete this division? This will be blocked if any employees are currently assigned to it. This action cannot be undone."
                >
                    Delete Division
                </flux:button>
                <div class="flex justify-end gap-x-4">
                    <flux:button :href="route('admin.data.employees-and-divisions.divisions.index')" variant="ghost">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Save Changes</flux:button>
                </div>
            </div>
        </div>
    </form>
</div> 