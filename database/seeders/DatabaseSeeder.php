<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            UserSeeder::class,
            DivisionSeeder::class,
            EmployeeSeeder::class,
            PrimaryCategorySeeder::class,
            SecondaryCategorySeeder::class,
            ItemsCatalogSeeder::class,
            ItemSpecificationSeeder::class,
            SupplierAndContractSeeder::class,
            ContractItemsSeeder::class,
            IcsNumberSeeder::class,
            IcsItemBatchSeeder::class,
            ParDataSeeder::class,
            IdrDataSeeder::class,
            DesktopComputerSeeder::class,
            DivisionInventoryManagerSeeder::class,
            ConsumableSeeder::class,
            AuditLogSeeder::class,
        ]);
    }
}
