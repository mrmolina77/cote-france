<div wire:init="loadPosts">
    @section('content')
    <p>{{ __('Groups') }}</p>
    @endsection
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <x-table>
            <x-slot:header>
                <div class="flex flex-wrap items-center">
                    <div class="flex items-center">
                        <span>{{__('Show')}}</span>
                        <x-select class="mx-2" wire:model="cant">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </x-select>
                        <span>{{__('rows')}}</span>
                    </div>
                    <div class="relative w-full px-4 max-w-full flex-grow flex-1">
                        <div class="px-6 py-4">
                            <x-forms.input type="text" placeholder="{{__('Search')}}..." class="flex-1 ml-4" wire:model="search"/>
                        </div>
                    </div>
                    <div class="relative w-full px-4 max-w-full flex-grow flex-1 text-right">
                    @livewire('create-grupos')
                    </div>
                </div>
            </x-slot>
            <table class="items-center bg-transparent w-full border-collapse">
                <thead>
                <tr>
                <th class="cursor-pointer px-2 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left"
                    wire:click="order('grupo_id')">
                    Id
                    @if ($sort == 'grupo_id')
                        @if ($direction == 'asc')
                            <i class="fas fa-sort-alpha-up-alt float-right mt-1"></i>
                        @else
                            <i class="fas fa-sort-alpha-down-alt float-right mt-1"></i>
                        @endif
                    @else
                        <i class="fas fa-sort float-right mt-1"></i>
                    @endif
                    </th>
                <th class="cursor-pointer px-6 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left"
                    wire:click="order('grupo_nombre')">
                    Nombre
                    @if ($sort == 'grupo_nombre')
                        @if ($direction == 'asc')
                            <i class="fas fa-sort-alpha-up-alt float-right mt-1"></i>
                        @else
                            <i class="fas fa-sort-alpha-down-alt float-right mt-1"></i>
                        @endif
                    @else
                        <i class="fas fa-sort float-right mt-1"></i>
                    @endif
                    </th>
                <th class="cursor-pointer px-4 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left"
                    wire:click="order('grupo_nivel')">
                    Nivel
                    @if ($sort == 'grupo_nivel')
                        @if ($direction == 'asc')
                            <i class="fas fa-sort-alpha-up-alt float-right mt-1"></i>
                        @else
                            <i class="fas fa-sort-alpha-down-alt float-right mt-1"></i>
                        @endif
                    @else
                        <i class="fas fa-sort float-right mt-1"></i>
                    @endif
                    </th>
                <th class="cursor-pointer px-4 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left"
                    wire:click="order('grupo_capitulo')">
                    Capitulo
                    @if ($sort == 'grupo_capitulo')
                        @if ($direction == 'asc')
                            <i class="fas fa-sort-alpha-up-alt float-right mt-1"></i>
                        @else
                            <i class="fas fa-sort-alpha-down-alt float-right mt-1"></i>
                        @endif
                    @else
                        <i class="fas fa-sort float-right mt-1"></i>
                    @endif
                </th>
                <th class="px-4 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left"
                    wire:click="order('modalidad_nombre')">
                    Modalidad
                    @if ($sort == 'modalidad_nombre')
                        @if ($direction == 'asc')
                            <i class="fas fa-sort-alpha-up-alt float-right mt-1"></i>
                        @else
                            <i class="fas fa-sort-alpha-down-alt float-right mt-1"></i>
                        @endif
                    @else
                        <i class="fas fa-sort float-right mt-1"></i>
                    @endif
                </th>
                <th class="px-4 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left"
                    wire:click="order('estado_nombre')">
                    Estado
                    @if ($sort == 'estado_nombre')
                        @if ($direction == 'asc')
                            <i class="fas fa-sort-alpha-up-alt float-right mt-1"></i>
                        @else
                            <i class="fas fa-sort-alpha-down-alt float-right mt-1"></i>
                        @endif
                    @else
                        <i class="fas fa-sort float-right mt-1"></i>
                    @endif
                </th>
                <th class="px-4 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
                    Acción
                    </th>
                </tr>
                </thead>

                <tbody style="max-height: 10px;">
                @forelse ( $grupos as $item )
                <tr>
                    <th class="border-t-0 px-2 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-left text-blueGray-700 ">
                        {{$item->grupo_id}}
                    </th>
                    <td class="border-t-0 px-4 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 ">
                        {{$item->grupo_nombre}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->grupo_nivel}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->grupo_capitulo}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->modalidad_nombre}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->estado_nombre}}
                    </td>
                    <td class="flex border-t-0 px-4 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        <i class="fas fa-pen text-emerald-500 mr-4 cursor-pointer" wire:click="edit({{ $item->grupo_id }})"></i>
                        <i class="fas fa-trash text-red-500 mr-4 cursor-pointer" wire:click="$emit('deleteGrupo',{{$item->grupo_id}})"></i>
                    </td>
                </tr>
                @empty
                @if ($readyToLoad)
                <tr>
                    <th colspan="5" class="border-t-0 px-2 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-left text-blueGray-700 ">
                        No hay grupos cargados
                    </th>
                </tr>
                @else
                <tr>
                    <th colspan="5" class="border-t-0 px-2 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-left text-blueGray-700 ">
                        <div class="px-4 py-12">
                            <div class="rounded relative">
                              <div
                                class="rounded-full bg-indigo-200 w-[190px] h-[190px] relative flex justify-center items-center mx-auto animate-spin"
                              >
                                <svg
                                  class="absolute top-[2px] right-0"
                                  width="76"
                                  height="97"
                                  viewBox="0 0 76 97"
                                  fill="none"
                                  xmlns="http://www.w3.org/2000/svg"
                                >
                                  <mask id="path-1-inside-1_2495_2146" fill="white">
                                    <path
                                      d="M76 97C76 75.6829 69.2552 54.9123 56.7313 37.6621C44.2074 20.4118 26.5466 7.56643 6.27743 0.964994L0.0860505 19.9752C16.343 25.2698 30.5078 35.5725 40.5526 49.408C50.5974 63.2436 56.007 79.9026 56.007 97H76Z"
                                    />
                                  </mask>
                                  <path
                                    d="M76 97C76 75.6829 69.2552 54.9123 56.7313 37.6621C44.2074 20.4118 26.5466 7.56643 6.27743 0.964994L0.0860505 19.9752C16.343 25.2698 30.5078 35.5725 40.5526 49.408C50.5974 63.2436 56.007 79.9026 56.007 97H76Z"
                                    stroke="#4338CA"
                                    stroke-width="40"
                                    mask="url(#path-1-inside-1_2495_2146)"
                                  />
                                </svg>
                                <div class="rounded-full bg-white w-[150px] h-[150px]"></div>
                              </div>
                              <p
                                class="absolute mx-auto inset-x-0 my-auto inset-y-[80px] text-base font-medium text-gray-800 text-center"
                              >
                                Loading ...
                              </p>
                            </div>
                          </div>
                        </div>
                    </th>
                </tr>
                @endif
                @endforelse

                </tbody>

            </table>
            @if (count($grupos) > 0 and !is_array($grupos) and $grupos->hasPages())
                <div class="px-6 py-3">
                    {{$prospectos->links()}}
                </div>
            @endif
        </x-table>

        <x-dialog-modal wire:model="open_edit">
            <x-slot name="title">
                Actualizar grupo
            </x-slot>
            <x-slot name="content">
                <div>
                    <div class="mb-4 flex">
                        <x-forms.label value="{{__('Names')}}: " />
                        <x-forms.input type="text" class="flex-1 ml-4" wire:model="grupo.grupo_nombre"/>
                    </div>
                    <x-forms.input-error for="grupo_nombre"/>
            </div>
                <div>
                    <div class="mb-4 flex">
                        <x-forms.label value="{{__('Level')}}: " />
                        <x-forms.input type="text" class="flex-1 ml-4" wire:model="grupo.grupo_nivel"/>
                    </div>
                    <x-forms.input-error for="grupo_nivel"/>
                </div>
                <div>
                    <div class="mb-4 flex">
                        <x-forms.label value="{{__('Chapter')}}: " />
                        <x-forms.input type="text" class="flex-1 ml-4" wire:model="grupo.grupo_capitulo"/>
                    </div>
                    <x-forms.input-error for="grupo_capitulo"/>
                </div>
                <div>
                    <div class="mb-4 flex">
                        <x-forms.label value="{{__('Master book')}}: " />
                        <x-forms.textarea rows="4" class="flex-1 ml-4" wire:model="grupo.grupo_libro_maestro">
                        </x-forms.textarea>
                    </div>
                    <x-forms.input-error for="grupo_libro_maestro"/>
                </div>
                <div>
                    <div class="mb-4 flex">
                        <x-forms.label value="{{__('Student book')}}: " />
                        <x-forms.textarea rows="4" class="flex-1 ml-4" wire:model="grupo.grupo_libro_alumno">
                        </x-forms.textarea>
                    </div>
                    <x-forms.input-error for="grupo_libro_alumno"/>
                </div>
                <div>
                    <div class="mb-4 flex">
                        <x-forms.label value="{{__('Observations')}}: " />
                        <x-forms.textarea rows="4" class="flex-1 ml-4" wire:model="grupo.grupo_observacion">
                        </x-forms.textarea>
                    </div>
                    <x-forms.input-error for="grupo_observacion"/>
                </div>
                <div>
                    <div class="mb-4 flex">
                        <x-forms.label value="{{__('Modality')}}: " />
                        <x-select class="flex-1 ml-4" wire:model="grupo.modalidad_id">
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
                {{-- <div>
                    <div class="mb-4 flex">
                        <x-forms.label value="{{__('Teacher')}}: " />
                        <x-select class="flex-1 ml-4" wire:model="grupo.profesores_id">
                            <option value="">{{__('Select')}}</option>
                            @forelse ($profesores as $item)
                            <option value="{{$item->profesores_id}}">{{$item->profesores_nombres}} {{$item->profesores_apellidos}}</option>
                            @empty
                            <option value="">{{__('No Content')}}</option>
                            @endforelse
                        </x-select>
                    </div>
                    <x-forms.input-error for="profesores_id"/>
                </div> --}}
                <div>
                    <div class="mb-4 flex">
                        <x-forms.label value="{{__('State')}}: " />
                        <x-select class="flex-1 ml-4" wire:model="grupo.estado_id">
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
                                    Profesores
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
                                        <i class="fas fa-trash text-red-500 mr-4 cursor-pointer" wire:click="$emit('deleteDetalle',{{$loop->index}})"></i>
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
                <x-forms.red-button wire:click="$set('open_edit',false)">
                    {{__('Cancel')}}
                </x-forms.red-button>
                <x-forms.blue-button wire:click="save"  wire:loading.attr="disabled" wire:click="update" class="disabled:opacity-65">
                    {{__('Modify')}}
                </x-forms.blue-button>
                {{-- <span wire:loading wire:target="save">Cargando...</span> --}}
            </x-slot>
        </x-dialog-modal>

    @push('js');
    <script>
        livewire.on('deleteGrupo',itemId=>{
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
                livewire.emitTo('show-grupos','delete',itemId);
            }
            });
        });

        livewire.on('deleteDetalle',itemId=>{
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
                livewire.emitTo('show-grupos','deleteDetalle',itemId);
            }
            });
        });
    </script>
    @endpush
</div>
