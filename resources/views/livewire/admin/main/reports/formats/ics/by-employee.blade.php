{{-- List of ICS Issued to Employee --}}

@php
    /** @var \Illuminate\Support\Collection|array $items */
    $items = isset($items) && $items instanceof \Illuminate\Support\Collection ? $items : collect();
    $employeeName = $employeeName ?? ($items->first()?->assignedEmployee?->name ?? '');

    // Transform items to a simpler structure expected by the table
    $mapped = $items->map(function($ics) {
        return [
            'article' => $ics->contractItem?->itemSpecification?->itemCatalog?->name ?? '—',
            'desc' => collect([
                $ics->contractItem?->itemSpecification?->brand,
                $ics->contractItem?->itemSpecification?->model,
            ])->filter()->join(' '),
            'qty' => 1,
            'unit' => $ics->contractItem?->itemSpecification?->itemCatalog?->unit ?? 'unit',
            'unit_cost' => number_format($ics->contractItem?->unit_price ?? 0, 2),
            'inv_no' => $ics->ics_type . '-' . str_pad($ics->ics_number, 4, '0', STR_PAD_LEFT) . '-' . optional($ics->date_accepted)->format('m-Y'),
            'remarks' => $ics->remarks,
        ];
    });

    // If no dynamic items, fall back to placeholder
    if ($mapped->isEmpty()) {
        $mapped = collect([
            ['article' => 'TIME ATTENDANCE 1', 'desc' => 'Brand/Model: Anviz C2 Pro<br>Serial Number: 0750100024490026', 'qty' => 1, 'unit' => 'unit', 'unit_cost' => '20,000.00', 'inv_no' => 'SPHV- 0001 - 01 - 2025', 'remarks' => 'For Office use'],
            // ... (rest of placeholders remain)
        ]);
        $employeeName = $employeeName ?: 'DICKSON, Julio Earl';
    }

    // Paginate/chunk per 25 items (~fits a page). Adjust as needed.
    $pages = $mapped->chunk(25);
@endphp

@foreach($pages as $pageIndex => $pageItems)
<div class="report-page mx-auto w-[1024px] aspect-[1.414/1] bg-white p-6 text-stone-900 shadow-lg dark:bg-white">
    <div class="text-center">
        <h4 class="text-lg font-bold">LIST OF "I.C.S." ISSUED TO EMPLOYEE</h4>
    </div>

    <div class="mt-6 flex justify-between text-sm">
        <p><strong>Name of Employee:</strong> {{ $employeeName }}</p>
        <p><strong>Date printed:</strong> {{ now()->format('n/j/Y') }}</p>
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
                    <th class="border border-stone-800 p-2 text-center" style="width: 10%;">INVENTORY NUMBER</th>
                    <th class="border border-stone-800 p-2 text-center" style="width: 10%;">REMARKS</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pageItems as $item)
                    <tr>
                        <td class="border border-stone-800 p-1 align-top">{!! $item['article'] !!}</td>
                        <td class="border border-stone-800 p-1 align-top">{!! $item['desc'] !!}</td>
                        <td class="border border-stone-800 p-1 text-center align-top">{{ $item['qty'] }}</td>
                        <td class="border border-stone-800 p-1 text-center align-top">{{ $item['unit'] }}</td>
                        <td class="border border-stone-800 p-1 text-right align-top">{{ $item['unit_cost'] }}</td>
                        <td class="border border-stone-800 p-1 text-center align-top">{{ $item['inv_no'] }}</td>
                        <td class="border border-stone-800 p-1 align-top">{{ $item['remarks'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="absolute bottom-6 left-6 text-sm">
        Page {{ $pageIndex + 1 }} of {{ $pages->count() }}
    </div>
    <div class="absolute bottom-6 right-6 text-sm font-bold underline">
        {{ $employeeName }}
    </div>
</div>
@endforeach