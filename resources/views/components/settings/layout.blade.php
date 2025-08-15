<div class="flex items-start max-md:flex-col gap-4 lg:gap-6">
    <!-- Settings Sidebar Navigation -->
    <aside class="w-full md:w-[280px] flex-shrink-0">
        <div class="bg-white dark:bg-stone-800 rounded-lg shadow ring-1 ring-black ring-opacity-5 dark:ring-stone-700 p-6">
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-stone-900 dark:text-stone-100 uppercase tracking-wider">{{ __('Navigation') }}</h3>
                <p class="text-sm text-stone-600 dark:text-stone-400 mt-1">{{ __('Account settings') }}</p>
            </div>
            
            <flux:navlist class="space-y-2">
                <flux:navlist.item 
                    :href="route('settings.profile')" 
                    :current="request()->routeIs('settings.profile')" 
                    wire:navigate
                    class="px-3 py-2 rounded-md text-sm transition-colors hover:bg-stone-50 dark:hover:bg-stone-700 {{ request()->routeIs('settings.profile') ? 'bg-stone-100 dark:bg-stone-700 text-stone-900 dark:text-stone-100' : 'text-stone-700 dark:text-stone-300' }}"
                >
                    <div class="flex items-center">
                        <x-flux::icon.user class="h-4 w-4 mr-3 flex-shrink-0" />
                        <span>{{ __('Profile') }}</span>
                    </div>
                </flux:navlist.item>
                
                <flux:navlist.item 
                    :href="route('settings.password')" 
                    :current="request()->routeIs('settings.password')" 
                    wire:navigate
                    class="px-3 py-2 rounded-md text-sm transition-colors hover:bg-stone-50 dark:hover:bg-stone-700 {{ request()->routeIs('settings.password') ? 'bg-stone-100 dark:bg-stone-700 text-stone-900 dark:text-stone-100' : 'text-stone-700 dark:text-stone-300' }}"
                >
                    <div class="flex items-center">
                        <x-flux::icon.lock-closed class="h-4 w-4 mr-3 flex-shrink-0" />
                        <span>{{ __('Password') }}</span>
                    </div>
                </flux:navlist.item>
                
                <flux:navlist.item 
                    :href="route('settings.appearance')" 
                    :current="request()->routeIs('settings.appearance')" 
                    wire:navigate
                    class="px-3 py-2 rounded-md text-sm transition-colors hover:bg-stone-50 dark:hover:bg-stone-700 {{ request()->routeIs('settings.appearance') ? 'bg-stone-100 dark:bg-stone-700 text-stone-900 dark:text-stone-100' : 'text-stone-700 dark:text-stone-300' }}"
                >
                    <div class="flex items-center">
                        <x-flux::icon.swatch class="h-4 w-4 mr-3 flex-shrink-0" />
                        <span>{{ __('Appearance') }}</span>
                    </div>
                </flux:navlist.item>
            </flux:navlist>
        </div>
    </aside>

    <flux:separator class="md:hidden" />

    <!-- Main Content Area -->
    <main class="flex-1 self-stretch max-md:pt-4">
        @if(isset($heading))
            <header class="mb-6">
                <div class="border-b border-stone-200 dark:border-stone-700 pb-4">
                    <h2 class="text-xl font-semibold text-stone-900 dark:text-stone-100">
                        {{ $heading }}
                    </h2>
                    @if (isset($subheading))
                        <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                            {{ $subheading }}
                        </p>
                    @endif
                </div>
            </header>
        @endif

        <!-- Content Area -->
        <div class="space-y-6">
             {{ $slot }}
        </div>
    </main>
</div>
