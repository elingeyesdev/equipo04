<div>
    @php
        /**
         * @var \Illuminate\Support\Collection<int, \App\Models\Reporte>|array<int, \App\Models\Reporte> $misReportes
         * @var \Illuminate\Support\Collection<int, \App\Models\Reporte>|array<int, \App\Models\Reporte> $reportesPendientes
         * @var \Illuminate\Support\Collection<int, \App\Models\Reporte>|array<int, \App\Models\Reporte> $reportesRechazados
         * @var iterable<int, array<string, mixed>> $inundacionesActivas
         * @var iterable<int, array<string, mixed>> $inundacionesTerminadas
         * @var \Illuminate\Support\Collection<int, \App\Models\Inundacion> $inundacionesActivasParaVincular
         */
    @endphp
    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);
        }
        .glass-panel-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1);
        }
        .glass-table th { background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(4px); }
        .glass-table tr:hover { background: rgba(255, 255, 255, 0.5); }
        .gradient-text {
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .report-location-minimap {
            width: 11rem;
            height: 5rem;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, 0.8);
            background: #f1f5f9;
            position: relative;
            z-index: 0;
            isolation: isolate;
        }
        .report-location-minimap .leaflet-container {
            z-index: 1 !important;
        }
        @media (min-width: 768px) {
            .report-location-minimap {
                width: 12.5rem;
                height: 5.5rem;
            }
        }
        .approve-dropdown-menu {
            min-width: 13rem;
        }
    </style>

    <!-- Main Container with custom gradient background -->
    <div class="min-h-screen bg-gray-100 -m-4 sm:-m-6 lg:-m-8 p-4 sm:p-6 lg:p-8">
        
        <div class="max-w-7xl mx-auto">
            
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-10 gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-blue-800">Registro de Inundaciones</h1>
                    <p class="mt-2 text-sm font-medium text-slate-600">Centro de Monitoreo y Validación de Eventos Hidrológicos.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('command-center.index', [], false) }}" class="hidden items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        Análisis de Impacto
                    </a>
                    <a href="{{ route('vehiculos.mapa', [], false) }}" class="hidden items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                        Vehículos
                    </a>
                    @if ($role === 'authority')
                        <a href="{{ route('vehiculos.index', [], false) }}" class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 shadow-sm hover:bg-emerald-100 transition-colors">
                            Gestionar Flota
                        </a>
                    @endif
                    <a href="{{ route('reports.create', [], false) }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-6 py-2.5 text-white font-semibold shadow-md hover:bg-blue-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Nuevo Reporte
                    </a>
                </div>
            </div>

    <!-- Map Filters -->
    <div class="mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200 mt-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">Buscar Reportes por Ubicación</h3>
        <x-location-filter formAction="{{ route('reports.index', [], false) }}" />
    </div>

    <x-reports-map :reports="$inundacionesActivas" :pendingReports="$reportesPendientes ?? []" :showRouting="true" />


            @if (!empty($error))
                <div class="mb-6 rounded border border-red-300 bg-red-100 p-4 text-sm flex items-center gap-3">
                    <span class="text-red-800 font-medium">{{ $error }}</span>
                </div>
            @endif

            @if(count($misReportes ?? []) > 0 || (isset($role) && $role === 'citizen'))
                <div class="bg-white rounded border border-gray-200 overflow-hidden mb-10 shadow-sm">
                    <div class="px-6 py-5 border-b border-gray-200 flex items-center justify-between bg-gray-50">
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                            Mis reportes enviados
                        </h2>
                        <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded text-xs font-bold">{{ count($misReportes ?? []) }} registro(s)</span>
                    </div>
                    <div class="overflow-x-auto p-2">
                        <table class="w-full text-sm glass-table rounded-xl overflow-hidden">
                            <thead class="text-slate-600">
                                <tr>
                                    <th class="text-left font-semibold px-4 py-3 rounded-tl-xl">ID</th>
                                    <th class="text-left font-semibold px-4 py-3">Estado</th>
                                    <th class="text-left font-semibold px-4 py-3">Intensidad</th>
                                    <th class="text-left font-semibold px-4 py-3 rounded-tr-xl">Actualización</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/50">
                                @forelse(($misReportes ?? []) as $rep)
                                    @php /** @var \App\Models\Reporte $rep */ @endphp
                                    <tr class="transition-colors duration-200">
                                        <td class="px-4 py-3 font-semibold text-slate-700">N°{{ $rep->id }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded px-2.5 py-1 text-xs font-bold
                                                {{ (string) $rep->estado_validacion === 'pendiente' ? 'bg-yellow-100 text-yellow-800' : ((string) $rep->estado_validacion === 'aceptado' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') }}">
                                                {{ ucfirst((string) $rep->estado_validacion) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 font-medium text-slate-600">{{ ucfirst((string) $rep->intensidad_propuesta) }}</td>
                                        <td class="px-4 py-3 text-slate-500">{{ optional($rep->updated_at)->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-4 py-8 text-slate-500 text-center font-medium" colspan="4">Aún no has enviado reportes. ¡Tu reporte salva vidas!</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- ══════════════════════════════════════════════════════════════════
                 PANEL: Inundaciones Activas
            ══════════════════════════════════════════════════════════════════ --}}
            <div class="bg-white rounded border border-gray-200 overflow-hidden mb-10 shadow-sm">
                <div class="px-6 py-5 border-b border-gray-200 flex items-center justify-between bg-gray-50">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                        Inundaciones Activas
                    </h2>
                    <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded text-xs font-bold">{{ count($inundacionesActivas) }} registro(s)</span>
                </div>
                <div class="overflow-x-auto p-2">
                    <table class="w-full text-sm glass-table rounded-xl overflow-hidden">
                        <thead class="text-slate-600">
                            <tr>
                                <th class="text-left font-semibold px-4 py-3 rounded-tl-xl">ID</th>
                                <th class="text-left font-semibold px-4 py-3">Estado</th>
                                <th class="text-left font-semibold px-4 py-3">Intensidad</th>
                                <th class="text-left font-semibold px-4 py-3">Quórum</th>
                                <th class="text-left font-semibold px-4 py-3">Creado</th>
                                <th class="text-right font-semibold px-4 py-3 rounded-tr-xl">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/50">
                            @forelse ($inundacionesActivas as $inundacion)
                                @php
                                    $id = data_get($inundacion, 'id');
                                    $estado = data_get($inundacion, 'estado', '');
                                    $int = data_get($inundacion, 'intensidad_calculada', null);
                                    $quorum = data_get($inundacion, 'quorum_total', 0);
                                    $confirmada = data_get($inundacion, 'esta_confirmada', false);
                                @endphp
                                
                                <tr class="transition-colors duration-200 cursor-pointer hover:bg-white/30" onclick="toggleDetails({{ $id }})">
                                    <td class="px-4 py-3 font-semibold text-slate-700">N°{{ $id }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded px-2.5 py-1 text-xs font-bold uppercase tracking-wider bg-blue-100 text-blue-700 shadow-sm">
                                            {{ $estado }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($int)
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider shadow-sm
                                                {{ $int === 'alta' ? 'bg-rose-500 text-white' : ($int === 'media' ? 'bg-amber-400 text-amber-900' : 'bg-teal-400 text-teal-900') }}">
                                                {{ $int }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-extrabold {{ $confirmada ? 'text-teal-600' : 'text-slate-700' }}">
                                            {{ $quorum }} pts @if($confirmada) <svg class="w-3 h-3 fill-current inline-block ml-1 text-teal-500" viewBox="0 0 640 640"><path d="M530.8 134.1C545.1 144.5 548.3 164.5 537.9 178.8L281.9 530.8C276.4 538.4 267.9 543.1 258.5 543.9C249.1 544.7 240 541.2 233.4 534.6L105.4 406.6C92.9 394.1 92.9 373.8 105.4 361.3C117.9 348.8 138.2 348.8 150.7 361.3L252.2 462.8L486.2 141.1C496.6 126.8 516.6 123.6 530.9 134z"/></svg> @endif
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 text-xs">
                                        {{ data_get($inundacion, 'created_at', '') ? \Carbon\Carbon::parse(data_get($inundacion, 'created_at'))->format('d M, Y H:i') : '' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-3 relative">
                                            @if(isset($role) && $role === 'authority' && $estado === 'activa')
                                                <button wire:click="desactivar({{ $id }})" wire:confirm="¿Desactivar la inundación N°{{ $id }}? Pasará a estado Terminada." class="p-1.5 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 transition-colors shadow-sm" title="Finalizar Inundación" onclick="event.stopPropagation()">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                </button>
                                            @endif
                                            <div class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                                <svg id="chevron-{{ $id }}" class="w-4 h-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                
                                <tr id="details-{{ $id }}" class="hidden bg-slate-50/50">
                                    <td colspan="6" class="p-0 border-t border-white/50">
                                        <div class="p-5">
                                            <div class="flex flex-col md:flex-row gap-6">
                                                <div class="flex-1">
                                                    <h4 class="font-bold text-sm text-indigo-900 uppercase tracking-wide mb-3 flex items-center gap-2">
                                                        <svg class="w-4 h-4 fill-current inline-block mr-1" viewBox="0 0 640 640"><path d="M541.9 139.5C546.4 127.7 543.6 114.3 534.7 105.4C525.8 96.5 512.4 93.6 500.6 98.2L84.6 258.2C71.9 263 63.7 275.2 64 288.7C64.3 302.2 73.1 314.1 85.9 318.3L262.7 377.2L321.6 554C325.9 566.8 337.7 575.6 351.2 575.9C364.7 576.2 376.9 568 381.8 555.4L541.8 139.4z"/></svg> Reportes Vinculados (Últimas 3h)
                                                    </h4>
                                                    @php
                                                        $reportesActivos = data_get($inundacion, 'reportes_activos', []);
                                                    @endphp
                                                    @if(count($reportesActivos) > 0)
                                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                            @foreach($reportesActivos as $rep)
                                                                <div class="bg-white/70 border border-white rounded-xl p-3 shadow-sm flex items-start justify-between group hover:border-indigo-200 transition-colors">
                                                                    <div>
                                                                        <div class="flex items-center gap-2 mb-1">
                                                                            <span class="font-bold text-slate-700 text-sm">N°{{ $rep['id'] }}</span>
                                                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                                                                {{ $rep['intensidad_propuesta'] === 'alta' ? 'bg-rose-100 text-rose-700' : ($rep['intensidad_propuesta'] === 'media' ? 'bg-amber-100 text-amber-700' : 'bg-teal-100 text-teal-700') }}">
                                                                                {{ $rep['intensidad_propuesta'] }}
                                                                            </span>
                                                                        </div>
                                                                        <p class="text-xs text-slate-500 mb-1">GPS: {{ number_format((float)$rep['lat_reporte'], 4) }}, {{ number_format((float)$rep['long_reporte'], 4) }}</p>
                                                                        <p class="text-[11px] font-medium text-slate-400">{{ $rep['created_at_human'] ?? '' }} • Aportó {{ $rep['peso'] }} pts</p>
                                                                    </div>
                                                                    
                                                                    {{-- Botón de Renovación para Autoridades --}}
                                                                    @if(isset($role) && $role === 'authority')
                                                                        <button wire:click="renovarReporte({{ $rep['id'] }})" class="opacity-0 group-hover:opacity-100 transition-opacity bg-indigo-50 hover:bg-indigo-100 text-indigo-600 p-1.5 rounded-lg shadow-sm border border-indigo-100" title="Renovar TTL (+3h)">
                                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <div class="bg-slate-100/50 rounded-xl p-4 text-center">
                                                            <p class="text-sm text-slate-500 font-medium">No hay reportes activos en las últimas 3h.</p>
                                                        </div>
                                                    @endif
                                                    @php
                                                        $reportesInactivos = data_get($inundacion, 'reportes_inactivos', []);
                                                    @endphp
                                                    @if(count($reportesInactivos) > 0)
                                                        <h4 class="font-bold text-sm text-slate-500 uppercase tracking-wide mt-6 mb-3 flex items-center gap-2 border-t border-slate-200/60 pt-4">
                                                            <svg class="w-4 h-4 fill-current inline-block mr-1" viewBox="0 0 640 640"><path d="M128 96C128 78.3 142.3 64 160 64L480 64C497.7 64 512 78.3 512 96C512 113.7 497.7 128 480 128L480 139C480 181.4 463.1 222.1 433.1 252.1L365.2 320L433.1 387.9C463.1 417.9 480 458.6 480 501L480 512C497.7 512 512 526.3 512 544C512 561.7 497.7 576 480 576L160 576C142.3 576 128 561.7 128 544C128 526.3 142.3 512 160 512L160 501C160 458.6 176.9 417.9 206.9 387.9L274.8 320L206.9 252.1C176.9 222.1 160 181.4 160 139L160 128C142.3 128 128 113.7 128 96zM224 128L224 139C224 164.5 234.1 188.9 252.1 206.9L320 274.8L387.9 206.9C405.9 188.9 416 164.5 416 139L416 128L224 128zM224 512L416 512L416 501C416 475.5 405.9 451.1 387.9 433.1L320 365.2L252.1 433.1C234.1 451.1 224 475.5 224 501L224 512z"/></svg> Reportes Inactivos (TTL Caducado)
                                                        </h4>
                                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 opacity-75 grayscale-[30%]">
                                                            @foreach($reportesInactivos as $rep)
                                                                <div class="bg-white/40 border border-slate-200/50 rounded-xl p-3 shadow-sm flex items-start justify-between group hover:border-slate-300 transition-colors">
                                                                    <div>
                                                                        <div class="flex items-center gap-2 mb-1">
                                                                            <span class="font-bold text-slate-500 text-sm">N°{{ $rep['id'] }}</span>
                                                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                                                                {{ $rep['intensidad_propuesta'] === 'alta' ? 'bg-rose-50 text-rose-500' : ($rep['intensidad_propuesta'] === 'media' ? 'bg-amber-50 text-amber-500' : 'bg-teal-50 text-teal-500') }}">
                                                                                {{ $rep['intensidad_propuesta'] }}
                                                                            </span>
                                                                        </div>
                                                                        <p class="text-[11px] font-medium text-slate-400">Caducó hace: {{ $rep['caducado_hace'] }}</p>
                                                                    </div>
                                                                    
                                                                    {{-- Botón de Renovación para Autoridades --}}
                                                                    @if(isset($role) && $role === 'authority')
                                                                        <button wire:click="renovarReporte({{ $rep['id'] }})" class="opacity-100 transition-opacity bg-indigo-50 hover:bg-indigo-100 text-indigo-600 p-1.5 rounded-lg shadow-sm border border-indigo-100" title="Renovar TTL (+3h) para reactivarlo">
                                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                                
                                                <div class="w-full md:w-64 flex flex-col gap-4">
                                                    <div class="bg-white/60 border border-white rounded-2xl p-4 shadow-sm">
                                                        <h4 class="font-bold text-[11px] text-slate-400 uppercase tracking-widest mb-3">Distribución de Quórum</h4>
                                                        @php
                                                            $desglose = data_get($inundacion, 'desglose_puntos', ['alta' => 0, 'media' => 0, 'baja' => 0]);
                                                        @endphp
                                                        <div class="space-y-2">
                                                            <div class="flex justify-between items-center text-sm">
                                                                <span class="text-rose-600 font-bold">Alta</span>
                                                                <span class="bg-rose-100 text-rose-800 px-2 py-0.5 rounded-full font-semibold text-xs">{{ $desglose['alta'] }} pts</span>
                                                            </div>
                                                            <div class="flex justify-between items-center text-sm">
                                                                <span class="text-amber-600 font-bold">Media</span>
                                                                <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full font-semibold text-xs">{{ $desglose['media'] }} pts</span>
                                                            </div>
                                                            <div class="flex justify-between items-center text-sm">
                                                                <span class="text-teal-600 font-bold">Baja</span>
                                                                <span class="bg-teal-100 text-teal-800 px-2 py-0.5 rounded-full font-semibold text-xs">{{ $desglose['baja'] }} pts</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <a href="{{ route('reports.show', ['id' => $id], false) }}" class="flex items-center justify-center gap-2 bg-blue-700 text-white rounded-xl py-2.5 px-4 font-semibold hover:bg-blue-800 transition-colors">
                                                        Ver Ficha Completa
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-8 text-slate-500 text-center font-medium" colspan="6">No hay inundaciones registradas en este momento.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @php
                $currentPage = (int) ($meta['current_page'] ?? 1);
                $lastPage = (int) ($meta['last_page'] ?? 1);
            @endphp

            @if ($lastPage > 1)
                <div class="mt-6 mb-12 flex items-center justify-between">
                    <div class="text-slate-500 font-medium bg-white/50 px-4 py-1.5 rounded-full shadow-sm border border-white/60">
                        Página {{ $currentPage }} de {{ $lastPage }}
                    </div>
                    <div class="flex items-center gap-3">
                        @if ($currentPage > 1)
                            <a class="bg-white/60 hover:bg-white text-slate-700 font-semibold py-2 px-5 rounded-full shadow-sm border border-white/60 transition-colors" href="{{ route('reports.index', ['page' => $currentPage - 1], false) }}">Anterior</a>
                        @endif
                        @if ($currentPage < $lastPage)
                            <a class="bg-white/60 hover:bg-white text-slate-700 font-semibold py-2 px-5 rounded-full shadow-sm border border-white/60 transition-colors" href="{{ route('reports.index', ['page' => $currentPage + 1], false) }}">Siguiente</a>
                        @endif
                    </div>
                </div>
            @endif

            @if(isset($role) && $role === 'authority')

            {{-- ══════════════════════════════════════════════════════════════════
                 PANEL: Reportes Pendientes de Validación
            ══════════════════════════════════════════════════════════════════ --}}
            <div class="bg-white rounded border border-gray-200 overflow-hidden mt-10 mb-10 shadow-sm">
                <div class="px-6 py-5 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center bg-gray-50 gap-4">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                        Pendientes de Validación
                    </h2>
                    <a href="#map-container" onclick="document.getElementById('map-container').scrollIntoView({behavior: 'smooth'})" class="bg-orange-600 text-white font-bold px-5 py-2 rounded shadow-md hover:bg-orange-700 transition-colors">Validar en el Mapa</a>
                </div>
                <div class="overflow-x-auto p-2">
                    <table class="w-full text-sm glass-table rounded-xl overflow-hidden">
                        <thead class="text-slate-600">
                            <tr>
                                <th class="text-left font-semibold px-4 py-3 rounded-tl-xl w-32">Foto</th>
                                <th class="text-left font-semibold px-4 py-3">Reporte N°</th>
                                <th class="text-left font-semibold px-4 py-3">Intensidad</th>
                                <th class="text-left font-semibold px-4 py-3">Detalles</th>
                                <th class="text-left font-semibold px-4 py-3">Ubicación</th>
                                <th class="text-left font-semibold px-4 py-3 rounded-tr-xl">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/50">
                            @forelse ($reportesPendientes ?? [] as $rep)
                                @php
                                    /** @var \App\Models\Reporte $rep */
                                    $reporterName = $rep->citizen?->name
                                        ?? ($rep->citizen_carnet ? 'Carnet ' . $rep->citizen_carnet : 'Ciudadano anónimo');
                                @endphp
                                <tr class="transition-colors duration-200 hover:bg-white/30">
                                    <td class="px-4 py-3">
                                        <div class="w-24 md:w-32 flex-shrink-0 flex items-center justify-center bg-white/50 border border-white/60 rounded-2xl overflow-hidden h-32 shadow-sm">
                                            @if(!empty($rep->foto_path))
                                                <img src="{{ asset('storage/' . $rep->foto_path) }}" alt="Foto del reporte N°{{ $rep->id }}" onclick="openImageModal('{{ asset('storage/' . $rep->foto_path) }}')" class="w-full h-full object-cover cursor-pointer hover:opacity-90 transition-opacity">
                                            @else
                                                <div class="flex flex-col items-center justify-center">
                                                    <svg class="w-8 h-8 opacity-20 mb-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                    <span class="text-[9px] font-bold text-slate-400 uppercase text-center leading-tight px-2">Sin foto<br>adjunta</span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-bold text-slate-700 block">N°{{ $rep->id }}</span>
                                        <span class="text-[10px] text-slate-500 block">{{ $rep->created_at->format('d/m/Y H:i') }}</span>
                                        <span class="text-[10px] text-slate-600 block mt-1" title="{{ $reporterName }}">
                                            <span class="font-semibold text-slate-500">Por:</span> {{ $reporterName }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded px-2 py-0.5 text-[10px] font-bold uppercase bg-amber-100 text-amber-800 shadow-sm">
                                            {{ $rep->intensidad_propuesta }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <p class="text-xs text-slate-500 max-w-[14rem] whitespace-normal break-words leading-relaxed">
                                            {{ !empty($rep->description) ? $rep->description : 'Sin descripción.' }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-xs text-slate-600 max-w-[10rem] truncate mb-1.5" title="{{ !empty($rep->address) ? $rep->address : 'Ubicación GPS' }}">
                                            {{ !empty($rep->address) ? $rep->address : 'Ubicación GPS' }}
                                        </p>
                                        <div
                                            id="report-minimap-pending-{{ $rep->id }}"
                                            class="report-location-minimap"
                                            wire:ignore
                                            aria-label="Mapa de ubicación del reporte N°{{ $rep->id }}"
                                            data-lat-gps="{{ $rep->lat_gps }}"
                                            data-lng-gps="{{ $rep->long_gps }}"
                                            data-lat-rep="{{ $rep->lat_reporte }}"
                                            data-lng-rep="{{ $rep->long_reporte }}"
                                        ></div>
                                        <p class="text-[9px] text-slate-400 mt-1"><span class="text-blue-500">●</span> Usuario <span class="text-rose-500 ml-1">●</span> Evento</p>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="flex items-start gap-2 flex-nowrap">
                                            <div class="relative approve-dropdown">
                                                <button type="button" onclick="toggleApproveMenu({{ $rep->id }}, event)" class="inline-flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white pl-3 pr-2.5 py-2 text-xs rounded-lg font-bold shadow-sm transition-colors">
                                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                    <span>Aprobar</span>
                                                    <svg class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                                                </button>
                                                <div id="approve-menu-{{ $rep->id }}" class="approve-dropdown-menu hidden absolute left-0 top-full mt-1 z-20 bg-white rounded-xl shadow-lg border border-slate-200 py-1 overflow-hidden">
                                                    <button type="button" onclick="closeApproveMenus(); validarRapido({{ $rep->id }}, 'crear')" class="w-full flex items-center gap-2 px-3 py-2.5 text-xs font-semibold text-slate-700 hover:bg-emerald-50 transition-colors text-left">
                                                        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                        Como Nueva Inundación
                                                    </button>
                                                    @if(count($rep->cercanas ?? []) > 0)
                                                        @php
                                                            $reportDrawerPayload = array_merge($rep->toArray(), [
                                                                'cercanas' => collect($rep->cercanas ?? [])->values()->all(),
                                                            ]);
                                                        @endphp
                                                        <button type="button" id="review-trigger-{{ $rep->id }}" data-report="{{ json_encode($reportDrawerPayload) }}" onclick="closeApproveMenus(); openReviewDrawer(this)" class="w-full flex items-center gap-2 px-3 py-2.5 text-xs font-semibold text-slate-700 hover:bg-blue-50 transition-colors text-left border-t border-slate-100">
                                                            <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                            Como Vinculada (Ver Mapa)
                                                        </button>
                                                    @else
                                                        <span class="w-full flex items-center gap-2 px-3 py-2.5 text-xs font-medium text-slate-400 border-t border-slate-100 cursor-not-allowed" title="Sin inundaciones activas cercanas">
                                                            <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                            Como Vinculada (Ver Mapa)
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            <button type="button" onclick="validarRapido({{ $rep->id }}, 'rechazar')" class="inline-flex items-center gap-1.5 bg-white hover:bg-slate-50 text-slate-600 border border-slate-300 px-3 py-2 text-xs rounded-lg font-bold shadow-sm transition-colors">
                                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                Rechazar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500 font-medium">
                                        No hay reportes pendientes de revisión.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════════════════
                 PANEL: Reportes Rechazados
            ══════════════════════════════════════════════════════════════════ --}}
            <div class="bg-white rounded border border-gray-200 overflow-hidden mb-10 shadow-sm">
                <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                        Reportes Rechazados
                    </h2>
                    <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded text-xs font-bold">{{ count($reportesRechazados ?? []) }} registro(s)</span>
                </div>
                <div class="divide-y divide-gray-200/50">
                    @forelse ($reportesRechazados ?? [] as $rep)
                        @php
                            /** @var \App\Models\Reporte $rep */
                            $reporterName = $rep->citizen?->name
                                ?? ($rep->citizen_carnet ? 'Carnet ' . $rep->citizen_carnet : 'Ciudadano anónimo');
                        @endphp
                        <div class="p-5 flex flex-col md:flex-row gap-5 hover:bg-white/30 transition-colors">
                            <div class="w-full md:w-32 flex-shrink-0 flex items-center justify-center bg-white/50 border border-white/60 rounded-2xl overflow-hidden h-32 shadow-sm">
                                @if($rep->foto_path)
                                    <img src="{{ asset('storage/' . $rep->foto_path) }}" alt="Foto" onclick="openImageModal('{{ asset('storage/' . $rep->foto_path) }}')" class="w-full h-full object-cover cursor-pointer hover:opacity-90 transition-opacity">
                                @else
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-8 h-8 opacity-20 mb-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <span class="text-[9px] font-bold text-slate-400 uppercase text-center leading-tight px-2">Sin foto<br>adjunta</span>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="flex-grow grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm text-slate-600">
                                <div><span class="block text-[10px] font-bold text-slate-400 uppercase">ID</span><span class="font-semibold text-slate-800">N°{{ $rep->id }}</span></div>
                                <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Reportado por</span><span class="font-medium text-xs text-slate-700 truncate block" title="{{ $reporterName }}">{{ $reporterName }}</span></div>
                                <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Intensidad</span>
                                    <span class="inline-block mt-0.5 px-2 py-0.5 bg-rose-100 text-rose-700 font-bold text-[10px] rounded uppercase">{{ $rep->intensidad_propuesta }}</span>
                                </div>
                                <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Creado</span><span class="font-medium">{{ $rep->created_at->format('d/m/Y H:i') }}</span></div>
                                <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Rechazado</span><span class="font-medium">{{ $rep->updated_at->format('d/m/Y H:i') }}</span></div>

                                <div class="col-span-full">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Descripción</span>
                                    <p class="text-xs text-slate-600 whitespace-normal break-words leading-relaxed">
                                        {{ !empty($rep->description) ? $rep->description : 'Sin descripción.' }}
                                    </p>
                                </div>
                                
                                <div class="col-span-full mt-2 pt-4 border-t border-white/40">
                                    <form wire:submit.prevent="updateEstadoValidacion({{ $rep->id }})" class="flex flex-wrap items-end gap-3">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Estado</label>
                                            <select wire:model="estadoValidacionUpdates.{{ $rep->id }}" class="text-xs font-medium border-0 rounded-xl bg-white shadow-sm focus:ring-2 focus:ring-indigo-500">
                                                <option value="pendiente">Pendiente</option>
                                                <option value="aceptado">Aceptado</option>
                                                <option value="rechazado">Rechazado</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Vincular a Inundación (Si Aceptado)</label>
                                            <select wire:model="inundacionVincularIds.{{ $rep->id }}" class="text-xs font-medium border-0 rounded-xl bg-white shadow-sm focus:ring-2 focus:ring-indigo-500">
                                                <option value="">Ninguna</option>
                                                @foreach(($inundacionesActivasParaVincular ?? []) as $inundacionActiva)
                                                    @php /** @var \App\Models\Inundacion $inundacionActiva */ @endphp
                                                    <option value="{{ $inundacionActiva->id }}">
                                                        Inundación N°{{ $inundacionActiva->id }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-sm transition-colors">
                                            <span wire:loading.remove wire:target="updateEstadoValidacion({{ $rep->id }})">Guardar Cambios</span>
                                            <span wire:loading wire:target="updateEstadoValidacion({{ $rep->id }})">Guardando...</span>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="flex-shrink-0 w-[11rem] md:w-[12.5rem]">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Ubicación</span>
                                <p class="text-xs text-slate-600 font-medium mb-1.5 truncate" title="{{ !empty($rep->address) ? $rep->address : 'Ubicación GPS' }}">
                                    {{ !empty($rep->address) ? $rep->address : 'Ubicación GPS' }}
                                </p>
                                <div
                                    id="report-minimap-rejected-{{ $rep->id }}"
                                    class="report-location-minimap"
                                    wire:ignore
                                    aria-label="Mapa de ubicación del reporte rechazado N°{{ $rep->id }}"
                                    data-lat-gps="{{ $rep->lat_gps }}"
                                    data-lng-gps="{{ $rep->long_gps }}"
                                    data-lat-rep="{{ $rep->lat_reporte }}"
                                    data-lng-rep="{{ $rep->long_reporte }}"
                                ></div>
                                <p class="text-[9px] text-slate-400 mt-1"><span class="text-blue-500">●</span> Usuario <span class="text-rose-500 ml-1">●</span> Evento</p>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-slate-500 font-medium text-sm">No hay reportes rechazados.</div>
                    @endforelse
                </div>
            </div>

            @endif {{-- end authority --}}

            {{-- ══════════════════════════════════════════════════════════════════
                 PANEL: Inundaciones Terminadas (Historial)
            ══════════════════════════════════════════════════════════════════ --}}
            <div class="bg-white rounded border border-gray-200 overflow-hidden mb-10 shadow-sm">
                <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                        Historial de Inundaciones
                    </h2>
                    <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded text-xs font-bold">{{ count($inundacionesTerminadas) }} registro(s)</span>
                </div>
                <div class="overflow-x-auto p-2">
                    <table class="w-full text-sm glass-table rounded-xl overflow-hidden">
                        <thead class="text-slate-600">
                            <tr>
                                <th class="text-left font-semibold px-4 py-3 rounded-tl-xl">ID</th>
                                <th class="text-left font-semibold px-4 py-3">Intensidad</th>
                                <th class="text-left font-semibold px-4 py-3">Duración</th>
                                <th class="text-left font-semibold px-4 py-3">Distribución Q.</th>
                                <th class="text-right font-semibold px-4 py-3 rounded-tr-xl">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/50">
                            @forelse ($inundacionesTerminadas as $term)
                                @php
                                    $tid = data_get($term, 'id');
                                    $desglose = data_get($term, 'desglose_historico', ['alta' => 0, 'media' => 0, 'baja' => 0]);
                                    $totalQ = data_get($term, 'quorum_historico', 0);
                                    $intGanadora = $desglose['alta'] >= $desglose['media'] && $desglose['alta'] >= $desglose['baja']
                                        ? 'alta'
                                        : ($desglose['media'] >= $desglose['baja'] ? 'media' : 'baja');
                                @endphp
                                
                                <tr class="transition-colors duration-200 cursor-pointer hover:bg-white/30" onclick="toggleDetails('term-{{ $tid }}')">
                                    <td class="px-4 py-3 font-semibold text-slate-700">N°{{ $tid }}</td>
                                    <td class="px-4 py-3">
                                        @if($totalQ > 0)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $intGanadora === 'alta' ? 'bg-rose-100 text-rose-700' : ($intGanadora === 'media' ? 'bg-amber-100 text-amber-700' : 'bg-teal-100 text-teal-700') }}">{{ $intGanadora }}</span>
                                        @endif
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-200 text-slate-600 ml-1">Terminada</span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 font-medium">
                                        {{ data_get($term, 'duracion_texto', '—') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-1 text-[10px] font-bold uppercase">
                                            <span class="bg-rose-50 text-rose-600 px-2 py-1 rounded">A: {{ $desglose['alta'] }}</span>
                                            <span class="bg-amber-50 text-amber-600 px-2 py-1 rounded">M: {{ $desglose['media'] }}</span>
                                            <span class="bg-teal-50 text-teal-600 px-2 py-1 rounded">B: {{ $desglose['baja'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="w-8 h-8 ml-auto flex items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <svg id="chevron-term-{{ $tid }}" class="w-4 h-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </td>
                                </tr>
                                <tr id="details-term-{{ $tid }}" class="hidden bg-slate-50/50 border-t border-white/50">
                                    <td colspan="5" class="p-4">
                                        @php
                                            $repsVinc = data_get($term, 'reportes_vinculados', []);
                                        @endphp
                                        @if(count($repsVinc) > 0)
                                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2">
                                                @foreach($repsVinc as $rv)
                                                    <div class="bg-white rounded-lg p-2.5 text-xs border border-slate-100 shadow-sm flex justify-between items-center">
                                                        <div>
                                                            <span class="font-bold text-slate-700">N°{{ $rv['id'] }}</span>
                                                            <span class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase {{ $rv['intensidad_propuesta'] === 'alta' ? 'bg-rose-50 text-rose-700' : ($rv['intensidad_propuesta'] === 'media' ? 'bg-amber-50 text-amber-700' : 'bg-teal-50 text-teal-700') }}">{{ $rv['intensidad_propuesta'] }}</span>
                                                        </div>
                                                        <span class="text-slate-400 font-medium">{{ $rv['peso'] }}pts</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-sm text-slate-500 font-medium text-center">Sin reportes vinculados.</p>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500 font-medium">
                                        No hay eventos en el historial.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div> <!-- /max-w-7xl -->
    </div> <!-- /min-h-screen -->

    <!-- LEAFLET CDN -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<!-- LEAFLET HEATMAP PLUGIN -->
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script src="{{ asset('js/smart-heatmap.js') }}?v=20260623b"></script>

<!-- RUTAS SEGURAS -->
<script>
    window.ORS_API_KEY = "{{ $ors_key ?? '' }}";
</script>
<script src="{{ asset('js/safe-routing.js') }}"></script>


<script>
window.renderPendingReports = function(pendingData) {
    pendingData.forEach(report => {
        const lat = parseFloat(report.lat_reporte);
        const lng = parseFloat(report.long_reporte);
        if (isNaN(lat) || isNaN(lng)) return;

        const customIcon = L.divIcon({
            className: 'custom-leaflet-marker',
            html: '<div style="background-color: #F59E0B; width: 16px; height: 16px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 4px rgba(0,0,0,0.5); animation: pulse 2s infinite;"></div>',
            iconSize: [16, 16],
            iconAnchor: [8, 8]
        });

        const contentStr = '<div class="max-w-xs"><p class="font-semibold text-sm mb-1 text-orange-600">Reporte Pendiente</p><p class="text-xs text-gray-600 mb-2"><b>Intensidad Propuesta:</b> ' + report.intensidad_propuesta + '</p><div class="flex flex-col space-y-2 mt-2"><button onclick="validateReport(' + report.id + ', \'vincular\');" class="bg-blue-500 text-white px-2 py-1 text-xs rounded">Vincular a Cercana</button><button onclick="validateReport(' + report.id + ', \'crear\');" class="bg-green-500 text-white px-2 py-1 text-xs rounded">Crear Nueva</button><button onclick="validateReport(' + report.id + ', \'rechazar\');" class="bg-red-500 text-white px-2 py-1 text-xs rounded">Rechazar</button></div></div>';

        const marker = L.marker([lat, lng], { icon: customIcon }).bindPopup(contentStr, { minWidth: 200 });
        if (window.mapObj) window.mapObj.addLayer(marker);
    });
};

window.validateReport = function(id, action) {
    let body = { action: action };
    if (action === 'vincular') {
        const inundacion_id = prompt('Ingrese el ID de la inundación a la que desea vincular:');
        if (!inundacion_id) return;
        body.inundacion_id = inundacion_id;
    }

    fetch('/api/reportes/' + id + '/validar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': 'Bearer {{ session("api_token") }}'
        },
        body: JSON.stringify(body)
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        location.reload();
    });
};
</script>


<script>
        const reportMinimaps = new Map();

        function destroyReportMinimap(id) {
            if (reportMinimaps.has(id)) {
                reportMinimaps.get(id).remove();
                reportMinimaps.delete(id);
            }
        }

        function initReportLocationMinimap(containerEl, coords) {
            const id = containerEl.id;
            if (!id) return;

            destroyReportMinimap(id);

            const latGps = parseFloat(coords.latGps);
            const lngGps = parseFloat(coords.lngGps);
            const latRep = parseFloat(coords.latRep);
            const lngRep = parseFloat(coords.lngRep);

            const hasUser = !isNaN(latGps) && !isNaN(lngGps);
            const hasEvent = !isNaN(latRep) && !isNaN(lngRep);

            if (!hasUser && !hasEvent) {
                containerEl.innerHTML = '<div class="flex items-center justify-center h-full text-[10px] text-slate-400 font-medium px-2 text-center">Sin coordenadas</div>';
                return;
            }

            containerEl.innerHTML = '';

            const centerLat = hasEvent ? latRep : latGps;
            const centerLng = hasEvent ? lngRep : lngGps;

            const map = L.map(containerEl, {
                zoomControl: false,
                attributionControl: false,
                dragging: true,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false,
            }).setView([centerLat, centerLng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

            const bounds = L.latLngBounds();
            let addedUser = false;
            let addedEvent = false;

            if (hasUser) {
                const userIcon = L.divIcon({
                    className: 'custom-leaflet-user',
                    html: '<div style="background-color:#3b82f6;width:10px;height:10px;border-radius:50%;border:2px solid white;box-shadow:0 0 5px rgba(0,0,0,0.4);"></div>',
                    iconSize: [10, 10],
                    iconAnchor: [5, 5],
                });
                L.marker([latGps, lngGps], { icon: userIcon })
                    .bindTooltip('Ubicación del Reportero (GPS)', { direction: 'top', className: 'text-[10px]' })
                    .addTo(map);
                bounds.extend([latGps, lngGps]);
                addedUser = true;
            }

            if (hasEvent) {
                const eventIcon = L.divIcon({
                    className: 'custom-leaflet-event',
                    html: '<div style="background-color:#f43f5e;width:12px;height:12px;border-radius:50%;border:2px solid white;box-shadow:0 0 8px rgba(0,0,0,0.5);"></div>',
                    iconSize: [12, 12],
                    iconAnchor: [6, 6],
                });
                L.marker([latRep, lngRep], { icon: eventIcon })
                    .bindTooltip('Punto de Evento Reportado', { direction: 'top', className: 'text-[10px] font-bold' })
                    .addTo(map);
                bounds.extend([latRep, lngRep]);
                addedEvent = true;
            }

            if (addedUser && addedEvent && (latGps !== latRep || lngGps !== lngRep)) {
                L.polyline([[latGps, lngGps], [latRep, lngRep]], {
                    color: '#94a3b8',
                    weight: 2,
                    dashArray: '4, 4',
                }).addTo(map);

                const dist = map.distance([latGps, lngGps], [latRep, lngRep]);
                L.marker([(latGps + latRep) / 2, (lngGps + lngRep) / 2], {
                    icon: L.divIcon({
                        className: 'dist-label',
                        html: '<div style="background:rgba(255,255,255,0.9);color:#64748b;font-size:8px;font-weight:bold;padding:1px 3px;border-radius:3px;border:1px solid #cbd5e1;">' + Math.round(dist) + 'm</div>',
                        iconSize: [36, 14],
                        iconAnchor: [18, 7],
                    }),
                }).addTo(map);
            }

            if (bounds.isValid()) {
                map.fitBounds(bounds, { padding: [8, 8] });
            }

            reportMinimaps.set(id, map);
            setTimeout(() => map.invalidateSize(), 100);
        }

        function initAllReportMinimaps(force = false) {
            document.querySelectorAll('.report-location-minimap').forEach((el) => {
                if (!el.id) return;
                const hasMap = el.querySelector('.leaflet-container');
                if (force || !hasMap) {
                    initReportLocationMinimap(el, {
                        latGps: el.dataset.latGps,
                        lngGps: el.dataset.lngGps,
                        latRep: el.dataset.latRep,
                        lngRep: el.dataset.lngRep,
                    });
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            initAllReportMinimaps();
        });

        document.addEventListener('livewire:initialized', () => {
            initAllReportMinimaps();
            Livewire.on('refreshReports', () => {
                setTimeout(() => initAllReportMinimaps(true), 100);
            });
            Livewire.hook('morph.updated', () => {
                setTimeout(() => initAllReportMinimaps(false), 50);
            });
        });

        function toggleApproveMenu(id, event) {
            event.stopPropagation();
            const menu = document.getElementById('approve-menu-' + id);
            const isOpen = menu && !menu.classList.contains('hidden');
            closeApproveMenus();
            if (menu && !isOpen) {
                menu.classList.remove('hidden');
            }
        }

        function closeApproveMenus() {
            document.querySelectorAll('[id^="approve-menu-"]').forEach((el) => el.classList.add('hidden'));
        }

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.approve-dropdown')) {
                closeApproveMenus();
            }
        });

        function toggleDetails(id) {
            const el = document.getElementById('details-' + id);
            const icon = document.getElementById('chevron-' + id);
            if (el && el.classList.contains('hidden')) {
                el.classList.remove('hidden');
                if(icon) icon.classList.add('rotate-180');
            } else if (el) {
                el.classList.add('hidden');
                if(icon) icon.classList.remove('rotate-180');
            }
        }

        function validarRapido(id, action, inundacion_id = null) {
            let body = { action: action };
            if (action === 'vincular') {
                if (!inundacion_id) return;
                body.inundacion_id = inundacion_id;
            }

            Swal.fire({
                title: '¿Estás seguro?',
                text: '¿Deseas ' + action + ' este reporte?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (!result.isConfirmed) return;

                fetch('/api/reportes/' + id + '/validar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': 'Bearer {{ session("api_token") }}'
                    },
                    body: JSON.stringify(body)
                })
                .then(res => res.json())
                .then(data => {
                    Swal.fire('¡Listo!', data.message, 'success');
                    Livewire.dispatch('refreshReports');
                })
                .catch(() => {
                    Swal.fire('Error', 'Ocurrió un error al procesar la solicitud.', 'error');
                });
            });
        }



        function openImageModal(src) {
            closeApproveMenus();
            const modal = document.getElementById('imageModal');
            const img = document.getElementById('modalImage');
            img.src = src;
            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.remove('opacity-0'), 10);
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.add('opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.getElementById('modalImage').src = '';
            }, 300);
        }
    </script>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 z-[10000] hidden bg-slate-900/90 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0" onclick="closeImageModal()">
        <div class="relative max-w-5xl w-full max-h-[90vh] flex flex-col items-center justify-center" onclick="event.stopPropagation()">
            <button onclick="closeImageModal()" class="absolute -top-12 right-0 sm:-right-12 sm:top-0 text-white hover:text-rose-400 bg-white/10 hover:bg-white/20 rounded-full p-2 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <img id="modalImage" src="" alt="Report Image" class="max-w-full max-h-[85vh] object-contain rounded-2xl shadow-2xl border border-white/20">
        </div>
    </div>

    {{-- Drawer de Revisión de Reporte --}}
    <div id="review-drawer-backdrop" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[2500] hidden transition-opacity duration-300 opacity-0" onclick="closeReviewDrawer()"></div>
    <div id="review-drawer" class="fixed inset-y-0 right-0 w-full max-w-xl bg-white shadow-[0_0_40px_rgba(0,0,0,0.5)] z-[2501] flex flex-col translate-x-full transition-transform duration-300 ease-in-out border-l border-gray-200">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-white">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2" id="drawer-title">
                Revisar Reporte
            </h3>
            <button type="button" onclick="closeReviewDrawer()" class="text-gray-400 hover:text-gray-600 transition-colors bg-gray-100 hover:bg-gray-200 p-2 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar bg-white flex flex-col">
            <!-- Mapa de revisión -->
            <div class="w-full h-[350px] relative border-b border-gray-200 shrink-0" wire:ignore>
                <div id="review-map" class="w-full h-full z-0"></div>
            </div>

            <div class="p-6">
                <!-- Info del Reporte -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Información Reportada</span>
                            <h4 class="font-bold text-slate-800" id="drawer-desc">Descripción del problema</h4>
                            <p class="text-xs text-slate-500 mt-1" id="drawer-address">Dirección</p>
                        </div>
                        <span id="drawer-intensity" class="px-2 py-1 rounded text-[10px] font-bold uppercase">INTENSIDAD</span>
                    </div>
                </div>

                <!-- Inundaciones Cercanas Sugeridas -->
                <div>
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Inundaciones Cercanas (Sugeridas para Vincular)</h4>
                    <div id="drawer-cercanas-list" class="space-y-3">
                        <!-- Cards dinámicas -->
                    </div>
                </div>
            </div>
        </div>

        <div class="p-4 bg-white border-t border-slate-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
            <button id="btn-vincular" disabled class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 disabled:text-slate-500 disabled:cursor-not-allowed text-white py-3 rounded-lg text-sm font-bold shadow transition-colors flex justify-center items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                Vincular a Inundación Seleccionada
            </button>
        </div>
    </div>

    <script>
        let reviewDrawerReportId = null;
        let reviewMap = null;
        let reviewSelectedFloodId = null;
        let reviewPolygons = {};

        function openReviewDrawer(btnElement) {
            const reportData = JSON.parse(btnElement.getAttribute('data-report'));
            const id = reportData.id;
            reviewDrawerReportId = id;
            reviewSelectedFloodId = null;

            document.getElementById('drawer-title').innerText = 'Opciones de Vinculación - Reporte N°' + id;
            document.getElementById('drawer-desc').innerText = reportData.description || 'Sin descripción detallada';
            document.getElementById('drawer-address').innerText = reportData.address || 'Ubicación GPS generada';

            let intensityColor = 'bg-slate-100 text-slate-700';
            if (reportData.intensidad_propuesta === 'alta') intensityColor = 'bg-rose-100 text-rose-700';
            else if (reportData.intensidad_propuesta === 'media') intensityColor = 'bg-amber-100 text-amber-700';
            else if (reportData.intensidad_propuesta === 'baja') intensityColor = 'bg-teal-100 text-teal-700';

            const intBadge = document.getElementById('drawer-intensity');
            intBadge.className = `px-2 py-1 rounded text-[10px] font-bold uppercase ${intensityColor}`;
            intBadge.innerText = reportData.intensidad_propuesta || 'N/A';

            document.getElementById('review-drawer-backdrop').classList.remove('hidden');
            requestAnimationFrame(() => {
                document.getElementById('review-drawer-backdrop').classList.remove('opacity-0');
                document.getElementById('review-drawer').classList.remove('translate-x-full');

                setTimeout(() => {
                    initReviewMap(reportData);
                }, 350); // esperar la animación
            });

            const listContainer = document.getElementById('drawer-cercanas-list');
            listContainer.innerHTML = '';

            if (reportData.cercanas && reportData.cercanas.length > 0) {
                reportData.cercanas.forEach(flood => {
                    const card = document.createElement('div');
                    card.id = `flood-card-${flood.id}`;
                    card.className = `p-3 rounded-xl border-2 border-slate-200 bg-white shadow-sm cursor-pointer hover:border-blue-300 transition-all flex justify-between items-center`;
                    card.innerHTML = `
                        <div>
                            <span class="text-xs font-bold text-slate-800">Inundación N°${flood.id}</span>
                            <p class="text-[10px] text-slate-500 mt-1">Intensidad ${flood.intensidad_calculada || flood.intensidad || 'N/A'}</p>
                        </div>
                    `;

                    card.onclick = () => selectFloodToLink(flood.id);
                    card.onmouseenter = () => highlightFloodPolygon(flood.id, true);
                    card.onmouseleave = () => highlightFloodPolygon(flood.id, false);

                    listContainer.appendChild(card);
                });
            } else {
                listContainer.innerHTML = '<p class="text-xs text-slate-400 italic bg-slate-100 p-4 rounded-lg text-center">No se detectaron inundaciones activas cercanas.</p>';
            }

            document.getElementById('btn-vincular').onclick = () => {
                if (reviewSelectedFloodId) {
                    closeReviewDrawer();
                    validarRapido(id, 'vincular', reviewSelectedFloodId);
                }
            };

            updateVincularButton();
        }

        function closeReviewDrawer() {
            document.getElementById('review-drawer-backdrop').classList.add('opacity-0');
            document.getElementById('review-drawer').classList.add('translate-x-full');
            setTimeout(() => document.getElementById('review-drawer-backdrop').classList.add('hidden'), 300);
        }

        function initReviewMap(report) {
            // Reconstruimos el mapa en cada apertura para evitar estados obsoletos:
            // Livewire puede morfar el DOM entre aperturas y dejar el mapa "muerto"
            // (desconectado de la vista), lo que impedía dibujar los polígonos.
            if (reviewMap) {
                reviewMap.remove();
                reviewMap = null;
            }
            reviewPolygons = {};

            reviewMap = L.map('review-map', { zoomControl: false }).setView([-17.7833, -63.1821], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(reviewMap);
            L.control.zoom({ position: 'topright' }).addTo(reviewMap);

            const latRep = parseFloat(report.lat_reporte);
            const lngRep = parseFloat(report.long_reporte);
            const latUser = parseFloat(report.lat_gps);
            const lngUser = parseFloat(report.long_gps);

            const bounds = L.latLngBounds();
            let addedUser = false;
            let addedEvent = false;

            if (!isNaN(latUser) && !isNaN(lngUser)) {
                const userIcon = L.divIcon({
                    className: 'custom-leaflet-user',
                    html: `<div style="background-color:#3b82f6;width:12px;height:12px;border-radius:50%;border:2px solid white;box-shadow:0 0 5px rgba(0,0,0,0.4);"></div>`,
                    iconSize: [12, 12], iconAnchor: [6, 6]
                });
                L.marker([latUser, lngUser], { icon: userIcon }).bindTooltip("Ubicación del Reportero (GPS)").addTo(reviewMap);
                bounds.extend([latUser, lngUser]);
                addedUser = true;
            }

            if (!isNaN(latRep) && !isNaN(lngRep)) {
                const eventIcon = L.divIcon({
                    className: 'custom-leaflet-event',
                    html: `<div style="background-color:#f43f5e;width:16px;height:16px;border-radius:50%;border:2px solid white;box-shadow:0 0 10px rgba(0,0,0,0.5);animation:pulse 2s infinite;"></div>`,
                    iconSize: [16, 16], iconAnchor: [8, 8]
                });
                L.marker([latRep, lngRep], { icon: eventIcon }).bindTooltip("Punto de Evento Reportado", { permanent: true, direction: "top", className: "text-xs font-bold" }).addTo(reviewMap);
                bounds.extend([latRep, lngRep]);
                addedEvent = true;
            }

            if (addedUser && addedEvent && (latUser !== latRep || lngUser !== lngRep)) {
                L.polyline([[latUser, lngUser], [latRep, lngRep]], {
                    color: '#94a3b8', weight: 2, dashArray: '4, 4'
                }).addTo(reviewMap);

                const dist = reviewMap.distance([latUser, lngUser], [latRep, lngRep]);
                L.marker([(latUser + latRep) / 2, (lngUser + lngRep) / 2], {
                    icon: L.divIcon({
                        className: 'dist-label',
                        html: `<div style="background:rgba(255,255,255,0.9);color:#64748b;font-size:9px;font-weight:bold;padding:1px 4px;border-radius:4px;border:1px solid #cbd5e1;box-shadow:0 1px 2px rgba(0,0,0,0.1);">${Math.round(dist)}m</div>`,
                        iconSize: [40, 16], iconAnchor: [20, 8]
                    })
                }).addTo(reviewMap);
            }

            if (report.cercanas && report.cercanas.length > 0) {
                const baseStyle = { color: '#cbd5e1', fillColor: '#94a3b8', fillOpacity: 0.2, weight: 2, dashArray: '5,5' };

                report.cercanas.forEach(flood => {
                    const latC = parseFloat(flood.latitud);
                    const lngC = parseFloat(flood.longitud);

                    // El polígono NO viaja en data-report (sería enorme). Lo buscamos en
                    // window.floodReports (ya cargado por el mapa) por id.
                    let floodGeom = null;
                    if (Array.isArray(window.floodReports)) {
                        floodGeom = window.floodReports.find(f => String(f.id) === String(flood.id));
                    }
                    const polygonCoords = floodGeom ? floodGeom.polygon_coords : null;

                    // Normalizamos igual que el mapa principal: soporta anillo simple,
                    // multipolígono (inundaciones unificadas) y pares [lat,lng] u objetos {lat,lng}.
                    let rings = (window.normalizePolygonRings)
                        ? window.normalizePolygonRings(polygonCoords)
                        : [];

                    // Algunas inundaciones guardan polygon_coords como una grilla densa de
                    // muestreo (miles de puntos sin orden de contorno). Dibujarla como
                    // polígono produce una forma auto-intersectada. En esos casos usamos la
                    // envolvente convexa para representar el área de forma limpia.
                    rings = rings.map(r => r.length > 400 ? convexHullLatLng(r) : r)
                                 .filter(r => r && r.length >= 3);

                    let shape = null;
                    if (rings.length === 1) {
                        shape = L.polygon(rings[0], baseStyle);
                    } else if (rings.length > 1) {
                        // multipolígono: cada anillo es un área separada
                        shape = L.polygon(rings.map(r => [r]), baseStyle);
                    } else if (!isNaN(latC) && !isNaN(lngC)) {
                        shape = L.circle([latC, lngC], { radius: 150, ...baseStyle });
                    }

                    if (!shape) return;

                    shape.addTo(reviewMap);
                    shape.on('click', () => selectFloodToLink(flood.id));
                    shape.bindTooltip(`Inundación N°${flood.id}`, { className: 'text-xs font-bold' });

                    reviewPolygons[flood.id] = shape;
                    bounds.extend(shape.getBounds());
                });
            }

            if (bounds.isValid()) {
                reviewMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
            }

            setTimeout(() => {
                reviewMap.invalidateSize();
                if (bounds.isValid()) {
                    reviewMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
                }
            }, 100);
        }

        function highlightFloodPolygon(floodId, isHover) {
            if (floodId === reviewSelectedFloodId) return;
            const poly = reviewPolygons[floodId];
            if (poly) {
                if (isHover) {
                    poly.setStyle({ color: '#60a5fa', fillColor: '#93c5fd', fillOpacity: 0.4, weight: 2 });
                } else {
                    poly.setStyle({ color: '#cbd5e1', fillColor: '#94a3b8', fillOpacity: 0.2, weight: 2 });
                }
            }
        }

        function selectFloodToLink(floodId) {
            // Reset de TODOS los polígonos dibujados al estilo base.
            Object.keys(reviewPolygons).forEach(idKey => {
                reviewPolygons[idKey].setStyle({ color: '#cbd5e1', fillColor: '#94a3b8', fillOpacity: 0.2, weight: 2 });
            });

            // Reset de TODAS las tarjetas recorriendo el DOM (no reviewPolygons),
            // así se limpian aunque una inundación no tenga polígono en el mapa.
            document.querySelectorAll('#drawer-cercanas-list [id^="flood-card-"]').forEach(card => {
                card.classList.remove('border-blue-500', 'bg-blue-50');
                card.classList.add('border-slate-200', 'bg-white');
            });

            reviewSelectedFloodId = floodId;
            if (reviewPolygons[floodId]) {
                reviewPolygons[floodId].setStyle({ color: '#2563eb', fillColor: '#3b82f6', fillOpacity: 0.5, weight: 3 });
            }
            const selectedCard = document.getElementById(`flood-card-${floodId}`);
            if (selectedCard) {
                selectedCard.classList.remove('border-slate-200', 'bg-white');
                selectedCard.classList.add('border-blue-500', 'bg-blue-50');
            }

            updateVincularButton();
        }

        function updateVincularButton() {
            const btn = document.getElementById('btn-vincular');
            btn.disabled = !reviewSelectedFloodId;
        }

        // Envolvente convexa (monotone chain) para puntos [lat, lng].
        // Tratamos lng como X y lat como Y. Devuelve el anillo del casco.
        function convexHullLatLng(points) {
            if (!Array.isArray(points) || points.length < 3) return points;
            const pts = points.slice().sort((a, b) => (a[1] - b[1]) || (a[0] - b[0]));
            const cross = (o, a, b) => (a[1] - o[1]) * (b[0] - o[0]) - (a[0] - o[0]) * (b[1] - o[1]);

            const lower = [];
            for (const p of pts) {
                while (lower.length >= 2 && cross(lower[lower.length - 2], lower[lower.length - 1], p) <= 0) lower.pop();
                lower.push(p);
            }
            const upper = [];
            for (let i = pts.length - 1; i >= 0; i--) {
                const p = pts[i];
                while (upper.length >= 2 && cross(upper[upper.length - 2], upper[upper.length - 1], p) <= 0) upper.pop();
                upper.push(p);
            }
            lower.pop();
            upper.pop();
            return lower.concat(upper);
        }
    </script>
</div>
