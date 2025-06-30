<?php

namespace Database\Seeders;

use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use Illuminate\Database\Seeder;

class SecondaryCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'OFF-FURN' => [
                ['code' => 'CHAIRS', 'name' => 'Chairs and Seating'],
                ['code' => 'TABLES', 'name' => 'Tables and Desks'],
                ['code' => 'STORAGE', 'name' => 'Storage and Cabinets'],
                ['code' => 'PRESENTATION', 'name' => 'Presentation and Display'],
                ['code' => 'DISPLAY-RACKS', 'name' => 'Display Racks'],
            ],
            'OFF-ELEC' => [
                ['code' => 'COMPUTERS', 'name' => 'Computers and Laptops'],
                ['code' => 'PERIPHERALS', 'name' => 'Computer Peripherals'],
                ['code' => 'OFFICE-MACH', 'name' => 'Office Machinery'],
                ['code' => 'APPLIANCES', 'name' => 'General Office Appliances'],
                ['code' => 'POWER', 'name' => 'Power and Electrical'],
                ['code' => 'AV-EQUIP', 'name' => 'Audio-Visual Equipment'],
            ],
            'COMP-ACC' => [
                ['code' => 'DATA-STORAGE', 'name' => 'External Data Storage'],
                ['code' => 'CONNECTIVITY', 'name' => 'Connectivity and Hubs'],
                ['code' => 'SOFTWARE', 'name' => 'Software'],
            ],
            'AGRI-EQUI' => [
                ['code' => 'HAND-TOOLS', 'name' => 'Hand Tools'],
                ['code' => 'MACHINERY', 'name' => 'Field Machinery'],
                ['code' => 'MEASUREMENT', 'name' => 'Measurement Tools'],
                ['code' => 'SAFETY-GEAR', 'name' => 'Safety Gear'],
                ['code' => 'CARTS', 'name' => 'Carts and Trolleys'],
                ['code' => 'FERTILIZERS-SOIL', 'name' => 'Fertilizers & Soil Amendments'],
                ['code' => 'SEEDS-PLANTS', 'name' => 'Seeds & Planting Materials'],
                ['code' => 'FARM-SUPPLIES', 'name' => 'Farm Supplies'],
                ['code' => 'ANIMAL-FEEDS', 'name' => 'Animal Feeds'],
                ['code' => 'LIVESTOCK', 'name' => 'Livestock'],
                ['code' => 'VEHICLES', 'name' => 'Vehicles'],
            ],
            'GEN-SUP' => [
                ['code' => 'CONTAINERS', 'name' => 'Storage Containers'],
                ['code' => 'OFFICE-SUP', 'name' => 'Office Supplies'],
                ['code' => 'BINS', 'name' => 'Waste Bins'],
                ['code' => 'MAINTENANCE', 'name' => 'Maintenance Supplies'],
                ['code' => 'KITCHEN-SUPPLIES', 'name' => 'Kitchen Supplies'],
            ],
            'LAB-EQUI' => [
                ['code' => 'GEN-LAB', 'name' => 'General Laboratory Equipment'],
                ['code' => 'FREEZERS', 'name' => 'Refrigerators and Freezers'],
            ],
            'STRUCT' => [
                ['code' => 'OUTDOOR-STRUCT', 'name' => 'Outdoor Structures'],
            ],
            'PROMO-MAT' => [
                ['code' => 'PUBLICATIONS', 'name' => 'Publications'],
                ['code' => 'APPAREL', 'name' => 'Apparel & Wearables'],
                ['code' => 'BAGS-KITS', 'name' => 'Bags & Kits'],
                ['code' => 'GIVEAWAYS', 'name' => 'Giveaways & Merchandise'],
                ['code' => 'SIGNAGE', 'name' => 'Signage'],
            ],
            'OTHER' => [
                ['code' => 'MISC-OTHER', 'name' => 'Miscellaneous'],
            ],
        ];

        $primaryCategoriesMap = PrimaryCategory::all()->keyBy('code');

        foreach ($categories as $primaryCategoryCode => $secondaryCategories) {
            $primaryCategory = $primaryCategoriesMap->get($primaryCategoryCode);

            if ($primaryCategory) {
                foreach ($secondaryCategories as $secondaryCategory) {
                    SecondaryCategory::firstOrCreate(
                        ['code' => $secondaryCategory['code']],
                        [
                            'primary_category_id' => $primaryCategory->id,
                            'name' => $secondaryCategory['name'],
                        ]
                    );
                }
            } else {
                $this->command->error("Primary category with code '{$primaryCategoryCode}' not found. Skipping its secondary categories.");
            }
        }
        $this->command->info('Seeded the secondary_categories table.');
    }
}
