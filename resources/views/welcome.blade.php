<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>D'Agriventory</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="bg-background dark:bg-stone-950 text-foreground flex p-6 lg:p-8 items-center flex-col min-h-screen">
        <header class="w-full max-w-5xl text-sm mb-6">
            <nav class="flex items-center justify-between gap-4">
                <a href="{{ url('/') }}" class="flex items-center gap-2 text-stone-900 dark:text-white">
                    <x-app-logo-icon class="size-8" />
                    <span class="font-semibold text-lg">D'Agriventory</span>
                </a>
                <div>
                    @if (Route::has('login'))
                        <div class="flex items-center gap-x-4">
                            @auth
                                <a
                                    href="{{ url('/dashboard') }}"
                                    class="text-sm font-semibold leading-6 text-stone-900 dark:text-white"
                                >
                                    Dashboard
                                </a>
                            @else
                                <flux:button :href="route('login')" variant="ghost">
                                    Log in
                                </flux:button>

                                {{-- @if (Route::has('register'))
                                    <flux:button :href="route('register')" variant="filled">
                                        Register
                                    </flux:button>
                                @endif --}}
                            @endauth
                        </div>
                    @endif
                </div>
            </nav>
        </header>
        <div class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
            <main class="w-full max-w-2xl mx-auto text-center">
                <h1 class="text-4xl font-bold tracking-tight text-stone-900 dark:text-white sm:text-6xl">
                    Welcome to D'Agriventory
                </h1>
                <p class="mt-6 text-base leading-7 text-stone-600 dark:text-stone-400 sm:text-lg sm:leading-8">
                    The modern inventory management system for the Department of Agriculture. Streamline your procurement, tracking, and auditing processes with an intuitive and powerful interface.
                </p>
            </main>
        </div>
    </body>
</html>
