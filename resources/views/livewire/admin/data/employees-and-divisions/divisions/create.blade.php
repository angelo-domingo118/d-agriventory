<?php

use App\Models\Division;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $name = '';
    public string $code = '';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('divisions', 'name')],
            'code' => ['required', 'string', 'max:50', Rule::unique('divisions', 'code')],
        ]);

        Division::create($validated);

        session()->flash('success', 'Division created successfully.');

        $this->redirectRoute('admin.data.employees-and-divisions.divisions.index');
    }
}; ?>

<div>
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Create Division
        </h1>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            Create a new division or office.
        </p>
    </div>

    <form wire:submit.prevent="save" class="mt-8">
        <div class="max-w-2xl">
             <div class="space-y-6">
                <flux:input wire:model="name" label="Name" required />
                <flux:input wire:model="code" label="Code" required />
            </div>

            <div class="mt-8 flex justify-end gap-x-4">
                <flux:button :href="route('admin.data.employees-and-divisions.divisions.index')" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Create</flux:button>
            </div>
        </div>
    </form>
</div> 