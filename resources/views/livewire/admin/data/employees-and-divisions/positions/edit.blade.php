<?php

use App\Models\Position;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public Position $position;

    public function mount(Position $position): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }

        $this->position = $position;
    }

    public function save(): void
    {
        $this->validate([
            'position.name' => ['required', 'string', 'max:255', Rule::unique('positions', 'name')->ignore($this->position->id)],
        ]);

        $this->position->save();

        session()->flash('success', 'Position updated successfully.');
        
        $this->redirectRoute('admin.data.employees-and-divisions.index', ['currentTab' => 'positions']);
    }

    public function delete(): void
    {
        if ($this->position->employees()->exists()) {
            session()->flash('error', 'Cannot delete a position that is assigned to employees.');
            return;
        }

        $this->position->delete();

        session()->flash('success', 'Position deleted successfully.');

        $this->redirectRoute('admin.data.employees-and-divisions.index', ['currentTab' => 'positions']);
    }
}; ?>

<div>
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Edit Position
        </h1>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            Update the details for this position.
        </p>
    </div>

    <form wire:submit.prevent="save" class="mt-8">
        <div class="max-w-2xl">
             <div class="space-y-6">
                <flux:input wire:model="position.name" label="Position Name" required />
            </div>

            <div class="mt-8 flex items-center justify-between">
                <flux:button
                    type="button"
                    variant="danger"
                    wire:click="delete"
                    wire:confirm="Are you sure you want to delete this position? This action cannot be undone."
                >
                    Delete Position
                </flux:button>
                <div class="flex justify-end gap-x-4">
                    <flux:button :href="route('admin.data.employees-and-divisions.index', ['currentTab' => 'positions'])" variant="ghost">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Save Changes</flux:button>
                </div>
            </div>
        </div>
    </form>
</div> 