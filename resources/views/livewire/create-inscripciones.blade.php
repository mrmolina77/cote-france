<div>
    <button wire:click="$set('open',true)" class="bg-indigo-500 text-white active:bg-indigo-600 text-xs font-bold uppercase px-3 py-1 rounded outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150" type="button">
        {{__('Add registration')}}
    </button>
    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            Crear inscripción
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
                    <x-forms.label value="Cursos: " />
                    <x-select class="flex-1 ml-4" wire:model="cursos_id">
                        <option value="">Seleccionar</option>
                        @forelse ($cursos as $item)
                        <option value="{{$item->cursos_id}}">{{$item->cursos_descripcion}}</option>
                        @empty
                        <option value="">Sin cursos</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="cursos_id"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Fecha: " />
                    <x-forms.input type="date" class="flex-1 ml-4" wire:model="fecha_inscripcion"/>
                </div>
                <x-forms.input-error for="fecha_inscripcion"/>
        </div>
        </x-slot>
        <x-slot name="footer">
            <x-forms.red-button wire:click="$set('open',false)">
                Cancelar
            </x-forms.red-button>
            <x-forms.blue-button wire:click="save"  wire:loading.attr="disabled" wire:target="save" class="disabled:opacity-65">
                Crear inscripción
            </x-forms.blue-button>
        </x-slot>
    </x-dialog-modal>
</div>

