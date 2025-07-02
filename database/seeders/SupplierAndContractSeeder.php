<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierAndContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding suppliers and their contracts...');

        $data = [
            '02 Consumer Goods Trading' => ['256-34'],
            'A. Camarillo Trading' => ['256-1775'],
            'Arjelon Enterprises and Trading' => ['211-1664'],
            'Arjelon Enterprises and Trading Corporation' => ['254-1633'],
            'Arvil Joy Woodworks and General Merchandise' => ['254-2476'],
            'Arvil Joy Woodworks, Ironwork & General Merchandise' => ['254-2128'],
            'ASC Tire Supply and General Merchandise' => ['256-1914'],
            'Baguio Prince Enterprise' => ['256-559'],
            'Ban Bee Commercial Co. Inc.' => ['254-109'],
            'Bhipolito Office Supplies' => ['254-364'],
            'Bump Baby & Beyond Specialized Goods Trading' => ['254-2030', '256-2436'],
            'CA Bliss Enterprise' => ['256-2043', '256-393', '254-451'],
            'Catalyst Computer Office Equipment Wholesaling' => ['254-256', '256-2085', '256-515', '256-652', '256-569', 'BLANK-1'],
            'Cinco Vaqueros Corp.' => ['254-165', '254-397'],
            'Cokins Everywear and General Merchandise' => ['253-1899'],
            'Competitive Card Solutions Phils., Inc.' => ['254-1928'],
            'Copylandia Office Systems Corporation' => ['254-2296'],
            'Desamme Hardware & Auto Supply Incorporated' => ['256-263'],
            'ESM Learning Enterprise' => ['256-1730', '254-359'],
            'Glit Solar Trading' => ['254-669'],
            'HDG Construction & Enterprise' => ['254-2383'],
            'Hexacom Enterprises' => ['254-2297', '2024-222', '256-2325', '2025-01', '254-137', '254-285', '254-284', '254-488', '254-365'],
            'Infoworkx Inc.' => ['254-1907'],
            'Joana Tools and General Merchandise' => ['211-2052'],
            'Kait Builders and Construction' => ['2024-234'],
            'Laser Marketing' => ['254-2354', '254-2162', '256-2424'],
            'Markenjes Grains and General Merchandise' => ['256-41', '256-417'],
            'MDG Construction and Enterprise' => ['254-2387'],
            'Murasaka Enterprises' => [],
            'Nestor Aquino' => ['254-327'],
            'Noveaulab Asia Corporation' => ['254-1141', '253-840'],
            'Officetech General Merchandise' => ['256-2391', '256-513'],
            'Paper Cart Marketing' => ['256-491'],
            'Real Form Furniture Shop' => ['254-323'],
            'Reimbursement' => ['reimbursement'],
            'Segalpz Consumer Goods Trading' => ['254-2520', '254-2133', '256-382'],
            'Seige Techgrinds ICT & Business Solutions Co.' => ['254-578'],
            'The Stable Educational Supply' => ['254-1078', '253-1901'],
            'Universal Care Consumer Goods Trading' => ['254-2108'],
            'Wi-al Construction Builders' => ['256-2517', '254-352'],
            'Wilconstruct Enterprises' => ['256-298', '256-358'],
            'Zippy Trading Corporation' => ['254-266', '254-1947'],
        ];

        foreach ($data as $supplierName => $contractNumbers) {
            $supplier = Supplier::firstOrCreate(['name' => $supplierName]);

            foreach ($contractNumbers as $contractNumber) {
                Contract::firstOrCreate(
                    ['contract_po_ib_number' => $contractNumber],
                    ['supplier_id' => $supplier->id]
                );
            }
        }

        $this->command->info('Finished seeding suppliers and contracts.');
    }
}
