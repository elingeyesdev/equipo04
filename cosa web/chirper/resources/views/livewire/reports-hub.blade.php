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

    @include('components.reports.styles')

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

    <x-reports-map :reports="$inundacionesActivas" :pendingReports="$reportesPendientes ?? []" :showRouting="true" :fetchPending="$role === 'authority'" />


            @if (!empty($error))
                <div class="mb-6 rounded border border-red-300 bg-red-100 p-4 text-sm flex items-center gap-3">
                    <span class="text-red-800 font-medium">{{ $error }}</span>
                </div>
            @endif

            @if ($role === 'citizen' || $carnet !== '')
                <div class="bg-white rounded border border-gray-200 overflow-hidden mb-10 shadow-sm">
                    <div class="px-6 py-5 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3 bg-gray-50">
                        <h2 class="text-xl font-semibold text-gray-800">Mis reportes enviados</h2>
                        <div class="flex flex-wrap items-center gap-3">
                            @if(($totalMisReportes ?? 0) > 0)
                                <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded text-xs font-bold">{{ $totalMisReportes }} registro(s)</span>
                            @endif
                            <a href="{{ route('reports.mis', [], false) }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-indigo-600 hover:text-indigo-800">
                                Ver todos mis reportes
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        </div>
                    </div>
                    @if(($totalMisReportes ?? 0) > 2)
                        <p class="px-6 pt-4 text-xs text-slate-500">Mostrando los 2 reportes más recientes.</p>
                    @endif
                    <div class="overflow-x-auto p-2">
                        <table class="w-full text-sm glass-table rounded-xl overflow-hidden">
                            <thead class="text-slate-600">
                                <tr>
                                    <th class="text-left font-semibold px-4 py-3 rounded-tl-xl">ID</th>
                                    <th class="text-left font-semibold px-4 py-3">Estado</th>
                                    <th class="text-left font-semibold px-4 py-3">Intensidad</th>
                                    <th class="text-left font-semibold px-4 py-3">Detalle</th>
                                    <th class="text-left font-semibold px-4 py-3 rounded-tr-xl">Actualización</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/50">
                                @forelse($misReportes ?? [] as $rep)
                                    @php /** @var \App\Models\Reporte $rep */ @endphp
                                    <tr class="transition-colors duration-200">
                                        <td class="px-4 py-3 font-semibold text-slate-700">N°{{ $rep->id }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded px-2.5 py-1 text-xs font-bold
                                                {{ (string) $rep->estado_validacion === 'pendiente' ? 'bg-yellow-100 text-yellow-800' : ((string) $rep->estado_validacion === 'aceptado' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') }}">
                                                {{ ucfirst((string) $rep->estado_validacion) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 font-medium text-slate-600">
                                            {{ ucfirst((string) $rep->intensidad_propuesta) }}
                                            @if($rep->fueAjustado())
                                                <span class="block text-[11px] text-indigo-600 mt-0.5">Validada: {{ ucfirst((string) $rep->intensidad_validada) }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-slate-600 text-xs max-w-[14rem]">
                                            @if((string) $rep->estado_validacion === 'rechazado')
                                                {{ $rep->motivoRechazo?->label_ciudadano ?? 'Sin motivo registrado.' }}
                                            @elseif($rep->fueAjustado())
                                                {{ $rep->ajuste_comentario }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-slate-500">{{ optional($rep->updated_at)->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-slate-500 text-center font-medium">Aún no has enviado reportes. ¡Tu reporte salva vidas!</td>
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
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                            Pendientes de Validación
                        </h2>
                        @if(($totalPendientes ?? 0) > 5)
                            <p class="text-xs text-slate-500 mt-1">Mostrando 5 de {{ $totalPendientes }} en esta vista.</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('reports.pendientes', [], false) }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-indigo-600 hover:text-indigo-800">
                            Ver cola completa
                            @if(($totalPendientes ?? 0) > 0)
                                <span class="text-xs font-bold text-orange-700 bg-orange-100 px-2 py-0.5 rounded">({{ $totalPendientes }})</span>
                            @endif
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                        <a href="#map-container" onclick="document.getElementById('map-container').scrollIntoView({behavior: 'smooth'})" class="inline-flex items-center gap-1.5 bg-orange-600 text-white font-bold px-5 py-2 rounded shadow-md hover:bg-orange-700 transition-colors text-sm">Validar en el Mapa</a>
                    </div>
                </div>
                @include('components.reports.pending-table', ['reportesPendientes' => $reportesPendientes ?? []])
            </div>

            {{-- ══════════════════════════════════════════════════════════════════
                 PANEL: Reportes Rechazados
            ══════════════════════════════════════════════════════════════════ --}}
            <div class="bg-white rounded border border-gray-200 overflow-hidden mb-10 shadow-sm">
                <div class="px-6 py-5 border-b border-gray-200 flex flex-wrap justify-between items-center gap-3 bg-gray-50">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                            Reportes Rechazados
                        </h2>
                        @if(($totalRechazados ?? 0) > 5)
                            <p class="text-xs text-slate-500 mt-1">Mostrando 5 de {{ $totalRechazados }} en esta vista.</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded text-xs font-bold">{{ $totalRechazados ?? count($reportesRechazados ?? []) }} registro(s)</span>
                        <a href="{{ route('reports.rechazados', [], false) }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-indigo-600 hover:text-indigo-800">
                            Ver todos los rechazados
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                    </div>
                </div>
                @include('components.reports.rejected-table', ['reportesRechazados' => $reportesRechazados ?? []])
            </div>

            @include('components.reports.modals.historial')
            @include('components.reports.modals.modificar-rechazado')

            @endif {{-- end authority --}}

            <div class="bg-white rounded border border-gray-200 overflow-hidden mb-10 shadow-sm">
                <div class="px-6 py-5 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3 bg-gray-50">
                    <h2 class="text-xl font-semibold text-gray-800">Historial de Inundaciones</h2>
                    <a href="{{ route('reports.historial', [], false) }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-indigo-600 hover:text-indigo-800">
                        Ver historial completo
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>
                <div class="px-6 py-4 text-sm text-slate-600">
                    Consulta inundaciones terminadas, duración y distribución de quórum en la sección dedicada.
                </div>
            </div>

        </div> <!-- /max-w-7xl -->
    </div> <!-- /min-h-screen -->

    @include('components.reports.validation-scripts')
</div>
