{{-- Placeholder for PAR Report Format (By Employee) --}}
{{-- This is not the proper format. Edit this file to implement the actual report layout. --}}

@php
    $items = [
        [
            'article' => 'DESKTOP COMPUTER',
            'desc' => 'Brand/Model: Acer Altos P10 F8<br>Serial Number: DTL0JSP003342008633000<br><br>MONITOR<br>Brand/Model: Acer V24TY<br>Serial Number: 44201839042<br><br>KEYBOARD & MOUSE<br><br>UPS<br>Brand/Model: CyberPower UT1000EGLCD<br>Serial Number: 320710DZ30000303',
            'qty' => 1,
            'unit' => 'set',
            'unit_cost' => '64,379.10',
            'property_no' => '2025 - 5030 - 0081 - 01 - 02',
            'remarks' => 'For AFD (Supply and Property Unit) use'
        ],
        [
            'article' => 'DESKTOP COMPUTER',
            'desc' => 'Brand/Model: Acer Aspire TC-1785<br>Serial Number: DTBLNSP0034470077D9600<br><br>MONITOR<br>Brand/Model: Acer SA222Q<br>Serial Number: MMTX5SP0034410417E2X00<br><br>KEYBOARD & MOUSE<br><br>UPS<br>Brand/Model: CyberPower UT1000EGLCD<br>Serial Number: 320710DZ30000297',
            'qty' => 1,
            'unit' => 'set',
            'unit_cost' => '52,719.10',
            'property_no' => '2025 - 5030 - 0040 - 01 - 02',
            'remarks' => 'For AFD (Supply and Property Unit)'
        ],
    ];
@endphp

<div class="mx-auto w-[1024px] aspect-[1.414/1] bg-white p-6 text-stone-900 shadow-lg dark:bg-white relative">
    <div class="text-center">
        <h4 class="text-lg font-bold">LIST OF "P.A.R." ISSUED TO EMPLOYEE</h4>
    </div>

    <div class="mt-6 flex justify-between text-sm">
        <p><strong>Name of Employee:</strong> DICKSEN, Julio Earl</p>
        <p><strong>Date printed:</strong> 7/9/2025</p>
    </div>

    <div class="mt-4">
        <table class="w-full border-collapse border border-stone-800 text-sm">
            <thead>
                <tr>
                    <th class="border border-stone-800 p-2 text-center" style="width: 20%;">ARTICLE</th>
                    <th class="border border-stone-800 p-2 text-center" style="width: 35%;">DESCRIPTION</th>
                    <th class="border border-stone-800 p-2 text-center" style="width: 5%;">QTY</th>
                    <th class="border border-stone-800 p-2 text-center" style="width: 10%;">UNIT OF MEASURE</th>
                    <th class="border border-stone-800 p-2 text-center" style="width: 10%;">UNIT COST</th>
                    <th class="border border-stone-800 p-2 text-center" style="width: 10%;">PROPERTY NUMBER</th>
                    <th class="border border-stone-800 p-2 text-center" style="width: 10%;">REMARKS</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td class="border border-stone-800 p-1 align-top">{{ $item['article'] }}</td>
                        <td class="border border-stone-800 p-1 align-top">{!! $item['desc'] !!}</td>
                        <td class="border border-stone-800 p-1 text-center align-top">{{ $item['qty'] }}</td>
                        <td class="border border-stone-800 p-1 text-center align-top">{{ $item['unit'] }}</td>
                        <td class="border border-stone-800 p-1 text-right align-top">{{ $item['unit_cost'] }}</td>
                        <td class="border border-stone-800 p-1 text-center align-top">{{ $item['property_no'] }}</td>
                        <td class="border border-stone-800 p-1 align-top">{{ $item['remarks'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="absolute bottom-6 left-6 text-sm">
        Page 1 of 1
    </div>
    <div class="absolute bottom-6 right-6 text-sm font-bold underline">
        DICKSEN, Julio Earl
    </div>
</div> 