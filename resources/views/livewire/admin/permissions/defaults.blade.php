<?php

use function Livewire\Volt\{state};
use App\Services\PermissionService;

state([
    'role' => '',
    'permissionService' => null,
]);

$mount = function (string $role) {
    $this->role = $role;
    $this->permissionService = app(PermissionService::class);
};

$getPermissions = function () {
    return $this->permissionService->getDefaultPermissionsForRole($this->role);
};

$render = function () {
    // Return JSON response with default permissions for the role
    return response()->json([
        'role' => $this->role,
        'permissions' => $this->getPermissions(),
    ]);
};

?>

<!-- No template needed for API endpoint --> 