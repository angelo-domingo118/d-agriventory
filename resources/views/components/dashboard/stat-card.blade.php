@props([
    'title',
    'value',
    'subtitle' => null,
    'change' => null,
    'changeType' => null,
])

<div {{ $attributes->merge(['class' => 'p-4 rounded-lg shadow-sm bg-stone-50/50 dark:bg-stone-900/50']) }}>
    <div class="flex items-start justify-between">
        <div>
            <h4 class="font-medium text-stone-500 dark:text-stone-400">{{ $title }}</h4>
            <p class="text-3xl font-bold mt-1 text-stone-900 dark:text-stone-100">{{ $value }}</p>
            @if($subtitle)
            <p class="text-sm text-stone-500 dark:text-stone-400">{{ $subtitle }}</p>
            @endif
        </div>
        @if(isset($icon))
        <div class="p-2 bg-white/10 rounded-lg">
            {{ $icon }}
        </div>
        @endif
    </div>
    @if($change)
        <p @class([
            'text-sm mt-2',
            'text-green-600 dark:text-green-400' => $changeType === 'increase',
            'text-amber-600 dark:text-amber-400' => $changeType === 'decrease',
        ])>
            {{ $change }}
        </p>
    @endif
</div> 