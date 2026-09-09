<?php

use App\Models\MetodoPago;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('metodos_pago')
            ->where('clave', MetodoPago::INTERMEDIARIO_PAGOS)
            ->whereNull('clave_forma_pago_sat')
            ->where('requiere_forma_pago_sat', true)
            ->update(['clave_forma_pago_sat' => '31', 'requiere_forma_pago_sat' => false]);
    }

    public function down()
    {
        DB::table('metodos_pago')
            ->where('clave', MetodoPago::INTERMEDIARIO_PAGOS)
            ->where('clave_forma_pago_sat', '31')
            ->where('requiere_forma_pago_sat', false)
            ->where('nombre', 'Intermediario de pagos')
            ->where('orden', 100)
            ->where('requiere_proveedor', true)
            ->where('requiere_referencia', true)
            ->update(['clave_forma_pago_sat' => null, 'requiere_forma_pago_sat' => true]);
    }
};
