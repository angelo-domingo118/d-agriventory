<div class="w-full overflow-x-auto bg-white p-8 font-serif text-sm text-stone-900 dark:bg-white">
    <style>
        .table-bordered,
        .table-bordered th,
        .table-bordered td {
            border: 1px solid black;
        }

        .table-sm td,
        .table-sm th {
            padding: 0.25rem;
        }

        .border-collapse {
            border-collapse: collapse;
        }

        .font-bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .align-top {
            vertical-align: top;
        }

        .w-full {
            width: 100%;
        }

        .mt-2 {
            margin-top: 0.5rem;
        }

        .mt-4 {
            margin-top: 1rem;
        }

        .mt-8 {
            margin-top: 2rem;
        }

        .mb-2 {
            margin-bottom: 0.5rem;
        }

        .p-2 {
            padding: 0.5rem;
        }

        .px-2 {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        .pt-12 {
            padding-top: 3rem;
        }
    </style>
    <div class="w-full">
        <!-- Government Header -->
        <div class="text-center mb-2">
            <div class="text-xs">Republic of the Philippines</div>
            <div class="font-bold">Department of Agriculture</div>
            <div class="text-xs">BPI Compound, Guisad, Baguio City</div>
        </div>

        <div class="text-center mt-4 mb-2">
            <h1 class="font-bold">REPORT OF SUPPLIES AND MATERIALS ISSUED</h1>
        </div>

        <table class="w-full">
            <tr>
                <td class="w-1/2">
                    <div>Entity Name: <span class="font-bold underline">{{ $reportData['entityName'] ?? 'Regional Field Office- Cordillera Administrative Region' }}</span></div>
                    <div>Fund Cluster: <span class="font-bold underline">{{ $reportData['fundCluster'] ?? '01' }}</span></div>
                </td>
                <td class="w-1/2">
                    <div>Serial No: <span class="ml-4 border-b border-black w-48 inline-block">{{ $reportData['rsmiNumber'] ?? '' }}</span></div>
                    <div class="mt-2">Date: <span class="ml-4 border-b border-black w-48 inline-block">{{ $reportData['date'] ?? date('Y-m-d') }}</span></div>
                </td>
            </tr>
        </table>

        <table class="table-bordered border-collapse w-full mt-4">
            <thead>
                <tr>
                    <td colspan="3" class="text-center align-middle">
                        To be filled up by the Supply and/or Property Division/Unit
                    </td>
                    <td colspan="5" class="text-center align-middle">
                        To be filled up by the Accounting Division/Unit
                    </td>
                </tr>
                <tr>
                    <th class="text-center">RIS No</th>
                    <th class="text-center">Responsibility Center Code</th>
                    <th class="text-center">Stock No</th>
                    <th class="text-center">Item</th>
                    <th class="text-center">Unit</th>
                    <th class="text-center">Quantity Issued</th>
                    <th class="text-center">Unit Cost</th>
                    <th class="text-center">Amount</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($reportData['items']) && count($reportData['items']) > 0)
                    @foreach($reportData['items'] as $item)
                        <tr>
                            <td class="text-center align-top"></td>
                            <td class="text-center align-top">{{ $item['responsibilityCenter'] }}</td>
                            <td class="text-center align-top">{{ $item['stockNo'] }}</td>
                            <td class="px-2 align-top">
                                <span class="font-bold">{{ $item['name'] }}</span><br>
                                @foreach($item['description'] as $line)
                                    @if(str_starts_with($line, '-'))
                                        <div class="pl-4">{{ $line }}</div>
                                    @elseif(str_starts_with($line, 'accessories'))
                                        <div>{{ $line }}</div>
                                    @else
                                        {{ $line }}<br>
                                    @endif
                                @endforeach
                            </td>
                            <td class="text-center align-top">{{ $item['unit'] }}</td>
                            <td class="text-center align-top">{{ $item['quantity'] }}</td>
                            <td class="text-right align-top">{{ number_format($item['unitCost'], 2) }}</td>
                            <td class="text-right align-top">{{ number_format($item['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="6" class="p-2 h-36 align-top"></td>
                        <td class="text-right align-bottom"></td>
                        <td class="text-right align-bottom font-bold">{{ number_format($reportData['totalAmount'] ?? 0, 2) }}</td>
                    </tr>
                @else
                    <tr>
                        <td class="text-center align-top"></td>
                        <td class="text-center align-top">2025 - 0007 - 27</td>
                        <td class="text-center align-top">10402050</td>
                        <td class="px-2 align-top">
                            <span class="font-bold">KNAPSACK SPRAYER</span><br>
                            Brand: Powerhouse<br>
                            18L, dual type battery & manual operated power<br>
                            <div class="pl-4">
                                accessories<br>
                                - one set protective nose and mouth mask<br>
                                - pair of eye googles<br>
                                - pair of spare gasket and 2 nozzles of different spray qualities<br>
                                - with pressure gauge<br>
                                - with two filters (mesh 16/sq.cm)<br>
                                basic tools for maintenance
                            </div>
                        </td>
                        <td class="text-center align-top">unit</td>
                        <td class="text-center align-top">10</td>
                        <td class="text-right align-top">10,750.00</td>
                        <td class="text-right align-top">107,500.00</td>
                    </tr>
                    <tr>
                        <td class="text-center align-top"></td>
                        <td class="text-center align-top">2025 - 0005 - 27</td>
                        <td class="text-center align-top">10402090</td>
                        <td class="px-2 align-top">
                            <span class="font-bold">WATER TANK</span><br>
                            polyetylene, 1000L capacity<br>
                            cylindrical shape<br>
                            with print of "DA-CAR OAP"
                        </td>
                        <td class="text-center align-top">piece</td>
                        <td class="text-center align-top">2</td>
                        <td class="text-right align-top">21,200.00</td>
                        <td class="text-right align-top">42,400.00</td>
                    </tr>
                    <tr>
                        <td colspan="6" class="p-2 h-36 align-top"></td>
                        <td class="text-right align-bottom"></td>
                        <td class="text-right align-bottom font-bold">149,900.00</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <table class="w-full mt-4">
            <tr>
                <td class="w-1/2 pr-2">
                    <table class="table-bordered border-collapse w-full">
                        <thead>
                            <tr>
                                <th colspan="2" class="text-center">Recapitulation</th>
                            </tr>
                            <tr>
                                <th class="w-1/2 text-center">Stock No.</th>
                                <th class="w-1/2 text-center">Qty.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="h-12">&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class="w-1/2 pl-2">
                    <table class="table-bordered border-collapse w-full">
                        <thead>
                            <tr>
                                <th colspan="3" class="text-center">Recapitulation</th>
                            </tr>
                            <tr>
                                <th class="w-1/3 text-center">Unit Cost</th>
                                <th class="w-1/3 text-center">Total Cost</th>
                                <th class="w-1/3 text-center">UACS Object Code</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="h-12">&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <table class="table-bordered border-collapse w-full mt-4">
            <tr>
                <td class="w-1/2 text-left align-top p-2">
                    I hereby certify to the correctness of the above information.
                </td>
                <td class="w-1/2 text-left align-top p-2">
                    Posted By:
                </td>
            </tr>
            <tr>
                <td class="w-1/2 text-center pt-12">
                    <span class="font-bold">{{ $reportData['certifiedBy']['name'] ?? 'JULIO EARL C. DICKSEN' }}</span><br>
                    <span>{{ $reportData['certifiedBy']['position'] ?? 'Administrative Officer III' }}</span>
                </td>
                <td class="w-1/2 text-center pt-12">
                    <span class="border-t border-black w-3/4 inline-block"></span><br>
                    <span>Accountant</span>
                    <span class="float-right mr-8">Date</span>
                </td>
            </tr>
        </table>

        <div class="flex justify-between mt-2">
            <span class="text-xs">GAM for NGA Volume II</span>
            <span class="text-xs">Page 1 of 1</span>
        </div>
        <div class="flex justify-between">
            <span class="text-xs">Appendix No. 64, page 159</span>
        </div>
    </div>
</div> 