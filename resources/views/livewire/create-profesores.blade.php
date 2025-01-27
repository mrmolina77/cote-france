<div>
    <button wire:click="$set('open',true)" class="bg-indigo-500 text-white active:bg-indigo-600 text-xs font-bold uppercase px-3 py-1 rounded outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150" type="button">
        {{__('Create teacher')}}
    </button>

    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            {{__('Create teacher')}}
        </x-slot>
        <x-slot name="content">
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Names')}}: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="profesores_nombres"/>
                </div>
                <x-forms.input-error for="clasespruebas_descripcion"/>
           </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Last names')}}: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="profesores_apellidos"/>
                </div>
                <x-forms.input-error for="profesores_apellidos"/>
           </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Email')}}: " />
                    <x-forms.input type="email" class="flex-1 ml-4" wire:model="profesores_email"/>
                </div>
                <x-forms.input-error for="profesores_email"/>
           </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Color')}}: " />
                    <x-forms.input type="color" class="ml-4 w-12" wire:model="profesores_color"/>
                </div>
                <x-forms.input-error for="profesores_color"/>
           </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Entry date')}}: " />
                    <x-forms.input type="date" class="flex-1 ml-4" wire:model="profesores_fecha_ingreso"/>
                </div>
                <x-forms.input-error for="profesores_fecha_ingreso"/>
           </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Hours per week')}}: " />
                    <x-forms.input type="number" class="flex-1 ml-4" wire:model="profesores_horas_semanales"/>
                </div>
                <x-forms.input-error for="profesores_horas_semanales"/>
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
        </x-slot>
        <x-slot name="footer">
            <x-forms.red-button wire:click="$set('open',false)">
                {{__('Cancel')}}
            </x-forms.red-button>
            <x-forms.blue-button wire:click="save"  wire:loading.attr="disabled" wire:click="save" class="disabled:opacity-65">
                {{__('Create')}}
            </x-forms.blue-button>
            {{-- <span wire:loading wire:target="save">Cargando...</span> --}}
        </x-slot>
    </x-dialog-modal>
</div>
