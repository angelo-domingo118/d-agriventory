<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $section = 'Reports';
}

?>

<div>
    <x-inventory-manager.layout heading="Reports">
        <div class="bg-white dark:bg-stone-800 shadow-sm rounded-lg p-6">
            <p class="text-stone-600 dark:text-stone-300">Reports content will be displayed here.</p>
        </div>
    </x-inventory-manager.layout>
</div> 