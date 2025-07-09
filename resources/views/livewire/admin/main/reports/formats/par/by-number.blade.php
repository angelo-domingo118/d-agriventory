{{-- Placeholder for PAR Report Format (By Number) --}}
{{-- This is not the proper format. Edit this file to implement the actual report layout. --}}

@php
    $items = [['qty' => 1, 'unit' => 'unit', 'desc' => '<strong>PHOTOCOPIER</strong><br>Brand/Model: Kyocera TaskAlfa 2554CI<br>Serial Number: WB33600259', 'property_no' => '2025 - 5020 - 0001 - 01 - 13', 'date_acquired' => '1/16/2025', 'unit_cost' => '196,000.00']];
@endphp

<div
    class="mx-auto w-[724px] aspect-[1/1.414] bg-white p-8 text-stone-900 shadow-lg dark:bg-white text-xs relative">
    <div class="text-center mb-4">
        <h4 class="text-sm font-bold tracking-wider">PROPERTY ACKNOWLEDGEMENT RECEIPT</h4>
    </div>

    <div class="grid grid-cols-2">
        <div class="space-y-1">
            <p><strong>Date prepared:</strong> 1/31/2025</p>
            <p><strong>Entity Name:</strong> Department of Agriculture - Regional Field Unit - CAR</p>
            <p><strong>Fund Cluster:</strong> ____________________</p>
        </div>
        <div class="text-right">
            <p><strong>PAR No:</strong> ____________________</p>
        </div>
    </div>

    <table class="w-full border-collapse border border-black mt-2 text-xs">
        <thead>
            <tr>
                <th class="border border-black p-1 text-center" style="width: 5%;">Qty</th>
                <th class="border border-black p-1 text-center" style="width: 5%;">Unit</th>
                <th class="border border-black p-1 text-center" style="width: 45%;">Description</th>
                <th class="border border-black p-1 text-center" style="width: 20%;">Property Number</th>
                <th class="border border-black p-1 text-center" style="width: 12.5%;">Date Acquired</th>
                <th class="border border-black p-1 text-center" style="width: 12.5%;">Unit Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr class="align-top">
                    <td class="border border-black p-1 text-center">{{ $item['qty'] }}</td>
                    <td class="border border-black p-1 text-center">{{ $item['unit'] }}</td>
                    <td class="border border-black p-1">{!! $item['desc'] !!}</td>
                    <td class="border border-black p-1 text-center">{{ $item['property_no'] }}</td>
                    <td class="border border-black p-1 text-center">{{ $item['date_acquired'] }}</td>
                    <td class="border border-black p-1 text-right">{{ $item['unit_cost'] }}</td>
                </tr>
            @endforeach
            {{-- Add empty rows to fill the page --}}
            @for ($i = 0; $i < 15; $i++)
                <tr>
                    <td class="border border-black h-6">&nbsp;</td>
                    <td class="border border-black"></td>
                    <td class="border border-black"></td>
                    <td class="border border-black"></td>
                    <td class="border border-black"></td>
                    <td class="border border-black"></td>
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="mt-2">
        <p><strong>Remarks:</strong> For FOD (RICE) use</p>
        <p><strong>Document Source:</strong> Supplier: Infoworx Inc.; Contract/PO/IB No: 254-1907</p>
    </div>

    <div class="absolute bottom-24 w-[calc(100%-4rem)]">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p>Received by:</p>
                <div class="mt-10 border-t border-black pt-1 text-center">
                    <p class="font-bold">FRANCO, Edwin Joseph</p>
                    <p class="text-[10px]">Signature over Printed Name</p>
                </div>
                <div class="mt-4 border-t border-black pt-1 text-center">
                    <p>&nbsp;</p>
                    <p class="text-[10px]">Position</p>
                </div>
                <div class="mt-4 border-t border-black pt-1 text-center">
                    <p>&nbsp;</p>
                    <p class="text-[10px]">Date</p>
                </div>
            </div>
            <div>
                <p>Received from:</p>
                <div class="mt-10 border-t border-black pt-1 text-center">
                    <p class="font-bold">Julio Earl Dicksen</p>
                    <p class="text-[10px]">Signature over Printed Name</p>
                </div>
                <div class="mt-4 border-t border-black pt-1 text-center">
                    <p class="font-bold">Admin Officer III</p>
                    <p class="text-[10px]">Position</p>
                </div>
                <div class="mt-4 border-t border-black pt-1 text-center">
                    <p>&nbsp;</p>
                    <p class="text-[10px]">Date</p>
                </div>
            </div>
        </div>
    </div>

    <div class="absolute bottom-8 left-8 text-[10px]">
        <p>GAM for NGA Volume II</p>
        <p>Appendix 71, page 173</p>
    </div>
</div> 