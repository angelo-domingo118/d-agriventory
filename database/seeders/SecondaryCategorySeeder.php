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
                ['code' => 'CHAIRS', 'name' => 'Chairs and Seating', 'description' => 'All types of office chairs, including ergonomic, executive, and guest chairs.'],
                ['code' => 'TABLES', 'name' => 'Tables and Desks', 'description' => 'Includes office desks, conference tables, and other work surfaces.'],
                ['code' => 'STORAGE', 'name' => 'Storage and Cabinets', 'description' => 'Filing cabinets, shelves, and other storage solutions.'],
                ['code' => 'PRESENTATION', 'name' => 'Presentation and Display', 'description' => 'Whiteboards, bulletin boards, and other presentation equipment.'],
                ['code' => 'DISPLAY-RACKS', 'name' => 'Display Racks', 'description' => 'Racks for displaying promotional materials or products.'],
            ],
            'OFF-ELEC' => [
                ['code' => 'COMPUTERS', 'name' => 'Computers and Laptops', 'description' => 'Desktop computers, laptops, and workstations.'],
                ['code' => 'PERIPHERALS', 'name' => 'Computer Peripherals', 'description' => 'Monitors, keyboards, mice, printers, and scanners.'],
                ['code' => 'OFFICE-MACH', 'name' => 'Office Machinery', 'description' => 'Shredders, laminators, and other office machines.'],
                ['code' => 'APPLIANCES', 'name' => 'General Office Appliances', 'description' => 'Water dispensers, coffee makers, and other small appliances.'],
                ['code' => 'POWER', 'name' => 'Power and Electrical', 'description' => 'UPS, extension cords, and power strips.'],
                ['code' => 'AV-EQUIP', 'name' => 'Audio-Visual Equipment', 'description' => 'Projectors, screens, and sound systems.'],
            ],
            'COMP-ACC' => [
                ['code' => 'DATA-STORAGE', 'name' => 'External Data Storage', 'description' => 'External hard drives, USB flash drives, and memory cards.'],
                ['code' => 'CONNECTIVITY', 'name' => 'Connectivity and Hubs', 'description' => 'USB hubs, docking stations, and network cables.'],
                ['code' => 'SOFTWARE', 'name' => 'Software', 'description' => 'Operating systems, productivity software, and specialized applications.'],
            ],
            'AGRI-EQUI' => [
                ['code' => 'HAND-TOOLS', 'name' => 'Hand Tools', 'description' => 'Shovels, rakes, hoes, and other manual agricultural tools.'],
                ['code' => 'MACHINERY', 'name' => 'Field Machinery', 'description' => 'Tractors, plows, harvesters, and other heavy equipment.'],
                ['code' => 'MEASUREMENT', 'name' => 'Measurement Tools', 'description' => 'Soil testers, moisture meters, and other measuring devices.'],
                ['code' => 'SAFETY-GEAR', 'name' => 'Safety Gear', 'description' => 'Gloves, boots, masks, and other personal protective equipment.'],
                ['code' => 'CARTS', 'name' => 'Carts and Trolleys', 'description' => 'Wheelbarrows, utility carts, and trolleys for transport.'],
                ['code' => 'FERTILIZERS-SOIL', 'name' => 'Fertilizers & Soil Amendments', 'description' => 'Chemical and organic fertilizers, compost, and other soil enhancers.'],
                ['code' => 'SEEDS-PLANTS', 'name' => 'Seeds & Planting Materials', 'description' => 'Seeds, seedlings, and other materials for planting.'],
                ['code' => 'FARM-SUPPLIES', 'name' => 'Farm Supplies', 'description' => 'Fencing, irrigation supplies, and other general farm materials.'],
                ['code' => 'ANIMAL-FEEDS', 'name' => 'Animal Feeds', 'description' => 'Feed for livestock and poultry.'],
                ['code' => 'LIVESTOCK', 'name' => 'Livestock', 'description' => 'Live animals for farming and production.'],
                ['code' => 'VEHICLES', 'name' => 'Vehicles', 'description' => 'Farm trucks, ATVs, and other utility vehicles.'],
            ],
            'GEN-SUP' => [
                ['code' => 'CONTAINERS', 'name' => 'Storage Containers', 'description' => 'Plastic bins, crates, and other containers for storage.'],
                ['code' => 'OFFICE-SUP', 'name' => 'Office Supplies', 'description' => 'Pens, paper, staples, and other general office supplies.'],
                ['code' => 'BINS', 'name' => 'Waste Bins', 'description' => 'Trash cans and recycling bins.'],
                ['code' => 'MAINTENANCE', 'name' => 'Maintenance Supplies', 'description' => 'Cleaning products, light bulbs, and other maintenance items.'],
                ['code' => 'KITCHEN-SUPPLIES', 'name' => 'Kitchen Supplies', 'description' => 'Utensils, disposable plates, and other kitchen essentials.'],
            ],
            'LAB-EQUI' => [
                ['code' => 'GEN-LAB', 'name' => 'General Laboratory Equipment', 'description' => 'Beakers, microscopes, and other general lab equipment.'],
                ['code' => 'FREEZERS', 'name' => 'Refrigerators and Freezers', 'description' => 'Lab-grade refrigerators and freezers for sample storage.'],
            ],
            'STRUCT' => [
                ['code' => 'OUTDOOR-STRUCT', 'name' => 'Outdoor Structures', 'description' => 'Greenhouses, sheds, and other outdoor buildings.'],
            ],
            'PROMO-MAT' => [
                ['code' => 'PUBLICATIONS', 'name' => 'Publications', 'description' => 'Brochures, flyers, and informational booklets.'],
                ['code' => 'APPAREL', 'name' => 'Apparel & Wearables', 'description' => 'T-shirts, caps, and other clothing with promotional branding.'],
                ['code' => 'BAGS-KITS', 'name' => 'Bags & Kits', 'description' => 'Promotional bags, tote bags, and kits.'],
                ['code' => 'GIVEAWAYS', 'name' => 'Giveaways & Merchandise', 'description' => 'Pens, keychains, and other promotional merchandise.'],
                ['code' => 'SIGNAGE', 'name' => 'Signage', 'description' => 'Banners, posters, and other display signs.'],
            ],
            'OTHER' => [
                ['code' => 'MISC-OTHER', 'name' => 'Miscellaneous', 'description' => 'Any other items that do not fit into the predefined categories.'],
            ],
        ];

        $primaryCategoriesMap = PrimaryCategory::all()->keyBy('code');

        foreach ($categories as $primaryCategoryCode => $secondaryCategories) {
            $primaryCategory = $primaryCategoriesMap->get($primaryCategoryCode);

            if ($primaryCategory) {
                foreach ($secondaryCategories as $secondaryCategory) {
                    SecondaryCategory::firstOrCreate(
                        ['code' => $secondaryCategory['code']],
                        array_merge($secondaryCategory, ['primary_category_id' => $primaryCategory->id])
                    );
                }
            } else {
                $this->command->error("Primary category with code '{$primaryCategoryCode}' not found. Skipping its secondary categories.");
            }
        }
        $this->command->info('Seeded the secondary_categories table.');
    }
}
