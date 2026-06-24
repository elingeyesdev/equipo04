<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected $casts = [
        'lat_gps'          => 'decimal:7',
        'long_gps'         => 'decimal:7',
        'lat_reporte'      => 'decimal:7',
        'long_reporte'     => 'decimal:7',
        'peso'             => 'integer',
        'datos_clima_json' => 'array',
        'polygon_coords'   => 'array',
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

    /**
     * Calcula el ETA estimado de ayuda logística para este reporte.
     */
    public function getEtaAttribute(): ?array
    {
        $lat = (float) $this->lat_reporte;
        $lng = (float) $this->long_reporte;
        if (!$lat || !$lng) return null;

        $centros = \App\Models\CentroAsistencia::all();

        if ($centros->isEmpty()) {
            return null;
        }

        $minDistanceKm = null;
        $closest = null;

        foreach ($centros as $centro) {
            $cLat = (float) $centro->latitud;
            $cLng = (float) $centro->longitud;
            if (!$cLat || !$cLng) continue;

            $dLat = deg2rad($cLat - $lat);
            $dLng = deg2rad($cLng - $lng);
            $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat)) * cos(deg2rad($cLat)) * sin($dLng / 2) ** 2;
            $dist = 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));

            if ($minDistanceKm === null || $dist < $minDistanceKm) {
                $minDistanceKm = $dist;
                $closest = $centro;
            }
        }

        if (!$closest || $minDistanceKm === null) {
            return null;
        }

        $speedKmH  = 35.0;
        $etaMinutes = (int) max(3, ceil(($minDistanceKm / $speedKmH) * 60));

        return [
            'name'         => (string) ($closest->nombre ?? 'Centro de asistencia'),
            'distance_km'  => round($minDistanceKm, 2),
            'eta_minutes'  => $etaMinutes,
        ];
    }
}
