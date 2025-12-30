<x-app-layout>
    @section('content')
    <p>{{ __('Dashboard') }}</p>
    @endsection
    <div class="max-w-5xl mx-auto mt-10 px-4">
        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">Inasistencias en las últimas 3 clases</h2>
                <p class="text-sm text-gray-500">Basado en los últimos horarios actualizados.</p>
            </div>
            <div class="px-6 py-4">
                @if ($inasistentes->isEmpty())
                    <p class="text-sm text-gray-500">No hay inasistencias registradas en los últimos horarios.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-2 text-left font-medium text-gray-600">Alumno</th>
                                    <th scope="col" class="px-4 py-2 text-left font-medium text-gray-600">Grupo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($inasistentes as $inasistente)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-700">
                                            {{ $inasistente->prospectos_nombres }} {{ $inasistente->prospectos_apellidos }}
                                        </td>
                                        <td class="px-4 py-2 text-gray-700">
                                            {{ $inasistente->grupo_nombre }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="flex mt-20 justify-center items-center">
        <img src="{{asset("images/cote_logo.png")}}" alt="" srcset="">
    </div>
    <footer class="relative pt-8 pb-6 mt-16">
        <div class="container mx-auto px-4">
            <div class="flex flex-wrap items-center md:justify-between justify-center">
                <div class="w-full md:w-6/12 px-4 mx-auto text-center">
                    <div class="text-sm text-blueGray-500 font-semibold py-1">
                        Movida TCI - Programa de Gestión PDG - Web site Propiedad de <a href="https://www.cotefrance.mx/" class="text-blueGray-500 hover:text-gray-800" target="_blank">https://www.cotefrance.mx/</a> Todos los Derechos Reservados. Copyright © 2024
                    </div>
                </div>
            </div>
        </div>
    </footer>
</x-app-layout>
