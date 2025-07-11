@props([
    'ics',
    'densityClasses',
    'search' => '',
    'filterArticle' => '',
    'filterSerialNumber' => '',
])

<div wire:key="ics-card-{{ $ics->id }}" class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-black ring-opacity-5 dark:bg-stone-800 dark:ring-stone-700">
    <div class="{{ $densityClasses['card_padding'] }}">
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
                 @if ($densityClasses['show_secondary'] && $spec && ($spec->brand || $spec->model))
                    <p class="{{ $densityClasses['text_meta'] }} text-stone-500">
                        {!! \App\Helpers\TextHelper::highlight(collect([$spec->brand, $spec->model])->filter()->join(' / '), [$search, $filterArticle]) !!}
                    </p>
                @endif
            </div>
            <div class="ml-4 flex-shrink-0">
                <span class="inline-flex items-center rounded-full bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-500/20">{!! \App\Helpers\TextHelper::highlight($ics->ics_number, $search) !!}</span>
            </div>
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

        @if($densityClasses['show_tertiary'])
         <div class="mt-4">
            <p class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Serial Number(s) / Components</p>
            @php
                $hasAnyIdentification = $ics->itemBatches->some(fn($batch) => $batch->identification_data || $batch->components->isNotEmpty());
            @endphp

            @if($ics->itemBatches->isNotEmpty())
                @if($hasAnyIdentification)
                    <ul class="mt-1 space-y-2 {{ $densityClasses['text_meta'] }}">
                        @foreach($ics->itemBatches as $batch)
                            <li wire:key="card-batch-{{ $batch->id }}">
                                @if($ics->itemBatches->count() > 1)
                                    <p class="font-medium text-stone-600 dark:text-stone-300">Batch #{{ $loop->iteration }}:</p>
                                @endif
                                <div @if($ics->itemBatches->count() > 1) class="pl-4" @endif>
                                    @if($batch->components->isNotEmpty())
                                        <ul class="list-disc pl-5 text-stone-600 dark:text-stone-400">
                                            @foreach($batch->components as $component)
                                                <li>
                                                    <strong>{{ $component->component_type }}:</strong>
                                                    @if($component->serial_number)
                                                        {!! \App\Helpers\TextHelper::highlight($component->serial_number, [$search, $filterSerialNumber]) !!}
                                                    @else
                                                        <span class="italic text-stone-500">Not provided</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @elseif($batch->identification_data)
                                        <p class="text-stone-600 dark:text-stone-300">{!! \App\Helpers\TextHelper::highlight($batch->identification_data, [$search, $filterSerialNumber]) !!}</p>
                                    @else
                                        <p class="italic text-stone-500">No serial number recorded for this batch.</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="{{ $densityClasses['text_meta'] }} italic text-stone-500">No serial numbers recorded for any batches.</p>
                @endif
            @else
                <p class="{{ $densityClasses['text_meta'] }} italic text-stone-500">No item serials recorded.</p>
            @endif
        </div>

        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 {{ $densityClasses['text_base'] }}">
            <div>
                <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Quantity</dt>
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
                <dd class="font-medium text-stone-800 dark:text-stone-200">{{ $ics->estimated_useful_life }} yrs</dd>
            </div>
            @if($densityClasses['show_secondary'])
            <div class="col-span-2">
                <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Supplier</dt>
                <dd class="font-medium text-stone-800 dark:text-stone-200">{!! \App\Helpers\TextHelper::highlight($ics->contractItem->contract->supplier->name ?? 'Supplier Not Set', $search) !!}</dd>
            </div>
            @endif
        </dl>
        @endif
    </div>
    <div class="border-t border-stone-200 bg-stone-50 {{ $densityClasses['card_footer_padding'] }} dark:border-stone-700 dark:bg-stone-800/50">
         <div class="flex items-center justify-end gap-x-2">
            <a href="{{ route('admin.main.reports.index', ['reportType' => 'ics', 'reportFormat' => 'by_number', 'ics_number' => $ics->ics_number]) }}" 
                class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" 
                wire:navigate>
                <x-flux::icon.chart-bar class="mr-1 h-4 w-4" />
                Report
            </a>
            <a href="{{ route('admin.inventory.ics.show', $ics) }}" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                View
            </a>
            <a href="{{ route('admin.inventory.ics.edit', $ics) }}" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                Edit
            </a>
        </div>
    </div>
</div> 