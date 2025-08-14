@props([
    'heading' => null,
    'icon' => null,
    'color' => 'stone',
])

@php
    $colorClasses = [
        'stone' => 'text-stone-600 dark:text-stone-300 bg-stone-100/50 dark:bg-stone-800/30 border-stone-200/60 dark:border-stone-700/40',
        'blue' => 'text-blue-600 dark:text-blue-400 bg-blue-50/80 dark:bg-blue-900/20 border-blue-200/60 dark:border-blue-700/40',
        'purple' => 'text-purple-600 dark:text-purple-400 bg-purple-50/80 dark:bg-purple-900/20 border-purple-200/60 dark:border-purple-700/40',
        'red' => 'text-red-600 dark:text-red-400 bg-red-50/80 dark:bg-red-900/20 border-red-200/60 dark:border-red-700/40',
        'green' => 'text-green-600 dark:text-green-400 bg-green-50/80 dark:bg-green-900/20 border-green-200/60 dark:border-green-700/40',
    ];
    
    $bgColorClass = $colorClasses[$color] ?? $colorClasses['stone'];
@endphp

<div {{ $attributes->class('block space-y-3 mb-8') }}>
    @if($heading)
        <div class="relative">
            <div class="flex items-center px-3 py-2 rounded-lg border {{ $bgColorClass }} backdrop-blur-sm">
                @if($icon)
                    <x-flux::icon :name="$icon" class="w-4 h-4 mr-2 opacity-80" />
                @endif
                <div class="flex-1">
                    <h4 class="text-xs font-bold uppercase tracking-widest leading-none opacity-90">{{ $heading }}</h4>
                </div>
                <div class="w-2 h-2 rounded-full bg-current opacity-60 animate-pulse"></div>
            </div>
        </div>
    @endif

    <div class="space-y-1">
        {{ $slot }}
    </div>
</div>
