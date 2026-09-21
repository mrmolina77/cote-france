<?php

return [
    'institucion' => env('RECIBO_INSTITUCION', config('app.name')),
    'razon_social' => env('RECIBO_RAZON_SOCIAL', ''),
    'rfc' => env('RECIBO_RFC', ''),
    'domicilio' => env('RECIBO_DOMICILIO', ''),
    'telefono' => env('RECIBO_TELEFONO', ''),
    'correo' => env('RECIBO_CORREO', ''),
    'aviso_privacidad' => env('RECIBO_AVISO_PRIVACIDAD', 'Consulte el aviso de privacidad institucional vigente.'),
];
