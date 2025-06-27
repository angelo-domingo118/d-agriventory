<?php

namespace Tests\Unit;

use App\Models\AdminUser;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionService $permissionService;

    protected User $regularUser;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionService = new PermissionService;

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
        $this->adminUser->load('adminUser');
    }

    public function test_user_has_permission_returns_false_for_regular_users()
    {
        $result = $this->permissionService->userHasPermission($this->regularUser, 'view_users');
        $this->assertFalse($result);
    }

    public function test_user_has_permission_returns_true_for_admin_users_regardless_of_permission()
    {
        $result = $this->permissionService->userHasPermission($this->adminUser, 'view_users');
        $this->assertTrue($result);

        $result = $this->permissionService->userHasPermission($this->adminUser, 'delete_users');
        $this->assertTrue($result);

        $result = $this->permissionService->userHasPermission($this->adminUser, 'manage_settings');
        $this->assertTrue($result);
    }

    public function test_user_has_role_returns_correct_value_for_different_roles()
    {
        $this->assertFalse($this->permissionService->userHasRole($this->regularUser, 'admin'));
        $this->assertTrue($this->permissionService->userHasRole($this->adminUser, 'admin'));
        $this->assertFalse($this->permissionService->userHasRole($this->adminUser, 'non_existent_role'));
    }

    public function test_get_all_permissions_returns_all_allowed_permissions()
    {
        $permissions = $this->permissionService->getAllPermissions();
        $this->assertEquals(AdminUser::ALLOWED_PERMISSIONS, $permissions);
    }
}
