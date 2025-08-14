<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\IdrItemBatch;
use App\Models\IdrNumber;
use App\Models\ItemsCatalog;
use App\Models\ItemSpecification;
use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class IdrDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting IDR data seeding from idr-seeder.json...');

        // First, ensure required categories exist
        $this->ensureRequiredCategoriesExist();

        // Load and validate JSON data
        $jsonPath = base_path('idr-seeder.json');
        if (! File::exists($jsonPath)) {
            $this->command->error('❌ IDR seeder JSON file not found at: '.$jsonPath);

            return;
        }

        $jsonData = json_decode(File::get($jsonPath), true);
        if (! $jsonData) {
            $this->command->error('❌ Invalid JSON data in IDR seeder file.');

            return;
        }

        $this->command->info('📊 Loaded '.count($jsonData).' IDR records from JSON file.');

        // Process all records
        $this->processIdrData($jsonData);

        $this->command->info('✅ IDR data seeding completed successfully!');
    }

    /**
     * Ensure all required categories exist for IDR items.
     */
    protected function ensureRequiredCategoriesExist(): void
    {
        $this->command->info('📁 Ensuring required categories exist for IDR items...');

        // Get or create primary categories
        $agriEquipment = PrimaryCategory::firstOrCreate(
            ['code' => 'AGRI-EQUI'],
            ['name' => 'Agricultural & Field Equipment']
        );

        $genSupplies = PrimaryCategory::firstOrCreate(
            ['code' => 'GEN-SUP'],
            ['name' => 'General Supplies & Consumables']
        );

        $officeEquipment = PrimaryCategory::firstOrCreate(
            ['code' => 'OFF-ELEC'],
            ['name' => 'Office Equipment & Electronics']
        );

        $promoMat = PrimaryCategory::firstOrCreate(
            ['code' => 'PROMO-MAT'],
            ['name' => 'Promotional & Information Materials']
        );

        // Ensure secondary categories exist for IDR items
        $categories = [
            // Agricultural categories
            'Seeds & Planting Materials' => [
                'code' => 'SEEDS-PLANTS',
                'primary_category_id' => $agriEquipment->id,
                'description' => 'Seeds, seedlings, and planting materials',
            ],
            'Fertilizers & Soil Amendments' => [
                'code' => 'FERTILIZERS-SOIL',
                'primary_category_id' => $agriEquipment->id,
                'description' => 'Chemical and organic fertilizers, soil amendments',
            ],
            'Animal Feeds' => [
                'code' => 'ANIMAL-FEEDS',
                'primary_category_id' => $agriEquipment->id,
                'description' => 'Animal feed and nutrition products',
            ],
            'Farm Supplies' => [
                'code' => 'FARM-SUPPLIES',
                'primary_category_id' => $agriEquipment->id,
                'description' => 'Pesticides, insecticides, and farm chemicals',
            ],
            'Hand Tools' => [
                'code' => 'HAND-TOOLS',
                'primary_category_id' => $agriEquipment->id,
                'description' => 'Manual farming and gardening tools',
            ],
            'Field Machinery' => [
                'code' => 'MACHINERY',
                'primary_category_id' => $agriEquipment->id,
                'description' => 'Agricultural machinery and equipment',
            ],
            'Measurement Tools' => [
                'code' => 'MEASUREMENT',
                'primary_category_id' => $agriEquipment->id,
                'description' => 'Weighing scales, meters, and measuring tools',
            ],

            // Storage and containers
            'Storage Containers' => [
                'code' => 'CONTAINERS',
                'primary_category_id' => $genSupplies->id,
                'description' => 'Bags, crates, drums, and storage containers',
            ],
            'Kitchen Supplies' => [
                'code' => 'KITCHEN-SUPPLIES',
                'primary_category_id' => $genSupplies->id,
                'description' => 'Kitchen equipment and food service items',
            ],
            'Office Supplies' => [
                'code' => 'OFFICE-SUP',
                'primary_category_id' => $genSupplies->id,
                'description' => 'Stationery, forms, and office consumables',
            ],

            // Electronics and tools
            'Computer Peripherals' => [
                'code' => 'PERIPHERALS',
                'primary_category_id' => $officeEquipment->id,
                'description' => 'USB drives, calculators, and computer accessories',
            ],
            'Power and Electrical' => [
                'code' => 'POWER',
                'primary_category_id' => $officeEquipment->id,
                'description' => 'Power banks, electrical equipment',
            ],

            // Promotional materials
            'Apparel & Wearables' => [
                'code' => 'APPAREL',
                'primary_category_id' => $promoMat->id,
                'description' => 'Shirts, uniforms, and wearable items',
            ],
            'Publications' => [
                'code' => 'PUBLICATIONS',
                'primary_category_id' => $promoMat->id,
                'description' => 'Manuals, newsletters, and educational materials',
            ],
            'Giveaways & Merchandise' => [
                'code' => 'GIVEAWAYS',
                'primary_category_id' => $promoMat->id,
                'description' => 'Promotional items and branded merchandise',
            ],
            'Signage' => [
                'code' => 'SIGNAGE',
                'primary_category_id' => $promoMat->id,
                'description' => 'Signs, banners, and display materials',
            ],
        ];

        foreach ($categories as $name => $data) {
            SecondaryCategory::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $name,
                    'primary_category_id' => $data['primary_category_id'],
                    'description' => $data['description'],
                ]
            );
        }

        $this->command->info('✅ All required categories for IDR items are now available.');
    }

    /**
     * Process all IDR data from the JSON file.
     */
    protected function processIdrData(array $jsonData): void
    {
        $this->command->info('🔄 Processing IDR records...');

        $processedCount = 0;
        $skippedCount = 0;
        $errors = [];

        // Get all secondary categories for mapping
        $secondaryCategories = SecondaryCategory::with('primaryCategory')->get()->keyBy('name');

        // Process in chunks to avoid memory issues
        $chunks = array_chunk($jsonData, 25);

        foreach ($chunks as $chunkIndex => $chunk) {
            $this->command->info('   Processing chunk '.($chunkIndex + 1).' of '.count($chunks).'...');

            DB::transaction(function () use ($chunk, $secondaryCategories, &$processedCount, &$skippedCount, &$errors) {
                foreach ($chunk as $record) {
                    try {
                        $this->processIdrRecord($record, $secondaryCategories);
                        $processedCount++;

                        if ($processedCount % 10 == 0) {
                            $this->command->line("     ✓ Processed {$processedCount} records...");
                        }
                    } catch (\Exception $e) {
                        $skippedCount++;
                        $errors[] = "IDR #{$record['Series Number']}: ".$e->getMessage();
                        $this->command->warn("     ⚠️  Skipped IDR #{$record['Series Number']}: ".$e->getMessage());
                    }
                }
            });
        }

        $this->command->newLine();
        $this->command->info('📈 Processing Summary:');
        $this->command->info("   ✅ Successfully processed: {$processedCount} records");
        $this->command->info("   ⚠️  Skipped: {$skippedCount} records");

        if (! empty($errors) && count($errors) <= 10) {
            $this->command->warn("\n🔍 Error details:");
            foreach ($errors as $error) {
                $this->command->line("   • {$error}");
            }
        } elseif (count($errors) > 10) {
            $this->command->warn("\n🔍 Too many errors to display. Check logs for details.");
        }
    }

    /**
     * Process a single IDR record.
     */
    protected function processIdrRecord(array $record, $secondaryCategories): void
    {
        $idrNumber = $record['Series Number'];

        // Check if IDR already exists
        if (IdrNumber::where('number', $idrNumber)->exists()) {
            throw new \Exception('IDR number already exists');
        }

        // 1. Find or create employees
        $assignedEmployee = $this->findOrCreateEmployee($record['Issued To']);
        $approvingEmployee = $this->findOrCreateEmployee($record['Division Chief']);

        // 2. Find or create supplier and contract
        $contract = $this->findOrCreateContract($record['Document Source']);

        // 3. Find or create item catalog and specification
        $itemSpecification = $this->findOrCreateItemSpecification($record, $secondaryCategories);

        // 4. Find or create contract item
        $contractItem = $this->findOrCreateContractItem($contract, $itemSpecification, $record);

        // 5. Create IDR number record
        $idrNumberRecord = $this->createIdrNumber($record, $assignedEmployee, $approvingEmployee, $contractItem);

        // 6. Create IDR item batch
        $this->createIdrItemBatch($idrNumberRecord, $record);
    }

    /**
     * Find or create employee.
     */
    protected function findOrCreateEmployee(string $employeeName): Employee
    {
        // Parse name format "LASTNAME, Firstname" -> "Firstname LASTNAME"
        $parts = array_map('trim', explode(',', $employeeName));

        if (count($parts) >= 2) {
            $lastName = $parts[0];
            $firstNameAndSuffix = implode(', ', array_slice($parts, 1));
            $formattedName = trim($firstNameAndSuffix).' '.trim($lastName);
        } else {
            $formattedName = trim($employeeName);
        }

        // Try to find existing employee
        $employee = Employee::where('name', $formattedName)->first();

        if (! $employee) {
            // Try alternative search
            if (count($parts) >= 2) {
                $employee = Employee::where('name', 'LIKE', '%'.trim($parts[0]).'%')
                    ->where('name', 'LIKE', '%'.trim($parts[1]).'%')
                    ->first();
            }
        }

        if (! $employee) {
            $employee = Employee::create([
                'name' => $formattedName,
                'division_id' => null, // Can be assigned later
                'position' => $parts[0] === $employeeName ? null : ($record['Issued to Position'] ?? null),
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
        if (! preg_match('/Supplier:\s*([^;]+)/', $documentSource, $supplierMatches)) {
            throw new \Exception('Could not parse supplier from document source');
        }

        if (! preg_match('/Contract\/PO\/IB No:\s*([^;]+)/', $documentSource, $contractMatches)) {
            throw new \Exception('Could not parse contract number from document source');
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
        $secondaryCategory = $this->determineSecondaryCategory($article, $description, $secondaryCategories);

        // Find or create item catalog
        $itemCatalog = ItemsCatalog::where('name', $article)->first();

        if (! $itemCatalog) {
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

        if (! $itemSpecification) {
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
    protected function determineSecondaryCategory(string $article, string $description, $secondaryCategories): SecondaryCategory
    {
        $fullText = strtoupper($article.' '.$description);

        // Define category mapping with keywords
        $categoryMap = [
            // Seeds and plants
            'Seeds & Planting Materials' => ['BEAN', 'BELLPEPPER', 'BROCCOLI', 'CABBAGE', 'CARROT', 'CAULIFLOWER', 'EGGPLANT', 'FRENCH BEAN', 'GARLIC CLOVES', 'HOT PEPPER', 'HYBRID CORN SEEDS', 'HYBRID RICE SEED', 'LETTUCE', 'LIMA BEANS', 'OKRA', 'PACKCHOI', 'PEPPER', 'POLE SITAO', 'SILING LABUYO', 'SQUASH', 'STRAWBERRY', 'TOMATO', 'VALUE PACK', 'SEEDLING'],

            // Fertilizers
            'Fertilizers & Soil Amendments' => ['AMMONIUM PHOSPHATE', 'COMPLETE FERTILIZER', 'CONTROLLED RELEASE FERTILIZER', 'CONTROLLER RELEASE FERTILIZER', 'FERTILIZER', 'FOLIAR BIO-FERTILIZER', 'FOLIAR FERTILIZER', 'FLOWER INDUCER', 'MURIATE OF POTASH', 'ORGANIC FERTILIZER', 'ORGANIC LIQUID FERTILIZER', 'UREA', 'EM-1 CONCENTRATE', 'PEAT MOSS'],

            // Animal feeds
            'Animal Feeds' => ['CHICK BOOSTER CRUMBLE', 'CHICK BREEDER PELLET', 'CHICK FINISHER CRUMBLE', 'CHICK GROWER CRUMBLE', 'CHICK STARTER CRUMBLE', 'HOG GROWER PELLET', 'HOG STARTER PELLET', 'LAYER FEEDS', 'RICE BRAN', 'MOLASSES'],

            // Farm supplies (chemicals, spraying)
            'Farm Supplies' => ['FUNGICIDE', 'INSECTICIDE', 'ORGANIC INSECTICIDE', 'KNAPSACK SPRAYER', 'POWER SPRAYER', 'INSECT NET', 'BLACK NET', 'GARDEN SHADE NET', 'NET'],

            // Hand tools
            'Hand Tools' => ['FLORAL SCISSOR', 'FOLDING PRUNNING SAW', 'GRAB HOE', 'GRASS CUTTER', 'PRUNING SHEAR', 'PRUNNING SAW', 'PRUNNING SHEAR', 'RAKE', 'SHOVEL', 'SICKLE', 'TOOL SET'],

            // Machinery
            'Field Machinery' => ['COFFEE PULPER', 'FORAGE CHOPPER', 'MULTIPURPOSE THRESHER', 'PEANUT GRINDER', 'PUMP & ENGINE SET', 'PUMP and ENGINE SET', 'SOLAR GENERATOR', 'HAULING VEHICLE', 'VEGETABLE CHILLER'],

            // Measurement tools
            'Measurement Tools' => ['DIGITAL WEIGHING SCALE', 'ELECTONIC WEIGHING SCALE', 'WEIGHING SCALE', 'HAND HELD TALLY COUNTER', 'MEASURING CUP', 'MOISTURE METER'],

            // Storage containers
            'Storage Containers' => ['BAG', 'HERMETIC BAG', 'HERMETIC COCOON', 'MULTI-PURPOSE CRATES', 'PLASTIC CRATES', 'PLASTIC FRUIT CRATE', 'PLASTIC JAR', 'STEEL DRUM', 'WATER DRUM', 'WATER TANK', 'VEGETABLE CRATES', 'REPEAR HARVESTER SACK', 'CHILLER DISPLAY TRAY', 'STAINLESS DISPLAY RACK'],

            // Kitchen supplies
            'Kitchen Supplies' => ['CHEST COOLER', 'LPG TANK', 'STOVE', 'INSULATED TUMBLER', 'INSULATED VACUUM TUMBLER', 'PORTABLE VACUUM FLASK'],

            // Office supplies
            'Office Supplies' => ['CALCULATOR', 'CASH BOOK', 'CASH DISBURSEMENT JOURNAL', 'CASH RECEIPT JOURNAL', 'GENERAL JOURNAL', 'GENERAL LEDGER', 'NOTEPAD', 'PURCHASE JOURNAL', 'SALES JOURNAL', 'VOUCHER', 'WALL CALENDAR', 'PLANNER'],

            // Computer peripherals
            'Computer Peripherals' => ['DUAL DRIVE GO USB', 'FLASH DRIVE', 'OTG DUAL DRIVE GO', 'PHONE STAND', 'SOFTWARE', 'TECH ORGANIZER POUCH', 'USB', 'USB DUAL DRIVE'],

            // Power and electrical
            'Power and Electrical' => ['ELECTRIC HEAT GUN', 'POWERBANK'],

            // Apparel
            'Apparel & Wearables' => ['ADVOCACY LONG SLEEVE SHIRT', 'APRON', 'BACKPACK', 'COVER ALL', 'COVERALL', 'POLO SHIRT', 'RAINBOOTS', 'T-SHIRT', 'TOTE BAG', 'YOGA MAT'],

            // Publications
            'Publications' => ['3RD QUARTER 2024 NEWS BULLETIN', 'EXPLANATORY MANUAL', 'EXPLANATORY MANUAL FOR PNS/BAFS 337:2022', 'SAAD NEWS LETTER', 'TRAINING KIT'],

            // Giveaways
            'Giveaways & Merchandise' => ['CUSTOMIZED MUGS', 'CUSTOMIZED UMBRELLA', 'ID LACE LANYARD', 'RETIREMENT PLAQUE'],

            // Signage
            'Signage' => ['PERMANENT KADIWA OUTLET SIGNAGE'],
        ];

        foreach ($categoryMap as $categoryName => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($fullText, $keyword)) {
                    $category = $secondaryCategories->get($categoryName);
                    if ($category) {
                        return $category;
                    }
                }
            }
        }

        // Default to Miscellaneous if no match found
        $misc = $secondaryCategories->get('Miscellaneous');

        return $misc ?: $secondaryCategories->first();
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

        if (! $contractItem) {
            $contractItem = ContractItem::create([
                'contract_id' => $contract->id,
                'item_specification_id' => $itemSpecification->id,
                'unit_price' => $unitPrice,
                'item_type' => 'IDR',
            ]);
        }

        return $contractItem;
    }

    /**
     * Create IDR number record.
     */
    protected function createIdrNumber(array $record, Employee $assignedEmployee, Employee $approvingEmployee, ContractItem $contractItem): IdrNumber
    {
        return IdrNumber::create([
            'number' => (int) $record['Series Number'],
            'assigned_employee_id' => $assignedEmployee->id,
            'approving_employee_id' => $approvingEmployee->id,
            'contract_item_id' => $contractItem->id,
            'quantity' => (int) floatval($record['Quantity']), // Handle decimal quantities
            'inventory_code' => $record['Inventory Code'],
            'ors' => $this->parseOrsNumber($record['ORS Number']),
            'date_prepared' => $this->parseDate($record['Date prepared']),
            'date_accepted' => $this->parseDate($record['Date Accepted']),
            'date' => $this->parseDate($record['Date prepared']), // Use date_prepared as default
            'received_by_id' => $assignedEmployee->id, // Same as assigned employee
            'received_from_id' => $approvingEmployee->id, // Use approving employee as the issuer
            'remarks' => ! empty($record['Remarks']) ? $record['Remarks'] : null,
        ]);
    }

    /**
     * Create IDR item batch.
     */
    protected function createIdrItemBatch(IdrNumber $idrNumber, array $record): IdrItemBatch
    {
        $identificationData = $this->extractIdentificationData($record);

        return IdrItemBatch::create([
            'idr_number_id' => $idrNumber->id,
            'identification_data' => $identificationData,
        ]);
    }

    /**
     * Parse ORS number from string.
     */
    protected function parseOrsNumber(string $orsString): ?string
    {
        if (preg_match('/ORS Number:\s*([^\s]+)/', $orsString, $matches)) {
            return trim($matches[1]);
        }

        return $orsString;
    }

    /**
     * Extract identification data from record.
     */
    protected function extractIdentificationData(array $record): ?string
    {
        $identificationData = [];

        // Add series number and item number
        $identificationData[] = 'Series Number: '.$record['Series Number'];

        if (! empty($record['Item No']) && $record['Item No'] !== '0') {
            $identificationData[] = 'Item Number: '.$record['Item No'];
        }

        // Add location code
        if (! empty($record['Location Code'])) {
            $identificationData[] = 'Location Code: '.$record['Location Code'];
        }

        // Add balance information
        if (! empty($record['Balance per Card'])) {
            $identificationData[] = 'Balance per Card: '.$record['Balance per Card'];
        }

        // Add position information
        if (! empty($record['Issued to Position'])) {
            $identificationData[] = 'Issued to Position: '.$record['Issued to Position'];
        }

        if (! empty($record['Division Position'])) {
            $identificationData[] = 'Division Position: '.$record['Division Position'];
        }

        return empty($identificationData) ? null : implode("\n", $identificationData);
    }

    /**
     * Parse description for brand, model, and specs.
     */
    protected function parseDescription(string $description): array
    {
        $brand = null;
        $model = null;
        $detailedSpecs = $description;

        if (preg_match('/Brand:\s*([^\n\r]+)/i', $description, $matches)) {
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
     * Generate item code.
     */
    protected function generateItemCode(string $itemName): string
    {
        $baseCode = substr(strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $itemName))), 0, 35);

        return $baseCode.'-'.uniqid();
    }
}
