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
                <div class="bg-white shadow rounded-lg p-4"><div class="text-xs text-gray-500 uppercase">Saldo pendiente</div><div class="text-xl font-semibold">{{ $inscripcion->moneda ?: 'MXN' }} ${{ $resumen['saldoPendiente'] }}</div></div>
                <div class="bg-white shadow rounded-lg p-4"><div class="text-xs text-gray-500 uppercase">Saldo vencido</div><div class="text-xl font-semibold text-red-700">{{ $inscripcion->moneda ?: 'MXN' }} ${{ $resumen['saldoVencido'] }}</div></div>
                <div class="bg-white shadow rounded-lg p-4"><div class="text-xs text-gray-500 uppercase">Cargos abiertos</div><div class="text-xl font-semibold">{{ $resumen['cantidadCargosAbiertos'] }}</div></div>
                <div class="bg-white shadow rounded-lg p-4"><div class="text-xs text-gray-500 uppercase">Cargos vencidos</div><div class="text-xl font-semibold">{{ $resumen['cantidadCargosVencidos'] }}</div></div>
                <div class="bg-white shadow rounded-lg p-4 col-span-2 lg:col-span-1"><div class="text-xs text-gray-500 uppercase">Próximo vencimiento</div><div class="text-xl font-semibold">{{ $resumen['proximoVencimiento'] ?: 'Sin próximo vencimiento' }}</div></div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-x-auto">
                @if($cargos->isNotEmpty() || $errors->has('cargosSeleccionados'))
                    <div class="flex flex-wrap gap-2 items-center justify-between p-4 border-b">
                        @if($cargos->isNotEmpty())
                            <div class="flex gap-2">
                                <button type="button" wire:click="seleccionarTodosCargos" class="px-3 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Seleccionar todos</button>
                                <button type="button" wire:click="limpiarSeleccionCargos" class="px-3 py-2 border rounded text-gray-700 hover:bg-gray-50">Limpiar selección</button>
                            </div>
                        @else
                            <div></div>
                        @endif
                        @error('cargosSeleccionados') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs"><tr><th class="p-3 text-left">Aplicar</th><th class="p-3 text-left">Concepto</th><th class="p-3 text-left">Periodo</th><th class="p-3 text-left">Emisión</th><th class="p-3 text-left">Vencimiento</th><th class="p-3 text-right">Total original</th><th class="p-3 text-right">Saldo pendiente</th><th class="p-3 text-left">Importe a aplicar</th><th class="p-3 text-right">Saldo restante estimado</th><th class="p-3 text-left">Estado</th></tr></thead>
                    <tbody>
                    @forelse($cargos as $cargo)
                        @php($estadoClases=['pendiente'=>'bg-yellow-100 text-yellow-800','parcial'=>'bg-blue-100 text-blue-800','vencido'=>'bg-red-100 text-red-800'])
                        <tr class="border-t" wire:key="cargo-pago-{{ $cargo->cargo_id }}">
                            <td class="p-3"><input type="checkbox" aria-label="Aplicar cargo {{ $cargo->cargo_id }}" @checked(in_array($cargo->cargo_id, $cargosSeleccionados, true)) wire:click="{{ in_array($cargo->cargo_id, $cargosSeleccionados, true) ? 'deseleccionarCargo' : 'seleccionarCargo' }}({{ $cargo->cargo_id }})" /></td>
                            <td class="p-3">{{ $cargo->conceptoCobro?->nombre ?? $cargo->conceptoCobro?->clave ?? 'Sin concepto' }}</td>
                            <td class="p-3 whitespace-nowrap">{{ $cargo->periodo_anio && $cargo->periodo_mes ? sprintf('%04d-%02d', $cargo->periodo_anio, $cargo->periodo_mes) : 'Sin periodo' }}</td>
                            <td class="p-3 whitespace-nowrap">{{ $cargo->fecha_emision?->format('Y-m-d') ?? 'Sin fecha' }}</td>
                            <td class="p-3 whitespace-nowrap">{{ $cargo->fecha_vencimiento?->format('Y-m-d') ?? 'Sin fecha' }}</td>
                            <td class="p-3 text-right whitespace-nowrap">{{ $cargo->moneda }} ${{ $cargo->total }}</td>
                            <td class="p-3 text-right whitespace-nowrap font-semibold">{{ $cargo->moneda }} ${{ $cargo->saldo_pendiente }}</td>
                            <td class="p-3 min-w-[12rem]">
                                @if(in_array($cargo->cargo_id, $cargosSeleccionados, true))
                                    <input type="text" inputmode="decimal" wire:model.lazy="importesAplicar.{{ $cargo->cargo_id }}" class="w-full rounded border-gray-300" aria-label="Importe a aplicar al cargo {{ $cargo->cargo_id }}" />
                                    @error('importesAplicar.'.$cargo->cargo_id) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">{{ $cargo->moneda }} ${{ $resumenSeleccion['restantes'][$cargo->cargo_id] ?? $cargo->saldo_pendiente }}</td>
                            <td class="p-3"><span class="px-2 py-1 rounded {{ $estadoClases[$cargo->estado] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst($cargo->estado) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="p-8 text-center text-gray-500">No hay cargos pendientes.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                @if($cargos->isNotEmpty())
                    <div class="p-5 border-t bg-gray-50">
                        <h3 class="font-semibold text-gray-800">Resumen de la selección</h3>
                        <dl class="mt-3 grid grid-cols-2 lg:grid-cols-5 gap-4 text-sm">
                            <div><dt class="text-gray-500">Cargos</dt><dd class="font-semibold">{{ $resumenSeleccion['cantidad'] }}</dd></div>
                            <div><dt class="text-gray-500">Total a aplicar</dt><dd class="font-semibold">{{ $resumenSeleccion['moneda'] }} ${{ $resumenSeleccion['total'] }}</dd></div>
                            <div><dt class="text-gray-500">Moneda</dt><dd class="font-semibold">{{ $resumenSeleccion['moneda'] }}</dd></div>
                            <div><dt class="text-gray-500">Pagos parciales</dt><dd class="font-semibold">{{ $resumenSeleccion['tieneParciales'] ? 'Sí' : 'No' }}</dd></div>
                            <div><dt class="text-gray-500">Saldo restante estimado</dt><dd class="font-semibold">{{ $resumenSeleccion['moneda'] }} ${{ $resumenSeleccion['saldoRestante'] }}</dd></div>
                        </dl>
                    </div>
                @endif
            </div>

            <section class="bg-white rounded-lg shadow p-5 space-y-5">
                <div><h2 class="text-lg font-semibold text-gray-800">Información del pago</h2><p class="text-sm text-gray-500">Captura los datos para revisar la operación. En este paso no se guardará el pago.</p></div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div><x-forms.label value="Fecha y hora del pago" /><x-forms.input type="datetime-local" wire:model.lazy="fechaPago" class="w-full mt-1" />@error('fechaPago')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <div><x-forms.label value="Moneda" /><div class="mt-1 py-2 font-semibold">{{ $inscripcion->moneda }}</div></div>
                    <div><x-forms.label value="Método de pago" /><select wire:model="metodoPagoId" class="w-full mt-1 rounded border-gray-300"><option value="">Selecciona un método</option>@foreach($metodosPago as $metodo)<option value="{{ $metodo->getKey() }}" @disabled($metodo->requiere_anticipo_relacionado)>{{ $metodo->nombre }}{{ $metodo->requiere_anticipo_relacionado ? ' (próximamente)' : '' }}</option>@endforeach</select>@error('metodoPagoId')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                </div>
                @if($configuracionMetodo)
                    @if($configuracionMetodo['forma_pago_sat_fija'])<p class="text-sm"><span class="text-gray-500">Forma de pago SAT:</span> <strong>{{ $configuracionMetodo['forma_pago_sat_fija'] }}</strong></p>@endif
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($configuracionMetodo['campos'] as $campo => $config)
                        @if($campo === 'comprobante')
                            <div><x-forms.label value="Comprobante (PDF, JPG o PNG; máximo 10 MB)" /><input type="file" wire:model="comprobante" accept=".pdf,.jpg,.jpeg,.png" class="block mt-1 text-sm" />@error('comprobante')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                        @elseif($campo !== 'anticipo_relacionado_id')
                            <div><x-forms.label :value="$config['etiqueta']" /><x-forms.input type="{{ $config['control'] }}" wire:model.lazy="datosMetodo.{{ $campo }}" maxlength="{{ $config['maximo'] }}" class="w-full mt-1" />@error('datosMetodo.'.$campo)<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                        @endif
                    @endforeach
                    </div>
                @endif
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><x-forms.label value="Importe recibido" /><x-forms.input type="text" inputmode="decimal" wire:model.lazy="montoRecibido" class="w-full mt-1" />@error('montoRecibido')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <div><x-forms.label value="Observaciones" /><textarea wire:model.lazy="observaciones" maxlength="2000" rows="3" class="w-full mt-1 rounded border-gray-300"></textarea>@error('observaciones')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
                </div>
                @if($advertenciaDuplicidad)<p class="p-3 rounded bg-yellow-50 text-yellow-800">{{ $advertenciaDuplicidad }}</p>@endif
                <button type="button" wire:click="prepararPago" @disabled($resumenSeleccion['cantidad'] === 0) class="px-4 py-2 rounded bg-indigo-600 text-white disabled:opacity-50">Revisar pago</button>
            </section>

            @if($mostrarConfirmacion && $resumenConfirmacion)
                <section class="bg-indigo-50 border border-indigo-200 rounded-lg p-5" aria-label="Revisión del pago">
                    <h2 class="text-lg font-semibold text-indigo-900">Revisión del pago</h2><p class="text-sm text-indigo-700">Confirma que la información sea correcta. El pago todavía no se ha guardado.</p>
                    <dl class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div><dt class="text-gray-500">Alumno</dt><dd>{{ $resumenConfirmacion['alumno'] }}</dd></div><div><dt class="text-gray-500">Inscripción</dt><dd>#{{ $resumenConfirmacion['inscripcion'] }}</dd></div>
                        <div><dt class="text-gray-500">Responsable</dt><dd>{{ $resumenConfirmacion['responsable'] }}</dd></div><div><dt class="text-gray-500">Cargos</dt><dd>{{ $resumenConfirmacion['cantidad'] }}</dd></div>
                        <div><dt class="text-gray-500">Total recibido</dt><dd>{{ $resumenConfirmacion['moneda'] }} ${{ $resumenConfirmacion['totalRecibido'] }}</dd></div><div><dt class="text-gray-500">Total aplicado</dt><dd>{{ $resumenConfirmacion['moneda'] }} ${{ $resumenConfirmacion['totalAplicado'] }}</dd></div>
                        <div><dt class="text-gray-500">Saldo restante estimado</dt><dd>{{ $resumenConfirmacion['moneda'] }} ${{ $resumenConfirmacion['saldoRestante'] }}</dd></div><div><dt class="text-gray-500">Método</dt><dd>{{ $resumenConfirmacion['metodo'] }}</dd></div>
                        <div><dt class="text-gray-500">Fecha</dt><dd>{{ $resumenConfirmacion['fecha'] }} ({{ $resumenConfirmacion['zonaHoraria'] }})</dd></div><div><dt class="text-gray-500">Comprobante</dt><dd>{{ $resumenConfirmacion['comprobante'] ? 'Adjunto' : 'No requerido' }}</dd></div>
                        @foreach($resumenConfirmacion['datos'] as $campo => $valor)<div><dt class="text-gray-500">{{ $configuracionMetodo['campos'][$campo]['etiqueta'] ?? ($campo === 'forma_pago_sat' ? 'Forma de pago SAT' : ucfirst(str_replace('_', ' ', $campo))) }}</dt><dd>{{ $valor }}</dd></div>@endforeach
                    </dl>
                    @if($advertenciaDuplicidad)<p class="mt-4 p-3 rounded bg-yellow-100 text-yellow-900">{{ $advertenciaDuplicidad }}</p>@endif
                    <button type="button" wire:click="volverAEditar" class="mt-4 px-4 py-2 border rounded bg-white text-gray-700">Volver a editar</button>
                </section>
            @endif
        @endif
    </div>
</div>
