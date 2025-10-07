<div wire:init="loadPosts">
    @section('content')
    <p>{{ __('Timetable') }}</p>
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
                    @livewire('create-profesores')
                    </div>
                </div>
            </x-slot>
            <table class="items-center bg-transparent w-full border-collapse">
                <thead>
                <tr>
                <th class="cursor-pointer px-2 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left"
                    wire:click="order('profesores_id')">
                    Id
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
                <th class="cursor-pointer px-6 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left"
                    wire:click="order('profesores_nombres')">
                    Nombres
                    @if ($sort == 'profesores_nombres')
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
                    wire:click="order('profesores_apellidos')">
                    Apellidos
                    @if ($sort == 'profesores_apellidos')
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
                    wire:click="order('profesores_fecha_ingreso')">
                    Fecha ingresos
                    @if ($sort == 'profesores_fecha_ingreso')
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
                    wire:click="order('profesores_email')">
                    Fecha ingresos
                    @if ($sort == 'profesores_email')
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
                    wire:click="order('modalidad_id')">
                    Modalidad
                    @if ($sort == 'modalidad_id')
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
                    wire:click="order('profesores_email')">
                    Color
                    @if ($sort == 'profesores_color')
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
                @forelse ( $profesores as $item )

                <tr>
                    <th class="border-t-0 px-2 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-left text-blueGray-700 ">
                        {{$item->profesores_id}}
                    </th>
                    <td class="border-t-0 px-4 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 ">
                        {{$item->profesores_nombres}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->profesores_apellidos}}
                    </td>
                    <td class="border-t-0 px-4 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 ">
                        {{\Carbon\Carbon::parse($item->profesores_fecha_ingreso)->format('d-m-Y')}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->profesores_email}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->modalidad_nombre}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        <div class="w-16 h-8 rounded" style="background-color: {{$item->profesores_color}};"></div>
                    </td>
                    <td class="flex border-t-0 px-4 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        <i class="fas fa-pen text-emerald-500 mr-4 cursor-pointer" wire:click="edit({{ $item->profesores_id }})"></i>
                        <i class="fas fa-trash text-red-500 mr-4 cursor-pointer" wire:click="$emit('deleteProfesor',{{$item->profesores_id}})"></i>
                    </td>
                </tr>
                @empty
                @if ($readyToLoad)
                <tr>
                    <th colspan="5" class="border-t-0 px-2 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-left text-blueGray-700 ">
                        No hay profesores cargados
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
            @if (count($profesores) > 0 and !is_array($profesores) and $profesores->hasPages())
                <div class="px-6 py-3">
                    {{$profesores->links()}}
                </div>
            @endif
        </x-table>




    <x-dialog-modal wire:model="open_edit">
        <x-slot name="title">
            Actualizar Profesores
        </x-slot>
        <x-slot name="content">
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Names')}}: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="profesor.profesores_nombres"/>
                </div>
                <x-forms.input-error for="profesores_nombres"/>
           </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Last names')}}: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="profesor.profesores_apellidos"/>
                </div>
                <x-forms.input-error for="profesores_apellidos"/>
           </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Email')}}: " />
                    <x-forms.input type="email" class="flex-1 ml-4" wire:model="profesor.profesores_email"/>
                </div>
                <x-forms.input-error for="profesores_email"/>
           </div>
           <div>
            <div class="mb-4 flex">
                <x-forms.label value="{{__('Color')}}: " />
                <x-forms.input type="color" class="ml-4 w-12" wire:model="profesor.profesores_color"/>
            </div>
            <x-forms.input-error for="profesores_color"/>
       </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Entry date')}}: " />
                    <x-forms.input type="date" class="flex-1 ml-4" wire:model="profesor.profesores_fecha_ingreso"/>
                </div>
                <x-forms.input-error for="profesores_fecha_ingreso"/>
           </div>
           <div>
            <div class="mb-4 flex">
                <x-forms.label value="{{__('Hours per week')}}: " />
                <x-forms.input type="number" class="flex-1 ml-4" wire:model="profesor.profesores_horas_semanales"/>
            </div>
            <x-forms.input-error for="profesores_horas_semanales"/>
       </div>
       <div>
        <div class="mb-4 flex">
            <x-forms.label value="{{__('Modality')}}: " />
            <x-select class="flex-1 ml-4" wire:model="profesor.modalidad_id">
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

        {{-- Seccion para Bloqueo de Horarios (Edición) --}}
        <div class="mt-6 pt-6 border-t border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-2">{{__('Inactive Hours')}}</h3>

            {{-- Bloqueos Recurrentes Semanales (Edición) --}}
            <div>
                <h4 class="text-md font-semibold text-gray-700">{{__('Recurring Weekly Blocks')}}</h4>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-x-4 gap-y-2 mt-2 items-end">
                    <div class="md:col-span-1">
                        <x-forms.label value="{{__('Day of Week')}}" />
                        {{-- Usamos nombres de propiedades diferentes para el formulario de edición para evitar conflictos --}}
                        <x-select wire:model="newRecurringBlockForUpdate.dayOfWeek" class="w-full mt-1">
                            <option value="">{{__('Select Day')}}</option>
                            @foreach ( $dias as $dia )
                                <option value="{{$dia->dias_id}}">{{$dia->dias_nombre}}</option>
                            @endforeach
                        </x-select>
                        <x-forms.input-error for="newRecurringBlockForUpdate.dayOfWeek" class="mt-1"/>
                    </div>
                    <div class="md:col-span-2">
                        <x-forms.label value="{{__('Time Slot')}}" />
                        <x-select wire:model.defer="newRecurringBlockForUpdate.horas_id" class="w-full mt-1">
                            <option value="">{{__('Select Time Slot')}}</option>
                            @foreach($horasDisponibles as $hora)
                                <option value="{{ $hora->horas_id }}">{{ \Carbon\Carbon::parse($hora->horas_desde)->format('H:i') }} - {{ \Carbon\Carbon::parse($hora->horas_hasta)->format('H:i') }}</option>
                            @endforeach
                        </x-select>
                        <x-forms.input-error for="newRecurringBlockForUpdate.horas_id" class="mt-1"/>
                    </div>
                    <div class="md:col-span-1 pt-2">
                        {{-- Método diferente para añadir en el contexto de actualización --}}
                        <x-forms.blue-button wire:click="addRecurringBlockForUpdate" type="button" class="w-full md:w-auto">
                            {{__('Add Recurring Block')}}
                        </x-forms.blue-button>
                    </div>
                </div>
                <div class="mt-3 space-y-2">
                    {{-- Iterar sobre los bloques recurrentes del profesor actual --}}
                    @forelse($currentRecurringBlocks as $index => $block)
                        @php
                            // Encontrar la hora correspondiente al horas_id para mostrarla
                            $horaSeleccionada = $horaAll->firstWhere('horas_id', $block['horas_id']);
                        @endphp
                        <div class="flex justify-between items-center p-2 border rounded-md text-sm">
                            <span>
                                {{-- Create a Carbon instance and set the day of the week --}}
                                {{ \Carbon\Carbon::now()->startOfWeek()->addDays($block['dayOfWeek']-1)->isoFormat('dddd') }}: {{ $horaSeleccionada ? \Carbon\Carbon::parse($horaSeleccionada->horas_desde)->format('H:i').' - '.\Carbon\Carbon::parse($horaSeleccionada->horas_hasta)->format('H:i') : __('Time not found') }}
                            </span>
                            {{-- Método diferente para remover en el contexto de actualización --}}
                            <button wire:click="removeRecurringBlockForUpdate({{ $index }})" type="button" class="text-red-500 hover:text-red-700 font-semibold">X</button>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Bloqueo de Días Completos (Edición) --}}
            <div class="mt-6">
                <h4 class="text-md font-semibold text-gray-700">{{__('Block Full Days')}}</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-2 mt-2 items-end">
                    <div>
                        <x-forms.label value="{{__('Date')}}" />
                        <x-forms.input type="date" wire:model.defer="newFullDayBlockDateForUpdate" class="w-full mt-1"/>
                        <x-forms.input-error for="newFullDayBlockDateForUpdate" class="mt-1"/>
                    </div>
                    <div class="pt-2">
                        <x-forms.blue-button wire:click="addFullDayBlockForUpdate" type="button" class="w-full md:w-auto">
                            {{__('Block Full Day')}}
                        </x-forms.blue-button>
                    </div>
                </div>
                <div class="mt-3 space-y-2">
                    @foreach($currentFullDayBlocks as $index => $blockDate)
                        <div class="flex justify-between items-center p-2 border rounded-md text-sm">
                            <span>
                                @php
                                    $parsedDate = null;
                                    $dateValueToParse = $blockDate['date'] ?? null;
                                    if ($dateValueToParse && !is_array($dateValueToParse)) {
                                        try {
                                            $parsedDate = \Carbon\Carbon::parse($dateValueToParse);
                                        } catch (\Exception $e) {
                                            $parsedDate = null; // Ensure it's null on failure
                                        }
                                    }
                                @endphp
                                {{ $parsedDate instanceof \Carbon\Carbon ? $parsedDate->format('d/m/Y') : ('Invalid Date: ' . print_r($dateValueToParse, true)) }}
                            </span>
                            <button wire:click="removeFullDayBlockForUpdate({{ $index }})" type="button" class="text-red-500 hover:text-red-700 font-semibold">X</button>
                        </div>
                    @endforeach
                </div>
            </div>
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
        livewire.on('deleteProfesor',itemId=>{
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
