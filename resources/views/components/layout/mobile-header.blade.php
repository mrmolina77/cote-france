<div>
    <header x-data="{ isOpen: false }" class="w-full bg-sidebar py-5 px-6 sm:hidden">
        <div class="flex items-center justify-between">
            <a href="index.html" class="text-white text-3xl font-semibold uppercase hover:text-gray-300">Admin</a>
            <button @click="isOpen = !isOpen" class="!text-white !text-3xl focus:!outline-none">
                <i x-show="!isOpen" class="fas fa-bars"></i>
                <i x-show="isOpen" class="fas fa-times"></i>
            </button>
        </div>

        <!-- Dropdown Nav -->
        <nav :class="isOpen ? 'flex': 'hidden'" class="flex flex-col pt-4">
            <a href="{{ route('dashboard') }}" class="flex items-center text-white opacity-75 hover:opacity-100 py-2 pl-4 nav-item">
                <i class="fas fa-tachometer-alt mr-3"></i>
                {{ __('Dashboard') }}
            </a>
            <a href="{{ route('prospectos') }}" class="flex items-center active-nav-link text-white py-2 pl-4 nav-item">
                <i class="fas fa-table mr-3"></i>
                {{ __('Prospects') }}
            </a>
            <a href="{{ route('programadas') }}" class="flex items-center text-white opacity-75 hover:opacity-100 py-2 pl-4 nav-item">
                <i class="fas fa-chalkboard mr-3"></i>
                {{ __('Scheduled') }}
            </a>
            <a href="{{ route('clasespruebas') }}" class="flex items-center text-white opacity-75 hover:opacity-100 py-2 pl-4 nav-item">
                <i class="fas fa-sticky-note mr-3"></i>
                {{ __('Test classes') }}
            </a>
            <a href="{{ route('asistencias') }}" class="flex items-center text-white opacity-75 hover:opacity-100 py-2 pl-4 nav-item">
                <i class="fas fa-table mr-3"></i>
                {{ __('Assistance') }}
            </a>
            <a href="{{ route('tareas') }}" class="flex items-center text-white opacity-75 hover:opacity-100 py-2 pl-4 nav-item">
                <i class="fas fa-align-left mr-3"></i>
                {{ __('Homeworks') }}
            </a>
            <a href="{{ route('inscripciones') }}" class="flex items-center text-white opacity-75 hover:opacity-100 py-2 pl-4 nav-item">
                <i class="fas fa-tablet-alt mr-3"></i>
                {{ __('Enrollment') }}
            </a>
            <a href="{{ route('usuarios') }}" class="flex items-center text-white opacity-75 hover:opacity-100 py-2 pl-4 nav-item">
                <i class="fas fa-user-alt mr-3"></i>
                {{ __('Users') }}
            </a>
            <form method="POST" action="{{ route('logout') }}" x-data>
                @csrf

                <div class="flex items-center text-white opacity-75 hover:opacity-100 py-2 pl-4 nav-item" href="{{ route('logout') }}"
                         @click.prevent="$root.submit();">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    {{ __('Log Out') }}
                </div>
            </form>
            {{-- <a href="{{ route('logout') }}" class="flex items-center text-white opacity-75 hover:opacity-100 py-2 pl-4 nav-item">
                <i class="fas fa-sign-out-alt mr-3"></i>
                {{ __('Log Out') }}
            </a> --}}
            {{-- <button class="w-full bg-white cta-btn font-semibold py-2 mt-3 rounded-lg shadow-lg hover:shadow-xl hover:bg-gray-300 flex items-center justify-center">
                <i class="fas fa-arrow-circle-up mr-3"></i> Upgrade to Pro!
            </button> --}}
        </nav>
        <!-- <button class="w-full bg-white cta-btn font-semibold py-2 mt-5 rounded-br-lg rounded-bl-lg rounded-tr-lg shadow-lg hover:shadow-xl hover:bg-gray-300 flex items-center justify-center">
            <i class="fas fa-plus mr-3"></i> New Report
        </button> -->
    </header>
</div>
