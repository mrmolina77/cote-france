<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuditoriaPerfilFiscal extends Model
{
    public const CREAR = 'crear';
    public const ACTUALIZAR = 'actualizar';
    public const PREDETERMINAR = 'cambiar_predeterminado';
    public const ACTIVAR = 'activar';
    public const DESACTIVAR = 'desactivar';
    public const ACCIONES = [self::CREAR, self::ACTUALIZAR, self::PREDETERMINAR, self::ACTIVAR, self::DESACTIVAR];

    protected $table = 'auditoria_perfiles_fiscales';
    protected $primaryKey = 'auditoria_perfil_fiscal_id';
    protected $guarded = ['*'];
    protected $casts = ['perfil_fiscal_id' => 'integer', 'prospectos_id' => 'integer', 'usuario_id' => 'integer',
        'campos_modificados' => 'array', 'ocurrido_en' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('La auditoría de perfiles fiscales es inmutable.'));
        static::deleting(fn () => throw new LogicException('La auditoría de perfiles fiscales es append-only.'));
    }

    public function save(array $options = [])
    {
        if ($this->exists) throw new LogicException('La auditoría de perfiles fiscales es inmutable.');
        return parent::save($options);
    }

    public function update(array $attributes = [], array $options = [])
    {
        if ($this->exists) throw new LogicException('La auditoría de perfiles fiscales es inmutable.');
        return parent::update($attributes, $options);
    }

    public function perfilFiscal() { return $this->belongsTo(PerfilFiscal::class, 'perfil_fiscal_id', 'perfil_fiscal_id'); }
    public function prospecto() { return $this->belongsTo(Prospecto::class, 'prospectos_id', 'prospectos_id'); }
    public function usuario() { return $this->belongsTo(User::class, 'usuario_id'); }
}
