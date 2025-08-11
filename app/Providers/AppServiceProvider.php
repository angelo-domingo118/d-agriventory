<?php

namespace App\Providers;

use App\Models\{
    User, AdminUser, Employee, Division, DivisionInventoryManager,
    Supplier, Contract, ContractItem,
    PrimaryCategory, SecondaryCategory, ItemsCatalog, ItemSpecification,
    IcsNumber, ParNumber, IdrNumber,
    IcsItemBatch, ParItemBatch, IdrItemBatch,
    IcsTransfer, ParTransfer, AcknowledgementReceipt,
    ConsumableRecord, ConsumableItem,
    ItemComponent
};
use App\Observers\AuditLogObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register audit logging observers for all models
        $this->registerAuditObservers();

        // Admin user check
        Blade::if('admin', function () {
            return Auth::check() && Auth::user()->isAdmin();
        });

        // Admin permission check
        Blade::if('adminpermission', function (string $permission) {
            return Auth::check() && Auth::user()->hasAdminPermission($permission);
        });

        // Division inventory manager check
        Blade::if('inventorymanager', function () {
            return Auth::check() && Auth::user()->isDivisionInventoryManager();
        });
    }

    /**
     * Register audit logging observers for all models
     */
    private function registerAuditObservers(): void
    {
        $models = [
            User::class,
            AdminUser::class,
            Employee::class,
            Division::class,
            DivisionInventoryManager::class,
            Supplier::class,
            Contract::class,
            ContractItem::class,
            PrimaryCategory::class,
            SecondaryCategory::class,
            ItemsCatalog::class,
            ItemSpecification::class,
            IcsNumber::class,
            ParNumber::class,
            IdrNumber::class,
            IcsItemBatch::class,
            ParItemBatch::class,
            IdrItemBatch::class,
            IcsTransfer::class,
            ParTransfer::class,
            AcknowledgementReceipt::class,
            ConsumableRecord::class,
            ConsumableItem::class,
            ItemComponent::class,
        ];

        foreach ($models as $model) {
            $model::observe(AuditLogObserver::class);
        }
    }
}
