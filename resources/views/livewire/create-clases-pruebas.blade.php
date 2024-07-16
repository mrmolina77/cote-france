<div>

    <button wire:click="$set('open',true)" class="bg-indigo-500 text-white active:bg-indigo-600 text-xs font-bold uppercase px-3 py-1 rounded outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150" type="button">
        Agregar Clase de Prueba
    </button>

    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            Crear clase de prueba
        </x-slot>
        <x-slot name="content">
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Fecha: " />
                    <x-forms.input type="date" class="flex-1 ml-4" wire:model="clasespruebas_fecha"/>
                </div>
                <x-forms.input-error for="clasespruebas_fecha"/>
           </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Hora de Inicio: " />
                    <x-forms.input type="time" class="flex-1 ml-4" wire:model="clasespruebas_hora_inicio"/>
                </div>
                <x-forms.input-error for="clasespruebas_hora_inicio"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Hora de Fin: " />
                    <x-forms.input type="time" class="flex-1 ml-4" wire:model="clasespruebas_hora_fin"/>
                </div>
                <x-forms.input-error for="clasespruebas_hora_fin"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Profesor: " />
                    <x-select class="flex-1 ml-4" wire:model="profesores_id">
                        <option value="">Seleccionar</option>
                        @forelse ($profesores as $item)
                        <option value="{{$item->profesores_id}}">{{$item->profesores_nombres}} {{$item->profesores_apellidos}}</option>
                        @empty
                        <option value="">Sin profesores</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="profesores_id"/>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-forms.red-button wire:click="$set('open',false)">
                Cancelar
            </x-forms.red-button>
            <x-forms.blue-button wire:click="save"  wire:loading.attr="disabled" wire:target="save" class="disabled:opacity-65">
                Crear Clases de Pruebas
            </x-forms.blue-button>
            {{-- <span wire:loading wire:target="save">Cargando...</span> --}}
        </x-slot>
    </x-dialog-modal>
</div>
