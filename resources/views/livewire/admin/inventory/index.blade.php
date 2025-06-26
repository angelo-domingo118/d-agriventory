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
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold mb-6 dark:text-stone-100">Inventory Management</h1>
        
        <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-700 overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <p class="text-stone-600 dark:text-stone-400">This is a placeholder for the inventory management page.</p>
            </div>
        </div>
    </div>
</div> 