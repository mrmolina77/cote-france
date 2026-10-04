<div>
    @section('content')<p class="hidden">Cobranza</p>@endsection
    <div class="mx-auto px-4 py-8 sm:px-6 lg:px-8 max-w-7xl">
        <!-- Header Section -->
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-gray-100 mb-8 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-64 h-64 bg-indigo-50 rounded-full blur-3xl -z-10 -mr-20 -mt-20"></div>
            <div class="z-10">
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Panel de Cobranza</h1>
                <p class="mt-2 text-sm text-gray-500 font-medium max-w-2xl">Resumen financiero, estadísticas en tiempo real y movimientos de pago detallados.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 z-10">
                <a href="{{ route('facturacion.pagos.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Pagos
                </a>
                <a href="{{ route('facturacion.cargos') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path></svg>
                    Cargos
                </a>
                <a href="{{ route('facturacion.estado-cuenta') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Estado de cuenta
                </a>
                <a href="{{ route('facturacion.pagos.registrar') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-md hover:bg-indigo-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 transform hover:-translate-y-0.5">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Registrar pago
                </a>
            </div>
        </div>

        @php
            $tarjetas = [
                [
                    'titulo' => 'Cobrado este mes',
                    'valor' => $kpis['cobrado'],
                    'dinero' => true,
                    'icon' => '<svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                    'bg' => 'bg-gradient-to-br from-emerald-50 to-emerald-100/40',
                    'border' => 'border-emerald-100',
                    'circle' => 'bg-emerald-400',
                    'text' => 'text-emerald-800/70'
                ],
                [
                    'titulo' => 'Pendiente',
                    'valor' => $kpis['pendiente'],
                    'dinero' => true,
                    'icon' => '<svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                    'bg' => 'bg-gradient-to-br from-amber-50 to-amber-100/40',
                    'border' => 'border-amber-100',
                    'circle' => 'bg-amber-400',
                    'text' => 'text-amber-800/70'
                ],
                [
                    'titulo' => 'Vencido',
                    'valor' => $kpis['vencido'],
                    'dinero' => true,
                    'icon' => '<svg class="w-7 h-7 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
                    'bg' => 'bg-gradient-to-br from-rose-50 to-rose-100/40',
                    'border' => 'border-rose-100',
                    'circle' => 'bg-rose-400',
                    'text' => 'text-rose-800/70'
                ],
                [
                    'titulo' => 'Estudiantes activos',
                    'valor' => $kpis['estudiantes'],
                    'dinero' => false,
                    'icon' => '<svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>',
                    'bg' => 'bg-gradient-to-br from-blue-50 to-blue-100/40',
                    'border' => 'border-blue-100',
                    'circle' => 'bg-blue-400',
                    'text' => 'text-blue-800/70'
                ],
                [
                    'titulo' => 'Pagos por confirmar',
                    'valor' => $kpis['porConfirmar'],
                    'dinero' => false,
                    'icon' => '<svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>',
                    'bg' => 'bg-gradient-to-br from-purple-50 to-purple-100/40',
                    'border' => 'border-purple-100',
                    'circle' => 'bg-purple-400',
                    'text' => 'text-purple-800/70'
                ],
                [
                    'titulo' => 'Pagos cancelados',
                    'valor' => $kpis['cancelados'],
                    'dinero' => false,
                    'icon' => '<svg class="w-7 h-7 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                    'bg' => 'bg-gradient-to-br from-gray-50 to-gray-100/40',
                    'border' => 'border-gray-100',
                    'circle' => 'bg-gray-400',
                    'text' => 'text-gray-800/70'
                ],
            ];
        @endphp

        <!-- KPI Cards -->
        <style>
            .cobranza-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
                gap: 1.5rem;
                margin-bottom: 2.5rem;
            }
        </style>
        <section aria-label="Indicadores de cobranza" class="cobranza-grid">
            @foreach($tarjetas as $tarjeta)
                <div class="relative overflow-hidden rounded-2xl border {{ $tarjeta['border'] }} {{ $tarjeta['bg'] }} p-6 shadow-sm transition-all duration-300 hover:shadow-md hover:-translate-y-1 group cursor-pointer w-full">
                    <div class="flex flex-col h-full justify-between z-10 relative">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-sm border border-white/60 group-hover:scale-110 transition-transform duration-300">
                                {!! $tarjeta['icon'] !!}
                            </div>
                            <!-- Decorative shape behind icon -->
                            <div class="absolute right-2 top-2 h-20 w-20 rounded-full {{ $tarjeta['circle'] }} opacity-20 blur-2xl"></div>
                        </div>
                        <div>
                            <p class="text-xs font-bold tracking-wider {{ $tarjeta['text'] }} uppercase mb-1">{{ $tarjeta['titulo'] }}</p>
                            <p class="text-3xl font-black text-gray-900 tracking-tight">
                                {{ $tarjeta['dinero'] ? '$'.number_format($tarjeta['valor'], 2, '.', ',') : $tarjeta['valor'] }}
                                @if($tarjeta['dinero']) <span class="text-lg font-bold text-gray-500">MXN</span> @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </section>

        <!-- Main Content Table -->
        <section class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden flex flex-col">
            <!-- Table Header & Filters -->
            <div class="border-b border-gray-200 bg-gray-50/80 p-6 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h2 class="text-xl font-bold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        Movimientos de pago
                    </h2>
                    <button type="button" wire:click="limpiarFiltros" class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors bg-indigo-50 px-3 py-1.5 rounded-lg hover:bg-indigo-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Limpiar filtros
                    </button>
                </div>
                
                <div class="grid grid-cols-1 gap-4 md:grid-cols-12 items-end">
                    <div class="md:col-span-4">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Buscar</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                            </div>
                            <x-forms.input class="w-full pl-10 rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-medium text-sm transition-shadow" type="search" maxlength="120" placeholder="Folio, inscripción, alumno..." wire:model.debounce.300ms="busqueda" />
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Estado</label>
                        <x-select class="w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-medium text-sm transition-shadow" wire:model="estado" aria-label="Estado">
                            <option value="todos">Todos los estados</option>
                            @foreach(\App\Models\Pago::ESTADOS as $valor)
                                <option value="{{ $valor }}">{{ ucfirst($valor) }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Método de pago</label>
                        <x-select class="w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-medium text-sm transition-shadow" wire:model="metodoPagoId" aria-label="Método de pago">
                            <option value="todos">Todos los métodos</option>
                            @foreach($metodos as $metodo)
                                <option value="{{ $metodo->metodo_pago_id }}">{{ $metodo->nombre }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Fecha Desde</label>
                        <x-forms.input class="w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-medium text-sm transition-shadow" type="date" wire:model="fechaDesde" aria-label="Fecha desde" />
                        @error('fechaDesde')<p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>@enderror
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Fecha Hasta</label>
                        <x-forms.input class="w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-medium text-sm transition-shadow" type="date" wire:model="fechaHasta" aria-label="Fecha hasta" />
                        @error('fechaHasta')<p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>@enderror
                    </div>
                </div>
                
                <div class="flex items-center justify-between text-sm pt-2 border-t border-gray-200/60 mt-6">
                    <div class="flex items-center text-gray-500 font-medium mt-4">
                        <span>Mostrar</span>
                        <x-select class="mx-2 py-1.5 pl-3 pr-8 text-sm font-semibold rounded-lg border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white" wire:model="porPagina" aria-label="Cantidad por página">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </x-select>
                        <span>filas por página</span>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach(['Fecha','Folio','Alumno','Inscripción','Método de pago','Monto','Estado','Acciones'] as $titulo)
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold tracking-wider text-gray-500 uppercase">{{ $titulo }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($movimientos as $pago)
                        <tr wire:key="movimiento-{{ $pago->pago_id }}" class="hover:bg-indigo-50/30 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-900">{{ optional($pago->fecha_pago)->format('d/m/Y') }}</span>
                                    <span class="text-xs text-gray-400 flex items-center mt-0.5">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ optional($pago->fecha_pago)->format('H:i') }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2.5 py-1 rounded-md bg-gray-100 border border-gray-200 font-mono text-xs font-semibold text-gray-700 tracking-wide">{{ $pago->folio }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <div class="flex items-center">
                                    <div class="h-9 w-9 rounded-full bg-gradient-to-br from-indigo-100 to-indigo-200 flex items-center justify-center text-indigo-700 font-bold mr-3 flex-shrink-0 shadow-inner border border-indigo-100">
                                        {{ substr(trim($pago->prospecto?->prospectos_nombres ?? 'S'), 0, 1) }}
                                    </div>
                                    <span class="font-semibold text-gray-900">{{ trim(($pago->prospecto?->prospectos_nombres ?? '').' '.($pago->prospecto?->prospectos_apellidos ?? '')) ?: 'Sin alumno' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                    #{{ $pago->inscripciones_id }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <div class="flex items-center gap-2 font-medium">
                                    @if($pago->metodoPago?->nombre == 'Efectivo')
                                        <div class="p-1.5 bg-emerald-100 rounded-lg text-emerald-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg></div>
                                    @elseif(str_contains(strtolower($pago->metodoPago?->nombre ?? ''), 'tarjeta'))
                                        <div class="p-1.5 bg-blue-100 rounded-lg text-blue-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg></div>
                                    @elseif(str_contains(strtolower($pago->metodoPago?->nombre ?? ''), 'transferencia'))
                                        <div class="p-1.5 bg-purple-100 rounded-lg text-purple-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg></div>
                                    @else
                                        <div class="p-1.5 bg-gray-100 rounded-lg text-gray-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
                                    @endif
                                    {{ $pago->metodoPago?->nombre ?: 'Sin método' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-gray-900">${{ number_format($pago->monto, 2, '.', ',') }}</span>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ $pago->moneda }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $estadoConfig = match($pago->estado) {
                                        'aprobado' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'dot' => 'bg-emerald-500'],
                                        'pendiente' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200', 'dot' => 'bg-amber-500'],
                                        'cancelado' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'border' => 'border-rose-200', 'dot' => 'bg-rose-500'],
                                        default => ['bg' => 'bg-gray-50', 'text' => 'text-gray-700', 'border' => 'border-gray-200', 'dot' => 'bg-gray-500'],
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold border {{ $estadoConfig['bg'] }} {{ $estadoConfig['text'] }} {{ $estadoConfig['border'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $estadoConfig['dot'] }} mr-1.5 animate-pulse"></span>
                                    {{ ucfirst($pago->estado) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex gap-2 justify-end opacity-70 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('facturacion.pagos.index') }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-indigo-600 hover:text-white hover:bg-indigo-600 transition-colors tooltip" title="Ver detalle de pago">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </a>
                                    <a href="{{ route('facturacion.estado-cuenta', $pago->inscripciones_id) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-emerald-600 hover:text-white hover:bg-emerald-600 transition-colors tooltip" title="Ver estado de cuenta">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-20 text-center bg-gray-50/50">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white shadow-sm border border-gray-100 mb-4">
                                    <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-1">No hay movimientos</h3>
                                <p class="text-sm text-gray-500 max-w-sm mx-auto">No se encontraron pagos que coincidan con tu búsqueda actual. Intenta ajustar los filtros.</p>
                                <button type="button" wire:click="limpiarFiltros" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl shadow-sm text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                    Limpiar todos los filtros
                                </button>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($movimientos->hasPages())
                <div class="border-t border-gray-200 bg-gray-50/50 px-6 py-4">
                    {{ $movimientos->links() }}
                </div>
            @endif
        </section>
    </div>
</div>
