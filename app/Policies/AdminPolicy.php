<?php

namespace App\Policies;

use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminPolicy
{
    use HandlesAuthorization;

    /**
     * @var PermissionService
     */
    protected $permissionService;

    /**
     * Create a new policy instance.
     */
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Generic method to check if a user has a specific permission.
     *
     * @param  User  $user  The user to check
     * @param  string  $permission  The permission to check for
     */
    public function checkPermission(User $user, string $permission): bool
    {
        return $this->permissionService->userHasPermission($user, $permission);
    }

    /**
     * Magic method to handle dynamic permission checks.
     *
     * Method names are expected to follow patterns like:
     * - viewUsers -> view_users
     * - createInventory -> create_inventory
     *
     * @param  string  $method  The called method name
     * @param  array  $args  The method arguments
     */
    public function __call(string $method, array $args): bool
    {
        if (empty($args) || ! ($args[0] instanceof User)) {
            return false;
        }

        // Convert camelCase method name to snake_case permission
        $permission = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $method));

        return $this->checkPermission($args[0], $permission);
    }

    /**
     * Determine whether the user has super admin privileges.
     */
    public function superAdmin(User $user): bool
    {
        return $this->permissionService->userHasRole($user, 'super_admin');
    }

    /**
     * Determine whether the user has regular admin privileges.
     */
    public function admin(User $user): bool
    {
        return $this->permissionService->userHasRole($user, ['admin', 'super_admin']);
    }
}
