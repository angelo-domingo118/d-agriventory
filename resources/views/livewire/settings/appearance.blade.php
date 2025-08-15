<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Customize the visual appearance of your account')">
        <!-- Appearance Settings Card -->
        <div class="group bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-lg shadow border border-stone-200/50 dark:border-stone-700/50 p-4 sm:p-5 hover:shadow-lg transition-all duration-300 backdrop-blur-sm">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0 p-2 bg-violet-100 dark:bg-violet-900/30 rounded-lg mr-3">
                    <x-flux::icon.swatch class="h-5 w-5 text-violet-600 dark:text-violet-400" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-stone-900 dark:text-stone-100">Theme Preferences</h3>
                    <p class="text-sm text-stone-600 dark:text-stone-400">Choose how the interface appears to you</p>
                </div>
            </div>

            <div class="space-y-4">
                <!-- Theme Selection -->
                <div class="space-y-3">
                    <h4 class="text-sm font-semibold text-stone-900 dark:text-stone-100 uppercase tracking-wider">Color Theme</h4>
                    
                    <flux:radio.group x-data variant="segmented" x-model="$flux.appearance" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <flux:radio value="light" icon="sun" class="bg-gradient-to-br from-amber-50 to-yellow-100 dark:from-amber-950/30 dark:to-yellow-950/30 border-amber-200 dark:border-amber-800/50 hover:shadow-lg transition-all duration-200">
                            <div class="text-center">
                                <div class="font-medium">{{ __('Light') }}</div>
                                <div class="text-xs text-stone-600 dark:text-stone-400 mt-1">Bright and clean</div>
                            </div>
                        </flux:radio>
                        
                        <flux:radio value="dark" icon="moon" class="bg-gradient-to-br from-slate-50 to-stone-100 dark:from-slate-950/30 dark:to-stone-950/30 border-slate-200 dark:border-slate-800/50 hover:shadow-lg transition-all duration-200">
                            <div class="text-center">
                                <div class="font-medium">{{ __('Dark') }}</div>
                                <div class="text-xs text-stone-600 dark:text-stone-400 mt-1">Easy on the eyes</div>
                            </div>
                        </flux:radio>
                        
                        <flux:radio value="system" icon="computer-desktop" class="bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-950/30 dark:to-indigo-950/30 border-blue-200 dark:border-blue-800/50 hover:shadow-lg transition-all duration-200">
                            <div class="text-center">
                                <div class="font-medium">{{ __('System') }}</div>
                                <div class="text-xs text-stone-600 dark:text-stone-400 mt-1">Matches device</div>
                            </div>
                        </flux:radio>
                    </flux:radio.group>
                </div>

                <!-- Theme Preview Cards -->
                <div class="space-y-3">
                    <h4 class="text-sm font-semibold text-stone-900 dark:text-stone-100 uppercase tracking-wider">Preview</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Light Theme Preview -->
                        <div class="bg-white rounded-lg border border-stone-200 p-4 shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <h5 class="font-medium text-stone-900">Light Theme</h5>
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            </div>
                            <div class="space-y-2">
                                <div class="h-2 bg-stone-200 rounded"></div>
                                <div class="h-2 bg-stone-200 rounded w-3/4"></div>
                                <div class="h-2 bg-blue-500 rounded w-1/2"></div>
                            </div>
                        </div>

                        <!-- Dark Theme Preview -->
                        <div class="bg-stone-900 rounded-lg border border-stone-700 p-4 shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <h5 class="font-medium text-stone-100">Dark Theme</h5>
                                <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                            </div>
                            <div class="space-y-2">
                                <div class="h-2 bg-stone-700 rounded"></div>
                                <div class="h-2 bg-stone-700 rounded w-3/4"></div>
                                <div class="h-2 bg-blue-400 rounded w-1/2"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Settings (Future Enhancement) -->
                <div class="bg-stone-50 dark:bg-stone-800/50 rounded-lg p-4 border border-stone-200 dark:border-stone-700/50">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <x-flux::icon.information-circle class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-stone-800 dark:text-stone-200 mb-1">Theme Information</h4>
                            <p class="text-sm text-stone-600 dark:text-stone-400">
                                Your theme preference is saved automatically and will be applied across all pages. 
                                The system option will match your device's current theme setting.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-settings.layout>
</section>
