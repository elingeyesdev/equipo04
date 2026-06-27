<div>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @include('components.reports.styles')

    <div class="min-h-screen bg-gray-100 -m-4 sm:-m-6 lg:-m-8 p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <a href="{{ route('reports.index', [], false) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">← Vista general de reportes</a>
                <h1 class="text-3xl font-bold tracking-tight text-blue-800 mt-2">Reportes Rechazados</h1>
                <p class="mt-2 text-sm text-slate-600">Auditoría, filtros y modificación de reportes rechazados.</p>
            </div>

            <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-sm mb-10">
                <div class="px-6 py-5 border-b border-gray-200 flex flex-wrap justify-between items-center gap-3 bg-gray-50">
                    <h2 class="text-xl font-semibold text-gray-800">Rechazados</h2>
                    <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded text-xs font-bold">{{ $reportesRechazados->total() }} registro(s)</span>
                </div>
                <x-reports.filter-bar
                    :motivos-rechazo="$motivosRechazo"
                    :validadores-rechazo="$validadoresRechazo"
                    :filtro-rechazo-motivo="$filtroRechazoMotivo"
                    :filtro-rechazo-validador="$filtroRechazoValidador"
                    :filtro-rechazo-desde="$filtroRechazoDesde"
                    :filtro-rechazo-hasta="$filtroRechazoHasta"
                />
                @include('components.reports.rejected-table', ['reportesRechazados' => $reportesRechazados])
                <div class="px-4 pb-4">
                    <x-reports.pagination :paginator="$reportesRechazados" />
                </div>
            </div>

            @include('components.reports.modals.historial')
            @include('components.reports.modals.modificar-rechazado')
        </div>
    </div>

    <script src="{{ asset('js/report-minimaps.js') }}"></script>
</div>
