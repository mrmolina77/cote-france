<div>
    @section('content')
    <p>{{ __('Timetable') }}</p>
    @endsection
    <div class="w-full px-0" wire:ignore.self>
        @if ($semanal)
            {{-- Cabecera Fija --}}
            <div class="border p-2 bg-gray-100">
                <div class="grid h-full max-w-lg grid-cols-4 gap-4 mx-auto">
                    <div class="col-span-full text-center font-bold">
                        <div>Semana # {{$semana}}</div>
                    </div>
                    <div class="flex items-center justify-center">
                        <button class="w-full py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700" wire:click="anterior">
                            Anterior
                        </button>
                    </div>
                    <div class="flex items-center">
                        <x-select id="porcentaje-select" class="w-full text-sm font-medium text-gray-900 py-2.5 px-5" wire:model="porcentaje">
                            @forelse ($porcentajes as $key => $item)
                            <option value="{{$key}}">{{$item}}</option>
                            @empty
                            <option value="">{{__('No Content')}}</option>
                            @endforelse
                        </x-select>
                    </div>
                    <div class="flex items-center">
                        <x-select id="semanal-select" class="w-full text-sm font-medium text-gray-900" wire:model="semanal">
                            <option value="1">{{__('Weekly')}}</option>
                            <option value="0">{{__('Daily')}}</option>
                        </x-select>
                    </div>
                    <div class="flex items-center justify-center">
                        <button class="w-full py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700" wire:click="siguiente">
                            Siguiente
                        </button>
                    </div>
                </div>
            </div>

            {{-- Contenedor del Grid con Scroll --}}
            <div @class([
                'overflow-x-auto origin-top-left',
                'scale-100 w-full' => $porcentaje == '0',
                'scale-95 w-[105.26%]' => $porcentaje == '1',
                'scale-90 w-[111.11%]' => $porcentaje == '2',
                'scale-75 w-[133.33%]' => $porcentaje == '3',
                'scale-50 w-[200%]' => $porcentaje == '4',
            ]) wire:updated="initializeDragAndDrop">
                <div class="grid min-w-max border-2 border-gray-200 rounded-lg overflow-hidden" id="horarios-table" style="display: grid; grid-template-columns: auto repeat({{ count($dias) * count($profesores) }}, minmax(8rem, 1fr)) auto repeat({{ count($dias2) * count($profesores) }}, minmax(8rem, 1fr));">
                {{-- Day/Professor Headers --}}
                <div class="border-r border-gray-200 p-2 w-20 sticky top-0 bg-gray-50 z-10 flex items-center justify-center font-sans font-semibold text-base">{{ __('Hours') }}</div>
                @foreach ( $dias as $dia )
                    <div class="border-r border-gray-200 p-[10px] sticky top-0 bg-gray-50 z-10" style="grid-column: span {{ count($profesores) }};">
                        <div class="text-center font-sans font-semibold text-base">{{$dia->dias_nombre}} {{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('DD')}}</div>
                        <div class="grid" style="grid-template-columns: repeat({{ count($profesores) }}, 1fr);">
                            @foreach ($profesores as $profesor)
                            <div class="w-full items-center justify-center p-1">
                                <div style="background-color:{{$profesor->profesores_color}}" class="overflow-hidden text-ellipsis whitespace-nowrap font-sans font-semibold text-sm text-center text-white rounded-md py-1">{{$profesor->profesores_nombres}}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div class="border-r border-gray-200 p-2 w-20 sticky top-0 bg-gray-50 z-10 flex items-center justify-center font-sans font-semibold text-base">{{ __('Hours') }}</div>
                @foreach ( $dias2 as $dia )
                    <div class="border-r border-gray-200 p-[10px] sticky top-0 bg-gray-50 z-10" style="grid-column: span {{ count($profesores) }};">
                        <div class="text-center font-sans font-semibold text-base">{{$dia->dias_nombre}} {{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('DD')}}</div>
                        <div class="grid" style="grid-template-columns: repeat({{ count($profesores) }}, 1fr);">
                            @foreach ($profesores as $profesor)
                            <div class="w-full items-center justify-center p-1">
                                <div style="background-color:{{$profesor->profesores_color}}" class="overflow-hidden text-ellipsis whitespace-nowrap font-sans font-semibold text-sm text-center text-white rounded-md py-1">{{$profesor->profesores_nombres}}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                {{-- Schedule Body --}}
                @foreach ($horas as $pos1 => $hora)
                    {{-- Hour Cell --}}
                    <div class="border-r border-gray-200 text-center p-2 flex items-center justify-center"><samp class="font-sans font-semibold text-sm leading-tight">{{\Carbon\Carbon::parse($hora->horas_desde)->format('H:i')}}<br>{{\Carbon\Carbon::parse($hora->horas_hasta)->format('H:i')}}</samp></div>

                    {{-- Dias (Mon-Fri) --}}
                    @foreach ($dias as $dia)
                        @foreach ($profesores as $profesor)
                            @php
                                $currentDateString = \Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD');
                                $isBlocked = isset($bloqueosProfesores[$profesor->profesores_id]['full_days'][$currentDateString]) || isset($bloqueosProfesores[$profesor->profesores_id]['recurring'][$dia->dias_id][$hora->horas_id]);
                                $horarioItem = $horarios[$currentDateString][$hora->horas_id][$profesor->profesores_id] ?? null;
                            @endphp

                            @if ($horarioItem)
                                @php
                                    $nombreDelHorario = $horarioItem['nombre'];
                                    $estilosParaDiv = "color: " . e($horarioItem['color']) . ";";
                                    $estilosDisplay = "";
                                    $cellgrupo = "grupo-cell";
                                    if (strtolower(trim($nombreDelHorario)) === 'bloqueado') {
                                        $estilosParaDiv .= " transform: rotate(-45deg);";
                                        $estilosDisplay = "display: flex; justify-content: center; align-items: center;";
                                        $cellgrupo = "";
                                    }
                                @endphp
                                <div class="h-full p-1 text-center {{$cellgrupo}}"
                                    data-id="{{ $horarioItem['id'] }}"
                                    data-dia="{{ $currentDateString }}"
                                    data-espacio="{{ $horarioItem['espacios_id'] }}"
                                    data-hora="{{ $hora->horas_id }}"
                                    data-grupo="{{ $horarioItem['grupo_id'] }}"
                                    data-profesor="{{ $profesor->profesores_id }}">
                                    <div style="{{$estilosDisplay}}" class="w-full min-h-20 grid grid-cols-1 {{$horarioItem['bgcolor']}} rounded-md">
                                        <div style="{{ $estilosParaDiv }}" class="font-sans text-sm font-extrabold overflow-hidden text-ellipsis whitespace-nowrap w-full text-center uppercase">
                                            @if ($horarioItem['modalidad'] == '2')
                                                <a href="{{$horarioItem['enlace']}}" target="_blank" rel="noopener noreferrer">{{$nombreDelHorario}}</a>
                                            @elseif ($nombreDelHorario === "BLOQUEADO")
                                                <span class="text-red-500 font-bold">&nbsp;</span>
                                            @else
                                                {{$nombreDelHorario}}
                                            @endif
                                        </div>
                                        @if(strtoupper(trim($nombreDelHorario)) !== "BLOQUEADO")
                                            <div class="flex items-center justify-center">
                                                <div><i class="fas fa-trash text-red-500 m-2 cursor-pointer" wire:click="$emit('deleteHorario',{{ $horarioItem['id'] }})"></i></div>
                                                <div><i class="fas fa-calendar-check text-green-500 m-2 cursor-pointer" wire:click="editPlan({{ $horarioItem['id'] }})"></i></div>
                                                <div><i class="fas fa-book text-blue-500 m-2 cursor-pointer" wire:click="editDiario({{ $horarioItem['id'] }})"></i></div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @elseif ($isBlocked)
                                <div class="h-full p-1 text-center">
                                    <div class="w-full min-h-20 grid grid-cols-1 justify-center items-center bg-gray-300 text-gray-600 rounded-md" wire:key="blocked-{{ $dia->dias_id }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                        <span class="text-xs font-semibold">{{ __('Blocked') }}</span>
                                    </div>
                                </div>
                            @else
                                @php $grupoDetalle = $grupo_deta[$dia->dias_id][$hora->horas_id][$profesor->profesores_id] ?? null; @endphp
                                @if($grupoDetalle)
                                    <div class="h-full p-1 text-center grupo-cell"
                                        data-id="0"
                                        data-dia="{{$currentDateString}}"
                                        data-espacio="{{$grupoDetalle['espacios_id']}}"
                                        data-hora="{{$hora->horas_id}}"
                                        data-grupo="{{$grupoDetalle['grupo_id']}}"
                                        data-profesor="{{ $profesor->profesores_id }}">
                                        <div class="w-full min-h-20 grid grid-cols-1 justify-center items-center {{$grupoDetalle['color']}} uppercase rounded-md" wire:key="task-{{ $dia->dias_id }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                            <div class="overflow-hidden text-ellipsis whitespace-nowrap text-center font-sans font-extrabold text-sm uppercase">
                                                {{$grupoDetalle['grupo_nombre']}}
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="h-full p-1 text-center grupo-cell"
                                        data-id="0"
                                        data-dia="{{$currentDateString}}"
                                        data-espacio="0"
                                        data-hora="{{$hora->horas_id}}"
                                        data-grupo="0"
                                        data-profesor="{{ $profesor->profesores_id }}">
                                        <div class="w-full min-h-20 grid grid-cols-1 justify-center items-center bg-amber-50 rounded-md" wire:key="task-{{ $dia->dias_id }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                            <i class="fas fa-plus text-emerald-500 cursor-pointer" wire:click="edit('{{$currentDateString}}',{{ $profesor->profesores_id }},{{$hora->horas_id}},{{$profesor->profesores_id}})"></i>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        @endforeach
                    @endforeach

                    {{-- Hour Cell (Weekend) --}}
                    <div class="border-r border-gray-200 text-center p-2 flex items-center justify-center">
                        @if (isset($horas2[$pos1]) && $horas2[$pos1]->horas_id < 14)
                            <samp class="font-sans font-semibold text-sm leading-tight">{{\Carbon\Carbon::parse($horas2[$pos1]->horas_desde)->format('H:i')}}<br>{{\Carbon\Carbon::parse($horas2[$pos1]->horas_hasta)->format('H:i')}}</samp>
                        @else
                            <samp class="font-sans font-extrabold text-sm">&nbsp;</samp>
                        @endif
                    </div>

                    {{-- Dias2 (Sat/Sun) --}}
                    @foreach ($dias2 as $dia)
                        @foreach ($profesores as $profesor)
                             @php
                                $currentDateString = \Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD');
                                $currentHourId = $horas2[$pos1]->horas_id ?? null;
                                $isBlocked = $currentHourId ? (isset($bloqueosProfesores[$profesor->profesores_id]['full_days'][$currentDateString]) || isset($bloqueosProfesores[$profesor->profesores_id]['recurring'][$dia->dias_id][$currentHourId])) : false;
                                $horarioItem = ($currentHourId && isset($horarios[$currentDateString][$currentHourId][$profesor->profesores_id])) ? $horarios[$currentDateString][$currentHourId][$profesor->profesores_id] : null;
                            @endphp

                            @if ($horarioItem)
                                @php
                                    $nombreDelHorario = $horarioItem['nombre'];
                                    $estilosParaDiv = "color: " . e($horarioItem['color']) . ";";
                                    $estilosDisplay = "";
                                    $cellgrupo = "grupo-cell";
                                    if (strtolower(trim($nombreDelHorario)) === 'bloqueado') {
                                        $estilosParaDiv .= " transform: rotate(-45deg);";
                                        $estilosDisplay = "display: flex; justify-content: center; align-items: center;";
                                        $cellgrupo = "";
                                    }
                                @endphp
                                <div class="h-full p-1 text-center {{$cellgrupo}}"
                                    data-id="{{ $horarioItem['id'] }}"
                                    data-dia="{{ $currentDateString }}"
                                    data-espacio="{{ $horarioItem['espacios_id'] }}"
                                    data-hora="{{ $currentHourId }}"
                                    data-grupo="{{ $horarioItem['grupo_id'] }}"
                                    data-profesor="{{ $profesor->profesores_id }}">
                                    <div style="{{$estilosDisplay}}" class="w-full min-h-20 grid grid-cols-1 {{$horarioItem['bgcolor']}} rounded-md">
                                        <div style="{{ $estilosParaDiv }}" class="font-sans text-sm font-extrabold overflow-hidden text-ellipsis whitespace-nowrap w-full text-center uppercase">
                                            @if ($nombreDelHorario === "BLOQUEADO")
                                                <span class="text-red-500 font-bold">&nbsp;</span>
                                            @else
                                                {{$nombreDelHorario}}
                                            @endif
                                        </div>
                                        @if(strtoupper(trim($nombreDelHorario)) !== "BLOQUEADO")
                                            <div class="flex items-center justify-center">
                                                <div><i class="fas fa-trash text-red-500 m-2 cursor-pointer" wire:click="$emit('deleteHorario',{{ $horarioItem['id'] }})"></i></div>
                                                <div><i class="fas fa-calendar-check text-green-500 m-2 cursor-pointer" wire:click="editPlan({{ $horarioItem['id'] }})"></i></div>
                                                <div><i class="fas fa-book text-blue-500 m-2 cursor-pointer" wire:click="editDiario({{ $horarioItem['id'] }})"></i></div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @elseif ($isBlocked)
                                <div class="h-full p-1 text-center">
                                    <div class="w-full min-h-20 grid grid-cols-1 justify-center items-center bg-gray-300 text-gray-600 rounded-md" wire:key="blocked-{{ $dia->dias_id }}-{{ $currentHourId }}-{{ $profesor->profesores_id }}">
                                        <span class="text-xs font-semibold">{{ __('Blocked') }}</span>
                                    </div>
                                </div>
                            @else
                                @php $grupoDetalle = ($currentHourId && isset($grupo_deta[$dia->dias_id][$currentHourId][$profesor->profesores_id])) ? $grupo_deta[$dia->dias_id][$currentHourId][$profesor->profesores_id] : null; @endphp
                                @if($grupoDetalle)
                                    <div class="h-full p-1 text-center grupo-cell"
                                        data-id="0"
                                        data-dia="{{$currentDateString}}"
                                        data-espacio="{{$grupoDetalle['espacios_id']}}"
                                        data-hora="{{$currentHourId}}"
                                        data-grupo="{{$grupoDetalle['grupo_id']}}"
                                        data-profesor="{{ $profesor->profesores_id }}">
                                        <div class="w-full min-h-20 grid grid-cols-1 justify-center items-center {{$grupoDetalle['color']}} uppercase rounded-md" wire:key="task-{{ $dia->dias_id }}-{{ $currentHourId }}-{{ $profesor->profesores_id }}">
                                            <div class="overflow-hidden text-ellipsis whitespace-nowrap text-center font-sans font-extrabold text-sm uppercase">
                                                {{$grupoDetalle['grupo_nombre']}}
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="h-full p-1 text-center grupo-cell"
                                        data-id="0"
                                        data-dia="{{$currentDateString}}"
                                        data-espacio="0"
                                        data-hora="{{$currentHourId}}"
                                        data-grupo="0"
                                        data-profesor="{{ $profesor->profesores_id }}">
                                        @if ($currentHourId && $currentHourId < 14)
                                            <div class="w-full min-h-20 grid grid-cols-1 justify-center items-center bg-amber-50 rounded-md" wire:key="task-{{ $dia->dias_id }}-{{ $currentHourId }}-{{ $profesor->profesores_id }}">
                                                <i class="fas fa-plus text-emerald-500 cursor-pointer" wire:click="edit('{{$currentDateString}}',{{ $profesor->profesores_id }},{{$currentHourId}},{{$profesor->profesores_id}})"></i>
                                            </div>
                                        @else
                                            <div class="w-full min-h-20 grid grid-cols-1 justify-center items-center bg-amber-50 rounded-md"></div>
                                        @endif
                                    </div>
                                @endif
                            @endif
                        @endforeach
                    @endforeach
                @endforeach
                </div>
            </div>
        @else
            {{-- Cabecera Fija --}}
            <div class="border p-2 bg-gray-100">
                <div class="grid h-full max-w-lg grid-cols-2 gap-4 mx-auto p-2">
                    <div>
                        <input type="date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" wire:model="ydiario">
                    </div>
                    <div>
                        <x-select id="semanal-select" class="w-full text-sm font-medium text-gray-900 p-2.5" wire:model="semanal">
                            <option value="1">{{__('Weekly')}}</option>
                            <option value="0">{{__('Daily')}}</option>
                        </x-select>
                    </div>
                </div>
            </div>

            {{-- Contenedor del Grid con Scroll --}}
            <div @class([
                'overflow-x-auto origin-top-left',
                'scale-100 w-full' => $porcentaje == '0',
                'scale-95 w-[105.26%]' => $porcentaje == '1',
                'scale-90 w-[111.11%]' => $porcentaje == '2',
                'scale-75 w-[133.33%]' => $porcentaje == '3',
                'scale-50 w-[200%]' => $porcentaje == '4',
            ]) wire:updated="initializeDragAndDrop">
                <div class="grid min-w-max border-2 border-gray-200 rounded-lg overflow-hidden" id="horarios-table" style="display: grid; grid-template-columns: auto repeat({{ count($profesores) }}, minmax(8rem, 1fr));">
                {{-- Professor Headers --}}
                <div class="border-r border-gray-200 p-2 w-20 sticky top-0 bg-gray-50 z-10 flex items-center justify-center font-sans font-semibold text-base">{{ __('Hours') }}</div>
                @foreach ( $profesores as $profesor )
                    <div class="border p-2 sticky top-0 bg-gray-50 z-10 text-center">
                        <div style="background-color:{{$profesor->profesores_color}}" class="overflow-hidden text-ellipsis whitespace-nowrap font-sans font-semibold text-sm text-white rounded-md py-1">{{$profesor->profesores_nombres}}</div>
                    </div>
                @endforeach

                {{-- Schedule Body --}}
                @foreach ( $horas as $hora )
                    {{-- Hour Cell --}}
                    <div class="border-r border-gray-200 text-center p-2 flex items-center justify-center"><samp class="font-sans font-semibold text-sm leading-tight">{{\Carbon\Carbon::parse($hora->horas_desde)->format('H:i')}}<br>{{\Carbon\Carbon::parse($hora->horas_hasta)->format('H:i')}}</samp></div>

                    {{-- Professor Slots for this Hour --}}
                    @foreach ($profesores as $profesor)
                        @php
                            $currentDailyDateString = \Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD');
                            $currentDayOfWeek = \Carbon\Carbon::parse($fecha)->dayOfWeekIso;
                            $isBlockedDaily = isset($bloqueosProfesores[$profesor->profesores_id]['full_days'][$currentDailyDateString]) || isset($bloqueosProfesores[$profesor->profesores_id]['recurring'][$currentDayOfWeek][$hora->horas_id]);
                            $horarioItem = $horarios[$currentDailyDateString][$hora->horas_id][$profesor->profesores_id] ?? null;
                        @endphp

                        @if ($horarioItem)
                            @php
                                $nombreDelHorario = $horarioItem['nombre'];
                                $estilosParaDiv = "color: " . e($horarioItem['color']) . ";";
                                $estilosDisplay = "";
                                $cellgrupo = "grupo-cell";
                                if (strtolower(trim($nombreDelHorario)) === 'bloqueado') {
                                    $estilosParaDiv .= " transform: rotate(-45deg);";
                                    $estilosDisplay = "display: flex; justify-content: center; align-items: center;";
                                    $cellgrupo = "";
                                }
                            @endphp
                            <div class="h-full border p-0 text-center {{$cellgrupo}}"
                                data-id="{{ $horarioItem['id'] }}"
                                data-dia="{{ $currentDailyDateString }}"
                                data-espacio="{{ $horarioItem['espacios_id'] }}"
                                data-hora="{{ $hora->horas_id }}"
                                data-grupo="{{ $horarioItem['grupo_id'] }}"
                                data-profesor="{{ $profesor->profesores_id }}">
                                <div style="{{$estilosDisplay}}" class="w-full min-h-20 grid grid-cols-1 {{$horarioItem['bgcolor']}}">
                                    <div style="{{ $estilosParaDiv }}" class="font-sans text-sm font-extrabold overflow-hidden text-ellipsis whitespace-nowrap w-full text-center uppercase">
                                        @if ($horarioItem['modalidad'] == '2')
                                            <a href="{{$horarioItem['enlace']}}" target="_blank" rel="noopener noreferrer">{{$nombreDelHorario}}</a>
                                        @elseif ($nombreDelHorario === "BLOQUEADO")
                                            <span class="text-red-500 font-bold">&nbsp;</span>
                                        @else
                                            {{$nombreDelHorario}}
                                        @endif
                                    </div>
                                    @if(strtoupper(trim($nombreDelHorario)) !== "BLOQUEADO")
                                        <div class="flex items-center justify-center">
                                            <div><i class="fas fa-trash text-red-500 m-2 cursor-pointer" wire:click="$emit('deleteHorario',{{ $horarioItem['id'] }})"></i></div>
                                            <div><i class="fas fa-calendar-check text-green-500 m-2 cursor-pointer" wire:click="editPlan({{ $horarioItem['id'] }})"></i></div>
                                            <div><i class="fas fa-book text-blue-500 m-2 cursor-pointer" wire:click="editDiario({{ $horarioItem['id'] }})"></i></div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @elseif ($isBlockedDaily)
                            <div class="h-full border p-0 text-center">
                                <div class="w-full min-h-20 grid grid-cols-1 justify-center items-center bg-gray-300 text-gray-600" wire:key="blocked-daily-{{ $currentDailyDateString }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                    <span class="text-xs font-semibold">{{ __('Blocked') }}</span>
                                </div>
                            </div>
                        @else
                            @php $grupoDetalle = $grupo_deta[$currentDayOfWeek][$hora->horas_id][$profesor->profesores_id] ?? null; @endphp
                            @if($grupoDetalle)
                                <div class="h-full border p-0 text-center grupo-cell"
                                    data-id="0"
                                    data-dia="{{$currentDailyDateString}}"
                                    data-espacio="{{$grupoDetalle['espacios_id']}}"
                                    data-hora="{{$hora->horas_id}}"
                                    data-grupo="{{$grupoDetalle['grupo_id']}}"
                                    data-profesor="{{ $profesor->profesores_id }}">
                                    <div class="w-full min-h-20 grid grid-cols-1 justify-center items-center {{$grupoDetalle['color']}} uppercase" wire:key="task-daily-{{ $currentDayOfWeek }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                        <div class="overflow-hidden text-ellipsis whitespace-nowrap text-center font-sans font-extrabold text-sm uppercase">
                                            {{$grupoDetalle['grupo_nombre']}}
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="h-full border p-0 text-center grupo-cell"
                                    data-id="0"
                                    data-dia="{{$currentDailyDateString}}"
                                    data-espacio="0"
                                    data-hora="{{$hora->horas_id}}"
                                    data-grupo="0"
                                    data-profesor="{{ $profesor->profesores_id }}">
                                    <div class="w-full min-h-20 grid grid-cols-1 justify-center items-center bg-amber-50 rounded-md" wire:key="task-daily-{{ $currentDayOfWeek }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                        <i class="fas fa-plus text-emerald-500 cursor-pointer" wire:click="edit('{{$currentDailyDateString}}',{{ $profesor->profesores_id }},{{$hora->horas_id}},{{$profesor->profesores_id}})"></i>
                                    </div>
                                </div>
                            @endif
                        @endif
                    @endforeach
                @endforeach
                </div>
            </div>
        @endif
    </div>
    <x-dialog-modal  wire:model="open_edit">
        <x-slot name="title">
            Agregar diario
        </x-slot>
        <x-slot name="content">
            <div>
                <div class="mb-4 flex">
                    <x-forms.label for="grupo_id" value="{{__('Group')}}: "/>
                    <x-select class="flex-1 ml-4" wire:model.defer="grupo_id" id="grupo_id">
                        <option value="">{{__('Select')}}</option>
                        @forelse ($grupos as $item)
                        <option value="{{$item->grupo_id}}">{{$item->grupo_nombre}}</option>
                        @empty
                        <option value="">{{__('No Content')}}</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="grupo_id"/>
                <div>
                    <div class="mb-4 flex">
                        <x-forms.label for="id_espacios" value="{{__('Salons')}}: "/>
                        <x-select class="flex-1 ml-4" wire:model="id_espacios" id="id_espacios">
                            <option value="">{{__('Select')}}</option>
                            @forelse ($espacios as $item)
                            <option value="{{$item->espacios_id}}">{{$item->espacios_nombre}}</option>
                            @empty
                            <option value="">{{__('No Content')}}</option>
                            @endforelse
                        </x-select>
                    </div>
                    <x-forms.input-error for="id_espacios"/>
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-forms.red-button wire:click="$set('open_edit',false)">
                {{__('Cancel')}}
            </x-forms.red-button>
            <x-forms.blue-button wire:click="save"  wire:loading.attr="disabled" wire:click="save" class="disabled:opacity-65">
                {{__('Create')}}
            </x-forms.blue-button>
            {{-- <span wire:loading wire:target="save">Cargando...</span> --}}
        </x-slot>
    </x-dialog-modal>

    <x-dialog-modal wire:model="open_edit_diario">
        <x-slot name="title">
            Actualizar diario
        </x-slot>
        <x-slot name="content">

            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Teacher')}}: " class="font-bold mr-4" />
                    <x-forms.label value="{{ $diarios_profesor}} "/> {{-- Añadido font-bold y margen --}}
                    <x-forms.label value="{{__('Salon')}}: " class="font-bold"/>
                    <x-select class="flex-1 ml-4" wire:model="espacios_id">
                        <option value="">{{__('Select')}}</option>
                        @forelse ($espacios as $item)
                        <option value="{{$item->espacios_id}}">{{$item->espacios_nombre}}</option>
                        @empty
                        <option value="">{{__('No Content')}}</option>
                        @endforelse
                    </x-select>
                </div>
                <div class="mb-4 flex">
                    <x-forms.label for="diarios_hecho" value="{{__('Done')}}: "/>
                    <x-forms.textarea id="diarios_hecho" rows="3" class="flex-1 ml-4" wire:model="diarios_hecho">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="diarios_hecho"/>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('To do')}}: "/>
                    <x-forms.textarea id="diarios_porhacer" rows="3" class="flex-1 ml-4" wire:model="diarios_porhacer">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="diarios_porhacer"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Level')}}: " required/>
                    <x-select class="flex-1 ml-4" wire:model="idnivel">
                        <option value="">{{__('Select')}}</option>
                        @forelse ($arr_niveles as $key => $item)
                        <option value="{{$key}}">{{ucfirst($item)}} </option>
                        @empty
                        <option value="">{{__('No Content')}}</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="idnivel"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Chapter')}}: " required/>
                    <x-select class="flex-1 ml-4" wire:model="id_capitulo">
                        <option value="">{{__('Select')}}</option>
                        @forelse ($arr_capitulos as $item)
                        <option value="{{$item->capitulo_id}}">{{$item->capitulo_descripcion}} - {{$item->capitulo_codigo}}</option>
                        @empty
                        <option value="">{{__('No Content')}}</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="id_capitulo"/>
            </div>
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="font-sans font-extrabold text-sm text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3 rounded-s-lg w-1/2">
                                Estudiante
                            </th>
                            <th scope="col" class="px-4 py-3 text-center">
                                Asistió
                            </th>
                            <th scope="col" class="px-4 py-3 rounded-e-lg text-center">
                                Observación
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($estudiantes as $estudiante)
                            <tr class="bg-white dark:bg-gray-800">
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white w-1/2">
                                    {{ $estudiante->prospectos_nombres }} {{ $estudiante->prospectos_apellidos }}
                                </th>
                                <td class="px-4 py-4 text-center">
                                    <x-checkbox  wire:model="asistencias.{{ $estudiante->prospectos_id }}" />
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <x-input  wire:model="observaciones.{{ $estudiante->prospectos_id }}"
                                            class="h-10 w-25 text-sm text-center" maxlength="255" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </x-slot>
        <x-slot name="footer">
            <x-forms.red-button wire:click="$set('open_edit_diario',false)">
                {{__('Cancel')}}
            </x-forms.red-button>
            <x-forms.blue-button wire:click="saveDiario"  wire:loading.attr="disabled" wire:click="saveDiario" class="disabled:opacity-65">
                {{__('Update')}}
            </x-forms.blue-button>
            {{-- <span wire:loading wire:target="save">Cargando...</span> --}}
        </x-slot>
    </x-dialog-modal>

    <x-dialog-modal wire:model="open_edit_plan">
        <x-slot name="title">
            Actualizar plan
        </x-slot>

        <x-slot name="content">
            <div
                id="scroll-container"
                class="max-h-96 overflow-y-auto p-4 border rounded-lg shadow bg-white dark:bg-gray-800"
            >
            @foreach($evaluaciones as $horarioId => $items)
                    <div class="mb-6">
                        @php
                            $firstItem = $items[0] ?? null;
                            $diarios_hecho = $firstItem['horario']['diario']['diarios_hecho'] ?? 'Sin descripción';
                            $diarios_porhacer = $firstItem['horario']['diario']['diarios_porhacer'] ?? 'Sin descripción';
                            $fecha = date('d-m-Y', strtotime($firstItem['horario']['horarios_dia']));
                            $profesor = ($firstItem['horario']['profesor']['profesores_nombres'] ?? '') .' '.($firstItem['horario']['profesor']['profesores_apellidos'] ?? '');
                            $espacio = $firstItem['horario']['espacio']['espacios_nombre'];
                        @endphp

                        <h3 class="text-lg font-bold text-gray-700 dark:text-gray-100">Fecha: {{ $fecha }}</h3>
                        <p class="text-lg font-bold text-gray-700 dark:text-gray-100">Profesor: {{ $profesor }} - Salón: {{ $espacio }} </p>

                        <p class="text-sm text-gray-500 dark:text-gray-300 mb-2">
                            Hecho: {{ $diarios_hecho }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-300 mb-2">
                            Por hacer: {{ $diarios_porhacer }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-300 mb-2">
                            Nivel: {{ $arr_niveles[$firstItem['horario']['diario']['niveles_id']] ?? '' }} - Capitulo: {{ $arr_capitulos2[$firstItem['horario']['diario']['capitulos_id']] ?? '' }}
                        </p>

                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="font-sans font-extrabold text-sm text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-4 py-2">Estudiante</th>
                                    <th class="px-4 py-2">Asistencia</th>
                                    <th class="px-4 py-2">Observación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $eval)
                                    <tr class="bg-white dark:bg-gray-800 border-b">
                                        <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">
                                            {{ $eval['prospecto']['prospectos_nombres'] ?? '' }}
                                            {{ $eval['prospecto']['prospectos_apellidos'] ?? '' }}
                                        </td>
                                        <td class="px-4 py-2">{{ $eval['asistio'] ? 'Sí' : 'No' }}</td>
                                        <td class="px-4 py-2">{{ $eval['observacion'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-forms.red-button wire:click="$set('open_edit_plan', false)">
                {{ __('Close') }}
            </x-forms.red-button>
        </x-slot>
    </x-dialog-modal>


    @push('js');
    <script>
        livewire.on('deleteHorario',itemId=>{
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
                livewire.emitTo('show-horarios','delete',itemId);
            }
            });
        })
        // Este es el listener importante para el scroll
        document.addEventListener('livewire:load', function () {
            Livewire.on('scrollToBottom', () => {
                // Pequeña demora para asegurar que el DOM esté actualizado
                setTimeout(() => {
                    const container = document.getElementById('scroll-container');
                    if (container) { // Verifica si el contenedor existe
                       container.scrollTop = container.scrollHeight;
                    }
                }, 50); // 50ms de demora, puedes ajustarlo si es necesario
            });
        });

        // ... (resto de tu script de drag and drop) ...
    </script>
    {{-- <script src="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.umd.js"></script> --}}

    <script>
    // Escuchar el evento de Livewire para inicializar el arrastre y la caída

    document.addEventListener('DOMContentLoaded', function () {
        const initializeDragAndDrop = () => {
            let table = document.getElementById('horarios-table');
            let cells = table.querySelectorAll('.grupo-cell');

            cells.forEach(cell => {
                cell.setAttribute('draggable', true);

                cell.addEventListener('dragstart', function (e) {
                    e.dataTransfer.setData('text', JSON.stringify({
                        id: cell.dataset.id,
                        dia: cell.dataset.dia,
                        hora: cell.dataset.hora,
                        grupo: cell.dataset.grupo,
                        profesor: cell.dataset.profesor,
                        espacio: cell.dataset.espacio,
                    }));
                });
            });

            table.addEventListener('dragover', function (e) {
                e.preventDefault();
            });

            table.addEventListener('drop', function (e) {
                e.preventDefault();
                let data = JSON.parse(e.dataTransfer.getData('text'));
                let targetCell = e.target.closest('.grupo-cell');

                if (targetCell) {
                    let targetId = targetCell.dataset.id;
                    let targetDia = targetCell.dataset.dia;
                    let targetHora = targetCell.dataset.hora;
                    let targetProfesor = targetCell.dataset.profesor;
                    let targetEspacio = targetCell.dataset.espacio;
                    // Llama a Livewire directamente
                    @this.updateGrupoHorario(targetId, targetDia, targetHora, data.grupo, targetProfesor, data.espacio, data.id);

                }
            });
        };

        // Inicializar al cargar la página
        initializeDragAndDrop();

        // Volver a inicializar después de una actualización de Livewire
        document.addEventListener('livewire:update', () => {
            initializeDragAndDrop();
        });
    });

    </script>
    @endpush


</div>
