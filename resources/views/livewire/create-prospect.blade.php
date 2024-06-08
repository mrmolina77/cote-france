<div>
    
    
    
    <button wire:click="$set('open',true)" class="bg-indigo-500 text-white active:bg-indigo-600 text-xs font-bold uppercase px-3 py-1 rounded outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150" type="button">
        Agregar Prospecto
    </button>
    
    
    
    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            Crear prospecto
        </x-slot>
        <x-slot name="content">
            <div class="mb-4 flex">
                <x-forms.label value="Nombres: " />
                <x-forms.input type="text" class="flex-1 ml-4" wire:model.defer="prospectos_nombres"/>
            </div>
            <div class="mb-4 flex">
                <x-forms.label value="Apellidos: " />
                <x-forms.input type="text" class="flex-1 ml-4" wire:model.defer="prospectos_apellidos"/>
            </div>
            <div class="mb-4 flex">
                <x-forms.label value="Telefono: " />
                <x-forms.input type="text" class="flex-1 ml-4" wire:model.defer="prospectos_telefono"/>
            </div>
            <div class="mb-4 flex">
                <x-forms.label value="Correo: " />
                <x-forms.input type="text" class="flex-1 ml-4" wire:model.defer="prospectos_correo"/>
            </div>
            <div class="mb-4 flex">
                <x-forms.label value="Origenes: " />
                <x-select class="flex-1 ml-4" wire:model.defer="origenes_id">
                    <option value="">Seleccionar</option>
                    @forelse ($origenes as $item)
                    <option value="{{$item->origenes_id}}">{{$item->origenes_descripcion}}</option>
                    @empty
                    <option value="">Sin origenes</option>
                    @endforelse
                </x-select>
            </div>
            <div class="mb-4 flex">
                <x-forms.label value="Seguimientos: " />
                <x-select class="flex-1 ml-4" wire:model.defer="seguimientos_id">
                    <option value="">Seleccionar</option>
                    @forelse ($seguimientos as $item)
                    <option value="{{$item->seguimientos_id}}">{{$item->seguimientos_descripcion}}</option>
                    @empty
                    <option value="">Sin segumientos</option>
                    @endforelse
                </x-select>
            </div>
            <div class="mb-4 flex" >
                    <x-forms.label value="Origenes: " />
                    <x-select class="flex-1 ml-4" wire:model.defer="estatus_id">
                        <option value="">Seleccionar</option>
                        @forelse ($estatus as $item)
                        <option value="{{$item->estatus_id}}">{{$item->estatus_descripcion}}</option>
                        @empty
                        <option value="">Sin origenes</option>
                        @endforelse
                    </x-select>
                </div>
                <div class="mb-4 flex">
                    <x-forms.label value="Comentario: " />
                    <x-forms.textarea id="message" rows="4" class="flex-1 ml-4" wire:model.defer="prospectos_comentarios">
                    </x-forms.textarea>
                </div>
                <div class="mb-4 flex">
                    <x-forms.label value="Fecha: " />
                    <x-forms.input type="date" class="flex-1 ml-4" wire:model.defer="prospectos_fecha"/>
                </div>
        </x-slot>
        <x-slot name="footer">
            <x-forms.red-button wire:click="$set('open',false)">
                Cancelar
            </x-forms.red-button>
            <x-forms.blue-button wire:click="save">
                Crear Prospecto
            </x-forms.blue-button>
        </x-slot>
    </x-dialog-modal>
</div>
