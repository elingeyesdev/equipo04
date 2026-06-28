@if($historialReporteId)
    <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/40" wire:click.self="cerrarHistorial">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[85vh] overflow-hidden flex flex-col" wire:click.stop>
            <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800">Historial de validación — Reporte N°{{ $historialReporteId }}</h3>
                <button type="button" wire:click="cerrarHistorial" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
            </div>
            <div class="overflow-y-auto p-5 space-y-3">
                @forelse($historialEntradas as $entry)
                    @php
                        $accionLabel = match ($entry['accion'] ?? '') {
                            'rechazar' => 'Rechazo',
                            'aprobar_crear' => 'Aprobación (nueva inundación)',
                            'aprobar_vincular' => 'Aprobación (vinculado)',
                            'aprobar_con_ajuste' => 'Aprobación con ajuste',
                            'revertir_pendiente' => 'Reversión a pendiente',
                            're_rechazar' => 'Re-rechazo',
                            default => ucfirst(str_replace('_', ' ', (string) ($entry['accion'] ?? ''))),
                        };
                    @endphp
                    <div class="border border-slate-200 rounded-lg p-3 bg-slate-50/50">
                        <div class="flex flex-wrap justify-between gap-2 mb-1">
                            <span class="text-xs font-bold text-indigo-700">{{ $accionLabel }}</span>
                            <span class="text-[11px] text-slate-500">{{ $entry['fecha'] ?? '—' }}</span>
                        </div>
                        <p class="text-xs text-slate-600">
                            <span class="font-semibold">Estado:</span>
                            {{ $entry['estado_anterior'] ?? '—' }} → {{ $entry['estado_nuevo'] ?? '—' }}
                        </p>
                        <p class="text-xs text-slate-600"><span class="font-semibold">Validador:</span> {{ $entry['validador'] ?? '—' }}</p>
                        @if(!empty($entry['motivo']))
                            <p class="text-xs text-slate-600"><span class="font-semibold">Motivo:</span> {{ $entry['motivo'] }}</p>
                        @endif
                        @if(!empty($entry['intensidad_propuesta']) || !empty($entry['intensidad_validada']))
                            <p class="text-xs text-slate-600">
                                <span class="font-semibold">Intensidad:</span>
                                {{ ucfirst((string) ($entry['intensidad_propuesta'] ?? '—')) }}
                                @if(!empty($entry['intensidad_validada']) && ($entry['intensidad_validada'] ?? '') !== ($entry['intensidad_propuesta'] ?? ''))
                                    → {{ ucfirst((string) $entry['intensidad_validada']) }}
                                @endif
                            </p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500 text-center py-6">Sin entradas en el historial.</p>
                @endforelse
            </div>
        </div>
    </div>
@endif
