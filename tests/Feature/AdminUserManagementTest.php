<?php

use App\Models\AdminUser;
use App\Models\User;
use App\Models\Division;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

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
        ->assertForbidden();
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

test('admin users can create a new admin user', function () {
    $this->actingAs($this->admin);

    Livewire::test('admin.system.users.create')
        ->set('name', 'New Admin User')
        ->set('username', 'newadmin')
        ->set('email', 'newadmin@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->set('userType', 'admin')
        ->call('store')
        ->assertRedirect(route('admin.system.users.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'newadmin@example.com',
        'username' => 'newadmin',
    ]);

    $newUser = User::where('email', 'newadmin@example.com')->first();
    $this->assertDatabaseHas('admin_users', [
        'user_id' => $newUser->id,
    ]);
});

test('admin users can create a new inventory manager user', function () {
    $this->actingAs($this->admin);
    $division = Division::factory()->create();

    Livewire::test('admin.system.users.create')
        ->set('name', 'New Manager')
        ->set('username', 'newmanager')
        ->set('email', 'manager@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->set('userType', 'inventory_manager')
        ->set('divisionId', $division->id)
        ->call('store')
        ->assertRedirect(route('admin.system.users.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'manager@example.com',
        'username' => 'newmanager',
    ]);

    $newUser = User::where('email', 'manager@example.com')->first();
    $this->assertDatabaseHas('division_inventory_managers', [
        'user_id' => $newUser->id,
        'division_id' => $division->id,
    ]);
});

test('admin users cannot create a user with invalid data', function () {
    $this->actingAs($this->admin);

    Livewire::test('admin.system.users.create')
        ->set('name', '')
        ->set('username', '')
        ->set('email', 'not-an-email')
        ->set('password', 'short')
        ->set('password_confirmation', 'not-matching')
        ->call('store')
        ->assertHasErrors(['name', 'username', 'email', 'password']);
});

test('regular users cannot access admin user management', function () {
    $this->actingAs($this->regularUser)
        ->get(route('admin.system.users.index'))
        ->assertForbidden();

    $this->actingAs($this->regularUser)
        ->get(route('admin.system.users.create'))
        ->assertForbidden();

    $user = User::factory()->create();
    $this->actingAs($this->regularUser)
        ->get(route('admin.system.users.edit', $user))
        ->assertForbidden();
});
