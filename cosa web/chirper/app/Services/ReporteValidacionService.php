<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\CalcularPoligonoInundacion;
use App\Models\Inundacion;
use App\Models\MotivoRechazo;
use App\Models\Reporte;
use App\Models\ReporteValidacionHistorial;
use App\Models\User;
use App\Services\InundacionMapaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Centraliza la lógica de validación de reportes por autoridad
 * y el disparo del cálculo topográfico asociado.
 */
final class ReporteValidacionService
{
    public const INTENSIDADES = ['baja', 'media', 'alta'];

    public function crearInundacionDesdeReporte(
        Reporte $reporte,
        string $validadorCarnet,
        ?string $intensidadValidada = null,
        ?string $ajusteComentario = null,
    ): Inundacion {
        $this->validarAjusteIntensidad($reporte, $intensidadValidada, $ajusteComentario);
        $this->validarNoDentroContornoActivo($reporte);

        return DB::transaction(function () use ($reporte, $validadorCarnet, $intensidadValidada, $ajusteComentario) {
            $estadoAnterior = $reporte->estado_validacion;
            $inundacionAnterior = $reporte->inundacion_id;
            $intensidadFinal = $this->resolverIntensidadValidada($reporte, $intensidadValidada);
            $huboAjuste = $intensidadFinal !== null;

            $inundacion = Inundacion::create([
                'latitud'      => $reporte->lat_reporte,
                'longitud'     => $reporte->long_reporte,
                'estado'       => Inundacion::ESTADO_ACTIVA,
                'validador_id' => $validadorCarnet,
            ]);

            $inundacion->resolverMunicipio();

            $reporte->update([
                'estado_validacion'   => Reporte::VALIDACION_ACEPTADO,
                'inundacion_id'       => $inundacion->id,
                'validador_id'        => $validadorCarnet,
                'validado_at'         => now(),
                'motivo_rechazo_codigo' => null,
                'motivo_rechazo_texto'  => null,
                'intensidad_validada' => $intensidadFinal,
                'ajuste_comentario'   => $huboAjuste ? $ajusteComentario : null,
            ]);

            $this->registrarHistorial(
                $reporte,
                $estadoAnterior,
                Reporte::VALIDACION_ACEPTADO,
                $huboAjuste
                    ? ReporteValidacionHistorial::ACCION_APROBAR_CON_AJUSTE
                    : ReporteValidacionHistorial::ACCION_APROBAR_CREAR,
                $validadorCarnet,
                null,
                null,
                $inundacionAnterior,
                $inundacion->id,
                $intensidadFinal,
                $ajusteComentario,
            );

            $this->dispatchTopografia($reporte->fresh());

            return $inundacion->fresh();
        });
    }

    public function aceptarYVincular(
        Reporte $reporte,
        int $inundacionId,
        string $validadorCarnet,
        ?string $intensidadValidada = null,
        ?string $ajusteComentario = null,
    ): Inundacion {
        $this->validarAjusteIntensidad($reporte, $intensidadValidada, $ajusteComentario);

        return DB::transaction(function () use ($reporte, $inundacionId, $validadorCarnet, $intensidadValidada, $ajusteComentario) {
            $estadoAnterior = $reporte->estado_validacion;
            $inundacionAnterior = $reporte->inundacion_id;
            $intensidadFinal = $this->resolverIntensidadValidada($reporte, $intensidadValidada);
            $huboAjuste = $intensidadFinal !== null;

            $inundacion = Inundacion::with('reportesActivosTTL')->findOrFail($inundacionId);

            if ($inundacion->estado !== Inundacion::ESTADO_ACTIVA) {
                throw ValidationException::withMessages([
                    'inundacion_id' => 'Solo se puede vincular a inundaciones activas.',
                ]);
            }

            $mapaService = app(InundacionMapaService::class);

            if (! $mapaService->inundacionTieneReportesVivos($inundacion)) {
                throw ValidationException::withMessages([
                    'inundacion_id' => 'La inundación seleccionada no tiene reportes con TTL vigente.',
                ]);
            }

            $latRep = (float) $reporte->lat_reporte;
            $lngRep = (float) $reporte->long_reporte;

            if (! $mapaService->esVinculableGeograficamente($latRep, $lngRep, $inundacion)) {
                throw ValidationException::withMessages([
                    'inundacion_id' => 'El reporte está fuera del contorno activo y a más de '
                        .InundacionMapaService::BUFFER_CONTORNO_METROS
                        .' m de su borde (o fuera del radio de la mancha de los reportes activos).',
                ]);
            }

            $reporte->update([
                'estado_validacion'     => Reporte::VALIDACION_ACEPTADO,
                'inundacion_id'         => $inundacion->id,
                'validador_id'          => $validadorCarnet,
                'validado_at'           => now(),
                'motivo_rechazo_codigo' => null,
                'motivo_rechazo_texto'  => null,
                'intensidad_validada'   => $intensidadFinal,
                'ajuste_comentario'     => $huboAjuste ? $ajusteComentario : null,
            ]);

            $inundacion->recalcularCentroide();

            $this->registrarHistorial(
                $reporte,
                $estadoAnterior,
                Reporte::VALIDACION_ACEPTADO,
                $huboAjuste
                    ? ReporteValidacionHistorial::ACCION_APROBAR_CON_AJUSTE
                    : ReporteValidacionHistorial::ACCION_APROBAR_VINCULAR,
                $validadorCarnet,
                null,
                null,
                $inundacionAnterior,
                $inundacion->id,
                $intensidadFinal,
                $ajusteComentario,
            );

            $this->dispatchTopografia($reporte->fresh());

            return $inundacion->fresh();
        });
    }

