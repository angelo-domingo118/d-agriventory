<div class="flex items-start max-md:flex-col">
    <aside class="me-10 w-full pb-4 md:w-[220px] flex-shrink-0">
        <flux:navlist>
            <flux:navlist.item :href="route('settings.profile')" :current="request()->routeIs('settings.profile')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item :href="route('settings.password')" :current="request()->routeIs('settings.password')" wire:navigate>{{ __('Password') }}</flux:navlist.item>
            <flux:navlist.item :href="route('settings.appearance')" :current="request()->routeIs('settings.appearance')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
        </flux:navlist>
    </aside>

    <flux:separator class="md:hidden" />

    <main class="flex-1 self-stretch max-md:pt-6">
        <header class="mb-6">
             <h2 class="text-xl font-bold text-stone-900 dark:text-stone-100">
                 {{ $heading ?? '' }}
             </h2>
             @if (isset($subheading))
                 <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                     {{ $subheading ?? '' }}
                 </p>
             @endif
        </header>

        <div class="space-y-6">
             {{ $slot }}
        </div>
    </main>
</div>
