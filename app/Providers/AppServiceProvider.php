<?php

namespace App\Providers;

use App\Models\AcknowledgementReceipt;
use App\Models\AdminUser;
use App\Models\ConsumableItem;
use App\Models\ConsumableRecord;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Division;
use App\Models\DivisionInventoryManager;
use App\Models\Employee;
use App\Models\IcsItemBatch;
use App\Models\IcsNumber;
use App\Models\IcsTransfer;
use App\Models\IdrItemBatch;
use App\Models\IdrNumber;
use App\Models\ItemComponent;
use App\Models\ItemsCatalog;
use App\Models\ItemSpecification;
use App\Models\ParItemBatch;
use App\Models\ParNumber;
use App\Models\ParTransfer;
use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use App\Models\Supplier;
use App\Models\User;
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
