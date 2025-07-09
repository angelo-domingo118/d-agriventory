{{-- Placeholder for IDR Report Format (Batch) --}}
{{-- This is not the proper format. Edit this file to implement the actual report layout. --}}

<div
    class="mx-auto w-[724px] aspect-[1/1.414] bg-white p-8 text-stone-900 shadow-lg dark:bg-white text-xs relative">
    <div class="text-center mb-4">
        <h4 class="text-sm font-bold tracking-wider">INVENTORY DISTRIBUTION RECEIPT</h4>
    </div>

    <div class="grid grid-cols-2">
        <div class="space-y-1">
            <p><strong>Division:</strong> ____________________</p>
            <p><strong>Office/Section:</strong> ____________________</p>
        </div>
        <div class="text-right">
            <p><strong>IDR No:</strong> ____________________</p>
            <p><strong>Date:</strong> ____________________</p>
        </div>
    </div>

    <table class="w-full border-collapse border border-black mt-2 text-xs">
        <thead>
            <tr>
                <th class="border border-black p-1 text-center" style="width: 10%;">Item No.</th>
                <th class="border border-black p-1 text-center" style="width: 10%;">Qty</th>
                <th class="border border-black p-1 text-center" style="width: 10%;">Unit</th>
                <th class="border border-black p-1 text-center" style="width: 50%;">Description</th>
                <th class="border border-black p-1 text-center" style="width: 20%;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < 20; $i++)
                <tr>
                    <td class="border border-black p-1 text-center h-6">{{ $i + 1 }}</td>
                    <td class="border border-black"></td>
                    <td class="border border-black"></td>
                    <td class="border border-black"></td>
                    <td class="border border-black"></td>
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="absolute bottom-24 w-[calc(100%-4rem)]">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p>Received by:</p>
                <div class="mt-10 border-t border-black pt-1 text-center">
                    <p>&nbsp;</p>
                    <p class="text-[10px]">Signature over Printed Name/Date</p>
                </div>
            </div>
            <div>
                <p>Issued by:</p>
                <div class="mt-10 border-t border-black pt-1 text-center">
                    <p>&nbsp;</p>
                    <p class="text-[10px]">Signature over Printed Name/Date</p>
                </div>
            </div>
        </div>
    </div>

    <div class="absolute bottom-8 right-8 text-[10px]">
        <span>DA-CAR-FMD-Form No. 36-A, Rev. 00</span>
    </div>
</div> 