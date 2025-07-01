<?php

namespace Database\Seeders;

use App\Models\ItemsCatalog;
use App\Models\SecondaryCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ItemsCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = config('seeders.items_catalog');

        // Fetch all secondary categories at once and key by name to prevent N+1 queries.
        $secondaryCategoriesMap = SecondaryCategory::all()->keyBy('name');

        foreach ($items as $secondaryCategoryName => $itemsList) {
            $secondaryCategory = $secondaryCategoriesMap->get($secondaryCategoryName);

            if ($secondaryCategory) {
                foreach ($itemsList as $itemName) {
                    ItemsCatalog::firstOrCreate(
                        ['name' => $itemName],
                        [
                            'secondary_category_id' => $secondaryCategory->id,
                            'unit' => $this->determineUnit($itemName),
                            'code' => $this->generateItemCode($itemName),
                        ]
                    );
                }
            } else {
                // Report an error if a secondary category is not found to avoid silent failures.
                $this->command->error("Secondary category '{$secondaryCategoryName}' not found. Skipping items.");
            }
        }
        $this->command->info('Seeded the items_catalog table.');
    }

    /**
     * Determine the appropriate unit for an item based on its name.
     */
    private function determineUnit(string $itemName): string
    {
        $upperItemName = Str::upper($itemName);

        $unitMap = [
            'kg' => ['FERTILIZER', 'UREA', 'PHOSPHATE', 'POTASH', 'RICE BRAN', 'POWDER', 'CRUMBLE', 'PELLET', 'SEEDS', 'BEAN', 'PEANUT', 'CORN', 'KILOGRAM'],
            'liter' => ['PAINT', 'LIQUID', 'MOLASSES', 'CONCENTRATE', 'EM-1'],
            'gallon' => ['GALLON'],
            'meter' => ['HOSE', 'ROPE', 'WIRE', 'SHEET', 'NET'],
            'pack' => ['PACK'],
            'set' => ['SET', 'KIT', 'WITH', 'WORKSTATION'],
            'unit' => ['UNIT', 'COMPUTER', 'PRINTER', 'PHOTOCOPIER', 'TANK', 'PULVERIZER', 'MACHINE', 'EQUIPMENT', 'DISPLAY', 'TERMINAL', 'STERILIZER', 'FREEZER', 'DISPENSER', 'LIGHT', 'TELEVISION', 'UPS', 'COOLER', 'SHREDDER', 'METER', 'SCALE', 'BATH'],
            'bag' => ['BAG', 'SACK'],
            'book' => ['BOOK', 'JOURNAL', 'BULLETIN', 'NEWS LETTER'],
            'bottle' => ['BOTTLE'],
            'box' => ['BOX', 'BIN'],
            'can' => ['CAN', 'CANISTER'],
            'container' => ['CONTAINER', 'DRUM'],
            'copy' => ['COPY', 'MANUAL'],
            'roll' => ['ROLL'],
            'head' => ['SHEEP'],
            'lot' => ['KIOSK', 'LOT'],
        ];

        foreach ($unitMap as $unit => $keywords) {
            // Use a regex with word boundaries for precise, whole-word matching.
            $pattern = '/\b(' . implode('|', array_map('preg_quote', $keywords)) . ')\b/';
            if (preg_match($pattern, $upperItemName)) {
                return $unit;
            }
        }

        return 'piece';
    }

    /**
     * Generate a unique and standardized code for an item.
     */
    private function generateItemCode(string $itemName): string
    {
        // Sanitize, uppercase, and truncate the item name to create a base code.
        $baseCode = substr(strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $itemName))), 0, 35);

        // Append a unique ID to ensure the code is always unique.
        return $baseCode.'-'.uniqid();
    }
}