    public function rechazar(
        Reporte $reporte,
        string $validadorCarnet,
        string $motivoCodigo,
        ?string $motivoTexto = null,
    ): void {
        $motivo = MotivoRechazo::query()
            ->where('codigo', $motivoCodigo)
            ->where('activo', true)
            ->firstOrFail();

        $this->validarNotaMotivo($motivo, $motivoTexto);

        DB::transaction(function () use ($reporte, $validadorCarnet, $motivoCodigo, $motivoTexto) {
            $estadoAnterior = $reporte->estado_validacion;
            $inundacionAnterior = $reporte->inundacion_id;
            $accion = $estadoAnterior === Reporte::VALIDACION_RECHAZADO
                ? ReporteValidacionHistorial::ACCION_RE_RECHAZAR
                : ReporteValidacionHistorial::ACCION_RECHAZAR;

            $reporte->update([
                'estado_validacion'     => Reporte::VALIDACION_RECHAZADO,
                'inundacion_id'         => null,
                'validador_id'          => $validadorCarnet,
                'motivo_rechazo_codigo' => $motivoCodigo,
                'motivo_rechazo_texto'  => $motivoTexto,
                'rechazado_at'          => $reporte->rechazado_at ?? now(),
                'validado_at'           => null,
                'intensidad_validada'   => null,
                'ajuste_comentario'     => null,
            ]);

            $this->registrarHistorial(
                $reporte,
                $estadoAnterior,
                Reporte::VALIDACION_RECHAZADO,
                $accion,
                $validadorCarnet,
                $motivoCodigo,
                $motivoTexto,
                $inundacionAnterior,
                null,
            );

            $this->refrescarBanCiudadano($reporte);
        });
    }

    public function revertirAPendiente(
        Reporte $reporte,
        string $validadorCarnet,
        ?string $motivoTexto = null,
    ): void {
        if (trim((string) $motivoTexto) === '') {
            throw ValidationException::withMessages([
                'motivo_texto' => 'Debes indicar el motivo de la reversión a pendiente.',
            ]);
        }

        DB::transaction(function () use ($reporte, $validadorCarnet, $motivoTexto) {
            $estadoAnterior = $reporte->estado_validacion;
            $inundacionAnterior = $reporte->inundacion_id;

            $reporte->update([
                'estado_validacion'     => Reporte::VALIDACION_PENDIENTE,
                'inundacion_id'         => null,
                'validador_id'          => $validadorCarnet,
                'motivo_rechazo_codigo' => null,
                'motivo_rechazo_texto'  => null,
                'intensidad_validada'   => null,
                'ajuste_comentario'     => null,
            ]);

            $this->registrarHistorial(
                $reporte,
                $estadoAnterior,
                Reporte::VALIDACION_PENDIENTE,
                ReporteValidacionHistorial::ACCION_REVERTIR_PENDIENTE,
                $validadorCarnet,
                null,
                $motivoTexto,
                $inundacionAnterior,
                null,
            );

            $this->refrescarBanCiudadano($reporte);
        });
    }

    public function contarRechazosCiudadano(?string $citizenCarnet): int
    {
        if ($citizenCarnet === null || $citizenCarnet === '') {
            return 0;
        }

        return Reporte::query()
            ->where('citizen_carnet', $citizenCarnet)
            ->where('estado_validacion', Reporte::VALIDACION_RECHAZADO)
            ->count();
    }

    private function validarNoDentroContornoActivo(Reporte $reporte): void
    {
        $mapaService = app(InundacionMapaService::class);

        $activas = Inundacion::activas()
            ->with('reportesActivosTTL')
            ->get()
            ->filter(static fn (Inundacion $i): bool => $mapaService->inundacionTieneReportesVivos($i));

        if ($mapaService->reporteDentroInundacionActiva($reporte, $activas)) {
            throw ValidationException::withMessages([
                'action' => 'El reporte está dentro de una inundación activa; debe vincularse en lugar de crear una nueva.',
            ]);
        }
    }

