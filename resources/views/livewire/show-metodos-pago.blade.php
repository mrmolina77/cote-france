<div>
    @section('content')<p>Métodos de pago</p>@endsection
    <div class="mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <x-table>
            <x-slot:header>
                <div class="flex flex-wrap gap-4 items-center">
                    <div class="flex items-center"><span>Mostrar</span><x-select class="mx-2" wire:model="cant"><option>10</option><option>25</option><option>50</option><option>100</option></x-select><span>filas</span></div>
                    <x-select wire:model="estado" aria-label="Filtrar por estado"><option value="todos">Todos</option><option value="activos">Activos</option><option value="inactivos">Inactivos</option></x-select>
                    <div class="flex-1 min-w-[14rem]"><x-forms.input class="w-full" type="search" placeholder="Buscar por clave, nombre, descripción o clave SAT..." wire:model.debounce.300ms="search" /></div>
                    <button type="button" wire:click="create" wire:loading.attr="disabled" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50"><i class="fas fa-plus mr-2"></i>Nuevo método</button>
                </div>
            </x-slot:header>
            <div class="overflow-x-auto">
                <table class="items-center bg-transparent w-full border-collapse">
                    <thead><tr>
                        @foreach (['orden' => 'Orden', 'clave' => 'Clave interna', 'nombre' => 'Nombre', 'clave_forma_pago_sat' => 'Clave SAT'] as $column => $label)
                            <th wire:click="order('{{ $column }}')" class="cursor-pointer px-4 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase whitespace-nowrap text-left">{{ $label }} <i class="fas fa-sort float-right"></i></th>
                        @endforeach
                        <th class="px-4 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase text-left">Datos requeridos</th>
                        <th wire:click="order('activo')" class="cursor-pointer px-4 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase">Estado</th>
                        <th class="px-4 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase">Acciones</th>
                    </tr></thead>
                    <tbody>
                    @forelse ($metodos as $metodo)
                        <tr wire:key="metodo-pago-{{ $metodo->metodo_pago_id }}" class="{{ $metodo->activo ? '' : 'bg-gray-50 text-gray-500' }}">
                            <td class="px-4 py-3 border-t text-sm">{{ $metodo->orden }}</td><td class="px-4 py-3 border-t text-sm font-mono">{{ $metodo->clave }}</td><td class="px-4 py-3 border-t text-sm">{{ $metodo->nombre }}</td><td class="px-4 py-3 border-t text-sm font-mono">{{ $metodo->clave_forma_pago_sat ?: 'Sin configurar' }}</td>
                            <td class="px-4 py-3 border-t text-sm"><div class="flex flex-wrap gap-1">@php($hasRequirements = false) @foreach ($requirementLabels as $field => $label) @if ($metodo->{$field}) @php($hasRequirements = true)<span class="px-2 py-1 rounded bg-blue-100 text-blue-700 text-xs">{{ $label }}</span>@endif @endforeach @unless($hasRequirements)<span class="text-gray-500">Sin datos adicionales</span>@endunless</div></td>
                            <td class="px-4 py-3 border-t text-sm"><span class="px-2 py-1 rounded {{ $metodo->activo ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">{{ $metodo->activo ? 'Activo' : 'Inactivo' }}</span></td>
                            <td class="px-4 py-3 border-t text-sm whitespace-nowrap"><button type="button" wire:click="edit({{ $metodo->metodo_pago_id }})" class="text-emerald-600 mr-3"><i class="fas fa-pen"></i> Editar</button><button type="button" onclick="confirmarEstadoMetodoPago({{ $metodo->metodo_pago_id }}, {{ $metodo->activo ? 'true' : 'false' }})" class="{{ $metodo->activo ? 'text-red-600' : 'text-green-600' }}">{{ $metodo->activo ? 'Desactivar' : 'Activar' }}</button></td>
                        </tr>
                    @empty<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No se encontraron métodos de pago.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
            @if ($metodos->hasPages())<div class="px-6 py-3">{{ $metodos->links() }}</div>@endif
        </x-table>
    </div>

    @if ($open_form)
    <x-dialog-modal wire:model="open_form">
        <x-slot name="title">{{ $editingId ? 'Editar método de pago' : 'Crear método de pago' }}</x-slot>
        <x-slot name="content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><x-forms.label value="Clave interna" /><x-forms.input class="w-full" wire:model.defer="clave" maxlength="50" :readonly="$editingId !== null" />@error('clave')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror</div>
                <div><x-forms.label value="Nombre visible" /><x-forms.input class="w-full" wire:model.defer="nombre" maxlength="120" />@error('nombre')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror</div>
                <div class="md:col-span-2"><x-forms.label value="Descripción" /><textarea wire:model.defer="descripcion" class="w-full rounded border-gray-300"></textarea>@error('descripcion')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror</div>
                <div><x-forms.label value="Clave de forma de pago SAT" /><x-forms.input class="w-full" wire:model.defer="clave_forma_pago_sat" maxlength="2" inputmode="numeric" placeholder="Ej. 01" />@error('clave_forma_pago_sat')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror</div>
                <div><x-forms.label value="Orden" /><x-forms.input type="number" min="0" max="65535" class="w-full" wire:model.defer="orden" />@error('orden')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror</div>
                <label class="flex items-center"><input type="checkbox" wire:model.defer="activo" class="rounded border-gray-300 text-indigo-600"><span class="ml-2">Activo</span></label>
                <fieldset class="md:col-span-2 border rounded p-4"><legend class="font-semibold px-2">Datos requeridos al registrar un pago</legend><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">@foreach ($requirementLabels as $field => $label)<div><label class="flex items-center"><input type="checkbox" wire:model.defer="{{ $field }}" class="rounded border-gray-300 text-indigo-600"><span class="ml-2">{{ $label }}</span></label>@error($field)<p class="text-red-600 text-xs">{{ $message }}</p>@enderror</div>@endforeach</div><p class="mt-4 text-xs text-gray-500 rounded bg-blue-50 p-3">Estos indicadores preparan el comportamiento futuro del formulario de pagos. En este bloque todavía no se implementa la captura dinámica.</p></fieldset>
            </div>
        </x-slot>
        <x-slot name="footer"><button type="button" wire:click="closeForm" class="px-4 py-2 mr-2 border rounded">Cancelar</button><button type="button" wire:click="{{ $editingId ? 'update' : 'store' }}" wire:loading.attr="disabled" class="px-4 py-2 bg-indigo-600 text-white rounded disabled:opacity-50"><span wire:loading.remove wire:target="store,update">Guardar</span><span wire:loading wire:target="store,update">Guardando…</span></button></x-slot>
    </x-dialog-modal>
    @endif
</div>

@push('js')
<script>
window.confirmarEstadoMetodoPago = function (id, activo) {
    const desactivar = activo === true;
    Swal.fire({title: desactivar ? '¿Desactivar método de pago?' : '¿Activar método de pago?', text: desactivar ? 'El método dejará de estar disponible para nuevos pagos.' : 'El método volverá a estar disponible para nuevos pagos.', icon: 'warning', showCancelButton: true, confirmButtonColor: desactivar ? '#dc2626' : '#16a34a', cancelButtonColor: '#6b7280', confirmButtonText: desactivar ? 'Sí, desactivar' : 'Sí, activar', cancelButtonText: 'Cancelar'}).then((result) => { if (result.isConfirmed) Livewire.emit(desactivar ? 'desactivarMetodoPagoConfirmado' : 'activarMetodoPagoConfirmado', Number(id)); });
};
</script>
@endpush
