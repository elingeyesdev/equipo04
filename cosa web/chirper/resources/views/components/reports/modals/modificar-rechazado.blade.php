@if($modificarReporteId)
    @php $modId = $modificarReporteId; @endphp
    <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/40" wire:click.self="cerrarModificarRechazado">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-hidden flex flex-col" wire:click.stop>
            <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800">Modificar reporte N°{{ $modId }}</h3>
                <button type="button" wire:click="cerrarModificarRechazado" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
            </div>
            <form wire:submit.prevent="updateEstadoValidacion({{ $modId }})" class="report-validation-form overflow-y-auto p-5 space-y-4">
                <div>
                    <label class="report-field-label mb-1">Motivo rechazo</label>
                    <select wire:model="motivoRechazoUpdates.{{ $modId }}">
                        <option value="">Seleccionar...</option>
                        @foreach(($motivosRechazo ?? []) as $motivo)
                            <option value="{{ $motivo->codigo }}">{{ $motivo->label_autoridad }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="report-field-label mb-1">Nota del motivo</label>
                    <textarea wire:model="motivoTextoUpdates.{{ $modId }}" rows="3" placeholder="Detalle opcional u obligatorio según motivo"></textarea>
                </div>
                <div>
                    <label class="report-field-label mb-1">Estado</label>
                    <select wire:model.live="estadoValidacionUpdates.{{ $modId }}">
                        <option value="pendiente">Pendiente</option>
                        <option value="aceptado">Aceptado</option>
                        <option value="rechazado">Rechazado</option>
                    </select>
                </div>
                @if(($estadoValidacionUpdates[$modId] ?? 'rechazado') === 'pendiente')
                    <div>
                        <label class="report-field-label mb-1">Motivo reversión</label>
                        <textarea wire:model="reversionTextoUpdates.{{ $modId }}" rows="3" placeholder="Por qué vuelve a pendiente"></textarea>
                    </div>
                @endif
                @if(($estadoValidacionUpdates[$modId] ?? '') === 'aceptado')
                    <div>
                        <label class="report-field-label mb-1">Vincular a inundación</label>
                        <select wire:model="inundacionVincularIds.{{ $modId }}">
                            <option value="">Seleccionar inundación...</option>
                            @foreach(($inundacionesActivasParaVincular ?? []) as $inundacionActiva)
                                <option value="{{ $inundacionActiva->id }}">Inundación N°{{ $inundacionActiva->id }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="flex gap-2 pt-2">
                    <button type="button" wire:click="cerrarModificarRechazado" class="flex-1 px-4 py-2.5 text-sm rounded-lg border border-slate-200 text-slate-600 font-semibold">Cancelar</button>
                    <button type="submit" class="flex-1 px-4 py-2.5 text-sm rounded-lg btn-report-aprobar font-bold">
                        <span wire:loading.remove wire:target="updateEstadoValidacion({{ $modId }})">Guardar cambios</span>
                        <span wire:loading wire:target="updateEstadoValidacion({{ $modId }})">Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
