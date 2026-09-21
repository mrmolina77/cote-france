<div>
    @section('content')<p>Cobranza</p>@endsection
    <div class="mx-auto px-4 py-10 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h1 class="text-2xl font-bold text-gray-900">Cobranza</h1><p class="text-sm text-gray-500">Resumen financiero y movimientos de pago.</p></div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('facturacion.pagos.registrar') }}" class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Registrar pago</a>
                <a href="{{ route('facturacion.pagos.index') }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-gray-700 hover:bg-gray-50">Pagos</a>
                <a href="{{ route('facturacion.cargos') }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-gray-700 hover:bg-gray-50">Cargos</a>
                <a href="{{ route('facturacion.estado-cuenta') }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-gray-700 hover:bg-gray-50">Estado de cuenta</a>
            </div>
        </div>

        @php($tarjetas = [
            ['Cobrado este mes', $kpis['cobrado'], true], ['Pendiente', $kpis['pendiente'], true],
            ['Vencido', $kpis['vencido'], true], ['Estudiantes activos', $kpis['estudiantes'], false],
            ['Pagos por confirmar', $kpis['porConfirmar'], false], ['Pagos cancelados', $kpis['cancelados'], false],
        ])
        <section aria-label="Indicadores de cobranza" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($tarjetas as [$titulo, $valor, $dinero])
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">{{ $titulo }}</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ $dinero ? '$'.number_format($valor, 2, '.', ',').' MXN' : $valor }}</p>
                </div>
            @endforeach
        </section>

        <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 p-5 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg font-semibold">Movimientos de pago</h2><button type="button" wire:click="limpiarFiltros" class="text-sm font-medium text-indigo-700 hover:underline">Limpiar filtros</button></div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
                    <div class="md:col-span-2"><x-forms.input class="w-full" type="search" maxlength="120" placeholder="Folio, inscripción, alumno, referencia o SPEI…" wire:model.debounce.300ms="busqueda" /></div>
                    <x-select wire:model="estado" aria-label="Estado"><option value="todos">Todos los estados</option>@foreach(\App\Models\Pago::ESTADOS as $valor)<option value="{{ $valor }}">{{ ucfirst($valor) }}</option>@endforeach</x-select>
                    <x-select wire:model="metodoPagoId" aria-label="Método de pago"><option value="todos">Todos los métodos</option>@foreach($metodos as $metodo)<option value="{{ $metodo->metodo_pago_id }}">{{ $metodo->nombre }}</option>@endforeach</x-select>
                    <div><x-forms.input class="w-full" type="date" wire:model="fechaDesde" aria-label="Fecha desde" />@error('fechaDesde')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><x-forms.input class="w-full" type="date" wire:model="fechaHasta" aria-label="Fecha hasta" />@error('fechaHasta')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                </div>
                <div class="flex items-center text-sm"><span>Mostrar</span><x-select class="mx-2" wire:model="porPagina" aria-label="Cantidad por página"><option value="10">10</option><option value="25">25</option><option value="50">50</option></x-select><span>filas</span></div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead><tr>@foreach(['Fecha','Folio','Alumno','Inscripción','Método de pago','Monto y moneda','Estado','Acción'] as $titulo)<th class="border-b bg-gray-50 px-4 py-3 text-left text-xs uppercase text-gray-500 whitespace-nowrap">{{ $titulo }}</th>@endforeach</tr></thead>
                    <tbody>
                    @forelse($movimientos as $pago)
                        <tr wire:key="movimiento-{{ $pago->pago_id }}">
                            <td class="border-b px-4 py-3 whitespace-nowrap">{{ optional($pago->fecha_pago)->format('Y-m-d H:i') }}</td>
                            <td class="border-b px-4 py-3 font-mono">{{ $pago->folio }}</td>
                            <td class="border-b px-4 py-3">{{ trim(($pago->prospecto?->prospectos_nombres ?? '').' '.($pago->prospecto?->prospectos_apellidos ?? '')) ?: 'Sin alumno' }}</td>
                            <td class="border-b px-4 py-3">#{{ $pago->inscripciones_id }}</td>
                            <td class="border-b px-4 py-3">{{ $pago->metodoPago?->nombre ?: 'Sin método' }}</td>
                            <td class="border-b px-4 py-3 whitespace-nowrap">{{ $pago->moneda }} ${{ number_format($pago->monto, 2, '.', ',') }}</td>
                            <td class="border-b px-4 py-3"><span class="rounded bg-gray-100 px-2 py-1">{{ ucfirst($pago->estado) }}</span></td>
                            <td class="border-b px-4 py-3 whitespace-nowrap"><a href="{{ route('facturacion.pagos.index') }}" class="text-indigo-700 hover:underline">Ver en Pagos</a><a href="{{ route('facturacion.estado-cuenta', $pago->inscripciones_id) }}" class="ml-3 text-indigo-700 hover:underline">Estado de cuenta</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-gray-500">No se encontraron movimientos.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($movimientos->hasPages())<div class="px-5 py-4">{{ $movimientos->links() }}</div>@endif
        </section>
    </div>
</div>
