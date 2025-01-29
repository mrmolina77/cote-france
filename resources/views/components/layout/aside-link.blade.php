@props(['href', 'active', 'modalidad' => null])

@php
$classes = ($active && ($modalidad === null || request('modalidad') == $modalidad))
            ? 'flex items-center active-nav-link text-white py-4 pl-6 nav-item'
            : 'flex items-center text-white opacity-75 hover:opacity-100 py-4 pl-6 nav-item';
@endphp

<a {{ $attributes->merge(['href' => $href, 'class' => $classes]) }}>
    {{ $slot }}
</a>
