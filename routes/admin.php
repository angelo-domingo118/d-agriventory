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
    Route::prefix('api')->name('api.')->group(function () {
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
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::middleware([HasAdminPermission::class.':view_inventory'])->group(function () {
            Volt::route('/', 'admin.inventory.index')->name('index');
            Volt::route('ics', 'admin.inventory.ics.index')->name('ics.index');
            Volt::route('ics/{icsNumber}', 'admin.inventory.ics.show')->name('ics.show');
            Volt::route('par', 'admin.inventory.par.index')->name('par.index');
            Volt::route('par/{parNumber}', 'admin.inventory.par.show')->name('par.show');
            Volt::route('idr', 'admin.inventory.idr.index')->name('idr.index');
            Volt::route('idr/{idrNumber}', 'admin.inventory.idr.show')->name('idr.show');
            Volt::route('consumables', 'admin.inventory.consumables.index')->name('consumables.index');
        });

        Route::middleware([HasAdminPermission::class.':create_inventory'])->group(function () {
            Volt::route('ics-create', 'admin.inventory.ics.create')->name('ics.create');
            Volt::route('par-create', 'admin.inventory.par.create')->name('par.create');
            Volt::route('idr-create', 'admin.inventory.idr.create')->name('idr.create');
        });

        Route::middleware([HasAdminPermission::class.':edit_inventory'])->group(function () {
            Volt::route('ics/{icsNumber}/edit', 'admin.inventory.ics.edit')->name('ics.edit');
            Volt::route('par/{parNumber}/edit', 'admin.inventory.par.edit')->name('par.edit');
            Volt::route('idr/{idrNumber}/edit', 'admin.inventory.idr.edit')->name('idr.edit');
        });
    });

    // Data management routes
    Route::prefix('data')->name('data.')->group(function () {
        Route::middleware([HasAdminPermission::class.':manage_data'])->group(function () {
            Volt::route('employees-and-divisions', 'admin.data.employees-and-divisions.index')->name('employees-and-divisions');
            Route::prefix('employees-and-divisions')->name('employees-and-divisions.')->group(function () {
                Volt::route('divisions', 'admin.data.employees-and-divisions.divisions.index')->name('divisions.index');
                Volt::route('divisions/create', 'admin.data.employees-and-divisions.divisions.create')->name('divisions.create');
                Volt::route('divisions/{division}/edit', 'admin.data.employees-and-divisions.divisions.edit')->name('divisions.edit');

                Volt::route('positions/create', 'admin.data.employees-and-divisions.positions.create')->name('positions.create');
                Volt::route('positions/{position}/edit', 'admin.data.employees-and-divisions.positions.edit')->name('positions.edit');

                Volt::route('employees/create', 'admin.data.employees-and-divisions.employees.create')->name('employees.create');
                Volt::route('employees/{employee}/edit', 'admin.data.employees-and-divisions.employees.edit')->name('employees.edit');
            });
            Volt::route('items-and-categories', 'admin.data.items-and-categories.index')->name('items-and-categories');
            Route::prefix('items-and-categories')->name('items-and-categories.')->group(function () {
                Volt::route('items-catalog/create', 'admin.data.items-and-categories.items-catalog.create')->name('items-catalog.create');
                Volt::route('items-catalog/{item}/edit', 'admin.data.items-and-categories.items-catalog.edit')->name('items-catalog.edit');

                Volt::route('primary-categories/create', 'admin.data.items-and-categories.primary-categories.create')->name('primary-categories.create');
                Volt::route('primary-categories/{category}/edit', 'admin.data.items-and-categories.primary-categories.edit')->name('primary-categories.edit');

                Volt::route('secondary-categories/create', 'admin.data.items-and-categories.secondary-categories.create')->name('secondary-categories.create');
                Volt::route('secondary-categories/{category}/edit', 'admin.data.items-and-categories.secondary-categories.edit')->name('secondary-categories.edit');
            });

            Volt::route('suppliers-and-contracts', 'admin.data.suppliers-and-contracts.index')->name('suppliers-and-contracts');
            Route::prefix('suppliers-and-contracts')->name('suppliers-and-contracts.')->group(function () {
                Route::prefix('suppliers')->name('suppliers.')->group(function () {
                    Volt::route('/', 'admin.data.suppliers-and-contracts.suppliers.index')->name('index');
                    Volt::route('create', 'admin.data.suppliers-and-contracts.suppliers.create')->name('create');
                    Volt::route('{supplier}/edit', 'admin.data.suppliers-and-contracts.suppliers.edit')->name('edit');
                });

                Route::prefix('contracts')->name('contracts.')->group(function () {
                    Volt::route('/', 'admin.data.suppliers-and-contracts.contracts.index')->name('index');
                    Volt::route('create', 'admin.data.suppliers-and-contracts.contracts.create')->name('create');
                    Volt::route('{contract}/edit', 'admin.data.suppliers-and-contracts.contracts.edit')->name('edit');
                });
            });
        });
    });

    // Main routes (reports)
    Route::prefix('main')->name('main.')->group(function () {
        Route::middleware([HasAdminPermission::class.':view_reports'])->group(function () {
            Volt::route('reports', 'admin.main.reports.index')->name('reports.index');
        });
    });
});
