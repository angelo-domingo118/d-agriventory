@props([
    'par',
    'densityClasses',
    'columns',
    'search' => '',
    'filterArticle' => '',
    'filterSerialNumber' => '',
    'filterContract' => '',
    'filterRemarks' => '',
    'filterInventoryNumber' => '',
    'filterAreaCode' => '',
    'filterBuildingCode' => '',
    'filterAccountCode' => '',
    'showIssuedTo' => true,
    'highlightedParId' => null,
])

<tr
    wire:key="par-{{ $par->id }}"
    class="hover:bg-stone-50 dark:hover:bg-stone-800/50 transition-colors duration-500 ease-in-out"
    x-data="{ highlighted: false }"
    x-init="
        if ({{ $highlightedParId ?? 'null' }} === {{ $par->id }}) {
            setTimeout(() => { highlighted = true; }, 50);
            setTimeout(() => { highlighted = false; }, 5000);
        }
    "
    :class="{ 'bg-green-100 dark:bg-green-900/50': highlighted }"
>
    <td class="w-full max-w-md {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} sm:w-auto sm:max-w-none border-r border-stone-300 dark:border-stone-700">
        <div class="space-y-2">
            <div>
                @if ($par->contractItem?->itemSpecification?->itemCatalog?->name)
                    <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($par->contractItem->itemSpecification->itemCatalog->name, [$search, $filterArticle]) !!}</div>
                @else
                    <div class="font-semibold text-stone-900 dark:text-stone-100 italic">Item name not available</div>
                @endif
                @php $spec = $par->contractItem?->itemSpecification; @endphp
                @if ($columns['brand_model'] && $densityClasses['show_secondary'] && $spec)
                    @if ($spec->brand || $spec->model)
                        <div class="{{ $densityClasses['text_meta'] }} text-stone-500">
                            {!! \App\Helpers\TextHelper::highlight(collect([$spec->brand, $spec->model])->filter()->join(' / '), [$search, $filterArticle]) !!}
                        </div>
                    @endif
                @endif
            </div>

            @if ($columns['specifications'] && $densityClasses['show_tertiary'] && $spec?->detailed_specifications)
                <div class="{{ $densityClasses['text_meta'] }}">
                    <div class="grid grid-cols-[auto_1fr] gap-x-2">
                        <span class="font-semibold uppercase text-stone-500 dark:text-stone-400">Description:</span>
                        <p class="text-stone-600 dark:text-stone-300 break-words">
                            {!! \App\Helpers\TextHelper::highlight($spec->detailed_specifications, [$search, $filterArticle]) !!}
                        </p>
                    </div>
                </div>
            @endif

            @if($columns['serials'])
                <div class="{{ $densityClasses['text_meta'] }}">
                    <p class="font-semibold uppercase text-stone-500 dark:text-stone-400">Identification Number(s) / Components:</p>
                    @if($par->itemBatches->isNotEmpty() && $par->itemBatches->pluck('identification_data')->filter()->isNotEmpty())
                        <ul class="mt-1 space-y-2">
                            @foreach($par->itemBatches as $batch)
                                @if($batch->identification_data)
                                <li wire:key="batch-{{ $batch->id }}" class="text-stone-600 dark:text-stone-300">
                                    @if($par->itemBatches->count() > 1) <span class="font-medium">Batch #{{$loop->iteration}}:</span> @endif
                                    {!! \App\Helpers\TextHelper::highlight($batch->identification_data, [$search, $filterSerialNumber]) !!}
                                </li>
                                @endif
                            @endforeach
                        </ul>
                    @else
                        <p class="{{ $densityClasses['text_meta'] }} italic text-stone-500">No identification data recorded.</p>
                    @endif
                </div>
            @endif
        </div>

        @if(!$densityClasses['show_secondary'])
            <div class="mt-1 text-stone-600 dark:text-stone-400 sm:hidden">
                <p>{!! \App\Helpers\TextHelper::highlight($par->assignedEmployee?->name ?? 'Unassigned', $search) !!}</p>
                @if($par->assignedEmployee)
                    <p>
                        @php $divisionName = $par->assignedEmployee->division?->name; @endphp
                        @if($divisionName)
                            {!! \App\Helpers\TextHelper::highlight($divisionName, $search) !!}
                        @else
                            <span class="italic text-stone-500">No division assigned</span>
                        @endif
                    </p>
                @endif
            </div>
        @endif
    </td>

    <td class="{{ $densityClasses['table_cell_px'] }} align-top {{ $densityClasses['text_base'] }} border-r border-stone-300 dark:border-stone-700">
        <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($par->par_number, $search) !!}</div>
        @if($densityClasses['show_secondary'])
        <div class="mt-1 space-y-1 text-stone-600 dark:text-stone-400">
             <div>
                 <span class="font-medium">Inventory No:</span>
                @if($par->date_acquired && $par->inventory_code)
                    @php $inventoryNumber = $par->inventory_code . '-' . $par->par_number . '-' . $par->date_acquired->format('m-Y'); @endphp
                    {!! \App\Helpers\TextHelper::highlight($inventoryNumber, [$search, $filterInventoryNumber]) !!}
                @else
                    <span class="italic text-stone-500">Awaiting acceptance</span>
                @endif
             </div>
           @if($columns['quantity'])
                <div><span class="font-medium">Quantity:</span> {{ $par->quantity }} {{ $par->contractItem?->itemSpecification?->itemCatalog?->unit ?? 'unit' }}(s)</div>
           @endif
           @if($columns['unit_cost'])<div><span class="font-medium">Unit Cost:</span> ₱{{ number_format($par->contractItem?->unit_price ?? 0, 2) }}</div>@endif
           @if($columns['codes'] && $densityClasses['show_tertiary'])
                <div><span class="font-medium">Area Code:</span> {!! $par->area_code ? \App\Helpers\TextHelper::highlight($par->area_code, [$search, $filterAreaCode]) : '<span class="italic text-stone-500">Not recorded</span>' !!}</div>
                <div><span class="font-medium">Building Code:</span> {!! $par->building_code ? \App\Helpers\TextHelper::highlight($par->building_code, [$search, $filterBuildingCode]) : '<span class="italic text-stone-500">Not recorded</span>' !!}</div>
                <div><span class="font-medium">Account Code:</span> {!! $par->account_code ? \App\Helpers\TextHelper::highlight($par->account_code, [$search, $filterAccountCode]) : '<span class="italic text-stone-500">Not recorded</span>' !!}</div>
           @endif
        </div>
        @endif
    </td>

    <td class="hidden {{ $densityClasses['table_cell_px'] }} align-top {{ $densityClasses['text_base'] }} lg:table-cell border-r border-stone-300 dark:border-stone-700">
        <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($par->contractItem->contract->supplier->name ?? 'Supplier Not Set', $search) !!}</div>
        @if($densityClasses['show_secondary'])
        <div class="mt-1 space-y-1 text-stone-600 dark:text-stone-400">
            @if($columns['contract'])
                <div>
                    <span class="font-medium">Contract/PO:</span>
                    @if($par->contractItem?->contract?->contract_po_ib_number)
                        {!! \App\Helpers\TextHelper::highlight($par->contractItem->contract->contract_po_ib_number, [$search, $filterContract]) !!}
                    @else
                        <span class="italic text-stone-500">Not available</span>
                    @endif
                </div>
            @endif
            @if($columns['dates'] && $densityClasses['show_tertiary'])
                <div>
                    <span class="font-medium">Prepared:</span>
                    @if($par->date_prepared)
                        {{ $par->date_prepared->format('M d, Y') }}
                    @else
                        <span class="italic text-stone-500">Not set</span>
                    @endif
                </div>
                <div>
                    <span class="font-medium">Accepted:</span>
                    @if($par->date_accepted)
                        {{ $par->date_accepted->format('M d, Y') }}
                    @else
                        <span class="italic text-stone-500">Not set</span>
                    @endif
                </div>
            @endif
        </div>
        @endif
        @if($columns['remarks'] && $par->remarks)
            <div class="mt-2 text-xs text-stone-500 italic">"{!! \App\Helpers\TextHelper::highlight($par->remarks, [$search, $filterRemarks]) !!}"</div>
        @endif
    </td>

    @if($showIssuedTo)
    <td class="hidden {{ $densityClasses['table_cell_px'] }} align-top {{ $densityClasses['text_base'] }} sm:table-cell border-r border-stone-300 dark:border-stone-700">
        <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($par->assignedEmployee?->name ?? 'Unassigned', $search) !!}</div>
         @if($columns['division'] && $par->assignedEmployee)
            @php $divisionName = $par->assignedEmployee->division?->name; @endphp
            <div class="text-stone-600 dark:text-stone-400">
                @if($divisionName)
                    {!! \App\Helpers\TextHelper::highlight($divisionName, $search) !!}
                @else
                    <span class="italic text-stone-500">No division assigned</span>
                @endif
            </div>
         @endif
    </td>
    @endif

    <td class="{{ $densityClasses['table_cell'] }} pl-3 pr-4 text-right align-top {{ $densityClasses['text_base'] }} font-medium sm:pr-6">
        <div class="flex items-center justify-end gap-x-2">
            <a href="{{ route('admin.inventory.par.show', $par) }}" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                <x-flux::icon.eye class="mr-1.5 h-4 w-4" />
                View<span class="sr-only">, {{ $par->par_number }}</span>
            </a>
            <a href="{{ route('admin.inventory.par.edit', $par) }}" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                <x-flux::icon.edit class="mr-1.5 h-4 w-4" />
                Edit<span class="sr-only">, {{ $par->par_number }}</span>
            </a>
        </div>
    </td>
</tr> 