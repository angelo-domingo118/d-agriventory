{{-- Placeholder for ICS Report Format (By Number) --}}
{{-- This is not the proper format. Edit this file to implement the actual report layout. --}}

@php
    $items_page1 = [
        ['inv_no' => 'SPHV - 0002 - 01 - 2025', 'serial' => '0750100024490016'],
        ['inv_no' => 'SPHV - 0003 - 01 - 2025', 'serial' => '0750100024490024'],
        ['inv_no' => 'SPHV - 0004 - 01 - 2025', 'serial' => '0750100024490025'],
        ['inv_no' => 'SPHV - 0005 - 01 - 2025', 'serial' => '0750100024490018'],
        ['inv_no' => 'SPHV - 0006 - 01 - 2025', 'serial' => '0750100024490027'],
        ['inv_no' => 'SPHV - 0007 - 01 - 2025', 'serial' => '0750100024490022'],
        ['inv_no' => 'SPHV - 0008 - 01 - 2025', 'serial' => '0750100024490021'],
    ];
    $items_page2 = [
        ['inv_no' => 'SPHV - 0009 - 01 - 2025', 'serial' => '0750100024490030'],
        ['inv_no' => 'SPHV - 0010 - 01 - 2025', 'serial' => '0750100024490023'],
        ['inv_no' => 'SPHV - 0011 - 01 - 2025', 'serial' => '0750100024490019'],
        ['inv_no' => 'SPHV - 0012 - 01 - 2025', 'serial' => '0750100024490020'],
    ];
@endphp

{{-- Page 1 --}}
<div
    class="mx-auto bg-white p-6 text-stone-900 shadow-lg dark:bg-white @if ($reportFormat === 'by_employee') w-[1024px] aspect-[1.414/1] @else w-[724px] aspect-[1/1.414] @endif">
    <div class="text-center">
        <h4 class="text-lg font-bold">INVENTORY CUSTODIAN SLIP</h4>
    </div>
    <div class="mt-6 flex justify-between text-sm">
        <div>
            <p><strong>Date prepared:</strong> 1/7/2025</p>
            <p><strong>Entity Name:</strong> Department of Agriculture - Regional Field
                Unit -
                CAR</p>
            <p><strong>Fund Cluster:</strong></p>
        </div>
        <div>
            <p><strong>ICS No:</strong> ____________________</p>
        </div>
    </div>
    <div class="mt-4">
        <table class="w-full border-collapse border text-sm">
            <thead>
                <tr>
                    <th class="border p-2 text-center" style="width: 5%;">Qty</th>
                    <th class="border p-2 text-center" style="width: 5%;">Unit</th>
                    <th class="border p-2 text-center" colspan="2">Amount</th>
                    <th class="border p-2 text-center" style="width: 40%;">Description
                    </th>
                    <th class="border p-2 text-center" style="width: 20%;">Inventory
                        Item
                        Number</th>
                    <th class="border p-2 text-center" style="width: 15%;">Estimated
                        Useful
                        Life</th>
                </tr>
                <tr>
                    <th class="border p-2"></th>
                    <th class="border p-2"></th>
                    <th class="border p-2" style="width: 10%;">Unit Cost</th>
                    <th class="border p-2" style="width: 10%;">Total Cost</th>
                    <th class="border p-2"></th>
                    <th class="border p-2"></th>
                    <th class="border p-2"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items_page1 as $item)
                    <tr>
                        <td class="border p-2 text-center">1</td>
                        <td class="border p-2 text-center">unit</td>
                        <td class="border p-2 text-right">20,000.00</td>
                        <td class="border p-2 text-right">20,000.00</td>
                        <td class="border p-2">
                            <strong>TIME ATTENDANCE TERMINAL</strong><br>
                            Brand/Model: Anviz C2 Pro<br>
                            Serial Number: {{ $item['serial'] }}
                        </td>
                        <td class="border p-2 text-center">{{ $item['inv_no'] }}</td>
                        <td class="border p-2"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-8 grid grid-cols-2 gap-16">
        <div>
            <p>Received by:</p>
            <div class="mt-10 border-t pt-2 text-center">
                <p>DICKSON, Julio Earl</p>
                <p class="text-xs">Signature over Printed Name</p>
            </div>
            <div class="mt-6 border-t pt-2 text-center">
                <p>&nbsp;</p>
                <p class="text-xs">Position</p>
            </div>
            <div class="mt-6 border-t pt-2 text-center">
                <p>&nbsp;</p>
                <p class="text-xs">Date</p>
            </div>
        </div>
        <div>
            <p>Received from:</p>
            <div class="mt-10 border-t pt-2 text-center">
                <p>Julio Earl Dicksen</p>
                <p class="text-xs">Signature over Printed Name</p>
            </div>
            <div class="mt-6 border-t pt-2 text-center">
                <p>Admin Officer III</p>
                <p class="text-xs">Position</p>
            </div>
            <div class="mt-6 border-t pt-2 text-center">
                <p>&nbsp;</p>
                <p class="text-xs">Date</p>
            </div>
        </div>
    </div>
    <div class="mt-4 flex justify-between text-xs">
        <span>G.A.M. for NGA, Volume II, Appendix 59, page 149</span>
        <span>Page 1 of 2</span>
    </div>
