<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <header>
        <div class="sm:flex sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold leading-7 text-stone-900 dark:text-stone-100 sm:truncate">
                    {{ $heading ?? '' }}
                </h1>
                @if(isset($subheading))
                    <p class="mt-2 text-sm text-stone-500 dark:text-stone-400 max-w-2xl">
                        {{ $subheading }}
                    </p>
                @endif
            </div>
            @if(isset($header))
                <div class="mt-5 sm:mt-0 sm:ml-6 sm:flex-shrink-0 sm:flex sm:items-center">
                    {{ $header }}
                </div>
            @endif
        </div>
    </header>

    <main class="mt-8">
        {{ $slot }}
    </main>
</div> 