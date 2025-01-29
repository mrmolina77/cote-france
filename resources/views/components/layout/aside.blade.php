<aside class="relative bg-sidebar h-screen w-64 hidden sm:block shadow-xl">
    <div class="p-6">
        <a href="{{ route('dashboard') }}" class="text-white text-2xl font-semibold capitalize hover:text-gray-300">Côté France</a>
        {{-- <button class="w-full bg-white cta-btn font-semibold py-2 mt-5 rounded-br-lg rounded-bl-lg rounded-tr-lg shadow-lg hover:shadow-xl hover:bg-gray-300 flex items-center justify-center">
            <i class="fas fa-plus mr-3"></i> New Report
        </button> --}}
    </div>
    <nav class="text-white text-base font-semibold pt-3">
        <x-layout.aside-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
            <i class="fas fa-tachometer-alt mr-3"></i>
            {{ __('Dashboard') }}
        </x-layout.aside-link>
        <x-layout.aside-link href="{{ route('prospectos') }}" :active="request()->routeIs('prospectos')">
            <i class="fas fa-table mr-3"></i>
            {{ __('Prospects') }}
        </x-layout.aside-link>
        <x-layout.aside-link href="{{ route('programadas') }}" :active="request()->routeIs('programadas')">
            <i class="fas fa-chalkboard mr-3"></i>
            {{ __('Scheduled') }}
        </x-layout.aside-link>
        <x-layout.aside-link href="{{ route('horarios',['modalidad' => 1]) }}" :active="request()->routeIs('horarios')" :modalidad="1">
            <i class="fas fa-sticky-note mr-3"></i>
            {{ __('Timetable in person') }}
        </x-layout.aside-link>
        <x-layout.aside-link href="{{ route('horarios',['modalidad' => 2]) }}" :active="request()->routeIs('horarios')" :modalidad="2">
            <i class="fas fa-sticky-note mr-3"></i>
            {{ __('Timetable online') }}
        </x-layout.aside-link>

        {{-- <x-layout.aside-link href="{{ route('asistencias') }}" :active="request()->routeIs('asistencias')">
            <i class="fas fa-table mr-3"></i>
            {{ __('Assistance') }}
        </x-layout.aside-link> --}}
        <x-layout.aside-link href="{{ route('tareas') }}" :active="request()->routeIs('tareas')">
            <i class="fas fa-align-left mr-3"></i>
            {{ __('Homeworks') }}
        </x-layout.aside-link>
        <x-layout.aside-link href="{{ route('grupos') }}" :active="request()->routeIs('grupos')">
            <i class="fas fa-align-left mr-3"></i>
            {{ __('Groups') }}
        </x-layout.aside-link>
        <x-layout.aside-link href="{{ route('inscripciones') }}" :active="request()->routeIs('inscripciones')">
            <i class="fas fa-tablet-alt mr-3"></i>
            {{ __('Enrollment') }}
        </x-layout.aside-link>
        <x-layout.aside-link href="{{ route('profesores') }}" :active="request()->routeIs('profesores')">
            <i class="fas fa-user-alt mr-3"></i>
            {{ __('Teachers') }}
        </x-layout.aside-link>
        <x-layout.aside-link href="{{ route('usuarios') }}" :active="request()->routeIs('usuarios')">
            <i class="fas fa-user-alt mr-3"></i>
            {{ __('Users') }}
        </x-layout.aside-link>
        {{-- <x-layout.aside-link href="" :active="request()->routeIs('x')">
            <i class="fas fa-calendar mr-3"></i>
            Calendar
        </x-layout.aside-link> --}}
    </nav>
    {{-- <a href="#" class="absolute w-full upgrade-btn bottom-0 active-nav-link text-white flex items-center justify-center py-4">
        <i class="fas fa-arrow-circle-up mr-3"></i>
        Upgrade to Pro!
    </a> --}}
</aside>
