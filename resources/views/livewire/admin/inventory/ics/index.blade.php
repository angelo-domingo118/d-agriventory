<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    public function mount(): void
    {
        // Verify the user has permission to view ICS management
        if (!auth()->user()->hasAdminPermission('view_inventory')) {
            abort(403);
        }
    }
}; ?>

<div>
    <x-admin.inventory.placeholder-layout heading="ICS Management">
        <p class="text-stone-600 dark:text-stone-400">This is a placeholder for the ICS Management page.</p>
    </x-admin.inventory.placeholder-layout>
</div> 