<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding audit logs...');

        $users = User::all();
        $tables = [
            'users', 'employees', 'divisions', 'items_catalog', 'item_specifications',
            'suppliers', 'contracts', 'contract_items', 'ics_number', 'par_number',
            'idr_number', 'consumable_records', 'consumable_items',
        ];
        $actions = ['CREATE', 'UPDATE', 'DELETE'];

        // Generate sample audit logs
        for ($i = 0; $i < 200; $i++) {
            $user = $users->random();
            $table = $tables[array_rand($tables)];
            $action = $actions[array_rand($actions)];
            $recordId = rand(1, 100);

            $oldValues = null;
            $newValues = null;
            $description = null;

            // Generate realistic data based on action type
            switch ($action) {
                case 'CREATE':
                    $newValues = $this->generateSampleData($table, 'new');
                    $description = "Created new {$this->getModelName($table)} record";
                    break;
                case 'UPDATE':
                    $oldValues = $this->generateSampleData($table, 'old');
                    $newValues = $this->generateSampleData($table, 'new');
                    $description = "Updated {$this->getModelName($table)} record";
                    break;
                case 'DELETE':
                    $oldValues = $this->generateSampleData($table, 'old');
                    $description = "Deleted {$this->getModelName($table)} record";
                    break;
            }

            AuditLog::create([
                'user_id' => $user->id,
                'table_name' => $table,
                'record_id' => $recordId,
                'action_type' => $action,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'description' => $description,
                'created_at' => Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
            ]);
        }

        // Create some system logs (no user)
        for ($i = 0; $i < 20; $i++) {
            $table = $tables[array_rand($tables)];
            $action = $actions[array_rand($actions)];
            $recordId = rand(1, 100);

            AuditLog::create([
                'user_id' => null,
                'table_name' => $table,
                'record_id' => $recordId,
                'action_type' => $action,
                'old_values' => null,
                'new_values' => $this->generateSampleData($table, 'new'),
                'description' => "System automated {$action} operation on {$this->getModelName($table)}",
                'created_at' => Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
            ]);
        }

        $this->command->info('Finished seeding audit logs.');
    }

    private function generateSampleData(string $table, string $type): array
    {
        $baseData = match ($table) {
            'users' => [
                'name' => $type === 'old' ? 'John Doe' : 'John Updated',
                'username' => $type === 'old' ? 'jdoe' : 'john_doe',
            ],
            'employees' => [
                'name' => $type === 'old' ? 'Jane Smith' : 'Jane Updated',
                'division_id' => $type === 'old' ? 1 : 2,
            ],
            'items_catalog' => [
                'name' => $type === 'old' ? 'Desktop Computer' : 'Desktop Computer - Updated',
                'unit' => 'piece',
                'code' => $type === 'old' ? 'DC001' : 'DC001-UPD',
            ],
            'ics_number' => [
                'ics_number' => $type === 'old' ? 'ICS-2024-001' : 'ICS-2024-001-REV',
                'quantity' => $type === 'old' ? 1 : 2,
                'ics_type' => 'SPLV',
            ],
            'contracts' => [
                'contract_po_ib_number' => $type === 'old' ? 'PO-2024-001' : 'PO-2024-001-REV',
                'supplier_id' => 1,
            ],
            default => [
                'id' => rand(1, 100),
                'updated_field' => $type === 'old' ? 'old_value' : 'new_value',
                'status' => $type === 'old' ? 'inactive' : 'active',
            ]
        };

        return $baseData;
    }

    private function getModelName(string $table): string
    {
        return match ($table) {
            'users' => 'User',
            'employees' => 'Employee',
            'divisions' => 'Division',
            'items_catalog' => 'Item Catalog',
            'item_specifications' => 'Item Specification',
            'suppliers' => 'Supplier',
            'contracts' => 'Contract',
            'contract_items' => 'Contract Item',
            'ics_number' => 'ICS Number',
            'par_number' => 'PAR Number',
            'idr_number' => 'IDR Number',
            'consumable_records' => 'Consumable Record',
            'consumable_items' => 'Consumable Item',
            default => 'Record'
        };
    }
}
