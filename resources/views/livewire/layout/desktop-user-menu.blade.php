<?php

use function Livewire\Volt\{state, on};

state(['user' => fn () => auth()->user() ?? null]);

on([
    'profile-updated' => function () {
        // First check if the user is authenticated
        if (!auth()->user()) {
            abort(401);
        }
        
        // Get the fresh user data
        $freshUser = auth()->user()->fresh();
        
        // Ensure the fresh user data is valid
        if (!$freshUser) {
            abort(401);
        }
        
        // Assign the fresh user data only if it's valid
        $this->user = $freshUser;
    }
]);

?>

<flux:dropdown class="hidden lg:block" position="bottom" align="start">
    <flux:profile
        :name="$user->name"
        :initials="$user->initials()"
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
                            {{ $user->initials() }}
                        </span>
                    </span>

                    <div class="grid flex-1 text-start text-sm leading-tight">
                        <span class="truncate font-semibold">{{ $user->name }}</span>
                        <span class="truncate text-xs">{{ $user->email }}</span>
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