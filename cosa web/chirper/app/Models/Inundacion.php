<?php

namespace App\Models;

use App\Services\GeoLocationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Modelo Inundacion — Quórum Dinámico
 *
 * Los campos puntos_quorum, intensidad_actual y expira_at fueron eliminados
 * de la tabla. El quórum y la intensidad se calculan al vuelo usando los
 * reportes asociados que estén dentro del TTL (3 horas).
 */
class Inundacion extends Model
{
    use HasFactory;

    /** Tiempo de vida (TTL) de un reporte en horas para el cómputo de quórum. */
    public const TTL_HORAS = 3;

    /** Puntos mínimos para considerar una inundación como "Confirmada". */
    public const UMBRAL_QUORUM = 5;

    public const ESTADO_ACTIVA    = 'activa';
    public const ESTADO_TERMINADA = 'terminada';
    public const ESTADO_FALSA     = 'falsa';

    protected $table = 'inundaciones';

    protected $fillable = [
        'citizen_carnet',
        'validador_id',
        'latitud',
        'longitud',
        'estado',
        'municipio_id',
        'polygon_coords',
        'polygon_geojson',
        'polygon_calculado_at',
        'polygon_editado_autoridad',
        'polygon_es_fallback',
    ];

    protected $casts = [
        'latitud'                    => 'decimal:7',
        'longitud'                   => 'decimal:7',
        'polygon_coords'             => 'array',
        'polygon_geojson'            => 'array',
        'polygon_calculado_at'       => 'datetime',
        'polygon_editado_autoridad'  => 'boolean',
        'polygon_es_fallback'        => 'boolean',
    ];

    // ─────────────────────────────────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────────────────────────────────

    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validador_id', 'carnet');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function reportes(): HasMany
    {
        return $this->hasMany(Reporte::class, 'inundacion_id');
    }

    /**
     * Víctimas registradas en esta inundación.
     */
    public function victimas(): HasMany
    {
        return $this->hasMany(Victima::class, 'inundacion_id');
    }

    /**
     * Inventario / donaciones registradas en esta inundación.
     */
    public function inventarios(): HasMany
    {
        return $this->hasMany(Inventario::class, 'inundacion_id');
    }

