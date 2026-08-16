<section><h3 class="font-semibold text-blue-700 mb-3">Responsable de pago</h3>
    <div class="space-y-3"><x-select class="w-full" wire:model="responsable_opcion">
        @if(isset($permitirConservar) && $permitirConservar)<option value="conservar">Conservar responsable actual</option>@endif
        <option value="alumno">El propio alumno</option><option value="existente">Responsable existente</option><option value="nuevo">Crear responsable nuevo</option>
    </x-select><x-forms.input-error for="responsable_opcion"/>
    @if($responsable_opcion === 'existente')<label>Responsable activo<x-select class="w-full" wire:model.defer="responsable_pago_id"><option value="">Seleccionar</option>@foreach($responsables as $r)<option value="{{$r->responsable_pago_id}}">{{$r->nombre_razon_social}} ({{$r->tipo}}){{$r->telefono ? ' · '.$r->telefono : ''}}</option>@endforeach</x-select><x-forms.input-error for="responsable_pago_id"/></label>@endif
    @if($responsable_opcion === 'nuevo')<div class="grid md:grid-cols-2 gap-4">
        <label>Tipo<x-select class="w-full" wire:model.defer="responsable_tipo"><option value="persona">Persona</option><option value="empresa">Empresa</option></x-select><x-forms.input-error for="responsable_tipo"/></label>
        <label>Nombre o razón social<x-forms.input class="w-full" wire:model.defer="responsable_nombre"/><x-forms.input-error for="responsable_nombre"/></label>
        <label>Teléfono<x-forms.input class="w-full" wire:model.defer="responsable_telefono"/><x-forms.input-error for="responsable_telefono"/></label>
        <label>Correo<x-forms.input type="email" class="w-full" wire:model.defer="responsable_correo"/><x-forms.input-error for="responsable_correo"/></label>
    </div>@endif
    @if($responsable_opcion === 'alumno')<p class="text-sm text-gray-500">Se reutilizará el responsable activo vinculado al alumno o se creará al guardar.</p>@endif
    </div>
</section>
