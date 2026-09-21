<div>
<p>{{ $config['institucion'] ?: 'Sin configurar' }}</p>
<p>Razón social: {{ $config['razon_social'] ?: 'Sin configurar' }}</p>
<p>RFC: {{ $config['rfc'] ?: 'Sin configurar' }}</p>
@if($config['domicilio'])<p>Domicilio: {{ $config['domicilio'] }}</p>@endif
@if($config['telefono'])<p>Teléfono: {{ $config['telefono'] }}</p>@endif
@if($config['correo'])<p>Correo: {{ $config['correo'] }}</p>@endif
<p>RECIBO INTERNO {{ $comprobante->folio }}</p>
<p>Generado: {{ $comprobante->generado_en->timezone(config('app.timezone'))->format('Y-m-d H:i:s T') }}</p>
<p>Alumno: {{ trim(($pago->prospecto->prospectos_nombres ?? '').' '.($pago->prospecto->prospectos_apellidos ?? '')) ?: 'Sin configurar' }}</p>
<p>Responsable de pago: {{ $pago->responsablePago->nombre_razon_social ?? 'Sin configurar' }}</p>
<p>Inscripción: #{{ $pago->inscripciones_id }}</p>
<p>Curso: {{ $pago->inscripcion->cursos->cursos_descripcion ?? 'Sin configurar' }} · Grupo: {{ $pago->inscripcion->grupo->grupo_nombre ?? 'Sin configurar' }}</p>
@foreach($pago->aplicaciones as $aplicacion)
<p>Concepto: {{ $aplicacion->cargo->conceptoCobro->nombre ?? 'Sin concepto' }}@if($aplicacion->cargo->periodo_anio && $aplicacion->cargo->periodo_mes) · Periodo {{ sprintf('%04d-%02d', $aplicacion->cargo->periodo_anio, $aplicacion->cargo->periodo_mes) }}@endif · Aplicado {{ $pago->moneda }} ${{ number_format($aplicacion->importe_aplicado, 2, '.', ',') }}</p>
@endforeach
<p>Importe recibido: {{ $pago->moneda }} ${{ number_format($pago->monto, 2, '.', ',') }}</p>
<p>Importe en letras: {{ $importeLetras }}</p>
<p>Método de pago: {{ $pago->metodoPago->nombre ?? 'Sin configurar' }}</p>
@foreach(['banco'=>'Banco','referencia'=>'Referencia','rastreo_spei'=>'Rastreo SPEI','numero_cheque'=>'Cheque','numero_autorizacion'=>'Autorización'] as $campo=>$etiqueta)@if($pago->{$campo})<p>{{ $etiqueta }}: {{ $pago->{$campo} }}</p>@endif @endforeach
<p>Recibió/confirmó: {{ $pago->confirmedBy->name ?? 'Sin configurar' }}</p>
@if($pago->observaciones)<p>Observaciones: {{ $pago->observaciones }}</p>@endif
<p>Código QR interno: {{ $urlQr }}</p>
<p>Hash verificable: {{ $hashPresentacion }}</p>
<p>{{ $config['aviso_privacidad'] }}</p>
<p>Comprobante interno de pago. Este documento no constituye un CFDI.</p>
</div>
