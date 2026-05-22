<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tematica extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'tematicas';

    protected $primaryKey = 'tematica_id';

    protected $fillable = [
        'capitulo_id',
        'tematica_descripcion',
        'orden',
        'tematica_activo',
    ];

    public function capitulo()
    {
        return $this->belongsTo(Capitulo::class, 'capitulo_id', 'capitulo_id');
    }
}
