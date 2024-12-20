<div>

    <button wire:click="$set('open',true)" class="bg-indigo-500 text-white active:bg-indigo-600 text-xs font-bold uppercase px-3 py-1 rounded outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150" type="button">
        {{__('Add group')}}
    </button>

    <x-dialog-modal maxWidth="3xl" wire:model="open">
        <x-slot name="title">
            Crear grupo
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
                    <x-forms.textarea rows="2" class="flex-1 ml-4" wire:model="grupo_libro_maestro">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="grupo_libro_maestro"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Student book')}}: " />
                    <x-forms.textarea rows="2" class="flex-1 ml-4" wire:model="grupo_libro_alumno">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="grupo_libro_alumno"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Observations')}}: " />
                    <x-forms.textarea rows="2" class="flex-1 ml-4" wire:model="grupo_observacion">
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
            <div class="inline-flex items-center justify-center w-full">
                <hr class="w-64 h-px my-8 bg-gray-200 border-0 dark:bg-gray-700">
                <span class="absolute px-3 font-medium text-gray-900 -translate-x-1/2 bg-white left-1/2 dark:text-white dark:bg-gray-900"> Horarios </span>
            </div>
            <div class="flex flex-row">
                <div class="basis-1/4">
                    <div class="mb-4">
                        <x-select class="flex-1 ml-4" wire:model="dias_id">
                            <option value="">{{__('Day')}}</option>
                            @forelse ($dias as $item)
                            <option value="{{$item->dias_id}}">{{$item->dias_nombre}}</option>
                            @empty
                            <option value="">{{__('No Content')}}</option>
                            @endforelse
                        </x-select>
                    </div>
                    <x-forms.input-error for="dias_id"/>
                </div>
                <div class="basis-1/4">
                    <div class="mb-4">
                        <x-select class="flex-1 ml-4" wire:model="horas_id">
                            <option value="">{{__('Hours')}}</option>
                            @forelse ($horas as $item)
                            <option value="{{$item->horas_id}}">{{$item->horas_desde}} - {{$item->horas_hasta}}</option>
                            @empty
                            <option value="">{{__('No Content')}}</option>
                            @endforelse
                        </x-select>
                    </div>
                    <x-forms.input-error for="horas_id"/>
                </div>
                <div class="basis-1/4">
                    <div class="mb-4">
                        <x-select class="flex-1 ml-4" wire:model="espacios_id">
                            <option value="">{{__('Spaces')}}</option>
                            @forelse ($espacios as $item)
                            <option value="{{$item->espacios_id}}">{{$item->espacios_nombre}}</option>
                            @empty
                            <option value="">{{__('No Content')}}</option>
                            @endforelse
                        </x-select>
                    </div>
                    <x-forms.input-error for="espacios_id"/>
                </div>
                <div class="basis-1/4">
                    <button type="button" wire:click="add"  wire:loading.attr="disabled" wire:target="add" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 disabled:opacity-65">
                        Agrerar
                    </button>
                </div>
            </div>


            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                Días
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Horas
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Espacios
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Acción
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($detalles_grupos as $item )
                            <tr class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700">
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{$item['dia']}}
                                </th>
                                <td class="px-6 py-4">
                                    {{$item['hora']}}
                                </td>
                                <td class="px-6 py-4">
                                    {{$item['espacio']}}
                                </td>
                                <td class="px-6 py-4">
                                    <i class="fas fa-trash text-red-500 mr-4 cursor-pointer" wire:click="$emit('showDeleteDetalle',{{$loop->index}})"></i>
                                </td>
                            </tr>
                        @empty
                            <tr class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700">
                                <td class="px-6 py-4" colspan="4">
                                    Sin definir
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
    @push('js');
    <script>
        livewire.on('showDeleteDetalle',itemId=>{
            Swal.fire({
            title: "{{__('Are you sure you want to delete the record?')}}",
            text: "{{__('You will not be able to reverse this!')}}",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            cancelButtonText: "{{__('Cancel')}}",
            confirmButtonText: "{{__('Yes, delete it!')}}"
            }).then((result) => {
            if (result.isConfirmed) {
                livewire.emitTo('create-grupos','createDelete',itemId);
            }
            });
        })
    </script>
    @endpush
</div>
