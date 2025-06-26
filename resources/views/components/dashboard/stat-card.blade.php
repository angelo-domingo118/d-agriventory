@props([
    'title',
    'value',
    'icon'
])

<div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-700 overflow-hidden shadow-sm rounded-lg">
    <div class="p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-accent rounded-md p-3">
                <div class="h-6 w-6 text-accent-foreground">
                    {{ $icon }}
                </div>
            </div>
            <div class="ml-4">
                <div class="text-sm font-medium text-stone-500 dark:text-stone-400 truncate">
                    {{ $title }}
                </div>
                <div class="text-lg font-semibold text-stone-900 dark:text-stone-100">
                    {{ $value }}
                </div>
            </div>
        </div>
    </div>
</div> 