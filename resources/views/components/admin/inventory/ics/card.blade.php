@props([
    'ics',
    'densityClasses',
    'search' => '',
    'filterArticle' => '',
    'filterSerialNumber' => '',
    'highlightedIcsId' => null,
])

<div
    wire:key="ics-card-{{ $ics->id }}"
    class="flex flex-col overflow-hidden rounded-lg bg-white shadow ring-1 ring-black ring-opacity-5 dark:bg-stone-800 dark:ring-stone-700 transition-colors duration-500 ease-in-out"
    x-data="{ highlighted: false }"
    x-init="
        if ({{ $highlightedIcsId ?? 'null' }} === {{ $ics->id }}) {
            setTimeout(() => { highlighted = true; }, 50);
            setTimeout(() => { highlighted = false; }, 5000);
        }
    "
    :class="{ 'bg-green-100 dark:bg-green-900/50': highlighted }"
>
    <div class="{{ $densityClasses['card_padding'] }} flex-grow">
        <div class="flex items-start justify-between">
            <div class="max-w-xs">
                <p class="truncate {{ $densityClasses['text_base'] }} font-semibold text-stone-900 dark:text-stone-100">
                    @if ($ics->contractItem?->itemSpecification?->itemCatalog?->name)
                        {!! \App\Helpers\TextHelper::highlight($ics->contractItem->itemSpecification->itemCatalog->name, [$search, $filterArticle]) !!}
                    @else
                        <span class="italic">Item name not available</span>
                    @endif
                </p>
                @php $spec = $ics->contractItem?->itemSpecification; @endphp
            </div>
            <div class="ml-4 flex-shrink-0">
                <span class="inline-flex items-center rounded-full bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-500/20">{!! \App\Helpers\TextHelper::highlight($ics->ics_number, $search) !!}</span>
            </div>
        </div>

        <div class="mt-2 space-y-1 {{ $densityClasses['text_meta'] }}">
            @if ($densityClasses['show_secondary'] && $spec)
                <div class="flex items-start gap-x-2">
                    <span class="font-semibold text-stone-500 dark:text-stone-400">Brand/Model:</span>
                    <span class="text-stone-600 dark:text-stone-300">
                        @if($spec->brand || $spec->model)
                            {!! \App\Helpers\TextHelper::highlight(collect([$spec->brand, $spec->model])->filter()->join(' '), [$search, $filterArticle]) !!}
                        @else
                            <span class="italic text-stone-500">Not specified</span>
                        @endif
                    </span>
                </div>
            @endif

            @if ($densityClasses['show_tertiary'] && $spec)
                 <div class="flex items-start gap-x-2">
                    <span class="font-semibold text-stone-500 dark:text-stone-400">Details:</span>
                    <p class="text-stone-600 dark:text-stone-300 break-words">
                        @if($spec->detailed_specifications)
                            {!! \App\Helpers\TextHelper::highlight($spec->detailed_specifications, [$search, $filterArticle]) !!}
                        @else
                            <span class="italic text-stone-500">Not specified</span>
                        @endif
                    </p>
                </div>
            @endif
        </div>

        <div class="mt-4">
            <p class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Issued To</p>
            <p class="{{ $densityClasses['text_base'] }} font-medium text-stone-800 dark:text-stone-200">{!! \App\Helpers\TextHelper::highlight($ics->assignedEmployee?->name ?? 'Unassigned', $search) !!}</p>
            @if($densityClasses['show_secondary'])
                @if($ics->assignedEmployee)
                    @php $divisionName = $ics->assignedEmployee->division?->name; @endphp
                    <p class="{{ $densityClasses['text_base'] }} text-stone-600 dark:text-stone-400">
                        @if($divisionName)
                            {!! \App\Helpers\TextHelper::highlight($divisionName, $search) !!}
                        @else
                            <span class="italic text-stone-500">No division assigned</span>
                        @endif
                    </p>
                @endif
            @endif
        </div>

        <div class="mt-4">
            <p class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Identification Number(s) / Components</p>
            @php
                $hasAnyIdentification = $ics->itemBatches->some(fn($batch) => $batch->identification_data || $batch->components->isNotEmpty());
            @endphp

            @if($ics->itemBatches->isNotEmpty())
                @if($hasAnyIdentification)
                    <div class="mt-1 space-y-2 pl-4 {{ $densityClasses['text_meta'] }}">
                        @foreach($ics->itemBatches as $batch)
                            <div wire:key="card-batch-{{ $batch->id }}">
                                @if($ics->itemBatches->count() > 1)
                                    <p class="font-medium text-stone-600 dark:text-stone-300">Batch #{{ $loop->iteration }}:</p>
                                @endif
                                <div class="space-y-2 text-stone-600 dark:text-stone-400 @if($ics->itemBatches->count() > 1) pl-4 @endif">
                                    @if($batch->identification_data)
                                        <div>
                                            <div class="text-sm">
                                                <span>
                                                    {!! \App\Helpers\TextHelper::highlight($batch->identification_data, [$search, $filterSerialNumber]) !!}
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                    @foreach($batch->components as $component)
                                        <div>
                                            <div class="font-semibold text-stone-700 dark:text-stone-300">{{ strtoupper($component->component_type) }}</div>
                                            <div class="ml-3 text-sm">
                                                <span class="text-stone-500">Brand/Model:</span>
                                                <span>
                                                    @if($component->brand || $component->model)
                                                        {!! \App\Helpers\TextHelper::highlight(collect([$component->brand, $component->model])->filter()->join(' '), [$search, $filterSerialNumber]) !!}
                                                    @else
                                                        <span class="italic text-stone-500">Not specified</span>
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <span class="text-stone-500">Serial Number:</span>
                                                <span>
                                                    @if($component->serial_number)
                                                        {!! \App\Helpers\TextHelper::highlight($component->serial_number, [$search, $filterSerialNumber]) !!}
                                                    @else
                                                        <span class="italic text-stone-500">Not specified</span>
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="{{ $densityClasses['text_meta'] }} italic text-stone-500 pl-4">No identification data provided for any batches.</p>
                @endif
            @else
                <p class="{{ $densityClasses['text_meta'] }} italic text-stone-500 pl-4">No item batches recorded.</p>
            @endif
        </div>

        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 {{ $densityClasses['text_base'] }}">
            <div>
                <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Quantity per Batch</dt>
                <dd class="font-medium text-stone-800 dark:text-stone-200">{{ $ics->quantity }} {{ $ics->contractItem?->itemSpecification?->itemCatalog?->unit ?? 'unit' }}(s)</dd>
            </div>
            <div>
                <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Batches</dt>
                <dd class="font-medium text-stone-800 dark:text-stone-200">{{ $ics->itemBatches->count() }}</dd>
            </div>
            <div>
                <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Unit Cost</dt>
                <dd class="font-medium text-stone-800 dark:text-stone-200">₱{{ number_format($ics->contractItem?->unit_price ?? 0, 2) }}</dd>
            </div>
             <div>
                <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Life</dt>
                <dd class="font-medium text-stone-800 dark:text-stone-200">
                    @if($ics->estimated_useful_life && $ics->estimated_useful_life > 0)
                        {{ $ics->estimated_useful_life }} yrs
                    @else
                        <span class="italic text-stone-500">Not specified</span>
                    @endif
                </dd>
            </div>
            @if($densityClasses['show_secondary'])
            <div class="col-span-2">
                <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Supplier</dt>
                <dd class="font-medium text-stone-800 dark:text-stone-200">{!! \App\Helpers\TextHelper::highlight($ics->contractItem->contract->supplier->name ?? 'Supplier Not Set', $search) !!}</dd>
            </div>
            @endif
        </dl>
    </div>
    <div class="border-t border-stone-200 bg-stone-50 {{ $densityClasses['card_footer_padding'] }} dark:border-stone-700 dark:bg-stone-800/50">
         <div class="grid grid-cols-3 gap-x-2">
            <a href="{{ route('admin.main.reports.index', ['reportType' => 'ics', 'reportFormat' => 'by_number', 'ics_number' => $ics->ics_number]) }}" 
                class="flex w-full justify-center items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" 
                wire:navigate>
                <x-flux::icon.chart-bar class="mr-1 h-4 w-4" />
                Report
            </a>
            <a href="{{ route('admin.inventory.ics.show', $ics) }}" class="flex w-full justify-center items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                <x-flux::icon.eye class="mr-1 h-4 w-4" />
                View
            </a>
            <a href="{{ route('admin.inventory.ics.edit', $ics) }}" class="flex w-full justify-center items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                <x-flux::icon.pencil class="mr-1 h-4 w-4" />
                Edit
            </a>
        </div>
    </div>
</div> 