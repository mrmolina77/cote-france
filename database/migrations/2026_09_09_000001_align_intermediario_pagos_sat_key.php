<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $this->intermediarioCanonico(null, true)
            ->update(['clave_forma_pago_sat' => '31', 'requiere_forma_pago_sat' => false]);
    }

    public function down()
    {
        $this->intermediarioCanonico('31', false)
            ->update(['clave_forma_pago_sat' => null, 'requiere_forma_pago_sat' => true]);
    }

    /** Consulta la configuración canónica, variando exclusivamente los dos atributos SAT. */
    private function intermediarioCanonico(?string $claveSat, bool $requiereFormaPagoSat)
    {
        $query = DB::table('metodos_pago')
            ->where('clave', 'INTERMEDIARIO_PAGOS')
            ->where('nombre', 'Intermediario de pagos')
            ->whereNull('descripcion')
            ->where('requiere_forma_pago_sat', $requiereFormaPagoSat)
            ->where('requiere_banco', false)
            ->where('requiere_referencia', true)
            ->where('requiere_numero_cheque', false)
            ->where('requiere_rastreo_spei', false)
            ->where('requiere_autorizacion', false)
            ->where('requiere_terminal', false)
            ->where('requiere_ultimos_4_digitos', false)
            ->where('requiere_proveedor', true)
            ->where('requiere_anticipo_relacionado', false)
            ->where('requiere_comprobante', false)
            ->where('activo', true)
            ->where('orden', 100);

        return $claveSat === null
            ? $query->whereNull('clave_forma_pago_sat')
            : $query->where('clave_forma_pago_sat', $claveSat);
    }
};
