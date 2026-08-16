<div wire:init="loadPosts">
    @section('content')
    <p>{{ __('Enrollment') }}</p>
    @endsection
    <div class="mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <x-table>
            <x-slot:header>
                <div class="flex flex-wrap items-center">
                    <div class="flex items-center">
                        <span>{{__('Show')}}</span>
                        <x-select class="mx-2" wire:model="cant">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="75">75</option>
                            <option value="100">100</option>
                        </x-select>
                        <span>{{__('rows')}}</span>
                    </div>
                    <div class="relative w-full px-4 max-w-full flex-grow flex-1">
                        <div class="px-6 py-4">
                            <x-forms.input type="text" placeholder="{{__('Search')}}..." class="flex-1 ml-4" wire:model="search"/>
                        </div>
                    </div>
                    <div class="relative w-full px-4 max-w-full flex-grow flex-1 text-right">
                    @livewire('create-inscripciones')
                    </div>
                </div>
            </x-slot>
            <div class="overflow-x-auto"><table class="items-center bg-transparent w-full border-collapse">
                <thead>
                <tr>
                <th class="cursor-pointer px-2 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left"
                    wire:click="order('inscripciones_id')">
                    Id
                    @if ($sort == 'inscripciones_id')
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
                    wire:click="order('fecha_inscripcion')">
                    Fecha
                    @if ($sort == 'fecha_inscripcion')
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
                    wire:click="order('cursos_id')">
                    Cursos
                    @if ($sort == 'cursos_id')
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
                    wire:click="order('grupo_id')">
                    Grupos
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
                <th class="cursor-pointer px-4 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left"
                    wire:click="order('prospectos_id')">
                    Prospecto
                    @if ($sort == 'prospectos_id')
                        @if ($direction == 'asc')
                            <i class="fas fa-sort-alpha-up-alt float-right mt-1"></i>
                        @else
                            <i class="fas fa-sort-alpha-down-alt float-right mt-1"></i>
                        @endif
                    @else
                        <i class="fas fa-sort float-right mt-1"></i>
                    @endif
                    </th>
                <th wire:click="order('estatus')" class="cursor-pointer px-4 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase whitespace-nowrap">Estatus</th>
                <th wire:click="order('monto_mensualidad')" class="cursor-pointer px-4 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase whitespace-nowrap">Mensualidad</th>
                <th class="px-4 bg-blueGray-50 text-blueGray-500 border py-3 text-xs uppercase whitespace-nowrap">Responsable</th>
                <th class="px-4 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
                    Acción
                    </th>
                </tr>
                </thead>

                <tbody style="max-height: 10px;">
                @forelse ( $inscripciones as $item )

                <tr>
                    <th class="border-t-0 px-2 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-left text-blueGray-700 ">
                        {{$item->inscripciones_id}}
                    </th>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{\Carbon\Carbon::parse($item->fecha_inscripcion)->format('d-m-Y')}}
                    </td>
                    <td class="border-t-0 px-4 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 ">
                        {{$item->cursos_descripcion}}
                    </td>
                    <td class="border-t-0 px-4 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 ">
                        {{$item->grupo_nombre}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->prospectos_nombres}} {{$item->prospectos_apellidos}}
                    </td>
                    <td class="px-4 text-xs whitespace-nowrap"><span class="px-2 py-1 rounded {{ ['activa'=>'bg-green-100 text-green-700','suspendida'=>'bg-yellow-100 text-yellow-700','finalizada'=>'bg-blue-100 text-blue-700','cancelada'=>'bg-red-100 text-red-700'][$item->estatus] ?? 'bg-gray-100 text-gray-600' }}">{{ $item->estatus ? ucfirst($item->estatus) : 'Sin configurar' }}</span></td>
                    <td class="px-4 text-xs whitespace-nowrap">{{ $item->monto_mensualidad === null ? 'Sin configurar' : '$'.number_format($item->monto_mensualidad, 2).' '.($item->moneda ?: 'MXN') }}</td>
                    <td class="px-4 text-xs whitespace-nowrap">{{ $item->responsable_nombre ?: 'Sin configurar' }}</td>
                    <td class="flex border-t-0 px-4 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        <i class="fas fa-pen text-emerald-500 mr-4 cursor-pointer" wire:click="edit({{ $item->inscripciones_id }})"></i>
                        <i class="fas fa-trash text-red-500 mr-4 cursor-pointer" wire:click="$emit('deleteInscripcion',{{$item->inscripciones_id}})"></i>
                    </td>
                </tr>
                @empty
                @if ($readyToLoad)
                <tr>
                    <th colspan="5" class="border-t-0 px-2 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-left text-blueGray-700 ">
                        No hay inscripciones cargadas
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

            </table></div>
            @if (count($inscripciones) > 0 and !is_array($inscripciones) and $inscripciones->hasPages())
                <div class="px-6 py-3">
                    {{$inscripciones->links()}}
                </div>
            @endif
        </x-table>




    <x-dialog-modal wire:model="open_edit">
        <x-slot name="title">
            Actualizar Inscripción
        </x-slot>
        <x-slot name="content">
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Prospect')}}: " />
                    <x-select class="flex-1 ml-4" wire:model="inscripcion.prospectos_id">
                        <option value="">Seleccionar</option>
                        @forelse ($prospectos as $item)
                        <option value="{{$item->prospectos_id}}">{{$item->prospectos_nombres}} {{$item->prospectos_apellidos}}</option>
                        @empty
                        <option value="">Sin prospectos</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="inscripcion.prospectos_id"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Course')}}: " />
                    <x-select class="flex-1 ml-4" wire:model="inscripcion.cursos_id">
                        <option value="">{{__('Select')}}</option>
                        @forelse ($cursos as $item)
                        <option value="{{$item->cursos_id}}">{{$item->cursos_descripcion}}</option>
                        @empty
                        <option value="">{{__('No Content')}}</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="inscripcion.cursos_id"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Groups')}}: " />
                    <x-select class="flex-1 ml-4" wire:model="inscripcion.grupo_id">
                        <option value="">{{__('Select')}}</option>
                        @forelse ($grupos as $item)
                        <option value="{{$item->grupo_id}}">{{$item->grupo_nombre}}</option>
                        @empty
                        <option value="">{{__('No Content')}}</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="inscripcion.grupo_id"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Date')}}: " />
                    <x-forms.input type="date" class="flex-1 ml-4" wire:model="inscripcion.fecha_inscripcion"/>
                </div>
                <x-forms.input-error for="inscripcion.fecha_inscripcion"/>
           </div>
           <div class="mt-6 space-y-4"><h3 class="font-semibold text-blue-700">Condiciones económicas</h3><div class="grid md:grid-cols-2 gap-4">
           <label>Estatus<x-select class="w-full" wire:model.defer="inscripcion.estatus"><option value="">Sin configurar</option>@foreach(['activa','suspendida','finalizada','cancelada'] as $v)<option value="{{$v}}">{{ucfirst($v)}}</option>@endforeach</x-select><x-forms.input-error for="inscripcion.estatus"/></label>
           <label>Fecha inicio<x-forms.input type="date" class="w-full" wire:model.defer="inscripcion.fecha_inicio"/><x-forms.input-error for="inscripcion.fecha_inicio"/></label>
           <label>Fecha final<x-forms.input type="date" class="w-full" wire:model.defer="inscripcion.fecha_fin"/><x-forms.input-error for="inscripcion.fecha_fin"/></label>
           <label>Moneda<x-forms.input value="MXN" class="w-full bg-gray-100" disabled/></label>
           @foreach(['monto_inscripcion'=>'Monto inscripción','monto_mensualidad'=>'Monto mensualidad','dia_vencimiento'=>'Día vencimiento','numero_mensualidades'=>'Número mensualidades','descuento'=>'Descuento (%)','beca'=>'Beca (%)'] as $field=>$label)<label>{{$label}}<x-forms.input type="number" step="0.01" class="w-full" wire:model.defer="inscripcion.{{$field}}"/><x-forms.input-error for="inscripcion.{{$field}}"/></label>@endforeach
           <label class="md:col-span-2">Observaciones<textarea class="w-full rounded border-gray-300" wire:model.defer="inscripcion.observaciones_financieras"></textarea><x-forms.input-error for="inscripcion.observaciones_financieras"/></label></div>
           @include('livewire.partials.responsable-pago-fields', ['permitirConservar' => true])</div>
        </x-slot>
        <x-slot name="footer">
            <x-forms.red-button wire:click="$set('open_edit',false)">
                {{__('Cancel')}}
            </x-forms.red-button>
            <x-forms.blue-button wire:click="update" wire:loading.attr="disabled" wire:target="update" class="disabled:opacity-65">
                {{__('Modify')}}
            </x-forms.blue-button>
            {{-- <span wire:loading wire:target="save">Cargando...</span> --}}
        </x-slot>
    </x-dialog-modal>

    @push('js');
    <script>
        livewire.on('deleteInscripcion',itemId=>{
            Swal.fire({
            title: "{{__('Are you sure you want to delete the record?')}}",
            text: "{{__('You will not be able to reverse this!')}}",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "{{__('Yes, delete it!')}}"
            }).then((result) => {
            if (result.isConfirmed) {
                livewire.emitTo('show-inscripciones','delete',itemId);
            }
            });
        })
    </script>
    @endpush
</div>
