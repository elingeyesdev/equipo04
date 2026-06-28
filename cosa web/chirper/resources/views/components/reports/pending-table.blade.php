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
                                        'solo_vincular' => (bool) ($rep->solo_vincular ?? false),
                                        'dentro_contorno_activo' => (bool) ($rep->dentro_contorno_activo ?? false),
                                    ]);
                                @endphp
                                <tr class="transition-colors duration-200 hover:bg-white/30">
                                    <td>
                                        <x-reports.report-photo :report="$rep" />
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
                                                @if($rep->dentro_contorno_activo ?? false)
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 font-semibold">Dentro de zona activa</span>
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
                                            data-solo-vincular="{{ ($rep->solo_vincular ?? false) ? '1' : '0' }}"
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
                                    </td>
                                    <td>
                                        <div class="flex flex-col gap-1.5 w-full max-w-[9.5rem]">
                                            @if(!($rep->solo_vincular ?? false))
                                                <button type="button" onclick="openApproveModal({{ $rep->id }}, 'crear', '{{ $rep->intensidad_propuesta }}')" class="w-full inline-flex items-center justify-center btn-report-aprobar px-3 py-2 text-xs rounded-lg font-bold shadow-sm transition-colors">
                                                    Aprobar
                                                </button>
                                            @endif
                                            @if(count($rep->cercanas ?? []) > 0)
                                                <button type="button" data-report="{{ json_encode($reportDrawerPayload) }}" onclick="openReviewDrawer(this)" class="w-full inline-flex items-center justify-center btn-report-vincular px-3 py-2 text-xs rounded-lg font-bold shadow-sm transition-colors report-vincular-btn">
                                                    Vincular
                                                </button>
                                            @endif
                                            <button type="button" onclick="openRejectModal({{ $rep->id }}, '{{ e($reporterName) }}', {{ $rep->distancia_gps_metros ?? 'null' }}, {{ $rep->precipitacionAlReportar() ?? 'null' }}, {{ $rep->peso }}, {{ count($rep->cercanas ?? []) }}, {{ $rep->rechazos_previos ?? 0 }})" class="w-full inline-flex items-center justify-center btn-report-rechazar px-3 py-2 text-xs rounded-lg font-bold shadow-sm transition-colors">
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
