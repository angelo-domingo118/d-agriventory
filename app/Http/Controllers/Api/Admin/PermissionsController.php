<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\InvalidRoleException;
use App\Http\Controllers\Controller;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class PermissionsController extends Controller
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function getDefaultsByRole(string $role): JsonResponse
    {
        Gate::authorize('view_permissions');

        try {
            $permissions = $this->permissionService->getDefaultsByRole($role);
            return response()->json([
                'role' => $role,
                'permissions' => $permissions,
            ]);
        } catch (InvalidRoleException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve default permissions.', [
                'role' => $role,
                'exception' => $e
            ]);
            return response()->json(['error' => 'Could not retrieve permissions.'], 500);
        }
    }
} 