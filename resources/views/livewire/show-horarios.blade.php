<div @class([
    'scale-100 -translate-x-0 -translate-y-0'  => $porcentaje === '0',
    'scale-95 -translate-x-0 -translate-y-0' => $porcentaje === '1',
    'scale-90 -translate-x-10 -translate-y-10' => $porcentaje === '2',
    'scale-75 -translate-x-40 -translate-y-20' => $porcentaje === '3',
    'scale-50 -translate-x-80 -translate-y-60' => $porcentaje === '4'
    ]) >
    @section('content')
    <p>{{ __('Timetable') }}</p>
    @endsection
    <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-1 py-1"  wire:ignore.self wire:updated="initializeDragAndDrop">
        @if ($semanal)
            <table class="table-auto w-full border-collapse" id="horarios-table">
                <thead>
                    <tr>
                        <th class="border p-0 w-36" colspan="8">
                            <div>Semana # {{$semana}}</div>
                        </th>
                    </tr>
                    <tr>
                        <!-- Columnas de cabecera vacías -->
                        <th class="border p-0 w-20" colspan="8">
                            <div class="flex justify-between">
                                <div>
                                    <button wire:click="anterior">
                                        Anterior
                                    </button>
                                </div>
                                <div>
                                    <x-select id="porcentaje-select" class="flex-1 ml-4" wire:model="porcentaje">
                                        @forelse ($porcentajes as $key => $item)
                                        <option value="{{$key}}">{{$item}}</option>
                                        @empty
                                        <option value="">{{__('No Content')}}</option>
                                        @endforelse
                                    </x-select>
                                </div>
                                <div>
                                    <x-select id="semanal-select" class="flex-1 ml-4" wire:model="semanal">
                                        <option value="1">{{__('Weekly')}}</option>
                                        <option value="0">{{__('Daily')}}</option>
                                    </x-select>
                                </div>
                                <div>
                                    <button wire:click="siguiente">
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
                            <th class="border p-[10px]">{{$dia->dias_nombre}} {{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('DD')}}
                                <table>
                                    <tr>
                                        @foreach ($profesores as $profesor)
                                        <td class="w-20 border-1 items-center justify-center"><div style="color:{{$profesor->profesores_color}}">{{$profesor->profesores_nombres}}</div></td>
                                        @endforeach
                                    </tr>
                                </table>
                            </th>
                        @endforeach
                    </tr>
                    @foreach ( $horas as $hora )
                        <tr>
                            <td class="border text-center align-top"><samp class="text-xs">{{$hora->horas_desde}} - {{$hora->horas_hasta}}</samp></td>
                            @foreach ( $dias as $dia )
                                <td class="border p-0 text-center">
                                    <table>
                                        <tr>
                                            @foreach ($profesores as $profesor)
                                                @if (isset($horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]))
                                                    <td class="h-full bg-gray-300 grupo-cell"
                                                        data-id="{{ $horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['id'] }}"
                                                        data-dia="{{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')}}"
                                                        data-espacio="{{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['espacios_id']}}"
                                                        data-hora="{{ $hora->horas_id }}"
                                                        data-grupo="{{ $horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['grupo_id'] }}"
                                                        data-profesor="{{$profesor->profesores_id}}"
                                                        >
                                                        <div class="border-1 w-20 min-h-20 grid grid-cols-1">
                                                            <div style="color: {{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['color']}};" class="text-sm">{{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['nombre']}}</div>
                                                            <div style="color: {{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['color']}};" class="text-sm">{{$horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['espacio']}}</div>
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
                                                            <div class="border-1 w-20 min-h-20 grid grid-cols-1 justify-center items-center" wire:key="task-{{ $dia->dias_id }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                                                {{$grupo_deta[$dia->dias_id][$hora->horas_id][$profesor->profesores_id]['grupo_nombre']}}
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
                                                            <div class="border-1 w-20 min-h-20 grid grid-cols-1 justify-center items-center" wire:key="task-{{ $dia->dias_id }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
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
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <table class="table-auto w-full border-collapse" id="horarios-table">
                <thead>
                    <tr>
                        <!-- Columnas de cabecera vacías -->
                        <th class="border p-0 w-60" colspan="{{count($profesores)+1}}"><input type="date" wire:model="ydiario"></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Fila 1 -->
                    <tr>
                        <!-- Columnas de cabecera vacías -->
                        <th class="border p-0 w-40">{{ __('Hours') }}</th>
                        @foreach ( $profesores as $profesor )
                        <th><div style="color:{{$profesor->profesores_color}}" class=" border-1" >{{$profesor->profesores_nombres}}</div></th>
                        @endforeach
                    </tr>
                    @foreach ( $horas as $hora )
                        <tr>
                            <td class="border p-0 text-center align-top"><samp class="text-xs">{{$hora->horas_desde}} - {{$hora->horas_hasta}}</samp></td>
                            @foreach ($profesores as $profesor)
                                @if (isset($horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]))
                                <td class="h-full bg-gray-300 grupo-cell text-center align-middle"
                                    data-id="{{ $horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['id'] }}"
                                    data-dia="{{\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')}}"
                                    data-espacio="{{$horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['espacios_id']}}"
                                    data-hora="{{ $hora->horas_id }}"
                                    data-grupo="{{ $horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['grupo_id'] }}"
                                    data-profesor="{{$profesor->profesores_id}}"
                                >
                                    <div class="w-full h-full flex flex-col items-center justify-center">
                                        <div style="color: {{$horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['color']}};" class="text-sm">
                                            {{$horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['nombre']}}
                                        </div>
                                        <div style="color: {{$horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['color']}};" class="text-sm">
                                            {{$horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['espacio']}}
                                        </div>
                                        <div class="flex items-center justify-center">
                                            <div><i class="fas fa-trash text-red-500 m-2 cursor-pointer" wire:click="$emit('deleteHorario',{{ $horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['id'] }})"></i></div>
                                            <div><i class="fas fa-calendar-check text-green-500 m-2 cursor-pointer" wire:click="editPlan({{ $horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['id'] }})"></i></div>
                                            <div><i class="fas fa-book text-blue-500 m-2 cursor-pointer" wire:click="editDiario({{ $horarios[\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]['id'] }})"></i></div>
                                        </div>
                                    </div>
                                </td>
                                @else
                                    @if(isset($grupo_deta[\Carbon\Carbon::parse($fecha)->isoFormat('d')+1][$hora->horas_id][$profesor->profesores_id]))
                                        <td class="items-center justify-center h-full grupo-cell text-center align-middle"
                                        data-id="0"
                                        data-dia="{{\Carbon\Carbon::parse($fecha)->isoFormat('YYYY-MM-DD')}}"
                                        data-espacio="{{$grupo_deta[\Carbon\Carbon::parse($fecha)->isoFormat('d')+1][$hora->horas_id][$profesor->profesores_id]['espacios_id']}}"
                                        data-hora="{{$hora->horas_id}}"
                                        data-grupo="{{$grupo_deta[\Carbon\Carbon::parse($fecha)->isoFormat('d')+1][$hora->horas_id][$profesor->profesores_id]['grupo_id']}}"
                                        data-profesor="{{ $profesor->profesores_id }}"
                                        >
                                            <div class="border-1 min-h-20 grid grid-cols-1 justify-center items-center" wire:key="task-{{ \Carbon\Carbon::parse($fecha)->isoFormat('d')+1 }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
                                                {{$grupo_deta[\Carbon\Carbon::parse($fecha)->isoFormat('d')+1][$hora->horas_id][$profesor->profesores_id]['grupo_nombre']}}
                                            </div>
                                        </td>
                                    @else
                                        <td class="items-center justify-center h-full grupo-cell text-center align-middle"
                                        data-id="0"
                                        data-dia="{{$fecha}}"
                                        data-espacio="0"
                                        data-hora="{{$hora->horas_id}}"
                                        data-grupo="0"
                                        data-profesor="{{ $profesor->profesores_id }}"
                                        >
                                            <div class="border-1 min-h-20 grid grid-cols-1 justify-center items-center" wire:key="task-{{ \Carbon\Carbon::parse($fecha)->isoFormat('d')+1 }}-{{ $hora->horas_id }}-{{ $profesor->profesores_id }}">
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
    <x-dialog-modal wire:model="open_edit">
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
                    <x-forms.label for="diarios_descripcion" value="{{__('Description')}}: " />
                    <x-forms.textarea id="diarios_descripcion" rows="8" class="flex-1 ml-4" wire:model="diarios_descripcion">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="diarios_descripcion"/>
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

            <div>
                <div class="mb-4 flex">
                    <x-forms.label for="planes_descripcion" value="{{__('Description')}}: " />
                    <x-forms.textarea id="planes_descripcion" rows="8" class="flex-1 ml-4" wire:model="planes_descripcion" id="planes_descripcion">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="planes_descripcion"/>
            </div>

        </x-slot>
        <x-slot name="footer">
            <x-forms.red-button wire:click="$set('open_edit_plan',false)">
                {{__('Cancel')}}
            </x-forms.red-button>
            <x-forms.blue-button wire:click="savePlan"  wire:loading.attr="disabled" wire:click="savePlan" class="disabled:opacity-65">
                {{__('Update')}}
            </x-forms.blue-button>
            {{-- <span wire:loading wire:target="save">Cargando...</span> --}}
        </x-slot>
    </x-dialog-modal>
    @push('css')
        <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.css" />
    @endpush
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
    </script>
    {{-- <script src="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.umd.js"></script> --}}

    <script>
        // const {
        //     ClassicEditor,
        //     Essentials,
        //     Bold,
        //     Italic,
        //     Font,
        //     Paragraph
        // } = CKEDITOR;

        // ClassicEditor
        //     .create( document.querySelector( '#editor' ), {
        //         plugins: [ Essentials, Bold, Italic, Font, Paragraph ],
        //         toolbar: [
        //             'undo', 'redo', '|', 'bold', 'italic', '|',
        //             'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor'
        //         ]
        //     } )
        //     .then( /* ... */ )
        //     .catch( /* ... */ );
    </script>
    <script>
    //     document.addEventListener('DOMContentLoaded', function () {
    //     let table = document.getElementById('horarios-table');
    //     let cells = table.querySelectorAll('.grupo-cell');

    //     cells.forEach(cell => {
    //         cell.setAttribute('draggable', true);
    //         cell.addEventListener('dragstart', function (e) {
    //             e.dataTransfer.setData('text', JSON.stringify({
    //                 id:       cell.dataset.id,
    //                 dia:      cell.dataset.dia,
    //                 hora:     cell.dataset.hora,
    //                 grupo:    cell.dataset.grupo,
    //                 profesor: cell.dataset.profesor,
    //                 espacio: cell.dataset.espacio,
    //             }));
    //         });
    //     });

    //     table.addEventListener('dragover', function (e) {
    //         e.preventDefault();
    //     });

    //     table.addEventListener('drop', function (e) {
    //         e.preventDefault();
    //         let data = JSON.parse(e.dataTransfer.getData('text'));
    //         let targetCell = e.target.closest('.grupo-cell');

    //         if (targetCell) {

    //             let targetId = targetCell.dataset.id;
    //             let targetDia = targetCell.dataset.dia;
    //             let targetEspacio = targetCell.dataset.espacio;
    //             let targetHora = targetCell.dataset.hora;
    //             let targetGrupo = targetCell.dataset.grupo;
    //             let targetProfesor = targetCell.dataset.profesor;
    //             // Evitar cualquier cambio visual directo en las celdas
    //             e.stopPropagation();
    //             // Llamar a Livewire para actualizar el grupo
    //             @this.updateGrupoHorario(targetId, targetDia, targetHora,data.grupo,targetProfesor,data.espacio,data.id);
    //         }
    //     });
    // });
    // document.addEventListener('DOMContentLoaded', function () {
    //     let table = document.getElementById('horarios-table');
    //     let cells = table.querySelectorAll('.grupo-cell');

    //     // Permitir que las celdas sean arrastrables
    //     cells.forEach(cell => {
    //         cell.setAttribute('draggable', true);
    //         cell.addEventListener('dragstart', function (e) {
    //             e.dataTransfer.effectAllowed = 'move'; // Solo se permite mover
    //             e.dataTransfer.setData('text/plain', JSON.stringify({
    //                 id:       cell.dataset.id,
    //                 dia:      cell.dataset.dia,
    //                 hora:     cell.dataset.hora,
    //                 grupo:    cell.dataset.grupo,
    //                 profesor: cell.dataset.profesor,
    //                 espacio:  cell.dataset.espacio,
    //             }));
    //         });
    //     });

    //     // Permitir que la tabla acepte el drop
    //     table.addEventListener('dragover', function (e) {
    //         e.preventDefault();
    //         e.dataTransfer.dropEffect = 'move';
    //     });

    //     table.addEventListener('drop', function (e) {
    //         e.preventDefault();
    //         e.stopPropagation(); // Evitar comportamiento predeterminado

    //         let data = JSON.parse(e.dataTransfer.getData('text/plain'));
    //         let targetCell = e.target.closest('.grupo-cell');

    //         if (targetCell) {
    //             // Obtener datos de la celda destino
    //             let targetId = targetCell.dataset.id;
    //             let targetDia = targetCell.dataset.dia;
    //             let targetHora = targetCell.dataset.hora;
    //             let targetProfesor = targetCell.dataset.profesor;
    //             let targetEspacio = targetCell.dataset.espacio;

    //             // Llamar a Livewire para manejar la actualización
    //             @this.updateGrupoHorario(targetId, targetDia, targetHora,data.grupo,targetProfesor,data.espacio,data.id)

    //              // Opcional: Mostrar visualmente que la celda destino está procesando
    //             targetCell.classList.add('updating');
    //             setTimeout(() => targetCell.classList.remove('updating'), 1000);
    //         }
    //     });
    // });

    // document.addEventListener('DOMContentLoaded', function () {

    //     let table = document.getElementById('horarios-table');
    //     let cells = table.querySelectorAll('.grupo-cell');

    //     // Permitir que las celdas sean arrastrables
    //     cells.forEach(cell => {
    //         cell.setAttribute('draggable', true);
    //         cell.addEventListener('dragstart', function (e) {
    //             e.dataTransfer.effectAllowed = 'move';
    //             e.dataTransfer.setData('text/plain', JSON.stringify({
    //                 id:       cell.dataset.id,
    //                 dia:      cell.dataset.dia,
    //                 hora:     cell.dataset.hora,
    //                 grupo:    cell.dataset.grupo,
    //                 profesor: cell.dataset.profesor,
    //                 espacio:  cell.dataset.espacio,
    //             }));
    //             // Añadir una clase para resaltar la celda de origen
    //             cell.classList.add('dragging');
    //         });

    //         cell.addEventListener('dragend', function () {
    //             // Remover la clase después de finalizar el drag
    //             cell.classList.remove('dragging');
    //         });
    //     });

    //     // Permitir que la tabla acepte el drop
    //     table.addEventListener('dragover', function (e) {
    //         e.preventDefault();
    //         e.dataTransfer.dropEffect = 'move';
    //     });

    //     table.addEventListener('drop', function (e) {
    //         e.preventDefault();
    //         e.stopPropagation();

    //         let data = JSON.parse(e.dataTransfer.getData('text/plain'));
    //         let targetCell = e.target.closest('.grupo-cell');

    //         if (targetCell) {
    //             let targetId = targetCell.dataset.id;
    //             let targetDia = targetCell.dataset.dia;
    //             let targetHora = targetCell.dataset.hora;
    //             let targetProfesor = targetCell.dataset.profesor;
    //             let targetEspacio = targetCell.dataset.espacio;

    //             // Llamar a Livewire para manejar la actualización
    //             console.log('✅ Llamando a Livewire para actualizar el horario');

    //             // Llamar directamente a Livewire desde el frontend
    //             Livewire.find('{{ $this->id }}').call('updateGrupoHorario', targetId, targetDia, targetHora, data.grupo, targetProfesor, data.espacio, data.id);

    //             // Opcional: Mostrar visualmente que la celda destino está procesando
    //             targetCell.classList.add('updating');
    //             setTimeout(() => targetCell.classList.remove('updating'), 1000); // Remover después de 1s
    //         }
    //     });

    //     // Inicializar al cargar la página
    //     window.initializeDragAndDrop();

    //     // Volver a inicializar después de una actualización de Livewire
    //     document.addEventListener('livewire:update', () => {
    //         window.initializeDragAndDrop();
    //     });
    // });

    // Livewire.on('refreshHorario', () => {
    //     console.log('🔄 Refrescando componente Livewire');
    //     Livewire.restart();
    // });

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
