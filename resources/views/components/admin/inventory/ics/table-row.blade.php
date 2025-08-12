@props([
    'ics',
    'densityClasses',
    'search' => '',
    'filterArticle' => '',
    'filterSerialNumber' => '',
    'filterContract' => '',
    'filterRemarks' => '',
    'filterInventoryNumber' => '',
    'showIssuedTo' => true,
    'highlightedIcsId' => null,
])

<tr
    wire:key="ics-{{ $ics->id }}"
    class="hover:bg-stone-50 dark:hover:bg-stone-800/50 transition-colors duration-500 ease-in-out"
    x-data="{ highlighted: false }"
    x-init="
        if ({{ $highlightedIcsId ?? 'null' }} === {{ $ics->id }}) {
            setTimeout(() => { highlighted = true; }, 50);
            setTimeout(() => { highlighted = false; }, 5000);
        }
    "
    :class="{ 'bg-green-100 dark:bg-green-900/50': highlighted }"
>
    <td class="w-full max-w-md {{ $densityClasses['table_cell'] }} {{ $densityClasses['text_base'] }} sm:w-auto sm:max-w-none border-r border-stone-300 dark:border-stone-700">
        <div class="space-y-2">
            <div>
                @if ($ics->contractItem?->itemSpecification?->itemCatalog?->name)
                    <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($ics->contractItem->itemSpecification->itemCatalog->name, [$search, $filterArticle]) !!}</div>
                @else
                    <div class="font-semibold text-stone-900 dark:text-stone-100 italic">Item name not available</div>
                @endif
                @php $spec = $ics->contractItem?->itemSpecification; @endphp
            </div>

            <div class="mt-1 space-y-1 {{ $densityClasses['text_meta'] }}">
                @if ($densityClasses['show_secondary'] && $spec && ($spec->brand || $spec->model))
                    <div class="flex items-start gap-x-2">
                        <span class="font-semibold text-stone-500 dark:text-stone-400">Brand/Model:</span>
                        <span class="text-stone-600 dark:text-stone-300">
                            {!! \App\Helpers\TextHelper::highlight(collect([$spec->brand, $spec->model])->filter()->join(' '), [$search, $filterArticle]) !!}
                        </span>
                    </div>
                @endif

                @if ($densityClasses['show_tertiary'] && $spec?->detailed_specifications)
                     <div class="flex items-start gap-x-2">
                        <span class="font-semibold text-stone-500 dark:text-stone-400">Details:</span>
                        <p class="text-stone-600 dark:text-stone-300 break-words">
                            {!! \App\Helpers\TextHelper::highlight($spec->detailed_specifications, [$search, $filterArticle]) !!}
                        </p>
                    </div>
                @endif
            </div>

            @if($densityClasses['show_secondary'])
                <div class="{{ $densityClasses['text_meta'] }}">
                    <p class="font-semibold uppercase text-stone-500 dark:text-stone-400">Serial Number(s) / Components:</p>
                    @php
                        $hasAnyIdentification = $ics->itemBatches->some(fn($batch) => $batch->identification_data || $batch->components->isNotEmpty());
                    @endphp
                    @if($ics->itemBatches->isNotEmpty())
                        @if($hasAnyIdentification)
                            <div class="mt-1 space-y-2 pl-4">
                                @foreach($ics->itemBatches as $batch)
                                    <div wire:key="batch-{{ $batch->id }}">
                                        @if($ics->itemBatches->count() > 1)
                                            <p class="font-medium text-stone-600 dark:text-stone-300">Batch #{{ $loop->iteration }}:</p>
                                        @endif
                                        <div class="space-y-2 text-stone-600 dark:text-stone-400 @if($ics->itemBatches->count() > 1) pl-4 @endif">
                                            @if($batch->identification_data)
                                                <div>
                                                    <div class="font-semibold text-stone-700 dark:text-stone-300">SYSTEM UNIT</div>
                                                    <div class="ml-3 text-sm">
                                                        <span class="text-stone-500">Serial Number:</span>
                                                        <span>{!! \App\Helpers\TextHelper::highlight($batch->identification_data, [$search, $filterSerialNumber]) !!}</span>
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            @foreach($batch->components as $component)
                                                <div>
                                                    <div class="font-semibold text-stone-700 dark:text-stone-300">{{ strtoupper($component->component_type) }}</div>
                                                    @if($component->brand || $component->model)
                                                        <div class="ml-3 text-sm">
                                                            <span class="text-stone-500">Brand/Model:</span>
                                                            <span>{!! \App\Helpers\TextHelper::highlight(collect([$component->brand, $component->model])->filter()->join(' '), [$search, $filterArticle, $filterSerialNumber]) !!}</span>
                                                        </div>
                                                    @endif
                                                    @if($component->serial_number)
                                                        <div class="ml-3 text-sm">
                                                            <span class="text-stone-500">Serial Number:</span>
                                                            <span>{!! \App\Helpers\TextHelper::highlight($component->serial_number, [$search, $filterSerialNumber]) !!}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="italic text-stone-500 pl-4">No serial numbers recorded for any batches.</p>
                        @endif
                    @else
                        <p class="italic text-stone-500 pl-4">No item serials recorded.</p>
                    @endif
                </div>
            @endif
        </div>

        @if(!$densityClasses['show_secondary'])
            <div class="mt-1 text-stone-600 dark:text-stone-400 sm:hidden">
                <p>{!! \App\Helpers\TextHelper::highlight($ics->assignedEmployee?->name ?? 'Unassigned', $search) !!}</p>
                @if($ics->assignedEmployee)
                    <p>
                        @php $divisionName = $ics->assignedEmployee->division?->name; @endphp
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
        <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($ics->ics_number, $search) !!}</div>
        @if($densityClasses['show_secondary'])
        <div class="mt-1 space-y-1 text-stone-600 dark:text-stone-400">
             <div>
                 <span class="font-medium">Inventory No:</span>
                @if($ics->date_accepted)
                    @php $inventoryNumber = $ics->ics_type . '-' . $ics->ics_number . '-' . $ics->date_accepted->format('m-Y'); @endphp
                    {!! \App\Helpers\TextHelper::highlight($inventoryNumber, [$search, $filterInventoryNumber]) !!}
                @else
                    <span class="italic text-stone-500">Awaiting acceptance</span>
                @endif
             </div>
           <div><span class="font-medium">Type:</span> {{ $ics->ics_type }}</div>
           <div><span class="font-medium">Quantity per Batch:</span> {{ $ics->quantity }} {{ $ics->contractItem?->itemSpecification?->itemCatalog?->unit ?? 'unit' }}(s)</div>
           @if($densityClasses['show_secondary'])
                <div><span class="font-medium">Batches:</span> {{ $ics->itemBatches->count() }}</div>
                <div><span class="font-medium">Unit Cost:</span> ₱{{ number_format($ics->contractItem?->unit_price ?? 0, 2) }}</div>
                <div><span class="font-medium">Life:</span> 
                    @if($ics->estimated_useful_life && $ics->estimated_useful_life > 0)
                        {{ $ics->estimated_useful_life }} yrs
                    @else
                        <span class="italic text-stone-500">Not specified</span>
                    @endif
                </div>
           @endif
        </div>
        @endif
    </td>

    <td class="hidden {{ $densityClasses['table_cell_px'] }} align-top {{ $densityClasses['text_base'] }} lg:table-cell border-r border-stone-300 dark:border-stone-700">
        <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($ics->contractItem->contract->supplier->name ?? 'Supplier Not Set', $search) !!}</div>
        @if($densityClasses['show_secondary'])
        <div class="mt-1 space-y-1 text-stone-600 dark:text-stone-400">
            <div>
                <span class="font-medium">Contract/PO:</span>
                @if($ics->contractItem?->contract?->contract_po_ib_number)
                    {!! \App\Helpers\TextHelper::highlight($ics->contractItem->contract->contract_po_ib_number, [$search, $filterContract]) !!}
                @else
                    <span class="italic text-stone-500">Not available</span>
                @endif
            </div>
            @if($densityClasses['show_tertiary'])
                <div>
                    <span class="font-medium">Prepared:</span>
                    @if($ics->date_prepared)
                        {{ $ics->date_prepared->format('M d, Y') }}
                    @else
                        <span class="italic text-stone-500">Not set</span>
                    @endif
                </div>
                <div>
                    <span class="font-medium">Accepted:</span>
                    @if($ics->date_accepted)
                        {{ $ics->date_accepted->format('M d, Y') }}
                    @else
                        <span class="italic text-stone-500">Not set</span>
                    @endif
                </div>
            @endif
        </div>
        @endif
        @if($densityClasses['show_tertiary'] && $ics->remarks)
            <div class="mt-2 text-xs text-stone-500 italic">"{!! \App\Helpers\TextHelper::highlight($ics->remarks, [$search, $filterRemarks]) !!}"</div>
        @endif
    </td>

    @if($showIssuedTo)
    <td class="hidden {{ $densityClasses['table_cell_px'] }} align-top {{ $densityClasses['text_base'] }} sm:table-cell border-r border-stone-300 dark:border-stone-700">
        <div class="font-semibold text-stone-900 dark:text-stone-100">{!! \App\Helpers\TextHelper::highlight($ics->assignedEmployee?->name ?? 'Unassigned', $search) !!}</div>
         @if($densityClasses['show_secondary'] && $ics->assignedEmployee)
            @php $divisionName = $ics->assignedEmployee->division?->name; @endphp
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
            <a href="{{ route('admin.main.reports.index', ['reportType' => 'ics', 'reportFormat' => 'by_number', 'ics_number' => $ics->ics_number]) }}" 
                class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" 
                wire:navigate>
                <x-flux::icon.chart-bar class="mr-1 h-4 w-4" />
                Report<span class="sr-only">, {{ $ics->ics_number }}</span>
            </a>
            <a href="{{ route('admin.inventory.ics.show', $ics) }}" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                <x-flux::icon.eye class="mr-1 h-4 w-4" />
                View<span class="sr-only">, {{ $ics->ics_number }}</span>
            </a>
            <a href="{{ route('admin.inventory.ics.edit', $ics) }}" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                <x-flux::icon.pencil class="mr-1 h-4 w-4" />
                Edit<span class="sr-only">, {{ $ics->ics_number }}</span>
            </a>
        </div>
    </td>
</tr> 