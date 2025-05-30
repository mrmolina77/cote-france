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
                <x-forms.input-error for="profesores_nombres"/>
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

        {{-- Seccion para Bloqueo de Horarios --}}
        <div class="mt-6 pt-6 border-t border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-2">{{__('Schedule Blocks')}}</h3>

            {{-- Bloqueos Recurrentes Semanales --}}
            <div>
                <h4 class="text-md font-semibold text-gray-700">{{__('Recurring Weekly Blocks')}}</h4>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-x-4 gap-y-2 mt-2 items-end">
                    <div class="md:col-span-1">
                        <x-forms.label value="{{__('Day of Week')}}" />
                        <x-select wire:model="newRecurringBlock.dayOfWeek" class="w-full mt-1">
                            <option value="">{{__('Select Day')}}</option>
                            @foreach ( $dias as $dia )
                                <option value="{{$dia->dias_id}}">{{$dia->dias_nombre}}</option>
                            @endforeach
                        </x-select>
                        <x-forms.input-error for="newRecurringBlock.dayOfWeek" class="mt-1"/>
                    </div>
                    <div class="md:col-span-2">
                        <x-forms.label value="{{__('Time Slot')}}" />
                        <x-select wire:model.defer="newRecurringBlock.horas_id" class="w-full mt-1">
                            <option value="">{{__('Select Time Slot')}}</option>
                            @foreach($horasDisponibles as $hora)
                                <option value="{{ $hora->horas_id }}">{{ \Carbon\Carbon::parse($hora->horas_desde)->format('H:i') }} - {{ \Carbon\Carbon::parse($hora->horas_hasta)->format('H:i') }}</option>
                            @endforeach
                        </x-select>
                        <x-forms.input-error for="newRecurringBlock.horas_id" class="mt-1"/>
                    </div>
                    <div class="md:col-span-1 pt-2">
                        <x-forms.blue-button wire:click="addRecurringBlock" type="button" class="w-full md:w-auto">
                            {{__('Add Recurring Block')}}
                        </x-forms.blue-button>
                    </div>
                </div>
                <div class="mt-3 space-y-2">
                    @foreach($recurringBlocks as $index => $block)
                        @php
                            // Encontrar la hora correspondiente al horas_id para mostrarla
                            $horaSeleccionada = $horasDisponibles->firstWhere('horas_id', $block['horas_id']);
                        @endphp
                        <div class="flex justify-between items-center p-2 border rounded-md text-sm">
                            <span>
                                {{-- Create a Carbon instance and set the day of the week --}}
                                {{ \Carbon\Carbon::now()->startOfWeek()->addDays($block['dayOfWeek']-1)->isoFormat('dddd') }}: {{ $horaSeleccionada ? \Carbon\Carbon::parse($horaSeleccionada->horas_desde)->format('H:i').' - '.\Carbon\Carbon::parse($horaSeleccionada->horas_hasta)->format('H:i') : __('Time not found') }}
                            </span>
                            <button wire:click="removeRecurringBlock({{ $index }})" type="button" class="text-red-500 hover:text-red-700 font-semibold">X</button>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Bloqueo de Días Completos --}}
            <div class="mt-6">
                <h4 class="text-md font-semibold text-gray-700">{{__('Block Full Days')}}</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-2 mt-2 items-end">
                    <div>
                        <x-forms.label value="{{__('Date')}}" />
                        <x-forms.input type="date" wire:model.defer="newFullDayBlockDate" class="w-full mt-1"/>
                        <x-forms.input-error for="newFullDayBlockDate" class="mt-1"/>
                    </div>
                    <div class="pt-2">
                        <x-forms.blue-button wire:click="addFullDayBlock" type="button" class="w-full md:w-auto">
                            {{__('Block Full Day')}}
                        </x-forms.blue-button>
                    </div>
                </div>
                <div class="mt-3 space-y-2">
                    @foreach($fullDayBlocks as $index => $blockDate)
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
                            <button wire:click="removeFullDayBlock({{ $index }})" type="button" class="text-red-500 hover:text-red-700 font-semibold">X</button>
                        </div>
                    @endforeach
                </div>
            </div>
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
