<?php

namespace Database\Seeders;

use App\Models\IcsItemBatch;
use App\Models\IcsNumber;
use App\Models\ItemComponent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DesktopComputerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding desktop computer item batches and components...');

        $data = $this->getDesktopComputersData();
        $icsNumbers = IcsNumber::whereHas('contractItem.itemSpecification.itemCatalog', function ($query) {
            $query->where('name', 'like', '%DESKTOP COMPUTER%');
        })->get()->keyBy('ics_number');

        DB::transaction(function () use ($data, $icsNumbers) {
            foreach ($data as $item) {
                $icsNumberModel = $icsNumbers->get($item['ics_number']);

                if (! $icsNumberModel) {
                    $this->command->warn("ICS number '{$item['ics_number']}' not found. Skipping desktop computer '{$item['article']}'.");

                    continue;
                }

                // Update the item specification with main computer details if provided
                if (isset($item['brand']) || isset($item['model'])) {
                    $contractItem = $icsNumberModel->contractItem;
                    if ($contractItem && $contractItem->itemSpecification) {
                        $spec = $contractItem->itemSpecification;
                        $spec->update([
                            'brand' => $item['brand'],
                            'model' => $item['model'],
                            'detailed_specifications' => null, // Clear the old description text
                        ]);
                    }
                }

                // Create ICS item batch with main unit serial number if provided
                $identificationData = isset($item['serial_number']) ? $item['serial_number'] : null;

                $icsItemBatch = IcsItemBatch::updateOrCreate(
                    [
                        'ics_number_id' => $icsNumberModel->id,
                        'identification_data' => $identificationData,
                    ],
                    []
                );

                // Create components
                if (isset($item['components']) && is_array($item['components'])) {
                    // First, delete existing components for this batch to avoid duplicates
                    ItemComponent::where('ics_item_batch_id', $icsItemBatch->id)->delete();

                    foreach ($item['components'] as $component) {
                        if (empty($component['component_type'])) {
                            $this->command->warn("Skipping component for ICS #{$item['ics_number']} due to missing 'component_type'.");

                            continue;
                        }
                        ItemComponent::create([
                            'ics_item_batch_id' => $icsItemBatch->id,
                            'component_type' => $component['component_type'],
                            'brand' => $component['brand'] ?? null,
                            'model' => $component['model'] ?? null,
                            'serial_number' => $component['serial_number'] ?? null,
                        ]);
                    }
                }

                $componentCount = count($item['components'] ?? []);
                $this->command->info("Processed desktop computer ICS #{$item['ics_number']} with {$componentCount} components.");
            }
        });

        $this->command->info('Finished seeding desktop computer item batches and components.');
    }

    private function getDesktopComputersData(): array
    {
        return include database_path('seeders/data/desktop_computers_data.php');
    }
}
