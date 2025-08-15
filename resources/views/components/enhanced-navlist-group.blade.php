@props([
    'heading' => null,
    'icon' => null,
    'color' => 'stone',
    'collapsible' => true,
    'defaultExpanded' => true,
    'storageKey' => null,
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
    $sectionKey = $storageKey ?? 'sidebar-section-' . str($heading)->slug();
@endphp

<div 
    {{ $attributes->class('block mb-8') }}
    x-data="sidebarSection('{{ $sectionKey }}', {{ $defaultExpanded ? 'true' : 'false' }})"
>
    @if($heading)
        <div class="relative mb-3">
            @if($collapsible)
                <button 
                    @click="toggle()"
                    class="w-full flex items-center px-3 py-2 rounded-lg border {{ $bgColorClass }} backdrop-blur-sm hover:bg-opacity-80 cursor-pointer group"
                >
                    @if($icon)
                        <x-flux::icon :name="$icon" class="w-4 h-4 mr-2 opacity-80" />
                    @endif
                    <div class="flex-1 text-left">
                        <h4 class="text-xs font-bold uppercase tracking-widest leading-none opacity-90">{{ $heading }}</h4>
                    </div>
                    <x-flux::icon 
                        name="chevron-down" 
                        class="w-3 h-3 opacity-70"
                        x-bind:class="{ 'rotate-180': expanded, 'rotate-0': !expanded }"
                    />
                </button>
            @else
                <div class="flex items-center px-3 py-2 rounded-lg border {{ $bgColorClass }} backdrop-blur-sm">
                    @if($icon)
                        <x-flux::icon :name="$icon" class="w-4 h-4 mr-2 opacity-80" />
                    @endif
                    <div class="flex-1">
                        <h4 class="text-xs font-bold uppercase tracking-widest leading-none opacity-90">{{ $heading }}</h4>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div 
        class="overflow-hidden"
        x-show="expanded"
        x-cloak
    >
        <div class="space-y-1">
            {{ $slot }}
        </div>
    </div>
</div>
