@props(['value', 'required' => false])

<label {{ $attributes->merge(['class' => 'w-36 mt-2 block font-medium text-base text-gray-700']) }}>
    {{ $value ?? $slot }}
    @if ($required)
        <span class="text-red-500">*</span>
    @endif
</label>
