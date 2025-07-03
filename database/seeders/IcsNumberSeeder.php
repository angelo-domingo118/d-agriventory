<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\IcsItemBatch;
use App\Models\IcsNumber;
use App\Models\ItemsCatalog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IcsNumberSeeder extends Seeder
{
    private const SPHV_THRESHOLD = 5000;

    /**
     * @var array<string, string>
     */
    private array $employeeNameMap = [
        'COSME, Jezhelie Mae' => 'CALIAS, Jezhelie Mae',
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Seeding ICS numbers...');

        $usedContractItemIds = IcsNumber::pluck('contract_item_id')->flip()->toArray();
        $data = $this->getIcsData();
        if (empty($data)) {
            return;
        }

        DB::transaction(function () use ($data, &$usedContractItemIds) {
            foreach (array_chunk($data, 50) as $chunk) {
                $this->seedChunk($chunk, $usedContractItemIds);
            }
        });

        $this->command->info('Finished seeding ICS numbers.');
    }

    private function seedChunk(array $chunk, array &$usedContractItemIds): void
    {
        $contractNumbers = collect($chunk)->map(fn ($item) => $this->parseContractNumber($item['contract_po_ib_number']))->filter()->unique()->all();
        $articles = collect($chunk)->pluck('article')->unique()->all();
        $employeeNames = collect($chunk)->pluck('issued_to')->unique();

        $mappedEmployeeNames = $employeeNames->map(fn ($name) => $this->employeeNameMap[$name] ?? $name)->all();
        $formattedNames = $this->formatEmployeeNames($mappedEmployeeNames);

        $contracts = Contract::whereIn('contract_po_ib_number', $contractNumbers)->get()->keyBy('contract_po_ib_number');
        $itemsCatalog = ItemsCatalog::with('secondaryCategory.primaryCategory')->whereIn('name', $articles)->get()->keyBy('name');
        $employees = Employee::whereIn('name', $formattedNames)->get()->keyBy('name');

        foreach ($chunk as $icsItem) {
            if (isset($icsItem['range'])) {
                $this->seedRange($icsItem, $employees, $contracts, $itemsCatalog, $usedContractItemIds);
            } else {
                $this->seedSingle($icsItem, $employees, $contracts, $itemsCatalog, $usedContractItemIds);
            }
        }
    }

    private function seedSingle($icsItem, $employees, $contracts, $itemsCatalog, &$usedContractItemIds, $icsNumber = null)
    {
        $icsNumber = $icsNumber ?? $icsItem['ics_number'];
        $this->command->info("Seeding ICS #{$icsNumber}");

        $contractNumber = $this->parseContractNumber($icsItem['contract_po_ib_number']);
        $contract = $contracts->get($contractNumber);
        if (! $contract) {
            $this->command->warn("Contract '{$contractNumber}' not found for ICS #{$icsNumber}. Skipping.");

            return;
        }

        $issuedTo = $icsItem['issued_to'];
        $correctedName = $this->employeeNameMap[$issuedTo] ?? $issuedTo;
        $employeeName = $this->formatEmployeeName($correctedName);
        $employee = $employees->get($employeeName);

        if (! $employee && $issuedTo !== 'Multiple') {
            $this->command->warn("Employee '{$issuedTo}' ('{$employeeName}') not found for ICS #{$icsNumber}. Skipping.");

            return;
        } elseif ($issuedTo === 'Multiple') {
            $employee = $employees->random();
        }

        $item = $itemsCatalog->get($icsItem['article']);
        if (! $item) {
            $this->command->warn("Item '{$icsItem['article']}' not found for ICS #{$icsNumber}. Skipping.");

            return;
        }

        $contractItem = $this->findContractItem($contract->id, $item->id, $usedContractItemIds);
        if (! $contractItem) {
            $this->command->warn("ContractItem for '{$icsItem['article']}' in contract '{$contractNumber}' not found. Skipping ICS #{$icsNumber}.");

            return;
        }

        $icsType = $contractItem->unit_price > self::SPHV_THRESHOLD ? 'SPHV' : 'SPLV';

        $date_prepared_value = $icsItem['date_prepared'];
        if (is_array($date_prepared_value)) {
            // If it's an array of dates, pick one randomly for each item in the range.
            $date_prepared_string = $date_prepared_value[array_rand($date_prepared_value)];
        } else {
            // Handle single date string, also taking the first part if it's a " & " separated string.
            $date_prepared_string = explode(' & ', $date_prepared_value)[0];
        }

        $newIcsNumber = IcsNumber::updateOrCreate(
            ['ics_number' => $icsNumber],
            [
                'assigned_employee_id' => $employee->id,
                'contract_item_id' => $contractItem->id,
                'ics_type' => $icsType,
                'quantity' => $icsItem['quantity'],
                'estimated_useful_life' => 5,
                'date_prepared' => $this->parseDate($date_prepared_string, $icsNumber),
                'date_accepted' => $this->parseDate($icsItem['date_accepted'], $icsNumber),
                'remarks' => $icsItem['remarks'],
            ]
        );

        IcsItemBatch::updateOrCreate(
            ['ics_number_id' => $newIcsNumber->id],
            []
        );
    }

    private function seedRange($rangeData, $employees, $contracts, $itemsCatalog, &$usedContractItemIds)
    {
        $start = $rangeData['range'][0];
        $end = $rangeData['range'][1];
        $this->command->info("Seeding ICS range from {$start} to {$end}");

        for ($i = $start; $i <= $end; $i++) {
            if (in_array($i, $rangeData['exclude'] ?? [])) {
                continue;
            }
            $this->seedSingle($rangeData, $employees, $contracts, $itemsCatalog, $usedContractItemIds, $i);
        }
    }

    private function findContractItem(int $contractId, int $itemCatalogId, array $usedContractItemIds)
    {
        $contractItems = ContractItem::where('contract_id', $contractId)
            ->whereHas('itemSpecification', fn ($q) => $q->where('item_catalog_id', $itemCatalogId))
            ->get();

        return $contractItems->first();
    }

    private function parseDate(string $dateString, string $icsNumber): ?Carbon
    {
        try {
            return Carbon::createFromFormat('n/j/y', trim($dateString));
        } catch (\Exception $e) {
            $this->command->warn("Could not parse date '{$dateString}' for ICS #{$icsNumber}. Setting date to null.");

            return null;
        }
    }

    private function formatEmployeeNames(array $fullNames): array
    {
        return array_map([$this, 'formatEmployeeName'], $fullNames);
    }

    private function formatEmployeeName(string $fullName): string
    {
        if ($fullName === 'Multiple') {
            return 'Multiple';
        }
        $parts = explode(', ', $fullName);
        if (count($parts) < 2) {
            return $fullName;
        }
        $lastName = array_shift($parts);
        $firstNameAndSuffix = implode(', ', $parts);

        return trim($firstNameAndSuffix).' '.trim($lastName);
    }

    private function parseContractNumber(string $contractNumber): ?string
    {
        return trim($contractNumber);
    }

    /**
     * Get the data from the PHP data file.
     */
    private function getIcsData(): array
    {
        $filePath = database_path('seeders/data/ics_data.php');
        if (! file_exists($filePath)) {
            $this->command->warn('ICS data file not found, skipping: '.$filePath);

            return [];
        }

        return require $filePath;
    }
}
