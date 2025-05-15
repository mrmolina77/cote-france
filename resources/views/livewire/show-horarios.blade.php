<div @class([
    'origin-top-left scale-100 -translate-x-0 '  => $porcentaje === '0',
    'origin-top-left scale-95 -translate-x-0 ' => $porcentaje === '1',
    'origin-top-left scale-90 -translate-x-0 ' => $porcentaje === '2',
    'origin-top-left scale-75 -translate-x-0 ' => $porcentaje === '3',
    'origin-top-left scale-50 -translate-x-0 ' => $porcentaje === '4'
    ]) >
    @section('content')
    <p>{{ __('Timetable') }}</p>
    @endsection
    <div class="w-full px-0"  wire:ignore.self wire:updated="initializeDragAndDrop">
        @if ($semanal)
            <table class="table-auto w-full border-2 border-separate" id="horarios-table">
                <thead>
                    <tr>
                        <th class="border p-0 w-36" colspan="8">
                            <div>Semana # {{$semana}}</div>
                        </th>
                    </tr>
                    <tr>
                        <!-- Columnas de cabecera vacías -->
                        <th class="border p-0 w-20" colspan="8">
                            <div class="grid h-full max-w-lg grid-cols-4 gap-4 mx-auto">
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
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Fila 1 -->
                    <tr>
                        <!-- Columnas de cabecera vacías -->
                        <th class="border p-2 w-40">{{ __('Hours') }}</th>
                        @foreach ( $dias as $dia )
                            <th class="border border-black p-[10px]">{{$dia->dias_nombre}} {{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('DD')}}
                                <table>
                                    <tr>
                                        @foreach ($profesores as $profesor)
                                        <td class="w-24 border-1 items-center justify-center">
                                            <div style="color:{{$profesor->profesores_color}}" class="overflow-hidden text-ellipsis whitespace-nowrap font-serif font-extrabold text-sm">{{$profesor->profesores_nombres}}</div> {{-- Tamaño de fuente ajustado --}}
                                        </td>
                                        @endforeach
                                    </tr>
                                </table>
                            </th>
                        @endforeach
                        <th class="border p-2 w-40">{{ __('Hours') }}</th>
                        @foreach ( $dias2 as $dia )
                            <th class="border border-black p-[10px]">{{$dia->dias_nombre}} {{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('DD')}}
                                <table>
                                    <tr>
                                        @foreach ($profesores as $profesor)
                                        <td class="w-24 border-1 items-center justify-center">
                                            <div style="color:{{$profesor->profesores_color}}" class="overflow-hidden text-ellipsis whitespace-nowrap font-serif font-extrabold text-sm">{{$profesor->profesores_nombres}}</div> {{-- Tamaño de fuente ajustado --}}
                                        </td>
                                        @endforeach
                                    </tr>
                                </table>
                            </th>
                        @endforeach
                    </tr>
                    @foreach ( $horas  as $pos1 => $hora )
                        <tr>
                            <td class="border text-center align-top"><samp class="font-serif font-extrabold text-sm">{{$hora->horas_desde}} - {{$hora->horas_hasta}}</samp></td>
                            @foreach ( $dias as $dia )
                                <td class="border p-0 text-center">
                                    <table class="border border-black">
                                        <tr>
                                            @foreach ($profesores as $profesor)
                                                @if (isset($horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]))
                                                    <td class="h-full grupo-cell"
                                                        data-id="{{ $horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['id'] }}"
                                                        data-dia="{{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')}}"
                                                        data-espacio="{{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['espacios_id']}}"
                                                        data-hora="{{ $hora->horas_id }}"
                                                        data-grupo="{{ $horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['grupo_id'] }}"
                                                        data-profesor="{{$profesor->profesores_id}}"
                                                        >
                                                        <div class="border-2 w-24 min-h-20 grid grid-cols-1 {{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['bgcolor']}}"> {{-- Ancho ajustado --}}
                                                            @if ($horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['modalidad'] == '2')
                                                            <div style="color: {{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['color']}};" class="font-serif text-sm font-bold overflow-hidden text-ellipsis whitespace-nowrap"> <a href="{{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['enlace']}}" target="_blank" rel="noopener noreferrer">{{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['nombre']}}</a></div> {{-- Tamaño de fuente ajustado --}}
                                                            @else
                                                                <div style="color: {{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['color']}};" class="font-serif font-extrabold text-sm overflow-hidden text-ellipsis whitespace-nowrap">{{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['nombre']}}</div> {{-- Tamaño de fuente ajustado --}}
                                                            @endif
                                                            {{-- <div style="color: {{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['color']}};" class="text-base font-bold">{{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['espacio']}}</div> --}}
                                                            <div class="flex items-center justify-center">
                                                                <div><i class="fas fa-trash text-red-500 m-2 cursor-pointer" wire:click="$emit('deleteHorario',{{ $horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['id'] }})"></i></div>
                                                                <div><i class="fas fa-calendar-check text-green-500 m-2 cursor-pointer" wire:click="editPlan({{ $horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['id'] }})"></i></div>
                                                                <div><i class="fas fa-book text-blue-500 m-2 cursor-pointer" wire:click="editDiario({{ $horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['id'] }})"></i></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                @else
                                                    @if(isset($grupo_deta[$dia->dias_id][$hora->horas_id][$profesor->profesores_id]))
                                                        <td class="h-full grupo-cell"
                                                        data-id="0"
                                                        data-dia="{{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')}}"
                                                        data-espacio="{{$grupo_deta[$dia->dias_id][$hora->horas_id][$profesor->profesores_id]['espacios_id']}}"
                                                        data-hora="{{$hora->horas_id}}"
                                                        data-grupo="{{$grupo_deta[$dia->dias_id][$hora->horas_id][$profesor->profesores_id]['grupo_id']}}"
                                                        data-profesor="{{ $profesor->profesores_id }}"
                                                        >
                                                            <div class="border-2 w-24 min-h-20 grid grid-cols-1 justify-center items-center {{$grupo_deta[$dia->dias_id][$hora->horas_id][$profesor->profesores_id]['color']}}" wire:key="task-{{ $dia->dias_id }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}"> {{-- Ancho ajustado --}}
                                                                <div class="overflow-hidden text-ellipsis whitespace-nowrap text-center font-serif font-extrabold text-sm"> {{-- Tamaño de fuente ajustado --}}
                                                                    {{$grupo_deta[$dia->dias_id][$hora->horas_id][$profesor->profesores_id]['grupo_nombre']}}
                                                                </div>
                                                                {{-- <i class="fas fa-plus text-emerald-500 mr-4 cursor-pointer" wire:click="edit('{{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')}}',{{ $profesor->profesores_id }},{{$hora->horas_id}},{{$profesor->profesores_id}})"></i> --}}
                                                            </div>
                                                        </td>
                                                    @else
                                                        <td class="h-full grupo-cell"
                                                        data-id="0"
                                                        data-dia="{{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')}}"
                                                        data-espacio="0"
                                                        data-hora="{{$hora->horas_id}}"
                                                        data-grupo="0"
                                                        data-profesor="{{ $profesor->profesores_id }}"
                                                        >
                                                            <div class="border-2 w-24 min-h-20 grid grid-cols-1 justify-center items-center" wire:key="task-{{ $dia->dias_id }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}"> {{-- Ancho ajustado --}}
                                                                <i class="fas fa-plus text-emerald-500 cursor-pointer" wire:click="edit('{{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')}}',{{ $profesor->profesores_id }},{{$hora->horas_id}},{{$profesor->profesores_id}})"></i>
                                                            </div>
                                                        </td>
                                                    @endif
                                                @endif
                                            @endforeach
                                        </tr>
                                    </table>
                                </td>

                            @endforeach

                           <td class="border text-center align-top"><samp class="font-serif font-extrabold text-sm">{{$horas2[$pos1]->horas_desde}} - {{$horas2[$pos1]->horas_hasta}}</samp></td>
                           <td class="border p-0 text-center">
                                    <table class="border border-black">
                                        <tr>
                                            @foreach ($profesores as $profesor)
                                                @if (isset($horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dias2[0]->dias_id)->isoFormat('YYYY-MM-DD')][$horas2[$pos1]->horas_id][$profesor->profesores_id]))
                                                    <td class="h-full grupo-cell"
                                                        data-id="{{ $horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dias2[0]->dias_id)->isoFormat('YYYY-MM-DD')][$horas2[$pos1]->horas_id][$profesor->profesores_id]['id'] }}"
                                                        data-dia="{{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dias2[0]->dias_id)->isoFormat('YYYY-MM-DD')}}"
                                                        data-espacio="{{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dias2[0]->dias_id)->isoFormat('YYYY-MM-DD')][$horas2[$pos1]->horas_id][$profesor->profesores_id]['espacios_id']}}"
                                                        data-hora="{{ $horas2[$pos1]->horas_id }}"
                                                        data-grupo="{{ $horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dias2[0]->dias_id)->isoFormat('YYYY-MM-DD')][$horas2[$pos1]->horas_id][$profesor->profesores_id]['grupo_id'] }}"
                                                        data-profesor="{{$profesor->profesores_id}}"
                                                        >
                                                        <div class="border-2 w-24 min-h-20 grid grid-cols-1 {{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dias2[0]->dias_id)->isoFormat('YYYY-MM-DD')][$horas2[$pos1]->horas_id][$profesor->profesores_id]['bgcolor']}}"> {{-- Ancho ajustado --}}
                                                            @if ($horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dias2[0]->dias_id)->isoFormat('YYYY-MM-DD')][$horas2[$pos1]->horas_id][$profesor->profesores_id]['modalidad'] == '2')
                                                            <div style="color: {{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dias2[0]->dias_id)->isoFormat('YYYY-MM-DD')][$horas2[$pos1]->horas_id][$profesor->profesores_id]['color']}};" class="font-serif font-extrabold text-sm overflow-hidden text-ellipsis whitespace-nowrap"> <a href="{{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dias2[0]->dias_id)->isoFormat('YYYY-MM-DD')][$horas2[$pos1]->horas_id][$profesor->profesores_id]['enlace']}}" target="_blank" rel="noopener noreferrer">{{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dias2[0]->dias_id)->isoFormat('YYYY-MM-DD')][$horas2[$pos1]->horas_id][$profesor->profesores_id]['nombre']}}</a></div> {{-- Tamaño de fuente ajustado --}}
                                                            @else
                                                                <div style="color: {{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dias2[0]->dias_id)->isoFormat('YYYY-MM-DD')][$horas2[$pos1]->horas_id][$profesor->profesores_id]['color']}};" class="font-serif font-extrabold text-sm overflow-hidden text-ellipsis whitespace-nowrap">{{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dias2[0]->dias_id)->isoFormat('YYYY-MM-DD')][$horas2[$pos1]->horas_id][$profesor->profesores_id]['nombre']}}</div> {{-- Tamaño de fuente ajustado --}}
                                                            @endif
                                                            {{-- <div style="color: {{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['color']}};" class="text-base font-bold">{{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['espacio']}}</div> --}}
                                                            <div class="flex items-center justify-center">
                                                                <div><i class="fas fa-trash text-red-500 m-2 cursor-pointer" wire:click="$emit('deleteHorario',{{ $horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dias2[0]->dias_id)->isoFormat('YYYY-MM-DD')][$horas2[$pos1]->horas_id][$profesor->profesores_id]['id'] }})"></i></div>
                                                                <div><i class="fas fa-calendar-check text-green-500 m-2 cursor-pointer" wire:click="editPlan({{ $horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dias2[0]->dias_id)->isoFormat('YYYY-MM-DD')][$horas2[$pos1]->horas_id][$profesor->profesores_id]['id'] }})"></i></div>
                                                                <div><i class="fas fa-book text-blue-500 m-2 cursor-pointer" wire:click="editDiario({{ $horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dias2[0]->dias_id)->isoFormat('YYYY-MM-DD')][$horas2[$pos1]->horas_id][$profesor->profesores_id]['id'] }})"></i></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                @else
                                                    @if(isset($grupo_deta[$dias2[0]->dias_id][$horas2[$pos1]->horas_id][$profesor->profesores_id]))
                                                        <td class="h-full grupo-cell"
                                                        data-id="0"
                                                        data-dia="{{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dias2[0]->dias_id)->isoFormat('YYYY-MM-DD')}}"
                                                        data-espacio="{{$grupo_deta[$dias2[0]->dias_id][$horas2[$pos1]->horas_id][$profesor->profesores_id]['espacios_id']}}"
                                                        data-hora="{{$horas2[$pos1]->horas_id}}"
                                                        data-grupo="{{$grupo_deta[$dias2[0]->dias_id][$horas2[$pos1]->horas_id][$profesor->profesores_id]['grupo_id']}}"
                                                        data-profesor="{{ $profesor->profesores_id }}"
                                                        >
                                                            <div class="border-2 w-24 min-h-20 grid grid-cols-1 justify-center items-center {{$grupo_deta[$dias2[0]->dias_id][$horas2[$pos1]->horas_id][$profesor->profesores_id]['color']}}" wire:key="task-{{ $dias2[0]->dias_id }}-{{ $horas2[$pos1]->horas_id }}-{{ $profesor->profesores_id }}"> {{-- Ancho ajustado --}}
                                                                <div class="overflow-hidden text-ellipsis whitespace-nowrap text-center font-serif font-extrabold text-sm"> {{-- Tamaño de fuente ajustado --}}
                                                                    {{$grupo_deta[$dias2[0]->dias_id][$horas2[$pos1]->horas_id][$profesor->profesores_id]['grupo_nombre']}}
                                                                </div>
                                                                {{-- <i class="fas fa-plus text-emerald-500 mr-4 cursor-pointer" wire:click="edit('{{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')}}',{{ $profesor->profesores_id }},{{$hora->horas_id}},{{$profesor->profesores_id}})"></i> --}}
                                                            </div>
                                                        </td>
                                                    @else
                                                        <td class="h-full grupo-cell"
                                                        data-id="0"
                                                        data-dia="{{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dias2[0]->dias_id)->isoFormat('YYYY-MM-DD')}}"
                                                        data-espacio="0"
                                                        data-hora="{{$horas2[$pos1]->horas_id}}"
                                                        data-grupo="0"
                                                        data-profesor="{{ $profesor->profesores_id }}"
                                                        >
                                                            <div class="border-2 w-24 min-h-20 grid grid-cols-1 justify-center items-center" wire:key="task-{{ $dias2[0]->dias_id }}-{{ $horas2[$pos1]->horas_id }}-{{ $profesor->profesores_id }}"> {{-- Ancho ajustado --}}
                                                                <i class="fas fa-plus text-emerald-500 cursor-pointer" wire:click="edit('{{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dias2[0]->dias_id)->isoFormat('YYYY-MM-DD')}}',{{ $profesor->profesores_id }},{{$horas2[$pos1]->horas_id}},{{$profesor->profesores_id}})"></i>
                                                            </div>
                                                        </td>
                                                    @endif
                                                @endif
                                            @endforeach
                                        </tr>
                                    </table>
                                </td>
                        </tr>
                    @endforeach

                </tbody>
            </table>
        @else
            <table class="table-auto w-full border-2 border-separate" id="horarios-table">
                <thead>
                    <tr>
                        <!-- Columnas de cabecera vacías -->
                        <th class="border p-0 w-36" colspan="{{count($profesores)+1}}">
                            <div class="grid h-full max-w-lg grid-cols-2 gap-4 mx-auto">
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
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Fila 1 -->
                    <tr>
                        <!-- Columnas de cabecera vacías -->
                        <th class="border p-2 w-40">{{ __('Hours') }}</th>
                        @foreach ( $profesores as $profesor )
                        <th class="border p-2 w-24"> {{-- Ancho ajustado --}}
                            <div style="color:{{$profesor->profesores_color}}" class="overflow-hidden text-ellipsis whitespace-nowrap font-serif font-extrabold text-sm">{{$profesor->profesores_nombres}}</div> {{-- Tamaño de fuente ajustado --}}
                        </th>
                        @endforeach
                    </tr>
                    @foreach ( $horas as $hora )
                        <tr>
                            <td class="border text-center align-top"><samp class="font-serif font-extrabold text-sm">{{$hora->horas_desde}} - {{$hora->horas_hasta}}</samp></td>
                            @foreach ($profesores as $profesor)
                                @if (isset($horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]))
                                <td class="h-full bg-gray-200 grupo-cell w-24" {{-- Ancho ajustado --}}
                                    data-id="{{ $horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['id'] }}"
                                    data-dia="{{\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')}}"
                                    data-espacio="{{$horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['espacios_id']}}"
                                    data-hora="{{ $hora->horas_id }}"
                                    data-grupo="{{ $horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['grupo_id'] }}"
                                    data-profesor="{{$profesor->profesores_id}}"
                                >
                                    <div class="h-full flex flex-col items-center justify-center"> {{-- w-full eliminado, el td ya tiene ancho --}}
                                        @if ($horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['modalidad'] == '2')
                                            <div style="color: {{$horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['color']}};" class="font-serif text-sm font-bold overflow-hidden text-ellipsis whitespace-nowrap w-full text-center"> {{-- Tamaño de fuente ajustado --}}
                                               <a href="{{$horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['enlace']}}" target="_blank" rel="noopener noreferrer"> {{$horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['nombre']}} </a>
                                            </div>
                                        @else
                                            <div style="color: {{$horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['color']}};" class="font-serif text-sm font-extrabold overflow-hidden text-ellipsis whitespace-nowrap w-full text-center"> {{-- Tamaño de fuente ajustado --}}
                                                {{$horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['nombre']}}
                                            </div>
                                        @endif
                                        {{-- <div style="color: {{$horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['color']}};" class="text-base font-bold">
                                            {{$horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['espacio']}}
                                        </div> --}}
                                        <div class="flex items-center justify-center">
                                            <div><i class="fas fa-trash text-red-500 m-2 cursor-pointer" wire:click="$emit('deleteHorario',{{ $horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['id'] }})"></i></div>
                                            <div><i class="fas fa-calendar-check text-green-500 m-2 cursor-pointer" wire:click="editPlan({{ $horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['id'] }})"></i></div>
                                            <div><i class="fas fa-book text-blue-500 m-2 cursor-pointer" wire:click="editDiario({{ $horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['id'] }})"></i></div>
                                        </div>
                                    </div>
                                </td>
                                @else
                                    @if(isset($grupo_deta[\Carbon\Carbon::parse($fecha)->isoFormat('d')+1][$hora->horas_id][$profesor->profesores_id]))
                                        <td class="h-full grupo-cell text-center align-middle w-24" {{-- Ancho ajustado --}}
                                        data-id="0"
                                        data-dia="{{\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')}}"
                                        data-espacio="{{$grupo_deta[\Carbon\Carbon::parse($fecha)->isoFormat('d')+1][$hora->horas_id][$profesor->profesores_id]['espacios_id']}}"
                                        data-hora="{{$hora->horas_id}}"
                                        data-grupo="{{$grupo_deta[\Carbon\Carbon::parse($fecha)->isoFormat('d')+1][$hora->horas_id][$profesor->profesores_id]['grupo_id']}}"
                                        data-profesor="{{ $profesor->profesores_id }}"
                                        >
                                            <div class="min-h-20 grid grid-cols-1 justify-center items-center" wire:key="task-{{ \Carbon\Carbon::parse($fecha)->isoFormat('d')+1 }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                                <div class="overflow-hidden text-ellipsis whitespace-nowrap w-full font-serif font-extrabold text-sm text-center"> {{-- Tamaño de fuente y centrado ajustado --}}
                                                    {{$grupo_deta[\Carbon\Carbon::parse($fecha)->isoFormat('d')+1][$hora->horas_id][$profesor->profesores_id]['grupo_nombre']}}
                                                </div>
                                            </div>
                                        </td>
                                    @else
                                        <td class="h-full grupo-cell text-center align-middle border-2 w-24" {{-- Ancho ajustado --}}
                                        data-id="0"
                                        data-dia="{{$fecha}}"
                                        data-espacio="0"
                                        data-hora="{{$hora->horas_id}}"
                                        data-grupo="0"
                                        data-profesor="{{ $profesor->profesores_id }}"
                                        >
                                            <div class="min-h-20 grid grid-cols-1 justify-center items-center" wire:key="task-{{ \Carbon\Carbon::parse($fecha)->isoFormat('d')+1 }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                                <i class="fas fa-plus text-emerald-500 cursor-pointer" wire:click="edit('{{\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')}}',{{ $profesor->profesores_id }},{{$hora->horas_id}},{{$profesor->profesores_id}})"></i>
                                            </div>
                                        </td>
                                    @endif
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <x-dialog-modal  wire:model="open_edit">
        <x-slot name="title">
            Agregar diario
        </x-slot>
        <x-slot name="content">
            <div>
                <div class="mb-4 flex">
                    <x-forms.label for="grupo_id" value="{{__('Group')}}: " />
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
                        <x-forms.label  for="espacios_id" value="{{__('Spaces')}}: " />
                        <x-select class="flex-1 ml-4" wire:model="espacios_id" id="espacios_id">
                            <option value="">{{__('Select')}}</option>
                            @forelse ($espacios as $item)
                            <option value="{{$item->espacios_id}}">{{$item->espacios_nombre}}</option>
                            @empty
                            <option value="">{{__('No Content')}}</option>
                            @endforelse
                        </x-select>
                    </div>
                    <x-forms.input-error for="espacios_id"/>
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
                    <x-forms.label value="{{ $diarios_profesor}} " /> {{-- Añadido font-bold y margen --}}
                    <x-forms.label value="{{__('Space')}}: " class="font-bold" />
                    <x-forms.label value="{{ $diarios_espacio}} " /> {{-- Añadido font-bold --}}
                </div>
                <div class="mb-4 flex">
                    <x-forms.label for="diarios_hecho" value="{{__('Done')}}: " />
                    <x-forms.textarea id="diarios_hecho" rows="3" class="flex-1 ml-4" wire:model="diarios_hecho">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="diarios_hecho"/>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('To do')}}: " />
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
                    <thead class="font-serif font-extrabold text-sm text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
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
                            $profesor = $firstItem['horario']['profesor']['profesores_nombres'].' '.$firstItem['horario']['profesor']['profesores_nombres'];
                            $espacio = $firstItem['horario']['espacio']['espacios_nombre'];
                        @endphp

                        <h3 class="text-lg font-bold text-gray-700 dark:text-gray-100">Fecha: {{ $fecha }}</h3>
                        <p class="text-lg font-bold text-gray-700 dark:text-gray-100">Profesor: {{ $profesor }} - Espacio: {{ $espacio }} </p>

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
                            <thead class="font-serif font-extrabold text-sm text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-300">
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
