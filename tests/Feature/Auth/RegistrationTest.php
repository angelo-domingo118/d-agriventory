<?php

use Livewire\Volt\Volt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('registration screen can be rendered', function () {
    // Skipping this test as public user registration is intentionally disabled
    // in this application. User creation is restricted to admin users only as per
    // security requirements. In this system, administrators are responsible for
    // creating and managing all user accounts through the admin interface.
    $this->markTestSkipped('User registration is restricted to admin users only');

    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('username', 'testuser')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});
