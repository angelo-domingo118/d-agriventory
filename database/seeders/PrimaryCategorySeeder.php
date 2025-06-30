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
            ['code' => 'OFF-FURN', 'name' => 'Office Furniture & Fixtures', 'description' => 'Includes items like desks, chairs, cabinets, and other furnishings for office spaces.'],
            ['code' => 'OFF-ELEC', 'name' => 'Office Equipment & Electronics', 'description' => 'Covers electronic devices used in an office environment, such as computers, printers, and projectors.'],
            ['code' => 'COMP-ACC', 'name' => 'Computer Accessories & Data Storage', 'description' => 'Items used with computers, including keyboards, mice, and external storage devices.'],
            ['code' => 'AGRI-EQUI', 'name' => 'Agricultural & Field Equipment', 'description' => 'Equipment and tools used for farming, cultivation, and other agricultural activities.'],
            ['code' => 'GEN-SUP', 'name' => 'General Supplies & Consumables', 'description' => 'Everyday items that are regularly used and replenished, such as paper, pens, and cleaning supplies.'],
            ['code' => 'LAB-EQUI', 'name' => 'Laboratory Equipment', 'description' => 'Specialized equipment and instruments for scientific research and analysis in a laboratory setting.'],
            ['code' => 'STRUCT', 'name' => 'Structures & Furnishings', 'description' => 'Large-scale structures like greenhouses, sheds, and other built furnishings.'],
            ['code' => 'PROMO-MAT', 'name' => 'Promotional & Information Materials', 'description' => 'Items used for marketing and information dissemination, like brochures, banners, and apparel.'],
            ['code' => 'OTHER', 'name' => 'Other Miscellaneous Items', 'description' => 'A catch-all category for items that do not fit into the other defined categories.'],
        ];

        foreach ($categories as $category) {
            PrimaryCategory::firstOrCreate(['code' => $category['code']], $category);
        }

        $this->command->info('Seeded the primary_categories table.');
    }
}
