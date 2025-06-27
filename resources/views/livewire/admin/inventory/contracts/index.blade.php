<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    public function mount(): void
    {
        // Verify the user has permission to view contracts
        if (!auth()->user()->hasAdminPermission('view_inventory')) {
            abort(403);
        }
    }
}; ?>

<div>
    <x-admin.inventory.placeholder-layout heading="Suppliers & Contracts">
        <p class="text-stone-600 dark:text-stone-400">This is a placeholder for the Suppliers & Contracts management page.</p>
    </x-admin.inventory.placeholder-layout>
</div> 