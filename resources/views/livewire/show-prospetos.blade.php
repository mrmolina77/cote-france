<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <x-table>
            <x-slot:header>
                <div class="flex flex-wrap items-center">
                    <div class="flex items-center">
                        <span>Mostrar</span>
                        <x-select class="mx-2" wire:model="cant">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </x-select>
                        <span>entradas</span>
                    </div>
                    <div class="relative w-full px-4 max-w-full flex-grow flex-1">
                        <div class="px-6 py-4">
                            <x-forms.input type="text" placeholder="Buscar..." class="flex-1 ml-4" wire:model="search"/>
                        </div>
                    </div>
                    <div class="relative w-full px-4 max-w-full flex-grow flex-1 text-right">
                    @livewire('create-prospect')
                    </div>
                </div>
            </x-slot>
            <table class="items-center bg-transparent w-full border-collapse">
                <thead>
                <tr>
                <th class="cursor-pointer px-2 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left"
                    wire:click="order('prospectos_id')">
                    Id
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
                <th class="cursor-pointer px-6 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left"
                    wire:click="order('prospectos_nombres')">
                    Nombre
                    @if ($sort == 'prospectos_nombres')
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
                    wire:click="order('origenes_id')">
                    Origen
                    @if ($sort == 'origenes_id')
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
                    wire:click="order('estatus_id')">
                    Estatus
                    @if ($sort == 'estatus_id')
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
                    Teléfono
                    </th>
                <th class="px-4 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
                    Acción
                    </th>
                </tr>
                </thead>

                <tbody style="max-height: 10px;">
                @forelse ( $prospectos as $item )

                <tr>
                    <th class="border-t-0 px-2 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-left text-blueGray-700 ">
                        {{$item->prospectos_id}}
                    </th>
                    <td class="border-t-0 px-4 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 ">
                        {{$item->prospectos_nombres}} {{$item->prospectos_apellidos}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->origenes_id}} {{$item->origen->origenes_descripcion}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->estatus_id}} {{$item->estatu->estatus_descripcion}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->prospectos_telefono}}
                    </td>
                    <td class="flex border-t-0 px-4 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        <i class="fas fa-pen text-emerald-500 mr-4" wire:click="edit({{ $item }})"></i>
                        <i class="fas fa-trash text-red-500 mr-4" wire:click="$emit('deleteProspecto',{{$item->prospectos_id}})"></i>
                    </td>
                </tr>
                @empty
                <tr>
                    <th colspan="5" class="border-t-0 px-2 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-left text-blueGray-700 ">
                    No hay propectos cargados
                    </th>
                </tr>
                @endforelse

                </tbody>

            </table>
            @if ($prospectos->hasPages())
                <div class="px-6 py-3">
                    {{$prospectos->links()}}
                </div>
            @endif
        </x-table>
    </div>

    <x-dialog-modal wire:model="open_edit">
        <x-slot name="title">
            Actualizar prospecto
        </x-slot>
        <x-slot name="content">
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Nombres: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="prospecto.prospectos_nombres"/>
                </div>
                <x-forms.input-error for="prospectos_nombres"/>
           </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Apellidos: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="prospecto.prospectos_apellidos"/>
                </div>
                <x-forms.input-error for="prospectos_apellidos"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Telefono: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="prospecto.prospectos_telefono"/>
                </div>
                <x-forms.input-error for="prospectos_telefono"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Correo: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="prospecto.prospectos_correo"/>
                </div>
                <x-forms.input-error for="prospectos_correo"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Origenes: " />
                    <x-select class="flex-1 ml-4" wire:model="prospecto.origenes_id">
                        <option value="">Seleccionar</option>
                        @forelse ($origenes as $item)
                        <option value="{{$item->origenes_id}}">{{$item->origenes_descripcion}}</option>
                        @empty
                        <option value="">Sin origenes</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="origenes_id"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Seguimientos: " />
                    <x-select class="flex-1 ml-4" wire:model="prospecto.seguimientos_id">
                        <option value="">Seleccionar</option>
                        @forelse ($seguimientos as $item)
                        <option value="{{$item->seguimientos_id}}">{{$item->seguimientos_descripcion}}</option>
                        @empty
                        <option value="">Sin segumientos</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="seguimientos_id"/>
            </div>
            <div>
                <div class="mb-4 flex" >
                    <x-forms.label value="Origenes: " />
                    <x-select class="flex-1 ml-4" wire:model="prospecto.estatus_id">
                        <option value="">Seleccionar</option>
                        @forelse ($estatus as $item)
                        <option value="{{$item->estatus_id}}">{{$item->estatus_descripcion}}</option>
                        @empty
                        <option value="">Sin origenes</option>
                        @endforelse
                    </x-select>
                </div>
                <x-forms.input-error for="estatus_id"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Comentario: " />
                    <x-forms.textarea rows="4" class="flex-1 ml-4" wire:model="prospecto.prospectos_comentarios">
                    </x-forms.textarea>
                </div>
                <x-forms.input-error for="prospectos_comentarios"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="Fecha: " />
                    <x-forms.input type="date" class="flex-1 ml-4" wire:model="prospecto.prospectos_fecha"/>
                </div>
                <x-forms.input-error for="prospectos_fecha"/>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-forms.red-button wire:click="$set('open_edit',false)">
                Cancelar
            </x-forms.red-button>
            <x-forms.blue-button wire:click="save"  wire:loading.attr="disabled" wire:click="update" class="disabled:opacity-65">
                Modificar Prospecto
            </x-forms.blue-button>
            {{-- <span wire:loading wire:target="save">Cargando...</span> --}}
        </x-slot>
    </x-dialog-modal>

    @push('js');
    <script>
        livewire.on('deleteProspecto',itemId=>{
            Swal.fire({
            title: "¿Estas seguro?",
            text: "¡No podrás revertir esto!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "¡Sí, bórralo!"
            }).then((result) => {
            if (result.isConfirmed) {
                livewire.emitTo('show-prospectos','delete',itemId);
            }
            });
        })
    </script>
    @endpush
</div>
