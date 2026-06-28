@extends('layouts.emergency')

@section('content')
<div id="rapidoApp">
    {{-- Alert banner (inside alta flood) --}}
    <div id="rapidoAlertBanner" class="rapido-alert-banner hidden" role="alert">
        Estás dentro de una zona de inundación alta. Si necesitas ayuda, reporta con intensidad Alta.
    </div>

    {{-- GPS refresh --}}
    <div class="rapido-gps-bar">
        <button type="button" id="rapidoBtnRefreshGps" class="rapido-btn-refresh-gps">
            Actualizar GPS
        </button>
    </div>

    {{-- Map --}}
    <p class="rapido-section-label">Tu ubicación en el mapa</p>
    <div class="rapido-map-wrap">
        <div id="rapidoMap"></div>
        <div id="rapidoHeatLegend" class="rapido-heat-legend hidden">
            <span class="rapido-heat-legend-item rapido-heat-legend-item--baja" data-heat-tier="baja">Baja</span>
            <span class="rapido-heat-legend-item rapido-heat-legend-item--media" data-heat-tier="media">Media</span>
            <span class="rapido-heat-legend-item rapido-heat-legend-item--alta" data-heat-tier="alta">Alta</span>
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
<script src="{{ asset('js/smart-heatmap.js') }}?v=20260629h"></script>
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
<script src="{{ asset('js/reporte-rapido.js') }}?v=4"></script>
@endpush
