@props(['title', 'description', 'href', 'icon' => null])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-700 overflow-hidden shadow-sm rounded-lg p-6 flex flex-col']) }}>
    <div class="flex-grow">
        @if($icon)
            <div class="flex items-center space-x-3 mb-3">
                <div class="flex-shrink-0">
                    <div class="h-8 w-8 rounded-lg bg-primary-100 dark:bg-primary-900/20 flex items-center justify-center">
                        <x-dynamic-component :component="'flux::icon.' . $icon" class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                    </div>
                </div>
                <h3 class="text-lg font-medium text-stone-900 dark:text-stone-100">{{ $title }}</h3>
            </div>
        @else
            <h3 class="text-lg font-medium text-stone-900 dark:text-stone-100">{{ $title }}</h3>
        @endif
        <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">{{ $description }}</p>
    </div>
    <div class="mt-4">
        <flux:button :href="$href" variant="ghost">
            {{ $slot }}
        </flux:button>
    </div>
</div> 