<div>

    <button wire:click="$set('open',true)" class="bg-indigo-500 text-white active:bg-indigo-600 text-xs font-bold uppercase px-3 py-1 rounded outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150" type="button">
        {{__('Add prospect')}}
    </button>

    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            Crear prospecto
        </x-slot>
        <x-slot name="content">
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Names')}}: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="prospectos_nombres"/>
                </div>
                <x-forms.input-error for="prospectos_nombres"/>
           </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Surname')}}: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="prospectos_apellidos"/>
                </div>
                <x-forms.input-error for="prospectos_apellidos"/>
            </div>
            <div>
                <div>
                    <div class="mb-4 flex">
                        <x-forms.label value="{{__('Phone')}} : " />
                        <x-forms.input type="text" class="flex-1 ml-4" wire:model="prospectos_telefono1"/>
                        <x-forms.input type="text" class="flex-1 ml-4" wire:model="prospectos_telefono2"/>
                    </div>
                    <x-forms.input-error for="prospectos_telefono1"/>
                </div>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Mail')}}: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="prospectos_correo"/>
                </div>
                <x-forms.input-error for="prospectos_correo"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Origins')}}: " />
                    <x-select class="flex-1 ml-4" wire:model="origenes_id">
                        <option value="">{{__('Select')}}</option>
                        @forelse ($origenes as $item)
                        <option value="{{$item->origenes_id}}">{{$item->origenes_descripcion}}</option>
                        @empty
                        <option value="">{{__('No Content')}}</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="origenes_id"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Follow-ups')}}: " />
                    <x-select class="flex-1 ml-4" wire:model="seguimientos_id">
                        <option value="">{{__('Select')}}</option>
                        @forelse ($seguimientos as $item)
                        <option value="{{$item->seguimientos_id}}">{{$item->seguimientos_descripcion}}</option>
                        @empty
                        <option value="">{{__('No Content')}}</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="seguimientos_id"/>
            </div>
            @if ($seguimientos_id === '2')
                <div>
                    <div class="mb-4 flex" >
                        <x-forms.label value="{{__('Group')}}: " />
                        <x-select class="flex-1 ml-4" wire:model="grupoid">
                            <option value="">{{__('Select')}}</option>
                            @forelse ($grupos as $item)
                            <option value="{{$item->grupo_id}}">{{$item->grupo_nombre}}</option>
                            @empty
                            <option value="">{{__('No Content')}}</option>
                            @endforelse
                        </x-select>
                    </div>
                    <x-forms.input-error for="grupo_id"/>
                </div>
                <div>
                    <div class="mb-4 flex" >
                        <x-forms.label value="{{__('Timetable')}}: " />
                        <x-select class="flex-1 ml-4" wire:model="horarios_id">
                            <option value="">{{__('Select')}}</option>
                            @forelse ($horarios as $item)
                            <option value="{{$item->horarios_id}}">{{\Carbon\Carbon::parse($item->horarios_dia)->format('d-m-Y')}} {{$item->hora->horas_desde}} {{$item->hora->horas_hasta}}</option>
                            @empty
                            <option value="">{{__('No Content')}}</option>
                            @endforelse
                        </x-select>
                    </div>
                    <x-forms.input-error for="estatus_id"/>
                </div>
            @endif
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
                <div class="mb-4 flex" >
                    <x-forms.label value="{{__('Status')}}: " />
                    <x-select class="flex-1 ml-4" wire:model="estatus_id">
                        <option value="">{{__('Select')}}</option>
                        @forelse ($estatus as $item)
                        <option value="{{$item->estatus_id}}">{{$item->estatus_descripcion}}</option>
                        @empty
                        <option value="">{{__('No Content')}}</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="estatus_id"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Comment')}}: " />
                    <x-forms.textarea rows="4" class="flex-1 ml-4" wire:model="prospectos_comentarios">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="prospectos_comentarios"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Date')}}: " />
                    <x-forms.input type="date" class="flex-1 ml-4" wire:model="prospectos_fecha"/>
                </div>
                <x-forms.input-error for="prospectos_fecha"/>
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
