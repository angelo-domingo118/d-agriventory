<?php

namespace Database\Seeders;

use App\Models\ConsumableRecord;
use App\Models\ConsumableItem;
use App\Models\Division;
use App\Models\ItemSpecification;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ConsumableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding consumable records...');

        $divisions = Division::all();
        if ($divisions->isEmpty()) {
            $this->command->warn('No divisions found. Please run DivisionSeeder first.');
            return;
        }

        $itemSpecifications = ItemSpecification::with('itemCatalog')->get();
        if ($itemSpecifications->isEmpty()) {
            $this->command->warn('No item specifications found. Creating some basic ones...');
            $this->createBasicItemSpecifications();
            $itemSpecifications = ItemSpecification::with('itemCatalog')->get();
        }

        // Create consumable records for each division
        foreach ($divisions as $division) {
            $this->createConsumableRecordsForDivision($division, $itemSpecifications);
        }

        $this->command->info('Finished seeding consumable records.');
    }

    private function createConsumableRecordsForDivision(Division $division, $itemSpecifications)
    {
        $recordsToCreate = rand(3, 8); // 3-8 records per division

        for ($i = 1; $i <= $recordsToCreate; $i++) {
            $recordNumber = 'CR-' . $division->code . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            
            $record = ConsumableRecord::create([
                'record_number' => $recordNumber,
                'division_id' => $division->id,
                'date_received' => Carbon::now()->subDays(rand(1, 365)),
                'remarks' => $this->getRandomRemarks(),
            ]);

            // Add 1-5 different items to each record
            $itemsToAdd = rand(1, 5);
            $usedSpecifications = [];

            for ($j = 0; $j < $itemsToAdd; $j++) {
                $spec = $itemSpecifications->random();
                
                // Avoid duplicating specifications in the same record
                if (in_array($spec->id, $usedSpecifications)) {
                    continue;
                }
                $usedSpecifications[] = $spec->id;

                $initialQuantity = $this->getQuantityForItem($spec->itemCatalog->name);
                $currentQuantity = $this->getCurrentQuantity($initialQuantity);

                ConsumableItem::create([
                    'consumable_record_id' => $record->id,
                    'item_specification_id' => $spec->id,
                    'initial_quantity' => $initialQuantity,
                    'current_quantity' => $currentQuantity,
                ]);
            }
        }

        $this->command->info("Created $recordsToCreate consumable records for division: {$division->name}");
    }

    private function createBasicItemSpecifications()
    {
        $basicConsumables = [
            ['name' => 'Copy Paper A4', 'unit' => 'ream', 'brand' => 'Paper One'],
            ['name' => 'Ballpoint Pen', 'unit' => 'piece', 'brand' => 'Pilot'],
            ['name' => 'Stapler Wire', 'unit' => 'box', 'brand' => 'MAX'],
            ['name' => 'Correction Tape', 'unit' => 'piece', 'brand' => 'Correction'],
            ['name' => 'Manila Folder', 'unit' => 'piece', 'brand' => 'Generic'],
            ['name' => 'Rubber Band', 'unit' => 'pack', 'brand' => 'Generic'],
            ['name' => 'Paper Clip', 'unit' => 'box', 'brand' => 'Generic'],
            ['name' => 'Marker Pen', 'unit' => 'piece', 'brand' => 'Sharpie'],
            ['name' => 'Envelope Long', 'unit' => 'pack', 'brand' => 'Generic'],
            ['name' => 'Post-it Notes', 'unit' => 'pack', 'brand' => '3M'],
        ];

        foreach ($basicConsumables as $item) {
            // Create item in catalog if doesn't exist
            $catalog = \App\Models\ItemsCatalog::firstOrCreate([
                'name' => $item['name'],
            ], [
                'unit' => $item['unit'],
                'secondary_category_id' => 1, // Assuming first category exists
                'code' => strtoupper(substr(str_replace(' ', '', $item['name']), 0, 6)) . rand(100, 999),
            ]);

            // Create specification
            \App\Models\ItemSpecification::firstOrCreate([
                'item_catalog_id' => $catalog->id,
                'brand' => $item['brand'],
            ], [
                'model' => null,
                'detailed_specifications' => 'Standard office consumable item',
            ]);
        }
    }

    private function getQuantityForItem(string $itemName): int
    {
        // Different quantities based on item type
        if (str_contains(strtolower($itemName), 'paper') || str_contains(strtolower($itemName), 'ream')) {
            return rand(20, 100); // Reams of paper
        } elseif (str_contains(strtolower($itemName), 'pen') || str_contains(strtolower($itemName), 'marker')) {
            return rand(50, 200); // Pens/markers
        } elseif (str_contains(strtolower($itemName), 'folder') || str_contains(strtolower($itemName), 'envelope')) {
            return rand(100, 500); // Folders/envelopes
        } elseif (str_contains(strtolower($itemName), 'pack') || str_contains(strtolower($itemName), 'box')) {
            return rand(10, 50); // Boxed/packed items
        } else {
            return rand(25, 100); // General items
        }
    }

    private function getCurrentQuantity(int $initialQuantity): int
    {
        // Simulate usage - current quantity is 40%-100% of initial
        $minPercentage = 40;
        $maxPercentage = 100;
        $percentage = rand($minPercentage, $maxPercentage) / 100;
        
        $currentQuantity = (int) ($initialQuantity * $percentage);
        
        // Sometimes make items completely out of stock (5% chance)
        if (rand(1, 100) <= 5) {
            $currentQuantity = 0;
        }
        
        return max(0, $currentQuantity);
    }

    private function getRandomRemarks(): ?string
    {
        $remarks = [
            'Standard office supplies procurement',
            'Emergency stock replenishment',
            'Quarterly supply order',
            'Additional supplies for special project',
            'Monthly consumables restock',
            'Bulk order for cost savings',
            'High-priority items requested',
            null, // Some records have no remarks
            null,
        ];

        return $remarks[array_rand($remarks)];
    }
}
