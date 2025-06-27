<?php

use App\Http\Middleware\HasAdminPermission;
use App\Http\Middleware\IsAdmin;
use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a regular user
    $this->regularUser = User::factory()->create();

    // Create an admin user
    $this->adminUser = User::factory()->create();
    AdminUser::create([
        'user_id' => $this->adminUser->id,
        'role' => 'admin',
        'permissions' => null, // Admin role has all permissions by default
        'is_active' => true,
    ]);
    // Load the admin relationship explicitly
    $this->adminUser->load('adminUser');

    // Create an inactive admin user
    $this->inactiveAdminUser = User::factory()->create();
    AdminUser::create([
        'user_id' => $this->inactiveAdminUser->id,
        'role' => 'admin',
        'permissions' => null,
        'is_active' => false,
    ]);
    // Load the admin relationship explicitly
    $this->inactiveAdminUser->load('adminUser');
});

test('is admin middleware allows access to admin users', function () {
    $request = Request::create('/admin/dashboard');
    $request->setUserResolver(function () {
        return $this->adminUser;
    });

    $middleware = new IsAdmin;
    $response = $middleware->handle($request, function ($req) {
        return response('Allowed');
    });

    expect($response->getContent())->toBe('Allowed');
});

test('is admin middleware redirects regular users', function () {
    $request = Request::create('/admin/dashboard');
    $request->setUserResolver(function () {
        return $this->regularUser;
    });

    $middleware = new IsAdmin;
    $response = $middleware->handle($request, function ($req) {
        return response('Allowed');
    });

    expect($response)->toBeInstanceOf(\Illuminate\Http\RedirectResponse::class);
});

test('has admin permission middleware allows access when user has the required permission', function () {
    $request = Request::create('/admin/users');
    $request->setUserResolver(function () {
        return $this->adminUser;
    });

    $middleware = new HasAdminPermission;
    $response = $middleware->handle($request, function ($req) {
        return response('Allowed');
    }, 'view_users');

    expect($response->getContent())->toBe('Allowed');
});

test('inactive admin user is blocked from admin routes', function () {
    $request = Request::create('/admin/dashboard');
    $request->setUserResolver(function () {
        return $this->inactiveAdminUser;
    });

    $middleware = new IsAdmin;
    $response = $middleware->handle($request, function ($req) {
        return response('Allowed');
    });

    expect($response)->toBeInstanceOf(\Illuminate\Http\RedirectResponse::class);
});
