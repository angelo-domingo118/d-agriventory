<?php

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create an admin user
    $this->admin = User::factory()->create();
    AdminUser::factory()->admin()->create([
        'user_id' => $this->admin->id,
    ]);

    // Create a regular user
    $this->regularUser = User::factory()->create();
});

test('admin dashboard is accessible by admin users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertStatus(200);
});

test('admin dashboard is not accessible by regular users', function () {
    $this->actingAs($this->regularUser)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('dashboard'));
});

test('admin users index page is accessible by admin users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.system.users.index'))
        ->assertStatus(200);
});

test('admin users can view user details', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.system.users.show', $user))
        ->assertStatus(200)
        ->assertSee($user->name)
        ->assertSee($user->email);
});

test('admin users can access the create new user page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.system.users.create'))
        ->assertStatus(200);
});

test('admin users can edit users', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.system.users.edit', $user))
        ->assertStatus(200)
        ->assertSee($user->name)
        ->assertSee($user->email);
});

test('regular users cannot access admin user management', function () {
    $this->actingAs($this->regularUser)
        ->get(route('admin.system.users.index'))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($this->regularUser)
        ->get(route('admin.system.users.create'))
        ->assertRedirect(route('dashboard'));

    $user = User::factory()->create();
    $this->actingAs($this->regularUser)
        ->get(route('admin.system.users.edit', $user))
        ->assertRedirect(route('dashboard'));
});
