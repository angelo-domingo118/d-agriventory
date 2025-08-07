@props(['name', 'maxWidth' => 'lg'])

@php
$maxWidthClass = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

<flux:modal :name="$name" variant="bare" class="w-full {{ $maxWidthClass }}" style="position: relative; z-index: 9999;">
    <div class="rounded-lg bg-white p-6 shadow-xl dark:bg-stone-800">
        {{ $slot }}
    </div>
</flux:modal>

