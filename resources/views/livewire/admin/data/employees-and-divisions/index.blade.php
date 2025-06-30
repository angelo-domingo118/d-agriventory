<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Gate;

new #[Layout('components.layouts.app')] class extends Component {
    public function mount(): void
    {
        if (Gate::denies('view_employees_and_divisions')) {
            abort(403);
        }
    }
}; ?>

<div>
    <x-admin.inventory.placeholder-layout heading="Employees & Divisions">
        <p class="text-stone-600 dark:text-stone-400">This is a placeholder for the Employees & Divisions management page.</p>
    </x-admin.inventory.placeholder-layout>
</div> 