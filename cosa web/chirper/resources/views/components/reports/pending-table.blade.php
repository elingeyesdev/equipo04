                <div class="overflow-x-auto p-2">
                    <table class="w-full text-sm glass-table rounded-xl overflow-hidden report-validation-table">
                        <thead>
                            <tr>
                                <th class="w-[4.5rem] md:w-24">Foto</th>
                                <th class="w-36">Reporte</th>
                                <th class="min-w-[11rem] max-w-[16rem]">Detalles</th>
                                <th class="w-[11rem] md:w-[12.5rem]">Mapa</th>
                                <th class="w-28">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/50">
                            @forelse ($reportesPendientes ?? [] as $rep)
                                @php
                                    /** @var \App\Models\Reporte $rep */
                                    $reporterName = $rep->citizen?->name
                                        ?? ($rep->citizen_carnet ? 'Carnet ' . $rep->citizen_carnet : 'Ciudadano anónimo');
                                    $descText = !empty($rep->description) ? $rep->description : 'Sin descripción.';
                                    $addrText = !empty($rep->address) ? $rep->address : 'Ubicación GPS';
                                    $intensityClass = match ($rep->intensidad_propuesta) {
                                        'alta'  => 'intensity-pill-alta',
                                        'media' => 'intensity-pill-media',
                                        default => 'intensity-pill-baja',
                                    };
                                    $reportDrawerPayload = array_merge($rep->toArray(), [
                                        'cercanas' => collect($rep->cercanas ?? [])->values()->all(),
                                    ]);
                                @endphp
                                <tr class="transition-colors duration-200 hover:bg-white/30">
                                    <td>
                                        <div class="w-[4.5rem] md:w-24 flex-shrink-0 flex items-center justify-center bg-white/50 border border-white/60 rounded-xl overflow-hidden h-[4.5rem] md:h-24 shadow-sm">
                                            @if(!empty($rep->foto_path))
                                                <img src="{{ asset('storage/' . $rep->foto_path) }}" alt="Foto del reporte N°{{ $rep->id }}" onclick="openImageModal('{{ asset('storage/' . $rep->foto_path) }}')" class="w-full h-full object-cover cursor-pointer hover:opacity-90 transition-opacity">
                                            @else
                                                <div class="flex flex-col items-center justify-center p-1">
                                                    <svg class="w-6 h-6 opacity-20 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                    <span class="text-[8px] font-bold text-slate-400 uppercase text-center leading-tight">Sin foto</span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <p class="text-sm font-bold text-[#1F2937] leading-snug mb-2">N°{{ $rep->id }}</p>
                                        <div class="space-y-1.5">
                                            <div>
                                                <span class="report-field-label">Fecha</span>
                                                <p class="report-field-value">{{ $rep->created_at->format('d/m/Y H:i') }}</p>
                                            </div>
                                            <div>
                                                <span class="report-field-label">Reportado por</span>
                                                <p class="report-field-value truncate max-w-[10rem]" title="{{ $reporterName }}">{{ $reporterName }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="min-w-[11rem] max-w-[16rem]">
                                        <div class="space-y-2">
                                            <div>
                                                <span class="report-field-label mb-0.5">Descripción</span>
                                                <p class="report-field-value whitespace-normal break-words line-clamp-4">{{ $descText }}</p>
                                            </div>
                                            <div class="flex gap-3 items-start">
                                                <div class="w-1/2 min-w-0">
                                                    <span class="report-field-label mb-0.5">Dirección</span>
                                                    <p class="report-field-value whitespace-normal break-words line-clamp-2">{{ $addrText }}</p>
                                                </div>
                                                <div class="w-1/2 min-w-0">
                                                    <span class="report-field-label mb-0.5">Intensidad propuesta</span>
                                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide {{ $intensityClass }}">
                                                        {{ ucfirst((string) $rep->intensidad_propuesta) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap gap-2 mt-1">
                                                @if($rep->distancia_gps_metros !== null)
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">GPS: {{ number_format((float) $rep->distancia_gps_metros, 0) }} m</span>
                                                @endif
                                                @if($rep->precipitacionAlReportar() !== null)
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-sky-100 text-sky-700">Lluvia: {{ $rep->precipitacionAlReportar() }} mm</span>
                                                @endif
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">Peso: {{ $rep->peso }}</span>
                                                @if(($rep->rechazos_previos ?? 0) > 0)
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800">{{ $rep->rechazos_previos }} rechazo(s) previo(s)</span>
                                                @endif
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            onclick="openReportDetailModal(this)"
                                            class="mt-2 text-sm font-semibold report-detail-link underline-offset-2 hover:underline transition-colors"
                                            data-id="{{ $rep->id }}"
                                            data-description="{{ e($descText) }}"
                                            data-address="{{ e($addrText) }}"
                                            data-reporter="{{ e($reporterName) }}"
                                            data-date="{{ $rep->created_at->format('d/m/Y H:i') }}"
                                            data-intensity="{{ $rep->intensidad_propuesta }}"
                                            data-lat-gps="{{ $rep->lat_gps }}"
                                            data-lng-gps="{{ $rep->long_gps }}"
                                            data-lat-rep="{{ $rep->lat_reporte }}"
                                            data-lng-rep="{{ $rep->long_reporte }}"
                                            data-has-cercanas="{{ count($rep->cercanas ?? []) > 0 ? '1' : '0' }}"
                                            data-distancia="{{ $rep->distancia_gps_metros ?? '' }}"
                                            data-precipitacion="{{ $rep->precipitacionAlReportar() ?? '' }}"
                                            data-peso="{{ $rep->peso }}"
                                            data-cercanas="{{ count($rep->cercanas ?? []) }}"
                                            data-rechazos="{{ $rep->rechazos_previos ?? 0 }}"
                                        >Ver detalle completo</button>
                                    </td>
                                    <td>
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
                                    <td>
                                        <div class="flex flex-col gap-1.5 w-full max-w-[9.5rem]">
                                            <button type="button" onclick="openApproveModal({{ $rep->id }}, 'crear', '{{ $rep->intensidad_propuesta }}')" class="w-full inline-flex items-center justify-center gap-1.5 btn-report-aprobar px-3 py-2 text-xs rounded-lg font-bold shadow-sm transition-colors">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                Aprobar
                                            </button>
                                            @if(count($rep->cercanas ?? []) > 0)
                                                <button type="button" data-report="{{ json_encode($reportDrawerPayload) }}" onclick="openReviewDrawer(this)" class="w-full inline-flex items-center justify-center gap-1.5 btn-report-vincular px-3 py-2 text-xs rounded-lg font-bold shadow-sm transition-colors report-vincular-btn">
                                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                                    Vincular
                                                </button>
                                            @else
                                                <button type="button" disabled title="Sin inundaciones activas cercanas" class="w-full inline-flex items-center justify-center gap-1.5 btn-report-vincular px-3 py-2 text-xs rounded-lg font-bold shadow-sm report-vincular-btn">
                                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                                    Vincular
                                                </button>
                                            @endif
                                            <button type="button" onclick="openRejectModal({{ $rep->id }}, '{{ e($reporterName) }}', {{ $rep->distancia_gps_metros ?? 'null' }}, {{ $rep->precipitacionAlReportar() ?? 'null' }}, {{ $rep->peso }}, {{ count($rep->cercanas ?? []) }}, {{ $rep->rechazos_previos ?? 0 }})" class="w-full inline-flex items-center justify-center gap-1.5 btn-report-rechazar px-3 py-2 text-xs rounded-lg font-bold shadow-sm transition-colors">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
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
