<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Prospects') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <x-table>
            <table class="items-center bg-transparent w-full border-collapse ">
                <thead>
                <tr>
                <th class="px-2 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
                                Id
                                </th>
                <th class="px-6 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
                                Nombre
                                </th>
                <th class="px-4 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
                                Teléfono
                                </th>
                <th class="px-4 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
                                Origen
                                </th>
                <th class="px-4 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
                                Estatus
                                </th>
                <th class="px-4 bg-blueGray-50 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-left">
                                Acción
                                </th>
                </tr>
                </thead>

                <tbody>
                @forelse ( $prospectos as $item )
                    
                <tr>
                    <th class="border-t-0 px-2 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-left text-blueGray-700 ">
                        {{$item->prospectos_id}}
                    </th>
                    <td class="border-t-0 px-4 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 ">
                        {{$item->prospectos_nombres}} {{$item->prospectos_apellidos}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->prospectos_telefono}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->origen->origenes_descripcion}}
                    </td>
                    <td class="border-t-0 px-4 align-center border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                        {{$item->estatu->estatus_descripcion}}
                    </td>
                    <td class="border-t-0 px-4 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4">
                    <i class="fas fa-pen text-emerald-500 mr-4"></i>
                    <i class="fas fa-trash text-red-500 mr-4"></i>
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
        </x-table>
    </div>

</div>
