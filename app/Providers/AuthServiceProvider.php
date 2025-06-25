<?php

namespace App\Providers;

use App\Models\AdminUser;
use App\Models\User;
use App\Policies\AdminPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => AdminPolicy::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register default admin policy mappings
        $this->registerPolicies();

        // Define gates for each permission
        foreach (AdminUser::ALLOWED_PERMISSIONS as $permission) {
            $gateName = str_replace('_', '.', $permission);

            Gate::define($gateName, function (User $user) use ($permission) {
                return $user->hasAdminPermission($permission);
            });
        }

        // Define special gates for admin roles
        Gate::define('admin', function (User $user) {
            return $user->isAdmin() && $user->adminUser && in_array($user->adminUser->role, ['admin', 'super_admin']);
        });

        Gate::define('super-admin', function (User $user) {
            return $user->isAdmin() && $user->adminUser && $user->adminUser->role === 'super_admin';
        });
    }
}
