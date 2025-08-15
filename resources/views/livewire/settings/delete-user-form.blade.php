<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<!-- Account Deletion Card -->
<div class="mt-6 group bg-gradient-to-br from-red-50 to-rose-100 dark:from-red-950/30 dark:to-rose-950/30 rounded-lg shadow border border-red-200/50 dark:border-red-800/50 p-4 sm:p-5 hover:shadow-lg transition-all duration-300 backdrop-blur-sm">
    <div class="flex items-center mb-4">
        <div class="flex-shrink-0 p-2 bg-red-100 dark:bg-red-900/30 rounded-lg mr-3">
            <x-flux::icon.exclamation-triangle class="h-5 w-5 text-red-600 dark:text-red-400" />
        </div>
        <div>
            <h3 class="text-base font-bold text-red-900 dark:text-red-100">Danger Zone</h3>
            <p class="text-sm text-red-700 dark:text-red-300">Permanently delete your account and all associated data</p>
        </div>
    </div>

    <div class="bg-white/50 dark:bg-red-900/20 rounded-lg p-3 mb-4 border border-red-200/30 dark:border-red-800/30">
        <h4 class="font-semibold text-red-900 dark:text-red-100 mb-2">{{ __('Delete Account') }}</h4>
        <p class="text-sm text-red-800 dark:text-red-200 mb-4">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. This action cannot be undone.') }}
        </p>
        
        <flux:modal.trigger name="confirm-user-deletion">
            <flux:button 
                variant="danger" 
                class="bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 dark:from-red-500 dark:to-red-600 dark:hover:from-red-600 dark:hover:to-red-700 transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl"
                x-data="" 
                x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            >
                <x-flux::icon.trash class="h-4 w-4 mr-2" />
                {{ __('Delete Account') }}
            </flux:button>
        </flux:modal.trigger>
    </div>
    
    <flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <div class="bg-gradient-to-br from-white to-stone-50 dark:from-stone-800 dark:to-stone-900 rounded-xl shadow-2xl border border-stone-200/50 dark:border-stone-700/50 p-6">
            <form wire:submit="deleteUser" class="space-y-6">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
                        <x-flux::icon.exclamation-triangle class="h-6 w-6 text-red-600 dark:text-red-400" />
                    </div>
                    <flux:heading size="lg" class="text-red-900 dark:text-red-100">{{ __('Are you sure you want to delete your account?') }}</flux:heading>

                    <flux:subheading class="mt-2 text-red-700 dark:text-red-300">
                        {{ __('This action is permanent and cannot be undone. All your data will be permanently deleted.') }}
                    </flux:subheading>
                </div>

                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800/50">
                    <flux:input 
                        wire:model="password" 
                        :label="__('Enter your password to confirm')" 
                        type="password" 
                        required
                        class="transition-all duration-200 focus:ring-2 focus:ring-red-500/20"
                        placeholder="Password"
                    />
                </div>

                <div class="flex justify-end space-x-3 rtl:space-x-reverse pt-4 border-t border-stone-200 dark:border-stone-700">
                    <flux:modal.close>
                        <flux:button 
                            variant="filled" 
                            class="px-4 py-2 bg-gradient-to-r from-stone-100 to-stone-200 hover:from-stone-200 hover:to-stone-300 dark:from-stone-700 dark:to-stone-800 dark:hover:from-stone-600 dark:hover:to-stone-700 transition-all duration-200"
                        >
                            {{ __('Cancel') }}
                        </flux:button>
                    </flux:modal.close>

                    <flux:button 
                        variant="danger" 
                        type="submit"
                        class="px-4 py-2 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 dark:from-red-500 dark:to-red-600 dark:hover:from-red-600 dark:hover:to-red-700 transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove>
                            <x-flux::icon.trash class="h-4 w-4 mr-2 inline" />
                            {{ __('Delete Account') }}
                        </span>
                        <span wire:loading class="flex items-center">
                            <x-flux::icon.arrow-path class="h-4 w-4 mr-2 animate-spin" />
                            {{ __('Deleting...') }}
                        </span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
