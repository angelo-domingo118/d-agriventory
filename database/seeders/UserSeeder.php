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
        // Get the test division created in DivisionSeeder
        $testDivision = Division::where('code', 'TEST-DIV')->first();

        // Create a test inventory manager with known credentials if the test division exists
        if ($testDivision) {
            $this->createInventoryManager(
                'Test Inventory Manager',
                'inventory',
                'inventory@example.com',
                $testDivision->id
            );
            $this->command->info('Test inventory manager created:');
            $this->command->info('  Username: inventory');
            $this->command->info('  Password: password');
        }

        // Create some other inventory managers with predictable credentials for testing
        $divisions = Division::where('code', '!=', 'TEST-DIV')->inRandomOrder()->take(3)->get();
        foreach ($divisions as $index => $division) {
            $userNumber = $index + 1;
            $this->createInventoryManager(
                "Inv Manager {$userNumber}",
                "manager{$userNumber}",
                "manager{$userNumber}@example.com",
                $division->id
            );
            $this->command->info("  Created manager{$userNumber} for division: {$division->name}");
        }
    }

    /**
     * Helper function to create an inventory manager.
     */
    private function createInventoryManager(string $name, string $username, string $email, int $divisionId): void
    {
        $user = User::factory()->create([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make('password'),
        ]);

        DivisionInventoryManager::create([
            'user_id' => $user->id,
            'division_id' => $divisionId,
        ]);
    }
}
