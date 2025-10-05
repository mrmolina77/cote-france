@props([
    'href' => '#',
    'active' => false,
    'icon' => 'fas fa-circle',
])

<a href="{{ $href }}"
   {{ $attributes->merge([
       'class' => ($active
           ? 'bg-blue-600 text-white'
           : 'text-gray-300 hover:bg-gray-700 hover:text-white') .
         ' flex items-center relative group px-3 py-2 rounded-md transition-colors duration-200'
   ]) }}
>
    <!-- Ícono siempre visible -->
    <i class="{{ $icon }} text-lg"></i>

    <!-- Texto visible solo si sidebar abierto -->
    <span x-show="open" class="ml-3 truncate">{{ $slot }}</span>

    <!-- Tooltip cuando sidebar cerrado -->
    <span x-show="!open"
          class="absolute left-full top-1/2 -translate-y-1/2 ml-2 px-2 py-1 text-sm bg-black text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap z-50">
        {{ $slot }}
    </span>
</a>
