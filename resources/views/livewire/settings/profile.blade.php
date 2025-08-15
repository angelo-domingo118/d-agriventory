<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $username = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->username = Auth::user()->username;
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
        ]);

        $user->fill($validated);
        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }


}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and username')">
        <!-- Profile Information Card -->
        <div class="bg-white dark:bg-stone-800 rounded-lg shadow ring-1 ring-black ring-opacity-5 dark:ring-stone-700 p-6">
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Personal Information</h3>
                <p class="text-sm text-stone-600 dark:text-stone-400 mt-1">Update your basic profile details</p>
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
                            :label="__('Username (Login ID)')" 
                            type="text" 
                            required 
                            autocomplete="username"
                            hint="This is used to log into the system"
                            class="transition-all duration-200 focus:ring-2 focus:ring-blue-500/20"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-between pt-6 border-t border-stone-200 dark:border-stone-700">
                    <x-action-message class="text-green-600 dark:text-green-400 font-medium" on="profile-updated">
                        <div class="flex items-center">
                            <x-flux::icon.check-circle class="h-4 w-4 mr-2" />
                            {{ __('Profile updated successfully!') }}
                        </div>
                    </x-action-message>

                    <flux:button 
                        variant="primary" 
                        type="submit"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove>
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
