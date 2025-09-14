<div wire:init="loadPosts">
    @section('content')
    <p>{{__('Salons')}}</p>
    @endsection
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <x-table>
            <x-slot:header>
                <div class="flex flex-wrap items-center">
                    <div class="flex items-center">
                        <span>{{__('Show')}}</span>
                        <select wire:model="cant" class="mx-2 form-control">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="75">75</option>
                            <option value="100">100</option>
                        </select>
                        <span>{{__('entries')}}</span>
                    </div>
                    <div class="relative w-full px-4 max-w-full flex-grow flex-1 text-right">
                        <div class="px-6 py-4">
                            <x-forms.input type="text" placeholder="{{__('Search')}}..." class="flex-1 ml-4" wire:model="search"/>
                        </div>
                    </div>
                    <div class="relative w-full px-4 max-w-full flex-grow flex-1 text-right">
                    @livewire('create-espacios')
                    </div>
                </div>
            </x-slot>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">

                    <tr>
                        <th class="cursor-pointer px-2 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left"
                        wire:click="order('espacios_id')">
                        Id
                        @if ($sort == 'espacios_id')
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
                            wire:click="order('espacios_nombre')">
                            Nombre
                        @if ($sort == 'espacios_nombre')
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
                        <th class="cursor-pointer px-6 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left"
                            wire:click="order('espacios_activo')">
                            Activo
                        @if ($sort == 'espacios_activo')
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
                            {{__('Actions')}}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($espacios as $item)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{$item->espacios_id}}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{$item->espacios_nombre}}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{$item->modalidad_nombre}}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">@if ($item->espacios_activo)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Activo
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Inactivo
                                </span>
                            @endif</div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <i class="fas fa-pen text-emerald-500 mr-4 cursor-pointer" wire:click="edit({{ $item->espacios_id }})"></i>
                            <i class="fas fa-trash text-red-500 mr-4 cursor-pointer" wire:click="$emit('deleteEspacio',{{$item->espacios_id}})"></i>
                        </td>
                    </tr>
                    @empty
                    @if ($readyToLoad)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap" colspan="4">
                            <div class="text-sm text-gray-900">{{__('No results')}}</div>
                        </td>
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
        @if (count($espacios) > 0 and !is_array($espacios) and  $espacios->hasPages())
            <div class="px-6 py-3">
                {{$espacios->links()}}
            </div>
        @endif
    </x-table>

    <x-dialog-modal wire:model="open_edit">
        <x-slot name="title">
            Editar salón
        </x-slot>
        <x-slot name="content">
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Name')}}: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="espacio.espacios_nombre"/>
                </div>
                <x-forms.input-error for="espacios_nombre"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Description')}}: " />
                    <x-forms.textarea rows="4" class="flex-1 ml-4" wire:model="espacio.espacios_descripcion">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="espacios_descripcion"/>
            </div>
            <div>
                <div class="mb-4 flex items-center">
                    <x-forms.label value="{{__('Active')}}: " />
                    <input type="checkbox" class="ml-4" wire:model="espacio.espacios_activo">
                </div>
                <x-forms.input-error for="espacios_activo"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Modality')}}: " />
                    <x-select class="flex-1 ml-4" wire:model="espacio.modalidad_id">
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
            @if ($this->open_edit and isset($this->espacio->modalidad_id) and (int)$this->espacio->modalidad_id === 2)
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Link')}}" />
                    <x-forms.textarea rows="2" class="flex-1 ml-4" wire:model="espacio.espacios_enlace">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="espacios_enlace"/>
            </div>
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-forms.red-button wire:click="$set('open_edit',false)">
                {{__('Cancel')}}
            </x-forms.red-button>
            <x-forms.blue-button wire:click="update"  wire:loading.attr="disabled" wire:target="update" class="disabled:opacity-65">
                {{__('Modify')}}
            </x-forms.blue-button>
            {{-- <span wire:loading wire:target="save">Cargando...</span> --}}
        </x-slot>
    </x-dialog-modal>

    @push('js');
    <script>
        livewire.on('deleteEspacio',itemId =>{
            Swal.fire({
                title:"{{__('Are you sure you want to delete the record?')}}",
                text: "{{__('You will not be able to reverse this!')}}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{__('Yes, delete!')}}",
                cancelButtonText: "{{__('Cancel')}}" ,
                cancelButtonColor: '#d33',
                confirmButtonColor: '#3085d6',
            }).then((result) => {
                if(result.isConfirmed){
                    livewire.emitTo('show-espacios','delete',itemId);
                }
            });
        })
    </script>
    @endpush
</div>
