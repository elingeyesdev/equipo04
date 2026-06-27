@props([
    'motivosRechazo' => collect(),
    'validadoresRechazo' => collect(),
    'filtroRechazoMotivo' => '',
    'filtroRechazoValidador' => '',
    'filtroRechazoDesde' => '',
    'filtroRechazoHasta' => '',
])

<div class="report-filter-bar">
    <div class="report-filter-grid">
        <div>
            <label class="report-field-label mb-1">Motivo</label>
            <select wire:model.live="filtroRechazoMotivo" class="report-filter-control">
                <option value="">Todos</option>
                @foreach($motivosRechazo as $motivo)
                    <option value="{{ $motivo->codigo }}">{{ $motivo->label_autoridad }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="report-field-label mb-1">Validador</label>
            <select wire:model.live="filtroRechazoValidador" class="report-filter-control">
                <option value="">Todos</option>
                @foreach($validadoresRechazo as $validador)
                    <option value="{{ $validador->carnet }}">{{ $validador->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="report-field-label mb-1">Desde</label>
            <input type="date" wire:model.live="filtroRechazoDesde" class="report-filter-control">
        </div>
        <div>
            <label class="report-field-label mb-1">Hasta</label>
            <input type="date" wire:model.live="filtroRechazoHasta" class="report-filter-control">
        </div>
        <div class="flex items-end">
            @if($filtroRechazoMotivo || $filtroRechazoValidador || $filtroRechazoDesde || $filtroRechazoHasta)
                <button type="button" wire:click="limpiarFiltrosRechazados" class="btn-filter-clear w-full">
                    Limpiar filtros
                </button>
            @endif
        </div>
    </div>
</div>
