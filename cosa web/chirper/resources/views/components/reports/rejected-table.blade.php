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
                            @forelse ($reportesRechazados ?? [] as $rep)
                                @php
                                    /** @var \App\Models\Reporte $rep */
                                    $reporterName = $rep->citizen?->name
                                        ?? ($rep->citizen_carnet ? 'Carnet ' . $rep->citizen_carnet : 'Ciudadano anónimo');
                                    $descText = !empty($rep->description) ? $rep->description : 'Sin descripción.';
                                    $addrText = !empty($rep->address) ? $rep->address : 'Ubicación GPS';
                                    $rejectedIntensityClass = match ($rep->intensidad_propuesta) {
                                        'alta'  => 'intensity-pill-alta',
                                        'media' => 'intensity-pill-media',
                                        default => 'intensity-pill-baja',
                                    };
                                @endphp
                                <tr class="transition-colors duration-200 hover:bg-white/30" wire:key="rejected-report-{{ $rep->id }}">
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
                                            <div>
                                                <span class="report-field-label">Rechazado</span>
                                                <p class="report-field-value">{{ optional($rep->rechazado_at ?? $rep->updated_at)->format('d/m/Y H:i') }}</p>
                                            </div>
                                            <div>
                                                <span class="report-field-label">Rechazado por</span>
                                                <p class="report-field-value truncate max-w-[10rem]" title="{{ $rep->validador?->name ?? '—' }}">{{ $rep->validador?->name ?? '—' }}</p>
                                            </div>
                                            <div>
                                                <span class="report-field-label">Motivo</span>
                                                <p class="report-field-value text-xs leading-snug">{{ $rep->motivoRechazo?->label_autoridad ?? '—' }}</p>
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
                                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide {{ $rejectedIntensityClass }}">
                                                        {{ ucfirst((string) $rep->intensidad_propuesta) }}
                                                    </span>
                                                </div>
                                            </div>
                                            @if($rep->motivo_rechazo_texto)
                                                <p class="text-[11px] text-slate-500 italic">{{ $rep->motivo_rechazo_texto }}</p>
                                            @endif
                                            <div class="flex flex-wrap gap-2">
                                                @if($rep->distancia_gps_metros !== null)
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">GPS: {{ number_format((float) $rep->distancia_gps_metros, 0) }} m</span>
                                                @endif
                                                @if($rep->precipitacionAlReportar() !== null)
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-sky-100 text-sky-700">Lluvia: {{ $rep->precipitacionAlReportar() }} mm</span>
                                                @endif
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">Peso: {{ $rep->peso }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
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
                                    </td>
                                    <td>
                                        <div class="flex flex-col gap-2 w-28">
                                            <button type="button" wire:click="abrirModificarRechazado({{ $rep->id }})" class="w-full inline-flex items-center justify-center gap-1 px-3 py-2 text-xs rounded-lg font-bold shadow-sm transition-colors btn-report-modificar">
                                                Modificar
                                            </button>
                                            <button type="button" wire:click="verHistorial({{ $rep->id }})" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 underline text-center">
                                                Ver historial
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500 font-medium">
                                        No hay reportes rechazados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
