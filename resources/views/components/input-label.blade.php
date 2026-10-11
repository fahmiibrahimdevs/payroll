@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-[13.5px] text-[#344054]']) }}>
    {{ $value ?? $slot }}
</label>
