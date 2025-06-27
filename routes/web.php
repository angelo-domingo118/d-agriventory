<?php

use App\Http\Middleware\IsInventoryManager;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('dashboard', function () {
    $user = \Illuminate\Support\Facades\Auth::user();

    // Eager load relationships to avoid N+1 queries
    if ($user) {
        $user->load(['adminUser', 'divisionInventoryManager']);
    }

    if ($user && $user->adminUser) {
        return redirect()->route('admin.dashboard');
    } elseif ($user && $user->divisionInventoryManager) {
        return redirect()->route('inventory-manager.dashboard');
    }

    // Fallback for users without specific roles
    return redirect()->route('home')->with('error', 'Your account does not have any assigned roles.');
})
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('reports', 'reports')
    ->middleware(['auth', 'verified'])
    ->name('reports');

Route::view('settings', 'settings')
    ->middleware(['auth'])
    ->name('settings');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Route::prefix('inventory-manager')
    ->as('inventory-manager.')
    ->middleware(['auth', IsInventoryManager::class])
    ->group(function () {
        Volt::route('dashboard', 'inventory-manager.dashboard')->name('dashboard');
        Volt::route('items', 'inventory-manager.items.index')->name('items.index');
        Volt::route('transfers', 'inventory-manager.transfers.index')->name('transfers.index');
        Volt::route('reports', 'inventory-manager.reports.index')->name('reports.index');
    });

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
