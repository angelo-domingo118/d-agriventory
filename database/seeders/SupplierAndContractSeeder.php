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
            '02 Consumer Goods Trading' => ['256-34', '254-36', '256-571'],
            'A. Camarillo Trading' => ['256-1775'],
            'ASC Tire Supply and General Merchandise' => ['256-1914'],
            'Abra Vegetable Growers MPC (AVSECO)' => ['256-75', '256-77'],
            'Abra Vegetable Seed Growers MPC (AVSECO)' => ['254-2320', '254-2326'],
            'Ace Visual Solutions' => ['254-1943'],
            'Agribunch Products OPC' => ['2025-04'],
            'Ahcil Laboratories, Inc.' => ['2025-75'],
            'Aldyxzie Enterprise' => ['254-245'],
            'Allied Botanical Corporation' => ['2024-245', '2025-03', '2025-04', '2025-75', '254-2369'],
            'Arjelon Enterprises and Trading' => ['211-1664', '254-1633'],
            'Arvil Joy Woodworks & General Merchandise' => ['254-2128', '254-2476'],
            'Asian Hybrid Seed Technologies, Inc.' => ['2025-22'],
            'Aves Farm Supply and General Merchandise' => ['2025-21'],
            'Baguio Acrylic Fabrication Services' => ['254-2160'],
            'Baguio Prince Enterprise' => ['256-559'],
            'Ban Bee Commercial Co. Inc.' => ['254-109', '254-2187'],
            'Benguet State University' => ['256-895'],
            'Bhipolito Office Supplies' => ['254-364'],
            'Bump Baby & Beyond Specialized Goods & Trading' => ['254-2030', '256-2436', '254-211', '254-239', '254-1888', '256-223'],
            'CA Bliss Enterprises' => ['254-451', '256-393', '256-2043', '256-336'],
            'CBC Farm Machineries' => ['2024-240'],
            'Catalyst Computer Office Equipment Wholesaling' => ['254-256', '256-515', '256-569', '256-652', '256-2085'],
            'Ceboom Enterprises' => ['254-319', '254-1192'],
            'Cinco Vaqueros Corp.' => ['254-165', '254-397'],
            'Cokins Everywear and General Merchandise' => ['253-1899'],
            'Competitive Card Solutions Phils., Inc.' => ['254-1928'],
            'Copylandia Office Systems Corporation' => ['254-2296'],
            'Desamme Hardware & Auto Supply Incorporated' => ['256-263'],
            'Diamon Kim Enterprises' => ['2025-30'],
            'ESM Learning Enterprises' => ['254-359', '256-1730', '2024-171A', '2025-30', '254-282', '254-287', '254-296'],
            'Enviro Scope Synergy Inc.' => ['2025-06', '2025-27', '231-2233', '254-456'],
            'Equity Machineries, Inc.' => ['2024-217'],
            'Exherald Publishing Incorporated' => ['254-70'],
            'Gammad Tailoring' => ['256-2199'],
            'Gilcor Printing Press' => ['254-203', '254-1942', '256-657'],
            'Girochino Plant Nursery' => ['253-1599'],
            'Glit Solar Products Trading' => ['254-2164'],
            'Glit Solar Trading' => ['254-669'],
            'Gold Ink Printing Shop' => ['254-65', '256-516'],
            'HDG Construction & Enterprise' => ['254-2383'],
            'HDR Plastic Manufacturing Corporation' => ['2024-239'],
            'Heaven\'s Valley and General Merchandise' => ['254-2307'],
            'Hexacom Enterprises' => ['2024-222', '2025-01', '254-137', '254-284', '254-285', '254-365', '254-488', '254-2297', '256-382', '256-2325', '256-2243'],
            'Infoworkx Inc.' => ['254-1907'],
            'JTIM Enterprises' => ['2024-17'],
            'Jedeco Trading Corporation' => ['2025-20'],
            'Jerine Printing Services' => ['256-2385'],
            'Joana Tools and General Merchandise' => ['211-2052'],
            'Kadasan Office and School Supplies Trading' => ['256-573'],
            'Kait Builders and Construction' => ['2024-234'],
            'Katrans Marketing' => ['254-29'],
            'Laser Marketing' => ['254-2162', '254-2354', '256-2424'],
            'Leads Agricultural Products Corporation' => ['2025-20', '2025-21'],
            'Lord Elgyn Merchandising' => ['2024-1581', '2025-28', '254-252', '254-258', '254-2308', '256-2270'],
            'MDG Construction and Enterprise' => ['254-2387'],
            'Marabe Enterprises' => ['254-2360'],
            'Markenjes Grains and General Merchandise' => ['256-41', '256-417', '254-240', '254-309', '254-2036', '256-293'],
            'Murasaka Enterprises' => ['254-113', '254-2306', '254-2377'],
            'Nestor Aquino' => ['254-327'],
            'Nis and Kat General Merchandise' => ['254-267', '254-726', '256-565'],
            'Northern Asia Sales Corporation' => ['256-605'],
            'Noveaulab Asia Corporation' => ['253-840', '254-1141'],
            'Officetech General Merchandise' => ['256-513', '256-2391', '254-172'],
            'Paper Cart Marketing' => ['256-491'],
            'Pro Agri Seed Corporation' => ['2024-215'],
            'Ramgo International Corporation' => ['2025-03', '2025-04', '2025-60', '254-225', '254-243', '254-272', '254-292', '254-2333'],
            'Real Form Furniture Shop' => ['254-323'],
            'Roninjames Plumbing Supplies Trading' => ['256-1853'],
            'SL Agritech Corporation' => ['2024-215'],
            'Sabarre General Merchandise' => ['2024-900', '254-406', '254-483', '254-630', '254-2497'],
            'Segalpz Consumer Goods Trading' => ['254-2133', '254-2520'],
            'Seige Techgrinds ICT & Business Solutions Co.' => ['254-578'],
            'Sentinel Plastic Manufacturing Corporation' => ['2024-171A', '2025-05', '2025-30', '254-44'],
            'Snooks Commercial Trading' => ['254-2110'],
            'St. Joseph Agrimarketing Corporation' => ['2024-247', '2025-20', '2025-21', '2025-29', '256-504'],
            'St. Ruiz Agro Farm Supply & General Merchandise' => ['2024-173A', '256-224'],
            'Superior Multi-Purpose Packaging, Inc.' => ['2024-244', '231-2077', '231-2528'],
            'The Stable Educational Supply' => ['253-1901', '254-1078', '254-2204', '256-302'],
            'Threesixteen Specialized Goods Trading' => ['254-2168'],
            'Ugy General Merchandise' => ['254-762', '254-803', '254-804', '254-809', '254-2309', '256-44', '256-725', '256-799'],
            'United Farmcore Corporation' => ['2025-03', '2025-04', '2025-43', '2025-44'],
            'Universal Care Consumer Goods Trading' => ['254-2108'],
            'Universal Commercial Corp.' => ['2025-33'],
            'Velocity Motors Sales Corp.' => ['RO 2024-140'],
            'Wi-al Construction Builders' => ['254-352', '256-2517'],
            'Wilconstruct Enterprise' => ['254-2035'],
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