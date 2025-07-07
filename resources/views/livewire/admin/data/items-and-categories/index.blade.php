<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $currentTab = 'items';

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
            Items & Categories
        </h1>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            Manage the items catalog and their respective categories.
        </p>
    </div>

    <div class="mt-8">
        <div class="border-b border-stone-200 dark:border-stone-700">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button @click="currentTab = 'items'"
                   :class="currentTab === 'items' ? 'border-primary-500 text-primary-600' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700 dark:text-stone-400 dark:hover:border-stone-600 dark:hover:text-stone-200'"
                   class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                    Items Catalog
                </button>
                <button @click="currentTab = 'secondary'"
                   :class="currentTab === 'secondary' ? 'border-primary-500 text-primary-600' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700 dark:text-stone-400 dark:hover:border-stone-600 dark:hover:text-stone-200'"
                   class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                    Secondary Categories
                </button>
                 <button @click="currentTab = 'primary'"
                   :class="currentTab === 'primary' ? 'border-primary-500 text-primary-600' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700 dark:text-stone-400 dark:hover:border-stone-600 dark:hover:text-stone-200'"
                   class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                    Primary Categories
                </button>
            </nav>
        </div>

        <div class="mt-8">
            <div x-show="currentTab === 'items'" x-cloak>
                <livewire:admin.data.items-and-categories.items-catalog.index />
            </div>
            <div x-show="currentTab === 'secondary'" x-cloak>
                <livewire:admin.data.items-and-categories.secondary-categories.index />
            </div>
             <div x-show="currentTab === 'primary'" x-cloak>
                <livewire:admin.data.items-and-categories.primary-categories.index />
            </div>
        </div>
    </div>
</div> 