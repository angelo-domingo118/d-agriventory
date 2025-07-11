@props([
    'par',
    'densityClasses',
    'search' => '',
    'filterArticle' => '',
    'filterSerialNumber' => '',
])

<div wire:key="par-card-{{ $par->id }}" class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-black ring-opacity-5 dark:bg-stone-800 dark:ring-stone-700">
    <div class="{{ $densityClasses['card_padding'] }}">
        <div class="flex items-start justify-between">
            <div class="max-w-xs">
                <p class="truncate {{ $densityClasses['text_base'] }} font-semibold text-stone-900 dark:text-stone-100">
                    @if ($par->contractItem?->itemSpecification?->itemCatalog?->name)
                        {!! \App\Helpers\TextHelper::highlight($par->contractItem->itemSpecification->itemCatalog->name, [$search, $filterArticle]) !!}
                    @else
                        <span class="italic">Item name not available</span>
                    @endif
                </p>
                 @php $spec = $par->contractItem?->itemSpecification; @endphp
                 @if ($densityClasses['show_secondary'] && $spec && ($spec->brand || $spec->model))
                    <p class="{{ $densityClasses['text_meta'] }} text-stone-500">
                        {!! \App\Helpers\TextHelper::highlight(collect([$spec->brand, $spec->model])->filter()->join(' / '), [$search, $filterArticle]) !!}
                    </p>
                @endif
            </div>
            <div class="ml-4 flex-shrink-0">
                <span class="inline-flex items-center rounded-full bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-500/20">{!! \App\Helpers\TextHelper::highlight($par->par_number, $search) !!}</span>
            </div>
        </div>

        <div class="mt-4">
            <p class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Issued To</p>
            <p class="{{ $densityClasses['text_base'] }} font-medium text-stone-800 dark:text-stone-200">{!! \App\Helpers\TextHelper::highlight($par->assignedEmployee?->name ?? 'Unassigned', $search) !!}</p>
            @if($densityClasses['show_secondary'])
                @if($par->assignedEmployee)
                    @php $divisionName = $par->assignedEmployee->division?->name; @endphp
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
            <p class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Serial Number(s) / ID Data</p>
             @if($par->itemBatches->isNotEmpty() && $par->itemBatches->pluck('identification_data')->filter()->isNotEmpty())
                <ul class="mt-1 space-y-2 {{ $densityClasses['text_meta'] }}">
                    @foreach($par->itemBatches as $batch)
                        @if($batch->identification_data)
                        <li wire:key="card-batch-{{ $batch->id }}" class="text-stone-600 dark:text-stone-300">
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

        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 {{ $densityClasses['text_base'] }}">
            <div>
                <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Quantity</dt>
                <dd class="font-medium text-stone-800 dark:text-stone-200">{{ $par->quantity }} {{ $par->contractItem?->itemSpecification?->itemCatalog?->unit ?? 'unit' }}(s)</dd>
            </div>
            <div>
                <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Batches</dt>
                <dd class="font-medium text-stone-800 dark:text-stone-200">{{ $par->itemBatches->count() }}</dd>
            </div>
            <div>
                <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Unit Cost</dt>
                <dd class="font-medium text-stone-800 dark:text-stone-200">₱{{ number_format($par->contractItem?->unit_price ?? 0, 2) }}</dd>
            </div>
            @if($densityClasses['show_secondary'])
            <div class="col-span-2">
                <dt class="{{ $densityClasses['text_meta'] }} font-bold uppercase text-stone-500 dark:text-stone-400">Supplier</dt>
                <dd class="font-medium text-stone-800 dark:text-stone-200">{!! \App\Helpers\TextHelper::highlight($par->contractItem->contract->supplier->name ?? 'Supplier Not Set', $search) !!}</dd>
            </div>
            @endif
        </dl>
    </div>
    <div class="border-t border-stone-200 bg-stone-50 {{ $densityClasses['card_footer_padding'] }} dark:border-stone-700 dark:bg-stone-800/50">
         <div class="flex items-center justify-end gap-x-2">
            <a href="{{ route('admin.inventory.par.show', $par) }}" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                View
            </a>
            <a href="{{ route('admin.inventory.par.edit', $par) }}" class="inline-flex items-center rounded-md border border-stone-300 bg-white px-2.5 py-1.5 {{ $densityClasses['text_base'] }} font-semibold text-stone-900 shadow-sm hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-30 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700/50" wire:navigate>
                Edit
            </a>
        </div>
    </div>
</div> 