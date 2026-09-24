<?php

return [
    // No se infieren monedas ni se convierten importes. Amplíe esta lista si la operación acepta otras monedas.
    'monedas_permitidas' => array_values(array_filter(array_map('trim', explode(',', env('FACTURACION_MONEDAS', 'MXN'))))),
];
