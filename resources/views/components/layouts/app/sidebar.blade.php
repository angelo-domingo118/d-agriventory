<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-stone-800">
        <flux:sidebar sticky stashable class="w-80 border-e border-stone-200/60 bg-gradient-to-b from-stone-50 to-stone-100/50 dark:border-stone-700/50 dark:bg-gradient-to-b dark:from-stone-900 dark:to-stone-950/80 overflow-hidden shadow-xl backdrop-blur-sm">
            <div class="flex flex-col h-full">
                <!-- Mobile Toggle -->
                <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

                <!-- Logo Area -->
                <div class="flex-shrink-0 border-b border-stone-200/30 dark:border-stone-700/30 pb-6 mb-6 px-4">
                    <a href="{{ route('dashboard') }}" class="logo-area flex items-center justify-center px-4 py-4 rounded-xl hover:bg-stone-100/50 dark:hover:bg-stone-800/30 transition-all duration-300 group w-full" wire:navigate>
                        <x-app-logo />
                    </a>
                </div>

                <!-- Navigation Area -->
                <div
                    class="flex-1 overflow-y-auto sidebar-nav px-3"
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
                    <flux:navlist class="grid gap-2" variant="outline">
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

                <!-- User Profile Area - Bottom -->
                <div class="flex-shrink-0 border-t border-stone-200/30 dark:border-stone-700/30 pt-4 pb-4 px-4">
                    <flux:dropdown class="w-full" position="top" align="start">
                        <div class="w-full">
                            <flux:profile
                                :name="auth()->user()->name"
                                :initials="auth()->user()->initials()"
                                icon:trailing="chevrons-up-down"
                                class="w-full px-3 py-2 rounded-lg hover:bg-stone-100/50 dark:hover:bg-stone-800/30 transition-all duration-200"
                            />
                        </div>

                        <flux:menu class="w-[260px]">
                            <flux:menu.radio.group>
                                <div class="p-3 text-sm font-normal border-b border-stone-200 dark:border-stone-700">
                                    <div class="flex items-center gap-3">
                                        <span class="relative flex h-10 w-10 shrink-0 overflow-hidden rounded-lg">
                                            <span
                                                class="flex h-full w-full items-center justify-center rounded-lg bg-stone-200 text-black dark:bg-stone-700 dark:text-white text-sm font-semibold"
                                            >
                                                {{ auth()->user()->initials() }}
                                            </span>
                                        </span>

                                        <div class="grid flex-1 text-start leading-tight">
                                            <span class="truncate font-semibold text-stone-900 dark:text-stone-100">{{ auth()->user()->name }}</span>
                                            <span class="truncate text-xs text-stone-500 dark:text-stone-400">{{ auth()->user()->email }}</span>
                                            @if(auth()->user()->adminUser)
                                                <span class="text-xs inline-flex items-center gap-1 mt-1 px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 font-semibold rounded-full">
                                                    <div class="w-1.5 h-1.5 bg-green-500 rounded-full"></div>
                                                    {{ __('Administrator') }}
                                                </span>
                                            @elseif(auth()->user()->divisionInventoryManager)
                                                <span class="text-xs inline-flex items-center gap-1 mt-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 font-semibold rounded-full">
                                                    <div class="w-1.5 h-1.5 bg-blue-500 rounded-full"></div>
                                                    {{ __('Inventory Manager') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </flux:menu.radio.group>

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

        @persist('toast')
            <flux:toast />
        @endpersist

        {{-- Register the tableResizer Alpine helper globally (no need to touch app.js) --}}
        @once
            <script>
                (function registerTableResizer() {
                    function define() {
                        if (!window.Alpine) return;
                        if (Alpine?.data?.tableResizer) return; // already registered

                        // Sidebar section state management
                        Alpine.data('sidebarSection', (storageKey, defaultExpanded = true) => ({
                            expanded: defaultExpanded,
                            storageKey: storageKey,
                            init() {
                                // Only check localStorage if we have a storage key
                                if (this.storageKey && localStorage.getItem(this.storageKey) !== null) {
                                    this.expanded = localStorage.getItem(this.storageKey) === 'true';
                                }
                                
                                // Make sure this doesn't re-initialize unnecessarily
                                this.$el._sidebarSectionInitialized = true;
                            },
                            toggle() {
                                this.expanded = !this.expanded;
                                if (this.storageKey) {
                                    localStorage.setItem(this.storageKey, this.expanded.toString());
                                }
                            }
                        }));

                        Alpine.data('tableSettings', (storageKey, defaults = {}) => ({
                            init() {
                                // Load settings from localStorage and apply them on next tick
                                this.$nextTick(() => {
                                    const stored = JSON.parse(localStorage.getItem(storageKey) || '{}');
                                    
                                    // Apply stored values to Livewire properties
                                    Object.keys(stored).forEach(key => {
                                        if (this.$wire && this.$wire[key] !== undefined) {
                                            this.$wire.set(key, stored[key]);
                                        }
                                    });
                                });
                            },
                            updateSetting(key, value) {
                                // Update Livewire property
                                if (this.$wire && this.$wire[key] !== undefined) {
                                    this.$wire.set(key, value);
                                }
                                
                                // Save to localStorage
                                const stored = JSON.parse(localStorage.getItem(storageKey) || '{}');
                                stored[key] = value;
                                localStorage.setItem(storageKey, JSON.stringify(stored));
                            }
                        }));

                        Alpine.data('tableResizer', (storageKey, defaultWidths) => ({
                            columnWidths: {},
                            resizingColumn: null,
                            startX: 0,
                            startWidth: 0,
                            init() {
                                const stored = JSON.parse(localStorage.getItem(storageKey) || '{}');
                                this.columnWidths = { ...defaultWidths, ...stored };

                                this.$root.addEventListener('reset-column-widths', () => {
                                    this.columnWidths = { ...defaultWidths };
                                    localStorage.removeItem(storageKey);
                                });
                            },
                            startResize(event, column) {
                                this.resizingColumn = column;
                                this.startX = event.clientX;
                                this.startWidth = this.columnWidths[column];
                                event.preventDefault();

                                const move = (e) => {
                                    if (!this.resizingColumn) return;
                                    const newW = this.startWidth + (e.clientX - this.startX);
                                    this.columnWidths[this.resizingColumn] = Math.max(40, newW);
                                };

                                const up = () => {
                                    this.resizingColumn = null;
                                    localStorage.setItem(storageKey, JSON.stringify(this.columnWidths));
                                    window.removeEventListener('mousemove', move);
                                    window.removeEventListener('mouseup', up);
                                };

                                window.addEventListener('mousemove', move);
                                window.addEventListener('mouseup', up);
                            }
                        }));
                    }

                    if (window.Alpine) {
                        define();
                    } else {
                        document.addEventListener('alpine:init', define);
                    }
                })();
            </script>
        @endonce
        @fluxScripts
    </body>
</html>
