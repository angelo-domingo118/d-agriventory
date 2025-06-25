<?php

namespace App\Providers;

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
}
