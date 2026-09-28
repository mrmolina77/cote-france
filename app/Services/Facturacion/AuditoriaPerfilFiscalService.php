<?php

namespace App\Services\Facturacion;

use App\Models\AuditoriaPerfilFiscal;
use App\Models\PerfilFiscal;
use InvalidArgumentException;

class AuditoriaPerfilFiscalService
{
    public function registrar(PerfilFiscal $perfil, string $accion, int $usuarioId, array $campos): AuditoriaPerfilFiscal
    {
        if (! in_array($accion, AuditoriaPerfilFiscal::ACCIONES, true)) {
            throw new InvalidArgumentException('Acción de auditoría de perfil fiscal no válida.');
        }

        $campos = array_values(array_unique(array_filter($campos, fn ($campo) => is_string($campo) && $campo !== '')));
        sort($campos);
        $evento = new AuditoriaPerfilFiscal();
        $evento->forceFill([
            'perfil_fiscal_id' => $perfil->getKey(),
            'prospectos_id' => $perfil->prospectos_id,
            'usuario_id' => $usuarioId,
            'accion' => $accion,
            'campos_modificados' => $campos,
            'ocurrido_en' => now(),
        ])->save();

        return $evento;
    }
}
