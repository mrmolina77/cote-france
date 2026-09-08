<div>
    @section('content')<p>Cargos</p>@endsection
    <div class="mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <x-table>
            <x-slot:header>
                <div class="space-y-3">
                    <div class="flex flex-wrap gap-3 items-center">
                        <div class="flex items-center"><span>Mostrar</span><x-select class="mx-2" wire:model="cant"><option>10</option><option>25</option><option>50</option><option>100</option></x-select><span>filas</span></div>
                        <div class="flex-1 min-w-[16rem]"><x-forms.input class="w-full" type="search" placeholder="Buscar cargo, alumno, concepto u observaciones…" wire:model.debounce.300ms="search" /></div>
                        <button type="button" wire:click="create" wire:loading.attr="disabled" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50"><i class="fas fa-plus mr-2"></i>Nuevo cargo extraordinario</button>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-2">
                        <x-select wire:model="estado" aria-label="Estado"><option value="todos">Todos los estados</option>@foreach(\App\Models\Cargo::ESTADOS as $valor)<option value="{{ $valor }}">{{ ucfirst($valor) }}</option>@endforeach</x-select>
                        <x-select wire:model="origen" aria-label="Origen"><option value="todos">Todos los orígenes</option>@foreach(\App\Models\Cargo::ORIGENES as $valor)<option value="{{ $valor }}">{{ ucfirst($valor) }}</option>@endforeach</x-select>
                        <x-select wire:model="concepto" aria-label="Concepto"><option value="todos">Todos los conceptos</option>@foreach($conceptosFiltro as $item)<option value="{{ $item->concepto_cobro_id }}">{{ $item->clave }}</option>@endforeach</x-select>
                        <x-forms.input type="number" wire:model="periodo_anio_filtro" placeholder="Año" min="1" max="65535" />
                        <x-select wire:model="periodo_mes_filtro" aria-label="Mes"><option value="">Todos los meses</option>@for($mes=1;$mes<=12;$mes++)<option value="{{ $mes }}">{{ str_pad($mes, 2, '0', STR_PAD_LEFT) }}</option>@endfor</x-select>
                        <x-forms.input type="date" wire:model="vencimiento_desde" title="Vencimiento desde" />
                        <x-forms.input type="date" wire:model="vencimiento_hasta" title="Vencimiento hasta" />
                    </div>
                </div>
            </x-slot:header>
            <div class="overflow-x-auto">
                <table class="items-center bg-transparent w-full border-collapse text-sm">
                    <thead><tr>
                        @foreach(['cargo_id'=>'ID','fecha_emision'=>'Emisión','fecha_vencimiento'=>'Vencimiento'] as $column=>$label)<th wire:click="order('{{ $column }}')" class="cursor-pointer px-3 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase whitespace-nowrap">{{ $label }} <i class="fas fa-sort"></i></th>@endforeach
                        <th class="px-3 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase">Alumno / inscripción</th><th class="px-3 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase">Concepto</th>
                        <th wire:click="order('periodo_anio')" class="cursor-pointer px-3 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase">Periodo</th>
                        <th wire:click="order('total')" class="cursor-pointer px-3 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase">Total</th><th wire:click="order('saldo_pendiente')" class="cursor-pointer px-3 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase">Saldo</th>
                        <th wire:click="order('estado')" class="cursor-pointer px-3 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase">Estado</th><th wire:click="order('origen')" class="cursor-pointer px-3 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase">Origen</th><th class="px-3 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase">Creado por / observaciones</th>
                    </tr></thead>
                    <tbody>@forelse($cargos as $cargo)
                        @php($estadoClases=['pendiente'=>'bg-yellow-100 text-yellow-800','parcial'=>'bg-blue-100 text-blue-800','pagado'=>'bg-green-100 text-green-800','vencido'=>'bg-red-100 text-red-800','cancelado'=>'bg-gray-200 text-gray-700'])
                        <tr wire:key="cargo-{{ $cargo->cargo_id }}">
                            <td class="px-3 py-3 border-t font-mono">{{ $cargo->cargo_id }}</td><td class="px-3 py-3 border-t whitespace-nowrap">{{ optional($cargo->fecha_emision)->format('Y-m-d') }}</td><td class="px-3 py-3 border-t whitespace-nowrap">{{ optional($cargo->fecha_vencimiento)->format('Y-m-d') }}</td>
                            <td class="px-3 py-3 border-t"><strong>{{ $cargo->inscripcion?->prospecto?->prospectos_nombres }} {{ $cargo->inscripcion?->prospecto?->prospectos_apellidos }}</strong><div class="text-xs text-gray-500">Inscripción #{{ $cargo->inscripciones_id }}</div></td>
                            <td class="px-3 py-3 border-t"><span class="font-mono text-xs">{{ $cargo->conceptoCobro?->clave }}</span><br>{{ $cargo->conceptoCobro?->nombre }}</td>
                            <td class="px-3 py-3 border-t whitespace-nowrap">{{ $cargo->periodo_anio && $cargo->periodo_mes ? sprintf('%04d-%02d',$cargo->periodo_anio,$cargo->periodo_mes) : 'Sin periodo' }}</td>
                            <td class="px-3 py-3 border-t whitespace-nowrap">{{ $cargo->moneda }} ${{ $cargo->total }}</td><td class="px-3 py-3 border-t whitespace-nowrap">{{ $cargo->moneda }} ${{ $cargo->saldo_pendiente }}</td>
                            <td class="px-3 py-3 border-t"><span class="px-2 py-1 rounded {{ $estadoClases[$cargo->estado] ?? 'bg-gray-100' }}">{{ ucfirst($cargo->estado) }}</span></td><td class="px-3 py-3 border-t"><span class="px-2 py-1 rounded {{ $cargo->origen === 'manual' ? 'bg-purple-100 text-purple-800' : 'bg-cyan-100 text-cyan-800' }}">{{ ucfirst($cargo->origen) }}</span></td>
                            <td class="px-3 py-3 border-t max-w-xs"><div>{{ $cargo->createdBy?->name ?? 'Sistema' }}</div><div class="text-xs text-gray-500 truncate" title="{{ $cargo->observaciones }}">{{ $cargo->observaciones ?: 'Sin observaciones' }}</div></td>
                        </tr>
                    @empty<tr><td colspan="11" class="px-4 py-8 text-center text-gray-500">No se encontraron cargos.</td></tr>@endforelse</tbody>
                </table>
            </div>
            @if($cargos->hasPages())<div class="px-6 py-3">{{ $cargos->links() }}</div>@endif
        </x-table>
    </div>

    <x-dialog-modal wire:model="open_form">
        <x-slot name="title">Nuevo cargo extraordinario</x-slot>
        <x-slot name="content"><div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2"><x-forms.label value="Buscar inscripción por alumno o ID"/><x-forms.input class="w-full" type="search" wire:model.debounce.300ms="busqueda_inscripcion" placeholder="Escriba nombre, apellido o ID"/></div>
            <div class="md:col-span-2"><x-forms.label value="Inscripción"/><x-select class="w-full" wire:model.defer="inscripciones_id"><option value="">Seleccione una inscripción</option>@foreach($inscripciones as $item)<option value="{{ $item->inscripciones_id }}">#{{ $item->inscripciones_id }} — {{ $item->prospecto->prospectos_nombres }} {{ $item->prospecto->prospectos_apellidos }} — {{ $item->grupo->grupo_nombre }} / {{ $item->cursos->cursos_descripcion }}</option>@endforeach</x-select>@error('inscripciones_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror<p class="text-xs text-gray-500 mt-1">Se muestran como máximo 25 coincidencias.</p></div>
            <div class="md:col-span-2"><x-forms.label value="Concepto de cobro"/><x-select class="w-full" wire:model.defer="concepto_cobro_id"><option value="">Seleccione un concepto</option>@foreach($conceptosManuales as $item)<option value="{{ $item->concepto_cobro_id }}">{{ $item->clave }} — {{ $item->nombre }}</option>@endforeach</x-select>@error('concepto_cobro_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror</div>
            <div><x-forms.label value="Fecha de emisión"/><x-forms.input class="w-full" type="date" wire:model.defer="fecha_emision"/>@error('fecha_emision')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror</div><div><x-forms.label value="Fecha de vencimiento"/><x-forms.input class="w-full" type="date" wire:model.defer="fecha_vencimiento"/>@error('fecha_vencimiento')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror</div>
            <div><x-forms.label value="Importe (MXN)"/><x-forms.input class="w-full" type="text" inputmode="decimal" wire:model.defer="subtotal" placeholder="0.00"/>@error('subtotal')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror</div><div class="flex items-end pb-2 text-sm text-gray-600">Moneda: <strong class="ml-1">MXN</strong></div>
            <div><x-forms.label value="Año del periodo (opcional)"/><x-forms.input class="w-full" type="number" min="1" max="65535" wire:model.defer="periodo_anio"/>@error('periodo_anio')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror</div><div><x-forms.label value="Mes del periodo (opcional)"/><x-select class="w-full" wire:model.defer="periodo_mes"><option value="">Sin periodo</option>@for($mes=1;$mes<=12;$mes++)<option value="{{ $mes }}">{{ str_pad($mes,2,'0',STR_PAD_LEFT) }}</option>@endfor</x-select>@error('periodo_mes')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror</div>
            <div class="md:col-span-2"><x-forms.label value="Observaciones"/><textarea class="w-full rounded border-gray-300" maxlength="1000" rows="3" wire:model.defer="observaciones"></textarea>@error('observaciones')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror</div>
            @error('formulario')<p class="md:col-span-2 text-red-600 text-sm">{{ $message }}</p>@enderror
        </div></x-slot>
        <x-slot name="footer"><button type="button" wire:click="closeForm" class="px-4 py-2 mr-2 border rounded">Cancelar</button><button type="button" wire:click="store" wire:loading.attr="disabled" wire:target="store" class="px-4 py-2 bg-indigo-600 text-white rounded disabled:opacity-50"><span wire:loading.remove wire:target="store">Crear cargo</span><span wire:loading wire:target="store">Creando…</span></button></x-slot>
    </x-dialog-modal>
</div>
