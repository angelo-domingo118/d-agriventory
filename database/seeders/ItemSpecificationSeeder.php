<?php

namespace Database\Seeders;

use App\Models\ItemsCatalog;
use App\Models\ItemSpecification;
use Illuminate\Database\Seeder;

class ItemSpecificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding item specifications...');

        // Fetch all items catalog at once to avoid N+1 queries in the loop.
        $itemsCatalog = ItemsCatalog::all()->keyBy('name');

        $filePath = database_path('seeders/data/item_specifications.php');
        if (! file_exists($filePath)) {
            $this->command->error('Item specifications data file not found at: '.$filePath);

            return;
        }
        $specificationsData = require $filePath;

        foreach ($specificationsData as $itemName => $specs) {
            if ($itemsCatalog->has($itemName)) {
                $item = $itemsCatalog->get($itemName);
                foreach ($specs as $spec) {
                    ItemSpecification::firstOrCreate(
                        [
                            'item_catalog_id' => $item->id,
                            'brand' => $spec['brand'],
                            'model' => $spec['model'],
                            'detailed_specifications' => $spec['detailed_specifications'],
                        ]
                    );
                }
            } else {
                $this->command->warn("Item '{$itemName}' not found in items_catalog. Skipping.");
            }
        }

        $this->command->info('Finished seeding item specifications.');
    }
}
