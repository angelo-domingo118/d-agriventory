<?php

use App\Http\Controllers\Api\Admin\PermissionsController;
use App\Http\Middleware\HasAdminPermission;
use App\Http\Middleware\IsAdmin;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin routes for your application. These
| routes are loaded by the RouteServiceProvider and will be assigned to
| the "web" middleware group. Make something great!
|
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', IsAdmin::class])->group(function () {
    // Admin dashboard
    Volt::route('main/dashboard', 'admin.main.dashboard')
        ->name('dashboard');

    // API routes
    Route::prefix('api')->name('api.')->group(function() {
        Route::get('permissions/defaults/{role}', [PermissionsController::class, 'getDefaultsByRole'])
            ->name('permissions.defaults');
    });

    // System Routes
    Route::prefix('system')->name('system.')->group(function () {
        // Permission management
        Volt::route('permissions/defaults/{role}', 'admin.system.permissions.defaults')
            ->name('permissions.defaults');

        // User management routes
        Route::prefix('users')->name('users.')->group(function () {
            Route::middleware([HasAdminPermission::class.':create_users'])->group(function () {
                Volt::route('create', 'admin.system.users.create')->name('create');
                Route::post('/', fn () => Volt::render('admin.system.users.create'))->name('store');
            });

            Route::middleware([HasAdminPermission::class.':view_users'])->group(function () {
                Volt::route('/', 'admin.system.users.index')->name('index');
                Volt::route('{user}', 'admin.system.users.show')->name('show');
            });

            Route::middleware([HasAdminPermission::class.':edit_users'])->group(function () {
                Volt::route('{user}/edit', 'admin.system.users.edit')->name('edit');
            });
        });

        // Audit Logs routes
        Route::middleware([HasAdminPermission::class.':view_logs'])->group(function () {
            Volt::route('audit-logs', 'admin.system.audit-logs.index')
                ->name('audit-logs.index');
        });
    });

    // Inventory management routes
    Route::prefix('inventory')->name('inventory.')->middleware([HasAdminPermission::class.':view_inventory'])->group(function () {
        Volt::route('/', 'admin.inventory.index')->name('index');
        Volt::route('ics', 'admin.inventory.ics.index')->name('ics.index');
        Volt::route('par', 'admin.inventory.par.index')->name('par.index');
        Volt::route('idr', 'admin.inventory.idr.index')->name('idr.index');
        Volt::route('transfers', 'admin.inventory.transfers.index')->name('transfers.index');
        Volt::route('consumables', 'admin.inventory.consumables.index')->name('consumables.index');
    });

    // Data management routes
    Route::prefix('data')->name('data.')->group(function () {
        Route::middleware([HasAdminPermission::class.':view_inventory'])->group(function () {
            Volt::route('items-and-categories', 'admin.data.items-and-categories.index')->name('items.index');
            Volt::route('suppliers-and-contracts', 'admin.data.suppliers-and-contracts.index')->name('contracts.index');
        });
        Route::middleware([HasAdminPermission::class.':view_employees'])->group(function () {
            Volt::route('employees-and-divisions', 'admin.data.employees-and-divisions.index')->name('employees.index');
        });
    });

    // Main routes (reports)
    Route::prefix('main')->name('main.')->group(function () {
        Route::middleware([HasAdminPermission::class.':view_reports'])->group(function () {
            Volt::route('reports', 'admin.main.reports.index')->name('reports.index');
        });
    });
});
