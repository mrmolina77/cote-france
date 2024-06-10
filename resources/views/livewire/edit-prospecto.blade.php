<div>
    <i class="fas fa-pen text-emerald-500 mr-4" wire:click="$set('open',true)"></i>
    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            Actualizar prospecto
        </x-slot>
        <x-slot name="content">
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Nombres: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="prospecto.prospectos_nombres"/>                
                </div>
                <x-forms.input-error for="prospectos_nombres"/>
           </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Apellidos: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="prospecto.prospectos_apellidos"/>
                </div>
                <x-forms.input-error for="prospectos_apellidos"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Telefono: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="prospecto.prospectos_telefono"/>
                </div>
                <x-forms.input-error for="prospectos_telefono"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Correo: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="prospecto.prospectos_correo"/>
                </div>
                <x-forms.input-error for="prospectos_correo"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Origenes: " />
                    <x-select class="flex-1 ml-4" wire:model="prospecto.origenes_id">
                        <option value="">Seleccionar</option>
                        @forelse ($origenes as $item)
                        <option value="{{$item->origenes_id}}">{{$item->origenes_descripcion}}</option>
                        @empty
                        <option value="">Sin origenes</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="origenes_id"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Seguimientos: " />
                    <x-select class="flex-1 ml-4" wire:model="prospecto.seguimientos_id">
                        <option value="">Seleccionar</option>
                        @forelse ($seguimientos as $item)
                        <option value="{{$item->seguimientos_id}}">{{$item->seguimientos_descripcion}}</option>
                        @empty
                        <option value="">Sin segumientos</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="seguimientos_id"/>
            </div>
            <div>
                <div class="mb-4 flex" >
                    <x-forms.label value="Origenes: " />
                    <x-select class="flex-1 ml-4" wire:model="prospecto.estatus_id">
                        <option value="">Seleccionar</option>
                        @forelse ($estatus as $item)
                        <option value="{{$item->estatus_id}}">{{$item->estatus_descripcion}}</option>
                        @empty
                        <option value="">Sin origenes</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="estatus_id"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Comentario: " />
                    <x-forms.textarea rows="4" class="flex-1 ml-4" wire:model="prospecto.prospectos_comentarios">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="prospectos_comentarios"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Fecha: " />
                    <x-forms.input type="date" class="flex-1 ml-4" wire:model="prospecto.prospectos_fecha"/>
                </div>
                <x-forms.input-error for="prospectos_fecha"/>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-forms.red-button wire:click="$set('open',false)">
                Cancelar
            </x-forms.red-button>
            <x-forms.blue-button wire:click="save"  wire:loading.attr="disabled" wire:target="save" class="disabled:opacity-65">
                Modificar Prospecto
            </x-forms.blue-button>
            {{-- <span wire:loading wire:target="save">Cargando...</span> --}}
        </x-slot>
    </x-dialog-modal>
</div>