</div>

{{-- Page 2 --}}
<div
    class="mx-auto bg-white p-6 text-stone-900 shadow-lg dark:bg-white @if ($reportFormat === 'by_employee') w-[1024px] aspect-[1.414/1] @else w-[724px] aspect-[1/1.414] @endif">
    <div class="text-center">
        <h4 class="text-lg font-bold">INVENTORY CUSTODIAN SLIP</h4>
    </div>
    <div class="mt-6 flex justify-between text-sm">
        <div>
            <p><strong>Date prepared:</strong> 1/7/2025</p>
            <p><strong>Entity Name:</strong> Department of Agriculture - Regional Field
                Unit -
                CAR</p>
            <p><strong>Fund Cluster:</strong></p>
        </div>
        <div>
            <p><strong>ICS No:</strong> ____________________</p>
        </div>
    </div>
    <div class="mt-4">
        <table class="w-full border-collapse border text-sm">
            <thead>
                <tr>
                    <th class="border p-2 text-center" style="width: 5%;">Qty</th>
                    <th class="border p-2 text-center" style="width: 5%;">Unit</th>
                    <th class="border p-2 text-center" colspan="2">Amount</th>
                    <th class="border p-2 text-center" style="width: 40%;">Description
                    </th>
                    <th class="border p-2 text-center" style="width: 20%;">Inventory
                        Item
                        Number</th>
                    <th class="border p-2 text-center" style="width: 15%;">Estimated
                        Useful
                        Life</th>
                </tr>
                <tr>
                    <th class="border p-2"></th>
                    <th class="border p-2"></th>
                    <th class="border p-2" style="width: 10%;">Unit Cost</th>
                    <th class="border p-2" style="width: 10%;">Total Cost</th>
                    <th class="border p-2"></th>
                    <th class="border p-2"></th>
                    <th class="border p-2"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items_page2 as $item)
                    <tr>
                        <td class="border p-2 text-center">1</td>
                        <td class="border p-2 text-center">unit</td>
                        <td class="border p-2 text-right">20,000.00</td>
                        <td class="border p-2 text-right">20,000.00</td>
                        <td class="border p-2">
                            <strong>TIME ATTENDANCE TERMINAL</strong><br>
                            Brand/Model: Anviz C2 Pro<br>
                            Serial Number: {{ $item['serial'] }}
                        </td>
                        <td class="border p-2 text-center">{{ $item['inv_no'] }}</td>
                        <td class="border p-2"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-8 grid grid-cols-2 gap-16">
        <div>
            <p>Received by:</p>
            <div class="mt-10 border-t pt-2 text-center">
                <p>DICKSON, Julio Earl</p>
                <p class="text-xs">Signature over Printed Name</p>
            </div>
            <div class="mt-6 border-t pt-2 text-center">
                <p>&nbsp;</p>
                <p class="text-xs">Position</p>
            </div>
            <div class="mt-6 border-t pt-2 text-center">
                <p>&nbsp;</p>
                <p class="text-xs">Date</p>
            </div>
        </div>
        <div>
            <p>Received from:</p>
            <div class="mt-10 border-t pt-2 text-center">
                <p>Julio Earl Dicksen</p>
                <p class="text-xs">Signature over Printed Name</p>
            </div>
            <div class="mt-6 border-t pt-2 text-center">
                <p>Admin Officer III</p>
                <p class="text-xs">Position</p>
            </div>
            <div class="mt-6 border-t pt-2 text-center">
                <p>&nbsp;</p>
                <p class="text-xs">Date</p>
            </div>
        </div>
    </div>
    <div class="mt-4 flex justify-between text-xs">
        <span>G.A.M. for NGA, Volume II, Appendix 59, page 149</span>
        <span>Page 2 of 2</span>
    </div>
</div> 