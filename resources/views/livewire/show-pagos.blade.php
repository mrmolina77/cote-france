<div>
    @section('content')<p>Pagos</p>@endsection
    <div class="mx-auto px-4 sm:px-6 lg:px-8 py-12">
        @if (session()->has('status'))<div class="mb-4 rounded bg-green-100 px-4 py-3 text-green-800">{{ session('status') }}</div>@endif
        @error('pagoCancelarId')<div class="mb-4 rounded bg-red-100 px-4 py-3 text-red-800">{{ $message }}</div>@enderror
        <x-table>
            <x-slot:header>
                <div class="space-y-3">
                    <div class="flex flex-wrap gap-3 items-center">
                        <div class="flex items-center"><span>Mostrar</span><x-select class="mx-2" wire:model="porPagina"><option value="10">10</option><option value="25">25</option><option value="50">50</option></x-select><span>filas</span></div>
                        <div class="flex-1 min-w-[16rem]"><x-forms.input class="w-full" type="search" maxlength="120" placeholder="Folio, inscripción, alumno, referencia o SPEI…" wire:model.debounce.300ms="busqueda" /></div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                        <x-select wire:model="estado" aria-label="Estado"><option value="todos">Todos los estados</option>@foreach(\App\Models\Pago::ESTADOS as $valor)<option value="{{ $valor }}">{{ ucfirst($valor) }}</option>@endforeach</x-select>
                        <x-select wire:model="metodoPagoId" aria-label="Método de pago"><option value="todos">Todos los métodos</option>@foreach($metodos as $metodo)<option value="{{ $metodo->metodo_pago_id }}">{{ $metodo->nombre }}</option>@endforeach</x-select>
                        <x-forms.input type="date" wire:model="fechaDesde" title="Fecha de pago desde" />
                        <x-forms.input type="date" wire:model="fechaHasta" title="Fecha de pago hasta" />
                        <a href="{{ route('facturacion.pagos.registrar') }}" class="text-center rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Registrar pago</a>
                    </div>
                </div>
            </x-slot:header>
            <div class="overflow-x-auto">
                <table class="items-center bg-transparent w-full border-collapse text-sm">
                    <thead><tr>@foreach(['Folio','Fecha','Alumno / inscripción','Responsable','Método','Monto','Estado','Confirmó','Acciones'] as $titulo)<th class="px-3 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase whitespace-nowrap">{{ $titulo }}</th>@endforeach</tr></thead>
                    <tbody>@forelse($pagos as $pago)
                        @php($estadoClases=['borrador'=>'bg-gray-100 text-gray-700','confirmado'=>'bg-green-100 text-green-800','cancelado'=>'bg-red-100 text-red-800','reembolsado'=>'bg-purple-100 text-purple-800'])
                        <tr wire:key="pago-{{ $pago->pago_id }}">
                            <td class="px-3 py-3 border-t font-mono">{{ $pago->folio }}</td>
                            <td class="px-3 py-3 border-t whitespace-nowrap">{{ optional($pago->fecha_pago)->format('Y-m-d H:i') }}</td>
                            <td class="px-3 py-3 border-t"><strong>{{ $pago->prospecto?->prospectos_nombres }} {{ $pago->prospecto?->prospectos_apellidos }}</strong><div class="text-xs text-gray-500">Inscripción #{{ $pago->inscripciones_id }}</div></td>
                            <td class="px-3 py-3 border-t">{{ $pago->responsablePago?->nombre_razon_social ?: 'Sin responsable' }}</td>
                            <td class="px-3 py-3 border-t">{{ $pago->metodoPago?->nombre ?: 'Sin método' }}</td>
                            <td class="px-3 py-3 border-t whitespace-nowrap">{{ $pago->moneda }} ${{ $pago->monto }}</td>
                            <td class="px-3 py-3 border-t"><span class="px-2 py-1 rounded {{ $estadoClases[$pago->estado] ?? 'bg-gray-100' }}">{{ ucfirst($pago->estado) }}</span></td>
                            <td class="px-3 py-3 border-t">{{ $pago->confirmedBy?->name ?: '—' }}</td>
                            <td class="px-3 py-3 border-t whitespace-nowrap"><button type="button" wire:click="verDetalle({{ $pago->pago_id }})" class="text-indigo-700 hover:underline">Detalle</button>@if($pago->estado === \App\Models\Pago::ESTADO_CONFIRMADO) @can('cancel-pagos')<button type="button" wire:click="prepararCancelacion({{ $pago->pago_id }})" class="ml-3 text-red-700 hover:underline">Cancelar</button>@endcan @endif</td>
                        </tr>
                    @empty<tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">No se encontraron pagos.</td></tr>@endforelse</tbody>
                </table>
            </div>
            @if($pagos->hasPages())<div class="px-6 py-3">{{ $pagos->links() }}</div>@endif
        </x-table>
    </div>

    <x-dialog-modal wire:model="mostrarModalDetalle">
        <x-slot name="title">Detalle del pago</x-slot>
        <x-slot name="content">@if($detalle)<div class="space-y-4 text-sm">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div><strong>Folio:</strong> {{ $detalle->folio }}</div><div><strong>Estado:</strong> {{ ucfirst($detalle->estado) }}</div><div><strong>Fecha:</strong> {{ optional($detalle->fecha_pago)->format('Y-m-d H:i:s') }} {{ $detalle->zona_horaria }}</div>
                <div><strong>Alumno:</strong> {{ $detalle->prospecto?->prospectos_nombres }} {{ $detalle->prospecto?->prospectos_apellidos }}</div><div><strong>Inscripción:</strong> #{{ $detalle->inscripciones_id }}</div><div><strong>Responsable:</strong> {{ $detalle->responsablePago?->nombre_razon_social ?: '—' }}</div>
                <div><strong>Método:</strong> {{ $detalle->metodoPago?->nombre ?: '—' }}</div><div><strong>Monto:</strong> {{ $detalle->moneda }} ${{ $detalle->monto }}</div><div><strong>Confirmó:</strong> {{ $detalle->confirmedBy?->name ?: '—' }} / {{ optional($detalle->fecha_confirmacion)->format('Y-m-d H:i:s') ?: '—' }}</div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2"><div><strong>Banco:</strong> {{ $detalle->banco ?: '—' }}</div><div><strong>Referencia:</strong> {{ $detalle->referencia ?: '—' }}</div><div><strong>Rastreo SPEI:</strong> {{ $detalle->rastreo_spei ?: '—' }}</div><div><strong>Cheque:</strong> {{ $detalle->numero_cheque ?: '—' }}</div><div><strong>Autorización:</strong> {{ $detalle->numero_autorizacion ?: '—' }}</div><div><strong>Transacción externa:</strong> {{ $detalle->identificador_transaccion_externa ?: '—' }}</div></div>
            <div><strong>Observaciones:</strong><p class="whitespace-pre-wrap">{{ $detalle->observaciones ?: '—' }}</p></div>
            @if($detalle->estado === \App\Models\Pago::ESTADO_CANCELADO)<div class="rounded bg-red-50 p-3"><strong>Cancelación</strong><p class="whitespace-pre-wrap">{{ $detalle->motivo_cancelacion }}</p><p>{{ $detalle->cancelledBy?->name ?: '—' }} / {{ optional($detalle->fecha_cancelacion)->format('Y-m-d H:i:s') ?: '—' }}</p></div>@endif
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr>@foreach(['Cargo','Concepto','Importe','Saldo anterior','Saldo posterior'] as $titulo)<th class="border p-2 text-left">{{ $titulo }}</th>@endforeach</tr></thead><tbody>@forelse($detalle->aplicaciones as $aplicacion)<tr><td class="border p-2">#{{ $aplicacion->cargo_id }}</td><td class="border p-2">{{ $aplicacion->cargo?->conceptoCobro?->nombre ?: '—' }}</td><td class="border p-2">${{ $aplicacion->importe_aplicado }}</td><td class="border p-2">${{ $aplicacion->saldo_anterior }}</td><td class="border p-2">${{ $aplicacion->saldo_posterior }}</td></tr>@empty<tr><td colspan="5" class="border p-3 text-center">Sin aplicaciones.</td></tr>@endforelse</tbody></table></div>
        </div>@endif</x-slot>
        <x-slot name="footer"><button type="button" wire:click="cerrarDetalle" class="px-4 py-2 border rounded">Cerrar</button></x-slot>
    </x-dialog-modal>

    <x-dialog-modal wire:model="mostrarModalCancelacion">
        <x-slot name="title">Cancelar pago</x-slot>
        <x-slot name="content">@if($pagoCancelar)<div class="space-y-4">
            <div class="rounded bg-yellow-50 p-3 text-sm text-yellow-900">Esta operación restaurará los saldos de los cargos. El pago, su folio y sus aplicaciones no se eliminarán.</div>
            <div class="grid grid-cols-2 gap-2 text-sm"><div><strong>Folio:</strong> {{ $pagoCancelar->folio }}</div><div><strong>Alumno:</strong> {{ $pagoCancelar->prospecto?->prospectos_nombres }} {{ $pagoCancelar->prospecto?->prospectos_apellidos }}</div><div><strong>Fecha:</strong> {{ optional($pagoCancelar->fecha_pago)->format('Y-m-d H:i') }}</div><div><strong>Método:</strong> {{ $pagoCancelar->metodoPago?->nombre }}</div><div><strong>Monto:</strong> {{ $pagoCancelar->moneda }} ${{ $pagoCancelar->monto }}</div></div>
            <div><x-forms.label value="Motivo de cancelación"/><textarea wire:model.defer="motivoCancelacion" maxlength="2000" rows="4" class="w-full rounded border-gray-300"></textarea>@error('motivoCancelacion')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror @error('pagoCancelarId')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror</div>
        </div>@endif</x-slot>
        <x-slot name="footer"><button type="button" wire:click="cerrarCancelacion" class="px-4 py-2 mr-2 border rounded">Volver</button><button type="button" wire:click="confirmarCancelacion" wire:loading.attr="disabled" wire:target="confirmarCancelacion" class="px-4 py-2 bg-red-600 text-white rounded disabled:opacity-50">Confirmar cancelación</button></x-slot>
    </x-dialog-modal>
</div>
