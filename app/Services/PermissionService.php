<?php

namespace App\Services;

use App\Enums\User\Role;
use App\Exceptions\InvalidRoleException;
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

        // Admins have all permissions.
        return true;
    }

    /**
     * Check if a user has a specific role.
     *
     * @param  User  $user  The user to check
     * @param  string  $role  The role to check for
     * @return bool True if the user has the role, false otherwise
     */
    public function userHasRole(User $user, string $role): bool
    {
        if (! $user->isAdmin() || ! $user->adminUser) {
            return false;
        }

        return $user->adminUser->role === $role;
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
     * Get default permissions for a given role.
     *
     * @param string $role
     * @return array
     * @throws InvalidRoleException
     */
    public function getDefaultsByRole(string $role): array
    {
        if (!in_array($role, Role::values())) {
            throw new InvalidRoleException("Invalid role specified: {$role}");
        }

        $allPermissions = array_fill_keys($this->getAllPermissions(), false);

        if ($role === Role::ADMIN->value) {
            return array_fill_keys($this->getAllPermissions(), true);
        }

        if ($role === Role::INVENTORY_MANAGER->value) {
            $inventoryPermissions = [
                'view_inventory' => true,
                'create_inventory' => true,
                'edit_inventory' => true,
                'delete_inventory' => true,
                'view_reports' => true,
            ];
            return array_merge($allPermissions, $inventoryPermissions);
        }

        return $allPermissions;
    }
}
