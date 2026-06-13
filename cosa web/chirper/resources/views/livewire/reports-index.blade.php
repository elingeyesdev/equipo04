<div>
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
    </style>

    <!-- Main Container with custom gradient background -->
    <div class="min-h-screen bg-gray-100 -m-4 sm:-m-6 lg:-m-8 p-4 sm:p-6 lg:p-8">
        
        <div class="max-w-7xl mx-auto">
            
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-10 gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-blue-800">Registro de Inundaciones</h1>
                    <p class="mt-2 text-sm font-medium text-slate-600">Centro de Monitoreo y Validación de Eventos Hidrológicos.</p>
                </div>
                <a href="{{ route('reports.create', [], false) }}" class="group relative inline-flex items-center gap-2 rounded bg-blue-700 px-6 py-2.5 text-white font-semibold shadow-md hover:bg-blue-800 transition-colors">
                    <svg class="w-5 h-5 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span class="relative z-10">Nuevo Reporte</span>
                </a>
            </div>

    <!-- Map Filters -->
    <div class="mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200 mt-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">Buscar Reportes por Ubicación</h3>
        <x-location-filter formAction="{{ route('reports.index', [], false) }}" />
    </div>

    <x-reports-map :reports="$inundacionesActivas" :pendingReports="$reportesPendientes ?? []" :showRouting="true" />


            @if (session('error') || !empty($error))
                <div class="mb-6 rounded border border-red-300 bg-red-100 p-4 text-sm flex items-center gap-3">
                    <span class="text-red-800 font-medium">{{ session('error') ?? $error ?? '' }}</span>
                </div>
            @endif

            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-teal-200/60 bg-teal-50/80 backdrop-blur-sm p-4 text-sm shadow-sm flex items-center gap-3">
                    <span class="text-teal-500 text-xl"></span>
                    <span class="text-teal-800 font-medium">{{ session('success') }}</span>
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
                                    <tr class="transition-colors duration-200">
                                        <td class="px-4 py-3 font-semibold text-slate-700">N°{{ $rep->id }}</td>
                                        <td class="px-4 py-3">
                                            @php($estadoVal = (string) $rep->estado_validacion)
                                            <span class="inline-flex items-center rounded px-2.5 py-1 text-xs font-bold
                                                {{ $estadoVal === 'pendiente' ? 'bg-yellow-100 text-yellow-800' : ($estadoVal === 'aceptado' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') }}">
                                                {{ ucfirst($estadoVal) }}
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
                                @php($id = data_get($inundacion, 'id'))
                                @php($estado = data_get($inundacion, 'estado', ''))
                                @php($int = data_get($inundacion, 'intensidad_calculada', null))
                                @php($quorum = data_get($inundacion, 'quorum_total', 0))
                                @php($confirmada = data_get($inundacion, 'esta_confirmada', false))
                                
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
                                                    @php($reportesActivos = data_get($inundacion, 'reportes_activos', []))
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
                                                    @php($reportesInactivos = data_get($inundacion, 'reportes_inactivos', []))
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
                                                        @php($desglose = data_get($inundacion, 'desglose_puntos', ['alta'=>0,'media'=>0,'baja'=>0]))
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

            @php($currentPage = (int) ($meta['current_page'] ?? 1))
            @php($lastPage = (int) ($meta['last_page'] ?? 1))

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
                                <th class="text-left font-semibold px-4 py-3 rounded-tl-xl w-16">Foto</th>
                                <th class="text-left font-semibold px-4 py-3">Reporte N°</th>
                                <th class="text-left font-semibold px-4 py-3">Intensidad</th>
                                <th class="text-left font-semibold px-4 py-3">Detalles</th>
                                <th class="text-right font-semibold px-4 py-3 rounded-tr-xl">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/50">
                            @forelse ($reportesPendientes ?? [] as $rep)
                                <tr class="transition-colors duration-200 hover:bg-white/30">
                                    <td class="px-4 py-3">
                                        @if(!empty($rep->foto_path))
                                            <img src="{{ asset('storage/' . $rep->foto_path) }}" alt="Foto" onclick="openImageModal('{{ asset('storage/' . $rep->foto_path) }}')" class="w-10 h-10 object-cover rounded-lg shadow-sm border border-gray-200 cursor-pointer hover:scale-105 transition-transform">
                                        @else
                                            <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-bold text-slate-700 block">N°{{ $rep->id }}</span>
                                        <span class="text-[10px] text-slate-500">{{ $rep->created_at->format('d/m/Y H:i') }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded px-2 py-0.5 text-[10px] font-bold uppercase bg-amber-100 text-amber-800 shadow-sm">
                                            {{ $rep->intensidad_propuesta }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-xs text-slate-600 max-w-xs truncate" title="{{ !empty($rep->address) ? $rep->address : 'Ubicación GPS' }}">
                                            <span class="font-bold text-slate-700">Dir:</span> {{ !empty($rep->address) ? $rep->address : 'Ubicación GPS' }}
                                        </p>
                                        <p class="text-xs text-slate-500 max-w-xs truncate" title="{{ !empty($rep->description) ? $rep->description : 'Sin descripción.' }}">
                                            {{ !empty($rep->description) ? $rep->description : 'Sin descripción.' }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2 flex-wrap min-w-[280px]">
                                            <button onclick="validarRapido({{ $rep->id }}, 'crear')" class="bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-1.5 text-xs rounded-lg font-bold shadow-sm transition-colors">
                                                Aprobar como Nueva Inundación
                                            </button>
                                            
                                            @if(count($rep->cercanas ?? []) > 0)
                                                <div class="flex border border-blue-200 rounded-lg overflow-hidden shadow-sm h-[28px]">
                                                    <select id="select-vincular-{{ $rep->id }}" class="text-[10px] border-0 py-0 pl-2 pr-6 bg-blue-50 text-blue-900 focus:ring-0 font-medium w-24">
                                                        @foreach($rep->cercanas as $activa)
                                                            <option value="{{ $activa->id }}">Inundación N°{{ $activa->id }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button onclick="validarRapido({{ $rep->id }}, 'vincular', document.getElementById('select-vincular-{{ $rep->id }}').value)" class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-0 text-[10px] font-bold transition-colors">
                                                        Vincular
                                                    </button>
                                                </div>
                                            @endif
                                            
                                            <button onclick="validarRapido({{ $rep->id }}, 'rechazar')" class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 px-3 py-1.5 text-xs rounded-lg font-bold shadow-sm transition-colors">
                                                Rechazar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500 font-medium">
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
                                <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Intensidad</span>
                                    <span class="inline-block mt-0.5 px-2 py-0.5 bg-rose-100 text-rose-700 font-bold text-[10px] rounded uppercase">{{ $rep->intensidad_propuesta }}</span>
                                </div>
                                <div class="col-span-2"><span class="block text-[10px] font-bold text-slate-400 uppercase">Dirección</span><span class="font-medium text-xs">{{ !empty($rep->address) ? $rep->address : 'Ubicación GPS' }}</span></div>
                                <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Creado</span><span class="font-medium">{{ $rep->created_at->format('d/m/Y H:i') }}</span></div>
                                <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Rechazado</span><span class="font-medium">{{ $rep->updated_at->format('d/m/Y H:i') }}</span></div>
                                
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
                                @php($tid = data_get($term, 'id'))
                                @php($desglose = data_get($term, 'desglose_historico', ['alta'=>0,'media'=>0,'baja'=>0]))
                                @php($totalQ = data_get($term, 'quorum_historico', 0))
                                @php($intGanadora = $desglose['alta'] >= $desglose['media'] && $desglose['alta'] >= $desglose['baja'] ? 'alta' : ($desglose['media'] >= $desglose['baja'] ? 'media' : 'baja'))
                                
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
                                        @php($repsVinc = data_get($term, 'reportes_vinculados', []))
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
<script src="{{ asset('js/smart-heatmap.js') }}"></script>

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
        document.addEventListener('alpine:init', () => {
            Alpine.data('minimapComponent', (latGps, lngGps, latRep, lngRep) => ({
                map: null,
                init() {
                    const lGps = parseFloat(latGps);
                    const lnGps = parseFloat(lngGps);
                    const lRep = parseFloat(latRep);
                    const lnRep = parseFloat(lngRep);
                    
                    setTimeout(() => {
                        this.map = L.map(this.$el, {
                            zoomControl: true, attributionControl: false, dragging: true, scrollWheelZoom: true, doubleClickZoom: true
                        }).setView([lRep, lnRep], 15);
                        
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);
                        
                        const iconGps = L.divIcon({ className: '', html: '<div style=\'background-color: rgba(245,158,11,0.6); width: 14px; height: 14px; border-radius: 50%; border: 2px solid rgba(245,158,11,0.9); box-shadow: 0 0 8px rgba(0,0,0,0.4);\'></div>', iconSize: [14, 14], iconAnchor: [7, 7] });
                        L.marker([lGps, lnGps], { icon: iconGps })
                            .bindTooltip("Ubicacion del usuario (GPS)", { direction: 'top' })
                            .bindPopup("<div class='text-xs'><b>GPS del Ciudadano</b><br>Desde aquí se tomó la foto o se envió el reporte.</div>")
                            .addTo(this.map);
                            
                        L.circle([lGps, lnGps], { radius: 500, color: '#3B82F6', fillColor: '#3B82F6', fillOpacity: 0.1, weight: 1.5, dashArray: '4 4' }).addTo(this.map);
                        
                        const iconRep = L.divIcon({ className: '', html: '<div style=\'background-color: #EF4444; width: 18px; height: 18px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px rgba(239,68,68,0.8);\'></div>', iconSize: [18, 18], iconAnchor: [9, 9] });
                        L.marker([lRep, lnRep], { icon: iconRep })
                            .bindTooltip("Punto Reportado", { direction: 'top', className: 'font-bold text-rose-600' })
                            .bindPopup("<div class='text-xs text-rose-700'><b>Punto Reportado</b><br>Lugar exacto del reporte.</div>")
                            .addTo(this.map);
                    }, 50);
                    
                    
                }
            }));
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

            if (!confirm('¿Estás seguro de ' + action + ' este reporte?')) return;

            fetch('/api/reportes/' + id + '/validar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer {{ session('api_token') }}'
                },
                body: JSON.stringify(body)
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                Livewire.dispatch('refreshReports');
            })
            .catch(() => {
                alert('Ocurrió un error al procesar la solicitud.');
            });
        }



        function openImageModal(src) {
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
    <div id="imageModal" class="fixed inset-0 z-[100] hidden bg-slate-900/90 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0" onclick="closeImageModal()">
        <div class="relative max-w-5xl w-full max-h-[90vh] flex flex-col items-center justify-center" onclick="event.stopPropagation()">
            <button onclick="closeImageModal()" class="absolute -top-12 right-0 sm:-right-12 sm:top-0 text-white hover:text-rose-400 bg-white/10 hover:bg-white/20 rounded-full p-2 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <img id="modalImage" src="" alt="Report Image" class="max-w-full max-h-[85vh] object-contain rounded-2xl shadow-2xl border border-white/20">
        </div>
    </div>
</div>
