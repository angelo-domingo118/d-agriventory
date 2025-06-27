@props(['title', 'description', 'href'])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-700 overflow-hidden shadow-sm rounded-lg p-6 flex flex-col']) }}>
    <div class="flex-grow">
        <h3 class="text-lg font-medium text-stone-900 dark:text-stone-100">{{ $title }}</h3>
        <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">{{ $description }}</p>
    </div>
    <div class="mt-4">
        <flux:button :href="$href" variant="ghost">
            {{ $slot }}
        </flux:button>
    </div>
</div> 