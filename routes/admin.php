<?php

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

Route::prefix('admin')->middleware(['auth', IsAdmin::class])->group(function () {
    // Admin dashboard
    Volt::route('dashboard', 'admin.dashboard')
        ->name('admin.dashboard');

    // Permission management
    Volt::route('permissions/defaults/{role}', 'admin.permissions.defaults')
        ->name('admin.permissions.defaults');

    // User management routes with permission middleware
    Route::middleware([HasAdminPermission::class.':create_users'])->group(function () {
        Volt::route('users/create', 'admin.users.create')
            ->name('admin.users.create');

        // Add the store route
        Route::post('users', function () {
            return Volt::render('admin.users.create');
        })->name('admin.users.store');
    });

    Route::middleware([HasAdminPermission::class.':view_users'])->group(function () {
        Volt::route('users', 'admin.users.index')
            ->name('admin.users.index');

        Volt::route('users/{user}', 'admin.users.show')
            ->name('admin.users.show');
    });

    Route::middleware([HasAdminPermission::class.':edit_users'])->group(function () {
        Volt::route('users/{user}/edit', 'admin.users.edit')
            ->name('admin.users.edit');
    });

    // Inventory management routes
    Route::middleware([HasAdminPermission::class.':view_inventory'])->group(function () {
        // Main inventory route
        Volt::route('inventory', 'admin.inventory.index')
            ->name('admin.inventory.index');

        // ICS Management
        Volt::route('inventory/ics', 'admin.inventory.ics.index')
            ->name('admin.inventory.ics.index');

        // PAR Management
        Volt::route('inventory/par', 'admin.inventory.par.index')
            ->name('admin.inventory.par.index');

        // IDR Management
        Volt::route('inventory/idr', 'admin.inventory.idr.index')
            ->name('admin.inventory.idr.index');

        // Transfers Management
        Volt::route('inventory/transfers', 'admin.inventory.transfers.index')
            ->name('admin.inventory.transfers.index');

        // Consumables Management
        Volt::route('inventory/consumables', 'admin.inventory.consumables.index')
            ->name('admin.inventory.consumables.index');

        // Items & Categories
        Volt::route('inventory/items', 'admin.inventory.items.index')
            ->name('admin.inventory.items.index');

        // Suppliers & Contracts
        Volt::route('inventory/contracts', 'admin.inventory.contracts.index')
            ->name('admin.inventory.contracts.index');
    });

    // Reports management routes
    Route::middleware([HasAdminPermission::class.':view_reports'])->group(function () {
        Volt::route('reports', 'admin.reports.index')
            ->name('admin.reports.index');
    });

    // Employees & Divisions routes
    Route::middleware([HasAdminPermission::class.':view_employees'])->group(function () {
        Volt::route('employees', 'admin.employees.index')
            ->name('admin.employees.index');
    });

    // Audit Logs routes
    Route::middleware([HasAdminPermission::class.':view_logs'])->group(function () {
        Volt::route('logs', 'admin.logs.index')
            ->name('admin.logs.index');
    });
});
