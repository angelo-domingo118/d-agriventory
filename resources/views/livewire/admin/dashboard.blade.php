<?php

use App\Models\User;
use App\Models\ItemsCatalog;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

new #[Layout('components.layouts.app')] class extends Component {
    public function mount(): void
    {
        // Additional initialization can be added here
    }

    #[Computed]
    public function userCount(): int
    {
        return Cache::remember('admin.dashboard.user_count', now()->addMinutes(5), function () {
            return User::count();
        });
    }

    #[Computed]
    public function itemCount(): int
    {
        return Cache::remember('admin.dashboard.item_count', now()->addMinutes(5), function () {
            return ItemsCatalog::count();
        });
    }

    #[Computed]
    public function auditLogCount(): int
    {
        return Cache::remember('admin.dashboard.audit_log_count', now()->addMinutes(5), function () {
            return AuditLog::count();
        });
    }

    #[Computed]
    public function pendingTransfersCount(): int
    {
        return Cache::remember('admin.dashboard.pending_transfers_count', now()->addMinutes(5), function () {
            // In the future, this would count actual pending transfers
            return 0;
        });
    }
}; ?>

<div>
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold mb-6 dark:text-stone-100">Admin Dashboard</h1>
        
        <div class="mb-10">
            <h2 class="text-xl font-medium mb-4 dark:text-stone-100">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @adminpermission('view_users')
                <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-700 overflow-hidden shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-medium text-stone-900 dark:text-stone-100">Manage Users</h3>
                    <p class="mt-2 text-stone-600 dark:text-stone-400">Add, edit, or remove user accounts</p>
                    <div class="mt-4">
                        <flux:button :href="route('admin.users.index')" variant="ghost">
                            {{ __('View All Users →') }}
                        </flux:button>
                    </div>
                </div>
                @endadminpermission

                @adminpermission('view_inventory')
                <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-700 overflow-hidden shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-medium text-stone-900 dark:text-stone-100">Inventory Management</h3>
                    <p class="mt-2 text-stone-600 dark:text-stone-400">Manage inventory items and categories</p>
                    <div class="mt-4">
                        <flux:button :href="route('admin.inventory.index')" variant="ghost">
                            {{ __('View Inventory →') }}
                        </flux:button>
                    </div>
                </div>
                @endadminpermission

                @adminpermission('view_reports')
                <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-700 overflow-hidden shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-medium text-stone-900 dark:text-stone-100">Reports</h3>
                    <p class="mt-2 text-stone-600 dark:text-stone-400">View and generate system reports</p>
                    <div class="mt-4">
                        <flux:button :href="route('admin.reports.index')" variant="ghost">
                            {{ __('View Reports →') }}
                        </flux:button>
                    </div>
                </div>
                @endadminpermission
            </div>
        </div>

        <div>
            <h2 class="text-xl font-medium mb-4 dark:text-stone-100">System Statistics</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-dashboard.stat-card title="Total Users" :value="$this->userCount">
                    <x-slot name="icon">
                        <x-flux::icon.users />
                    </x-slot>
                </x-dashboard.stat-card>

                <x-dashboard.stat-card title="Total Inventory Items" :value="$this->itemCount">
                    <x-slot name="icon">
                        <x-flux::icon.boxes />
                    </x-slot>
                </x-dashboard.stat-card>

                <x-dashboard.stat-card title="Pending Transfers" :value="$this->pendingTransfersCount">
                    <x-slot name="icon">
                        <x-flux::icon.clipboard-list />
                    </x-slot>
                </x-dashboard.stat-card>

                <x-dashboard.stat-card title="Recent Activity" :value="$this->auditLogCount">
                    <x-slot name="icon">
                        <x-flux::icon.chart-bar />
                    </x-slot>
                </x-dashboard.stat-card>
            </div>
        </div>
    </div>
</div> 