<div>
    @section('content')<p>Perfiles fiscales</p>@endsection
    <div class="mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-5">
        <div class="bg-amber-50 border border-amber-200 rounded p-4 text-sm text-amber-900"><strong>Preparación fiscal:</strong> guardar estos datos no los verifica ante el SAT ni emite una factura o CFDI. La fecha de revisión es únicamente la capturada por el usuario autorizado.</div>
        <div class="bg-white shadow rounded p-5">
            <h1 class="text-2xl font-semibold">Perfiles fiscales por alumno</h1>
            <label class="block mt-4 text-sm font-medium">Buscar alumno</label><input wire:model.debounce.350ms="busqueda" class="mt-1 w-full rounded border-gray-300" placeholder="Nombre o apellido">
            @if($busqueda !== '')<div class="mt-2 border rounded divide-y">@forelse($alumnos as $item)<button type="button" wire:click="seleccionarAlumno({{ $item->getKey() }})" class="block w-full text-left p-2 hover:bg-gray-50">{{ $item->prospectos_nombres }} {{ $item->prospectos_apellidos }}</button>@empty<p class="p-2 text-gray-500">Sin resultados.</p>@endforelse</div>@endif
        </div>
        @if($alumno)
        <div class="bg-white shadow rounded p-5"><div class="flex justify-between"><h2 class="font-semibold">{{ $alumno->prospectos_nombres }} {{ $alumno->prospectos_apellidos }}</h2><button wire:click="create" class="bg-blue-600 text-white rounded px-4 py-2">Nuevo perfil</button></div>
            <div class="overflow-x-auto mt-4"><table class="min-w-full text-sm"><thead><tr class="text-left border-b"><th class="p-2">Receptor</th><th>RFC</th><th>Relación</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>@forelse($perfiles as $perfil)<tr class="border-b"><td class="p-2">{{ $perfil->nombre_razon_social }}</td><td>{{ $perfil->rfc }}</td><td>{{ $perfil->relacion_alumno }}</td><td>{{ $perfil->activo ? 'Activo' : 'Inactivo' }}{{ $perfil->predeterminado ? ' · Predeterminado' : '' }}</td><td class="space-x-2"><button wire:click="edit({{ $perfil->getKey() }})" class="text-blue-700">Editar</button>@if($perfil->activo)<button wire:click="desactivar({{ $perfil->getKey() }})" class="text-red-700">Desactivar</button>@unless($perfil->predeterminado)<button wire:click="predeterminar({{ $perfil->getKey() }})" class="text-green-700">Predeterminar</button>@endunless @else<button wire:click="activar({{ $perfil->getKey() }})" class="text-green-700">Activar</button>@endif</td></tr>@empty<tr><td colspan="5" class="p-4 text-gray-500">Este alumno no tiene perfiles fiscales.</td></tr>@endforelse</tbody></table></div>{{ $perfiles->links() }}
        </div>
        @endif
        @if($open_form)<div class="bg-white shadow rounded p-5"><h2 class="font-semibold text-lg">{{ $editingId ? 'Editar' : 'Crear' }} perfil</h2><form wire:submit.prevent="save" class="grid md:grid-cols-2 gap-4 mt-4">
            <div><label>Tipo de persona</label><select wire:model="tipo_persona" class="w-full rounded border-gray-300"><option value="fisica">Persona física</option><option value="moral">Persona moral</option></select>@error('tipo_persona')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror</div>
            @foreach(['rfc'=>'RFC (formato estructural)','nombre_razon_social'=>'Nombre o razón social','codigo_postal_fiscal'=>'Código postal fiscal','regimen_fiscal'=>'Clave de régimen (sin catálogo SAT)','uso_cfdi'=>'Clave de uso CFDI (sin catálogo SAT)','correo_facturacion'=>'Correo de facturación','relacion_alumno'=>'Relación con el alumno','curp'=>'CURP (opcional)','nivel_educativo'=>'Nivel educativo','rvoe'=>'RVOE'] as $campo=>$etiqueta)<div><label>{{ $etiqueta }}</label><input wire:model.defer="{{ $campo }}" class="w-full rounded border-gray-300">@error($campo)<p class="text-red-600 text-sm">{{ $message }}</p>@enderror</div>@endforeach
            <div><label>Fecha de revisión capturada</label><input type="date" wire:model.defer="fecha_validacion" class="w-full rounded border-gray-300">@error('fecha_validacion')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror</div>
            <div class="space-x-4"><label><input type="checkbox" wire:model="activo"> Activo</label><label><input type="checkbox" wire:model="predeterminado"> Predeterminado</label></div>
            <div class="md:col-span-2 space-x-2"><button class="bg-blue-600 text-white rounded px-4 py-2">Guardar</button><button type="button" wire:click="cancel" class="border rounded px-4 py-2">Cancelar</button></div>
        </form></div>@endif
    </div>
</div>
