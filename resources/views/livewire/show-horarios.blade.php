<div>
    @section('content')
    <p>{{ __('Timetable') }}</p>
    @endsection
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
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
                            <td class="border p-4 text-center align-top">
                            @if (isset($horarios[$fecha][$hora->horas_id][$espacio->espacios_id]))
                                <div style="color: {{$horarios[$fecha][$hora->horas_id][$espacio->espacios_id]['color']}};">{{$horarios[$fecha][$hora->horas_id][$espacio->espacios_id]['nombre']}}</div>
                                <div><i class="fas fa-trash text-red-500 mr-4 cursor-pointer" wire:click="$emit('deleteHorario',{{ $horarios[$fecha][$hora->horas_id][$espacio->espacios_id]['id'] }})"></i></div>
                            @else
                                <i class="fas fa-plus text-emerald-500 mr-4 cursor-pointer" wire:click="edit('{{ $fecha }}',{{ $espacio->espacios_id }},{{$hora->horas_id}})"></i>
                            @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    <x-dialog-modal wire:model="open_edit">
        <x-slot name="title">
            Actualizar prospecto
        </x-slot>
        <x-slot name="content">

            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Group')}}: " />
                    <x-select class="flex-1 ml-4" wire:model="grupo_id">
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
    @endpush


</div>
