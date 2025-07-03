<?php

namespace Database\Seeders;

use App\Models\IcsItemBatch;
use App\Models\IcsNumber;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IcsItemBatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding ICS item batches (excluding desktops)...');

        $data = $this->getIcsItemBatchesData();
        $icsNumbers = IcsNumber::all()->keyBy('ics_number');

        DB::transaction(function () use ($data, $icsNumbers) {
            foreach ($data as $item) {
                if (str_contains(strtoupper($item['Article']), 'DESKTOP COMPUTER')) {
                    continue;
                }
                
                $icsNumberModel = $icsNumbers->get($item['ICS Number']);

                if (! $icsNumberModel) {
                    $this->command->warn("ICS number '{$item['ICS Number']}' not found. Skipping batch item for article '{$item['Article']}'.");
                    continue;
                }

                $quantity = $item['Quantity'] ?? 1;
                $identificationData = $this->parseIdentificationData($item['Description']);

                // If a unique identifier (like a serial number) is found, the quantity should always be 1.
                if ($identificationData !== null) {
                    $quantity = 1;
                }

                for ($i = 0; $i < $quantity; $i++) {
                    IcsItemBatch::create([
                        'ics_number_id' => $icsNumberModel->id,
                        'identification_data' => $identificationData,
                    ]);
                }
            }
        });

        $this->command->info('Finished seeding non-desktop ICS item batches.');
    }

    private function parseIdentificationData(string $description): ?string
    {
        if (preg_match('/(?:Serial Number:|Casing Number:)\\s*(.*)/i', $description, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * @return array
     */
    private function getIcsItemBatchesData(): array
    {
        return include database_path('seeders/data/ics_item_batches_data.php');
    }
} 