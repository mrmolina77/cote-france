<div wire:init="loadPosts">
    @section('content')
    <p>{{ __('Test classes') }}</p>
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
                    @livewire('create-clases-pruebas')
                    </div>
                </div>
            </x-slot>
            <table class="items-center bg-transparent w-full border-collapse">
                <thead>
                <tr>
                <th class="cursor-pointer px-2 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left"
                    wire:click="order('clasespruebas_id')">
                    Id
                    @if ($sort == 'clasespruebas_id')
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
                    wire:click="order('clasespruebas_fecha')">
                    Fecha
                    @if ($sort == 'clasespruebas_fecha')
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
                    wire:click="order('clasespruebas_descripcion')">
                    Descripción
                    @if ($sort == 'clasespruebas_descripcion')
                        @if ($direction == 'asc')
                            <i class="fas fa-sort-alpha-up-alt float-right mt-1"></i>
                        @else
                            <i class="fas fa-sort-alpha-down-alt float-right mt-1"></i>
                        @endif
                    @else
                        <i class="fas fa-sort float-right mt-1"></i>
                    @endif
                    </th>
                <th class="cursor-pointer px-4 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
                    Hora Inicio
                    </th>
                <th class="cursor-pointer px-4 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
                    Hora Fin
                    </th>
                <th class="cursor-pointer px-4 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left"
                    wire:click="order('profesores_id')">
                    Profesor
                    @if ($sort == 'profesores_id')
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
                @forelse ( $clasespruebas as $item )

                <tr>
                    <th class="border-t-0 px-2 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-left text-blueGray-700 ">
                        {{$item->clasespruebas_id}}
                    </th>
                    <td class="border-t-0 px-4 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 ">
                        {{\Carbon\Carbon::parse($item->clasespruebas_fecha)->format('d-m-Y')}}
                    </td>
                    <td class="border-t-0 px-4 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 ">
                        {{$item->clasespruebas_descripcion}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->clasespruebas_hora_inicio}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->clasespruebas_hora_fin}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->profesores_nombres}} {{$item->profesores_apellidos}}
                    </td>
                    <td class="flex border-t-0 px-4 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        <i class="fas fa-pen text-emerald-500 mr-4 cursor-pointer" wire:click="edit({{ $item->clasespruebas_id }})"></i>
                        <i class="fas fa-trash text-red-500 mr-4 cursor-pointer" wire:click="$emit('deleteProspecto',{{$item->clasespruebas_id}})"></i>
                    </td>
                </tr>
                @empty
                @if ($readyToLoad)
                <tr>
                    <th colspan="5" class="border-t-0 px-2 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-left text-blueGray-700 ">
                        No hay clases de pruebas cargados
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
            @if (count($clasespruebas) > 0 and !is_array($clasespruebas) and $clasespruebas->hasPages())
                <div class="px-6 py-3">
                    {{$clasespruebas->links()}}
                </div>
            @endif
        </x-table>




    <x-dialog-modal wire:model="open_edit">
        <x-slot name="title">
            Actualizar Clases de Pruebas
        </x-slot>
        <x-slot name="content">
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Description')}}: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="claseprueba.clasespruebas_descripcion"/>
                </div>
                <x-forms.input-error for="clasespruebas_descripcion"/>
           </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Date')}}: " />
                    <x-forms.input type="date" class="flex-1 ml-4" wire:model="claseprueba.clasespruebas_fecha"/>
                </div>
                <x-forms.input-error for="clasespruebas_fecha"/>
           </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Start Time')}}: " />
                    <x-forms.input type="time" class="flex-1 ml-4" wire:model="claseprueba.clasespruebas_hora_inicio"/>
                </div>
                <x-forms.input-error for="clasespruebas_hora_inicio"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('End time')}}: " />
                    <x-forms.input type="time" class="flex-1 ml-4" wire:model="claseprueba.clasespruebas_hora_fin"/>
                </div>
                <x-forms.input-error for="clasespruebas_hora_fin"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Teacher')}}: " />
                    <x-select class="flex-1 ml-4" wire:model="claseprueba.profesores_id">
                        <option value="">{{__('Select')}}</option>
                        @forelse ($profesores as $item)
                        <option value="{{$item->profesores_id}}">{{$item->profesores_nombres}} {{$item->profesores_apellidos}}</option>
                        @empty
                        <option value="">{{__('No Content')}}</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="profesores_id"/>
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
        livewire.on('deleteClasesPruebas',itemId=>{
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
                livewire.emitTo('show-prospectos','delete',itemId);
            }
            });
        })
    </script>
    @endpush
</div>
