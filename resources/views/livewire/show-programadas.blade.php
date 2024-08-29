<div wire:init="loadPosts">
    @section('content')
    <p>{{ __('Prospects') }}</p>
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
                </div>
            </x-slot>
            <table class="items-center bg-transparent w-full border-collapse">
                <thead>
                <tr>
                <th class="cursor-pointer px-2 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left"
                    wire:click="order('prospectos_clase_fecha')">
                    Fecha
                    @if ($sort == 'prospectos_clase_fecha')
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
                    wire:click="order('prospectos_clase_hora')">
                    Hora
                    @if ($sort == 'prospectos_clase_hora')
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
                    wire:click="order('prospectos_nombres')">
                    Nombre
                    @if ($sort == 'prospectos_nombres')
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
                    wire:click="order('prospectos_telefono')">
                    Teléfono
                    @if ($sort == 'prospectos_telefono')
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
                    wire:click="order('prospectos_correo')">
                    Correo
                    @if ($sort == 'prospectos_correo')
                        @if ($direction == 'asc')
                            <i class="fas fa-sort-alpha-up-alt float-right mt-1"></i>
                        @else
                            <i class="fas fa-sort-alpha-down-alt float-right mt-1"></i>
                        @endif
                    @else
                        <i class="fas fa-sort float-right mt-1"></i>
                    @endif
                </th>
                </tr>
                </thead>

                <tbody style="max-height: 10px;">
                @forelse ( $prospectos as $item )
                <tr>
                    <th class="border-t-0 px-2 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-left text-blueGray-700 ">
                        {{\Carbon\Carbon::parse($item->prospectos_clase_fecha)->format('d-m-Y')}} 
                    </th>
                    <td class="border-t-0 px-4 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 ">
                        {{$item->prospectos_clase_hora}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->prospectos_nombres}} {{$item->prospectos_apellidos}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->prospectos_telefono}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->prospectos_correo}}
                    </td>
                </tr>
                @empty
                @if ($readyToLoad)
                <tr>
                    <th colspan="5" class="border-t-0 px-2 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-left text-blueGray-700 ">
                        No hay propectos cargados
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
            @if (count($prospectos) > 0 and !is_array($prospectos) and $prospectos->hasPages())
                <div class="px-6 py-3">
                    {{$prospectos->links()}}
                </div>
            @endif
        </x-table>


</div>
