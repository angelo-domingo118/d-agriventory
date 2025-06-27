<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    public function mount(): void
    {
        // Mount logic here
    }
}; ?>

<div>
    <x-admin.layout heading="Employees & Divisions">
        <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-700 overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <p class="text-stone-600 dark:text-stone-400">This is a placeholder for the Employees & Divisions management page.</p>
            </div>
        </div>
    </x-admin.layout>
</div> 