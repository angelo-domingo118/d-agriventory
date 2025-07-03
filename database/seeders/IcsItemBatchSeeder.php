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
        $this->command->info('Seeding ICS item batches...');

        $data = $this->getIcsItemBatchesData();
        $icsNumbers = IcsNumber::all()->keyBy('ics_number');

        DB::transaction(function () use ($data, $icsNumbers) {
            foreach ($data as $item) {
                $icsNumberModel = $icsNumbers->get($item['ICS Number']);

                if (! $icsNumberModel) {
                    $this->command->warn("ICS number '{$item['ICS Number']}' not found. Skipping batch item for article '{$item['Article']}'.");
                    continue;
                }

                IcsItemBatch::create([
                    'ics_number_id' => $icsNumberModel->id,
                    'identification_data' => $this->parseIdentificationData($item['Description']),
                ]);
            }
        });

        $this->command->info('Finished seeding ICS item batches.');
    }

    /**
     * Extracts identifying information from the description string.
     *
     * @param  string  $description
     * @return string|null
     */
    private function parseIdentificationData(string $description): ?string
    {
        $identifying_data = [];
        $lines = explode("\n", $description);
        $current_context = '';

        foreach ($lines as $line) {
            $trimmed_line = trim($line);

            if (in_array(strtoupper($trimmed_line), ['MONITOR', 'UPS'])) {
                $current_context = ucfirst(strtolower($trimmed_line));
                continue;
            }

            if (preg_match('/(Serial Number|Casing Number):\s*(.*)/i', $line, $matches)) {
                $identifier_type = trim($matches[1]);
                $identifier_value = trim($matches[2]);

                if (! empty($identifier_value)) {
                    $full_identifier = $current_context ? "{$current_context} {$identifier_type}" : $identifier_type;
                    $identifying_data[] = "{$full_identifier}: {$identifier_value}";
                }
            }
        }

        if (empty($identifying_data)) {
            return null;
        }

        return implode("\n", $identifying_data);
    }

    /**
     * @return array
     */
    private function getIcsItemBatchesData(): array
    {
        return include database_path('seeders/data/ics_item_batches_data.php');
    }
} 