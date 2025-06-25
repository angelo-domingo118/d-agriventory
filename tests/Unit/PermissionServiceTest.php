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

    protected User $superAdminUser;

    protected User $editorUser;

    protected User $viewerUser;

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

        // Create a super admin user
        $this->superAdminUser = User::factory()->create();
        AdminUser::create([
            'user_id' => $this->superAdminUser->id,
            'role' => 'super_admin',
            'permissions' => null, // Super Admin role has all permissions by default
            'is_active' => true,
        ]);
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
        $this->editorUser->load('adminUser');

        // Create a viewer user with only view permissions
        $this->viewerUser = User::factory()->create();
        AdminUser::create([
            'user_id' => $this->viewerUser->id,
            'role' => 'viewer',
            'permissions' => json_encode([
                'view_users' => true,
                'create_users' => false,
                'edit_users' => false,
                'delete_users' => false,
                'view_inventory' => true,
                'view_reports' => true,
                'create_reports' => false,
                'export_reports' => false,
                'manage_settings' => false,
            ]),
            'is_active' => true,
        ]);
        $this->viewerUser->load('adminUser');
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

    public function test_user_has_permission_returns_true_for_super_admin_users_regardless_of_permission()
    {
        $result = $this->permissionService->userHasPermission($this->superAdminUser, 'view_users');
        $this->assertTrue($result);

        $result = $this->permissionService->userHasPermission($this->superAdminUser, 'delete_users');
        $this->assertTrue($result);

        $result = $this->permissionService->userHasPermission($this->superAdminUser, 'manage_settings');
        $this->assertTrue($result);
    }

    public function test_user_has_permission_returns_correct_value_for_editor_users_with_specific_permissions()
    {
        $result = $this->permissionService->userHasPermission($this->editorUser, 'view_users');
        $this->assertTrue($result);

        $result = $this->permissionService->userHasPermission($this->editorUser, 'create_users');
        $this->assertTrue($result);

        $result = $this->permissionService->userHasPermission($this->editorUser, 'delete_users');
        $this->assertFalse($result);

        $result = $this->permissionService->userHasPermission($this->editorUser, 'manage_settings');
        $this->assertFalse($result);
    }

    public function test_user_has_permission_returns_correct_value_for_viewer_users_with_view_only_permissions()
    {
        $result = $this->permissionService->userHasPermission($this->viewerUser, 'view_users');
        $this->assertTrue($result);

        $result = $this->permissionService->userHasPermission($this->viewerUser, 'create_users');
        $this->assertFalse($result);

        $result = $this->permissionService->userHasPermission($this->viewerUser, 'delete_users');
        $this->assertFalse($result);
    }

    public function test_user_has_role_returns_correct_value_for_different_roles()
    {
        $this->assertFalse($this->permissionService->userHasRole($this->regularUser, 'admin'));
        $this->assertTrue($this->permissionService->userHasRole($this->adminUser, 'admin'));
        $this->assertTrue($this->permissionService->userHasRole($this->superAdminUser, 'super_admin'));
        $this->assertTrue($this->permissionService->userHasRole($this->editorUser, 'editor'));
        $this->assertTrue($this->permissionService->userHasRole($this->viewerUser, 'viewer'));

        // Test with array of roles
        $this->assertTrue($this->permissionService->userHasRole($this->adminUser, ['admin', 'super_admin']));
        $this->assertTrue($this->permissionService->userHasRole($this->superAdminUser, ['admin', 'super_admin']));
        $this->assertFalse($this->permissionService->userHasRole($this->editorUser, ['admin', 'super_admin']));
    }

    public function test_get_all_permissions_returns_all_allowed_permissions()
    {
        $permissions = $this->permissionService->getAllPermissions();
        $this->assertEquals(AdminUser::ALLOWED_PERMISSIONS, $permissions);
    }

    public function test_get_grouped_permissions_returns_permissions_grouped_by_category()
    {
        $groupedPermissions = $this->permissionService->getGroupedPermissions();

        // Check that we have the expected categories based on the ALLOWED_PERMISSIONS
        $this->assertArrayHasKey('users', $groupedPermissions);
        $this->assertArrayHasKey('inventory', $groupedPermissions);
        $this->assertArrayHasKey('reports', $groupedPermissions);
        $this->assertArrayHasKey('settings', $groupedPermissions);

        // Verify the users group has the expected permission entries
        foreach (['view', 'create', 'edit', 'delete'] as $action) {
            $found = false;
            foreach ($groupedPermissions['users'] as $permission) {
                if ($permission['action'] === $action) {
                    $found = true;
                    $this->assertEquals("{$action}_users", $permission['name']);
                    break;
                }
            }
            $this->assertTrue($found, "Action '{$action}' not found in users permissions");
        }
    }

    public function test_get_default_permissions_for_role_returns_all_true_for_super_admin()
    {
        $permissions = $this->permissionService->getDefaultPermissionsForRole('super_admin');

        foreach ($permissions as $permission => $value) {
            $this->assertTrue($value);
        }
    }

    public function test_get_default_permissions_for_role_returns_all_true_for_admin()
    {
        $permissions = $this->permissionService->getDefaultPermissionsForRole('admin');

        foreach ($permissions as $permission => $value) {
            $this->assertTrue($value);
        }
    }

    public function test_get_default_permissions_for_role_returns_view_create_edit_true_but_delete_false_for_editor()
    {
        $permissions = $this->permissionService->getDefaultPermissionsForRole('editor');

        foreach ($permissions as $permission => $value) {
            if (str_contains($permission, 'delete_')) {
                $this->assertFalse($value);
            } else {
                $this->assertTrue($value);
            }
        }
    }

    public function test_get_default_permissions_for_role_returns_only_view_true_for_viewer()
    {
        $permissions = $this->permissionService->getDefaultPermissionsForRole('viewer');

        foreach ($permissions as $permission => $value) {
            if (str_starts_with($permission, 'view_')) {
                $this->assertTrue($value);
            } else {
                $this->assertFalse($value);
            }
        }
    }

    public function test_get_available_roles_returns_array_with_expected_roles()
    {
        $roles = $this->permissionService->getAvailableRoles();

        $this->assertIsArray($roles);
        $this->assertArrayHasKey('super_admin', $roles);
        $this->assertArrayHasKey('admin', $roles);
        $this->assertArrayHasKey('editor', $roles);
        $this->assertArrayHasKey('viewer', $roles);
    }
}
