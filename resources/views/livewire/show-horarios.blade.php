<div @class([
    'scale-100 -translate-x-0 -translate-y-0'  => $porcentaje === '0',
    'scale-95 -translate-x-10 -translate-y-10' => $porcentaje === '1',
    'scale-90 -translate-x-20 -translate-y-20' => $porcentaje === '2',
    'scale-75 -translate-x-40 -translate-y-40' => $porcentaje === '3',
    'scale-50 -translate-x-80 -translate-y-80' => $porcentaje === '4'
    ]) >
    @section('content')
    <p>{{ __('Timetable') }}</p>
    @endsection
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        @if ($semanal)
            <table class="table-auto w-full border-collapse">
                <thead>
                    <tr>
                        <!-- Columnas de cabecera vacías -->
                        <th class="border p-2 w-36">
                            <x-select class="flex-1 ml-4" wire:model="porcentaje">
                                @forelse ($porcentajes as $key => $item)
                                <option value="{{$key}}">{{$item}}</option>
                                @empty
                                <option value="">{{__('No Content')}}</option>
                                @endforelse
                            </x-select>
                        </th>
                        <th class="border p-2 w-60" colspan="7">Semana {{$semana}}</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Fila 1 -->
                    <tr>
                        <!-- Columnas de cabecera vacías -->
                        <th class="border p-2 w-40">{{ __('Hours') }}</th>
                        @foreach ( $dias as $dia )
                            <th class="border p-[17px]">{{$dia->dias_nombre}} {{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('DD')}}
                                <table>
                                    <tr>
                                        @foreach ($profesores as $profesor)
                                        <td><div style="color:{{$profesor->profesores_color}}" class="w-20 border-2" >{{$profesor->profesores_nombres}}</div></td>
                                        @endforeach
                                    </tr>
                                </table>
                            </th>
                        @endforeach
                    </tr>
                    @foreach ( $horas as $hora )
                        <tr>
                            <td class="border p-4 text-center align-top"><samp class="text-xs">{{$hora->horas_desde}} - {{$hora->horas_hasta}}</samp></td>
                            @foreach ( $dias as $dia )
                                <td class="border p-4 text-center">
                                    <table>
                                        <tr>
                                            @foreach ($profesores as $profesor)
                                                @if (isset($horarios[\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')][$hora->horas_id][$profesor->profesores_id]))
                                                    <td class="items-center justify-center h-full bg-gray-300">
                                                        <div class="border-2 w-20 min-h-20 grid grid-cols-1">
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
                                                    <td class="items-center justify-center h-full">
                                                        <div class="border-2 w-20 min-h-20 grid grid-cols-1">
                                                            @if(isset($grupo_deta[$dia->dias_id][$hora->horas_id][$profesor->profesores_id]))
                                                                {{$grupo_deta[$dia->dias_id][$hora->horas_id][$profesor->profesores_id]['grupo_nombre']}}
                                                                <i class="fas fa-plus text-emerald-500 mr-4 cursor-pointer" wire:click="edit('{{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')}}',{{ $profesor->profesores_id }},{{$hora->horas_id}},{{$profesor->profesores_id}})"></i>
                                                            @else
                                                                <i class="fas fa-plus text-emerald-500 mr-4 cursor-pointer" wire:click="edit('{{\Carbon\Carbon::parse($fecha)->setISODate($year, $semana, $dia->dias_id)->isoFormat('YYYY-MM-DD')}}',{{ $profesor->profesores_id }},{{$hora->horas_id}},{{$profesor->profesores_id}})"></i>
                                                            @endif
                                                        </div>
                                                    </td>
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
            <table class="table-auto w-full border-collapse">
                <thead>
                    <tr>
                        <!-- Columnas de cabecera vacías -->
                        <th class="border p-2 w-60" colspan="{{count($espacios)+1}}"><input type="date" wire:model="fecha"></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Fila 1 -->
                    <tr>
                        <!-- Columnas de cabecera vacías -->
                        <th class="border p-2 w-40">{{ __('Hours') }}</th>
                        @foreach ( $espacios as $espacio )
                            <th class="border p-2">{{$espacio->espacios_nombre}}</th>
                        @endforeach
                    </tr>
                    @foreach ( $horas as $hora )
                        <tr>
                            <td class="border p-4 text-center align-top"><samp class="text-xs">{{$hora->horas_desde}} - {{$hora->horas_hasta}}</samp></td>
                            @foreach ( $espacios as $espacio )
                                <td class="border p-4 text-center align-top ">
                                @if (isset($horarios[$fecha][$hora->horas_id][$espacio->espacios_id]))
                                    <div style="color: {{$horarios[$fecha][$hora->horas_id][$espacio->espacios_id]['color']}};">{{$horarios[$fecha][$hora->horas_id][$espacio->espacios_id]['nombre']}}</div>
                                    <div class="flex justify-center">
                                        <div><i class="fas fa-trash text-red-500 m-2 cursor-pointer" wire:click="$emit('deleteHorario',{{ $horarios[$fecha][$hora->horas_id][$espacio->espacios_id]['id'] }})"></i></div>
                                        <div><i class="fas fa-calendar-check text-green-500 m-2 cursor-pointer" wire:click="editPlan({{ $horarios[$fecha][$hora->horas_id][$espacio->espacios_id]['id'] }})"></i></div>
                                        <div><i class="fas fa-book text-blue-500 m-2 cursor-pointer" wire:click="editDiario({{ $horarios[$fecha][$hora->horas_id][$espacio->espacios_id]['id'] }})"></i></div>
                                    </div>
                                @else
                                    <i class="fas fa-plus text-emerald-500 mr-4 cursor-pointer" wire:click="edit('{{ $fecha }}',{{ $espacio->espacios_id }},{{$hora->horas_id}})"></i>
                                @endif
                                </td>
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
            @if (isset($grupo_id) and $grupo_id=='')
                <div>
                    <div class="mb-4 flex">
                        <x-forms.label value="{{__('Group')}}: " />
                        <x-select class="flex-1 ml-4" wire:model.defer="grupo_id">
                            <option value="">{{__('Select')}}</option>
                            @forelse ($grupos as $item)
                            <option value="{{$item->grupo_id}}">{{$item->grupo_nombre}}</option>
                            @empty
                            <option value="">{{__('No Content')}}</option>
                            @endforelse
                        </x-select>
                    </div>
                    <x-forms.input-error for="grupo_id"/>
                </div>
            @else
                <div>
                    <x-forms.input type="hidden" wire:model="grupo_id"/>
                </div>
            @endif
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Spaces')}}: " />
                    <x-select class="flex-1 ml-4" wire:model="espacios_id">
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
                    <x-forms.label value="{{__('Description')}}: " />
                    <x-forms.textarea rows="8" class="flex-1 ml-4" wire:model="diarios_descripcion">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="planes_descripcion"/>
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
                    <x-forms.label value="{{__('Description')}}: " />
                    <x-forms.textarea id="planes_descripcion" rows="8" class="flex-1 ml-4" wire:model="planes_descripcion">
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
    <script src="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.umd.js"></script>

    <script>
        const {
            ClassicEditor,
            Essentials,
            Bold,
            Italic,
            Font,
            Paragraph
        } = CKEDITOR;

        ClassicEditor
            .create( document.querySelector( '#editor' ), {
                plugins: [ Essentials, Bold, Italic, Font, Paragraph ],
                toolbar: [
                    'undo', 'redo', '|', 'bold', 'italic', '|',
                    'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor'
                ]
            } )
            .then( /* ... */ )
            .catch( /* ... */ );
    </script>
    @endpush


</div>
