<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $currentTab = 'divisions';

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

<div x-data="{ currentTab: @entangle('currentTab'), view: 'tree' }">
    <div class="border-b border-stone-200 pb-5 dark:border-stone-700 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100">
                Employees & Divisions
            </h1>
            <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                Manage employees, their assigned positions, and divisions.
            </p>
        </div>
        <div class="mt-3 sm:mt-0 sm:ml-4">
            <div class="hidden sm:block">
                <div class="inline-flex items-center rounded-lg bg-stone-100 p-1 dark:bg-stone-800">
                    <button @click="view = 'tree'"
                            type="button"
                            :class="view === 'tree' ? 'bg-white text-stone-700 shadow-sm dark:bg-stone-700 dark:text-stone-100' : 'text-stone-500 hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-100'"
                            class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-semibold transition-colors focus:outline-none">
                        <x-flux::icon.folder-git-2 class="w-5 h-5 mr-2 -ml-1" />
                        Tree View
                    </button>
                    <button @click="view = 'table'"
                            type="button"
                            :class="view === 'table' ? 'bg-white text-stone-700 shadow-sm dark:bg-stone-700 dark:text-stone-100' : 'text-stone-500 hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-100'"
                            class="ml-1 inline-flex items-center rounded-md px-3 py-1.5 text-sm font-semibold transition-colors focus:outline-none">
                        <x-flux::icon.layout-grid class="w-5 h-5 mr-2 -ml-1" />
                        Table View
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8">
        <div x-show="view === 'tree'" x-cloak>
            <livewire:admin.data.employees-and-divisions.tree-view />
        </div>
        <div x-show="view === 'table'" x-cloak>
            <div class="border-b border-stone-200 dark:border-stone-700">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button @click="currentTab = 'divisions'"
                       :class="currentTab === 'divisions' ? 'border-primary-500 text-primary-600' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700 dark:text-stone-400 dark:hover:border-stone-600 dark:hover:text-stone-200'"
                       class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                        Divisions
                    </button>
                    <button @click="currentTab = 'positions'"
                        :class="currentTab === 'positions' ? 'border-primary-500 text-primary-600' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700 dark:text-stone-400 dark:hover:border-stone-600 dark:hover:text-stone-200'"
                        class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                        Positions
                    </button>
                    <button @click="currentTab = 'employees'"
                        :class="currentTab === 'employees' ? 'border-primary-500 text-primary-600' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700 dark:text-stone-400 dark:hover:border-stone-600 dark:hover:text-stone-200'"
                        class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                        Employees
                    </button>
                </nav>
            </div>

            <div class="mt-8">
                <div x-show="currentTab === 'divisions'" x-cloak>
                    <livewire:admin.data.employees-and-divisions.divisions.index />
                </div>
                <div x-show="currentTab === 'positions'" x-cloak>
                    <livewire:admin.data.employees-and-divisions.positions.index />
                </div>
                <div x-show="currentTab === 'employees'" x-cloak>
                    <livewire:admin.data.employees-and-divisions.employees.index />
                </div>
            </div>
        </div>
    </div>
</div> 