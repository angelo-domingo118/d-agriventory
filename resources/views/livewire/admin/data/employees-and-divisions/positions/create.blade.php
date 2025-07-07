<?php

use App\Models\Position;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public Position $position;

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }

        $this->position = new Position();
    }

    public function save(): void
    {
        $this->validate([
            'position.title' => ['required', 'string', 'max:255', Rule::unique('positions', 'title')->whereNull('deleted_at')],
        ]);

        $this->position->save();

        session()->flash('success', 'Position created successfully.');

        $this->redirectRoute('admin.data.employees-and-divisions.index', ['currentTab' => 'positions']);
    }
}; ?>

<div>
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Create Position
        </h1>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            Create a new employee position.
        </p>
    </div>

    <div class="mb-4 flex items-center gap-x-4">
        <a href="{{ route('admin.data.employees-and-divisions') }}"
            class="flex items-center gap-x-2 text-sm text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100">
            <x-flux::icon.arrow-left class="h-4 w-4" />
            Back
        </a>
    </div>

    <form wire:submit.prevent="save" class="mt-8">
        <div class="max-w-2xl">
            <div class="space-y-6">
                <flux:input wire:model="position.title" label="Position Title" required />
            </div>

            <div class="mt-8 flex justify-end gap-x-4">
                <flux:button :href="route('admin.data.employees-and-divisions')" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Create</flux:button>
            </div>
        </div>
    </form>
</div> 