    /**
     * Daños materiales registrados en esta inundación.
     */
    public function danosMateriales(): HasMany
    {
        return $this->hasMany(DanoMaterial::class, 'inundacion_id');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Cómputo dinámico de Quórum
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Devuelve los reportes válidos para el cómputo de quórum:
     *   - Creados dentro del TTL (últimas N horas)
     *   - Sin estado_validacion = 'rechazado'
     */
    public function reportesActivosTTL(): HasMany
    {
        $ttlInicio = Carbon::now()->subHours(self::TTL_HORAS);
        $ahora     = Carbon::now();

        return $this->reportes()
            ->where(function (Builder $query) use ($ttlInicio, $ahora) {
                // Ahora usamos 'updated_at' para que la autoridad pueda renovar
                // el tiempo de vida de un reporte haciendo un simple 'touch()'
                $query->whereBetween('updated_at', [$ttlInicio, $ahora])
                      ->orWhereBetween('created_at', [$ttlInicio, $ahora]); // Fallback por si acaso
            })
            ->whereNotIn('estado_validacion', [
                Reporte::VALIDACION_RECHAZADO,
                'rechazada',
            ]);
    }

    /**
     * Suma total de puntos (peso) de los reportes dentro del TTL.
     * Asume que la relación reportesActivosTTL ya fue cargada con eager load.
     */
    public function quorumTotal(): int
    {
        // Si la colección ya fue eager-loaded usamos sum() en memoria,
        // evitando una query adicional por cada inundación.
        if ($this->relationLoaded('reportesActivosTTL')) {
            return (int) $this->reportesActivosTTL->sum('peso');
        }

        return (int) \Illuminate\Support\Facades\Cache::remember("inundacion.{$this->id}.quorum", now()->addMinutes(5), function () {
            return (int) $this->reportesActivosTTL()->sum('peso');
        });
    }

    /**
     * Indica si la inundación ha alcanzado el umbral de confirmación.
     */
    public function estaConfirmada(): bool
    {
        return $this->quorumTotal() >= self::UMBRAL_QUORUM;
    }

    /**
     * Determina la intensidad ganadora por votación ponderada.
     * En caso de empate, se prioriza la intensidad más alta.
     *
     * @return string|null  'baja' | 'media' | 'alta' | null si no hay reportes
     */
    public function intensidadCalculada(): ?string
    {
        return \Illuminate\Support\Facades\Cache::remember("inundacion.{$this->id}.intensidad_calculada", now()->addMinutes(5), function () {
            if ($this->relationLoaded('reportesActivosTTL')) {
                $reportes = $this->reportesActivosTTL;
            } else {
                $reportes = $this->reportesActivosTTL()->get();
            }

            if ($reportes->isEmpty()) {
                return null;
            }

            $puntos = [
                'alta'  => 0,
                'media' => 0,
                'baja'  => 0,
            ];

            foreach ($reportes as $reporte) {
                $intensidad = $reporte->intensidad_propuesta;
                if (array_key_exists($intensidad, $puntos)) {
                    $puntos[$intensidad] += $reporte->peso;
                }
            }

            arsort($puntos);
            return array_key_first($puntos);
        });
    }

    /**
     * Recalcula el centroide (latitud, longitud) promediando ponderadamente
     * las coordenadas de todos sus reportes vinculados y actualiza el modelo.
     */
    public function recalcularCentroide(): void
    {
        $reportes = $this->reportes()->where('estado_validacion', Reporte::VALIDACION_ACEPTADO)->get();
        if ($reportes->isEmpty()) {
            return;
        }

        $sumaLat   = 0.0;
        $sumaLng   = 0.0;
        $sumaPesos = 0;

        foreach ($reportes as $rep) {
            $peso       = $rep->peso ?: 1; // Fallback por seguridad
            $sumaLat   += ((float) $rep->lat_reporte) * $peso;
            $sumaLng   += ((float) $rep->long_reporte) * $peso;
            $sumaPesos += $peso;
        }

        if ($sumaPesos > 0) {
            $this->update([
                'latitud'  => (string) round($sumaLat / $sumaPesos, 7),
                'longitud' => (string) round($sumaLng / $sumaPesos, 7),
            ]);

            // Resolver municipio automáticamente después de actualizar el centroide
            $this->resolverMunicipio();
        }
    }

    /**
     * Resuelve y persiste el municipio_id usando point-in-polygon sobre
     * el centroide actual (latitud, longitud) de la inundación.
     *
     * Se delega en GeoLocationService para mantener Single Responsibility.
     * Si el centroide cae fuera de todos los polígonos, municipio_id queda null
     * y se registra un aviso en el log para revisión manual.
     */
    public function resolverMunicipio(): void
    {
        $lat = (float) $this->latitud;
        $lng = (float) $this->longitud;

        if ($lat === 0.0 && $lng === 0.0) {
            return; // Coordenadas no inicializadas
        }

        /** @var GeoLocationService $geoService */
        $geoService = app(GeoLocationService::class);
        $municipio  = $geoService->findMunicipio($lat, $lng);

        if ($municipio === null) {
            Log::info("Inundacion #{$this->id}: no se pudo resolver municipio para ({$lat}, {$lng}).");
            return;
        }

        if ((int) $this->municipio_id !== $municipio->id) {
            $this->municipio_id = $municipio->id;
            $this->saveQuietly(); // sin disparar eventos adicionales
            Log::info("Inundacion #{$this->id}: municipio asignado → {$municipio->nombre} (id={$municipio->id}).");
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Scopes de conveniencia
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Scope: solo inundaciones activas (las visibles en el mapa).
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_ACTIVA);
    }

    /**
     * Scope: solo inundaciones terminadas (historial).
     */
    public function scopeTerminadas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_TERMINADA);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers de desglose
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Construye el desglose de puntos por categoría de intensidad
     * a partir de cualquier colección de reportes.
     *
     * @param  \Illuminate\Support\Collection  $reportes
     * @return array{alta: int, media: int, baja: int}
     */
    public function desgloseReportes(\Illuminate\Support\Collection $reportes): array
    {
        $desglose = ['alta' => 0, 'media' => 0, 'baja' => 0];

        foreach ($reportes as $rep) {
            $cat = $rep->intensidad_propuesta;
            if (array_key_exists($cat, $desglose)) {
                $desglose[$cat] += (int) $rep->peso;
            }
        }

        return $desglose;
    }
}
