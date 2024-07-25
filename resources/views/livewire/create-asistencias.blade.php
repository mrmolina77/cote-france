<div>
    <button wire:click="$set('open',true)" class="bg-indigo-500 text-white active:bg-indigo-600 text-xs font-bold uppercase px-3 py-1 rounded outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150" type="button">
        Agregar Asistencia
    </button>
    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            Crear clase de prueba
        </x-slot>
        <x-slot name="content">
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Prospecto: " />
                    <x-select class="flex-1 ml-4" wire:model="prospectos_id">
                        <option value="">Seleccionar</option>
                        @forelse ($prospectos as $item)
                        <option value="{{$item->prospectos_id}}">{{$item->prospectos_nombres}} {{$item->prospectos_apellidos}}</option>
                        @empty
                        <option value="">Sin prospectos</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="prospectos_id"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Clase de pruebas: " />
                    <x-select class="flex-1 ml-4" wire:model="clasespruebas_id">
                        <option value="">Seleccionar</option>
                        @forelse ($clasespruebas as $item)
                        <option value="{{$item->clasespruebas_id}}">{{$item->clasespruebas_descripcion}} {{$item->clasespruebas_fecha}} {{$item->clasespruebas_hora_inicio}}</option>
                        @empty
                        <option value="">Sin clases de pruebas</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="clasespruebas_id"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Asistió: " />
                    <x-forms.toggle wire:model="asistencias"/>
                </div>
                <x-forms.input-error for="asistencias"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Fecha: " />
                    <x-forms.input type="date" class="flex-1 ml-4" wire:model="asistencias_fecha"/>
                </div>
                <x-forms.input-error for="asistencias_fecha"/>
        </div>
        </x-slot>
        <x-slot name="footer">
            <x-forms.red-button wire:click="$set('open',false)">
                Cancelar
            </x-forms.red-button>
            <x-forms.blue-button wire:click="save"  wire:loading.attr="disabled" wire:target="save" class="disabled:opacity-65">
                Crear asistencia
            </x-forms.blue-button>
        </x-slot>
    </x-dialog-modal>
</div>

