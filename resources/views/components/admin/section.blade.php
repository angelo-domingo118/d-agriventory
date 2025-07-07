<div class="md:grid md:grid-cols-3 md:gap-6">
    <div class="md:col-span-1">
        <div class="px-4 sm:px-0">
            @if (isset($title))
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">{{ $title }}</h3>
            @endif

            @if (isset($description))
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ $description }}
                </p>
            @endif
        </div>
    </div>

    <div class="mt-5 md:col-span-2 md:mt-0">
        <div class="shadow sm:overflow-hidden sm:rounded-md">
            <div class="space-y-6 bg-white px-4 py-5 dark:bg-gray-800 sm:p-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</div> 