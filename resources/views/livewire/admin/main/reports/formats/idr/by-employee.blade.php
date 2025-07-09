@php
    $items = [
        ['article' => 'MEASURING CUP', 'desc' => 'plastic, 1000ml capacity<br>with handle<br><br>* training on peanut production and<br>processing (2 batches)', 'qty' => 10, 'unit' => 'unit', 'total_cost' => '2,080.00', 'stock_no' => '2025 - 13 - 43', 'remarks' => 'Supplier: Markjens Grains and General<br>Merchandise ; Contract/PO/IB No: 254-240'],
        ['article' => 'PEANUT GRINDER', 'desc' => 'portable grinder for wet and dry mills<br>voltage: 220V 50-60hz<br><br>* training on peanut production and<br>processing (2 batches)', 'qty' => 2, 'unit' => 'unit', 'total_cost' => '17,890.00', 'stock_no' => '2025 - 14 - 43', 'remarks' => 'Supplier: Markjens Grains and General<br>Merchandise ; Contract/PO/IB No: 254-240'],
        ['article' => 'PLASTIC JAR', 'desc' => 'for peanut butter<br>large, 15.1cmx8cm, 500ml<br>wide opening as well as clear round shape<br>100pcs/box<br><br>* training on peanut production and<br>processing (2 batches)', 'qty' => 4, 'unit' => 'box', 'total_cost' => '5,920.00', 'stock_no' => '2025 - 15 - 43', 'remarks' => 'Supplier: Markjens Grains and General<br>Merchandise ; Contract/PO/IB No: 254-240'],
        ['article' => 'PLASTIC JAR', 'desc' => 'for peanut butter<br>medium, 13cmx7.1cm, 400ml<br>wide opening as well as clear round shape<br><br>* training on peanut production and<br>processing (2 batches)', 'qty' => 4, 'unit' => 'box', 'total_cost' => '5,360.00', 'stock_no' => '2025 - 16 - 43', 'remarks' => 'Supplier: Markjens Grains and General<br>Merchandise ; Contract/PO/IB No: 254-240'],
        ['article' => 'PRUNING SHEAR', 'desc' => 'size: 8.5", 220mm<br>55# carbon steel blade<br><br>* training on Lufa Sponge Production and Post-<br>Harvest Processing (2 batches)', 'qty' => 30, 'unit' => 'piece', 'total_cost' => '10,650.00', 'stock_no' => '2025 - 18 - 43', 'remarks' => 'Supplier: Bump Baby & Beyond Specialized Goods<br>and Trading ; Contract/PO/IB No: 254-211'],
    ];
@endphp

<div class="relative mx-auto w-[1024px] aspect-[1.414/1] bg-white p-6 text-stone-900 shadow-lg dark:bg-white">
    <div class="text-center">
        <h4 class="text-lg font-bold">LIST OF "I.D.R." ISSUED TO EMPLOYEE</h4>
    </div>

    <div class="mt-6 flex justify-between text-sm">
        <p><strong>Name of Employee:</strong> TEJERO, Marilyn</p>
        <p><strong>Date printed:</strong> 7/9/2025</p>
    </div>

    <div class="mt-4">
        <table class="w-full border-collapse border border-black text-sm">
            <thead>
                <tr>
                    <th class="border border-black p-2 text-center" style="width: 15%;">ARTICLE</th>
                    <th class="border border-black p-2 text-center" style="width: 30%;">DESCRIPTION</th>
                    <th class="border border-black p-2 text-center" style="width: 5%;">QTY</th>
                    <th class="border border-black p-2 text-center" style="width: 10%;">UNIT OF MEASURE</th>
                    <th class="border border-black p-2 text-center" style="width: 10%;">TOTAL COST</th>
                    <th class="border border-black p-2 text-center" style="width: 10%;">STOCK NUMBER</th>
                    <th class="border border-black p-2 text-center" style="width: 20%;">REMARKS</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr class="align-top">
                        <td class="border border-black p-1 align-top">{{ $item['article'] }}</td>
                        <td class="border border-black p-1 align-top">{!! $item['desc'] !!}</td>
                        <td class="border border-black p-1 text-center align-top">{{ $item['qty'] }}</td>
                        <td class="border border-black p-1 text-center align-top">{{ $item['unit'] }}</td>
                        <td class="border border-black p-1 text-right align-top">{{ $item['total_cost'] }}</td>
                        <td class="border border-black p-1 text-center align-top">{{ $item['stock_no'] }}</td>
                        <td class="border border-black p-1 align-top">{!! $item['remarks'] !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="absolute bottom-6 left-6 text-sm">
        Page 1 of 9
    </div>
</div> 