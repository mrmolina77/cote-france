<div>
    @section('content')<p>Conceptos de cobro</p>@endsection
    <div class="mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <x-table>
            <x-slot:header>
                <div class="flex flex-wrap gap-4 items-center">
                    <div class="flex items-center"><span>Mostrar</span>
                        <x-select class="mx-2" wire:model="cant"><option>10</option><option>25</option><option>50</option><option>100</option></x-select><span>filas</span>
                    </div>
                    <x-select wire:model="estado" aria-label="Filtrar por estado">
                        <option value="todos">Todos</option><option value="activos">Activos</option><option value="inactivos">Inactivos</option>
                    </x-select>
                    <div class="flex-1 min-w-[14rem]"><x-forms.input class="w-full" type="search" placeholder="Buscar por clave, nombre o descripción..." wire:model.debounce.300ms="search" /></div>
                    <button type="button" wire:click="create" wire:loading.attr="disabled" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50">
                        <i class="fas fa-plus mr-2"></i>Nuevo concepto
                    </button>
                </div>
            </x-slot:header>

            <div class="overflow-x-auto">
                <table class="items-center bg-transparent w-full border-collapse">
                    <thead><tr>
                        @foreach (['orden' => 'Orden', 'clave' => 'Clave interna', 'nombre' => 'Nombre'] as $column => $label)
                            <th wire:click="order('{{ $column }}')" class="cursor-pointer px-4 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase whitespace-nowrap text-left">{{ $label }} <i class="fas fa-sort float-right"></i></th>
                        @endforeach
                        <th class="px-4 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase whitespace-nowrap">Producto/servicio SAT</th>
                        <th class="px-4 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase whitespace-nowrap">Unidad SAT</th>
                        <th class="px-4 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase whitespace-nowrap">Objeto impuesto</th>
                        <th class="px-4 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase whitespace-nowrap">IVA</th>
                        <th wire:click="order('activo')" class="cursor-pointer px-4 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase whitespace-nowrap">Estado</th>
                        <th class="px-4 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase whitespace-nowrap">Acciones</th>
                    </tr></thead>
                    <tbody>
                    @forelse ($conceptos as $concepto)
                        <tr wire:key="concepto-cobro-{{ $concepto->concepto_cobro_id }}" class="{{ $concepto->activo ? '' : 'bg-gray-50 text-gray-500' }}">
                            <td class="px-4 py-3 border-t text-sm">{{ $concepto->orden }}</td>
                            <td class="px-4 py-3 border-t text-sm font-mono">{{ $concepto->clave }}</td>
                            <td class="px-4 py-3 border-t text-sm">{{ $concepto->nombre }}</td>
                            <td class="px-4 py-3 border-t text-sm">{{ $concepto->clave_producto_servicio_sat ?: 'Sin configurar' }}</td>
                            <td class="px-4 py-3 border-t text-sm">{{ $concepto->clave_unidad_sat ?: 'Sin configurar' }}</td>
                            <td class="px-4 py-3 border-t text-sm">{{ $concepto->objeto_impuesto_sat ?: 'Sin configurar' }}</td>
                            <td class="px-4 py-3 border-t text-sm">{{ $concepto->tasa_iva === null ? 'Sin configurar' : rtrim(rtrim(number_format((float) $concepto->tasa_iva * 100, 4, '.', ''), '0'), '.').'%' }}</td>
                            <td class="px-4 py-3 border-t text-sm"><span class="px-2 py-1 rounded {{ $concepto->activo ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">{{ $concepto->activo ? 'Activo' : 'Inactivo' }}</span></td>
                            <td class="px-4 py-3 border-t text-sm whitespace-nowrap">
                                <button type="button" wire:click="edit({{ $concepto->concepto_cobro_id }})" wire:loading.attr="disabled" class="text-emerald-600 mr-3"><i class="fas fa-pen"></i> Editar</button>
                                <button type="button" onclick="confirm('¿Desea {{ $concepto->activo ? 'desactivar' : 'activar' }} este concepto?') || event.stopImmediatePropagation()" wire:click="{{ $concepto->activo ? 'desactivar' : 'activar' }}({{ $concepto->concepto_cobro_id }})" wire:loading.attr="disabled" class="{{ $concepto->activo ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $concepto->activo ? 'Desactivar' : 'Activar' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">No se encontraron conceptos de cobro.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($conceptos->hasPages())<div class="px-6 py-3">{{ $conceptos->links() }}</div>@endif
        </x-table>
    </div>

    <x-dialog-modal wire:model="open_form">
        <x-slot name="title">{{ $editingId ? 'Editar concepto de cobro' : 'Crear concepto de cobro' }}</x-slot>
        <x-slot name="content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><x-forms.label value="Clave interna" /><x-forms.input class="w-full" wire:model.defer="clave" maxlength="50" :readonly="$editingId !== null" />@error('clave')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror</div>
                <div><x-forms.label value="Nombre" /><x-forms.input class="w-full" wire:model.defer="nombre" maxlength="120" />@error('nombre')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror</div>
                <div class="md:col-span-2"><x-forms.label value="Descripción" /><textarea wire:model.defer="descripcion" class="w-full rounded border-gray-300"></textarea>@error('descripcion')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror</div>
                <div><x-forms.label value="Clave producto/servicio SAT" /><x-forms.input class="w-full" wire:model.defer="clave_producto_servicio_sat" maxlength="20" />@error('clave_producto_servicio_sat')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror</div>
                <div><x-forms.label value="Clave unidad SAT" /><x-forms.input class="w-full" wire:model.defer="clave_unidad_sat" maxlength="10" />@error('clave_unidad_sat')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror</div>
                <div><x-forms.label value="Objeto de impuesto SAT" /><x-forms.input class="w-full" wire:model.defer="objeto_impuesto_sat" maxlength="10" />@error('objeto_impuesto_sat')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror</div>
                <div><x-forms.label value="Tasa de IVA (0 a 1)" /><x-forms.input type="number" min="0" max="1" step="0.000001" class="w-full" wire:model.defer="tasa_iva" />@error('tasa_iva')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror</div>
                <div class="md:col-span-2 text-xs text-gray-500 rounded bg-blue-50 p-3">La configuración fiscal se utilizará en una etapa futura. Esta pantalla todavía no genera CFDI ni valida claves contra catálogos externos del SAT.</div>
                <div><x-forms.label value="Orden" /><x-forms.input type="number" min="0" max="65535" class="w-full" wire:model.defer="orden" />@error('orden')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror</div>
                <label class="flex items-center mt-6"><input type="checkbox" wire:model.defer="activo" class="rounded border-gray-300 text-indigo-600"><span class="ml-2">Activo</span></label>
            </div>
        </x-slot>
        <x-slot name="footer">
            <button type="button" wire:click="closeForm" class="px-4 py-2 mr-2 border rounded">Cancelar</button>
            <button type="button" wire:click="{{ $editingId ? 'update' : 'store' }}" wire:loading.attr="disabled" class="px-4 py-2 bg-indigo-600 text-white rounded disabled:opacity-50">
                <span wire:loading.remove wire:target="store,update">Guardar</span><span wire:loading wire:target="store,update">Guardando…</span>
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
