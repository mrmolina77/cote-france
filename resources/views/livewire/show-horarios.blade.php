<div>
    <style>
        .day-separator-left {
            border-left: 3px solid #9ca3af;
        }
    </style>
    @section('content')
    <p>{{ __('Timetable') }}</p>
    @endsection
    <div class="w-full px-0" wire:ignore.self>
        @if ($semanal)
            {{-- Cabecera Fija --}}
            <div class="border p-2 bg-gray-100">
                <div class="grid h-full max-w-4xl grid-cols-7 gap-2 mx-auto">
                    <div class="col-span-full text-center font-bold">
                        <div>Semana # {{$semana}}</div>
                    </div>
                    <div class="flex items-center justify-center">
                        <button class="w-full py-2 px-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700" wire:click="anterior">
                            Anterior
                        </button>
                    </div>
                    <div class="flex items-center">
                        <x-select id="porcentaje-select" class="w-full text-sm font-medium text-gray-900 py-2 px-3" wire:model="porcentaje">
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
                    <div class="flex items-center">
                        <button
                            class="w-full py-2 px-3 text-sm font-semibold rounded-lg border focus:outline-none focus:ring-4 transition-colors duration-150 {{ $semana_activa ? 'text-green-800 bg-green-100 border-green-200 focus:ring-green-200' : 'text-gray-900 bg-white border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:ring-gray-100' }}"
                            wire:click="toggleSemanaActiva"
                        >
                            <span class="flex items-center justify-center gap-2">
                                @if ($semana_activa)
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.854-9.646a.5.5 0 00-.708-.708L9 11.793 6.854 9.646a.5.5 0 10-.708.708l2.5 2.5a.5.5 0 00.708 0l4.5-4.5z" clip-rule="evenodd" />
                                    </svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                @endif
                                <span>Semana</span>
                            </span>
                        </button>
                    </div>
                    <div class="flex items-center justify-center">
                        <button
                            type="button"
                            class="w-full py-2 px-3 text-sm font-medium rounded-lg border focus:outline-none focus:ring-4 {{ empty($undoStack) ? 'text-gray-400 bg-gray-100 border-gray-200 cursor-not-allowed' : 'text-gray-900 bg-white border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:ring-gray-100' }}"
                            wire:click="undoHorario"
                            title="Deshacer último cambio (Ctrl+Z)"
                            @if(empty($undoStack)) disabled @endif
                        >
                            ↶ Deshacer
                        </button>
                    </div>
                    <div class="flex items-center justify-center">
                        <button
                            type="button"
                            class="w-full py-2 px-3 text-sm font-medium rounded-lg border focus:outline-none focus:ring-4 {{ empty($redoStack) ? 'text-gray-400 bg-gray-100 border-gray-200 cursor-not-allowed' : 'text-gray-900 bg-white border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:ring-gray-100' }}"
                            wire:click="redoHorario"
                            title="Rehacer cambio (Ctrl+Y)"
                            @if(empty($redoStack)) disabled @endif
                        >
                            ↷ Rehacer
                        </button>
                    </div>
                    <div class="flex items-center justify-center">
                        <button class="w-full py-2 px-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700" wire:click="siguiente">
                            Siguiente
                        </button>
                    </div>
                </div>
            </div>

            {{-- Contenedor del Grid con Scroll --}}
            @php
                $columnMapping = [];
                $columnIndex = 1; // Inicia después de la columna de horas inicial

                foreach ($dias as $dia) {
                    $currentDateString = \Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD');
                    foreach ($profesores as $profesor) {
                        $columnMapping[$currentDateString][$profesor->profesores_id] = $columnIndex;
                        $columnIndex++;
                    }
                }

                $columnIndex++; // Columna de horas para dias2

                foreach ($dias2 as $dia) {
                    $currentDateString = \Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD');
                    foreach ($profesores as $profesor) {
                        $columnMapping[$currentDateString][$profesor->profesores_id] = $columnIndex;
                        $columnIndex++;
                    }
                }
            @endphp
            <div @class([
                'overflow-x-auto origin-top-left',
                'scale-100 w-full' => $porcentaje == '0',
                'scale-95 w-[105.26%]' => $porcentaje == '1',
                'scale-90 w-[111.11%]' => $porcentaje == '2',
                'scale-75 w-[133.33%]' => $porcentaje == '3',
                'scale-50 w-[200%]' => $porcentaje == '4',
            ]) wire:updated="initializeDragAndDrop">
                <div class="grid min-w-max border border-gray-200 rounded-lg overflow-hidden" id="horarios-table" style="display: grid; grid-template-columns: minmax(2.56rem, 2.56rem) repeat({{ count($dias) * count($profesores) }}, minmax(2.56rem, 1fr)) minmax(2.56rem, 2.56rem) repeat({{ count($dias2) * count($profesores) }}, minmax(2.56rem, 1fr));">
                {{-- Day/Professor Headers --}}
                <div class="border-r border-gray-200 px-1 py-0.5 w-16 sticky top-0 bg-gray-50 z-10 flex items-center justify-center font-sans font-semibold text-sm">{{ __('Hours') }}</div>
                @foreach ( $dias as $dia )
                    @php
                        $firstProfessorId = $profesores->first()->profesores_id ?? null;
                        $startColumnIndex = $firstProfessorId ? ($columnMapping[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$firstProfessorId] ?? null) : null;
                    @endphp
                    <div class="border-r border-gray-200 p-[0.1rem] sticky top-0 bg-gray-50 z-10 day-header-group" style="grid-column: span {{ count($profesores) }};" data-start-index="{{ $startColumnIndex }}" data-span="{{ count($profesores) }}">
                        <div class="text-center font-sans font-semibold text-sm">{{$dia->dias_nombre}} {{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('DD')}}</div>
                        <div class="grid day-header-grid" style="grid-template-columns: repeat({{ count($profesores) }}, 1fr);">
                            @foreach ($profesores as $profesor)
                            @php
                                $dateString = \Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD');
                                $columnKey = $dateString . '-' . $profesor->profesores_id;
                            @endphp
                            @php $addSeparator = $loop->first; @endphp
                            <div class="w-full items-center justify-center p-[0.1rem] collapsible-header {{ $addSeparator ? 'day-separator-left' : '' }}" data-column-index="{{ $columnMapping[$dateString][$profesor->profesores_id] ?? '' }}" data-column-key="{{ $columnKey }}">
                                <div style="background-color:{{$profesor->profesores_color}}" class="overflow-hidden text-ellipsis whitespace-nowrap font-sans font-semibold text-xs text-center text-white rounded-md py-1 collapsible-label" data-initial="{{ strtoupper(mb_substr($profesor->profesores_nombres,0,1,'UTF-8')) }}" data-full-text="{{$profesor->profesores_nombres}}">{{$profesor->profesores_nombres}}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div class="border-r border-gray-200 px-1 py-0.5 w-16 sticky top-0 bg-gray-50 z-10 flex items-center justify-center font-sans font-semibold text-sm">{{ __('Hours') }}</div>
                @foreach ( $dias2 as $dia )
                    @php
                        $firstProfessorId = $profesores->first()->profesores_id ?? null;
                        $startColumnIndex = $firstProfessorId ? ($columnMapping[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$firstProfessorId] ?? null) : null;
                    @endphp
                    <div class="border-r border-gray-200 p-[0.1rem] sticky top-0 bg-gray-50 z-10 day-header-group" style="grid-column: span {{ count($profesores) }};" data-start-index="{{ $startColumnIndex }}" data-span="{{ count($profesores) }}">
                        <div class="text-center font-sans font-semibold text-sm">{{$dia->dias_nombre}} {{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('DD')}}</div>
                        <div class="grid day-header-grid" style="grid-template-columns: repeat({{ count($profesores) }}, 1fr);">
                            @foreach ($profesores as $profesor)
                            @php
                                $dateString = \Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD');
                                $columnKey = $dateString . '-' . $profesor->profesores_id;
                            @endphp
                            @php $addSeparator = $loop->first; @endphp
                            <div class="w-full items-center justify-center p-[0.1rem] collapsible-header {{ $addSeparator ? 'day-separator-left' : '' }}" data-column-index="{{ $columnMapping[$dateString][$profesor->profesores_id] ?? '' }}" data-column-key="{{ $columnKey }}">
                                <div style="background-color:{{$profesor->profesores_color}}" class="overflow-hidden text-ellipsis whitespace-nowrap font-sans font-semibold text-xs text-center text-white rounded-md py-1 collapsible-label" data-initial="{{ strtoupper(mb_substr($profesor->profesores_nombres,0,1,'UTF-8')) }}" data-full-text="{{$profesor->profesores_nombres}}">{{$profesor->profesores_nombres}}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                {{-- Schedule Body --}}
                @foreach ($horas as $pos1 => $hora)
                    {{-- Hour Cell --}}
                    <div class="border-r border-gray-200 text-center px-1 py-0.5 flex items-center justify-center"><samp class="font-sans font-semibold text-xs leading-tight">{{\Carbon\Carbon::parse($hora->horas_desde)->format('H:i')}}<br>{{\Carbon\Carbon::parse($hora->horas_hasta)->format('H:i')}}</samp></div>

                    {{-- Dias (Mon-Fri) --}}
                    @foreach ($dias as $dia)
                        @foreach ($profesores as $profesor)
                            @php
                                $currentDateString = \Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD');
                                $isBlocked = isset($bloqueosProfesores[$profesor->profesores_id]['full_days'][$currentDateString]) || isset($bloqueosProfesores[$profesor->profesores_id]['recurring'][$dia->dias_id][$hora->horas_id]);
                                $horarioItem = $horarios[$currentDateString][$hora->horas_id][$profesor->profesores_id] ?? null;
                                $isDayBoundary = $loop->first;
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
                                @php
                                    $columnKey = $currentDateString . '-' . $profesor->profesores_id;
                                    $columnIndex = $columnMapping[$currentDateString][$profesor->profesores_id] ?? null;
                                @endphp
                                <div class="h-full p-[0.1rem] text-center {{$cellgrupo}} {{ $isDayBoundary ? 'day-separator-left' : '' }}"
                                    data-id="{{ $horarioItem['id'] }}"
                                    data-dia="{{ $currentDateString }}"
                                    data-espacio="{{ $horarioItem['espacios_id'] }}"
                                    data-hora="{{ $hora->horas_id }}"
                                    data-grupo="{{ $horarioItem['grupo_id'] }}"
                                    data-profesor="{{ $profesor->profesores_id }}"
                                    data-column-key="{{ $columnKey }}"
                                    data-column-index="{{ $columnIndex }}">
                                    <div style="{{$estilosDisplay}}" class="relative w-full min-h-14 grid grid-cols-1 pb-4 {{$horarioItem['bgcolor']}} rounded-md">
                                        <div style="{{ $estilosParaDiv }}" class="font-sans text-xs font-extrabold overflow-hidden text-ellipsis whitespace-nowrap w-full text-center uppercase">
                                            @if ($horarioItem['modalidad'] == '2')
                                                <a href="{{$horarioItem['enlace']}}" target="_blank" rel="noopener noreferrer">
                                                    <x-group-name :name="$nombreDelHorario" />
                                                </a>
                                            @elseif ($nombreDelHorario === "BLOQUEADO")
                                                <span class="text-red-500 font-bold">&nbsp;</span>
                                            @else
                                                <x-group-name :name="$nombreDelHorario" />
                                            @endif
                                        </div>
                                        {{-- DEBUG: Esta condición muestra el ícono de clase manual únicamente por origen=manual. Revisar logs render:manual_visual_diagnostico si protegido causa falsos positivos. --}}
                                        @if(($horarioItem['origen'] ?? null) === 'manual')
                                            <span class="absolute top-1 right-1 inline-flex items-center justify-center rounded bg-amber-400 px-1.5 py-0.5 text-[0.6rem] leading-none text-amber-950 shadow"
                                                  title="Clase creada manualmente"
                                                  aria-label="Clase creada manualmente">
                                                <i class="fas fa-lock" aria-hidden="true"></i>
                                            </span>
                                        @endif
                                        @php
                                            $diarioActualizado = $horarioItem['diario_actualizado'] ?? null;
                                            $limiteActualizacion = \Carbon\Carbon::parse($currentDateString . ' ' . $hora->horas_desde)->addHour();
                                            $mostrarEstado = $diarioActualizado || \Carbon\Carbon::now()->greaterThanOrEqualTo($limiteActualizacion);
                                        @endphp
                                        @if(strtoupper(trim($nombreDelHorario)) !== "BLOQUEADO" && $mostrarEstado)
                                            @if($diarioActualizado)
                                                <span class="absolute bottom-1 right-1 h-2.5 w-2.5 rounded-full bg-emerald-500" aria-label="Actualizado"></span>
                                            @else
                                                <span class="absolute bottom-1 right-1 h-2.5 w-2.5 rounded-full bg-red-500" aria-label="Pendiente"></span>
                                            @endif
                                        @endif
                                        @php
                                            $horaInicio = \Carbon\Carbon::parse($currentDateString . ' ' . $hora->horas_desde);
                                            $mostrarPendienteAnterior = ($horarioItem['diario_anterior_pendiente'] ?? false)
                                                && \Carbon\Carbon::now()->lessThan($horaInicio);
                                        @endphp
                                        @if(strtoupper(trim($nombreDelHorario)) !== "BLOQUEADO" && $mostrarPendienteAnterior)
                                            <span class="absolute bottom-1 left-1 text-xs font-extrabold text-red-600" aria-label="Clase anterior pendiente">+</span>
                                        @endif
                                        @if(strtoupper(trim($nombreDelHorario)) !== "BLOQUEADO")
                                            <div class="flex items-center justify-center">
                                                <div><i class="fas fa-trash text-red-500 m-1 cursor-pointer" wire:click="$emit('deleteHorario',{{ $horarioItem['id'] }})"></i></div>
                                                <div><i class="fas fa-user-plus text-blue-500 m-1 cursor-pointer" wire:click="openCreateClasePrueba('{{$currentDateString}}',{{$hora->horas_id}},{{ $profesor->profesores_id }},{{ $horarioItem['grupo_id'] }})"></i></div>
                                                <div><i class="fas fa-calendar-check text-green-500 m-1 cursor-pointer" wire:click="editPlan({{ $horarioItem['id'] }})"></i></div>
                                                <div><i class="fas fa-book text-blue-500 m-1 cursor-pointer" wire:click="editDiario({{ $horarioItem['id'] }})"></i></div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @elseif ($isBlocked)
                                <div class="h-full p-[0.1rem] text-center {{ $isDayBoundary ? 'day-separator-left' : '' }}">
                                    <div class="w-full min-h-14 grid grid-cols-1 justify-center items-center bg-gray-300 text-gray-600 rounded-md" wire:key="blocked-{{ $dia->dias_id }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                        <span class="text-xs font-semibold">{{ __('Blocked') }}</span>
                                    </div>
                                </div>
                            @else
                                @php $grupoDetalle = $grupo_deta[$dia->dias_id][$hora->horas_id][$profesor->profesores_id] ?? null; @endphp
                                @if($grupoDetalle)
                                    @php
                                        $columnKey = $currentDateString . '-' . $profesor->profesores_id;
                                        $columnIndex = $columnMapping[$currentDateString][$profesor->profesores_id] ?? null;
                                    @endphp
                                    <div class="h-full p-[0.1rem] text-center grupo-cell {{ $isDayBoundary ? 'day-separator-left' : '' }}"
                                        data-id="0"
                                        data-dia="{{$currentDateString}}"
                                        data-espacio="{{$grupoDetalle['espacios_id']}}"
                                        data-hora="{{$hora->horas_id}}"
                                        data-grupo="{{$grupoDetalle['grupo_id']}}"
                                        data-profesor="{{ $profesor->profesores_id }}"
                                        data-column-key="{{ $columnKey }}"
                                        data-column-index="{{ $columnIndex }}">
                                        <div class="w-full min-h-14 grid grid-cols-1 justify-center items-center {{$grupoDetalle['color']}} uppercase rounded-md" wire:key="task-{{ $dia->dias_id }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                            <x-group-name :name="$grupoDetalle['grupo_nombre']" class="text-center font-sans font-extrabold text-xs uppercase" />
                                            <div class="flex items-center justify-center gap-2">
                                                <button type="button" class="text-red-500" wire:click="$emit('confirmDeactivateGrupo', {{ $grupoDetalle['grupo_id'] }}, '{{ $currentDateString }}', {{ $hora->horas_id }})">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                                <i class="fas fa-user-plus text-blue-500 cursor-pointer" wire:click="openCreateClasePrueba('{{$currentDateString}}',{{$hora->horas_id}},{{ $profesor->profesores_id }},{{ $grupoDetalle['grupo_id'] }})"></i>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    @php
                                        $columnKey = $currentDateString . '-' . $profesor->profesores_id;
                                        $columnIndex = $columnMapping[$currentDateString][$profesor->profesores_id] ?? null;
                                    @endphp
                                    <div class="h-full p-[0.1rem] text-center grupo-cell {{ $isDayBoundary ? 'day-separator-left' : '' }}"
                                        data-id="0"
                                        data-dia="{{$currentDateString}}"
                                        data-espacio="0"
                                        data-hora="{{$hora->horas_id}}"
                                        data-grupo="0"
                                        data-profesor="{{ $profesor->profesores_id }}"
                                        data-column-key="{{ $columnKey }}"
                                        data-column-index="{{ $columnIndex }}">
                                        <div class="w-full min-h-14 grid grid-cols-1 justify-center items-center bg-amber-50 rounded-md" wire:key="task-{{ $dia->dias_id }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                            <div class="flex items-center justify-center gap-2">
                                                <i class="fas fa-plus text-emerald-500 cursor-pointer" wire:click="edit('{{$currentDateString}}',{{ $profesor->profesores_id }},{{$hora->horas_id}},{{$profesor->profesores_id}})"></i>
                                                <i class="fas fa-user-plus text-blue-500 cursor-pointer" wire:click="openCreateClasePrueba('{{$currentDateString}}',{{$hora->horas_id}},{{ $profesor->profesores_id }},0)"></i>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        @endforeach
                    @endforeach

                    {{-- Hour Cell (Weekend) --}}
                    <div class="border-r border-gray-200 text-center px-1 py-0.5 flex items-center justify-center">
                        @if (isset($horas2[$pos1]) && $horas2[$pos1]->horas_id < 14)
                            <samp class="font-sans font-semibold text-xs leading-tight">{{\Carbon\Carbon::parse($horas2[$pos1]->horas_desde)->format('H:i')}}<br>{{\Carbon\Carbon::parse($horas2[$pos1]->horas_hasta)->format('H:i')}}</samp>
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
                                $isDayBoundary = $loop->first;
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
                                @php
                                    $columnKey = $currentDateString . '-' . $profesor->profesores_id;
                                    $columnIndex = $columnMapping[$currentDateString][$profesor->profesores_id] ?? null;
                                @endphp
                                <div class="h-full p-[0.1rem] text-center {{$cellgrupo}} {{ $isDayBoundary ? 'day-separator-left' : '' }}"
                                    data-id="{{ $horarioItem['id'] }}"
                                    data-dia="{{ $currentDateString }}"
                                    data-espacio="{{ $horarioItem['espacios_id'] }}"
                                    data-hora="{{ $currentHourId }}"
                                    data-grupo="{{ $horarioItem['grupo_id'] }}"
                                    data-profesor="{{ $profesor->profesores_id }}"
                                    data-column-key="{{ $columnKey }}"
                                    data-column-index="{{ $columnIndex }}">
                                    <div style="{{$estilosDisplay}}" class="relative w-full min-h-14 grid grid-cols-1 pb-4 {{$horarioItem['bgcolor']}} rounded-md">
                                        <div style="{{ $estilosParaDiv }}" class="font-sans text-xs font-extrabold overflow-hidden text-ellipsis whitespace-nowrap w-full text-center uppercase">
                                            @if ($nombreDelHorario === "BLOQUEADO")
                                                <span class="text-red-500 font-bold">&nbsp;</span>
                                            @else
                                                <x-group-name :name="$nombreDelHorario" />
                                            @endif
                                        </div>
                                        {{-- DEBUG: Esta condición muestra el ícono de clase manual únicamente por origen=manual. Revisar logs render:manual_visual_diagnostico si protegido causa falsos positivos. --}}
                                        @if(($horarioItem['origen'] ?? null) === 'manual')
                                            <span class="absolute top-1 right-1 inline-flex items-center justify-center rounded bg-amber-400 px-1.5 py-0.5 text-[0.6rem] leading-none text-amber-950 shadow"
                                                  title="Clase creada manualmente"
                                                  aria-label="Clase creada manualmente">
                                                <i class="fas fa-lock" aria-hidden="true"></i>
                                            </span>
                                        @endif
                                        @php
                                            $diarioActualizado = $horarioItem['diario_actualizado'] ?? null;
                                            $horaInicio = $horas2[$pos1]->horas_desde ?? null;
                                            $limiteActualizacion = $horaInicio
                                                ? \Carbon\Carbon::parse($currentDateString . ' ' . $horaInicio)->addHour()
                                                : null;
                                            $mostrarEstado = $diarioActualizado || ($limiteActualizacion && \Carbon\Carbon::now()->greaterThanOrEqualTo($limiteActualizacion));
                                        @endphp
                                        @if(strtoupper(trim($nombreDelHorario)) !== "BLOQUEADO" && $mostrarEstado)
                                            @if($diarioActualizado)
                                                <span class="absolute bottom-1 right-1 h-2.5 w-2.5 rounded-full bg-emerald-500" aria-label="Actualizado"></span>
                                            @else
                                                <span class="absolute bottom-1 right-1 h-2.5 w-2.5 rounded-full bg-red-500" aria-label="Pendiente"></span>
                                            @endif
                                        @endif
                                        @php
                                            $mostrarPendienteAnterior = ($horarioItem['diario_anterior_pendiente'] ?? false)
                                                && $horaInicio
                                                && \Carbon\Carbon::now()->lessThan(\Carbon\Carbon::parse($currentDateString . ' ' . $horaInicio));
                                        @endphp
                                        @if(strtoupper(trim($nombreDelHorario)) !== "BLOQUEADO" && $mostrarPendienteAnterior)
                                            <span class="absolute bottom-1 left-1 text-xs font-extrabold text-red-600" aria-label="Clase anterior pendiente">+</span>
                                        @endif
                                        @if(strtoupper(trim($nombreDelHorario)) !== "BLOQUEADO")
                                            <div class="flex items-center justify-center">
                                                <div><i class="fas fa-trash text-red-500 m-1 cursor-pointer" wire:click="$emit('deleteHorario',{{ $horarioItem['id'] }})"></i></div>
                                                <div><i class="fas fa-user-plus text-blue-500 m-1 cursor-pointer" wire:click="openCreateClasePrueba('{{$currentDateString}}',{{$currentHourId}},{{ $profesor->profesores_id }},{{ $horarioItem['grupo_id'] }})"></i></div>
                                                <div><i class="fas fa-calendar-check text-green-500 m-1 cursor-pointer" wire:click="editPlan({{ $horarioItem['id'] }})"></i></div>
                                                <div><i class="fas fa-book text-blue-500 m-1 cursor-pointer" wire:click="editDiario({{ $horarioItem['id'] }})"></i></div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @elseif ($isBlocked)
                                <div class="h-full p-[0.1rem] text-center {{ $isDayBoundary ? 'day-separator-left' : '' }}">
                                    <div class="w-full min-h-14 grid grid-cols-1 justify-center items-center bg-gray-300 text-gray-600 rounded-md" wire:key="blocked-{{ $dia->dias_id }}-{{ $currentHourId }}-{{ $profesor->profesores_id }}">
                                        <span class="text-xs font-semibold">{{ __('Blocked') }}</span>
                                    </div>
                                </div>
                            @else
                                @php $grupoDetalle = ($currentHourId && isset($grupo_deta[$dia->dias_id][$currentHourId][$profesor->profesores_id])) ? $grupo_deta[$dia->dias_id][$currentHourId][$profesor->profesores_id] : null; @endphp
                                @if($grupoDetalle)
                                    @php
                                        $columnKey = $currentDateString . '-' . $profesor->profesores_id;
                                        $columnIndex = $columnMapping[$currentDateString][$profesor->profesores_id] ?? null;
                                    @endphp
                                    <div class="h-full p-[0.1rem] text-center grupo-cell {{ $isDayBoundary ? 'day-separator-left' : '' }}"
                                        data-id="0"
                                        data-dia="{{$currentDateString}}"
                                        data-espacio="{{$grupoDetalle['espacios_id']}}"
                                        data-hora="{{$currentHourId}}"
                                        data-grupo="{{$grupoDetalle['grupo_id']}}"
                                        data-profesor="{{ $profesor->profesores_id }}"
                                        data-column-key="{{ $columnKey }}"
                                        data-column-index="{{ $columnIndex }}">
                                        <div class="w-full min-h-14 grid grid-cols-1 justify-center items-center {{$grupoDetalle['color']}} uppercase rounded-md" wire:key="task-{{ $dia->dias_id }}-{{ $currentHourId }}-{{ $profesor->profesores_id }}">
                                            <x-group-name :name="$grupoDetalle['grupo_nombre']" class="text-center font-sans font-extrabold text-xs uppercase" />
                                            <div class="flex items-center justify-center gap-2">
                                                <button type="button" class="text-red-500" wire:click="$emit('confirmDeactivateGrupo', {{ $grupoDetalle['grupo_id'] }}, '{{ $currentDateString }}', {{ $currentHourId }})">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                                <i class="fas fa-user-plus text-blue-500 cursor-pointer" wire:click="openCreateClasePrueba('{{$currentDateString}}',{{$currentHourId}},{{ $profesor->profesores_id }},{{ $grupoDetalle['grupo_id'] }})"></i>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    @php
                                        $columnKey = $currentDateString . '-' . $profesor->profesores_id;
                                        $columnIndex = $columnMapping[$currentDateString][$profesor->profesores_id] ?? null;
                                    @endphp
                                    <div class="h-full p-[0.1rem] text-center grupo-cell {{ $isDayBoundary ? 'day-separator-left' : '' }}"
                                        data-id="0"
                                        data-dia="{{$currentDateString}}"
                                        data-espacio="0"
                                        data-hora="{{$currentHourId}}"
                                        data-grupo="0"
                                        data-profesor="{{ $profesor->profesores_id }}"
                                        data-column-key="{{ $columnKey }}"
                                        data-column-index="{{ $columnIndex }}">
                                        @if ($currentHourId && $currentHourId < 14)
                                            <div class="w-full min-h-14 grid grid-cols-1 justify-center items-center bg-amber-50 rounded-md" wire:key="task-{{ $dia->dias_id }}-{{ $currentHourId }}-{{ $profesor->profesores_id }}">
                                                <div class="flex items-center justify-center gap-2">
                                                    <i class="fas fa-plus text-emerald-500 cursor-pointer" wire:click="edit('{{$currentDateString}}',{{ $profesor->profesores_id }},{{$currentHourId}},{{$profesor->profesores_id}})"></i>
                                                    <i class="fas fa-user-plus text-blue-500 cursor-pointer" wire:click="openCreateClasePrueba('{{$currentDateString}}',{{$currentHourId}},{{ $profesor->profesores_id }},0)"></i>
                                                </div>
                                            </div>
                                        @else
                                            <div class="w-full min-h-14 grid grid-cols-1 justify-center items-center bg-amber-50 rounded-md"></div>
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
                <div class="grid min-w-max border border-gray-200 rounded-lg overflow-hidden" id="horarios-table" style="display: grid; grid-template-columns: auto repeat({{ count($profesores) }}, minmax(3rem, 1fr));">
                {{-- Professor Headers --}}
                  <div class="border-r border-gray-200 px-1 py-0.5 w-16 sticky top-0 bg-gray-50 z-10 flex items-center justify-center font-sans font-semibold text-sm">{{ __('Hours') }}</div>
                @foreach ( $profesores as $profesor )
                    <div class="border p-2 sticky top-0 bg-gray-50 z-10 text-center">
                        <div style="background-color:{{$profesor->profesores_color}}" class="overflow-hidden text-ellipsis whitespace-nowrap font-sans font-semibold text-xs text-white rounded-md py-1">{{$profesor->profesores_nombres}}</div>
                    </div>
                @endforeach

                {{-- Schedule Body --}}
                @foreach ( $horas as $hora )
                    {{-- Hour Cell --}}
                    <div class="border-r border-gray-200 text-center px-1 py-0.5 flex items-center justify-center"><samp class="font-sans font-semibold text-xs leading-tight">{{\Carbon\Carbon::parse($hora->horas_desde)->format('H:i')}}<br>{{\Carbon\Carbon::parse($hora->horas_hasta)->format('H:i')}}</samp></div>

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
                                <div style="{{$estilosDisplay}}" class="relative w-full min-h-14 grid grid-cols-1 pb-4 {{$horarioItem['bgcolor']}}">
                                    <div style="{{ $estilosParaDiv }}" class="font-sans text-xs font-extrabold overflow-hidden text-ellipsis whitespace-nowrap w-full text-center uppercase">
                                        @if ($horarioItem['modalidad'] == '2')
                                            <a href="{{$horarioItem['enlace']}}" target="_blank" rel="noopener noreferrer">
                                                <x-group-name :name="$nombreDelHorario" />
                                            </a>
                                        @elseif ($nombreDelHorario === "BLOQUEADO")
                                            <span class="text-red-500 font-bold">&nbsp;</span>
                                        @else
                                            <x-group-name :name="$nombreDelHorario" />
                                        @endif
                                    </div>
                                    {{-- DEBUG: Esta condición muestra el ícono de clase manual únicamente por origen=manual. Revisar logs render:manual_visual_diagnostico si protegido causa falsos positivos. --}}
                                    @if(($horarioItem['origen'] ?? null) === 'manual')
                                        <span class="absolute top-1 right-1 inline-flex items-center justify-center rounded bg-amber-400 px-1.5 py-0.5 text-[0.6rem] leading-none text-amber-950 shadow"
                                              title="Clase creada manualmente"
                                              aria-label="Clase creada manualmente">
                                            <i class="fas fa-lock" aria-hidden="true"></i>
                                        </span>
                                    @endif
                                    @php
                                        $diarioActualizado = $horarioItem['diario_actualizado'] ?? null;
                                        $limiteActualizacion = \Carbon\Carbon::parse($currentDailyDateString . ' ' . $hora->horas_desde)->addHour();
                                        $mostrarEstado = $diarioActualizado || \Carbon\Carbon::now()->greaterThanOrEqualTo($limiteActualizacion);
                                    @endphp
                                    @if(strtoupper(trim($nombreDelHorario)) !== "BLOQUEADO" && $mostrarEstado)
                                        @if($diarioActualizado)
                                            <span class="absolute bottom-1 right-1 h-2.5 w-2.5 rounded-full bg-emerald-500" aria-label="Actualizado"></span>
                                        @else
                                            <span class="absolute bottom-1 right-1 h-2.5 w-2.5 rounded-full bg-red-500" aria-label="Pendiente"></span>
                                        @endif
                                    @endif
                                    @php
                                        $horaInicio = \Carbon\Carbon::parse($currentDailyDateString . ' ' . $hora->horas_desde);
                                        $mostrarPendienteAnterior = ($horarioItem['diario_anterior_pendiente'] ?? false)
                                            && \Carbon\Carbon::now()->lessThan($horaInicio);
                                    @endphp
                                    @if(strtoupper(trim($nombreDelHorario)) !== "BLOQUEADO" && $mostrarPendienteAnterior)
                                        <span class="absolute bottom-1 left-1 text-xs font-extrabold text-red-600" aria-label="Clase anterior pendiente">+</span>
                                    @endif
                                    @if(strtoupper(trim($nombreDelHorario)) !== "BLOQUEADO")
                                        <div class="flex items-center justify-center">
                                            <div><i class="fas fa-trash text-red-500 m-1 cursor-pointer" wire:click="$emit('deleteHorario',{{ $horarioItem['id'] }})"></i></div>
                                            <div><i class="fas fa-user-plus text-blue-500 m-1 cursor-pointer" wire:click="openCreateClasePrueba('{{$currentDailyDateString}}',{{$hora->horas_id}},{{ $profesor->profesores_id }},{{ $horarioItem['grupo_id'] }})"></i></div>
                                            <div><i class="fas fa-calendar-check text-green-500 m-1 cursor-pointer" wire:click="editPlan({{ $horarioItem['id'] }})"></i></div>
                                            <div><i class="fas fa-book text-blue-500 m-1 cursor-pointer" wire:click="editDiario({{ $horarioItem['id'] }})"></i></div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @elseif ($isBlockedDaily)
                            <div class="h-full border p-0 text-center">
                                <div class="w-full min-h-14 grid grid-cols-1 justify-center items-center bg-gray-300 text-gray-600" wire:key="blocked-daily-{{ $currentDailyDateString }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
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
                                    <div class="w-full min-h-14 grid grid-cols-1 justify-center items-center {{$grupoDetalle['color']}} uppercase" wire:key="task-daily-{{ $currentDayOfWeek }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                        <x-group-name :name="$grupoDetalle['grupo_nombre']" class="text-center font-sans font-extrabold text-xs uppercase" />
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button" class="text-red-500" wire:click="$emit('confirmDeactivateGrupo', {{ $grupoDetalle['grupo_id'] }}, '{{ $currentDailyDateString }}', {{ $hora->horas_id }})">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            <i class="fas fa-user-plus text-blue-500 cursor-pointer" wire:click="openCreateClasePrueba('{{$currentDailyDateString}}',{{$hora->horas_id}},{{ $profesor->profesores_id }},{{ $grupoDetalle['grupo_id'] }})"></i>
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
                                    <div class="w-full min-h-14 grid grid-cols-1 justify-center items-center bg-amber-50 rounded-md" wire:key="task-daily-{{ $currentDayOfWeek }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                        <div class="flex items-center justify-center gap-2">
                                            <i class="fas fa-plus text-emerald-500 cursor-pointer" wire:click="edit('{{$currentDailyDateString}}',{{ $profesor->profesores_id }},{{$hora->horas_id}},{{$profesor->profesores_id}})"></i>
                                            <i class="fas fa-user-plus text-blue-500 cursor-pointer" wire:click="openCreateClasePrueba('{{$currentDailyDateString}}',{{$hora->horas_id}},{{ $profesor->profesores_id }},0)"></i>
                                        </div>
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
                @php
                    $fechaSeleccionada = $horarios_dia ? \Carbon\Carbon::parse($horarios_dia)->toDateString() : null;
                    $gruposDiario = $grupos->map(function ($item) use ($fechaSeleccionada) {
                        $inicioGrupo = $item->fecha_inicio ? \Carbon\Carbon::parse($item->fecha_inicio)->toDateString() : null;
                        $esAnteriorInicio = $fechaSeleccionada && $inicioGrupo && \Carbon\Carbon::parse($fechaSeleccionada)->lt(\Carbon\Carbon::parse($inicioGrupo));
                        $prefijoGrupo = $item->es_evento ? '[E] ' : ($item->modalidad_id == 1 ? '[P] ' : '[L] ');

                        return [
                            'id' => (string) $item->grupo_id,
                            'nombre' => $prefijoGrupo . $item->grupo_nombre,
                            'disabled' => ! $item->es_evento && $esAnteriorInicio,
                        ];
                    })->values();
                    $espaciosDiario = $espacios->map(fn ($item) => [
                        'id' => (string) $item->espacios_id,
                        'nombre' => $item->espacios_nombre,
                        'disabled' => false,
                    ])->values();
                @endphp
                <div class="mb-4 flex items-start">
                    <x-forms.label for="grupo_id" value="{{__('Group')}}: "/>
                    <div class="relative flex-1 ml-4" x-data="searchableDiarySelect({
                            selected: @entangle('grupo_id').defer,
                            options: @js($gruposDiario),
                            placeholder: '{{ __('Select') }}',
                            searchPlaceholder: 'Buscar grupo...'
                        })" @click.away="close()">
                        <input type="hidden" id="grupo_id" :value="selected">
                        <button type="button" @click="toggle()" class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 text-left bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 flex items-center justify-between gap-3">
                            <span class="truncate" :class="selectedLabel() ? 'text-gray-800' : 'text-gray-500'" x-text="selectedLabel() || placeholder"></span>
                            <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                        </button>
                        <div x-show="open" x-transition class="mt-2 w-full bg-white border border-gray-200 rounded-2xl shadow-lg p-2" style="display: none;">
                            <div class="relative mb-2">
                                <i class="fas fa-search pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-sm text-slate-400"></i>
                                <input type="text" x-model="query" x-ref="search"  :placeholder="searchPlaceholder" class="w-full border border-blue-300 rounded-xl !pl-10 pr-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 focus:border-blue-400" style="padding-left: 2.5rem;" />
                            </div>
                            <div class="max-h-64 overflow-y-auto pr-1">
                                <template x-if="filteredOptions().length === 0">
                                    <div class="px-3 py-2 text-sm text-gray-500">{{ __('No Content') }}</div>
                                </template>
                                <template x-for="option in filteredOptions()" :key="option.id">
                                    <button type="button" @click="selectOption(option)" :disabled="option.disabled" class="w-full text-left px-3 py-2 text-sm rounded-lg transition" :class="option.disabled ? 'text-gray-400 cursor-not-allowed' : (String(selected) === String(option.id) ? 'bg-blue-600 text-white' : 'text-slate-700 hover:bg-blue-50')" x-text="option.nombre"></button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                <x-forms.input-error for="grupo_id"/>
                <div>
                    <div class="mb-4 flex items-start">
                        <x-forms.label for="id_espacios" value="{{__('Salons')}}: "/>
                        <div class="relative flex-1 ml-4" x-data="searchableDiarySelect({
                                selected: @entangle('id_espacios'),
                                options: @js($espaciosDiario),
                                placeholder: '{{ __('Select') }}',
                                searchPlaceholder: 'Buscar salón...'
                            })" @click.away="close()">
                            <input type="hidden" id="id_espacios" :value="selected">
                            <button type="button" @click="toggle()" class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 text-left bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 flex items-center justify-between gap-3">
                                <span class="truncate" :class="selectedLabel() ? 'text-gray-800' : 'text-gray-500'" x-text="selectedLabel() || placeholder"></span>
                                <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                            </button>
                            <div x-show="open" x-transition class="mt-2 w-full bg-white border border-gray-200 rounded-2xl shadow-lg p-2" style="display: none;">
                                <div class="relative mb-2">
                                    <i class="fas fa-search pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-sm text-slate-400"></i>
                                    <input type="text" x-model="query" x-ref="search"  :placeholder="searchPlaceholder" class="w-full border border-blue-300 rounded-xl !pl-10 pr-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 focus:border-blue-400" style="padding-left: 2.5rem;" />
                                </div>
                                <div class="max-h-64 overflow-y-auto pr-1">
                                    <template x-if="filteredOptions().length === 0">
                                        <div class="px-3 py-2 text-sm text-gray-500">{{ __('No Content') }}</div>
                                    </template>
                                    <template x-for="option in filteredOptions()" :key="option.id">
                                        <button type="button" @click="selectOption(option)" :disabled="option.disabled" class="w-full text-left px-3 py-2 text-sm rounded-lg transition" :class="option.disabled ? 'text-gray-400 cursor-not-allowed' : (String(selected) === String(option.id) ? 'bg-blue-600 text-white' : 'text-slate-700 hover:bg-blue-50')" x-text="option.nombre"></button>
                                    </template>
                                </div>
                            </div>
                        </div>
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

    <x-dialog-modal wire:model="open_create_clase_prueba">
        <x-slot name="title">Programar clase de prueba</x-slot>
        <x-slot name="content">
            <div class="grid grid-cols-2 gap-4">
                <div x-data="{ open: false, query: '', selected: @entangle('clase_prueba_prospectos_ids').defer,
                                init() {
                                    this.selected = Array.isArray(this.selected)
                                        ? this.selected.map(item => String(item))
                                        : [];
                                },
                                toggle(id) {
                                    id = String(id);
                                    const selected = Array.isArray(this.selected) ? this.selected.map(item => String(item)) : [];
                                    if (selected.includes(id)) {
                                        this.selected = selected.filter(item => item !== id);
                                    } else {
                                        this.selected = [...selected, id];
                                    }
                                    $wire.set('clase_prueba_prospectos_ids', this.selected);
                                },
                                isSelected(id) {
                                    const selected = Array.isArray(this.selected) ? this.selected.map(item => String(item)) : [];
                                    return selected.includes(String(id));
                                } }" class="relative" @click.away="open = false">
                    <button type="button" @click="open = !open" class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 text-left bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <span class="text-gray-500" x-show="selected.length === 0">Seleccionar...</span>
                        <span x-show="selected.length > 0" class="text-gray-800" x-text="selected.length + ' prospecto(s) seleccionado(s)'"></span>
                    </button>
                    <div x-show="open" x-transition class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg p-2 max-h-64 overflow-y-auto">
                        <input type="text" x-model="query" placeholder="Buscar prospecto..." class="w-full border border-gray-300 rounded-md mb-2 px-2 py-1 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                        <template x-for="prospecto in @js($prospectosClasePrueba->map(fn($p) => ['id' => (string) $p->prospectos_id, 'nombre' => $p->prospectos_nombres.' '.$p->prospectos_apellidos]))
                            .filter(item => item.nombre.toLowerCase().includes(query.toLowerCase()))" :key="prospecto.id">
                            <label class="flex items-center gap-2 py-1 cursor-pointer hover:bg-gray-50 rounded px-1">
                                <input type="checkbox" :checked="isSelected(prospecto.id)" @change="toggle(prospecto.id)">
                                <span x-text="prospecto.nombre"></span>
                            </label>
                        </template>
                    </div>
                    <x-forms.input-error for="clase_prueba_prospectos_ids" />
                </div>
                <div x-data="{ 
                        open: false, 
                        query: '', 
                        selectedId: @entangle('clase_prueba_grupo_id').defer,
                        grupos: @js(\App\Models\Grupo::orderBy('grupo_nombre')->get()->map(fn($g) => ['id' => (string) $g->grupo_id, 'nombre' => $g->grupo_nombre])),
                        selectedName() {
                            const item = this.grupos.find(g => g.id === String(this.selectedId));
                            return item ? item.nombre : '';
                        },
                        selectGrupo(grupo) {
                            this.selectedId = grupo.id;
                            $wire.set('clase_prueba_grupo_id', grupo.id);
                            this.query = '';
                            this.open = false;
                        }
                    }" class="relative" @click.away="open = false">
                    <button type="button" @click="open = !open" class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 text-left bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <span class="text-gray-500" x-show="!selectedName()">Grupo</span>
                        <span class="text-gray-800" x-show="selectedName()" x-text="selectedName()"></span>
                    </button>
                    <div x-show="open" x-transition class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg p-2 max-h-64 overflow-y-auto">
                        <input type="text" x-model="query" placeholder="Buscar grupo..." class="w-full border border-gray-300 rounded-md mb-2 px-2 py-1 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                        <template x-for="grupo in grupos.filter(item => item.nombre.toLowerCase().includes(query.toLowerCase()))" :key="grupo.id">
                            <button type="button" @click="selectGrupo(grupo)" class="w-full text-left py-1 px-1 rounded hover:bg-gray-50" x-text="grupo.nombre"></button>
                        </template>
                    </div>
                    <x-forms.input-error for="clase_prueba_grupo_id" />
                </div>
                <div>
                    <x-input type="date" wire:model="clase_prueba_horarios_dia" />
                    <x-forms.input-error for="clase_prueba_horarios_dia" />
                </div>
                <div>
                    <x-select wire:model="clase_prueba_horas_id"><option value="">Hora</option>@foreach(\App\Models\Hora::orderBy('horas_id')->get() as $h)<option value="{{$h->horas_id}}">{{\Carbon\Carbon::parse($h->horas_desde)->format('H:i')}} - {{\Carbon\Carbon::parse($h->horas_hasta)->format('H:i')}}</option>@endforeach</x-select>
                    <x-forms.input-error for="clase_prueba_horas_id" />
                </div>
                <x-select wire:model="clase_prueba_profesores_id"><option value="">Profesor (opcional)</option>@foreach(\App\Models\Profesor::orderBy('profesores_nombres')->get() as $pr)<option value="{{$pr->profesores_id}}">{{$pr->profesores_nombres}} {{$pr->profesores_apellidos}}</option>@endforeach</x-select>
                <x-select wire:model="clase_prueba_espacios_id"><option value="">Espacio (opcional)</option>@foreach(\App\Models\Espacio::orderBy('espacios_nombre')->get() as $e)<option value="{{$e->espacios_id}}">{{$e->espacios_nombre}}</option>@endforeach</x-select>
                <x-select wire:model="clase_prueba_modalidad_id"><option value="">Modalidad (opcional)</option>@foreach(\App\Models\Modalidad::orderBy('modalidad_nombre')->get() as $m)<option value="{{$m->modalidad_id}}">{{$m->modalidad_nombre}}</option>@endforeach</x-select>
                <x-input wire:model="clase_prueba_observacion" placeholder="Observación" />
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-forms.red-button wire:click="$set('open_create_clase_prueba',false)">Cancelar</x-forms.red-button>
            <x-forms.blue-button wire:click="saveClasePrueba">Guardar</x-forms.blue-button>
        </x-slot>
    </x-dialog-modal>

    @if($open_edit_diario)
    <x-dialog-modal wire:model="open_edit_diario">
        <x-slot name="title">
            Actualizar diario @if($diario_contexto) — {{ $diario_contexto }} @endif
        </x-slot>
        <x-slot name="content">
            <div class="space-y-5">
                @if($diario_grupo_es_evento)
                    <fieldset class="border border-slate-200 rounded-lg p-3">
                        <legend class="px-2 font-semibold text-slate-700">Hecho del evento</legend>
                        <div>
                            <x-forms.label for="diarios_hecho" value="{{__('Done')}}" required class="!w-auto !mt-0 !mb-1 text-xs uppercase tracking-wider font-semibold text-slate-500" />
                            <x-forms.textarea id="diarios_hecho" rows="7" class="w-full text-sm rounded-lg shadow-sm border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 placeholder-slate-400" wire:model="diarios_hecho" placeholder="¿Qué se logró en el evento?">
                            </x-forms.textarea>
                            <x-forms.input-error for="diarios_hecho" class="mt-1"/>
                        </div>
                    </fieldset>
                @else
                {{-- Info Bar: Profesor y Salón --}}
                <div class="bg-slate-50 border border-slate-200/60 rounded-xl p-4 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="text-xs uppercase tracking-wider font-semibold text-slate-400">Profesor</span>
                        <span class="text-sm font-bold text-slate-800 bg-white border border-slate-200 px-3 py-1.5 rounded-lg shadow-sm">{{ $diarios_profesor }}</span>
                    </div>
                    <div class="flex items-center gap-3 flex-1 min-w-[200px] max-w-xs">
                        <span class="text-xs uppercase tracking-wider font-semibold text-slate-400">Salón</span>
                        <x-select class="w-full text-sm rounded-lg shadow-sm border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 py-1.5" wire:model="espacios_id">
                            <option value="">{{__('Select')}}</option>
                            @forelse ($espacios as $item)
                            <option value="{{$item->espacios_id}}">{{$item->espacios_nombre}}</option>
                            @empty
                            <option value="">{{__('No Content')}}</option>
                            @endforelse
                        </x-select>
                    </div>
                </div>

                <fieldset class='border border-slate-200 rounded-lg p-3'>
                <legend class='px-2 font-semibold text-slate-700'>Parte 1 — Datos generales</legend>
                {{-- Fila 1: Nivel y Capítulo --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-forms.label value="{{__('Level')}}" required class="!w-auto !mt-0 !mb-1 text-xs uppercase tracking-wider font-semibold text-slate-500" />
                        <x-select class="w-full text-sm rounded-lg shadow-sm border-slate-300 focus:ring-indigo-500 focus:border-indigo-500" wire:model="idnivel">
                            <option value="">{{__('Select')}}</option>
                            @forelse ($arr_niveles as $key => $item)
                            <option value="{{$key}}">{{ucfirst($item)}} </option>
                            @empty
                            <option value="">{{__('No Content')}}</option>
                            @endforelse
                        </x-select>
                        <x-forms.input-error for="idnivel" class="mt-1" />
                    </div>
                    <div>
                        <x-forms.label value="{{__('Chapter')}}" required class="!w-auto !mt-0 !mb-1 text-xs uppercase tracking-wider font-semibold text-slate-500" />
                        <x-select class="w-full text-sm rounded-lg shadow-sm border-slate-300 focus:ring-indigo-500 focus:border-indigo-500" wire:model="id_capitulo">
                            <option value="">{{__('Select')}}</option>
                            @forelse ($arr_capitulos as $item)
                            <option value="{{$item->capitulo_id}}">{{$item->capitulo_descripcion}} - {{$item->capitulo_codigo}}</option>
                            @empty
                            <option value="">{{__('No Content')}}</option>
                            @endforelse
                        </x-select>
                        <x-forms.input-error for="id_capitulo" class="mt-1" />
                    </div>
                </div>
                {{-- Fila 2: Temática y Número de Clases --}}
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <div>
                        <x-forms.label value="Temática" required class="!w-auto !mt-0 !mb-1 text-xs uppercase tracking-wider font-semibold text-slate-500" />
                        <x-select class="w-full text-sm rounded-lg shadow-sm border-slate-300 focus:ring-indigo-500 focus:border-indigo-500" wire:model="id_tematica">
                            <option value="">{{__('Select')}}</option>
                            @forelse ($arr_tematicas as $tematica)
                            <option value="{{$tematica->tematica_id}}">{{$tematica->tematica_descripcion}}</option>
                            @empty
                            <option value="">{{__('No Content')}}</option>
                            @endforelse
                        </x-select>
                        <x-forms.input-error for="id_tematica" class="mt-1" />
                    </div>
                    <div>
                        <x-forms.label value="N° de Clases" class="!w-auto !mt-0 !mb-1 text-xs uppercase tracking-wider font-semibold text-slate-500" />
                        <x-input type="number" step="0.5" min="0.5" class="w-full text-sm text-center rounded-lg shadow-sm border-slate-300 focus:ring-indigo-500 focus:border-indigo-500" wire:model="numero_clases" />
                        <x-forms.input-error for="numero_clases" class="mt-1" />
                    </div>
                </div>
                <div class='flex flex-col items-end mt-3'><label class='text-sm flex items-center gap-2'><x-checkbox wire:model="validado_datos_generales" required/> Validado <span class="text-red-500">*</span></label><x-forms.input-error for="validado_datos_generales" class="mt-1" /></div>
                </fieldset>

                <fieldset class='border border-slate-200 rounded-lg p-3'><legend class='px-2 font-semibold text-slate-700'>Parte 2 — Hecho y Por hacer</legend>
                {{-- Hecho (Done) --}}
                <div>
                    <x-forms.label for="diarios_hecho" value="{{__('Done')}}" class="!w-auto !mt-0 !mb-1 text-xs uppercase tracking-wider font-semibold text-slate-500" />
                    <x-forms.textarea id="diarios_hecho" rows="7" class="w-full text-sm rounded-lg shadow-sm border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 placeholder-slate-400" wire:model="diarios_hecho" placeholder="¿Qué se logró en la clase?">
                    </x-forms.textarea>
                    <x-forms.input-error for="diarios_hecho" class="mt-1"/>
                </div>

                {{-- Por hacer (To do) --}}
                <div>
                    <x-forms.label for="diarios_porhacer" value="{{__('To do')}}" class="!w-auto !mt-0 !mb-1 text-xs uppercase tracking-wider font-semibold text-slate-500" />
                    <x-forms.textarea id="diarios_porhacer" rows="7" class="w-full text-sm rounded-lg shadow-sm border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 placeholder-slate-400" wire:model="diarios_porhacer" placeholder="Tareas o temas pendientes para la siguiente clase">
                    </x-forms.textarea>
                    <x-forms.input-error for="diarios_porhacer" class="mt-1"/>
                </div>
                <x-forms.input-error for="diarios_porhacer"/>
                <div class='flex flex-col items-end mt-3'><label class='text-sm flex items-center gap-2'><x-checkbox wire:model="validado_contenido_clase" required/> Validado <span class="text-red-500">*</span></label><x-forms.input-error for="validado_contenido_clase" class="mt-1" /></div>
            </fieldset>
            <fieldset class='border border-slate-200 rounded-lg p-3 mt-4'><legend class='px-2 font-semibold text-slate-700'>Parte 3 — Estudiantes</legend>
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-gray-700/50 dark:text-gray-400 border-b border-slate-200/60">
                        <tr>
                            <th scope="col" class="px-4 py-2.5 font-bold w-1/2">
                                Estudiante
                            </th>
                            <th scope="col" class="px-3 py-2.5 text-center font-bold">
                                Asistió
                            </th>
                            <th scope="col" class="px-3 py-2.5 text-center font-bold">
                                Observación
                            </th>
                                                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($estudiantes as $estudiante)
                            <tr class="transition-colors duration-200 bg-white hover:bg-slate-50/50 dark:bg-gray-800">
                                <td class="px-4 py-2.5 font-semibold text-slate-700 whitespace-nowrap dark:text-white w-1/2">
                                    {{ $estudiante->prospectos_nombres }} {{ $estudiante->prospectos_apellidos }}
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <x-checkbox wire:model="asistencias.{{ $estudiante->prospectos_id }}" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4" />
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <x-input wire:model="observaciones.{{ $estudiante->prospectos_id }}"
                                             class="w-full text-sm rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 py-1 px-2.5" placeholder="Nota o detalle" maxlength="255" />
                                </td>
                                                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class='flex flex-col items-end mt-3'><label class='text-sm flex items-center gap-2'><x-checkbox wire:model="validado_estudiantes" required/> Validado <span class="text-red-500">*</span></label><x-forms.input-error for="validado_estudiantes" class="mt-1" /></div>
            </fieldset>

            {{-- Tabla de Clases de Prueba --}}
            @if (count($clasesPrueba))
            <fieldset class='mt-6 border border-slate-100 rounded-xl overflow-hidden p-3'><legend class='px-2 font-semibold text-slate-700'>Parte 4 — Clases de prueba / Prospectos</legend>
                <div class="bg-slate-50 px-4 py-2 border-b border-slate-200/60">
                    <h3 class="text-xs uppercase tracking-wider font-bold text-slate-500">Clases de prueba</h3>
                </div>
                <div class="relative overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-slate-400 uppercase tracking-wider bg-slate-50/50 border-b border-slate-200/60">
                            <tr>
                                <th scope="col" class="px-4 py-2.5 font-bold">Prospecto</th>
                                <th scope="col" class="px-3 py-2.5 text-center font-bold">Asistió</th>
                                <th scope="col" class="px-3 py-2.5 text-center font-bold">No asistió</th>
                                <th scope="col" class="px-3 py-2.5 font-bold">Observación</th>
                                                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($clasesPrueba as $clase)
                                <tr class="transition-colors duration-200 bg-white hover:bg-slate-50/50 dark:bg-gray-800">
                                    <td class="px-4 py-2.5 font-semibold text-slate-700 whitespace-nowrap">{{ $clase->prospecto?->prospectos_nombres }} {{ $clase->prospecto?->prospectos_apellidos }}</td>
                                    <td class="px-3 py-2.5 text-center">
                                        <input type="radio" wire:model="asistenciasPrueba.{{$clase->clase_prueba_id}}" value="1" class="text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <input type="radio" wire:model="asistenciasPrueba.{{$clase->clase_prueba_id}}" value="0" class="text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                    </td>
                                    <td class="px-4 py-4">
                                        <x-input wire:model="observacionesPrueba.{{$clase->clase_prueba_id}}" class="w-full text-sm rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 py-1 px-2.5" placeholder="Nota de prueba"/>
                                    </td>
                                                                    </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class='flex flex-col items-end mt-3'><label class='text-sm flex items-center gap-2'><x-checkbox wire:model="validado_prospectos" required/> Validado <span class="text-red-500">*</span></label><x-forms.input-error for="validado_prospectos" class="mt-1" /></div>
            </fieldset>
            @endif
                @endif
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
    @endif

    @if($open_edit_plan)
    <x-dialog-modal wire:model="open_edit_plan" maxWidth="3xl">
        <x-slot name="title">
            Actualizar plan - Grupo: {{ $plan_modal_grupo ?? 'Sin cargar' }} - Fecha: {{ $plan_modal_fecha ?? 'Sin cargar' }} - Hora: {{ $plan_modal_hora ?? 'Sin cargar' }}
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
                            $diario = $firstItem['horario']['diario'] ?? null;
                            $diarios_hecho = data_get($diario, 'diarios_hecho');
                            $diarios_porhacer = data_get($diario, 'diarios_porhacer');
                            $fecha = isset($firstItem['horario']['horarios_dia']) ? date('d-m-Y', strtotime($firstItem['horario']['horarios_dia'])) : 'Sin cargar';
                            $profesor = trim(($firstItem['horario']['profesor']['profesores_nombres'] ?? '') .' '.($firstItem['horario']['profesor']['profesores_apellidos'] ?? '')) ?: 'Sin cargar';
                            $espacio = $firstItem['horario']['espacio']['espacios_nombre'] ?? 'Sin cargar';
                            $nivel = data_get($diario, 'nivel.nivel_descripcion')
                                ?? ($diario ? ($arr_niveles[data_get($diario, 'niveles_id')] ?? null) : null);
                            $capitulo = data_get($diario, 'capitulo.capitulo_descripcion')
                                ? trim(data_get($diario, 'capitulo.capitulo_descripcion') . ' - ' . data_get($diario, 'capitulo.capitulo_codigo'))
                                : ($diario ? ($arr_capitulos2[data_get($diario, 'capitulos_id')] ?? null) : null);
                            $tematicaRelacion = data_get($diario, 'tematica.tematica_descripcion');
                            $tematicaTexto = is_array(data_get($diario, 'tematica')) ? null : data_get($diario, 'tematica');
                            $tematica = $tematicaRelacion ?? data_get($diario, 'tematica_descripcion') ?? $tematicaTexto;
                            $numero_clases = data_get($diario, 'numero_clases');
                            $numero_clases_formateado = ($numero_clases !== null && $numero_clases !== '')
                                ? str_replace('.', ',', (string) (floor((float) $numero_clases) == (float) $numero_clases ? (int) $numero_clases : $numero_clases))
                                : null;
                            $regulares = collect($items)->filter(fn($eval) => !isset($eval['is_dummy']) && !isset($eval['clase_prueba_id']));
                            $pruebas = collect($items)->filter(fn($eval) => isset($eval['clase_prueba_id']));
                        @endphp

                        <div class="space-y-5">
                            <fieldset class="border border-slate-200 rounded-lg p-3">
                                <legend class="px-2 font-semibold text-slate-700">Parte 1 — Datos generales</legend>
                                <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm text-gray-500 dark:text-gray-300">
                                    <div>
                                        <p class="text-xs uppercase tracking-wider font-semibold text-slate-500 mb-1">Fecha</p>
                                        <p>{{ $fecha ?: 'Sin cargar' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wider font-semibold text-slate-500 mb-1">Profesor</p>
                                        <p>{{ $profesor ?: 'Sin cargar' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wider font-semibold text-slate-500 mb-1">Salón</p>
                                        <p>{{ $espacio ?: 'Sin cargar' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wider font-semibold text-slate-500 mb-1">Nivel</p>
                                        <p>{{ $nivel ?: 'Sin cargar' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wider font-semibold text-slate-500 mb-1">Capítulo</p>
                                        <p>{{ $capitulo ?: 'Sin cargar' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wider font-semibold text-slate-500 mb-1">Temática</p>
                                        <p>{{ $tematica ?: 'Sin cargar' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wider font-semibold text-slate-500 mb-1">N° de clases</p>
                                        <p>{{ $numero_clases_formateado ?? 'Sin cargar' }}</p>
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset class="border border-slate-200 rounded-lg p-3">
                                <legend class="px-2 font-semibold text-slate-700">Parte 2 — Hecho y Por hacer</legend>
                                <div class="space-y-4 text-sm text-gray-500 dark:text-gray-300">
                                    <div>
                                        <p class="text-xs uppercase tracking-wider font-semibold text-slate-500 mb-1">Hecho</p>
                                        <div class="min-h-[10.5rem] rounded-lg border border-slate-300 bg-white px-3 py-2 whitespace-pre-wrap break-words dark:bg-gray-800 dark:border-gray-600">{{ $diarios_hecho ?: 'Sin cargar' }}</div>
                                    </div>
                                    <div>
                                        <p class="text-xs uppercase tracking-wider font-semibold text-slate-500 mb-1">Por hacer</p>
                                        <div class="min-h-[10.5rem] rounded-lg border border-slate-300 bg-white px-3 py-2 whitespace-pre-wrap break-words dark:bg-gray-800 dark:border-gray-600">{{ $diarios_porhacer ?: 'Sin cargar' }}</div>
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset class="border border-slate-200 rounded-lg p-3">
                                <legend class="px-2 font-semibold text-slate-700">Parte 3 — Estudiantes</legend>
                                <div class="relative overflow-x-auto">
                                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                        <thead class="text-xs text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-gray-700/50 dark:text-gray-400 border-b border-slate-200/60">
                                            <tr>
                                                <th class="px-4 py-2.5 font-bold w-1/2">Estudiante</th>
                                                <th class="px-3 py-2.5 text-center font-bold">Asistencia</th>
                                                <th class="px-3 py-2.5 text-center font-bold">Observación</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse($regulares as $eval)
                                                <tr class="transition-colors duration-200 bg-white hover:bg-slate-50/50 dark:bg-gray-800">
                                                    <td class="px-4 py-2.5 font-semibold text-slate-700 whitespace-nowrap dark:text-white w-1/2">
                                                        {{ $eval['prospecto']['prospectos_nombres'] ?? '' }}
                                                        {{ $eval['prospecto']['prospectos_apellidos'] ?? '' }}
                                                    </td>
                                                    <td class="px-3 py-2.5 text-center">{{ is_null($eval['asistio'] ?? null) ? 'Sin cargar' : ((int) ($eval['asistio'] ?? 0) === 1 ? 'Sí' : 'No') }}</td>
                                                    <td class="px-3 py-2.5 text-center">{{ $eval['observacion'] ?: 'Sin cargar' }}</td>
                                                </tr>
                                            @empty
                                                <tr class="transition-colors duration-200 bg-white hover:bg-slate-50/50 dark:bg-gray-800">
                                                    <td colspan="3" class="px-4 py-2.5 text-center text-gray-500 italic">Sin registros de asistencia guardados</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </fieldset>

                            @if($pruebas->isNotEmpty())
                                <fieldset class="border border-slate-200 rounded-lg p-3">
                                    <legend class="px-2 font-semibold text-slate-700">Parte 4 — Clases de prueba / Prospectos</legend>
                                    <div class="relative overflow-x-auto">
                                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                            <thead class="text-xs text-slate-400 uppercase tracking-wider bg-slate-50/50 border-b border-slate-200/60 dark:bg-gray-700/50 dark:text-gray-400">
                                                <tr>
                                                    <th class="px-4 py-2.5 font-bold">Prospecto</th>
                                                    <th class="px-3 py-2.5 text-center font-bold">Asistencia</th>
                                                    <th class="px-4 py-2.5 font-bold">Observación</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($pruebas as $eval)
                                                    <tr class="transition-colors duration-200 bg-white hover:bg-slate-50/50 dark:bg-gray-800">
                                                        <td class="px-4 py-2.5 font-semibold text-slate-700 whitespace-nowrap dark:text-white">
                                                            {{ ($eval['prospecto']['prospectos_nombres'] ?? 'Sin cargar') . ' ' . ($eval['prospecto']['prospectos_apellidos'] ?? '') }}
                                                        </td>
                                                        <td class="px-3 py-2.5 text-center">{{ is_null($eval['asistio'] ?? null) ? 'Sin cargar' : ((int) ($eval['asistio'] ?? 0) === 1 ? 'Sí' : 'No') }}</td>
                                                        <td class="px-4 py-2.5">{{ $eval['observacion'] ?: 'Sin cargar' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </fieldset>
                            @endif
                        </div>
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
    @endif

    <div class="mt-6 border-t pt-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Resumen semanal</p>
                <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Clases asignadas esta semana</h4>
            </div>
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($profesores as $profesor)
                @php
                    $profesorColor = $profesor->profesores_color ?? '#10b981';
                @endphp
                <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800" style="border-color: {{ $profesorColor }};">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Profesor</p>
                    <div class="mt-2 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                {{ $profesor->profesores_nombres }} {{ $profesor->profesores_apellidos }}
                            </p>
                            <p class="text-xs text-gray-400">Clases asignadas</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-sm font-semibold text-white" style="background-color: {{ $profesorColor }};">
                            {{ $clasesPorProfesor[$profesor->profesores_id] ?? 0 }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>


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

        livewire.on('confirmDeactivateGrupo', (grupoId, fecha, horaId) => {
            Swal.fire({
                title: "{{ __('¿Está seguro de desactivar el grupo?') }}",
                text: "{{ __('Esta acción ocultará el grupo para la fecha y hora seleccionadas.') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                cancelButtonText: "{{__('Cancel')}}",
                confirmButtonText: "{{ __('Sí, desactivar') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    livewire.emitTo('show-horarios','deactivateGrupo', grupoId, fecha, horaId);
                }
            });
        });
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
    window.searchableDiarySelect = function ({ selected, options, placeholder, searchPlaceholder }) {
        return {
            open: false,
            query: '',
            selected,
            options,
            placeholder,
            searchPlaceholder,
            toggle() {
                this.open = !this.open;
                if (this.open) {
                    this.$nextTick(() => this.$refs.search?.focus());
                }
            },
            close() {
                this.open = false;
                this.query = '';
            },
            selectedLabel() {
                const option = this.options.find(item => String(item.id) === String(this.selected));
                return option ? option.nombre : '';
            },
            filteredOptions() {
                const normalizedQuery = this.query.toLowerCase().trim();

                if (!normalizedQuery) {
                    return this.options;
                }

                return this.options.filter(item => item.nombre.toLowerCase().includes(normalizedQuery));
            },
            selectOption(option) {
                if (option.disabled) {
                    return;
                }

                this.selected = option.id;
                this.close();
            },
        };
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (!window.horariosUndoRedoShortcutBound) {
            window.horariosUndoRedoShortcutBound = true;
            document.addEventListener('keydown', function (e) {
                const isCtrlOrCmd = e.ctrlKey || e.metaKey;
                if (!isCtrlOrCmd) return;

                const target = e.target;
                const tagName = target && target.tagName ? target.tagName.toLowerCase() : '';

                if (['input', 'textarea', 'select'].includes(tagName) || target.isContentEditable) {
                    return;
                }

                if (e.key.toLowerCase() === 'z') {
                    e.preventDefault();
                    Livewire.emit('undoHorarioShortcut');
                }

                if (e.key.toLowerCase() === 'y') {
                    e.preventDefault();
                    Livewire.emit('redoHorarioShortcut');
                }
            });
        }

        const getTable = () => document.getElementById('horarios-table');

        const refreshDraggableCells = () => {
            const table = getTable();
            if (!table) return;

            table.querySelectorAll('.grupo-cell').forEach(cell => {
                cell.setAttribute('draggable', 'true');
            });
        };

        const bindDragAndDropOnce = () => {
            const table = getTable();
            if (!table) return;

            if (table.dataset.dndBound === '1') return;
            table.dataset.dndBound = '1';

            table.addEventListener('dragstart', function (e) {
                const cell = e.target.closest('.grupo-cell');
                if (!cell) return;

                e.dataTransfer.setData('text', JSON.stringify({
                    id: cell.dataset.id,
                    dia: cell.dataset.dia,
                    hora: cell.dataset.hora,
                    grupo: cell.dataset.grupo,
                    profesor: cell.dataset.profesor,
                    espacio: cell.dataset.espacio,
                }));
            });

            table.addEventListener('dragover', function (e) {
                e.preventDefault();
            });

            table.addEventListener('drop', function (e) {
                e.preventDefault();

                const raw = e.dataTransfer.getData('text');
                if (!raw) return;

                let data;
                try {
                    data = JSON.parse(raw);
                } catch (err) {
                    console.error('DND invalid payload:', err);
                    return;
                }

                const targetCell = e.target.closest('.grupo-cell');
                if (!targetCell) return;

                const targetId = targetCell.dataset.id;
                const targetDia = targetCell.dataset.dia;
                const targetHora = targetCell.dataset.hora;
                const targetProfesor = targetCell.dataset.profesor;
                const targetEspacio = targetCell.dataset.espacio;
                const updateGrupoHorario = () => {
                    @this.updateGrupoHorario(
                        targetId,
                        targetDia,
                        targetHora,
                        data.grupo,
                        targetProfesor,
                        data.espacio,
                        data.id,
                        data.dia,
                        data.hora,
                        data.profesor,
                        data.espacio
                    );
                };

                const isChangingRow = String(data.hora) !== String(targetHora);

                if (!isChangingRow) {
                    updateGrupoHorario();
                    return;
                }

                Swal.fire({
                    title: '¿Está seguro de cambiar este grupo de fila?',
                    text: 'El grupo se moverá a un horario diferente. Esta acción puede afectar la programación existente.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    cancelButtonText: "{{ __('Cancel') }}",
                    confirmButtonText: 'Sí, cambiar de fila'
                }).then((result) => {
                    if (result.isConfirmed) {
                        updateGrupoHorario();
                    }
                });
            });
        };

        const initializeColumnCollapse = () => {
            const table = document.getElementById('horarios-table');
            if (!table) return;

            const headers = table.querySelectorAll('.collapsible-header');
            if (!headers.length) return;

            const gridTemplateValue = getComputedStyle(table).gridTemplateColumns;
            const baseColumns = gridTemplateValue.match(/(?:\([^()]*\)|\S)+/g) || [];
            if (!baseColumns.length) return;
            const collapsedColumns = new Set();

            const updateTemplateColumns = () => {
                const updatedColumns = baseColumns.map((size, index) => collapsedColumns.has(index) ? '2rem' : size);
                table.style.gridTemplateColumns = updatedColumns.join(' ');

                document.querySelectorAll('.day-header-group').forEach(group => {
                    const start = Number(group.dataset.startIndex);
                    const span = Number(group.dataset.span);
                    const innerGrid = group.querySelector('.day-header-grid');
                    if (!innerGrid || Number.isNaN(start) || Number.isNaN(span)) return;

                    const slice = updatedColumns.slice(start, start + span);
                    if (slice.length) {
                        innerGrid.style.gridTemplateColumns = slice.join(' ');
                    }
                });
            };

            headers.forEach(header => {
                if (header.dataset.collapseBound === '1') return;
                header.dataset.collapseBound = '1';

                header.addEventListener('click', () => {
                    const columnIndex = Number(header.dataset.columnIndex);
                    const columnKey = header.dataset.columnKey;
                    if (Number.isNaN(columnIndex) || !columnKey) return;

                    const shouldCollapse = !collapsedColumns.has(columnIndex);
                    if (shouldCollapse) {
                        collapsedColumns.add(columnIndex);
                        header.classList.add('is-collapsed');
                    } else {
                        collapsedColumns.delete(columnIndex);
                        header.classList.remove('is-collapsed');
                    }

                    const label = header.querySelector('.collapsible-label');
                    if (label) {
                        if (shouldCollapse) {
                            label.textContent = label.dataset.initial || label.textContent.charAt(0);
                        } else {
                            label.textContent = label.dataset.fullText || label.textContent;
                        }
                    }

                    document.querySelectorAll(`[data-column-key="${columnKey}"]`).forEach(cell => {
                        cell.classList.toggle('column-collapsed', shouldCollapse);
                    });

                    updateTemplateColumns();
                });
            });
        };

        // Inicializar al cargar la página
        bindDragAndDropOnce();
        refreshDraggableCells();
        initializeColumnCollapse();

        // Volver a inicializar después de una actualización de Livewire
        document.addEventListener('livewire:update', () => {
            refreshDraggableCells();
            initializeColumnCollapse();
        });
    });

    </script>
    @endpush


</div>
