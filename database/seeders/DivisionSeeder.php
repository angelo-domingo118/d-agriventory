<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            ['code' => 'ORED', 'name' => 'Office of the Regional Executive Director'],
            ['code' => 'AFD', 'name' => 'Administrative and Finance Division'],
            ['code' => 'PMED', 'name' => 'Planning, Monitoring, and Evaluation Division'],
            ['code' => 'AMAD', 'name' => 'Agribusiness and Marketing Assistance Division'],
            ['code' => 'FOD', 'name' => 'Field Operations Division'],
            ['code' => 'RAED', 'name' => 'Regional Agriculture Engineering Division'],
            ['code' => 'Reg-D', 'name' => 'Regulatory Division'],
            ['code' => 'Res-D', 'name' => 'Research Division'],
            ['code' => 'ILD', 'name' => 'Integrated Agricultural Laboratories Division'],
            ['code' => 'APCO', 'name' => 'Agricultural Program Coordinating Office'],
            ['code' => 'SAAD', 'name' => 'Special Area for Agricultural Development'],
            ['code' => 'PRDP', 'name' => 'Philippine Rural Development Project'],
            ['code' => 'RAFIS', 'name' => 'Regional Agriculture and Fisheries Information Section'],
            ['code' => 'ACED', 'name' => 'Agricultural Cooperative Enterprise Development'],
        ];

        foreach ($divisions as $division) {
            // Using updateOrCreate to prevent duplicates and keep data consistent on re-seeding.
            Division::updateOrCreate(['code' => $division['code']], ['name' => $division['name']]);
        }

        $this->command->info('Seeded the divisions table with the official list.');
    }
}
