<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MotivoRechazo extends Model
{
    protected $table = 'motivos_rechazo';

    protected $primaryKey = 'codigo';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'label_autoridad',
        'label_ciudadano',
        'requiere_nota',
        'activo',
    ];

    protected $casts = [
        'requiere_nota' => 'boolean',
        'activo'        => 'boolean',
    ];

    public function reportes(): HasMany
    {
        return $this->hasMany(Reporte::class, 'motivo_rechazo_codigo', 'codigo');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
