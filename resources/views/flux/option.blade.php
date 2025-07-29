@props(['value' => null])

<option value="{{ $value ?? $slot }}" {{ $attributes }}>
    {{ $slot }}
</option> 