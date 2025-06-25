<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the admin user record associated with the user.
     */
    public function adminUser(): HasOne
    {
        return $this->hasOne(AdminUser::class);
    }

    /**
     * Get the division inventory manager record associated with the user.
     */
    public function divisionInventoryManager(): HasOne
    {
        return $this->hasOne(DivisionInventoryManager::class);
    }

    /**
     * Get the audit logs associated with the user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->adminUser !== null;
    }

    /**
     * Check if the user is a division inventory manager.
     */
    public function isDivisionInventoryManager(): bool
    {
        if ($this->relationLoaded('divisionInventoryManager')) {
            return $this->divisionInventoryManager !== null;
        }

        return $this->divisionInventoryManager()->exists();
    }

    /**
     * Check if the user has a specific admin permission.
     */
    public function hasAdminPermission(string $permission): bool
    {
        if (! $this->isAdmin()) {
            return false;
        }

        // Ensure adminUser is loaded and not null
        if (! $this->relationLoaded('adminUser')) {
            $this->load('adminUser');
        }

        // If adminUser is still null, return false
        if ($this->adminUser === null) {
            return false;
        }

        // For now, admin and super_admin have all permissions
        if (in_array($this->adminUser->role, ['admin', 'super_admin'])) {
            return true;
        }

        // For future use: check specific permissions
        $permissions = $this->adminUser->permissions ?? [];

        return isset($permissions[$permission]) && $permissions[$permission] === true;
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }
}
