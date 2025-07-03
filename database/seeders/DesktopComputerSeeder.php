<?php

namespace Database\Seeders;

use App\Models\IcsItemBatch;
use App\Models\IcsNumber;
use App\Models\ItemComponent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Throwable;

class DesktopComputerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Desktop Computers...');

        $data = $this->getDesktopComputersData();

        if (! is_array($data)) {
            $this->command->error('Failed to load desktop computers data or data is not in expected array format.');

            return;
        }

        $icsNumbers = IcsNumber::all()->keyBy('ics_number');

        DB::transaction(function () use ($data, $icsNumbers) {
            foreach ($data as $item) {
                $icsNumberModel = $icsNumbers->get($item['ics_number']);

                if (! $icsNumberModel) {
                    $this->command->warn("ICS number '{$item['ics_number']}' not found. Skipping batch item for article '{$item['article']}'.");
                    continue;
                }

                $batch = IcsItemBatch::create([
                    'ics_number_id' => $icsNumberModel->id,
                    'identification_data' => null,
                ]);

                $this->seedDesktopComponents($batch, $item['description']);
            }
        });

        $this->command->info('Finished seeding Desktop Computers.');
    }

    private function seedDesktopComponents(IcsItemBatch $batch, string $description): void
    {
        $components = $this->parseDesktopDescription($description);

        foreach ($components as $component) {
            try {
                ItemComponent::create(array_merge(['ics_item_batch_id' => $batch->id], $component));
            } catch (Throwable $e) {
                $this->command->error("Failed to create item component for batch ID {$batch->id}: ".$e->getMessage());
            }
        }
    }

    private function parseDesktopDescription(string $description): array
    {
        $lines = preg_split("/\\r\\n|\\n|\\r/", $description);

        // Separate main component lines from sub-component lines
        $mainComponentLines = [];
        $subComponentLines = [];
        $foundSubComponent = false;

        foreach ($lines as $line) {
            if (preg_match('/^(MONITOR|UPS|CASING|KEYBOARD & MOUSE)/i', trim($line))) {
                $foundSubComponent = true;
            }
            if ($foundSubComponent) {
                $subComponentLines[] = $line;
            } else {
                $mainComponentLines[] = $line;
            }
        }

        $components = [];

        // Parse the main system unit
        $mainComponent = $this->parseMainComponent($mainComponentLines);
        if ($mainComponent) {
            $components[] = $mainComponent;
        }

        // Parse all sub-components
        $subComponents = $this->parseSubComponents($subComponentLines);
        
        return array_merge($components, $subComponents);
    }

    private function parseMainComponent(array $lines): ?array
    {
        $mainDesc = implode("\n", $lines);
        $mainComponent = ['component_type' => 'System Unit'];
        
        $details = $this->parseBrandModelAndSerial($mainDesc);
        $mainComponent = array_merge($mainComponent, $details);

        // Only return a component if it has a serial or brand
        if (isset($mainComponent['serial_number']) || isset($mainComponent['brand'])) {
            return $mainComponent;
        }

        return null;
    }

    private function parseSubComponents(array $lines): array
    {
        $components = [];
        $currentComponentData = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                if ($currentComponentData) {
                    $components[] = $currentComponentData;
                    $currentComponentData = null;
                }
                continue;
            }

            if (preg_match('/^(MONITOR|UPS|CASING|KEYBOARD & MOUSE)/i', $line, $matches)) {
                if ($currentComponentData) {
                    $components[] = $currentComponentData;
                }
                $type = ucwords(strtolower(trim($matches[1])));
                $currentComponentData = ['component_type' => $type];
                $line = trim(substr($line, strlen($matches[1])));
                if (str_starts_with($line, ':')) {
                    $line = trim(substr($line, 1));
                }
            }
            
            if (! $currentComponentData) {
                continue;
            }
            
            $details = $this->parseBrandModelAndSerial($line);
            $currentComponentData = array_merge($currentComponentData, $details);
        }

        if ($currentComponentData) {
            $components[] = $currentComponentData;
        }
        
        return $components;
    }

    private function parseBrandModelAndSerial(string $text): array
    {
        $details = [];

        if (preg_match('/(?:Brand\\/Model|Brand):\\s*(.+)/i', $text, $matches)) {
            $brandModel = trim($matches[1]);
            $parts = explode(' ', $brandModel, 2);
            $brand = ! empty($parts[0]) && strlen($parts[0]) < 255 ? $parts[0] : null;
            $model = ! empty($parts[1]) && strlen($parts[1]) < 255 ? $parts[1] : null;

            if ($brand) $details['brand'] = $brand;
            if ($model) $details['model'] = $model;

        } elseif (preg_match('/(Serial Number|Casing Number):\\s*(.+)/i', $text, $matches)) {
            $serial = trim($matches[2]);
            if (! empty($serial) && strlen($serial) < 255) {
                $details['serial_number'] = $serial;
            }
        }
        
        return $details;
    }

    private function getDesktopComputersData(): array
    {
        return include database_path('seeders/data/desktop_computers_data.php');
    }
}
