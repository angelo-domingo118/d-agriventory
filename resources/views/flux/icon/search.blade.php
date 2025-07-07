{{-- Credit: Lucide (https://lucide.dev) --}}

@props([
    'variant' => 'outline',
])

@php
if ($variant === 'solid') {
    throw new \Exception('The "solid" variant is not supported in Lucide.');
}

$class = Flux::classes('shrink-0')
    ->add(match($variant) {
        'outline' => 'size-6',
        'solid'   => 'size-5',
    });
@endphp

<svg {{ $attributes->merge([
    'class' => $class,
    'fill' => 'none',
    'viewBox' => '0 0 24 24',
    'stroke-width' => '1.5',
    'stroke' => 'currentColor',
]) }}>
    @if ($variant === 'outline')
        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
    @endif
    @if ($variant === 'solid')
        <path fill="currentColor" fill-rule="evenodd" d="M10 3a7 7 0 1 0 0 14 7 7 0 0 0 0-14ZM1.5 10a8.5 8.5 0 1 1 15.106 5.06l4.252 4.253a.75.75 0 1 1-1.06 1.06l-4.253-4.252A8.5 8.5 0 0 1 1.5 10Z" clip-rule="evenodd" />
    @endif
</svg>
