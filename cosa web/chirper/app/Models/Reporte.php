<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property \Illuminate\Support\Collection<int, Inundacion>|null $cercanas
 */
class Reporte extends Model
{
    use HasFactory;

    public const VALIDACION_PENDIENTE = 'pendiente';
    public const VALIDACION_ACEPTADO  = 'aceptado';
    public const VALIDACION_RECHAZADO = 'rechazado';

    /** Peso de un reporte sin foto. */
    public const PESO_SIN_FOTO  = 1;

    /** Peso de un reporte con foto adjunta. */
    public const PESO_CON_FOTO  = 3;

    protected $table = 'reportes';

    protected $fillable = [
        'user_uuid',
        'citizen_carnet',
        'inundacion_id',
        'lat_gps',
        'long_gps',
        'lat_reporte',
        'long_reporte',
        'intensidad_propuesta',
        'peso',
        'address',
        'description',
        'foto_path',
        'estado_validacion',
        'datos_clima_json',
        'polygon_coords',
        'polygon_geojson',
        'polygon_calculado_at',
        'polygon_es_fallback',
    ];

    protected $casts = [
        'lat_gps'          => 'decimal:7',
        'long_gps'         => 'decimal:7',
        'lat_reporte'      => 'decimal:7',
        'long_reporte'     => 'decimal:7',
        'peso'             => 'integer',
        'datos_clima_json' => 'array',
        'polygon_coords'   => 'array',
        'polygon_geojson'  => 'array',
        'polygon_calculado_at' => 'datetime',
        'polygon_es_fallback'  => 'boolean',
    ];

    public function inundacion(): BelongsTo
    {
        return $this->belongsTo(Inundacion::class, 'inundacion_id');
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(User::class, foreignKey: 'citizen_carnet', ownerKey: 'carnet');
    }

    protected static function booted(): void
    {
        $clearCache = function (Reporte $reporte) {
            if ($reporte->inundacion_id !== null) {
                \Illuminate\Support\Facades\Cache::forget("inundacion.{$reporte->inundacion_id}.quorum");
                \Illuminate\Support\Facades\Cache::forget("inundacion.{$reporte->inundacion_id}.intensidad_reportes");
            }
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }

    /**
     * Calcula el peso que debe tener este reporte según si incluye foto.
     */
    public static function calcularPeso(?string $fotoPath): int
    {
        return $fotoPath !== null ? self::PESO_CON_FOTO : self::PESO_SIN_FOTO;
    }
}
