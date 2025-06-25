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
        Volt::route('inventory', 'admin.inventory.index')
            ->name('admin.inventory.index');
    });

    // Reports management routes
    Route::middleware([HasAdminPermission::class.':view_reports'])->group(function () {
        Volt::route('reports', 'admin.reports.index')
            ->name('admin.reports.index');
    });
});
