<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $currentTab = 'employees';

    public function mount()
    {
        if (!auth()->user()->hasAdminPermission('manage_data')) {
            abort(403, 'You do not have permission to manage this data.');
        }
    }

    public function setTab(string $tab): void
    {
        $this->currentTab = $tab;
    }
}; ?>

<div x-data="{ currentTab: @entangle('currentTab') }">
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
            Employees & Divisions
        </h1>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            Manage employees, their assigned positions, and divisions.
        </p>
    </div>

    <div class="mt-8">
        <div class="border-b border-stone-200 dark:border-stone-700">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button @click="currentTab = 'employees'"
                   :class="currentTab === 'employees' ? 'border-primary-500 text-primary-600' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700 dark:text-stone-400 dark:hover:border-stone-600 dark:hover:text-stone-200'"
                   class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                    Employees
                </button>
                <button @click="currentTab = 'positions'"
                   :class="currentTab === 'positions' ? 'border-primary-500 text-primary-600' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700 dark:text-stone-400 dark:hover:border-stone-600 dark:hover:text-stone-200'"
                   class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                    Positions
                </button>
                 <button @click="currentTab = 'divisions'"
                   :class="currentTab === 'divisions' ? 'border-primary-500 text-primary-600' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700 dark:text-stone-400 dark:hover:border-stone-600 dark:hover:text-stone-200'"
                   class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                    Divisions
                </button>
            </nav>
        </div>

        <div class="mt-8">
            <div x-show="currentTab === 'employees'" x-cloak>
                <livewire:admin.data.employees-and-divisions.employees.index />
            </div>
            <div x-show="currentTab === 'positions'" x-cloak>
                <livewire:admin.data.employees-and-divisions.positions.index />
            </div>
             <div x-show="currentTab === 'divisions'" x-cloak>
                <livewire:admin.data.employees-and-divisions.divisions.index />
            </div>
        </div>
    </div>
</div> 