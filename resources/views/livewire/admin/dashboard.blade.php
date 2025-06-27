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
    <x-admin.layout :heading="__('Admin Dashboard')">
        <div class="space-y-10">
            <div>
                <h2 class="text-xl font-medium mb-4 text-stone-900 dark:text-stone-100">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @adminpermission('view_users')
                        <x-dashboard.action-card 
                            :title="__('Manage Users')" 
                            :description="__('Add, edit, or remove user accounts')" 
                            :href="route('admin.users.index')">
                            {{ __('View All Users →') }}
                        </x-dashboard.action-card>
                    @endadminpermission

                    @adminpermission('view_inventory')
                        <x-dashboard.action-card 
                            :title="__('Inventory Management')" 
                            :description="__('Manage inventory items and categories')" 
                            :href="route('admin.inventory.index')">
                            {{ __('View Inventory →') }}
                        </x-dashboard.action-card>
                    @endadminpermission

                    @adminpermission('view_reports')
                        <x-dashboard.action-card 
                            :title="__('Reports')" 
                            :description="__('View and generate system reports')" 
                            :href="route('admin.reports.index')">
                            {{ __('View Reports →') }}
                        </x-dashboard.action-card>
                    @endadminpermission
                </div>
            </div>

            <div>
                <h2 class="text-xl font-medium mb-4 text-stone-900 dark:text-stone-100">System Statistics</h2>
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
    </x-admin.layout>
</div> 