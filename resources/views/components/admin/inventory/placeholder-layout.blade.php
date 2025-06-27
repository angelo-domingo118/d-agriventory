@props(['heading'])

<x-admin.layout :heading="$heading">
    <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-700 overflow-hidden shadow-sm rounded-lg">
        <div class="p-6">
            {{ $slot }}
        </div>
    </div>
</x-admin.layout> 