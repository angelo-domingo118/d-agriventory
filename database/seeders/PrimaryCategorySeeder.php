<?php

namespace Database\Seeders;

use App\Models\PrimaryCategory;
use Illuminate\Database\Seeder;

class PrimaryCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['code' => 'OFF-FURN', 'name' => 'Office Furniture & Fixtures'],
            ['code' => 'OFF-ELEC', 'name' => 'Office Equipment & Electronics'],
            ['code' => 'COMP-ACC', 'name' => 'Computer Accessories & Data Storage'],
            ['code' => 'AGRI-EQUI', 'name' => 'Agricultural & Field Equipment'],
            ['code' => 'GEN-SUP', 'name' => 'General Supplies & Consumables'],
            ['code' => 'LAB-EQUI', 'name' => 'Laboratory Equipment'],
            ['code' => 'STRUCT', 'name' => 'Structures & Furnishings'],
            ['code' => 'PROMO-MAT', 'name' => 'Promotional & Information Materials'],
            ['code' => 'OTHER', 'name' => 'Other Miscellaneous Items'],
        ];

        foreach ($categories as $category) {
            PrimaryCategory::firstOrCreate(['code' => $category['code']], $category);
        }

        $this->command->info('Seeded the primary_categories table.');
    }
}
