<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\DivisionInventoryManager;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DivisionInventoryManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding division inventory managers...');

        $divisions = Division::all();
        if ($divisions->isEmpty()) {
            $this->command->warn('No divisions found. Please run DivisionSeeder first.');
            return;
        }

        foreach ($divisions as $division) {
            // Create a user for inventory manager
            $user = User::create([
                'name' => $division->name . ' Inventory Manager',
                'username' => 'manager_' . strtolower($division->code),
                'email' => 'manager.' . strtolower($division->code) . '@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);

            // Create division inventory manager record
            DivisionInventoryManager::create([
                'user_id' => $user->id,
                'division_id' => $division->id,
            ]);

            $this->command->info("Created inventory manager for division: {$division->name}");
        }

        $this->command->info('Finished seeding division inventory managers.');
    }
}
