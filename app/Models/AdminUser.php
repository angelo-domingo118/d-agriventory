<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;

class AdminUser extends Model
{
    use HasFactory;

    /**
     * The allowed permission keys.
     *
     * @var array<int, string>
     */
    public const ALLOWED_PERMISSIONS = [
        'view_users', 'create_users', 'edit_users', 'delete_users',
        'view_inventory', 'create_inventory', 'edit_inventory', 'delete_inventory',
        'view_reports', 'create_reports', 'export_reports',
        'manage_settings',
        'view_employees_and_divisions',
        'view_suppliers_and_contracts',
        'view_permissions',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'role',
        'permissions',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'permissions' => 'json',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    /**
     * Validation rules for AdminUser attributes.
     *
     * Note: These rules are not automatically applied by Laravel.
     * Use the static rules() method to access these rules in controllers,
     * form requests, or validation logic.
     *
     * @var array<string, mixed>
     */
    protected static $rules = [
        'user_id' => 'required|integer',
        'role' => 'required|string|in:admin',
        'permissions' => 'nullable|json',
        'is_active' => 'boolean',
        'last_login_at' => 'nullable|date',
    ];

    /**
     * Get the validation rules for AdminUser attributes.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return static::$rules;
    }

    /**
     * Get the user that this admin record belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Set the role attribute.
     *
     * @param  string  $value
     */
    public function setRoleAttribute($value): void
    {
        $this->attributes['role'] = $value;
    }

    /**
     * Validate if the given role is valid.
     */
    public function validateRole(string $role): bool
    {
        $validator = Validator::make(['role' => $role], [
            'role' => static::$rules['role'],
        ]);

        return ! $validator->fails();
    }

    /**
     * Get error message for invalid role.
     */
    public function getRoleErrorMessage(string $role): string
    {
        return "Invalid role: {$role}. Allowed roles are: admin";
    }

    /**
     * Set the permissions attribute.
     *
     * @param  mixed  $value
     */
    public function setPermissionsAttribute($value): void
    {
        // If null, set as null and return
        if ($value === null) {
            $this->attributes['permissions'] = null;

            return;
        }

        // Handle array input
        if (is_array($value)) {
            if (! $this->validatePermissionStructure($value)) {
                // Don't throw an exception, just assign the invalid value
                // Validation should be done outside the mutator
                $this->attributes['permissions'] = json_encode($value);

                return;
            }

            $jsonValue = json_encode($value);
            if ($jsonValue === false) {
                // Handle json_encode failure
                $this->attributes['permissions'] = null;

                return;
            }

            $this->attributes['permissions'] = $jsonValue;

            return;
        }

        // Handle string input
        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded) || ! $this->validatePermissionStructure($decoded)) {
            // Don't throw an exception, just assign the value as is
            // Validation should be handled outside the mutator
            $this->attributes['permissions'] = $value;

            return;
        }

        // Assign the valid JSON string
        $this->attributes['permissions'] = $value;
    }

    /**
     * Validate the permissions data.
     *
     * @param  mixed  $permissions
     * @return array|bool Array of errors or true if valid
     */
    public function validatePermissions($permissions)
    {
        if ($permissions === null) {
            return true;
        }

        $permissionsString = is_array($permissions) ? json_encode($permissions) : $permissions;

        // Check if it's valid JSON
        $decoded = json_decode($permissionsString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['Invalid JSON format: '.json_last_error_msg()];
        }

        // Check if it's an array after decoding
        if (! is_array($decoded)) {
            return ['Permissions must be a JSON object'];
        }

        // Check permission structure
        if (! $this->validatePermissionStructure($decoded)) {
            return ['The permissions structure is invalid. It must contain valid permission keys and boolean values.'];
        }

        return true;
    }

    /**
     * Validate the structure of the permissions array.
     */
    private function validatePermissionStructure(array $permissions): bool
    {
        // Check if all keys are valid and values are boolean
        foreach ($permissions as $key => $value) {
            if (! in_array($key, self::ALLOWED_PERMISSIONS) || ! is_bool($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\AdminUserFactory::new();
    }
}
