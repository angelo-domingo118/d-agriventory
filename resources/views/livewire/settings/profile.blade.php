<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $username = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->username = Auth::user()->username;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <!-- Profile Information Card -->
        <div class="group bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-lg shadow border border-stone-200/50 dark:border-stone-700/50 p-4 sm:p-5 hover:shadow-lg transition-all duration-300 backdrop-blur-sm">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0 p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg mr-3">
                    <x-flux::icon.user class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-stone-900 dark:text-stone-100">Personal Information</h3>
                    <p class="text-sm text-stone-600 dark:text-stone-400">Update your basic profile details</p>
                </div>
            </div>

            <form wire:submit="updateProfileInformation" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <flux:input 
                            wire:model="name" 
                            :label="__('Full Name')" 
                            type="text" 
                            required 
                            autofocus 
                            autocomplete="name"
                            class="transition-all duration-200 focus:ring-2 focus:ring-blue-500/20"
                        />
                    </div>

                    <div class="space-y-2">
                        <flux:input 
                            wire:model="username" 
                            :label="__('Username')" 
                            type="text" 
                            required 
                            autocomplete="username"
                            class="transition-all duration-200 focus:ring-2 focus:ring-blue-500/20"
                        />
                    </div>
                </div>

                <div class="space-y-2">
                    <flux:input 
                        wire:model="email" 
                        :label="__('Email Address')" 
                        type="email" 
                        required 
                        autocomplete="email"
                        class="transition-all duration-200 focus:ring-2 focus:ring-blue-500/20"
                    />

                    @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                        <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 rounded-lg">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <x-flux::icon.exclamation-triangle class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                </div>
                                <div class="ml-3">
                                    <flux:text class="text-amber-800 dark:text-amber-200">
                                        {{ __('Your email address is unverified.') }}
                                    </flux:text>
                                    <flux:link 
                                        class="text-sm text-amber-700 dark:text-amber-300 hover:text-amber-900 dark:hover:text-amber-100 underline cursor-pointer transition-colors duration-200" 
                                        wire:click.prevent="resendVerificationNotification"
                                    >
                                        {{ __('Click here to re-send the verification email.') }}
                                    </flux:link>
                                </div>
                            </div>

                            @if (session('status') === 'verification-link-sent')
                                <div class="mt-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800/50 rounded-lg">
                                    <div class="flex items-center">
                                        <x-flux::icon.check-circle class="h-5 w-5 text-green-600 dark:text-green-400 mr-2" />
                                        <flux:text class="text-green-800 dark:text-green-200 font-medium">
                                            {{ __('A new verification link has been sent to your email address.') }}
                                        </flux:text>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-stone-200 dark:border-stone-700">
                    <x-action-message class="text-green-600 dark:text-green-400 font-medium" on="profile-updated">
                        <div class="flex items-center">
                            <x-flux::icon.check-circle class="h-4 w-4 mr-2" />
                            {{ __('Profile updated successfully!') }}
                        </div>
                    </x-action-message>

                    <flux:button 
                        variant="primary" 
                        type="submit" 
                        class="px-6 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 dark:from-blue-500 dark:to-blue-600 dark:hover:from-blue-600 dark:hover:to-blue-700 transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove>
                            <x-flux::icon.check class="h-4 w-4 mr-2 inline" />
                            {{ __('Save Changes') }}
                        </span>
                        <span wire:loading class="flex items-center">
                            <x-flux::icon.arrow-path class="h-4 w-4 mr-2 animate-spin" />
                            {{ __('Saving...') }}
                        </span>
                    </flux:button>
                </div>
            </form>
        </div>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
