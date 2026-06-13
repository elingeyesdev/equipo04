@extends('layouts.app')

@section('content')
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
        .glass-table th { background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(4px); }
        .glass-table tr:hover { background: rgba(255, 255, 255, 0.5); }
        .gradient-text {
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>

    <div class="min-h-screen bg-gray-100 -m-4 sm:-m-6 lg:-m-8 p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-blue-800">Detalles de Inundación N°{{ $inundacion->id }}</h1>
                    <p class="mt-2 text-sm font-medium text-slate-600">Vista consolidada de reportes y afectaciones.</p>
                </div>
                <a href="{{ route('reports.index', [], false) }}" class="rounded-lg bg-white border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Volver
                </a>
            </div>

            <!-- Resumen -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="glass-panel rounded-2xl p-5">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Estado</div>
                    <div class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider bg-blue-100 text-blue-700 shadow-sm">
                        {{ $inundacion->estado }}
                    </div>
                </div>
                <div class="glass-panel rounded-2xl p-5">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Intensidad</div>
                    @php($int = $inundacion->intensidadCalculada() ?? 'N/A')
                    <div class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider shadow-sm
                        {{ $int === 'alta' ? 'bg-rose-500 text-white' : ($int === 'media' ? 'bg-amber-400 text-amber-900' : ($int === 'baja' ? 'bg-teal-400 text-teal-900' : 'bg-gray-100 text-gray-600')) }}">
                        {{ $int }}
                    </div>
                </div>
                <div class="glass-panel rounded-2xl p-5">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Quórum Confirmado</div>
                    <div class="text-2xl font-extrabold text-slate-800">
                        {{ $inundacion->quorumTotal() }} pts
                        @if($inundacion->estaConfirmada())
                            <svg class="w-4 h-4 fill-current inline-block ml-1 text-teal-500" viewBox="0 0 640 640"><path d="M530.8 134.1C545.1 144.5 548.3 164.5 537.9 178.8L281.9 530.8C276.4 538.4 267.9 543.1 258.5 543.9C249.1 544.7 240 541.2 233.4 534.6L105.4 406.6C92.9 394.1 92.9 373.8 105.4 361.3C117.9 348.8 138.2 348.8 150.7 361.3L252.2 462.8L486.2 141.1C496.6 126.8 516.6 123.6 530.9 134z"/></svg>
                        @endif
                    </div>
                </div>
                <div class="glass-panel rounded-2xl p-5">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Fecha de Registro</div>
                    <div class="font-bold text-slate-700">{{ $inundacion->created_at->format('d M, Y H:i') }}</div>
                </div>
            </div>

            <!-- Ubicacion y ETA -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="glass-panel rounded-2xl p-6">
                    <h3 class="text-sm font-bold text-indigo-900 uppercase tracking-wide mb-4">Ubicación y Centroide</h3>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-white/60 rounded-xl p-3 border border-white">
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Latitud</div>
                            <div class="font-medium text-slate-700 text-sm">{{ $inundacion->latitud }}</div>
                        </div>
                        <div class="bg-white/60 rounded-xl p-3 border border-white">
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Longitud</div>
                            <div class="font-medium text-slate-700 text-sm">{{ $inundacion->longitud }}</div>
                        </div>
                    </div>
                    @if ($inundacion->reportes->first()?->address)
                        <div class="bg-white/60 rounded-xl p-3 border border-white mb-4">
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Dirección Referencial</div>
                            <div class="font-medium text-slate-700 text-sm">{{ $inundacion->reportes->first()->address }}</div>
                        </div>
                    @endif
                    <div class="mt-4 border border-gray-200 rounded-xl overflow-hidden relative z-0" style="height: 220px;" id="flood-mini-map"></div>
                </div>

                <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
                <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
                <script src="{{ asset('js/smart-heatmap.js') }}"></script>
                
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const mapDiv = document.getElementById('flood-mini-map');
                        if (!mapDiv) return;

                        const lat = {{ $inundacion->latitud }};
                        const lng = {{ $inundacion->longitud }};
                        
                        const miniMap = L.map(mapDiv, {
                            zoomControl: true, 
                            attributionControl: false,
                            scrollWheelZoom: false,
                        }).setView([lat, lng], 14);
                        
                        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { 
                            subdomains: 'abcd', maxZoom: 20 
                        }).addTo(miniMap);
                        
                        const heatData = @json($inundacion->reportes->count() > 0 ? $inundacion->reportes : [['lat' => $inundacion->latitud, 'lng' => $inundacion->longitud, 'intensidad' => 'media']]);
                        
                        window.createSmartHeatmap(miniMap, heatData, {
                            heatOptions: { radius: 25, blur: 15 }
                        });
                        
                        // Icono simple para el centroide
                        L.marker([lat, lng], {
                            icon: L.divIcon({
                                className: 'custom-leaflet-marker',
                                html: `<div style="background-color:#2563eb;width:16px;height:16px;border-radius:50%;border:2px solid white;box-shadow:0 0 5px rgba(0,0,0,0.5);"></div>`,
                                iconSize: [16, 16], iconAnchor: [8, 8]
                            })
                        }).addTo(miniMap);
                    });
                </script>

                <div class="glass-panel rounded-2xl p-6">
                    <h3 class="text-sm font-bold text-indigo-900 uppercase tracking-wide mb-4">Predicción Logística</h3>
                    @if (!empty($eta))
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm mb-3">
                            <div class="bg-white/60 rounded-xl p-3 border border-white">
                                <div class="text-[10px] font-bold text-slate-400 uppercase">Centro Cercano</div>
                                <div class="font-medium text-slate-700 text-sm">{{ $eta['name'] }}</div>
                            </div>
                            <div class="bg-white/60 rounded-xl p-3 border border-white">
                                <div class="text-[10px] font-bold text-slate-400 uppercase">Distancia Aprox.</div>
                                <div class="font-medium text-slate-700 text-sm">{{ number_format((float) $eta['distance_km'], 2) }} km</div>
                            </div>
                            <div class="bg-white/60 rounded-xl p-3 border border-white">
                                <div class="text-[10px] font-bold text-slate-400 uppercase">Tiempo Estimado</div>
                                <div class="font-medium text-slate-700 text-sm">{{ (int) $eta['eta_minutes'] }} min</div>
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-500 font-medium">Estimación basada en línea recta al centro logístico más cercano.</p>
                    @else
                        <div class="bg-slate-100/50 rounded-xl p-4 text-center">
                            <p class="text-sm text-slate-500 font-medium">No se pudo calcular el tiempo de llegada.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Reportes Vinculados -->
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden mb-10 shadow-sm">
                <div class="px-6 py-5 border-b border-gray-200 flex items-center justify-between bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        Reportes Vinculados
                    </h2>
                    <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-xs font-bold">{{ $inundacion->reportes->count() }} reporte(s)</span>
                </div>
                <div class="overflow-x-auto p-2">
                    <table class="w-full text-sm glass-table rounded-xl overflow-hidden">
                        <thead class="text-slate-600">
                            <tr>
                                <th class="text-left font-semibold px-4 py-3 rounded-tl-xl w-16">Foto</th>
                                <th class="text-left font-semibold px-4 py-3">Reporte N°</th>
                                <th class="text-left font-semibold px-4 py-3">Intensidad</th>
                                <th class="text-left font-semibold px-4 py-3">Dirección</th>
                                <th class="text-left font-semibold px-4 py-3">Quórum</th>
                                <th class="text-left font-semibold px-4 py-3 rounded-tr-xl">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/50">
                            @forelse($inundacion->reportes as $rep)
                                <tr class="transition-colors duration-200">
                                    <td class="px-4 py-3">
                                        @if($rep->foto_path)
                                            <img src="{{ asset('storage/' . $rep->foto_path) }}" alt="Foto" class="w-10 h-10 object-cover rounded-lg shadow-sm border border-gray-200 cursor-pointer hover:scale-105 transition-transform clickable-image">
                                        @else
                                            <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-bold text-slate-700">N°{{ $rep->id }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded px-2 py-0.5 text-[10px] font-bold uppercase
                                            {{ $rep->intensidad_propuesta === 'alta' ? 'bg-rose-100 text-rose-700' : ($rep->intensidad_propuesta === 'media' ? 'bg-amber-100 text-amber-700' : 'bg-teal-100 text-teal-700') }}">
                                            {{ $rep->intensidad_propuesta }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 max-w-xs truncate" title="{{ $rep->address }}">
                                        {{ $rep->address ?: 'Ubicación GPS: ' . $rep->lat_reporte . ', ' . $rep->long_reporte }}
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-indigo-600">+{{ $rep->peso }} pts</td>
                                    <td class="px-4 py-3 text-slate-500 text-xs">{{ $rep->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-8 text-slate-500 text-center font-medium" colspan="6">No hay reportes vinculados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Victimas Asociadas -->
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden mb-10 shadow-sm">
                <div class="px-6 py-5 border-b border-gray-200 flex items-center justify-between bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Víctimas Asociadas
                    </h2>
                    <span class="bg-rose-100 text-rose-800 py-1 px-3 rounded-full text-xs font-bold">{{ $inundacion->victimas->count() }} víctima(s)</span>
                </div>
                <div class="overflow-x-auto p-2">
                    <table class="w-full text-sm glass-table rounded-xl overflow-hidden">
                        <thead class="text-slate-600">
                            <tr>
                                <th class="text-left font-semibold px-4 py-3">Nombre Completo</th>
                                <th class="text-left font-semibold px-4 py-3">Edad</th>
                                <th class="text-left font-semibold px-4 py-3">Estado</th>
                                <th class="text-left font-semibold px-4 py-3">Descripción</th>
                                <th class="text-left font-semibold px-4 py-3 rounded-tr-xl">Registrado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/50">
                            @forelse($inundacion->victimas as $vic)
                                <tr class="transition-colors duration-200">
                                    <td class="px-4 py-3 font-semibold text-slate-700">{{ $vic->nombre_completo }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $vic->fecha_nacimiento ? \Carbon\Carbon::parse($vic->fecha_nacimiento)->age . ' años' : 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded px-2 py-0.5 text-[10px] font-bold uppercase
                                            {{ $vic->estado === 'fallecido' ? 'bg-slate-800 text-white' : ($vic->estado === 'herido' ? 'bg-rose-100 text-rose-700' : ($vic->estado === 'perdido' ? 'bg-amber-100 text-amber-700' : 'bg-teal-100 text-teal-700')) }}">
                                            {{ $vic->estado }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $vic->descripcion ?: 'Ninguna' }}</td>
                                    <td class="px-4 py-3 text-slate-500 text-xs">{{ $vic->created_at->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-8 text-slate-500 text-center font-medium" colspan="5">No hay víctimas registradas para esta inundación.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Acciones de Autoridad -->
            @if ($role === 'authority' && $inundacion->estado === 'activa')
                <div class="glass-panel rounded-2xl p-6 mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-800">Finalizar Inundación</h3>
                        <p class="text-sm text-slate-500 mt-1">Marcar esta inundación como terminada y archivar sus reportes.</p>
                    </div>
                    <form method="POST" action="{{ route('reports.status.update', ['id' => $inundacion->id], false) }}">
                        @csrf
                        <input type="hidden" name="estado" value="terminada">
                        <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-md transition-colors">
                            Desactivar Inundación
                        </button>
                    </form>
                </div>
            @endif

        </div>
    </div>
@endsection
