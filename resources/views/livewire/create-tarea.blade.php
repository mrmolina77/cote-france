<div>

    <button wire:click="$set('open',true)" class="bg-indigo-500 text-white active:bg-indigo-600 text-xs font-bold uppercase px-3 py-1 rounded outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150" type="button">
        {{__('Add homework')}}
    </button>

    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            Crear tarea
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
                    <x-forms.label value="Fecha: " />
                    <x-forms.input type="date" class="flex-1 ml-4" wire:model="tareas_fecha"/>
                </div>
                <x-forms.input-error for="tareas_fecha"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Tarea: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="tareas_descripcion"/>
                </div>
                <x-forms.input-error for="tareas_descripcion"/>
           </div>
           <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Comentario: " />
                    <x-forms.textarea rows="4" class="flex-1 ml-4" wire:model="tareas_comentario">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="tareas_comentario"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Estatus: " />
                    <x-select class="flex-1 ml-4" wire:model="est_tareas_id">
                        <option value="">Seleccionar</option>
                        @forelse ($estatus as $item)
                        <option value="{{$item->est_tareas_id}}">{{$item->est_tareas_descripcion}}</option>
                        @empty
                        <option value="">Sin segumientos</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="est_tareas_id"/>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-forms.red-button wire:click="$set('open',false)">
                Cancelar
            </x-forms.red-button>
            <x-forms.blue-button wire:click="save"  wire:loading.attr="disabled" wire:target="save" class="disabled:opacity-65">
                Crear tarea
            </x-forms.blue-button>
            {{-- <span wire:loading wire:target="save">Cargando...</span> --}}
        </x-slot>
    </x-dialog-modal>
</div>
