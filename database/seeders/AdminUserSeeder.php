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

        // Create a default admin user
        $adminUser = User::create([
            'name' => 'Admin',
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
    }
}
