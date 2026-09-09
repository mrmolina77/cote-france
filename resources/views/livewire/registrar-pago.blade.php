<div>
    @section('content')<p>Registrar pago</p>@endsection
    <div class="mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
        <div class="bg-white rounded-lg shadow p-5">
            <h1 class="text-2xl font-semibold text-gray-800">Registrar pago</h1>
            <p class="mt-1 text-sm text-gray-500">Consulta la inscripción y sus cargos antes de preparar un pago.</p>

            <div class="mt-5">
                <x-forms.label value="Buscar alumno o inscripción" />
                <x-forms.input type="search" class="w-full mt-1" wire:model.debounce.300ms="busqueda" placeholder="ID, nombre o apellidos" autocomplete="off" />
                <p class="mt-1 text-xs text-gray-500">Se muestran como máximo 25 coincidencias.</p>
            </div>

            @if(trim($busqueda) !== '')
                <div class="mt-4 overflow-x-auto border rounded-lg">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-xs"><tr><th class="p-3 text-left">Inscripción</th><th class="p-3 text-left">Alumno</th><th class="p-3 text-left">Curso</th><th class="p-3 text-left">Grupo</th><th class="p-3 text-left">Estado</th><th class="p-3 text-left">Fecha</th><th class="p-3"></th></tr></thead>
                        <tbody>
                        @forelse($resultados as $resultado)
                            <tr class="border-t" wire:key="resultado-{{ $resultado->inscripciones_id }}">
                                <td class="p-3 font-mono">#{{ $resultado->inscripciones_id }}</td>
                                <td class="p-3 font-medium">{{ trim(($resultado->prospecto?->prospectos_nombres ?? '').' '.($resultado->prospecto?->prospectos_apellidos ?? '')) }}</td>
                                <td class="p-3">{{ $resultado->cursos?->cursos_descripcion ?? 'Sin curso' }}</td>
                                <td class="p-3">{{ $resultado->grupo?->grupo_nombre ?? 'Sin grupo' }}</td>
                                <td class="p-3">{{ ucfirst($resultado->estatus ?? 'Sin estado') }}</td>
                                <td class="p-3 whitespace-nowrap">{{ $resultado->fecha_inscripcion?->format('Y-m-d') ?? 'Sin fecha' }}</td>
                                <td class="p-3 text-right"><button type="button" wire:click="seleccionarInscripcion({{ $resultado->inscripciones_id }})" class="px-3 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Seleccionar</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="p-6 text-center text-gray-500">No existen coincidencias.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if($inscripcion)
            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex flex-wrap justify-between gap-3">
                    <h2 class="text-lg font-semibold text-gray-800">Inscripción seleccionada</h2>
                    <button type="button" wire:click="limpiarSeleccion" class="px-3 py-2 border rounded text-gray-700 hover:bg-gray-50">Cambiar inscripción</button>
                </div>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <section>
                        <h3 class="font-semibold text-indigo-700 mb-2">Alumno</h3>
                        <dl class="grid grid-cols-2 gap-2">
                            <dt class="text-gray-500">Nombre</dt><dd>{{ trim(($inscripcion->prospecto?->prospectos_nombres ?? '').' '.($inscripcion->prospecto?->prospectos_apellidos ?? '')) }}</dd>
                            <dt class="text-gray-500">ID</dt><dd>#{{ $inscripcion->inscripciones_id }}</dd>
                            <dt class="text-gray-500">Estado</dt><dd>{{ ucfirst($inscripcion->estatus ?? 'Sin estado') }}</dd>
                            <dt class="text-gray-500">Inscripción</dt><dd>{{ $inscripcion->fecha_inscripcion?->format('Y-m-d') ?? 'Sin fecha' }}</dd>
                            <dt class="text-gray-500">Inicio / fin</dt><dd>{{ $inscripcion->fecha_inicio?->format('Y-m-d') ?? 'Sin fecha' }} / {{ $inscripcion->fecha_fin?->format('Y-m-d') ?? 'Sin fecha' }}</dd>
                            <dt class="text-gray-500">Curso</dt><dd>{{ $inscripcion->cursos?->cursos_descripcion ?? 'Sin curso' }}</dd>
                            <dt class="text-gray-500">Grupo</dt><dd>{{ $inscripcion->grupo?->grupo_nombre ?? 'Sin grupo' }}</dd>
                            <dt class="text-gray-500">Moneda</dt><dd>{{ $inscripcion->moneda ?: 'Sin moneda' }}</dd>
                        </dl>
                    </section>
                    <section>
                        <h3 class="font-semibold text-indigo-700 mb-2">Responsable de pago</h3>
                        @if($inscripcion->responsablePago)
                            <dl class="grid grid-cols-2 gap-2">
                                <dt class="text-gray-500">Nombre o razón social</dt><dd>{{ $inscripcion->responsablePago->nombre_razon_social ?: 'Sin dato' }}</dd>
                                <dt class="text-gray-500">Tipo</dt><dd>{{ ucfirst($inscripcion->responsablePago->tipo ?? 'Sin dato') }}</dd>
                                <dt class="text-gray-500">Teléfono</dt><dd>{{ $inscripcion->responsablePago->telefono ?: 'Sin dato' }}</dd>
                                <dt class="text-gray-500">Correo</dt><dd>{{ $inscripcion->responsablePago->correo ?: 'Sin dato' }}</dd>
                                <dt class="text-gray-500">Estado</dt><dd>{{ $inscripcion->responsablePago->activo ? 'Activo' : 'Inactivo' }}</dd>
                            </dl>
                            @if(!$inscripcion->responsablePago->activo)<p class="mt-3 p-3 bg-yellow-50 text-yellow-800 rounded">El responsable de pago está inactivo.</p>@endif
                        @else
                            <p class="p-3 bg-yellow-50 text-yellow-800 rounded">Esta inscripción no tiene un responsable de pago válido.</p>
                        @endif
                    </section>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white shadow rounded-lg p-4"><div class="text-xs text-gray-500 uppercase">Saldo pendiente</div><div class="text-xl font-semibold">{{ $inscripcion->moneda ?: 'MXN' }} ${{ $saldoPendiente }}</div></div>
                <div class="bg-white shadow rounded-lg p-4"><div class="text-xs text-gray-500 uppercase">Saldo vencido</div><div class="text-xl font-semibold text-red-700">{{ $inscripcion->moneda ?: 'MXN' }} ${{ $saldoVencido }}</div></div>
                <div class="bg-white shadow rounded-lg p-4"><div class="text-xs text-gray-500 uppercase">Cargos abiertos</div><div class="text-xl font-semibold">{{ $cantidadCargosAbiertos }}</div></div>
                <div class="bg-white shadow rounded-lg p-4"><div class="text-xs text-gray-500 uppercase">Cargos vencidos</div><div class="text-xl font-semibold">{{ $cantidadCargosVencidos }}</div></div>
                <div class="bg-white shadow rounded-lg p-4 col-span-2 lg:col-span-1"><div class="text-xs text-gray-500 uppercase">Próximo vencimiento</div><div class="text-xl font-semibold">{{ $proximoVencimiento ?: 'Sin próximo vencimiento' }}</div></div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs"><tr><th class="p-3 text-left">Concepto</th><th class="p-3 text-left">Periodo</th><th class="p-3 text-left">Emisión</th><th class="p-3 text-left">Vencimiento</th><th class="p-3 text-right">Total original</th><th class="p-3 text-right">Saldo pendiente</th><th class="p-3 text-left">Estado</th></tr></thead>
                    <tbody>
                    @forelse($cargos as $cargo)
                        @php($estadoClases=['pendiente'=>'bg-yellow-100 text-yellow-800','parcial'=>'bg-blue-100 text-blue-800','vencido'=>'bg-red-100 text-red-800'])
                        <tr class="border-t" wire:key="cargo-pago-{{ $cargo->cargo_id }}">
                            <td class="p-3">{{ $cargo->conceptoCobro?->nombre ?? $cargo->conceptoCobro?->clave ?? 'Sin concepto' }}</td>
                            <td class="p-3 whitespace-nowrap">{{ $cargo->periodo_anio && $cargo->periodo_mes ? sprintf('%04d-%02d', $cargo->periodo_anio, $cargo->periodo_mes) : 'Sin periodo' }}</td>
                            <td class="p-3 whitespace-nowrap">{{ $cargo->fecha_emision?->format('Y-m-d') ?? 'Sin fecha' }}</td>
                            <td class="p-3 whitespace-nowrap">{{ $cargo->fecha_vencimiento?->format('Y-m-d') ?? 'Sin fecha' }}</td>
                            <td class="p-3 text-right whitespace-nowrap">{{ $cargo->moneda }} ${{ $cargo->total }}</td>
                            <td class="p-3 text-right whitespace-nowrap font-semibold">{{ $cargo->moneda }} ${{ $cargo->saldo_pendiente }}</td>
                            <td class="p-3"><span class="px-2 py-1 rounded {{ $estadoClases[$cargo->estado] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst($cargo->estado) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-8 text-center text-gray-500">No hay cargos pendientes.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
