<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\PagoAplicacion;
use App\Models\ResponsablePago;
use App\Models\User;

abstract class ComprobantePagoTestCase extends InscripcionesTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @return array{pago:Pago,cargos:array<int,Cargo>} */
    protected function pagoConfirmado(User $usuario, string $monto = '15.00'): array
    {
        [$prospecto, $curso, $grupo] = $this->catalogs();
        $prospecto->update(['prospectos_nombres' => 'Élodie', 'prospectos_apellidos' => 'Martin']);
        $responsable = ResponsablePago::create([
            'tipo' => 'persona', 'prospectos_id' => $prospecto->getKey(),
            'nombre_razon_social' => 'Responsable Prueba', 'activo' => true,
        ]);
        $inscripcion = $this->enroll($prospecto, $curso, $grupo);
        $inscripcion->update(['estatus' => 'activa', 'moneda' => 'MXN', 'responsable_pago_id' => $responsable->getKey()]);
        $cargos = [];
        foreach ([['10.00', 1], ['5.00', 2]] as [$importe, $concepto]) {
            $cargos[] = Cargo::create([
                'inscripciones_id' => $inscripcion->getKey(), 'concepto_cobro_id' => $concepto,
                'fecha_emision' => '2026-09-01', 'fecha_vencimiento' => '2026-09-30',
                'moneda' => 'MXN', 'subtotal' => $importe, 'total' => $importe,
                'saldo_pendiente' => '0.00', 'estado' => Cargo::ESTADO_PAGADO,
                'origen' => Cargo::ORIGEN_MANUAL,
            ]);
        }
        $pago = Pago::create([
            'folio' => uniqid('PAG-2026-'), 'inscripciones_id' => $inscripcion->getKey(),
            'prospectos_id' => $prospecto->getKey(), 'responsable_pago_id' => $responsable->getKey(),
            'fecha_pago' => '2026-09-21 12:00:00', 'zona_horaria' => 'UTC', 'monto' => $monto,
            'moneda' => 'MXN', 'metodo_pago_id' => MetodoPago::query()->firstOrFail()->getKey(),
        ]);
        $pago->forceFill(['estado' => Pago::ESTADO_CONFIRMADO, 'confirmed_by' => $usuario->getKey(), 'fecha_confirmacion' => now()])->save();
        foreach ($cargos as $cargo) {
            (new PagoAplicacion())->forceFill([
                'pago_id' => $pago->getKey(), 'cargo_id' => $cargo->getKey(),
                'importe_aplicado' => $cargo->total, 'saldo_anterior' => $cargo->total, 'saldo_posterior' => '0.00',
            ])->save();
        }

        return ['pago' => $pago->fresh(), 'cargos' => $cargos];
    }
}
