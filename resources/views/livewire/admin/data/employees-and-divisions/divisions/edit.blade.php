<?php

use App\Models\Division;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public Division $division;
    public string $name = '';
    public string $code = '';
    public string $previousView = 'tree';

    public function mount(Division $division): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }

        $this->division = $division;
        $this->name = $division->name;
        $this->code = $division->code;
        
        $this->previousView = request()->query('view', 'tree');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('divisions', 'name')->ignore($this->division->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('divisions', 'code')->ignore($this->division->id)],
        ]);

        $this->division->update($validated);

        session()->flash('success', 'Division updated successfully.');

        $this->redirect(route('admin.data.employees-and-divisions', ['currentTab' => 'divisions', 'view' => $this->previousView]), navigate: true);
    }

    public function delete(): void
    {
        if ($this->division->employees()->exists()) {
            session()->flash('error', 'Cannot delete a division that has employees.');
            return;
        }

        $this->division->delete();

        session()->flash('success', 'Division deleted successfully.');

        $this->redirect(route('admin.data.employees-and-divisions', ['currentTab' => 'divisions', 'view' => $this->previousView]), navigate: true);
    }
}; ?>

<form wire:submit="save">
    <!-- Breadcrumbs -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <flux:breadcrumbs class="text-2xl font-semibold">
                <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Data</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('admin.data.employees-and-divisions', ['currentTab' => 'divisions'])" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Employees & Divisions</flux:breadcrumbs.item>
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Edit Division</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
    </div>

    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                    Edit Division
                </h1>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Update division details.
                </p>
            </div>
            <div class="flex items-center gap-x-4">
                <flux:button type="button" variant="danger" wire:click="delete" wire:confirm="Are you sure you want to delete this division? This action cannot be undone.">
                    Delete
                </flux:button>
                <flux:button :href="route('admin.data.employees-and-divisions', ['currentTab' => 'divisions', 'view' => $this->previousView])" variant="ghost" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Save Changes
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