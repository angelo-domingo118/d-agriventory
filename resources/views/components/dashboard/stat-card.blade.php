@props([
    'title',
    'value',
    'subtitle' => null,
    'change' => null,
    'changeType' => null,
])

<div {{ $attributes->merge(['class' => 'relative p-4 rounded-lg shadow-sm bg-white dark:bg-stone-800 border border-stone-200/50 dark:border-stone-700/50 hover:shadow-md transition-shadow duration-200']) }}>
    <div class="flex items-start justify-between min-h-[4rem]">
        <div class="flex-1 min-w-0 pr-3">
            <h4 class="text-sm font-medium text-stone-500 dark:text-stone-400 truncate">{{ $title }}</h4>
            <p class="text-2xl lg:text-3xl font-bold mt-1 text-stone-900 dark:text-stone-100 leading-tight">{{ $value }}</p>
            @if($subtitle)
            <p class="text-xs text-stone-500 dark:text-stone-400 mt-1 truncate">{{ $subtitle }}</p>
            @endif
        </div>
        @if(isset($icon))
        <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center bg-stone-100/80 dark:bg-stone-700/50 rounded-lg">
            {{ $icon }}
        </div>
        @endif
    </div>
    @if($change)
        <div class="mt-3 pt-2 border-t border-stone-200/50 dark:border-stone-700/50">
            <p @class([
                'text-xs font-medium flex items-center gap-1',
                'text-green-600 dark:text-green-400' => $changeType === 'increase',
                'text-amber-600 dark:text-amber-400' => $changeType === 'decrease',
                'text-stone-500 dark:text-stone-400' => !$changeType
            ])>
                @if($changeType === 'increase')
                    <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                @elseif($changeType === 'decrease')
                    <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                @endif
                <span class="truncate">{{ $change }}</span>
            </p>
        </div>
    @endif
</div> 