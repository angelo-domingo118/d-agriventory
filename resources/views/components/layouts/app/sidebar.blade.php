<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-stone-800">
        <flux:sidebar sticky stashable class="w-80 border-e border-stone-200 bg-stone-50 dark:border-stone-700 dark:bg-stone-900 overflow-hidden">
            <div class="flex flex-col h-full overflow-hidden">
                <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

                <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                    <x-app-logo />
                </a>

                <div
                    class="relative flex-1 overflow-y-auto"
                    x-data="{
                        showIndicator: false,
                        checkScroll() {
                            const el = this.$el;
                            const tolerance = 1;
                            this.showIndicator = el.scrollHeight > el.clientHeight && (el.scrollHeight - el.clientHeight - el.scrollTop > tolerance);
                        }
                    }"
                    x-init="checkScroll()"
                    @scroll.debounce.50ms="checkScroll()"
                    @resize.window.debounce.150ms="checkScroll()"
                >
                    <flux:navlist class="grid gap-0.5 mt-4" variant="outline">
                        @if (auth()->check())
                            @if (auth()->user()->adminUser)
                                @include('partials.navigation.admin')
                            @elseif (auth()->user()->divisionInventoryManager)
                                @include('partials.navigation.inventory-manager')
                            @else
                                <!-- Default navigation for users without specific roles -->
                                <flux:navlist.item icon="house" href="{{ route('dashboard') }}" wire:navigate>
                                    {{ __('Dashboard') }}
                                </flux:navlist.item>
                            @endif
                        @endif
                    </flux:navlist>

                    <div
                        x-show="showIndicator"
                        x-transition
                        class="pointer-events-none fixed bottom-0 left-0 right-0 z-10 flex h-20 items-end justify-center bg-gradient-to-t from-stone-50 to-transparent pb-4 dark:from-stone-900"
                        style="width: inherit;"
                        aria-hidden="true"
                    >
                        <x-flux::icon name="chevron-down" class="h-6 w-6 animate-bounce text-stone-600 dark:text-stone-300" />
                    </div>
                </div>

                <!-- Desktop User Menu -->
                <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                    <flux:profile
                        :name="auth()->user()->name"
                        :initials="auth()->user()->initials()"
                        icon:trailing="chevrons-up-down"
                    />

                    <flux:menu class="w-[220px]">
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                        <span
                                            class="flex h-full w-full items-center justify-center rounded-lg bg-stone-200 text-black dark:bg-stone-700 dark:text-white"
                                        >
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    </span>

                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                        <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                        @if(auth()->user()->adminUser)
                                            <span class="text-xs inline-block mt-1 text-green-600 dark:text-green-500 font-semibold">{{ __('Administrator') }}</span>
                                        @elseif(auth()->user()->divisionInventoryManager)
                                            <span class="text-xs inline-block mt-1 text-green-600 dark:text-green-500 font-semibold">{{ __('Inventory Manager') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-stone-200 text-black dark:bg-stone-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                    @if(auth()->user()->adminUser)
                                        <span class="text-xs inline-block mt-1 text-green-600 dark:text-green-500 font-semibold">{{ __('Administrator') }}</span>
                                    @elseif(auth()->user()->divisionInventoryManager)
                                        <span class="text-xs inline-block mt-1 text-green-600 dark:text-green-500 font-semibold">{{ __('Inventory Manager') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
