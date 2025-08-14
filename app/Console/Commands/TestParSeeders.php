<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Employee;
use App\Models\ParNumber;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TestParSeeders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:par-seeders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test and validate PAR seeders functionality';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('🧪 Testing PAR Seeders Functionality...');
        $this->newLine();

        // Test 1: Check if JSON file exists
        $this->testJsonFileExists();

        // Test 2: Validate JSON structure
        $this->testJsonStructure();

        // Test 3: Check database prerequisites
        $this->testDatabasePrerequisites();

        // Test 4: Test a small sample of the seeding logic
        $this->testSeedingLogic();

        $this->newLine();
        $this->info('✅ All tests completed!');
    }

    protected function testJsonFileExists(): void
    {
        $this->info('1. Testing JSON file existence...');

        $jsonPath = base_path('par-seeder.json');
        if (File::exists($jsonPath)) {
            $size = File::size($jsonPath);
            $this->line('   ✅ JSON file found: '.number_format($size).' bytes');
        } else {
            $this->error("   ❌ JSON file not found at: {$jsonPath}");
            $this->line('   💡 Make sure to place par-seeder.json in the project root directory');
        }
    }

    protected function testJsonStructure(): void
    {
        $this->info('2. Testing JSON structure...');

        $jsonPath = base_path('par-seeder.json');
        if (! File::exists($jsonPath)) {
            $this->line('   ⏭️  Skipping - JSON file not found');

            return;
        }

        $jsonData = json_decode(File::get($jsonPath), true);
        if (! $jsonData) {
            $this->error('   ❌ Invalid JSON data');

            return;
        }

        $this->line('   ✅ Valid JSON with '.count($jsonData).' records');

        // Test first record structure
        if (! empty($jsonData)) {
            $firstRecord = $jsonData[0];
            $requiredFields = [
                'PAR Number', 'Date Prepared', 'Quantity', 'Unit Measure',
                'Unit Cost', 'Article', 'Description', 'Year Acquired',
                'Account Code', 'Area Code', 'Building Code', 'Date Accepted',
                'Document Source', 'Issued To',
            ];

            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (! array_key_exists($field, $firstRecord)) {
                    $missingFields[] = $field;
                }
            }

            if (empty($missingFields)) {
                $this->line('   ✅ All required fields present in first record');
            } else {
                $this->error('   ❌ Missing fields: '.implode(', ', $missingFields));
            }

            // Show sample data
            $this->line('   📄 Sample record (PAR #'.$firstRecord['PAR Number'].'):');
            $this->line('      Article: '.$firstRecord['Article']);
            $this->line('      Issued To: '.$firstRecord['Issued To']);
            $this->line('      Unit Cost: '.$firstRecord['Unit Cost']);
        }
    }

    protected function testDatabasePrerequisites(): void
    {
        $this->info('3. Testing database prerequisites...');

        $tests = [
            'Employees' => Employee::count(),
            'Suppliers' => Supplier::count(),
            'Contracts' => Contract::count(),
            'Contract Items' => ContractItem::count(),
        ];

        foreach ($tests as $table => $count) {
            if ($count > 0) {
                $this->line("   ✅ {$table}: {$count} records");
            } else {
                $this->warn("   ⚠️  {$table}: No records found - seeder might create new ones");
            }
        }
    }

    protected function testSeedingLogic(): void
    {
        $this->info('4. Testing seeding logic with sample data...');

        $jsonPath = base_path('par-seeder.json');
        if (! File::exists($jsonPath)) {
            $this->line('   ⏭️  Skipping - JSON file not found');

            return;
        }

        $jsonData = json_decode(File::get($jsonPath), true);
        if (empty($jsonData)) {
            $this->line('   ⏭️  Skipping - No JSON data');

            return;
        }

        // Test with first record
        $testRecord = $jsonData[0];

        $this->line('   🧪 Testing with PAR #'.$testRecord['PAR Number']);

        // Test employee name parsing
        $employeeName = $testRecord['Issued To'];
        $parts = array_map('trim', explode(',', $employeeName));
        if (count($parts) >= 2) {
            $lastName = $parts[0];
            $firstNameAndSuffix = implode(', ', array_slice($parts, 1));
            $formattedName = trim($firstNameAndSuffix).' '.trim($lastName);
            $this->line("   ✅ Employee name parsing: '{$employeeName}' → '{$formattedName}'");
        } else {
            $this->warn("   ⚠️  Unusual employee name format: '{$employeeName}'");
        }

        // Test document source parsing
        $documentSource = $testRecord['Document Source'];
        if (preg_match('/Supplier:\s*([^;]+)/', $documentSource, $matches)) {
            $supplierName = trim($matches[1]);
            $this->line("   ✅ Supplier parsing: '{$supplierName}'");
        } else {
            $this->error("   ❌ Could not parse supplier from: '{$documentSource}'");
        }

        if (preg_match('/Contract\/PO\/IB No:\s*([^;]+)/', $documentSource, $matches)) {
            $contractNumber = trim($matches[1]);
            $this->line("   ✅ Contract parsing: '{$contractNumber}'");
        } else {
            $this->error("   ❌ Could not parse contract from: '{$documentSource}'");
        }

        // Test price parsing
        $unitCost = $testRecord['Unit Cost'];
        $parsedPrice = (float) str_replace([',', ' '], '', $unitCost);
        $this->line("   ✅ Price parsing: '{$unitCost}' → {$parsedPrice}");

        // Test description parsing for identification data
        $description = $testRecord['Description'];
        $hasSerial = preg_match('/Serial Number:\s*([^\n\r]+)/i', $description, $matches);
        if ($hasSerial) {
            $this->line('   ✅ Serial number found: '.trim($matches[1]));
        } else {
            $this->line('   ℹ️  No serial number found in description');
        }

        // Check for existing PAR record
        $existingPar = ParNumber::where('par_number', $testRecord['PAR Number'])->first();
        if ($existingPar) {
            $this->warn("   ⚠️  PAR #{$testRecord['PAR Number']} already exists in database");
            $this->line('      Consider clearing existing data before re-seeding');
        } else {
            $this->line("   ✅ PAR #{$testRecord['PAR Number']} not found - ready for seeding");
        }
    }
}
