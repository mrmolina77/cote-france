<?php

namespace App\Services\Facturacion;

use App\Models\AuditoriaPerfilFiscal;
use App\Models\PerfilFiscal;
use App\Models\Prospecto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PerfilFiscalService
{
    public function __construct(private AuditoriaPerfilFiscalService $auditoria) {}

    public function guardar(array $datos, int $usuarioId, ?PerfilFiscal $perfil = null): PerfilFiscal
    {
        $datos = $this->normalizar($datos);
        $id = $perfil?->getKey();
        $validados = Validator::make($datos, $this->reglas($datos['tipo_persona'] ?? null, $datos['prospectos_id'] ?? null, $id))->validate();
        return DB::transaction(function () use ($validados, $usuarioId, $perfil) {
            Prospecto::query()->whereKey($validados['prospectos_id'])->lockForUpdate()->firstOrFail();
            $perfil = $perfil?->exists ? PerfilFiscal::query()->lockForUpdate()->findOrFail($perfil->getKey()) : new PerfilFiscal();
            if ($perfil->exists && (int) $perfil->prospectos_id !== (int) $validados['prospectos_id']) abort(404);
            $nuevo = ! $perfil->exists;
            $antes = $perfil->getAttributes();
            if (! $validados['activo']) $validados['predeterminado'] = false;
            if ($validados['predeterminado']) {
                PerfilFiscal::query()->where('prospectos_id', $validados['prospectos_id'])->where('perfil_fiscal_id', '!=', $perfil->getKey() ?: 0)
                    ->where('predeterminado', true)->lockForUpdate()->get()->each(function (PerfilFiscal $anterior) use ($usuarioId) {
                        $anterior->forceFill(['predeterminado' => false, 'updated_by' => $usuarioId])->save();
                        $this->auditoria->registrar($anterior, AuditoriaPerfilFiscal::PREDETERMINAR, $usuarioId, ['predeterminado']);
                    });
            }
            $perfil->fill($validados);
            $perfil->created_by ??= $usuarioId;
            $perfil->updated_by = $usuarioId;
            $perfil->save();
            $cambios = $nuevo ? array_keys($validados) : array_keys(array_filter($perfil->getChanges(),
                fn ($valor, $campo) => ! in_array($campo, ['updated_at', 'updated_by'], true) && ($antes[$campo] ?? null) != $valor,
                ARRAY_FILTER_USE_BOTH));
            $this->auditoria->registrar($perfil, $nuevo ? AuditoriaPerfilFiscal::CREAR : AuditoriaPerfilFiscal::ACTUALIZAR, $usuarioId, $cambios);
            if (! $nuevo && in_array('predeterminado', $cambios, true)) {
                $this->auditoria->registrar($perfil, AuditoriaPerfilFiscal::PREDETERMINAR, $usuarioId, ['predeterminado']);
            }
            if (! $nuevo && in_array('activo', $cambios, true)) {
                $this->auditoria->registrar($perfil, $perfil->activo ? AuditoriaPerfilFiscal::ACTIVAR : AuditoriaPerfilFiscal::DESACTIVAR, $usuarioId, ['activo']);
            }
            return $perfil->fresh();
        });
    }

    public function cambiarEstado(PerfilFiscal $perfil, bool $activo, int $usuarioId): PerfilFiscal
    {
        return DB::transaction(function () use ($perfil, $activo, $usuarioId) {
            Prospecto::query()->whereKey($perfil->prospectos_id)->lockForUpdate()->firstOrFail();
            $perfil = PerfilFiscal::query()->lockForUpdate()->findOrFail($perfil->getKey());
            if ((bool) $perfil->activo === $activo) return $perfil;
            $campos = ['activo'];
            if (! $activo && $perfil->predeterminado) $campos[] = 'predeterminado';
            $perfil->forceFill(['activo' => $activo, 'predeterminado' => $activo ? $perfil->predeterminado : false, 'updated_by' => $usuarioId])->save();
            $this->auditoria->registrar($perfil, $activo ? AuditoriaPerfilFiscal::ACTIVAR : AuditoriaPerfilFiscal::DESACTIVAR, $usuarioId, $campos);
            return $perfil;
        });
    }

    private function normalizar(array $d): array
    {
        foreach (['nombre_razon_social','regimen_fiscal','uso_cfdi','relacion_alumno','nivel_educativo','rvoe'] as $f) $d[$f] = trim((string) ($d[$f] ?? ''));
        $d['rfc'] = strtoupper(trim((string) ($d['rfc'] ?? '')));
        $d['curp'] = strtoupper(trim((string) ($d['curp'] ?? ''))) ?: null;
        $d['codigo_postal_fiscal'] = trim((string) ($d['codigo_postal_fiscal'] ?? ''));
        $d['correo_facturacion'] = mb_strtolower(trim((string) ($d['correo_facturacion'] ?? '')));
        return $d;
    }

    private function reglas($tipo, $alumno, ?int $id): array
    {
        $rfc = $tipo === PerfilFiscal::MORAL ? '/^[A-Z&Ñ]{3}\d{6}[A-Z0-9]{3}$/D' : '/^[A-Z&Ñ]{4}\d{6}[A-Z0-9]{3}$/D';
        return [
            'prospectos_id' => ['required','integer','exists:prospectos,prospectos_id'], 'tipo_persona' => ['required', Rule::in(PerfilFiscal::TIPOS)],
            'rfc' => ['required','string','max:13','regex:'.$rfc, Rule::unique('perfiles_fiscales','rfc')->where(fn ($q) => $q->where('prospectos_id', $alumno))->ignore($id, 'perfil_fiscal_id')],
            'nombre_razon_social' => ['required','string','max:255'], 'codigo_postal_fiscal' => ['required','regex:/^\d{5}$/D'],
            'regimen_fiscal' => ['required','regex:/^[A-Z0-9]{1,10}$/D'], 'uso_cfdi' => ['required','regex:/^[A-Z0-9]{1,10}$/D'],
            'correo_facturacion' => ['required','email:rfc','max:254'], 'relacion_alumno' => ['required','string','max:120'],
            'curp' => ['nullable','regex:/^[A-Z][AEIOU][A-Z]{2}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/D'],
            'nivel_educativo' => ['required','string','max:120'], 'rvoe' => ['required','string','max:120'],
            'predeterminado' => ['required','boolean'], 'activo' => ['required','boolean'], 'fecha_validacion' => ['nullable','date'],
        ];
    }
}
