<div>
    @section('content')
    <p>{{ __('Groups') }}</p>
    @endsection
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <table class="table-auto w-full border-collapse">
            <thead>
                <tr>
                    <!-- Columnas de cabecera vacías -->
                    <th class="border p-2" colspan="{{count($espacios)}}">{{\Carbon\Carbon::parse($fecha)->format('d-m-Y')}}</th>
                </tr>
            </thead>
            <tbody>
                <!-- Fila 1 -->
                <tr>
                    <!-- Columnas de cabecera vacías -->
                    @foreach ( $espacios as $espacio )
                        <th class="border p-2">{{$espacio->espacios_nombre}}</th>
                    @endforeach
                </tr>
                @foreach ( $horas as $hora )
                    <tr>
                        @foreach ( $espacios as $espacio )
                            <td class="border p-4 text-center align-top"><samp class="text-xs">{{$hora->horas_desde}} - {{$hora->horas_hasta}}</samp></td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
