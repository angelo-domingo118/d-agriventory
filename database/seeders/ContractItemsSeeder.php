<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\ItemsCatalog;
use App\Models\ItemSpecification;
use App\Models\SecondaryCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContractItemsSeeder extends Seeder
{
    /**
     * The data for seeding contract items.
     *
     * @var array
     */
    protected $data = [
        '211-1664' => [
            ['unit' => 'unit', 'unit_price' => 1090.00, 'article' => 'USB DOCK', 'description' => '8-in-1 USB C hub type C'],
            ['unit' => 'unit', 'unit_price' => 820.00, 'article' => 'USB HUB', 'description' => 'Brand: Orico USB hub, 4 port USB, 3.0'],
        ],
        '254-1633' => [
            ['unit' => 'unit', 'unit_price' => 7190.00, 'article' => 'OFFICE CHAIR', 'description' => 'Brand/Model: Ofix G11 gaming chair, cradle flexi ergonomic office chair'],
        ],
        '256-1730' => [
            ['unit' => 'unit', 'unit_price' => 12450.00, 'article' => 'PRINTER', 'description' => 'Brand/Model: Epson L3210'],
        ],
        '254-1928' => [
            ['unit' => 'unit', 'unit_price' => 20000.00, 'article' => 'TIME ATTENDANCE 1', 'description' => 'Brand/Model: Anviz C2 Pro'],
            ['unit' => 'unit', 'unit_price' => 20000.00, 'article' => 'TIME ATTENDANCE TERMINAL', 'description' => 'Brand/Model: Anviz C2 Pro'],
        ],
        '254-2030' => [
            ['unit' => 'piece', 'unit_price' => 1000.00, 'article' => 'GUN TACKER', 'description' => 'Brand: Inco for wood, insulation, light wiring and upholstery'],
        ],
        '254-2520' => [
            ['unit' => 'unit', 'unit_price' => 3500.00, 'article' => 'TOWER FAN', 'description' => 'Brand/Model: Kanazawa TS-03Y'],
            ['unit' => 'unit', 'unit_price' => 1000.00, 'article' => 'KEYBOARD with MOUSE', 'description' => 'Brand: Philips keyboard and mouse'],
            ['unit' => 'unit', 'unit_price' => 14000.00, 'article' => 'WATER DISPENSER', 'description' => 'Brand/Model: Everest E7WD601BL, bottom load'],
            ['unit' => 'unit', 'unit_price' => 1400.00, 'article' => 'ELECTRONIC INSECT KILLER', 'description' => 'Brand/Model: We Home Mosquito Killer Lamp, fly zapper attracting mosquito with UV light'],
        ],
        '254-109' => [
            ['unit' => 'piece', 'unit_price' => 14280.00, 'article' => 'CHEST COOLER', 'description' => 'Brand: Coleman CL, 65 quartz, wheeled'],
        ],
        '256-41' => [
            ['unit' => 'unit', 'unit_price' => 25000.00, 'article' => 'UPRIGHT FREEZER', 'description' => 'Brand/Model: Fujidenzo IUF-70S'],
        ],
        '254-1141' => [
            ['unit' => 'unit', 'unit_price' => 34800.00, 'article' => 'DIGITAL THERMOMETER', 'description' => 'Brand/Model: Extech EasyView 15 Thermometer Datalogger'],
            ['unit' => 'unit', 'unit_price' => 24000.00, 'article' => 'HOT PLATE', 'description' => 'Brand/Model: Dlab MS7-H550-S'],
        ],
        '253-840' => [
            ['unit' => 'unit', 'unit_price' => 11000.00, 'article' => 'WATER BATH', 'description' => 'Brand/Model: Biobase SY-1L1H'],
        ],
        '256-1914' => [
            ['unit' => 'piece', 'unit_price' => 3500.00, 'article' => 'HELMET', 'description' => ''],
        ],
        '256-2043' => [
            ['unit' => 'piece', 'unit_price' => 385.00, 'article' => 'DISPLAY PORT', 'description' => 'Brand: Ad-Link, display port to HDMI cable, 1.8m'],
            ['unit' => 'piece', 'unit_price' => 325.00, 'article' => 'VGA DISPLAYPORT', 'description' => ''],
            ['unit' => 'piece', 'unit_price' => 440.00, 'article' => 'POWERPOINT PRESENTER', 'description' => 'Wireless presenter, laser pointer'],
        ],
        '256-2517' => [
            ['unit' => 'piece', 'unit_price' => 3800.00, 'article' => 'WHEELBARROW', 'description' => 'Brand: Viking, galvanized, deep type tray, solid tire, 60 liters capacity'],
            ['unit' => 'piece', 'unit_price' => 4000.00, 'article' => 'WEIGHING SCALE', 'description' => 'Brand: Butterfly, mechanical, flat top metal/stainless steel plate, 100kg capacity'],
        ],
        '254-1078' => [
            ['unit' => 'piece', 'unit_price' => 10000.00, 'article' => 'STERILIZER', 'description' => 'Brand/Model: Hanabishi HDS-23cuft Dish Sterilizer'],
        ],
        '253-1901' => [
            ['unit' => 'unit', 'unit_price' => 1150.00, 'article' => 'EXTENSION CORD', 'description' => 'Brand: Omni Electric, 4 gang, 5m min cable, with individual switch and indicator light'],
            ['unit' => 'unit', 'unit_price' => 9500.00, 'article' => 'PAPER SHREDDER', 'description' => 'Brand/Model: Filux AF100'],
            ['unit' => 'piece', 'unit_price' => 350.00, 'article' => 'WASTE BIN', 'description' => 'green, 15L capacity, marked with biodegradable'],
            ['unit' => 'piece', 'unit_price' => 350.00, 'article' => 'WASTE BIN', 'description' => 'blue, 15L capacity, marked with biodegradable'],
            ['unit' => 'piece', 'unit_price' => 350.00, 'article' => 'WASTE BIN', 'description' => 'yellow, 15L capacity, marked with biodegradable'],
            ['unit' => 'unit', 'unit_price' => 12000.00, 'article' => 'WATER DISPENSER', 'description' => 'Brand/Model: Dowell WDS-19BLUV'],
        ],
        '256-2391' => [
            ['unit' => 'unit', 'unit_price' => 950.00, 'article' => 'EXTENSION WIRE', 'description' => 'Brand: Omni, 1.83m, 6 outlets, 4 switches, with circuit breaker'],
            ['unit' => 'unit', 'unit_price' => 400.00, 'article' => 'EXTENSION WIRE', 'description' => 'Brand: Omni, 5 meters, 4 outlets, 1 switch'],
            ['unit' => 'unit', 'unit_price' => 2950.00, 'article' => 'MOSQUITO KILLER', 'description' => 'Brand/Model: Daimaru BT2X10W, electric'],
        ],
        '2024-234' => [
            ['unit' => 'unit', 'unit_price' => 49968.08, 'article' => 'SOLAR-POWERED STREET LIGHT', 'description' => 'with PIR motion sensor, 6m high street light pole, with concrete pedestal footing'],
        ],
        '254-2383' => [
            ['unit' => 'unit', 'unit_price' => 2900.00, 'article' => 'UPS', 'description' => 'Brand/Model: APC BVX6501-PH'],
            ['unit' => 'unit', 'unit_price' => 1595.00, 'article' => 'WIRED HEADPHONES', 'description' => 'Brand/Model: AWEI GM-5'],
            ['unit' => 'unit', 'unit_price' => 32900.00, 'article' => 'LAPTOP COMPUTER', 'description' => 'Brand/Model: Lenovo Ideapad Slim 3 15IAH8'],
        ],
        '254-165' => [
            ['unit' => 'piece', 'unit_price' => 4800.00, 'article' => 'BIN BOX', 'description' => 'stackable, long side opening'],
            ['unit' => 'piece', 'unit_price' => 4500.00, 'article' => 'PUSH CART TROLLEY', 'description' => 'with foldable handle, 200 kgs capacity'],
            ['unit' => 'piece', 'unit_price' => 5900.00, 'article' => 'PUSH CART TROLLEY', 'description' => 'with foldable handle, 300 kgs capacity'],
            ['unit' => 'piece', 'unit_price' => 2900.00, 'article' => 'PRICE TAGGER', 'description' => 'heavy duty'],
            ['unit' => 'piece', 'unit_price' => 6000.00, 'article' => 'PALLET', 'description' => 'one-way export pallet, 1000x1200x120mm, 1 ton capacity'],
            ['unit' => 'piece', 'unit_price' => 4500.00, 'article' => 'STEEL RACK', 'description' => 'with wheels, 864(L) x 457(W) x 1905mm'],
        ],
        '254-1907' => [
            ['unit' => 'unit', 'unit_price' => 49000.00, 'article' => 'PRINTER', 'description' => 'Brand/Model: Epson L15150'],
        ],
        '254-2296' => [
            ['unit' => 'unit', 'unit_price' => 49000.00, 'article' => 'PHOTOCOPIER', 'description' => 'Brand/Model: Develop Ineo 205i, with table'],
        ],
        '254-2133' => [
            ['unit' => 'set', 'unit_price' => 46000.00, 'article' => 'DESKTOP COMPUTER', 'description' => 'clone, core i7-12700'],
            ['unit' => 'unit', 'unit_price' => 47000.00, 'article' => 'LAPTOP COMPUTER', 'description' => 'Brand/Model: HP 15-FD0252TU'],
            ['unit' => 'unit', 'unit_price' => 47000.00, 'article' => 'DESKTOP COMPUTER (CPU ONLY)', 'description' => 'clone, core i7'],
        ],
        '254-256' => [
            ['unit' => 'unit', 'unit_price' => 49750.00, 'article' => 'LAPTOP COMPUTER', 'description' => 'Brand/Model: Acer Aspire Lite AL 14-51M-57H1'],
            ['unit' => 'unit', 'unit_price' => 24850.00, 'article' => 'PRINTER', 'description' => 'Brand/Model: Epson Ecotank L6290'],
        ],
        '254-2297' => [
            ['unit' => 'unit', 'unit_price' => 17950.00, 'article' => 'PRINTER', 'description' => 'Brand/Model: Epson L6270'],
            ['unit' => 'unit', 'unit_price' => 1000.00, 'article' => 'CALCULATOR', 'description' => 'Brand/Model: Casio FX-82MS Scientific Calculator'],
        ],
        '211-2052' => [
            ['unit' => 'piece', 'unit_price' => 2500.00, 'article' => 'PLASTIC DRUM', 'description' => '200 liters'],
        ],
        '254-2354' => [
            ['unit' => 'unit', 'unit_price' => 48400.00, 'article' => 'LAPTOP COMPUTER', 'description' => 'Brand/Model: Vivo Book 16'],
        ],
        '2024-222' => [
            ['unit' => 'unit', 'unit_price' => 15900.00, 'article' => 'PRINTER', 'description' => 'Brand/Model: Epson L5290'],
            ['unit' => 'unit', 'unit_price' => 22260.00, 'article' => 'PRINTER', 'description' => 'Brand/Model: Epson Workforce WF-100'],
        ],
        '256-2325' => [
            ['unit' => 'unit', 'unit_price' => 45580.00, 'article' => 'TELEVISION', 'description' => 'Brand/Model: Samsung UA65DU7000G'],
        ],
        '256-2085' => [
            ['unit' => 'unit', 'unit_price' => 32000.00, 'article' => 'INTERNET DISH ANTENNA', 'description' => 'Brand/Model: Starlink UTA-232'],
        ],
        '254-2162' => [
            ['unit' => 'unit', 'unit_price' => 48900.00, 'article' => 'PRINTER', 'description' => 'Brand/Model: Epson EcoTank L6490'],
        ],
        '256-2436' => [
            ['unit' => 'unit', 'unit_price' => 485.00, 'article' => 'MOUSE', 'description' => 'Brand/Model: iMice G-1800, wireless mouse, rechargeable'],
        ],
        'reimbursement' => [
            ['unit' => 'unit', 'unit_price' => 3650.00, 'article' => 'WEIGHING SCALE', 'description' => 'Brand/Model: Ingco Electronic Scale, 30 kgs capacity'],
            ['unit' => 'unit', 'unit_price' => 16041.75, 'article' => 'PRINTER', 'description' => 'Brand/Model: Epson EcoTank L5290'],
        ],
        '256-2424' => [
            ['unit' => 'unit', 'unit_price' => 3600.00, 'article' => 'POWERBANK', 'description' => 'Brand/Model: Bavin PC089 Pro, 30000 mAh'],
        ],
        '2025-01' => [
            ['unit' => 'unit', 'unit_price' => 41605.00, 'article' => 'LAPTOP COMPUTER', 'description' => 'Brand/Model: Acer Aspire 15'],
            ['unit' => 'set', 'unit_price' => 48425.04, 'article' => 'DESKTOP COMPUTER', 'description' => 'Brand/Model: Acer Aspire TC-1775'],
        ],
        '256-298' => [
            ['unit' => 'unit', 'unit_price' => 3200.00, 'article' => 'KNAPSACK SPRAYER', 'description' => 'Brand: Gintong Palay, semi automatic, 16L capacity, with complete accessories and tools'],
        ],
        '254-2128' => [
            ['unit' => 'set', 'unit_price' => 35000.00, 'article' => 'TABLE', 'description' => 'customized table and chairs, detachable table design, lock mechanism, with 2 chairs'],
            ['unit' => 'set', 'unit_price' => 25000.00, 'article' => 'TABLE', 'description' => 'high chair table, with 3 high chair, lumber base'],
        ],
        '254-137' => [
            ['unit' => 'unit', 'unit_price' => 29650.00, 'article' => 'TELEVISION', 'description' => 'Brand/Model: Samsung 55" Smart TV'],
            ['unit' => 'unit', 'unit_price' => 4500.00, 'article' => 'MONITOR', 'description' => 'Brand/Model: Acer 21.5" LCD Monitor'],
            ['unit' => 'unit', 'unit_price' => 29250.00, 'article' => 'PRINTER', 'description' => 'Brand/Model: Epson Eco tank L11050'],
            ['unit' => 'unit', 'unit_price' => 7700.00, 'article' => 'PORTABLE AUDIO', 'description' => 'Brand: Jabra Portable Audio Conference Mic/Speaker'],
        ],
        '254-2108' => [
            ['unit' => 'piece', 'unit_price' => 340.00, 'article' => 'EXTENSION WIRE', 'description' => 'heavy duty, 3 gangs, flat cord, 5 meters'],
        ],
        '256-263' => [
            ['unit' => 'unit', 'unit_price' => 2350.00, 'article' => 'KNAPSACK SPRAYER', 'description' => 'Brand: Tung ho, capacity 16 liters'],
        ],
        '254-285' => [
            ['unit' => 'unit', 'unit_price' => 38500.00, 'article' => 'LAPTOP COMPUTER', 'description' => 'Brand/Model: MSI Modern 15 F13MG'],
            ['unit' => 'unit', 'unit_price' => 5330.00, 'article' => 'MONITOR', 'description' => 'Brand/Model: Philips 24E2N11'],
        ],
        '254-323' => [
            ['unit' => 'set', 'unit_price' => 18500.00, 'article' => 'MODULAR WORKSTATION', 'description' => '1290mm (W) x 645mm (D) x 1200mm (H), full fabric upholsetered panels, chrome finish'],
        ],
        '256-1775' => [
            ['unit' => 'unit', 'unit_price' => 7950.00, 'article' => 'PULVERIZER', 'description' => 'model: RRH-250, electric, stainless, food grade'],
            ['unit' => 'unit', 'unit_price' => 1000.00, 'article' => 'PH METER', 'description' => 'portable with buffer solution'],
            ['unit' => 'unit', 'unit_price' => 1500.00, 'article' => 'ILLUMINANCE METER', 'description' => 'Digital Lux Meter, model: LX1010B'],
            ['unit' => 'unit', 'unit_price' => 1500.00, 'article' => 'THERMOMETER/HYGROMETER', 'description' => '50 celcius to 70 celcius, 10% RN to 99% RH'],
            ['unit' => 'unit', 'unit_price' => 1000.00, 'article' => 'DIGITAL WEIGHING SCALE', 'description' => '1000g cap'],
        ],
        '254-284' => [
            ['unit' => 'unit', 'unit_price' => 13500.00, 'article' => 'PRINTER', 'description' => 'Brand/Model: Epson L5290'],
            ['unit' => 'unit', 'unit_price' => 6500.00, 'article' => 'PAPER SHREDDER', 'description' => 'Brand/Model: Aurora AS800CD'],
            ['unit' => 'unit', 'unit_price' => 40500.00, 'article' => 'LAPTOP COMPUTER', 'description' => 'Brand/Model: MSI Modern 15 F13MG-425 PH'],
        ],
        '254-2387' => [
            ['unit' => 'unit', 'unit_price' => 15900.00, 'article' => 'FILING CABINET', 'description' => 'Brand: Polaris, layer with vault, steel material, 4 layers, powder coated'],
            ['unit' => 'unit', 'unit_price' => 5300.00, 'article' => 'OFFICE CHAIR', 'description' => 'mesh + support wood + sponge layer, adjustable seat height, adjustable headrest, 90 degrees swivel armrest, fixed backrest'],
        ],
        '256-393' => [
            ['unit' => 'unit', 'unit_price' => 785.00, 'article' => 'FLIP PEN/PRESENTER', 'description' => 'PPT Presentation Pen, wireless rechargeable laser pointer, remote control plug and play'],
            ['unit' => 'unit', 'unit_price' => 4770.00, 'article' => 'EXTERNAL DATA STORAGE', 'description' => 'Brand/Model: Verbatim 53195, 2TB'],
        ],
        '256-652' => [
            ['unit' => 'unit', 'unit_price' => 5311.50, 'article' => 'UPS', 'description' => 'Brand/Model: AWD Aid 1500-1500VA'],
        ],
        '254-451' => [
            ['unit' => 'piece', 'unit_price' => 6950.00, 'article' => 'EXTERNAL HARD DRIVE', 'description' => 'Brand/Model: Verbatim 53195, 2TB'],
        ],
        '256-569' => [
            ['unit' => 'piece', 'unit_price' => 740.00, 'article' => 'PRESENTATION LASER POINTER', 'description' => 'PPT presentation pen, USB smart charging'],
        ],
        '256-491' => [
            ['unit' => 'set', 'unit_price' => 795.00, 'article' => 'KEYBOARD', 'description' => 'Brand: Deluxe, wireless keyboard and mouse combo'],
        ],
        '256-34' => [
            ['unit' => 'set', 'unit_price' => 3100.00, 'article' => 'SAFETY HARNESS', 'description' => 'Brand: Ingco, with comfort pad on thigh strap and waist strap, with multiple attachment and adjustment point, high strength polyester'],
            ['unit' => 'unit', 'unit_price' => 2700.00, 'article' => 'EXHAUST FAN', 'description' => 'Ventilating Fan, strong wind, silent operation'],
        ],
        '254-578' => [
            ['unit' => 'unit', 'unit_price' => 9000.00, 'article' => 'FAN', 'description' => 'Brand/Model: Kolin KIF-26SMBBLDC'],
            ['unit' => 'unit', 'unit_price' => 7500.00, 'article' => 'OFFICE CHAIR', 'description' => 'ergonomic office chair, heavy duty'],
        ],
        '254-397' => [
            ['unit' => 'unit', 'unit_price' => 7700.00, 'article' => 'PALLET', 'description' => 'reversible type, load: 1500 kgs, material: HDPE'],
        ],
        '254-359' => [
            ['unit' => 'piece', 'unit_price' => 999.00, 'article' => 'AWNING CANOPY', 'description' => 'roll up with scallop design, waterproof, heatproof, green, with free rope and 2 ft of eyelets'],
            ['unit' => 'piece', 'unit_price' => 500.00, 'article' => 'FOLDING OUTDOOR CHAIR', 'description' => 'waterproof, black, load bearing 150kgs'],
            ['unit' => 'piece', 'unit_price' => 1950.00, 'article' => 'FOLDING STORAGE BOX', 'description' => 'extended to portable table, multifunctional'],
            ['unit' => 'piece', 'unit_price' => 26250.00, 'article' => 'PARABOLIC TENT', 'description' => 'waterproof, blue'],
        ],
        '256-513' => [
            ['unit' => 'unit', 'unit_price' => 30000.00, 'article' => 'PAPER SHREDDER', 'description' => 'Brand/Model: Filux AF300'],
        ],
        '256-417' => [
            ['unit' => 'unit', 'unit_price' => 2250.00, 'article' => 'DIGITAL WEIGHING SCALE', 'description' => 'Brand/Model: Ingco HESA3303, precision: 1/3000'],
        ],
        '256-515' => [
            ['unit' => 'piece', 'unit_price' => 3500.00, 'article' => 'EXTENSION CORD', 'description' => 'Brand/Model: Huntkey SMV607 Power Strip, 6 ports, 5 meters'],
            ['unit' => 'piece', 'unit_price' => 3500.00, 'article' => 'WHITE BOARD', 'description' => 'with stand and wheels, double sided, adjustable height'],
        ],
        '254-352' => [
            ['unit' => 'set', 'unit_price' => 18000.00, 'article' => 'WATER TANK', 'description' => 'stainless, 1000 liters, vertical, with stand'],
            ['unit' => 'set', 'unit_price' => 9500.00, 'article' => 'TRASH BIN', 'description' => 'yellow, red, green, 120 liters each, with metal frame'],
        ],
        '256-382' => [
            ['unit' => 'unit', 'unit_price' => 14500.00, 'article' => 'WATER DISPENSER', 'description' => 'Brand/Model: Asahi WD-108, hot, cold and normal water, bottom load'],
        ],
        'BLANK-1' => [
            ['unit' => 'piece', 'unit_price' => 224.00, 'article' => 'MOUSE', 'description' => 'Brand/Model: A4tech OP-330 Optical Mouse'],
            ['unit' => 'piece', 'unit_price' => 649.00, 'article' => 'KEYBOARD', 'description' => 'Brand/Model: A4tech KRS-3, USB, wired'],
        ],
        '254-266' => [
            ['unit' => 'piece', 'unit_price' => 3900.00, 'article' => 'PALLETTE', 'description' => 'plastic, HDPE, color blue, 1200x100x150mm minimum'],
        ],
        '254-488' => [
            ['unit' => 'set', 'unit_price' => 48750.00, 'article' => 'DESKTOP COMPUTER', 'description' => 'Brand/Model: Acer Aspire TC-1775'],
            ['unit' => 'unit', 'unit_price' => 3180.00, 'article' => 'POWERBANK', 'description' => 'Brand/Model: Vention I13BB, 10000 mAH'],
        ],
        '254-365' => [
            ['unit' => 'unit', 'unit_price' => 31500.00, 'article' => 'PRINTER', 'description' => 'Brand/Model: Epson L14150'],
        ],
        '254-2476' => [
            ['unit' => 'unit', 'unit_price' => 10000.00, 'article' => 'EXECUTIVE CHAIR', 'description' => 'with headrest, faux leather, high back, lumbar support, padded arms'],
            ['unit' => 'unit', 'unit_price' => 46600.00, 'article' => 'EXECUTIVE TABLE', 'description' => 'customized design, solid wood/plywood, with 3 layer drawer, with 2 side tables'],
            ['unit' => 'unit', 'unit_price' => 25000.00, 'article' => 'OFFICE TABLE', 'description' => 'customized design, solid wood/plywood, with built in 4 drawers, 3 drawers'],
            ['unit' => 'unit', 'unit_price' => 21000.00, 'article' => 'STANDING DESK', 'description' => 'ergonomic, adjustable frame, adjustable height, electric system'],
            ['unit' => 'unit', 'unit_price' => 8000.00, 'article' => 'TRAINING TABLE', 'description' => 'foldable training table, with metal shelves, metal leg'],
            ['unit' => 'piece', 'unit_price' => 500.00, 'article' => 'MONOBLOCK CHAIR', 'description' => 'plastic, color white'],
        ],
        '253-1899' => [
            ['unit' => 'piece', 'unit_price' => 225.00, 'article' => 'MOUSE', 'description' => 'Brand/Model: A4tech OP720, wired, USB type'],
            ['unit' => 'piece', 'unit_price' => 400.00, 'article' => 'DESK ORGANIZER', 'description' => 'file tray, legal size, 3 layers'],
            ['unit' => 'set', 'unit_price' => 360.00, 'article' => 'EXTENSION CORD', 'description' => 'Brand: Omni, 3 gang, convenient outlet, 1.55m cable (min)'],
            ['unit' => 'unit', 'unit_price' => 2000.00, 'article' => 'FIRE EXTINGUISHER', 'description' => 'pure HCFC'],
        ],
        '254-669' => [
            ['unit' => 'unit', 'unit_price' => 5350.00, 'article' => 'STREET LIGHT', 'description' => '300 watts, with auto on and off, aluminum housing and glass cover, with solar panel with mounting accessories'],
        ],
        '256-559' => [
            ['unit' => 'unit', 'unit_price' => 13200.00, 'article' => 'LATERAL FILING CABINET', 'description' => 'powdered coated metal, plastic roller for railing, with central lock'],
        ],
        '254-364' => [
            ['unit' => 'set', 'unit_price' => 48500.00, 'article' => 'DESKTOP COMPUTER', 'description' => 'Brand/Model: Acer Aspire TC-1785'],
        ],
        '254-327' => [
            ['unit' => 'piece', 'unit_price' => 550.00, 'article' => 'TRIER', 'description' => 'buriki, standard size, stainless steel'],
            ['unit' => 'piece', 'unit_price' => 200.00, 'article' => 'SHARPENING STONE', 'description' => 'carborandum, good quality'],
            ['unit' => 'piece', 'unit_price' => 4800.00, 'article' => 'DIGITAL HUMIDITY & TEMPERATURE METER', 'description' => 'Brand/Model: Incgo HETHT01'],
            ['unit' => 'piece', 'unit_price' => 430.00, 'article' => 'THERMOMETER', 'description' => 'wall mounted, glass, +50 degres celcius'],
            ['unit' => 'piece', 'unit_price' => 380.00, 'article' => 'PRUNING SAW', 'description' => 'manual, two-component plastic handle'],
            ['unit' => 'piece', 'unit_price' => 4700.00, 'article' => 'EARTH AUGER DRILL BIT DIGGER', 'description' => 'Brand/Model: Racoco 4hp 68cc'],
            ['unit' => 'piece', 'unit_price' => 450.00, 'article' => 'BOLO', 'description' => 'with casing, atleast 20" length including handle, wood handle'],
            ['unit' => 'piece', 'unit_price' => 350.00, 'article' => 'PANABAS', 'description' => 'without handle, heavy duty'],
            ['unit' => 'piece', 'unit_price' => 1300.00, 'article' => 'POST HOLE DIGGER', 'description' => 'Brand/Model: Toolcraft TC2040, length 48"'],
            ['unit' => 'piece', 'unit_price' => 4700.00, 'article' => 'RACHET DIE STOCK (PIPE THREADER)', 'description' => '¼", ⅜", ½", ¾", 1", 1¼" (no. 62), with case'],
            ['unit' => 'piece', 'unit_price' => 600.00, 'article' => 'BARETA BAR', 'description' => '2" with by 1 ft fully welded with pipe handle, total length 5feet'],
        ],
        '256-358' => [
            ['unit' => 'piece', 'unit_price' => 600.00, 'article' => 'TAPE MEASURE', 'description' => 'Brand/Model: Creston CFT-350, Fiberglass Measuring Tape, 50m'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding contract items...');

        $secondaryCategories = SecondaryCategory::all()->keyBy('name');
        $allContractNumbers = array_keys($this->data);
        $foundContractNumbers = [];

        DB::transaction(function () use ($secondaryCategories, $allContractNumbers, &$foundContractNumbers) {
            foreach (Contract::whereIn('contract_po_ib_number', $allContractNumbers)->cursor() as $contract) {
                $foundContractNumbers[] = $contract->contract_po_ib_number;
                $items = $this->data[$contract->contract_po_ib_number] ?? [];

                foreach ($items as $item) {
                    $this->seedContractItem($item, $contract, $secondaryCategories);
                }
            }
        });

        $notFoundContracts = array_diff($allContractNumbers, $foundContractNumbers);
        foreach ($notFoundContracts as $contractNumber) {
            $this->command->warn("Contract '{$contractNumber}' not found. Skipping items.");
        }

        $this->command->info('Finished seeding contract items.');
    }

    private function seedContractItem(array $item, Contract $contract, $secondaryCategories): void
    {
        $categoryName = $this->determineSecondaryCategory($item['article'], $item['description']);
        $secondaryCategory = $secondaryCategories->get($categoryName);

        if (! $secondaryCategory) {
            $this->command->warn("Could not determine secondary category for '{$item['article']}'. Using 'Miscellaneous'.");
            $secondaryCategory = $secondaryCategories->get('Miscellaneous');
        }

        $itemCatalog = ItemsCatalog::firstOrCreate(
            ['name' => $item['article']],
            [
                'unit' => strtolower($item['unit']),
                'secondary_category_id' => $secondaryCategory->id,
                'code' => $this->generateItemCode($item['article']),
            ]
        );

        $specData = $this->parseDescription($item['description']);
        $itemSpecification = ItemSpecification::firstOrCreate(
            [
                'item_catalog_id' => $itemCatalog->id,
                'detailed_specifications' => $specData['detailed_specifications'],
            ],
            [
                'brand' => $specData['brand'],
                'model' => $specData['model'],
            ]
        );

        ContractItem::firstOrCreate(
            [
                'contract_id' => $contract->id,
                'item_specification_id' => $itemSpecification->id,
                'unit_price' => $item['unit_price'],
            ],
            [
                'item_type' => 'ICS',
            ]
        );
    }

    private function determineSecondaryCategory(string $article, string $description): ?string
    {
        $articleUpper = strtoupper($article);
        $fullText = $articleUpper.' '.strtoupper($description);

        $map = [
            'Chairs and Seating' => ['OFFICE CHAIR', 'EXECUTIVE CHAIR', 'MONOBLOCK CHAIR', 'FOLDING OUTDOOR CHAIR', 'TRAINING TABLE', 'STANDING DESK'],
            'Connectivity and Hubs' => ['USB DOCK', 'USB HUB', 'DISPLAY PORT', 'VGA DISPLAYPORT', 'INTERNET DISH ANTENNA'],
            'Computer Peripherals' => ['KEYBOARD WITH MOUSE', 'POWERPOINT PRESENTER', 'WIRED HEADPHONES', 'MOUSE', 'KEYBOARD', 'FLIP PEN/PRESENTER', 'PRESENTATION LASER POINTER'],
            'Office Machinery' => ['PRINTER', 'TIME ATTENDANCE', 'PAPER SHREDDER', 'PHOTOCOPIER', 'CALCULATOR', 'PRICE TAGGER'],
            'Hand Tools' => ['GUN TACKER', 'TRIER', 'SHARPENING STONE', 'PRUNING SAW', 'BOLO', 'PANABAS', 'POST HOLE DIGGER', 'RACHET DIE STOCK', 'BARETA BAR'],
            'General Office Appliances' => ['TOWER FAN', 'WATER DISPENSER', 'ELECTRONIC INSECT KILLER', 'CHEST COOLER', 'MOSQUITO KILLER', 'EXHAUST FAN', 'FAN'],
            'Refrigerators and Freezers' => ['UPRIGHT FREEZER'],
            'General Laboratory Equipment' => ['DIGITAL THERMOMETER', 'HOT PLATE', 'WATER BATH', 'STERILIZER', 'DIGITAL HUMIDITY & TEMPERATURE METER', 'THERMOMETER/HYGROMETER', 'ILLUMINANCE METER', 'PH METER'],
            'Safety Gear' => ['HELMET', 'SAFETY HARNESS'],
            'Carts and Trolleys' => ['WHEELBARROW', 'PUSH CART TROLLEY'],
            'Measurement Tools' => ['WEIGHING SCALE', 'DIGITAL WEIGHING SCALE', 'TAPE MEASURE'],
            'Power and Electrical' => ['EXTENSION CORD', 'EXTENSION WIRE', 'UPS', 'POWERBANK'],
            'Waste Bins' => ['WASTE BIN', 'TRASH BIN'],
            'Outdoor Structures' => ['SOLAR-POWERED STREET LIGHT', 'AWNING CANOPY', 'PARABOLIC TENT', 'STREET LIGHT'],
            'Computers and Laptops' => ['LAPTOP COMPUTER', 'DESKTOP COMPUTER'],
            'Storage Containers' => ['BIN BOX', 'PALLET', 'PLASTIC DRUM', 'FOLDING STORAGE BOX', 'WATER TANK', 'PALLETTE'],
            'Storage and Cabinets' => ['STEEL RACK', 'FILING CABINET', 'DESK ORGANIZER', 'LATERAL FILING CABINET'],
            'Audio-Visual Equipment' => ['TELEVISION', 'PORTABLE AUDIO'],
            'Tables and Desks' => ['EXECUTIVE TABLE', 'OFFICE TABLE', 'MODULAR WORKSTATION'],
            'Presentation and Display' => ['WHITE BOARD'],
            'Field Machinery' => ['EARTH AUGER DRILL BIT DIGGER', 'PULVERIZER'],
            'Farm Supplies' => ['KNAPSACK SPRAYER'],
        ];

        foreach ($map as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($fullText, $keyword)) {
                    return $category;
                }
            }
        }

        return 'Miscellaneous'; // Default category
    }

    private function parseDescription(string $description): array
    {
        $brand = null;
        $model = null;
        $detailedSpecs = $description;

        $multiWordBrands = ['WE HOME', 'OMNI ELECTRIC', 'AD-LINK', 'A4TECH'];

        if (str_contains(strtoupper($description), 'DESKTOP COMPUTER')) {
            // Special handling for desktop computers to avoid parsing complex descriptions
        } elseif (preg_match('/Brand\/Model:\s*([^,]+)/i', $description, $matches)) {
            $brandModelStr = trim($matches[1]);
            $foundBrand = false;

            foreach ($multiWordBrands as $multiWordBrand) {
                if (str_starts_with(strtoupper($brandModelStr), $multiWordBrand.' ')) {
                    $brand = substr($brandModelStr, 0, strlen($multiWordBrand));
                    $model = trim(substr($brandModelStr, strlen($multiWordBrand)));
                    $foundBrand = true;
                    break;
                }
            }

            if (! $foundBrand) {
                $parts = explode(' ', $brandModelStr, 2);
                $brand = $parts[0] ?? null;
                $model = $parts[1] ?? null;
            }

            $detailedSpecs = ltrim(trim(str_replace($matches[0], '', $description)), ', ');

        } elseif (preg_match('/Brand:\s*([^,]+)/i', $description, $matches)) {
            $brandStr = trim($matches[1]);
            $foundBrand = false;

            foreach ($multiWordBrands as $multiWordBrand) {
                if (str_starts_with(strtoupper($brandStr), $multiWordBrand)) {
                    $brand = substr($brandStr, 0, strlen($multiWordBrand));
                    $foundBrand = true;
                    break;
                }
            }

            if (! $foundBrand) {
                $parts = explode(' ', $brandStr, 2);
                $brand = $parts[0] ?? null;
            }
            $detailedSpecs = ltrim(trim(str_replace($matches[0], '', $description)), ', ');
        }

        return [
            'brand' => $brand,
            'model' => $model,
            'detailed_specifications' => $detailedSpecs,
        ];
    }

    private function generateItemCode(string $itemName): string
    {
        $baseCode = substr(strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $itemName))), 0, 35);

        return $baseCode.'-'.uniqid();
    }
}
