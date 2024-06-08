@props(['value'])

<label {{ $attributes->merge(['class' => 'w-32 mt-2 block font-medium text-base text-gray-700']) }}>
    {{ $value ?? $slot }}
</label>
