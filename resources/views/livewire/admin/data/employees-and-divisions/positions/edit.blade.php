<?php

use App\Models\Position;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public Position $position;
    public string $title;
    public string $position_type;
    public string $previousView = 'tree';

    public function mount(Position $position): void
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
        $this->position = $position;
        $this->title = $position->title;
        $this->position_type = $position->position_type ?? '';
        
        $this->previousView = request()->query('view', 'tree');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('positions', 'title')->ignore($this->position->id)],
            'position_type' => ['required', 'string', Rule::in(['DIVISION_CHIEF', 'COORDINATOR', 'FOCAL_PERSON', 'OFFICER', 'SPECIALIST', 'OTHER'])],
        ]);

        $this->position->update($validated);

        session()->flash('success', 'Position updated successfully.');
        $this->redirect(route('admin.data.employees-and-divisions', ['currentTab' => 'positions', 'view' => $this->previousView]), navigate: true);
    }

    public function delete(): void
    {
        if ($this->position->employees()->exists()) {
            session()->flash('error', 'Cannot delete a position that has employees assigned to it.');
            return;
        }

        $this->position->delete();

        session()->flash('success', 'Position deleted successfully.');
        $this->redirect(route('admin.data.employees-and-divisions', ['currentTab' => 'positions', 'view' => $this->previousView]), navigate: true);
    }
}; ?>

<form wire:submit="save">
    <!-- Breadcrumbs -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <flux:breadcrumbs class="text-2xl font-semibold">
                <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate icon="home" class="text-xl sm:text-2xl font-semibold text-stone-700 dark:text-stone-300" />
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Data</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('admin.data.employees-and-divisions', ['currentTab' => 'positions'])" wire:navigate class="text-xl sm:text-2xl font-semibold text-stone-500 dark:text-stone-400">Employees & Divisions</flux:breadcrumbs.item>
                <flux:breadcrumbs.item class="text-xl sm:text-2xl font-semibold text-stone-900 dark:text-stone-100">Edit Position</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>
    </div>

    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                    Edit Position
                </h1>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                    Update position details.
                </p>
            </div>
            <div class="flex items-center gap-x-4">
                <flux:button type="button" variant="danger" wire:click="delete" wire:confirm="Are you sure you want to delete this position? This action cannot be undone.">
                    Delete
                </flux:button>
                <flux:button :href="route('admin.data.employees-and-divisions', ['currentTab' => 'positions', 'view' => $this->previousView])" variant="ghost" wire:navigate>
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
                    <h3 class="font-semibold text-stone-800 dark:text-stone-200">Position Details</h3>
                </div>
                <div class="p-6">
                    <div class="max-w-2xl">
                        <div class="space-y-6">
                            <flux:input wire:model="title" label="Position Title" required />
                            <flux:select wire:model="position_type" label="Position Type" required>
                                <option value="DIVISION_CHIEF">Division Chief</option>
                                <option value="COORDINATOR">Coordinator</option>
                                <option value="FOCAL_PERSON">Focal Person</option>
                                <option value="OFFICER">Officer</option>
                                <option value="SPECIALIST">Specialist</option>
                                <option value="OTHER">Other</option>
                            </flux:select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form> 