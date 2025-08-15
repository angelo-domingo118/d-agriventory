<?php

use Livewire\Volt\Component;

new class extends Component {
    public string $theme = 'system';

    public function mount()
    {
        // Get current theme preference from session or user settings
        $this->theme = session('theme', 'system');
    }

    public function updatedTheme()
    {
        // Save theme preference to session
        session(['theme' => $this->theme]);
        
        // Dispatch event to update the theme
        $this->dispatch('theme-changed', theme: $this->theme);
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Customize the visual appearance of your account')">
        <!-- Theme Preferences Card -->
        <div class="bg-white dark:bg-stone-800 border border-stone-200/50 dark:border-stone-700/50 rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow duration-200" x-data="{ theme: @entangle('theme').live }">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-stone-900 dark:text-stone-100 mb-1">Theme Preferences</h2>
                <p class="text-sm text-stone-600 dark:text-stone-400">Choose how the interface appears to you</p>
            </div>

            <!-- Theme Selection -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-stone-700 dark:text-stone-300 mb-2">Color Theme</label>
                <p class="text-sm text-stone-600 dark:text-stone-400 mb-4">Select your preferred theme appearance</p>
                
                <div class="space-y-3">
                    <!-- Light Theme Option -->
                    <label class="relative block cursor-pointer">
                        <input type="radio" wire:model.live="theme" value="light" class="sr-only peer">
                        <div class="flex items-center space-x-4 p-4 rounded-lg border border-stone-200 dark:border-stone-700 hover:border-amber-300 dark:hover:border-amber-400 transition-all peer-checked:border-amber-500 peer-checked:bg-amber-50 dark:peer-checked:bg-amber-900/20 peer-checked:shadow-md">
                            <div class="flex-shrink-0">
                                <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                                    <x-flux::icon.sun class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="font-medium text-stone-900 dark:text-stone-100">Light Theme</div>
                                <div class="text-sm text-stone-600 dark:text-stone-400">Bright and clean interface</div>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="w-5 h-5 border-2 border-stone-300 dark:border-stone-600 rounded-full peer-checked:border-amber-500 peer-checked:bg-amber-500 flex items-center justify-center">
                                    <div class="w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                </div>
                            </div>
                        </div>
                    </label>

                    <!-- Dark Theme Option -->
                    <label class="relative block cursor-pointer">
                        <input type="radio" wire:model.live="theme" value="dark" class="sr-only peer">
                        <div class="flex items-center space-x-4 p-4 rounded-lg border border-stone-200 dark:border-stone-700 hover:border-slate-400 dark:hover:border-slate-500 transition-all peer-checked:border-slate-500 peer-checked:bg-slate-50 dark:peer-checked:bg-slate-900/20 peer-checked:shadow-md">
                            <div class="flex-shrink-0">
                                <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-lg">
                                    <x-flux::icon.moon class="w-5 h-5 text-slate-600 dark:text-slate-400" />
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="font-medium text-stone-900 dark:text-stone-100">Dark Theme</div>
                                <div class="text-sm text-stone-600 dark:text-stone-400">Easy on the eyes</div>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="w-5 h-5 border-2 border-stone-300 dark:border-stone-600 rounded-full peer-checked:border-slate-500 peer-checked:bg-slate-500 flex items-center justify-center">
                                    <div class="w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                </div>
                            </div>
                        </div>
                    </label>

                    <!-- System Theme Option -->
                    <label class="relative block cursor-pointer">
                        <input type="radio" wire:model.live="theme" value="system" class="sr-only peer">
                        <div class="flex items-center space-x-4 p-4 rounded-lg border border-stone-200 dark:border-stone-700 hover:border-blue-300 dark:hover:border-blue-400 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 peer-checked:shadow-md">
                            <div class="flex-shrink-0">
                                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                    <x-flux::icon.computer-desktop class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="font-medium text-stone-900 dark:text-stone-100">System Theme</div>
                                <div class="text-sm text-stone-600 dark:text-stone-400">Matches your device setting</div>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="w-5 h-5 border-2 border-stone-300 dark:border-stone-600 rounded-full peer-checked:border-blue-500 peer-checked:bg-blue-500 flex items-center justify-center">
                                    <div class="w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Live Theme Preview -->
            <div class="mb-6" x-show="theme !== 'system'" x-transition>
                <label class="block text-sm font-semibold text-stone-700 dark:text-stone-300 mb-3">Preview</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Light Theme Preview -->
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm transition-opacity" 
                         :class="theme === 'light' ? 'opacity-100 ring-2 ring-amber-300' : 'opacity-60'">
                        <div class="flex items-center justify-between px-3 py-2 bg-gray-50 border-b border-gray-200">
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 bg-green-100 rounded-lg flex items-center justify-center">
                                    <div class="w-3 h-3 bg-green-600 rounded"></div>
                                </div>
                                <span class="text-xs font-medium text-gray-700">D'Agriventory</span>
                            </div>
                            <div class="w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center">
                                <span class="text-xs font-bold text-gray-600">You</span>
                            </div>
                        </div>
                        
                        <div class="flex">
                            <div class="w-16 bg-gradient-to-b from-gray-50 to-gray-100/50 border-r border-gray-200/60 p-2">
                                <div class="space-y-1">
                                    <div class="w-3 h-3 bg-gray-300 rounded"></div>
                                    <div class="w-3 h-3 bg-green-600 rounded"></div>
                                    <div class="w-3 h-3 bg-gray-300 rounded"></div>
                                </div>
                            </div>
                            
                            <div class="flex-1 p-3">
                                <h6 class="text-xs font-semibold text-gray-900 mb-2">Light Theme</h6>
                                <div class="grid grid-cols-2 gap-2 mb-2">
                                    <div class="bg-white border border-gray-200/50 rounded p-2">
                                        <div class="w-2 h-2 bg-gray-200 rounded mb-1"></div>
                                        <div class="w-4 h-1 bg-gray-300 rounded"></div>
                                    </div>
                                    <div class="bg-white border border-gray-200/50 rounded p-2">
                                        <div class="w-2 h-2 bg-green-200 rounded mb-1"></div>
                                        <div class="w-4 h-1 bg-green-300 rounded"></div>
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <div class="h-1 bg-gray-200 rounded"></div>
                                    <div class="h-1 bg-gray-200 rounded w-2/3"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dark Theme Preview -->
                    <div class="bg-gray-900 rounded-lg border border-gray-700 overflow-hidden shadow-sm transition-opacity" 
                         :class="theme === 'dark' ? 'opacity-100 ring-2 ring-slate-400' : 'opacity-60'">
                        <div class="flex items-center justify-between px-3 py-2 bg-gray-800 border-b border-gray-700">
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 bg-green-900/30 rounded-lg flex items-center justify-center">
                                    <div class="w-3 h-3 bg-green-400 rounded"></div>
                                </div>
                                <span class="text-xs font-medium text-gray-300">D'Agriventory</span>
                            </div>
                            <div class="w-6 h-6 bg-gray-700 rounded-full flex items-center justify-center">
                                <span class="text-xs font-bold text-gray-300">You</span>
                            </div>
                        </div>
                        
                        <div class="flex">
                            <div class="w-16 bg-gradient-to-b from-gray-900 to-gray-950/80 border-r border-gray-700/50 p-2">
                                <div class="space-y-1">
                                    <div class="w-3 h-3 bg-gray-700 rounded"></div>
                                    <div class="w-3 h-3 bg-green-600 rounded"></div>
                                    <div class="w-3 h-3 bg-gray-700 rounded"></div>
                                </div>
                            </div>
                            
                            <div class="flex-1 p-3">
                                <h6 class="text-xs font-semibold text-gray-100 mb-2">Dark Theme</h6>
                                <div class="grid grid-cols-2 gap-2 mb-2">
                                    <div class="bg-gray-800 border border-gray-700/50 rounded p-2">
                                        <div class="w-2 h-2 bg-gray-700 rounded mb-1"></div>
                                        <div class="w-4 h-1 bg-gray-600 rounded"></div>
                                    </div>
                                    <div class="bg-gray-800 border border-gray-700/50 rounded p-2">
                                        <div class="w-2 h-2 bg-green-700 rounded mb-1"></div>
                                        <div class="w-4 h-1 bg-green-600 rounded"></div>
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <div class="h-1 bg-gray-700 rounded"></div>
                                    <div class="h-1 bg-gray-700 rounded w-2/3"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Theme Information -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <x-flux::icon.information-circle class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            Your theme preference is saved automatically and applied across all pages. 
                            The system option matches your device's current theme setting.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </x-settings.layout>
</section>

<script>
    document.addEventListener('livewire:init', function () {
        // Listen for theme changes
        Livewire.on('theme-changed', (event) => {
            const theme = event.theme;
            
            // Apply theme immediately for preview
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else if (theme === 'light') {
                document.documentElement.classList.remove('dark');
            } else if (theme === 'system') {
                // Check system preference
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
            
            // Show a toast notification
            if (window.Livewire) {
                window.Livewire.dispatch('toast', {
                    message: `Theme changed to ${theme === 'system' ? 'system preference' : theme + ' mode'}`,
                    type: 'success'
                });
            }
        });
    });
</script>
