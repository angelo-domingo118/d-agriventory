<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test division for development and testing
        Division::factory()->create([
            'name' => 'Test Division',
            'code' => 'TEST-DIV',
        ]);

        // Create some other divisions for variety in development
        if (! app()->environment('production')) {
            Division::factory()->count(5)->create();
        }
    }
} 