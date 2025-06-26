<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

new #[Layout('components.layouts.app')] class extends Component
{
    #[Computed]
    public function itemsInStock(): int
    {
        return 0; // Placeholder
    }

    #[Computed]
    public function lowStockItems(): int
    {
        return 0; // Placeholder
    }

    #[Computed]
    public function pendingTransfers(): int
    {
        return 0; // Placeholder
    }

    #[Computed]
    public function completedTransfers(): int
    {
        return 0; // Placeholder
    }
}

?>

<div>
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-100 mb-6">Inventory Manager Dashboard</h1>

        <div class="bg-white dark:bg-stone-800 shadow-sm rounded-lg p-6">
            <h2 class="text-xl font-medium text-stone-800 dark:text-stone-200 mb-4">Division Statistics</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-dashboard.stat-card title="Items In Stock" :value="$this->itemsInStock">
                    <x-slot name="icon">
                        <flux:icon.boxes class="w-6 h-6" />
                    </x-slot>
                </x-dashboard.stat-card>

                <x-dashboard.stat-card title="Low Stock Items" :value="$this->lowStockItems">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </x-slot>
                </x-dashboard.stat-card>

                <x-dashboard.stat-card title="Pending Transfers" :value="$this->pendingTransfers">
                    <x-slot name="icon">
                        <flux:icon.clipboard-list class="w-6 h-6" />
                    </x-slot>
                </x-dashboard.stat-card>

                <x-dashboard.stat-card title="Completed Transfers" :value="$this->completedTransfers">
                    <x-slot name="icon">
                        <flux:icon.package-check class="w-6 h-6" />
                    </x-slot>
                </x-dashboard.stat-card>
            </div>
        </div>
    </div>
</div> 