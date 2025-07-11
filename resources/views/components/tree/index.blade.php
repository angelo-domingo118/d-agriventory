@props([
    'items',
    'expandableIds' => [],
    'emptyMessage' => 'No items found.',
    'createUrl' => null,
    'createText' => 'Create',
    'search' => true,
    'searchModel' => 'search',
    'isSearching' => false,
])

<div x-data="{
    open: [],
    expandableIds: {{ json_encode($expandableIds) }},
    expandAll() {
        this.open = this.expandableIds;
    },
    collapseAll() {
        this.open = [];
    }
}" class="space-y-4">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        @if ($search)
            <div class="w-full max-w-xs">
                <flux:input
                    wire:model.live.debounce.300ms="{{ $searchModel }}"
                    placeholder="Search anything..."
                    class="{{ $isSearching ? 'border-primary-500 dark:border-primary-400' : '' }}"
                    icon="magnifying-glass"
                    clearable
                >
                    <x-slot:trailing>
                        <div wire:loading.delay wire:target="{{ $searchModel }}">
                            <x-flux::icon.rotate-cw class="h-5 w-5 animate-spin text-stone-400" />
                        </div>
                    </x-slot:trailing>
                </flux:input>
            </div>
        @endif
        <div class="flex items-center justify-end gap-x-4">
            @if ($items->isNotEmpty())
                <div class="inline-flex rounded-md shadow-sm">
                    <button type="button" @click="expandAll()"
                            class="relative inline-flex items-center gap-x-1.5 rounded-l-md bg-white px-3 py-2 text-sm font-medium text-stone-700 ring-1 ring-inset ring-stone-300 hover:bg-stone-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-stone-800 dark:text-stone-200 dark:ring-stone-600 dark:hover:bg-stone-700">
                        <x-flux::icon.chevron-down class="h-4 w-4 text-stone-400" />
                        Expand all
                    </button>
                    <button type="button" @click="collapseAll()"
                            class="relative -ml-px inline-flex items-center gap-x-1.5 rounded-r-md bg-white px-3 py-2 text-sm font-medium text-stone-700 ring-1 ring-inset ring-stone-300 hover:bg-stone-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-stone-800 dark:text-stone-200 dark:ring-stone-600 dark:hover:bg-stone-700">
                        <x-flux::icon.chevron-up class="h-4 w-4 text-stone-400" />
                        Collapse all
                    </button>
                </div>
            @endif

            @if ($createUrl)
                <flux:button :href="$createUrl . (str_contains($createUrl, '?') ? '&' : '?') . 'view=tree'" variant="primary" class="whitespace-nowrap">
                    <x-flux::icon.plus class="w-5 h-5 mr-2 -ml-1" />
                    {{ $createText }}
                </flux:button>
            @endif
        </div>
    </div>

    @if ($items->isNotEmpty())
        <div {{ $attributes->class('space-y-4 pt-4') }}>
            {{ $slot }}
        </div>
    @else
        <div class="rounded-lg border border-dashed border-stone-300 p-12 text-center dark:border-stone-700">
            <h3 class="text-lg font-medium text-stone-900 dark:text-stone-100">{{ $emptyMessage }}</h3>
            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                @if ($createUrl)
                    Get started by creating a new one.
                @endif
            </p>
        </div>
    @endif
</div> 