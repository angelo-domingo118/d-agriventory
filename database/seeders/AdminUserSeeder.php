<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Set password based on environment
        $password = app()->environment('production')
            ? config('app.admin_password', 'Ch@ngeMe!23') // Secure default if no config set
            : 'password'; // Simple password for non-production environments

        // Create a super admin user
        $superAdminUser = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'username' => 'superadmin',
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        AdminUser::create([
            'user_id' => $superAdminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
            'last_login_at' => now(),
        ]);

        // Create a regular admin user
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'username' => 'admin',
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        AdminUser::create([
            'user_id' => $adminUser->id,
            'role' => 'admin',
            'is_active' => true,
            'last_login_at' => now(),
        ]);

        // Create an editor admin with specific permissions
        $editorUser = User::create([
            'name' => 'Editor User',
            'email' => 'editor@example.com',
            'username' => 'editor',
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        AdminUser::create([
            'user_id' => $editorUser->id,
            'role' => 'editor',
            'permissions' => json_encode([
                'view_users' => true,
                'view_inventory' => true,
                'edit_inventory' => true,
                'view_reports' => true,
                'create_reports' => true,
            ]),
            'is_active' => true,
            'last_login_at' => now(),
        ]);
    }
}
