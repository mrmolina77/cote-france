<div>

    <button wire:click="$set('open',true)" class="bg-indigo-500 text-white active:bg-indigo-600 text-xs font-bold uppercase px-3 py-1 rounded outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150" type="button">
        {{__('Add group')}}
    </button>

    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            Crear prospecto
        </x-slot>
        <x-slot name="content">
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Names')}}: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="grupo_nombre"/>
                </div>
                <x-forms.input-error for="grupo_nombre"/>
           </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Level')}}: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="grupo_nivel"/>
                </div>
                <x-forms.input-error for="grupo_nivel"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Chapter')}}: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="grupo_capitulo"/>
                </div>
                <x-forms.input-error for="grupo_capitulo"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Master book')}}: " />
                    <x-forms.textarea rows="4" class="flex-1 ml-4" wire:model="grupo_libro_maestro">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="grupo_libro_maestro"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Student book')}}: " />
                    <x-forms.textarea rows="4" class="flex-1 ml-4" wire:model="grupo_libro_alumno">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="grupo_libro_alumno"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Observations')}}: " />
                    <x-forms.textarea rows="4" class="flex-1 ml-4" wire:model="grupo_observacion">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="grupo_observacion"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Modality')}}: " />
                    <x-select class="flex-1 ml-4" wire:model="modalidad_id">
                        <option value="">{{__('Select')}}</option>
                        @forelse ($modalidades as $item)
                        <option value="{{$item->modalidad_id}}">{{$item->modalidad_nombre}}</option>
                        @empty
                        <option value="">{{__('No Content')}}</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="modalidad_id"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('State')}}: " />
                    <x-select class="flex-1 ml-4" wire:model="estado_id">
                        <option value="">{{__('Select')}}</option>
                        @forelse ($estados as $item)
                        <option value="{{$item->estado_id}}">{{$item->estado_nombre}}</option>
                        @empty
                        <option value="">{{__('No Content')}}</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="estado_id"/>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-forms.red-button wire:click="$set('open',false)">
                {{__('Cancel')}}
            </x-forms.red-button>
            <x-forms.blue-button wire:click="save"  wire:loading.attr="disabled" wire:target="save" class="disabled:opacity-65">
                {{__('Create')}}
            </x-forms.blue-button>
            {{-- <span wire:loading wire:target="save">Cargando...</span> --}}
        </x-slot>
    </x-dialog-modal>
</div>
