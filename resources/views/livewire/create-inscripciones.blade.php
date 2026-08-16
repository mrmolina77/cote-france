<div>
    <button wire:click="$set('open',true)" class="bg-indigo-500 text-white text-xs font-bold uppercase px-3 py-1 rounded" type="button">{{__('Add registration')}}</button>
    <x-dialog-modal wire:model="open">
        <x-slot name="title">{{__('Create registration')}}</x-slot>
        <x-slot name="content">
            <div class="space-y-6 max-h-[70vh] overflow-y-auto px-1">
                <section><h3 class="font-semibold text-blue-700 mb-3">Datos de la inscripción</h3><div class="grid md:grid-cols-2 gap-4">
                    <label>Prospecto<x-select class="w-full" wire:model="prospectos_id"><option value="">Seleccionar</option>@foreach($prospectos as $item)<option value="{{$item->prospectos_id}}">{{$item->prospectos_nombres}} {{$item->prospectos_apellidos}}</option>@endforeach</x-select><x-forms.input-error for="prospectos_id"/></label>
                    <label>Curso<x-select class="w-full" wire:model.defer="cursos_id"><option value="">Seleccionar</option>@foreach($cursos as $item)<option value="{{$item->cursos_id}}">{{$item->cursos_descripcion}}</option>@endforeach</x-select><x-forms.input-error for="cursos_id"/></label>
                    <label>Grupo<x-select class="w-full" wire:model.defer="grupo_id"><option value="">Seleccionar</option>@foreach($grupos as $item)<option value="{{$item->grupo_id}}">{{$item->grupo_nombre}}</option>@endforeach</x-select><x-forms.input-error for="grupo_id"/></label>
                    <label>Fecha de inscripción<x-forms.input type="date" class="w-full" wire:model="fecha_inscripcion"/><x-forms.input-error for="fecha_inscripcion"/></label>
                    <label>Estatus<x-select class="w-full" wire:model.defer="estatus">@foreach(['activa'=>'Activa','suspendida'=>'Suspendida','finalizada'=>'Finalizada','cancelada'=>'Cancelada'] as $v=>$t)<option value="{{$v}}">{{$t}}</option>@endforeach</x-select><x-forms.input-error for="estatus"/></label>
                    <label>Fecha de inicio<x-forms.input type="date" class="w-full" wire:model.defer="fecha_inicio"/><x-forms.input-error for="fecha_inicio"/></label>
                    <label>Fecha de finalización<x-forms.input type="date" class="w-full" wire:model.defer="fecha_fin"/><x-forms.input-error for="fecha_fin"/></label>
                </div></section>
                <section><h3 class="font-semibold text-blue-700 mb-3">Condiciones económicas</h3><div class="grid md:grid-cols-2 gap-4">
                    <label>Moneda<x-forms.input class="w-full bg-gray-100" value="MXN" disabled/></label>
                    @foreach(['monto_inscripcion'=>'Monto de inscripción','monto_mensualidad'=>'Monto de mensualidad','dia_vencimiento'=>'Día de vencimiento','numero_mensualidades'=>'Número de mensualidades','descuento'=>'Descuento (%)','beca'=>'Beca (%)'] as $field=>$label)
                    <label>{{$label}}<x-forms.input type="number" step="{{$field === 'dia_vencimiento' || $field === 'numero_mensualidades' ? '1' : '0.01'}}" class="w-full" wire:model.defer="{{$field}}"/><x-forms.input-error for="{{$field}}"/></label>@endforeach
                    <label class="md:col-span-2">Observaciones financieras<textarea class="w-full rounded border-gray-300" wire:model.defer="observaciones_financieras"></textarea><x-forms.input-error for="observaciones_financieras"/></label>
                </div></section>
                @include('livewire.partials.responsable-pago-fields')
            </div>
        </x-slot>
        <x-slot name="footer"><x-forms.red-button wire:click="$set('open',false)">{{__('Cancel')}}</x-forms.red-button><x-forms.blue-button wire:click="save" wire:loading.attr="disabled">{{__('Create')}}</x-forms.blue-button></x-slot>
    </x-dialog-modal>
</div>
