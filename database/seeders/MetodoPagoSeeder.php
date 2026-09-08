<?php

namespace Database\Seeders;

use App\Models\MetodoPago;
use Illuminate\Database\Seeder;

class MetodoPagoSeeder extends Seeder
{
    public function run()
    {
        $metodos = [
            [MetodoPago::EFECTIVO, 'Efectivo', '01', 10, []],
            [MetodoPago::CHEQUE_NOMINATIVO, 'Cheque nominativo', '02', 20, ['requiere_banco', 'requiere_numero_cheque', 'requiere_comprobante']],
            [MetodoPago::TRANSFERENCIA_SPEI, 'Transferencia SPEI', '03', 30, ['requiere_banco', 'requiere_referencia', 'requiere_rastreo_spei', 'requiere_comprobante']],
            [MetodoPago::TARJETA_CREDITO, 'Tarjeta de crédito', '04', 40, ['requiere_autorizacion', 'requiere_terminal', 'requiere_ultimos_4_digitos']],
            [MetodoPago::MONEDERO_ELECTRONICO, 'Monedero electrónico', '05', 50, ['requiere_proveedor', 'requiere_referencia']],
            [MetodoPago::DINERO_ELECTRONICO, 'Dinero electrónico', '06', 60, ['requiere_proveedor', 'requiere_referencia']],
            [MetodoPago::TARJETA_DEBITO, 'Tarjeta de débito', '28', 70, ['requiere_autorizacion', 'requiere_terminal', 'requiere_ultimos_4_digitos']],
            [MetodoPago::TARJETA_SERVICIOS, 'Tarjeta de servicios', '29', 80, ['requiere_autorizacion']],
            [MetodoPago::APLICACION_ANTICIPO, 'Aplicación de anticipo', '30', 90, ['requiere_anticipo_relacionado']],
            [MetodoPago::INTERMEDIARIO_PAGOS, 'Intermediario de pagos', null, 100, ['requiere_proveedor', 'requiere_referencia', 'requiere_forma_pago_sat']],
            [MetodoPago::POR_DEFINIR, 'Por definir', '99', 110, []],
            [MetodoPago::DEPOSITO_BANCARIO, 'Depósito bancario', null, 120, ['requiere_banco', 'requiere_referencia', 'requiere_comprobante', 'requiere_forma_pago_sat']],
        ];

        foreach ($metodos as [$clave, $nombre, $claveSat, $orden, $indicadores]) {
            $atributos = [
                'nombre' => $nombre,
                'descripcion' => null,
                'clave_forma_pago_sat' => $claveSat,
                'orden' => $orden,
                'activo' => true,
            ];

            foreach ($indicadores as $indicador) {
                $atributos[$indicador] = true;
            }

            MetodoPago::firstOrCreate(['clave' => $clave], $atributos);
        }
    }
}
