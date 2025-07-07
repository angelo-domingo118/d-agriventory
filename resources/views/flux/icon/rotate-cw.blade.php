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
        'outline' => '[:where(&)]:size-6',
        'mini'    => '[:where(&)]:size-5',
        'micro'   => '[:where(&)]:size-4',
    });
@endphp

<svg
    {{ $attributes->merge([
        'class' => $class,
        'fill' => 'none',
        'viewBox' => '0 0 24 24',
        'stroke-width' => '1.5',
        'stroke' => 'currentColor',
    ]) }}
    data-flux-icon
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
    data-slot="icon"
>
    @if (in_array($variant, ['outline', 'mini', 'micro']))
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0 0 11.667 0l3.181-3.183m-4.991 0l-3.182-3.182a8.25 8.25 0 0 0-11.667 0L2.985 14.652z" />
    @endif
</svg>
