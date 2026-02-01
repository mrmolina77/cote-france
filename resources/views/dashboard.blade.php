<x-app-layout>
    @section('content')
    <p>{{ __('Dashboard') }}</p>
    @endsection
    @php
        $totalInasistentes = $inasistentes->count();
        $totalInasistencias = $inasistentes->sum('total_inasistencias');
        $maxInasistencias = $inasistentes->max('total_inasistencias') ?? 0;
    @endphp
    <div class="max-w-5xl mx-auto mt-10 px-4">
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            @if ($totalInasistentes > 0)
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 flex items-start justify-between">
                    <div>
                        <span class="inline-flex items-center gap-2 text-xs font-semibold text-emerald-700 bg-emerald-100 px-3 py-1 rounded-full">
                            <span class="h-2 w-2 bg-emerald-500 rounded-full"></span>
                            Inasistentes
                        </span>
                        <p class="mt-4 text-sm text-gray-500">Alumnos con 3+ faltas</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-800">{{ $totalInasistentes }}</p>
                    </div>
                    <div class="h-11 w-11 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-500">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372M4.875 19.5A9.38 9.38 0 017.5 19.128m0 0a3 3 0 00-3-3h-.375a2.25 2.25 0 01-2.25-2.25v-.375a3 3 0 013-3h.375a2.25 2.25 0 012.25 2.25v.375a3 3 0 003 3zm0 0h6m-6 0a3 3 0 013-3h.375a2.25 2.25 0 012.25 2.25v.375a3 3 0 003 3h.375a3 3 0 013 3v.375a2.25 2.25 0 01-2.25 2.25h-.375a3 3 0 01-3-3m-6 0a3 3 0 003 3h.375a2.25 2.25 0 002.25-2.25v-.375" />
                        </svg>
                    </div>
                </div>
            @endif
            @if ($totalInasistencias > 0)
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 flex items-start justify-between">
                    <div>
                        <span class="inline-flex items-center gap-2 text-xs font-semibold text-rose-700 bg-rose-100 px-3 py-1 rounded-full">
                            <span class="h-2 w-2 bg-rose-500 rounded-full"></span>
                            Total faltas
                        </span>
                        <p class="mt-4 text-sm text-gray-500">Inasistencias acumuladas</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-800">{{ $totalInasistencias }}</p>
                    </div>
                    <div class="h-11 w-11 rounded-full bg-rose-50 flex items-center justify-center text-rose-500">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-1.5M21 3v1.5M21 21v-1.5M7.5 7.5h9v9h-9v-9z" />
                        </svg>
                    </div>
                </div>
            @endif
            @if ($maxInasistencias > 0)
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 flex items-start justify-between">
                    <div>
                        <span class="inline-flex items-center gap-2 text-xs font-semibold text-amber-700 bg-amber-100 px-3 py-1 rounded-full">
                            <span class="h-2 w-2 bg-amber-500 rounded-full"></span>
                            Máximo
                        </span>
                        <p class="mt-4 text-sm text-gray-500">Mayor faltas en un alumno</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-800">{{ $maxInasistencias }}</p>
                    </div>
                    <div class="h-11 w-11 rounded-full bg-amber-50 flex items-center justify-center text-amber-500">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l3.75 3.75L12 9l4.5 4.5L21 9" />
                        </svg>
                    </div>
                </div>
            @endif
        </div>
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 mt-6">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">Alumnos con 3 o más inasistencias</h2>
                <p class="text-sm text-gray-500">Resumen de alumnos con ausencias acumuladas.</p>
            </div>
            <div class="px-6 py-4">
                @if ($inasistentes->isEmpty())
                    <p class="text-sm text-gray-500">No hay alumnos con 3 o más inasistencias registradas.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-2 text-left font-medium text-gray-600">Alumno</th>
                                    <th scope="col" class="px-4 py-2 text-left font-medium text-gray-600">Grupo</th>
                                    <th scope="col" class="px-4 py-2 text-left font-medium text-gray-600">Inasistencias</th>
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
                                        <td class="px-4 py-2 text-gray-700">
                                            {{ $inasistente->total_inasistencias }}
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
