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
}
