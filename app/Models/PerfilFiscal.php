<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class PerfilFiscal extends Model
{
    public const FISICA = 'fisica';
    public const MORAL = 'moral';
    public const TIPOS = [self::FISICA, self::MORAL];

    protected $table = 'perfiles_fiscales';
    protected $primaryKey = 'perfil_fiscal_id';
    protected $guarded = ['perfil_fiscal_id', 'created_by', 'updated_by'];
    protected $casts = ['activo' => 'boolean', 'predeterminado' => 'boolean', 'fecha_validacion' => 'date'];

    protected static function booted(): void
    {
        static::saving(function (PerfilFiscal $perfil): void {
            $perfil->rfc = strtoupper(trim((string) $perfil->rfc));
            $perfil->correo_facturacion = mb_strtolower(trim((string) $perfil->correo_facturacion));
            $perfil->codigo_postal_fiscal = trim((string) $perfil->codigo_postal_fiscal);
            $perfil->curp = ($curp = strtoupper(trim((string) $perfil->curp))) === '' ? null : $curp;
            if (! $perfil->activo && $perfil->predeterminado) {
                throw new LogicException('Un perfil inactivo no puede ser predeterminado.');
            }
        });
        static::deleting(function (PerfilFiscal $perfil): void {
            if ($perfil->pagos()->exists()) throw new LogicException('Un perfil fiscal usado por pagos no puede eliminarse; desactívalo.');
        });
    }

    public function prospecto() { return $this->belongsTo(Prospecto::class, 'prospectos_id', 'prospectos_id'); }
    public function pagos() { return $this->hasMany(Pago::class, 'perfil_fiscal_id', 'perfil_fiscal_id'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy() { return $this->belongsTo(User::class, 'updated_by'); }

    public function snapshot(): array
    {
        return ['version' => 1] + $this->only(['tipo_persona', 'rfc', 'nombre_razon_social', 'codigo_postal_fiscal',
            'regimen_fiscal', 'uso_cfdi', 'correo_facturacion', 'relacion_alumno', 'curp', 'nivel_educativo', 'rvoe', 'fecha_validacion']);
    }

    public function rfcEnmascarado(): string { return substr($this->rfc, 0, 3).str_repeat('*', max(0, strlen($this->rfc) - 6)).substr($this->rfc, -3); }
}
