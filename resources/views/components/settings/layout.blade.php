<div class="flex items-start max-md:flex-col gap-4 lg:gap-6">
    <!-- Settings Sidebar Navigation -->
    <aside class="w-full md:w-[280px] flex-shrink-0">
        <div class="bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-lg border border-stone-200/50 dark:border-stone-700/50 p-8 backdrop-blur-sm">
            <div class="mb-8 px-1">
                <h3 class="text-xs font-semibold text-stone-900 dark:text-stone-100 uppercase tracking-wider">{{ __('SETTINGS NAVIGATION') }}</h3>
                <p class="text-xs text-stone-600 dark:text-stone-400 mt-3">{{ __('Manage your account preferences') }}</p>
            </div>
            
            <flux:navlist class="space-y-5">
                <flux:navlist.item 
                    :href="route('settings.profile')" 
                    :current="request()->routeIs('settings.profile')" 
                    wire:navigate
                    class="px-5 py-3 rounded-lg transition-all duration-200 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:shadow-sm {{ request()->routeIs('settings.profile') ? 'bg-blue-100 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800/50 shadow-sm' : '' }}"
                >
                    <div class="flex items-center">
                        <x-flux::icon.user class="h-4 w-4 mr-3 text-blue-600 dark:text-blue-400 flex-shrink-0" />
                        <span class="text-sm font-medium">{{ __('Profile') }}</span>
                    </div>
                </flux:navlist.item>
                
                <flux:navlist.item 
                    :href="route('settings.password')" 
                    :current="request()->routeIs('settings.password')" 
                    wire:navigate
                    class="px-5 py-3 rounded-lg transition-all duration-200 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 hover:shadow-sm {{ request()->routeIs('settings.password') ? 'bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800/50 shadow-sm' : '' }}"
                >
                    <div class="flex items-center">
                        <x-flux::icon.lock-closed class="h-4 w-4 mr-3 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
                        <span class="text-sm font-medium">{{ __('Password') }}</span>
                    </div>
                </flux:navlist.item>
                
                <flux:navlist.item 
                    :href="route('settings.appearance')" 
                    :current="request()->routeIs('settings.appearance')" 
                    wire:navigate
                    class="px-5 py-3 rounded-lg transition-all duration-200 hover:bg-violet-50 dark:hover:bg-violet-900/20 hover:shadow-sm {{ request()->routeIs('settings.appearance') ? 'bg-violet-100 dark:bg-violet-900/30 border border-violet-200 dark:border-violet-800/50 shadow-sm' : '' }}"
                >
                    <div class="flex items-center">
                        <x-flux::icon.swatch class="h-4 w-4 mr-3 text-violet-600 dark:text-violet-400 flex-shrink-0" />
                        <span class="text-sm font-medium">{{ __('Appearance') }}</span>
                    </div>
                </flux:navlist.item>
            </flux:navlist>
        </div>
    </aside>

    <flux:separator class="md:hidden" />

    <!-- Compact Main Content Area -->
    <main class="flex-1 self-stretch max-md:pt-4">
        <!-- Compact Header -->
        <header class="mb-6">
            <div class="bg-gradient-to-r from-stone-50 to-white dark:from-stone-900 dark:to-stone-800 rounded-lg p-4 border border-stone-200/50 dark:border-stone-700/50 shadow-sm">
                <div class="flex items-center">
                    @php
                        $iconMap = [
                            'Profile' => 'user',
                            'Update password' => 'lock-closed', 
                            'Appearance' => 'swatch'
                        ];
                        $colorMap = [
                            'Profile' => 'blue',
                            'Update password' => 'emerald',
                            'Appearance' => 'violet'
                        ];
                        $icon = $iconMap[$heading ?? ''] ?? 'cog-6-tooth';
                        $color = $colorMap[$heading ?? ''] ?? 'stone';
                    @endphp
                    
                    <div class="flex-shrink-0 p-2 bg-{{ $color }}-100 dark:bg-{{ $color }}-900/30 rounded-lg mr-3">
                        <x-dynamic-component :component="'flux::icon.' . $icon" class="h-5 w-5 text-{{ $color }}-600 dark:text-{{ $color }}-400" />
                    </div>
                    
                    <div>
                        <h2 class="text-xl font-bold text-stone-900 dark:text-stone-100">
                            {{ $heading ?? '' }}
                        </h2>
                        @if (isset($subheading))
                            <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                                {{ $subheading ?? '' }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="space-y-6">
             {{ $slot }}
        </div>
    </main>
</div>
