<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivoPago extends Model
{
    protected $table = 'archivos_pago';

    protected $primaryKey = 'archivo_pago_id';

    protected $guarded = ['*'];

    protected $casts = [
        'pago_id' => 'integer',
        'tamano_bytes' => 'integer',
        'created_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function pago() { return $this->belongsTo(Pago::class, 'pago_id', 'pago_id'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by', 'id'); }
}
