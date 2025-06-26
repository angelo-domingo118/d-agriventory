<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $section = 'Reports';
}

?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100 mb-6">Inventory Manager - Reports</h1>
    
    <div class="bg-white dark:bg-stone-800 shadow-sm rounded-lg p-6">
        <p class="text-stone-600 dark:text-stone-300">Reports content will be displayed here.</p>
    </div>
</div> 