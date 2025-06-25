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

    // Create a super admin user
    $this->superAdminUser = User::factory()->create();
    AdminUser::create([
        'user_id' => $this->superAdminUser->id,
        'role' => 'super_admin',
        'permissions' => null, // Super Admin role has all permissions by default
        'is_active' => true,
    ]);
    // Load the admin relationship explicitly
    $this->superAdminUser->load('adminUser');

    // Create an editor user with specific permissions
    $this->editorUser = User::factory()->create();
    AdminUser::create([
        'user_id' => $this->editorUser->id,
        'role' => 'editor',
        'permissions' => json_encode([
            'view_users' => true,
            'create_users' => true,
            'edit_users' => true,
            'delete_users' => false,
            'view_inventory' => true,
            'manage_settings' => false,
        ]),
        'is_active' => true,
    ]);
    // Load the admin relationship explicitly
    $this->editorUser->load('adminUser');

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

test('is admin middleware allows access to super admin users', function () {
    $request = Request::create('/admin/dashboard');
    $request->setUserResolver(function () {
        return $this->superAdminUser;
    });

    $middleware = new IsAdmin;
    $response = $middleware->handle($request, function ($req) {
        return response('Allowed');
    });

    expect($response->getContent())->toBe('Allowed');
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

test('has admin permission middleware redirects when user lacks the required permission', function () {
    $request = Request::create('/admin/settings');
    $request->setUserResolver(function () {
        return $this->editorUser;
    });

    $middleware = new HasAdminPermission;
    $response = $middleware->handle($request, function ($req) {
        return response('Allowed');
    }, 'manage_settings');

    expect($response)->toBeInstanceOf(\Illuminate\Http\RedirectResponse::class);
});

test('editor user has access to permitted actions', function () {
    $request = Request::create('/admin/users');
    $request->setUserResolver(function () {
        return $this->editorUser;
    });

    $middleware = new HasAdminPermission;
    $response = $middleware->handle($request, function ($req) {
        return response('Allowed');
    }, 'view_users');

    expect($response->getContent())->toBe('Allowed');
});

test('editor user is blocked from unpermitted actions', function () {
    $request = Request::create('/admin/users/delete');
    $request->setUserResolver(function () {
        return $this->editorUser;
    });

    $middleware = new HasAdminPermission;
    $response = $middleware->handle($request, function ($req) {
        return response('Allowed');
    }, 'delete_users');

    expect($response)->toBeInstanceOf(\Illuminate\Http\RedirectResponse::class);
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
