<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporteValidacionHistorial extends Model
{
    public const ACCION_RECHAZAR           = 'rechazar';
    public const ACCION_APROBAR_CREAR      = 'aprobar_crear';
    public const ACCION_APROBAR_VINCULAR   = 'aprobar_vincular';
    public const ACCION_APROBAR_CON_AJUSTE = 'aprobar_con_ajuste';
    public const ACCION_REVERTIR_PENDIENTE = 'revertir_pendiente';
    public const ACCION_RE_RECHAZAR        = 're_rechazar';

    protected $table = 'reporte_validacion_historial';

    public $timestamps = false;

    protected $fillable = [
        'reporte_id',
        'estado_anterior',
        'estado_nuevo',
        'accion',
        'validador_id',
        'motivo_codigo',
        'motivo_texto',
        'inundacion_id_anterior',
        'inundacion_id_nuevo',
        'intensidad_propuesta_snapshot',
        'intensidad_validada_snapshot',
        'metadata_json',
        'fecha_accion',
    ];

    protected $casts = [
        'metadata_json' => 'array',
        'fecha_accion'  => 'datetime',
    ];

    public function reporte(): BelongsTo
    {
        return $this->belongsTo(Reporte::class);
    }

    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validador_id', 'carnet');
    }

    public function motivo(): BelongsTo
    {
        return $this->belongsTo(MotivoRechazo::class, 'motivo_codigo', 'codigo');
    }
}
