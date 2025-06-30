<?php

namespace Tests\Feature\Auth;

use App\Models\Division;
use App\Models\DivisionInventoryManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InventoryManagerAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_manager_is_redirected_to_dashboard_when_authenticated(): void
    {
        // Create a user with a specific password
        $password = 'password';
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'password' => Hash::make($password),
        ]);

        // Create a division inventory manager associated with the user
        // The factory will create a new division automatically.
        $manager = DivisionInventoryManager::factory()->create([
            'user_id' => $user->id,
        ]);

        // Verify the inventory manager record exists
        $this->assertDatabaseHas('division_inventory_managers', [
            'user_id' => $user->id,
            'division_id' => $manager->division_id,
        ]);

        // For Volt routes, we need to manually login the user for testing
        $this->assertFalse(Auth::check(), 'User should not be authenticated before login');

        // Manually authenticate the user for testing
        $this->actingAs($user);

        // Assert user is authenticated
        $this->assertAuthenticated();

        // Test dashboard redirection for inventory managers
        $response = $this->get('/dashboard');
        $response->assertRedirect(route('inventory-manager.dashboard'));
    }

    public function test_inventory_manager_cannot_access_admin_area(): void
    {
        // Create a user
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // Associate the user with a division inventory manager role
        DivisionInventoryManager::factory()->create([
            'user_id' => $user->id,
        ]);

        // Authenticate the user
        $this->actingAs($user);

        // Attempt to access admin area
        $response = $this->get(route('admin.dashboard'));

        // Should be redirected away from admin area
        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_without_roles_cannot_access_inventory_manager_area(): void
    {
        // Create a regular user with no roles
        $user = User::create([
            'name' => 'Regular User',
            'username' => 'regular_user',
            'email' => 'regular@example.com',
            'password' => Hash::make('password'),
        ]);

        // Authenticate the user
        $this->actingAs($user);

        // Attempt to access inventory manager area
        $response = $this->get('/inventory-manager/dashboard');

        // Should be redirected away from inventory manager area
        $response->assertRedirect(route('dashboard'));
    }
}
