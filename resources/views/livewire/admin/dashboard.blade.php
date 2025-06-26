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
        <h1 class="text-2xl font-semibold mb-6">Admin Dashboard</h1>
        
        <div class="mb-10">
            <h2 class="text-xl font-medium mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @adminpermission('view_users')
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium">Manage Users</h3>
                        <p class="mt-2 text-gray-600">Add, edit, or remove user accounts</p>
                        <div class="mt-4">
                            <a href="{{ route('admin.users.index') }}" class="text-indigo-600 hover:text-indigo-900">View All Users →</a>
                        </div>
                    </div>
                </div>
                @endadminpermission

                @adminpermission('view_inventory')
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium">Inventory Management</h3>
                        <p class="mt-2 text-gray-600">Manage inventory items and categories</p>
                        <div class="mt-4">
                            <a href="{{ route('admin.inventory.index') }}" class="text-indigo-600 hover:text-indigo-900">View Inventory →</a>
                        </div>
                    </div>
                </div>
                @endadminpermission

                @adminpermission('view_reports')
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium">Reports</h3>
                        <p class="mt-2 text-gray-600">View and generate system reports</p>
                        <div class="mt-4">
                            <a href="{{ route('admin.reports.index') }}" class="text-indigo-600 hover:text-indigo-900">View Reports →</a>
                        </div>
                    </div>
                </div>
                @endadminpermission
            </div>
        </div>

        <div>
            <h2 class="text-xl font-medium mb-4">System Statistics</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-dashboard.stat-card title="Total Users" :value="$this->userCount">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </x-slot>
                </x-dashboard.stat-card>

                <x-dashboard.stat-card title="Total Inventory Items" :value="$this->itemCount">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </x-slot>
                </x-dashboard.stat-card>

                <x-dashboard.stat-card title="Pending Transfers" :value="$this->pendingTransfersCount">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot>
                </x-dashboard.stat-card>

                <x-dashboard.stat-card title="Recent Activity" :value="$this->auditLogCount">
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </x-slot>
                </x-dashboard.stat-card>
            </div>
        </div>
    </div>
</div> 