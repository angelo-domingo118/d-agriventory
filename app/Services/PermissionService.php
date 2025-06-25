<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Models\User;

class PermissionService
{
    /**
     * Check if a user has a specific permission.
     *
     * @param  User  $user  The user to check
     * @param  string  $permission  The permission to check for
     * @return bool True if the user has the permission, false otherwise
     */
    public function userHasPermission(User $user, string $permission): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        // Super admin and admin roles have all permissions for now
        if ($this->userHasRole($user, ['super_admin', 'admin'])) {
            return true;
        }

        // Ensure adminUser is not null before accessing properties
        if (! $user->adminUser) {
            return false;
        }

        // Check explicit permissions (for future role-based implementation)
        $permissions = $user->adminUser->permissions ?? [];

        return isset($permissions[$permission]) && $permissions[$permission] === true;
    }

    /**
     * Check if a user has any of the specified roles.
     *
     * @param  User  $user  The user to check
     * @param  string|array  $roles  The role(s) to check for
     * @return bool True if the user has any of the roles, false otherwise
     */
    public function userHasRole(User $user, string|array $roles): bool
    {
        if (! $user->isAdmin() || ! $user->adminUser) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];

        return in_array($user->adminUser->role, $roles);
    }

    /**
     * Get all available permissions.
     *
     * @return array The list of all available permissions
     */
    public function getAllPermissions(): array
    {
        return AdminUser::ALLOWED_PERMISSIONS;
    }

    /**
     * Get all permissions grouped by category.
     *
     * @return array The permissions grouped by category
     */
    public function getGroupedPermissions(): array
    {
        $grouped = [];

        foreach (AdminUser::ALLOWED_PERMISSIONS as $permission) {
            // Validate permission string format
            if (! str_contains($permission, '_')) {
                // Handle invalid format by using the entire string as both action and category
                $action = $permission;
                $category = 'other';
            } else {
                // Split permission name to get category
                $parts = explode('_', $permission);
                $action = array_shift($parts); // First part is the action (view, create, etc.)
                $category = implode('_', $parts); // Rest is the category (users, inventory, etc.)
            }

            if (! isset($grouped[$category])) {
                $grouped[$category] = [];
            }

            $grouped[$category][] = [
                'name' => $permission,
                'action' => $action,
                'label' => ucfirst($action).' '.ucfirst($category),
            ];
        }

        return $grouped;
    }

    /**
     * Get default permissions for a specific role.
     *
     * @param  string  $role  The role to get default permissions for
     * @return array The default permissions for the role
     */
    public function getDefaultPermissionsForRole(string $role): array
    {
        $allPermissions = $this->getAllPermissions();
        $permissions = [];

        // Initialize all permissions to false
        foreach ($allPermissions as $permission) {
            $permissions[$permission] = false;
        }

        switch ($role) {
            case 'super_admin':
            case 'admin':
                // Super admin and admin have all permissions
                foreach ($allPermissions as $permission) {
                    $permissions[$permission] = true;
                }
                break;

            case 'editor':
                // Editor has all permissions except delete
                foreach ($allPermissions as $permission) {
                    $permissions[$permission] = ! str_contains($permission, 'delete_');
                }
                break;

            case 'viewer':
                // Viewer only has view permissions
                foreach ($allPermissions as $permission) {
                    $permissions[$permission] = str_starts_with($permission, 'view_');
                }
                break;
        }

        return $permissions;
    }

    /**
     * Get all available admin roles.
     *
     * @return array The list of available roles
     */
    public function getAvailableRoles(): array
    {
        return [
            'super_admin' => 'Super Administrator',
            'admin' => 'Administrator',
            'editor' => 'Editor',
            'viewer' => 'Viewer',
        ];
    }
}
