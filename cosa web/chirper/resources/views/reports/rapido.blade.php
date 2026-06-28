@extends('layouts.emergency')

@section('content')
<div id="rapidoApp">
    {{-- Alert banner (inside alta flood) --}}
    <div id="rapidoAlertBanner" class="rapido-alert-banner hidden" role="alert">
        Estás dentro de una zona de inundación alta. Si necesitas ayuda, reporta con intensidad Alta.
    </div>

    {{-- GPS block --}}
    <div class="px-4 pt-4 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 min-w-0">
            <span id="rapidoGpsDot" class="rapido-gps-dot rapido-gps-dot--pending" aria-hidden="true"></span>
            <span id="rapidoGpsStatus" class="text-sm font-medium text-slate-600 truncate">Obteniendo ubicación…</span>
        </div>
        <button type="button" id="rapidoBtnRefreshGps" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 whitespace-nowrap">
            Actualizar GPS
        </button>
    </div>

    {{-- Mode chip (apoyar) --}}
    <div id="rapidoModeChip" class="rapido-mode-chip hidden">
        <span id="rapidoModeChipText">Apoyando inundación</span>
        <button type="button" id="rapidoModeChipDismiss" aria-label="Cancelar modo apoyar">&times;</button>
    </div>

    {{-- Carousel --}}
    <p class="rapido-section-label">Inundaciones cercanas</p>
    <div id="rapidoCarouselWrap" class="rapido-carousel-wrap hidden">
        <button type="button" id="rapidoCarouselPrev" class="rapido-carousel-nav rapido-carousel-nav--prev" aria-label="Anterior">‹</button>
        <div id="rapidoCarousel" class="rapido-carousel"></div>
        <button type="button" id="rapidoCarouselNext" class="rapido-carousel-nav rapido-carousel-nav--next" aria-label="Siguiente">›</button>
    </div>
    <div id="rapidoCarouselDots" class="rapido-carousel-dots hidden"></div>
    <p id="rapidoCarouselEmpty" class="rapido-empty-carousel hidden">No hay inundaciones activas cerca de tu ubicación.</p>

    {{-- Map --}}
    <p class="rapido-section-label">Tu ubicación en el mapa</p>
    <div class="rapido-map-wrap">
        <div id="rapidoMap"></div>
        <div id="rapidoHeatLegend" class="rapido-heat-legend hidden">
            <span class="rapido-heat-legend-item rapido-heat-legend-item--baja">Baja</span>
            <span class="rapido-heat-legend-item rapido-heat-legend-item--media">Media</span>
            <span class="rapido-heat-legend-item rapido-heat-legend-item--alta">Alta</span>
        </div>
        <span class="rapido-map-hint">Arrástrame si es necesario</span>
    </div>
    <p id="rapidoDistanceWarn" class="rapido-distance-warn hidden">
        El marcador está demasiado lejos de tu GPS (máximo 500 m).
    </p>

    {{-- Intensity --}}
    <p class="rapido-section-label">Intensidad del agua</p>
    <div class="rapido-intensity-group" role="group" aria-label="Intensidad propuesta">
        <button type="button" class="rapido-intensity-btn rapido-intensity-btn--baja is-active" data-intensity="baja">
            Baja
        </button>
        <button type="button" class="rapido-intensity-btn rapido-intensity-btn--media" data-intensity="media">
            Media
        </button>
        <button type="button" class="rapido-intensity-btn rapido-intensity-btn--alta" data-intensity="alta">
            Alta / Emergencia
        </button>
    </div>

    {{-- Submit --}}
    <div id="rapidoFormSection" class="rapido-submit-wrap safe-bottom">
        <button type="button" id="rapidoSubmitBtn" class="rapido-submit-btn rapido-submit-btn--baja" disabled>
            Enviar reporte
        </button>
        <p id="rapidoSubmitHint" class="rapido-submit-hint">
            Tu reporte quedará pendiente de validación por las autoridades.
        </p>
    </div>

    {{-- Success --}}
    <div id="rapidoSuccessPanel" class="rapido-success-panel hidden">
        <h2>Reporte registrado</h2>
        <p id="rapidoSuccessMessage">Gracias por tu colaboración.</p>
        <p id="rapidoSuccessReportId" class="text-sm font-mono text-emerald-700"></p>
        <p id="rapidoSuccessEta" class="text-sm text-emerald-800 font-semibold hidden"></p>
        <div class="rapido-success-actions">
            <a href="{{ route('login', [], false) }}" class="rapido-link-primary">Crear cuenta para seguir tus reportes</a>
            <a href="{{ route('login', [], false) }}" class="rapido-link-secondary">Volver al inicio</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="{{ asset('js/flood-outline.js') }}?v=20260627c"></script>
<script src="{{ asset('js/smart-heatmap.js') }}?v=20260629f"></script>
<script src="{{ asset('js/flood-heat-sources.js') }}?v=2"></script>
<script>
window.RAPIDO_CONFIG = {
    apiContext: @json(url('/api/inundaciones/contexto')),
    apiStore: @json(url('/api/reportes')),
    userUuid: (function () {
        let uuid = localStorage.getItem('user_uuid');
        if (!uuid) {
            uuid = crypto.randomUUID();
            localStorage.setItem('user_uuid', uuid);
        }
        return uuid;
    })(),
    initialFloods: @json($inundacionesPublicas ?? []),
    loginUrl: @json(route('login', [], false)),
    refreshIntervalMs: 60000,
};
</script>
<script src="{{ asset('js/reporte-rapido.js') }}?v=3"></script>
@endpush
