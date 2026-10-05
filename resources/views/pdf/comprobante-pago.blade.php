<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 30px 40px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 13px; color: #333; line-height: 1.4; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 20px; text-transform: uppercase; letter-spacing: 1px; }
        .header p { margin: 2px 0; font-size: 11px; color: #666; }
        .title { text-align: center; font-size: 16px; font-weight: bold; margin: 15px 0; background: #eee; padding: 5px; border-radius: 4px;}
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th, table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background-color: #f8f9fa; font-weight: bold; font-size: 12px; }
        .totals { text-align: right; margin-top: 20px; }
        .totals p { margin: 5px 0; font-size: 14px; }
        .totals .grand-total { font-size: 18px; font-weight: bold; }
        .footer { position: fixed; bottom: -10px; left: 0; right: 0; font-size: 10px; color: #888; border-top: 1px solid #eee; padding-top: 10px; }
        .qr-container { text-align: center; margin-top: 30px; }
        .qr-code { width: 120px; height: 120px; margin-bottom: 5px; }
        .qr-text { font-size: 9px; color: #666; word-wrap: break-word; }
        .box { border: 1px solid #ddd; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .box-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #555; margin-bottom: 5px; border-bottom: 1px solid #eee; padding-bottom: 3px;}
        .info-grid { width: 100%; }
        .info-grid td { border: none; padding: 2px 5px; vertical-align: top;}
        .label { font-weight: bold; font-size: 11px; color: #555;}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>{{ $config['institucion'] ?: 'Sin configurar' }}</h1>
        <p>Razón social: {{ $config['razon_social'] ?: 'Sin configurar' }} | RFC: {{ $config['rfc'] ?: 'Sin configurar' }}</p>
        <p>
            @if($config['domicilio']){{ $config['domicilio'] }}@endif
            @if($config['telefono']) | Tel: {{ $config['telefono'] }}@endif
            @if($config['correo']) | Correo: {{ $config['correo'] }}@endif
        </p>
    </div>

    <div class="title">RECIBO INTERNO {{ $comprobante->folio }}</div>
    <p style="text-align: right; font-size: 11px; margin-top: -10px;">
        Generado: {{ $comprobante->generado_en->timezone(config('app.timezone'))->format('Y-m-d H:i:s T') }}
    </p>

    <div class="box">
        <div class="box-title">Detalles del Alumno</div>
        <table class="info-grid">
            <tr>
                <td class="label" width="15%">Alumno:</td>
                <td width="35%">{{ trim(($pago->prospecto->prospectos_nombres ?? '').' '.($pago->prospecto->prospectos_apellidos ?? '')) ?: 'Sin configurar' }}</td>
                <td class="label" width="20%">Responsable:</td>
                <td width="30%">{{ $pago->responsablePago->nombre_razon_social ?? 'Sin configurar' }}</td>
            </tr>
            <tr>
                <td class="label">Inscripción:</td>
                <td>#{{ $pago->inscripciones_id }}</td>
                <td class="label">Curso/Grupo:</td>
                <td>{{ $pago->inscripcion->cursos->cursos_descripcion ?? 'Sin configurar' }} · {{ $pago->inscripcion->grupo->grupo_nombre ?? 'Sin configurar' }}</td>
            </tr>
        </table>
    </div>

    <div class="box">
        <div class="box-title">Conceptos Aplicados</div>
        <table>
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Periodo</th>
                    <th style="text-align: right;">Importe Aplicado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pago->aplicaciones as $aplicacion)
                <tr>
                    <td>{{ $aplicacion->cargo->conceptoCobro->nombre ?? 'Sin concepto' }}</td>
                    <td>@if($aplicacion->cargo->periodo_anio && $aplicacion->cargo->periodo_mes){{ sprintf('%04d-%02d', $aplicacion->cargo->periodo_anio, $aplicacion->cargo->periodo_mes) }}@else N/A @endif</td>
                    <td style="text-align: right;">{{ $pago->moneda }} ${{ number_format($aplicacion->importe_aplicado, 2, '.', ',') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="totals">
            <p><strong>Total Importe Recibido:</strong> <span class="grand-total">{{ $pago->moneda }} ${{ number_format($pago->monto, 2, '.', ',') }}</span></p>
            <p style="font-size: 11px; font-style: italic;">({{ $importeLetras }})</p>
        </div>
    </div>

    <div class="box">
        <div class="box-title">Información del Pago</div>
        <table class="info-grid">
            <tr>
                <td class="label" width="15%">Método:</td>
                <td width="35%">{{ $pago->metodoPago->nombre ?? 'Sin configurar' }}</td>
                <td class="label" width="15%">Recibió:</td>
                <td width="35%">{{ $pago->confirmedBy->name ?? 'Sin configurar' }}</td>
            </tr>
            @foreach(['banco'=>'Banco','referencia'=>'Referencia','rastreo_spei'=>'Rastreo SPEI','numero_cheque'=>'Cheque','numero_autorizacion'=>'Autorización'] as $campo=>$etiqueta)
                @if($pago->{$campo})
                <tr>
                    <td class="label">{{ $etiqueta }}:</td>
                    <td colspan="3">{{ $pago->{$campo} }}</td>
                </tr>
                @endif
            @endforeach
            @if($pago->observaciones)
            <tr>
                <td class="label">Observaciones:</td>
                <td colspan="3">{{ $pago->observaciones }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="qr-container">
        <img class="qr-code" src="{{ $qrBase64 }}" alt="QR Code">
        <p class="qr-text">Hash verificable: {{ $hashPresentacion }}</p>
    </div>

    <div class="footer">
        <p>Comprobante interno de pago. Este documento no constituye un CFDI.</p>
        <p>{{ $config['aviso_privacidad'] }}</p>
    </div>
</div>
</body>
</html>
