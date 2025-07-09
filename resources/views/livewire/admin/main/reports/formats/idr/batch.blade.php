<div
    class="mx-auto bg-white p-6 text-stone-900 shadow-lg dark:bg-white @if ($reportFormat === 'by_employee') w-[1024px] aspect-[1.414/1] @else w-[724px] aspect-[1/1.414] @endif">
    {{-- IDR Batch Preview --}}
    <div class="text-center">
        <h4 class="text-lg font-bold">INVENTORY DISTRIBUTION RECEIPT</h4>
    </div>
    <div class="mt-6 flex justify-between text-sm">
        <div>
            <p><strong>Division:</strong>____________________</p>
            <p><strong>Office/Section:</strong>____________________</p>
        </div>
        <div>
            <p><strong>IDR No:</strong>____________________</p>
            <p><strong>Date:</strong>____________________</p>
        </div>
    </div>
    <div class="mt-4">
        <table class="w-full border-collapse border text-sm">
            <thead>
                <tr>
                    <th class="border p-2 text-center" style="width: 10%;">Item No.</th>
                    <th class="border p-2 text-center" style="width: 10%;">Qty</th>
                    <th class="border p-2 text-center" style="width: 10%;">Unit</th>
                    <th class="border p-2 text-center" style="width: 50%;">Description
                    </th>
                    <th class="border p-2 text-center" style="width: 20%;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < 15; $i++)
                    <tr>
                        <td class="border p-2 text-center">{{ $i + 1 }}</td>
                        <td class="border p-2"></td>
                        <td class="border p-2"></td>
                        <td class="border p-2"></td>
                        <td class="border p-2"></td>
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>
    <div class="mt-8 grid grid-cols-2 gap-16">
        <div>
            <p>Received by:</p>
            <div class="mt-10 border-t pt-2 text-center">
                <p>&nbsp;</p>
                <p class="text-xs">Signature over Printed Name/Date</p>
            </div>
        </div>
        <div>
            <p>Issued by:</p>
            <div class="mt-10 border-t pt-2 text-center">
                <p>&nbsp;</p>
                <p class="text-xs">Signature over Printed Name/Date</p>
            </div>
        </div>
    </div>
    <div class="mt-4 flex justify-end text-xs">
        <span>DA-CAR-FMD-Form No. 36-A, Rev. 00</span>
    </div>
</div> 