    private function validarAjusteIntensidad(
        Reporte $reporte,
        ?string $intensidadValidada,
        ?string $ajusteComentario,
    ): void {
        if ($intensidadValidada === null || $intensidadValidada === '') {
            return;
        }

        if (! in_array($intensidadValidada, self::INTENSIDADES, true)) {
            throw ValidationException::withMessages([
                'intensidad_validada' => 'Intensidad validada no reconocida.',
            ]);
        }

        if ($intensidadValidada === $reporte->intensidad_propuesta) {
            return;
        }

        if (trim((string) $ajusteComentario) === '' || mb_strlen(trim((string) $ajusteComentario)) < 10) {
            throw ValidationException::withMessages([
                'ajuste_comentario' => 'Al ajustar la intensidad debes incluir un comentario de al menos 10 caracteres.',
            ]);
        }
    }

    private function resolverIntensidadValidada(Reporte $reporte, ?string $intensidadValidada): ?string
    {
        if ($intensidadValidada === null || $intensidadValidada === '') {
            return null;
        }

        if ($intensidadValidada === $reporte->intensidad_propuesta) {
            return null;
        }

        return $intensidadValidada;
    }

    private function validarNotaMotivo(MotivoRechazo $motivo, ?string $motivoTexto): void
    {
        $texto = trim((string) $motivoTexto);

        if (! $motivo->requiere_nota) {
            return;
        }

        $minLength = $motivo->codigo === 'otro' ? 10 : 5;

        if ($texto === '' || mb_strlen($texto) < $minLength) {
            throw ValidationException::withMessages([
                'motivo_texto' => "El motivo \"{$motivo->label_autoridad}\" requiere una nota de al menos {$minLength} caracteres.",
            ]);
        }
    }

    private function registrarHistorial(
        Reporte $reporte,
        string $estadoAnterior,
        string $estadoNuevo,
        string $accion,
        string $validadorCarnet,
        ?string $motivoCodigo,
        ?string $motivoTexto,
        ?int $inundacionAnterior,
        ?int $inundacionNuevo,
        ?string $intensidadValidada = null,
        ?string $ajusteComentario = null,
    ): void {
        ReporteValidacionHistorial::create([
            'reporte_id'                      => $reporte->id,
            'estado_anterior'                 => $estadoAnterior,
            'estado_nuevo'                    => $estadoNuevo,
            'accion'                          => $accion,
            'validador_id'                    => $validadorCarnet,
            'motivo_codigo'                   => $motivoCodigo,
            'motivo_texto'                    => $motivoTexto,
            'inundacion_id_anterior'          => $inundacionAnterior,
            'inundacion_id_nuevo'             => $inundacionNuevo,
            'intensidad_propuesta_snapshot'   => $reporte->intensidad_propuesta,
            'intensidad_validada_snapshot'    => $intensidadValidada,
            'metadata_json'                   => $this->construirMetadata($reporte, $ajusteComentario),
            'fecha_accion'                    => now(),
        ]);
    }

    private function construirMetadata(Reporte $reporte, ?string $ajusteComentario = null): array
    {
        $precipitacion = data_get($reporte->datos_clima_json, 'current.precipitation');

        return array_filter([
            'distancia_gps_m'           => $reporte->distancia_gps_metros !== null
                ? (float) $reporte->distancia_gps_metros
                : null,
            'precipitacion_mm'          => $precipitacion,
            'peso'                      => $reporte->peso,
            'intensidad_propuesta'      => $reporte->intensidad_propuesta,
            'intensidad_validada'       => $reporte->intensidad_validada,
            'ajuste_comentario'         => $ajusteComentario,
            'tiene_foto'                => $reporte->foto_path !== null,
            'rechazos_previos_ciudadano'=> $this->contarRechazosCiudadano($reporte->citizen_carnet),
            'citizen_carnet'            => $reporte->citizen_carnet,
        ], fn ($value) => $value !== null);
    }

    private function refrescarBanCiudadano(Reporte $reporte): void
    {
        if ($reporte->citizen_carnet === null) {
            return;
        }

        User::query()
            ->where('carnet', $reporte->citizen_carnet)
            ->first()
            ?->refreshBanStatus();
    }

    private function dispatchTopografia(Reporte $reporte): void
    {
        CalcularPoligonoInundacion::dispatch($reporte->id);

        if ($reporte->inundacion_id !== null) {
            CalcularPoligonoInundacion::dispatch($reporte->inundacion_id, 'inundacion');
        }
    }
}
