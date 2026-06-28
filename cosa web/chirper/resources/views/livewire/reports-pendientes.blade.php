<div>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @include('components.reports.styles')

    <div class="min-h-screen bg-gray-100 -m-4 sm:-m-6 lg:-m-8 p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <a href="{{ route('reports.index', [], false) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">← Vista general de reportes</a>
                <h1 class="text-3xl font-bold tracking-tight text-blue-800 mt-2">Reportes Pendientes de Validación</h1>
                <p class="mt-2 text-sm text-slate-600">Cola completa de reportes sin vincular esperando revisión.</p>
            </div>

            <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-sm">
                <div class="px-6 py-5 border-b border-gray-200 flex flex-wrap justify-between items-center gap-3 bg-gray-50">
                    <h2 class="text-xl font-semibold text-gray-800">Pendientes</h2>
                    <span class="bg-orange-100 text-orange-800 py-1 px-3 rounded text-xs font-bold">{{ $reportesPendientes->total() }} registro(s)</span>
                </div>
                @include('components.reports.pending-table', ['reportesPendientes' => $reportesPendientes])
                <div class="px-4 pb-4">
                    <x-reports.pagination :paginator="$reportesPendientes" />
                </div>
            </div>
        </div>
    </div>

    @include('components.reports.validation-scripts')
</div>
