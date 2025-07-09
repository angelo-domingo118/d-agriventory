<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Cache;

trait ClearsDashboardCache
{
    /**
     * Boot the trait.
     *
     * @return void
     */
    protected static function bootClearsDashboardCache(): void
    {
        static::saved(function () {
            static::clearDashboardCache();
        });

        static::deleted(function () {
            static::clearDashboardCache();
        });
    }

    /**
     * Clear the dashboard cache.
     *
     * @return void
     */
    public static function clearDashboardCache(): void
    {
        Cache::forget('admin.dashboard.stats');
        Cache::forget('admin.dashboard.alerts');
        Cache::forget('admin.dashboard.division_inventory');
        Cache::forget('admin.dashboard.category_inventory');
        Cache::forget('admin.dashboard.supplier_spending');
    }
} 