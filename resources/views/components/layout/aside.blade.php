<aside x-data="{ open: true }"
       class="relative h-screen transition-all duration-300"
       :class="open ? 'w-64' : 'w-20'">

    <!-- Header -->
    <div class="p-6 flex items-center bg-sidebar" :class="open ? 'justify-between' : 'justify-center'">
        <a href="{{ route('dashboard') }}"
           class="text-white text-2xl font-semibold capitalize hover:text-gray-200"
           x-show="open">
            Côté France
        </a>

        <!-- Logo colapsado (se muestra cuando el menú está cerrado) -->
        <a href="{{ route('dashboard') }}"
           x-show="!open"
           class="transition-opacity duration-300">
            <img src="{{ asset('images/cote_logo_white.png') }}" alt="Logo" class="h-8 w-auto">
        </a>

        <!-- Botón colapsar -->
        <button @click="open = !open"
                class="text-white focus:outline-none"
                :class="open ? 'ml-auto' : 'absolute top-7 right-4'">
            <i :class="open ? 'fas fa-angle-left' : 'fas fa-angle-right'"></i>
        </button>
    </div>

    <!-- Navegación -->
    <nav class="text-white text-base font-semibold space-y-1 px-2 bg-sidebar h-full overflow-y-auto">

        <!-- Dashboard -->
        <x-layout.aside-link href="{{ route('dashboard') }}"
                             :active="request()->routeIs('dashboard')"
                             icon="fas fa-home text-indigo-400 hover:text-indigo-200">
            {{ __('Dashboard') }}
        </x-layout.aside-link>

        <!-- Prospectos y Programadas -->
        @if(optional(auth()->user()->role)->roles_codigo == 'admin' || optional(auth()->user()->role)->roles_codigo == 'venta')
            <x-layout.aside-link href="{{ route('prospectos') }}"
                                 :active="request()->routeIs('prospectos')"
                                 icon="fas fa-user-plus text-green-400 hover:text-green-200">
                {{ __('Prospects') }}
            </x-layout.aside-link>

            <x-layout.aside-link href="{{ route('programadas') }}"
                                 :active="request()->routeIs('programadas')"
                                 icon="fas fa-calendar-check text-green-400 hover:text-green-200">
                {{ __('Scheduled') }}
            </x-layout.aside-link>
        @endif

        <!-- Horarios (admin) -->
        @if(optional(auth()->user()->role)->roles_codigo == 'admin')
            <x-layout.aside-link href="{{ route('horarios',['modalidad' => 1]) }}"
                                 :active="request()->routeIs('horarios') && request()->route('modalidad') == 1"
                                 icon="fas fa-chalkboard-teacher text-yellow-400 hover:text-yellow-200">
                {{ __('Timetable in person') }}
            </x-layout.aside-link>

            <x-layout.aside-link href="{{ route('horarios',['modalidad' => 2]) }}"
                                 :active="request()->routeIs('horarios') && request()->route('modalidad') == 2"
                                 icon="fas fa-laptop-house text-yellow-400 hover:text-yellow-200">
                {{ __('Timetable online') }}
            </x-layout.aside-link>
        @endif

        <!-- Horarios (profesor) -->
        @if(optional(auth()->user()->role)->roles_codigo == 'profe')
            <x-layout.aside-link href="{{ route('horario_profesor',['modalidad' => 1]) }}"
                                 :active="request()->routeIs('horario_profesor') && request()->route('modalidad') == 1"
                                 icon="fas fa-chalkboard-teacher text-yellow-400 hover:text-yellow-200">
                {{ __('Timetable in person') }}
            </x-layout.aside-link>

            <x-layout.aside-link href="{{ route('horario_profesor',['modalidad' => 2]) }}"
                                 :active="request()->routeIs('horario_profesor') && request()->route('modalidad') == 2"
                                 icon="fas fa-video text-yellow-400 hover:text-yellow-200">
                {{ __('Timetable online') }}
            </x-layout.aside-link>
        @endif

        <!-- Tareas -->
        @if(optional(auth()->user()->role)->roles_codigo == 'admin' || optional(auth()->user()->role)->roles_codigo == 'venta')
            <x-layout.aside-link href="{{ route('tareas') }}"
                                 :active="request()->routeIs('tareas')"
                                 icon="fas fa-tasks text-pink-400 hover:text-pink-200">
                {{ __('Homeworks') }}
                <span class="ml-auto bg-pink-600 text-xs text-white rounded-full px-2 py-0.5"
                      x-show="open">3</span>
            </x-layout.aside-link>
        @endif

        <!-- Grupos -->
        @if(optional(auth()->user()->role)->roles_codigo == 'admin')
            <x-layout.aside-link href="{{ route('grupos') }}"
                                 :active="request()->routeIs('grupos')"
                                 icon="fas fa-users-cog text-blue-400 hover:text-blue-200">
                {{ __('Groups') }}
            </x-layout.aside-link>

            <x-layout.aside-link href="{{ route('espacios') }}"
                                 :active="request()->routeIs('espacios')"
                                 icon="fas fa-school text-blue-400 hover:text-blue-200">
                {{ __('Salons') }}
            </x-layout.aside-link>

            <x-layout.aside-link href="{{ route('inscripciones') }}"
                                 :active="request()->routeIs('inscripciones')"
                                 icon="fas fa-clipboard-list text-purple-400 hover:text-purple-200">
                {{ __('Enrollment') }}
            </x-layout.aside-link>

            <x-layout.aside-link href="{{ route('profesores') }}"
                                 :active="request()->routeIs('profesores')"
                                 icon="fas fa-user-tie text-orange-400 hover:text-orange-200">
                {{ __('Teachers') }}
            </x-layout.aside-link>

            <x-layout.aside-link href="{{ route('usuarios') }}"
                                 :active="request()->routeIs('usuarios')"
                                 icon="fas fa-users text-orange-400 hover:text-orange-200">
                {{ __('Users') }}
            </x-layout.aside-link>
        @endif

        @can('view-financial-enrollments')
            @cannot('manage-inscripciones')
                <x-layout.aside-link href="{{ route('inscripciones') }}" :active="request()->routeIs('inscripciones')" icon="fas fa-clipboard-list text-purple-400">{{ __('Enrollment') }}</x-layout.aside-link>
            @endcannot
        @endcan

        @can('view-financial')
            <div class="px-4 pt-4 pb-1 text-xs uppercase tracking-wider text-gray-400" x-show="open">Facturación y pagos</div>
            <x-layout.aside-link href="{{ route('facturacion.cobranza') }}"
                                 :active="request()->routeIs('facturacion.cobranza')"
                                 icon="fas fa-chart-line text-emerald-400 hover:text-emerald-200">
                Cobranza
            </x-layout.aside-link>
            <x-layout.aside-link href="{{ route('facturacion.estado-cuenta') }}"
                                 :active="request()->routeIs('facturacion.estado-cuenta')"
                                 icon="fas fa-file-invoice-dollar text-emerald-400 hover:text-emerald-200">
                Estado de cuenta
            </x-layout.aside-link>
            @can('manage-cargos')
            <x-layout.aside-link href="{{ route('facturacion.cargos') }}"
                                 :active="request()->routeIs('facturacion.cargos')"
                                 icon="fas fa-receipt text-emerald-400 hover:text-emerald-200">
                Cargos
            </x-layout.aside-link>
            @endcan
            <x-layout.aside-link href="{{ route('facturacion.pagos.index') }}"
                                 :active="request()->routeIs('facturacion.pagos.index')"
                                 icon="fas fa-money-check-alt text-emerald-400 hover:text-emerald-200">
                Pagos
            </x-layout.aside-link>
            @can('register-payments')
            <x-layout.aside-link href="{{ route('facturacion.pagos.registrar') }}"
                                 :active="request()->routeIs('facturacion.pagos.registrar')"
                                 icon="fas fa-cash-register text-green-400 hover:text-green-200">
                Registrar pago
            </x-layout.aside-link>
            @endcan
            @can('audit-payments')
                <x-layout.aside-link href="{{ route('facturacion.auditoria') }}"
                                     :active="request()->routeIs('facturacion.auditoria')"
                                     icon="fas fa-clipboard-check text-amber-400 hover:text-amber-200">
                    Auditoría financiera
                </x-layout.aside-link>
            @endcan
            @can('manage-conceptos-cobro')
            <x-layout.aside-link href="{{ route('configuracion.conceptos-cobro') }}"
                                 :active="request()->routeIs('configuracion.conceptos-cobro', 'configuracion.metodos-pago')"
                                 icon="fas fa-cog text-cyan-400 hover:text-cyan-200">
                Configuración
            </x-layout.aside-link>
            @endcan
            @can('manage-metodos-pago')
            <x-layout.aside-link href="{{ route('configuracion.metodos-pago') }}"
                                 :active="request()->routeIs('configuracion.metodos-pago')"
                                 icon="fas fa-credit-card text-cyan-400 hover:text-cyan-200">
                Métodos de pago
            </x-layout.aside-link>
            @endcan
        @endcan
    </nav>
</aside>
