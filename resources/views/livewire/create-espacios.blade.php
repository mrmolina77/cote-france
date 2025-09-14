<div>
    <button wire:click="$set('open',true)" class="bg-indigo-500 text-white active:bg-indigo-600 text-xs font-bold uppercase px-3 py-1 rounded outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150" type="button">
        {{__('Add salon')}}
    </button>
    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            Crear salón
        </x-slot>
        <x-slot name="content">
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Name')}}: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="espacios_nombre"/>
                </div>
                <x-forms.input-error for="espacios_nombre"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Description')}}: " />
                    <x-forms.textarea rows="4" class="flex-1 ml-4" wire:model="espacios_descripcion">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="espacios_descripcion"/>
            </div>
            <div>
                <div class="mb-4 flex items-center">
                    <x-forms.label value="{{__('Active')}}: " />
                    <input type="checkbox" class="ml-4" wire:model="espacios_activo">
                </div>
                <x-forms.input-error for="espacios_activo"/>
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
            @if ($modalidad_id == 2)
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Link')}}" />
                    <x-forms.textarea rows="2" class="flex-1 ml-4" wire:model="espacios_enlace">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="espacios_enlace"/>
            </div>
            @endif
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
