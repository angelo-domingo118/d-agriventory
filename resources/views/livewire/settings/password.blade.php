<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component {
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <!-- Password Update Card -->
        <div class="group bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-lg shadow border border-stone-200/50 dark:border-stone-700/50 p-4 sm:p-5 hover:shadow-lg transition-all duration-300 backdrop-blur-sm">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0 p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg mr-3">
                    <x-flux::icon.lock-closed class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-stone-900 dark:text-stone-100">Security Settings</h3>
                    <p class="text-sm text-stone-600 dark:text-stone-400">Update your password to keep your account secure</p>
                </div>
            </div>

            <form wire:submit="updatePassword" class="space-y-4">
                <div class="space-y-2">
                    <flux:input
                        wire:model="current_password"
                        :label="__('Current Password')"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="transition-all duration-200 focus:ring-2 focus:ring-emerald-500/20"
                    />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <flux:input
                            wire:model="password"
                            :label="__('New Password')"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="transition-all duration-200 focus:ring-2 focus:ring-emerald-500/20"
                        />
                    </div>

                    <div class="space-y-2">
                        <flux:input
                            wire:model="password_confirmation"
                            :label="__('Confirm New Password')"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="transition-all duration-200 focus:ring-2 focus:ring-emerald-500/20"
                        />
                    </div>
                </div>

                <!-- Password Requirements Info -->
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800/50">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <x-flux::icon.information-circle class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">Password Requirements</h4>
                            <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                                <li class="flex items-center">
                                    <x-flux::icon.check class="h-3 w-3 text-blue-600 dark:text-blue-400 mr-2" />
                                    At least 8 characters long
                                </li>
                                <li class="flex items-center">
                                    <x-flux::icon.check class="h-3 w-3 text-blue-600 dark:text-blue-400 mr-2" />
                                    Include uppercase and lowercase letters
                                </li>
                                <li class="flex items-center">
                                    <x-flux::icon.check class="h-3 w-3 text-blue-600 dark:text-blue-400 mr-2" />
                                    Include at least one number
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-stone-200 dark:border-stone-700">
                    <x-action-message class="text-emerald-600 dark:text-emerald-400 font-medium" on="password-updated">
                        <div class="flex items-center">
                            <x-flux::icon.check-circle class="h-4 w-4 mr-2" />
                            {{ __('Password updated successfully!') }}
                        </div>
                    </x-action-message>

                    <flux:button 
                        variant="primary" 
                        type="submit" 
                        class="px-6 py-2 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 dark:from-emerald-500 dark:to-emerald-600 dark:hover:from-emerald-600 dark:hover:to-emerald-700 transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove>
                            <x-flux::icon.shield-check class="h-4 w-4 mr-2 inline" />
                            {{ __('Update Password') }}
                        </span>
                        <span wire:loading class="flex items-center">
                            <x-flux::icon.arrow-path class="h-4 w-4 mr-2 animate-spin" />
                            {{ __('Updating...') }}
                        </span>
                    </flux:button>
                </div>
            </form>
        </div>
    </x-settings.layout>
</section>
