# Admin User Implementation Documentation

This document provides an overview of the admin user implementation in the D'Agriventory system.

## Overview

The D'Agriventory system supports multiple user types:

1. **Regular Users**: Basic system users with no special privileges
2. **Admin Users**: Users with administrative capabilities
3. **Division Inventory Managers**: Users responsible for inventory management within specific divisions

This documentation focuses on the Admin User implementation, explaining the architecture, permissions system, and usage guidelines.

## Architecture

### Models

- **User**: The base user model that all user types extend
- **AdminUser**: A related model that contains admin-specific information:
  - `role`: The admin role (super_admin, admin, editor, viewer)
  - `permissions`: JSON-encoded permissions for fine-grained access control
  - `is_active`: Boolean flag indicating if the admin account is active

### User Types & Relationships

Each user in the system can have one of the following types:

```php
// Regular user (no additional relationship)
$user = User::find(1);

// Admin user
$user = User::find(2);
$adminUser = $user->adminUser; // AdminUser model

// Division Inventory Manager
$user = User::find(3);
$divisionManager = $user->divisionInventoryManager; // DivisionInventoryManager model
```

### Admin Roles

The system supports several admin roles with different levels of access:

1. **Super Admin**: Has complete access to all system functions
2. **Admin**: Has full access to administrative functions
3. **Editor**: Can view, create, and edit resources, but cannot delete them
4. **Viewer**: Can only view resources, with no creation, editing, or deletion capabilities

## Permissions System

### Permissions Structure

Permissions are defined as string identifiers in the `ALLOWED_PERMISSIONS` constant in the `AdminUser` model:

```php
public const ALLOWED_PERMISSIONS = [
    'view_users', 'create_users', 'edit_users', 'delete_users',
    'view_inventory', 'create_inventory', 'edit_inventory', 'delete_inventory',
    'view_reports', 'create_reports', 'export_reports',
    'manage_settings'
];
```

Each permission follows the pattern `{action}_{resource}` (e.g., `view_users`, `edit_inventory`).

### Permission Storage

For `super_admin` and `admin` roles, no explicit permissions are stored as they have full access. For `editor` and `viewer` roles, permissions are stored in the `permissions` column as a JSON string.

### Checking Permissions

Permissions can be checked in several ways:

1. **User Model Methods**:
   ```php
   $user->isAdmin(); // Check if user is an admin
   $user->hasAdminPermission('view_users'); // Check for specific permission
   ```

2. **PermissionService**:
   ```php
   $permissionService = app(App\Services\PermissionService::class);
   $permissionService->userHasPermission($user, 'view_users');
   $permissionService->userHasRole($user, 'admin');
   ```

3. **Gates** (for controllers and Blade templates):
   ```php
   Gate::allows('view.users'); // Note the dot notation
   ```

4. **Blade Directives**:
   ```blade
   @admin
       <!-- Content only visible to admin users -->
   @endadmin
   
   @adminpermission('view_users')
       <!-- Content only visible to users with 'view_users' permission -->
   @endadminpermission
   ```

5. **Middleware**:
   ```php
   Route::middleware(['auth', IsAdmin::class])->group(function () {
       // Routes protected by admin check
   });
   
   Route::middleware([HasAdminPermission::class.':view_users'])->group(function () {
       // Routes protected by specific permission check
   });
   ```

## Components

### User Management Components

The admin section includes several components for user management:

1. **User Index**: Lists all users with filtering and sorting capabilities
   - Route: `/admin/users`
   - Component: `resources/views/livewire/admin/users/index.blade.php`

2. **User Details**: Shows detailed user information
   - Route: `/admin/users/{user}`
   - Component: `resources/views/livewire/admin/users/show.blade.php`

3. **User Creation**: Form for creating new users
   - Route: `/admin/users/create`
   - Component: `resources/views/livewire/admin/users/create.blade.php`

4. **User Editing**: Form for editing existing users
   - Route: `/admin/users/{user}/edit`
   - Component: `resources/views/livewire/admin/users/edit.blade.php`

### Permissions Management Component

The system includes a reusable component for managing permissions:

```blade
<x-admin.permissions-manager :permissions="$permissions" />
```

This component displays permissions grouped by category and allows easy selection/deselection.

## Configuration

### Route Configuration

Admin routes are defined in `routes/admin.php` and are automatically loaded by the `RouteServiceProvider`. All admin routes are protected by the `auth` and `is.admin` middleware:

```php
Route::prefix('admin')->middleware(['auth', IsAdmin::class])->group(function () {
    // Admin routes...
});
```

### Audit Logging

User management actions are automatically logged in the `audit_logs` table, recording:

- The user who performed the action
- The type of action (created, updated, deleted)
- The affected resource
- Details of the changes made

## Best Practices

1. **Always Check Permissions**: Use middleware or explicit permission checks to secure admin routes and actions.

2. **Use Roles Appropriately**:
   - `super_admin`: Only for system administrators with unrestricted access
   - `admin`: For regular administrators who need full access to the admin panel
   - `editor`: For users who need to create and edit but not delete resources
   - `viewer`: For users who only need to view resources

3. **Custom Permissions**: For `editor` and `viewer` roles, customize permissions based on the user's specific needs.

4. **Audit Logging**: Always include proper audit logging for sensitive operations.

5. **Testing**: Write comprehensive tests for permission checks and admin functionality.

## Example Usage

### Creating an Admin User

```php
$user = User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'username' => 'admin',
    'password' => Hash::make('password'),
]);

AdminUser::create([
    'user_id' => $user->id,
    'role' => 'admin',
    'permissions' => null, // Full access for admin role
    'is_active' => true,
]);
```

### Creating a Restricted Admin User

```php
$user = User::create([
    'name' => 'Editor User',
    'email' => 'editor@example.com',
    'username' => 'editor',
    'password' => Hash::make('password'),
]);

$permissions = [
    'view_users' => true,
    'create_users' => true,
    'edit_users' => true,
    'delete_users' => false,
    'view_inventory' => true,
    'create_inventory' => true,
    'edit_inventory' => true,
    'delete_inventory' => false,
    'view_reports' => true,
    'create_reports' => true,
    'export_reports' => true,
    'manage_settings' => false,
];

AdminUser::create([
    'user_id' => $user->id,
    'role' => 'editor',
    'permissions' => json_encode($permissions),
    'is_active' => true,
]);
```

## Future Enhancements

1. **Role-Based Access Control**: Enhance the system with a more sophisticated RBAC model
2. **Permission Groups**: Group related permissions for easier management
3. **Permission Inheritance**: Implement hierarchical permissions
4. **Multi-Factor Authentication**: Add extra security for admin accounts
5. **Activity Dashboard**: Create a dashboard showing admin user activity 