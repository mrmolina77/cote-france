<?php

namespace App\Services\Facturacion;

use App\Models\PerfilFiscal;
use App\Models\Prospecto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PerfilFiscalService
{
    public function guardar(array $datos, int $usuarioId, ?PerfilFiscal $perfil = null): PerfilFiscal
    {
        $datos = $this->normalizar($datos);
        $id = $perfil?->getKey();
        $validados = Validator::make($datos, $this->reglas($datos['tipo_persona'] ?? null, $datos['prospectos_id'] ?? null, $id))->validate();
        return DB::transaction(function () use ($validados, $usuarioId, $perfil) {
            Prospecto::query()->whereKey($validados['prospectos_id'])->lockForUpdate()->firstOrFail();
            $perfil ??= new PerfilFiscal();
            if ($perfil->exists && (int) $perfil->prospectos_id !== (int) $validados['prospectos_id']) abort(404);
            if (! $validados['activo']) $validados['predeterminado'] = false;
            if ($validados['predeterminado']) {
                PerfilFiscal::query()->where('prospectos_id', $validados['prospectos_id'])->where('perfil_fiscal_id', '!=', $perfil->getKey() ?: 0)->update(['predeterminado' => false, 'updated_by' => $usuarioId]);
            }
            $perfil->fill($validados);
            $perfil->created_by ??= $usuarioId;
            $perfil->updated_by = $usuarioId;
            $perfil->save();
            return $perfil->fresh();
        });
    }

    public function cambiarEstado(PerfilFiscal $perfil, bool $activo, int $usuarioId): PerfilFiscal
    {
        return DB::transaction(function () use ($perfil, $activo, $usuarioId) {
            Prospecto::query()->whereKey($perfil->prospectos_id)->lockForUpdate()->firstOrFail();
            $perfil->forceFill(['activo' => $activo, 'predeterminado' => $activo ? $perfil->predeterminado : false, 'updated_by' => $usuarioId])->save();
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
