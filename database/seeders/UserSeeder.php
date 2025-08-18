<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\DivisionInventoryManager;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating inventory managers for all divisions:');

        // Process divisions in chunks to avoid memory issues with large datasets.
        Division::chunk(100, function ($divisions) {
            foreach ($divisions as $division) {
                $username = strtolower(str_replace('-', '', $division->code)).'_manager';
                $this->createInventoryManager(
                    "{$division->code} Inventory Manager",
                    $username,
                    $division->id
                );
                $this->command->info("  Created manager '{$username}' for division: {$division->name}");
            }
        });

        $this->command->info('All users have password: "password"');
    }

    /**
     * Helper function to create an inventory manager.
     */
    private function createInventoryManager(string $name, string $username, int $divisionId): void
    {
        $user = User::firstOrCreate(
            ['username' => $username],
            [
                'name' => $name,
                'password' => Hash::make('password'),
            ]
        );

        // Use updateOrCreate to ensure a single, consistent manager for each division.
        DivisionInventoryManager::updateOrCreate(
            ['division_id' => $divisionId],
            ['user_id' => $user->id]
        );
    }
}
