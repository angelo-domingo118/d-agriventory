<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    public function mount(): void
    {
        // TODO: Add permission check
    }
}; ?>

<div>
    <x-admin.inventory.placeholder-layout heading="Reports">
        <p class="text-stone-600 dark:text-stone-400">This is a placeholder for the Reports page.</p>
    </x-admin.inventory.placeholder-layout>
</div> 