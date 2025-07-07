<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $currentTab = 'contracts';

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
            Suppliers & Contracts
        </h1>
        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
            Manage suppliers and their associated contracts.
        </p>
    </div>

    <div class="mt-8">
        <div class="border-b border-stone-200 dark:border-stone-700">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button @click="currentTab = 'contracts'"
                   :class="currentTab === 'contracts' ? 'border-primary-500 text-primary-600' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700 dark:text-stone-400 dark:hover:border-stone-600 dark:hover:text-stone-200'"
                   class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                    Contracts
                </button>
                <button @click="currentTab = 'suppliers'"
                   :class="currentTab === 'suppliers' ? 'border-primary-500 text-primary-600' : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700 dark:text-stone-400 dark:hover:border-stone-600 dark:hover:text-stone-200'"
                   class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                    Suppliers
                </button>
            </nav>
        </div>

        <div class="mt-8">
            <div x-show="currentTab === 'contracts'" x-cloak>
                <livewire:admin.data.suppliers-and-contracts.contracts.index />
            </div>
            <div x-show="currentTab === 'suppliers'" x-cloak>
                <livewire:admin.data.suppliers-and-contracts.suppliers.index />
            </div>
        </div>
    </div>
</div> 