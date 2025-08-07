<?php

use App\Models\Division;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $name = '';
    public string $code = '';
    public string $previousView = 'tree';

    public function mount(): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        
        $this->previousView = request()->query('view', 'tree');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('divisions', 'name')],
            'code' => ['required', 'string', 'max:50', Rule::unique('divisions', 'code')],
        ]);

        Division::create($validated);

        session()->flash('success', 'Division created successfully.');
        $this->redirect(route('admin.data.employees-and-divisions', ['currentTab' => 'divisions', 'view' => $this->previousView]), navigate: true);
    }
}; ?>

<form wire:submit="save">
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <div>
                <flux:breadcrumbs class="text-2xl font-semibold">
                    <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Data</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item :href="route('admin.data.employees-and-divisions', ['currentTab' => 'divisions'])" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Employees & Divisions</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Create New Division</flux:breadcrumbs.item>
                </flux:breadcrumbs>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Add a new division or office.
                </p>
            </div>
            <div class="flex items-center gap-x-4">
                <x-action-message class="me-3" on="division-created">
                    {{ __('Division created successfully.') }}
                </x-action-message>
                <flux:button :href="route('admin.data.employees-and-divisions', ['currentTab' => 'divisions', 'view' => $this->previousView])" variant="ghost" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Save Division
                </flux:button>
            </div>
        </div>
    </div>

    <div class="mt-8">
        <div class="grid grid-cols-1 gap-8">
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">Division Details</h3>
                </div>
                <div class="p-6">
                    <div class="max-w-2xl">
                        <div class="space-y-6">
                            <flux:input wire:model="name" label="Name" required />
                            <flux:input wire:model="code" label="Code" required />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>