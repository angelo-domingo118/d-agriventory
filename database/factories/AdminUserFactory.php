<?php

namespace Database\Factories;

use App\Models\AdminUser;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdminUser>
 */
class AdminUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roles = ['admin', 'super_admin', 'editor', 'viewer'];
        $role = $this->faker->randomElement($roles);

        return [
            'user_id' => User::factory(),
            'role' => $role,
            'permissions' => null, // Will be set based on role in afterCreating
            'is_active' => true,
            'last_login_at' => $this->faker->optional(0.7)->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (AdminUser $adminUser) {
            // If permissions are null, set default permissions based on role
            if ($adminUser->permissions === null) {
                $permissionService = app(PermissionService::class);
                $defaultPermissions = $permissionService->getDefaultPermissionsForRole($adminUser->role);

                // Update using Eloquent's update method instead of direct property modification
                $adminUser->update([
                    'permissions' => $defaultPermissions,
                ]);
            }
        });
    }

    /**
     * Indicate that the admin user has the super_admin role.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function superAdmin()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'super_admin',
            ];
        });
    }

    /**
     * Indicate that the admin user has the admin role.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function admin()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'admin',
            ];
        });
    }

    /**
     * Indicate that the admin user has the editor role.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function editor()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'editor',
            ];
        });
    }

    /**
     * Indicate that the admin user has the viewer role.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function viewer()
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'viewer',
            ];
        });
    }

    /**
     * Indicate that the admin user has custom permissions.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withPermissions(array $permissions)
    {
        return $this->state(function (array $attributes) use ($permissions) {
            return [
                'permissions' => $permissions,
            ];
        });
    }
}
