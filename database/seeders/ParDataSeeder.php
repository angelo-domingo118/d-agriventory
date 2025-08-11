<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\ItemsCatalog;
use App\Models\ItemSpecification;
use App\Models\ParItemBatch;
use App\Models\ParNumber;
use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ParDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting PAR data seeding from par-seeder.json...');

        // First, ensure required categories exist
        $this->ensureRequiredCategoriesExist();

        // Load and validate JSON data
        $jsonPath = base_path('par-seeder.json');
        if (!File::exists($jsonPath)) {
            $this->command->error('❌ PAR seeder JSON file not found at: ' . $jsonPath);
            return;
        }

        $jsonData = json_decode(File::get($jsonPath), true);
        if (!$jsonData) {
            $this->command->error('❌ Invalid JSON data in PAR seeder file.');
            return;
        }

        $this->command->info('📊 Loaded ' . count($jsonData) . ' PAR records from JSON file.');

        // Process all records
        $this->processParData($jsonData);

        $this->command->info('✅ PAR data seeding completed successfully!');
    }

    /**
     * Ensure all required categories exist for PAR items.
     */
    protected function ensureRequiredCategoriesExist(): void
    {
        $this->command->info('📁 Ensuring required categories exist...');

        // Get or create primary categories
        $officeEquipment = PrimaryCategory::firstOrCreate(
            ['code' => 'OFF-ELEC'],
            ['name' => 'Office Equipment & Electronics']
        );

        $agriEquipment = PrimaryCategory::firstOrCreate(
            ['code' => 'AGRI-EQUI'],
            ['name' => 'Agricultural & Field Equipment']
        );

        // Ensure secondary categories exist
        $categories = [
            // Office Equipment categories
            'Computers and Laptops' => [
                'code' => 'COMPUTERS',
                'primary_category_id' => $officeEquipment->id,
                'description' => 'Desktop computers, laptops, and computer systems'
            ],
            'Office Machinery' => [
                'code' => 'OFFICE-MACH',
                'primary_category_id' => $officeEquipment->id,
                'description' => 'Printers, photocopiers, scanners, and office machines'
            ],
            'Audio-Visual Equipment' => [
                'code' => 'AV-EQUIP',
                'primary_category_id' => $officeEquipment->id,
                'description' => 'Display equipment, projectors, and AV systems'
            ],

            // Agricultural Equipment categories
            'Livestock' => [
                'code' => 'LIVESTOCK',
                'primary_category_id' => $agriEquipment->id,
                'description' => 'Farm animals and livestock'
            ],
            'Dairy Equipment' => [
                'code' => 'DAIRY-EQUIP',
                'primary_category_id' => $agriEquipment->id,
                'description' => 'Milk processing and dairy equipment'
            ],
        ];

        foreach ($categories as $name => $data) {
            SecondaryCategory::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $name,
                    'primary_category_id' => $data['primary_category_id'],
                    'description' => $data['description']
                ]
            );
        }

        $this->command->info('✅ All required categories are now available.');
    }

    /**
     * Process all PAR data from the JSON file.
     */
    protected function processParData(array $jsonData): void
    {
        $this->command->info('🔄 Processing PAR records...');

        $processedCount = 0;
        $skippedCount = 0;
        $errors = [];

        // Get all secondary categories for mapping
        $secondaryCategories = SecondaryCategory::with('primaryCategory')->get()->keyBy('name');

        // Process in chunks to avoid memory issues
        $chunks = array_chunk($jsonData, 25);

        foreach ($chunks as $chunkIndex => $chunk) {
            $this->command->info("   Processing chunk " . ($chunkIndex + 1) . " of " . count($chunks) . "...");

            DB::transaction(function () use ($chunk, $secondaryCategories, &$processedCount, &$skippedCount, &$errors) {
                foreach ($chunk as $record) {
                    try {
                        $this->processParRecord($record, $secondaryCategories);
                        $processedCount++;
                        
                        if ($processedCount % 10 == 0) {
                            $this->command->line("     ✓ Processed {$processedCount} records...");
                        }
                    } catch (\Exception $e) {
                        $skippedCount++;
                        $errors[] = "PAR #{$record['PAR Number']}: " . $e->getMessage();
                        $this->command->warn("     ⚠️  Skipped PAR #{$record['PAR Number']}: " . $e->getMessage());
                    }
                }
            });
        }

        $this->command->newLine();
        $this->command->info("📈 Processing Summary:");
        $this->command->info("   ✅ Successfully processed: {$processedCount} records");
        $this->command->info("   ⚠️  Skipped: {$skippedCount} records");

        if (!empty($errors) && count($errors) <= 10) {
            $this->command->warn("\n🔍 Error details:");
            foreach ($errors as $error) {
                $this->command->line("   • {$error}");
            }
        } elseif (count($errors) > 10) {
            $this->command->warn("\n🔍 Too many errors to display. Check logs for details.");
        }
    }

    /**
     * Process a single PAR record.
     */
    protected function processParRecord(array $record, $secondaryCategories): void
    {
        $parNumber = $record['PAR Number'];

        // Check if PAR already exists
        if (ParNumber::where('par_number', $parNumber)->exists()) {
            throw new \Exception("PAR number already exists");
        }

        // 1. Find or create employee
        $employee = $this->findOrCreateEmployee($record['Issued To']);

        // 2. Find or create supplier and contract
        $contract = $this->findOrCreateContract($record['Document Source']);

        // 3. Find or create item catalog and specification
        $itemSpecification = $this->findOrCreateItemSpecification($record, $secondaryCategories);

        // 4. Find or create contract item
        $contractItem = $this->findOrCreateContractItem($contract, $itemSpecification, $record);

        // 5. Create PAR number record
        $parNumberRecord = $this->createParNumber($record, $employee, $contractItem);

        // 6. Create PAR item batch
        $this->createParItemBatch($parNumberRecord, $record);
    }

    /**
     * Find or create employee.
     */
    protected function findOrCreateEmployee(string $issuedTo): Employee
    {
        // Parse name format "LASTNAME, Firstname" -> "Firstname LASTNAME"
        $parts = array_map('trim', explode(',', $issuedTo));
        
        if (count($parts) >= 2) {
            $lastName = $parts[0];
            $firstNameAndSuffix = implode(', ', array_slice($parts, 1));
            $formattedName = trim($firstNameAndSuffix) . ' ' . trim($lastName);
        } else {
            $formattedName = trim($issuedTo);
        }

        // Try to find existing employee
        $employee = Employee::where('name', $formattedName)->first();

        if (!$employee) {
            // Try alternative search
            if (count($parts) >= 2) {
                $employee = Employee::where('name', 'LIKE', '%' . trim($parts[0]) . '%')
                                  ->where('name', 'LIKE', '%' . trim($parts[1]) . '%')
                                  ->first();
            }
        }

        if (!$employee) {
            $employee = Employee::create([
                'name' => $formattedName,
                'division_id' => null, // Can be assigned later
                'position' => null,
            ]);
        }

        return $employee;
    }

    /**
     * Find or create contract from document source.
     */
    protected function findOrCreateContract(string $documentSource): Contract
    {
        // Parse "Supplier: Name ; Contract/PO/IB No: Number"
        if (!preg_match('/Supplier:\s*([^;]+)/', $documentSource, $supplierMatches)) {
            throw new \Exception("Could not parse supplier from document source");
        }

        if (!preg_match('/Contract\/PO\/IB No:\s*([^;]+)/', $documentSource, $contractMatches)) {
            throw new \Exception("Could not parse contract number from document source");
        }

        $supplierName = trim($supplierMatches[1]);
        $contractNumber = trim($contractMatches[1]);

        // Find or create supplier
        $supplier = Supplier::firstOrCreate(['name' => $supplierName]);

        // Find or create contract
        $contract = Contract::firstOrCreate(
            ['contract_po_ib_number' => $contractNumber],
            ['supplier_id' => $supplier->id]
        );

        return $contract;
    }

    /**
     * Find or create item specification.
     */
    protected function findOrCreateItemSpecification(array $record, $secondaryCategories): ItemSpecification
    {
        $article = $record['Article'];
        $description = $record['Description'];

        // Determine secondary category
        $secondaryCategory = $this->determineSecondaryCategory($article, $secondaryCategories);

        // Find or create item catalog
        $itemCatalog = ItemsCatalog::where('name', $article)->first();

        if (!$itemCatalog) {
            $itemCatalog = ItemsCatalog::create([
                'name' => $article,
                'unit' => strtolower($record['Unit Measure']),
                'secondary_category_id' => $secondaryCategory->id,
                'code' => $this->generateItemCode($article),
            ]);
        }

        // Parse description for brand, model, and detailed specs
        $specData = $this->parseDescription($description);

        // Find or create item specification
        $itemSpecification = ItemSpecification::where('item_catalog_id', $itemCatalog->id)
            ->where('detailed_specifications', $specData['detailed_specifications'])
            ->first();

        if (!$itemSpecification) {
            $itemSpecification = ItemSpecification::create([
                'item_catalog_id' => $itemCatalog->id,
                'brand' => $specData['brand'],
                'model' => $specData['model'],
                'detailed_specifications' => $specData['detailed_specifications'],
            ]);
        }

        return $itemSpecification;
    }

    /**
     * Determine secondary category for an article.
     */
    protected function determineSecondaryCategory(string $article, $secondaryCategories): SecondaryCategory
    {
        $mapping = [
            'DESKTOP COMPUTER' => 'Computers and Laptops',
            'LAPTOP COMPUTER' => 'Computers and Laptops',
            'PHOTOCOPIER' => 'Office Machinery',
            'PRINTER' => 'Office Machinery',
            'INFORMATION KIOSK EQUIPMENT DISPLAY' => 'Audio-Visual Equipment',
            'SHEEP' => 'Livestock',
            'MILK COOLING TANK' => 'Dairy Equipment',
        ];

        $categoryName = $mapping[$article] ?? 'Miscellaneous';
        
        $category = $secondaryCategories->get($categoryName);
        
        if (!$category) {
            // Fallback to first available category
            $category = $secondaryCategories->first();
        }

        return $category;
    }

    /**
     * Find or create contract item.
     */
    protected function findOrCreateContractItem(Contract $contract, ItemSpecification $itemSpecification, array $record): ContractItem
    {
        $unitPrice = $this->parsePrice($record['Unit Cost']);

        $contractItem = ContractItem::where('contract_id', $contract->id)
            ->where('item_specification_id', $itemSpecification->id)
            ->where('unit_price', $unitPrice)
            ->first();

        if (!$contractItem) {
            $contractItem = ContractItem::create([
                'contract_id' => $contract->id,
                'item_specification_id' => $itemSpecification->id,
                'unit_price' => $unitPrice,
                'item_type' => 'PAR',
            ]);
        }

        return $contractItem;
    }

    /**
     * Create PAR number record.
     */
    protected function createParNumber(array $record, Employee $employee, ContractItem $contractItem): ParNumber
    {
        return ParNumber::create([
            'par_number' => $record['PAR Number'],
            'assigned_employee_id' => $employee->id,
            'contract_item_id' => $contractItem->id,
            'quantity' => (int) $record['Quantity'],
            'area_code' => $record['Area Code'],
            'building_code' => $record['Building Code'],
            'account_code' => $record['Account Code'],
            'date_prepared' => $this->parseDate($record['Date Prepared']),
            'date_accepted' => $this->parseDate($record['Date Accepted']),
            'date_acquired' => $this->parseDate($record['Year Acquired'] . '-01-01'),
            'inventory_code' => $this->generateInventoryCode($record),
            'remarks' => $record['Remarks'] ?? null,
        ]);
    }

    /**
     * Create PAR item batch.
     */
    protected function createParItemBatch(ParNumber $parNumber, array $record): ParItemBatch
    {
        $identificationData = $this->extractIdentificationData($record['Description']);

        return ParItemBatch::create([
            'par_number_id' => $parNumber->id,
            'identification_data' => $identificationData,
        ]);
    }

    /**
     * Parse description for brand, model, and specs.
     */
    protected function parseDescription(string $description): array
    {
        $brand = null;
        $model = null;
        $detailedSpecs = $description;

        if (preg_match('/Brand\/Model:\s*([^\n\r]+)/i', $description, $matches)) {
            $brandModelStr = trim($matches[1]);
            $parts = explode(' ', $brandModelStr, 2);
            $brand = $parts[0] ?? null;
            $model = $parts[1] ?? null;
            $detailedSpecs = trim(str_replace($matches[0], '', $description));
        } elseif (preg_match('/Brand:\s*([^\n\r,]+)/i', $description, $matches)) {
            $brand = trim($matches[1]);
            $detailedSpecs = trim(str_replace($matches[0], '', $description));
        }

        return [
            'brand' => $brand,
            'model' => $model,
            'detailed_specifications' => $detailedSpecs ?: $description,
        ];
    }

    /**
     * Extract identification data from description.
     */
    protected function extractIdentificationData(string $description): ?string
    {
        $identificationData = [];

        // Extract serial numbers
        if (preg_match_all('/Serial Number:\s*([^\n\r]+)/i', $description, $matches)) {
            foreach ($matches[1] as $serialNumber) {
                $serialNumber = trim($serialNumber);
                if (!empty($serialNumber)) {
                    $identificationData[] = 'Serial Number: ' . $serialNumber;
                }
            }
        }

        // Extract ear tags for livestock
        if (preg_match_all('/ear tag:\s*([^\n\r]+)/i', $description, $matches)) {
            foreach ($matches[1] as $earTag) {
                $identificationData[] = 'Ear Tag: ' . trim($earTag);
            }
        }

        return empty($identificationData) ? null : implode("\n", $identificationData);
    }

    /**
     * Parse price string to float.
     */
    protected function parsePrice(string $priceString): float
    {
        return (float) str_replace([',', ' '], '', $priceString);
    }

    /**
     * Parse date string to Carbon instance.
     */
    protected function parseDate(string $dateString): Carbon
    {
        try {
            $formats = ['m/d/y', 'm/d/Y', 'Y-m-d', 'd/m/Y'];
            
            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, trim($dateString));
                } catch (\Exception $e) {
                    continue;
                }
            }

            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            return Carbon::now();
        }
    }

    /**
     * Generate inventory code.
     */
    protected function generateInventoryCode(array $record): string
    {
        $accountCode = $record['Account Code'];
        $article = strtoupper($record['Article']);
        $articleAbbrev = substr(preg_replace('/[^A-Z]/', '', $article), 0, 3);
        
        return "PAR-{$accountCode}-{$articleAbbrev}-" . uniqid();
    }

    /**
     * Generate item code.
     */
    protected function generateItemCode(string $itemName): string
    {
        $baseCode = substr(strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $itemName))), 0, 35);
        return $baseCode . '-' . uniqid();
    }
}
