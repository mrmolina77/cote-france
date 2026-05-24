<div>
    <style>
        .day-separator-left {
            border-left: 3px solid #9ca3af;
        }
    </style>
    @section('content')
    <p>{{ __('Timetable') }} {{__('Teacher')}}</p>
    @endsection
    <div class="w-full px-0" wire:ignore.self>
        @if ($semanal)
            {{-- Cabecera Fija --}}
            <div class="border p-2 bg-gray-100">
                <div class="grid h-full max-w-4xl grid-cols-6 gap-4 mx-auto">
                    <div class="col-span-full text-center font-bold">
                        <div>Semana # {{$semana}}</div>
                    </div>
                    <div class="flex items-center justify-center">
                        <button class="w-full py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700" wire:click="toggleSoloProfesor">
                            {{ $solo_profesor ? 'Todos' : 'Mis horarios' }}
                        </button>
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
                    <div class="flex items-center">
                        <span
                            class="w-full py-2.5 px-5 text-sm font-semibold rounded-lg border flex items-center justify-center gap-2 {{ $semana_activa ? 'text-green-800 bg-green-100 border-green-200' : 'text-yellow-800 bg-yellow-100 border-yellow-200' }}"
                        >
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
                    </div>
                    <div class="flex items-center justify-center">
                        <button class="w-full py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700" wire:click="siguiente">
                            Siguiente
                        </button>
                    </div>
                </div>
            </div>

            {{-- Contenedor del Grid con Scroll --}}
            @if($semana_activa)
            <div @class([
                'overflow-x-auto origin-top-left',
                'scale-100 w-full' => $porcentaje == '0',
                'scale-95 w-[105.26%]' => $porcentaje == '1',
                'scale-90 w-[111.11%]' => $porcentaje == '2',
                'scale-75 w-[133.33%]' => $porcentaje == '3',
                'scale-50 w-[200%]' => $porcentaje == '4',
            ]) wire:updated="initializeDragAndDrop">
                <div class="grid min-w-max border-2 border-gray-200 rounded-lg overflow-hidden" id="horarios-table" style="display: grid; grid-template-columns: minmax(2.56rem, 2.56rem) repeat({{ count($dias) * count($profesores) }}, minmax(2.56rem, 1fr)) minmax(2.56rem, 2.56rem) repeat({{ count($dias2) * count($profesores) }}, minmax(2.56rem, 1fr));">
                {{-- Day/Professor Headers --}}
                <div class="border-r border-gray-200 px-1 py-0.5 w-16 sticky top-0 bg-gray-50 z-10 flex items-center justify-center font-sans font-semibold text-sm">{{ __('Hours') }}</div>
                @foreach ( $dias as $dia )
                    <div class="border-r border-gray-200 p-[0.1rem] sticky top-0 bg-gray-50 z-10" style="grid-column: span {{ count($profesores) }};">
                        <div class="text-center font-sans font-semibold text-sm">{{$dia->dias_nombre}} {{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('DD')}}</div>
                        <div class="grid" style="grid-template-columns: repeat({{ count($profesores) }}, 1fr);">
                            @foreach ($profesores as $profesor)
                            @php $addSeparator = $loop->first; @endphp
                            <div class="w-full items-center justify-center p-[0.1rem] {{ $addSeparator ? 'day-separator-left' : '' }}">
                                <div style="background-color:{{$profesor->profesores_color}}" class="overflow-hidden text-ellipsis whitespace-nowrap font-sans font-semibold text-xs text-center text-white rounded-md py-1">{{$profesor->profesores_nombres}}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div class="border-r border-gray-200 px-1 py-0.5 w-16 sticky top-0 bg-gray-50 z-10 flex items-center justify-center font-sans font-semibold text-sm">{{ __('Hours') }}</div>
                @foreach ( $dias2 as $dia )
                    <div class="border-r border-gray-200 p-[0.1rem] sticky top-0 bg-gray-50 z-10" style="grid-column: span {{ count($profesores) }};">
                        <div class="text-center font-sans font-semibold text-sm">{{$dia->dias_nombre}} {{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('DD')}}</div>
                        <div class="grid" style="grid-template-columns: repeat({{ count($profesores) }}, 1fr);">
                            @foreach ($profesores as $profesor)
                            @php $addSeparator = $loop->first; @endphp
                            <div class="w-full items-center justify-center p-[0.1rem] {{ $addSeparator ? 'day-separator-left' : '' }}">
                                <div style="background-color:{{$profesor->profesores_color}}" class="overflow-hidden text-ellipsis whitespace-nowrap font-sans font-semibold text-xs text-center text-white rounded-md py-1">{{$profesor->profesores_nombres}}</div>
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
                                $horarioItem = $horarios[$currentDateString][$hora->horas_id][$profesor->profesores_id] ?? null;
                                $isOwnSlot = $profesor->profesores_id == $id_relacionado;
                                $isDayBoundary = $loop->first;
                            @endphp

                            @if ($horarioItem)
                                <div class="h-full p-[0.1rem] text-center {{ $isDayBoundary ? 'day-separator-left' : '' }}">
                                    <div class="relative w-full min-h-14 grid grid-cols-1 pb-4 {{$horarioItem['bgcolor']}} rounded-md">
                                        <div style="color: {{ $horarioItem['color'] }};" class="font-sans text-xs font-extrabold overflow-hidden text-ellipsis whitespace-nowrap w-full text-center uppercase">
                                            @if ($horarioItem['modalidad'] == '2')
                                                <a href="{{$horarioItem['enlace']}}" target="_blank" rel="noopener noreferrer">
                                                    <x-group-name :name="$horarioItem['nombre']" :abbreviate="!$solo_profesor" />
                                                </a>
                                            @else
                                                <x-group-name :name="$horarioItem['nombre']" :abbreviate="!$solo_profesor" />
                                            @endif
                                        </div>
                                        @php
                                            $diarioActualizado = $horarioItem['diario_actualizado'] ?? null;
                                            $limiteActualizacion = \Carbon\Carbon::parse($currentDateString . ' ' . $hora->horas_desde)->addHour();
                                            $mostrarEstado = $diarioActualizado || \Carbon\Carbon::now()->greaterThanOrEqualTo($limiteActualizacion);
                                        @endphp
                                        @if($mostrarEstado)
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
                                        @if ($mostrarPendienteAnterior)
                                            <span class="absolute bottom-1 left-1 text-xs font-extrabold text-red-600" aria-label="Clase anterior pendiente">+</span>
                                        @endif
                                        <div class="flex items-center justify-center">
                                            <div><i class="fas fa-calendar-check text-green-500 m-1 cursor-pointer" wire:click="editPlan({{ $horarioItem['id'] }})"></i></div>
                                            <div><i class="fas fa-book text-blue-500 m-1 cursor-pointer" wire:click="editDiario({{ $horarioItem['id'] }})"></i></div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                @php $grupoDetalle = $grupo_deta[$dia->dias_id][$hora->horas_id][$profesor->profesores_id] ?? null; @endphp
                                @if($grupoDetalle)
                                    <div class="h-full p-[0.1rem] text-center {{ $isDayBoundary ? 'day-separator-left' : '' }}">
                                        <div class="w-full min-h-14 grid grid-cols-1 justify-center items-center {{$grupoDetalle['color']}} uppercase rounded-md" wire:key="task-{{ $dia->dias_id }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                            <x-group-name :name="$grupoDetalle['grupo_nombre']" :abbreviate="!$solo_profesor" class="text-center font-sans font-extrabold text-xs uppercase" />
                                        </div>
                                    </div>
                                @else
                                    <div class="h-full p-[0.1rem] text-center {{ $isDayBoundary ? 'day-separator-left' : '' }}">
                                        <div class="w-full min-h-14 grid grid-cols-1 justify-center items-center bg-amber-50 rounded-md" wire:key="task-{{ $dia->dias_id }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                            {{-- Celda vacía sin acciones --}}
                                        </div>
                                    </div>
                                @endif
                            @endif
                        @endforeach
                    @endforeach

                    {{-- Hour Cell (Weekend) --}}
                    <div class="border-r border-gray-200 text-center px-1 py-0.5 flex items-center justify-center">
                        @if (isset($horas2[$pos1]))
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
                                $horarioItem = ($currentHourId && isset($horarios[$currentDateString][$currentHourId][$profesor->profesores_id])) ? $horarios[$currentDateString][$currentHourId][$profesor->profesores_id] : null;
                                $isOwnSlot = $profesor->profesores_id == $id_relacionado;
                                $isDayBoundary = $loop->first;
                            @endphp

                            @if ($horarioItem)
                                <div class="h-full p-[0.1rem] text-center {{ $isDayBoundary ? 'day-separator-left' : '' }}">
                                    <div class="relative w-full min-h-14 grid grid-cols-1 pb-4 {{$horarioItem['bgcolor']}} rounded-md">
                                        <div style="color: {{ $horarioItem['color'] }};" class="font-sans text-xs font-extrabold overflow-hidden text-ellipsis whitespace-nowrap w-full text-center uppercase">
                                            <x-group-name :name="$horarioItem['nombre']" :abbreviate="!$solo_profesor" />
                                        </div>
                                        @php
                                            $diarioActualizado = $horarioItem['diario_actualizado'] ?? null;
                                            $horaInicio = $horas2[$pos1]->horas_desde ?? null;
                                            $limiteActualizacion = $horaInicio
                                                ? \Carbon\Carbon::parse($currentDateString . ' ' . $horaInicio)->addHour()
                                                : null;
                                            $mostrarEstado = $diarioActualizado || ($limiteActualizacion && \Carbon\Carbon::now()->greaterThanOrEqualTo($limiteActualizacion));
                                        @endphp
                                        @if($mostrarEstado)
                                            @if($diarioActualizado)
                                                <span class="absolute bottom-1 right-1 h-2.5 w-2.5 rounded-full bg-emerald-500" aria-label="Actualizado"></span>
                                            @else
                                                <span class="absolute bottom-1 right-1 h-2.5 w-2.5 rounded-full bg-red-500" aria-label="Pendiente"></span>
                                            @endif
                                        @endif
                                        @php
                                            $horaInicioCarbon = $horaInicio
                                                ? \Carbon\Carbon::parse($currentDateString . ' ' . $horaInicio)
                                                : null;
                                            $mostrarPendienteAnterior = ($horarioItem['diario_anterior_pendiente'] ?? false)
                                                && $horaInicioCarbon
                                                && \Carbon\Carbon::now()->lessThan($horaInicioCarbon);
                                        @endphp
                                        @if ($mostrarPendienteAnterior)
                                            <span class="absolute bottom-1 left-1 text-xs font-extrabold text-red-600" aria-label="Clase anterior pendiente">+</span>
                                        @endif
                                        <div class="flex items-center justify-center">
                                            <div><i class="fas fa-calendar-check text-green-500 m-1 cursor-pointer" wire:click="editPlan({{ $horarioItem['id'] }})"></i></div>
                                            <div><i class="fas fa-book text-blue-500 m-1 cursor-pointer" wire:click="editDiario({{ $horarioItem['id'] }})"></i></div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                @php $grupoDetalle = ($currentHourId && isset($grupo_deta[$dia->dias_id][$currentHourId][$profesor->profesores_id])) ? $grupo_deta[$dia->dias_id][$currentHourId][$profesor->profesores_id] : null; @endphp
                                @if($grupoDetalle)
                                    <div class="h-full p-[0.1rem] text-center {{ $isDayBoundary ? 'day-separator-left' : '' }}">
                                        <div class="w-full min-h-14 grid grid-cols-1 justify-center items-center {{$grupoDetalle['color']}} uppercase rounded-md" wire:key="task-{{ $dia->dias_id }}-{{ $currentHourId }}-{{ $profesor->profesores_id }}">
                                            <x-group-name :name="$grupoDetalle['grupo_nombre']" :abbreviate="!$solo_profesor" class="text-center font-sans font-extrabold text-xs uppercase" />
                                        </div>
                                    </div>
                                @else
                                    <div class="h-full p-[0.1rem] text-center {{ $isDayBoundary ? 'day-separator-left' : '' }}">
                                        @if ($currentHourId)
                                            <div class="w-full min-h-14 grid grid-cols-1 justify-center items-center bg-amber-50 rounded-md" wire:key="task-{{ $dia->dias_id }}-{{ $currentHourId }}-{{ $profesor->profesores_id }}">
                                                {{-- Celda vacía sin acciones --}}
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
            <div class="p-4 mt-4 text-center text-sm text-yellow-800 bg-yellow-50 border border-yellow-200 rounded-lg">
                Esta semana no está activa.  Espere que sea activida.
            </div>
            @endif
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
            @if($semana_activa)
            <div @class([
                'overflow-x-auto origin-top-left',
                'scale-100 w-full' => $porcentaje == '0',
                'scale-95 w-[105.26%]' => $porcentaje == '1',
                'scale-90 w-[111.11%]' => $porcentaje == '2',
                'scale-75 w-[133.33%]' => $porcentaje == '3',
                'scale-50 w-[200%]' => $porcentaje == '4',
            ]) wire:updated="initializeDragAndDrop">
                <div class="grid min-w-max border-2 border-gray-200 rounded-lg overflow-hidden" id="horarios-table" style="display: grid; grid-template-columns: auto repeat({{ count($profesores) }}, minmax(3rem, 1fr));">
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
                            $horarioItem = $horarios[$currentDailyDateString][$hora->horas_id][$profesor->profesores_id] ?? null;
                            $isOwnSlot = $profesor->profesores_id == $id_relacionado;
                        @endphp

                        @if ($horarioItem)
                            <div class="h-full border p-0 text-center">
                                <div class="relative w-full min-h-14 grid grid-cols-1 {{$horarioItem['bgcolor']}}">
                                    <div style="color: {{ $horarioItem['color'] }};" class="font-sans text-xs font-extrabold overflow-hidden text-ellipsis whitespace-nowrap w-full text-center uppercase">
                                        @if ($horarioItem['modalidad'] == '2')
                                            <a href="{{$horarioItem['enlace']}}" target="_blank" rel="noopener noreferrer">
                                                <x-group-name :name="$horarioItem['nombre']" :abbreviate="!$solo_profesor" />
                                            </a>
                                        @else
                                            <x-group-name :name="$horarioItem['nombre']" :abbreviate="!$solo_profesor" />
                                        @endif
                                    </div>
                                    @php
                                        $diarioActualizado = $horarioItem['diario_actualizado'] ?? null;
                                        $limiteActualizacion = \Carbon\Carbon::parse($currentDailyDateString . ' ' . $hora->horas_desde)->addHour();
                                        $mostrarEstado = $diarioActualizado || \Carbon\Carbon::now()->greaterThanOrEqualTo($limiteActualizacion);
                                    @endphp
                                    @if($mostrarEstado)
                                        @if($diarioActualizado)
                                            <span class="absolute bottom-1 right-1 h-2.5 w-2.5 rounded-full bg-emerald-500" aria-label="Actualizado"></span>
                                        @else
                                            <span class="absolute bottom-1 right-1 h-2.5 w-2.5 rounded-full bg-red-500" aria-label="Pendiente"></span>
                                        @endif
                                    @endif
                                    @if ($horarioItem['diario_anterior_pendiente'] ?? false)
                                        <span class="absolute bottom-1 left-1 text-xs font-extrabold text-red-600" aria-label="Clase anterior pendiente">+</span>
                                    @endif
                                    <div class="flex items-center justify-center">
                                        <div><i class="fas fa-calendar-check text-green-500 m-1 cursor-pointer" wire:click="editPlan({{ $horarioItem['id'] }})"></i></div>
                                        <div><i class="fas fa-book text-blue-500 m-1 cursor-pointer" wire:click="editDiario({{ $horarioItem['id'] }})"></i></div>
                                    </div>
                                </div>
                            </div>
                        @else
                            @php $grupoDetalle = $grupo_deta[$currentDayOfWeek][$hora->horas_id][$profesor->profesores_id] ?? null; @endphp
                            @if($grupoDetalle)
                                <div class="h-full border p-0 text-center">
                                    <div class="w-full min-h-14 grid grid-cols-1 justify-center items-center {{$grupoDetalle['color']}} uppercase" wire:key="task-daily-{{ $currentDayOfWeek }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                        <x-group-name :name="$grupoDetalle['grupo_nombre']" :abbreviate="!$solo_profesor" class="text-center font-sans font-extrabold text-xs uppercase" />
                                    </div>
                                </div>
                            @else
                                <div class="h-full border p-0 text-center">
                                    <div class="w-full min-h-14 grid grid-cols-1 justify-center items-center bg-amber-50 rounded-md" wire:key="task-daily-{{ $currentDayOfWeek }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                        {{-- Celda vacía sin acciones --}}
                                    </div>
                                </div>
                            @endif
                        @endif
                    @endforeach
                @endforeach
                </div>
            </div>
            @else
            <div class="p-4 mt-4 text-center text-sm text-yellow-800 bg-yellow-50 border border-yellow-200 rounded-lg">
                Esta semana no está activa.  Espere que sea activida.
            </div>
            @endif
        @endif
    </div>

    <!-- Modales (copiados de show-horarios.blade.php con las mismas mejoras) -->

    @if($open_edit_diario)
    <x-dialog-modal wire:model="open_edit_diario">
        <x-slot name="title">
            Actualizar diario @if($diario_contexto) — {{ $diario_contexto }} @endif
        </x-slot>
        <x-slot name="content">
            <div class="space-y-5">
                {{-- Info Bar: Profesor y Salón (Read-only para profesores) --}}
                <div class="bg-slate-50 border border-slate-200/60 rounded-xl p-4 flex flex-wrap items-center justify-start gap-8">
                    <div class="flex items-center gap-3">
                        <span class="text-xs uppercase tracking-wider font-semibold text-slate-400">Profesor</span>
                        <span class="text-sm font-bold text-slate-800 bg-white border border-slate-200 px-3 py-1.5 rounded-lg shadow-sm">{{ $diarios_profesor }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs uppercase tracking-wider font-semibold text-slate-400">Salón</span>
                        <span class="text-sm font-bold text-slate-800 bg-white border border-slate-200 px-3 py-1.5 rounded-lg shadow-sm">{{ $diarios_espacio }}</span>
                    </div>
                </div>

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
                <div class="grid grid-cols-2 gap-4">
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
            </div>
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
                            <th scope="col" class="px-3 py-2.5 text-center font-bold">
                                Validar
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($estudiantes as $estudiante)
                            @php
                                $isValidated = $validados[$estudiante->prospectos_id] ?? false;
                            @endphp
                            <tr class="transition-colors duration-200 {{ $isValidated ? 'bg-emerald-50/60 hover:bg-emerald-100/40 dark:bg-emerald-950/20' : 'bg-white hover:bg-slate-50/50 dark:bg-gray-800' }}">
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
                                <td class="px-3 py-2.5 text-center">
                                    <button type="button" wire:click="toggleValidado({{ $estudiante->prospectos_id }})" class="inline-flex items-center justify-center p-1.5 rounded-lg transition-all duration-200 {{ $isValidated ? 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-400 dark:bg-slate-700 dark:hover:bg-slate-600' }}">
                                        @if ($isValidated)
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                        @endif
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Tabla de Clases de Prueba --}}
            @if (count($clasesPrueba))
            <div class="mt-6 border border-slate-100 rounded-xl overflow-hidden">
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
                                <th scope="col" class="px-3 py-2.5 text-center font-bold">Validar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($clasesPrueba as $clase)
                                @php
                                    $isValidatedPrueba = $validadosPrueba[$clase->clase_prueba_id] ?? false;
                                @endphp
                                <tr class="transition-colors duration-200 {{ $isValidatedPrueba ? 'bg-emerald-50/60 hover:bg-emerald-100/40 dark:bg-emerald-950/20' : 'bg-white hover:bg-slate-50/50 dark:bg-gray-800' }}">
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
                                    <td class="px-3 py-2.5 text-center">
                                        <button type="button" wire:click="toggleValidadoPrueba({{ $clase->clase_prueba_id }})" class="inline-flex items-center justify-center p-1.5 rounded-lg transition-all duration-200 {{ $isValidatedPrueba ? 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-400 dark:bg-slate-700 dark:hover:bg-slate-600' }}">
                                            @if ($isValidatedPrueba)
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                            @endif
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
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
                            $diario = $firstItem['horario']['diario'] ?? null;
                            $diarios_hecho = $diario['diarios_hecho'] ?? 'Sin descripción';
                            $diarios_porhacer = $diario['diarios_porhacer'] ?? 'Sin descripción';
                            $fecha = isset($firstItem['horario']['horarios_dia']) ? date('d-m-Y', strtotime($firstItem['horario']['horarios_dia'])) : '';
                            $profesor = ($firstItem['horario']['profesor']['profesores_nombres'] ?? '') .' '.($firstItem['horario']['profesor']['profesores_apellidos'] ?? '');
                            $espacio = $firstItem['horario']['espacio']['espacios_nombre'] ?? 'N/A';
                            $tematica = $diario['tematica'] ?? '';
                            $numero_clases = $diario['numero_clases'] ?? '';
                        @endphp

                        <h3 class="text-lg font-bold text-gray-700 dark:text-gray-100">Fecha: {{ $fecha }}</h3>
                        <p class="text-lg font-bold text-gray-700 dark:text-gray-100">Profesor: {{ $profesor }} - Salón: {{ $espacio }} </p>

                        @if ($tematica)
                        <p class="text-sm text-gray-500 dark:text-gray-300 mb-2">
                            Temática: {{ $tematica }}
                        </p>
                        @endif
                        @if ($numero_clases)
                        <p class="text-sm text-gray-500 dark:text-gray-300 mb-2">
                            Número de clases: {{ $numero_clases }}
                        </p>
                        @endif
                        <p class="text-sm text-gray-500 dark:text-gray-300 mb-2">
                            Hecho: {{ $diarios_hecho }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-300 mb-2">
                            Por hacer: {{ $diarios_porhacer }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-300 mb-2">
                            Nivel: {{ $diario ? ($arr_niveles[$diario['niveles_id']] ?? '') : '' }} - Capitulo: {{ $diario ? ($arr_capitulos2[$diario['capitulos_id']] ?? '') : '' }}
                        </p>

                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="font-mono text-sm text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-4 py-2">Estudiante</th>
                                    <th class="px-4 py-2">Asistencia</th>
                                    <th class="px-4 py-2">Observación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $eval)
                                    @if(isset($eval['is_dummy']) && $eval['is_dummy'])
                                        <tr class="bg-white dark:bg-gray-800 border-b">
                                            <td colspan="3" class="px-4 py-2 text-center text-gray-500 italic">
                                                Sin registros de asistencia guardados
                                            </td>
                                        </tr>
                                    @else
                                        <tr class="bg-white dark:bg-gray-800 border-b">
                                            <td class="px-4 py-2 font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                                <span>
                                                    {{ $eval['prospecto']['prospectos_nombres'] ?? '' }}
                                                    {{ $eval['prospecto']['prospectos_apellidos'] ?? '' }}
                                                </span>
                                                @if (isset($eval['clase_prueba_id']))
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-300">
                                                        Prueba
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2">{{ $eval['asistio'] ? 'Sí' : 'No' }}</td>
                                            <td class="px-4 py-2">{{ $eval['observacion'] }}</td>
                                        </tr>
                                    @endif
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
    @endif

</div>
