@props(['paginator' => null])

@if($paginator && $paginator->hasPages())
    <div class="mt-6 mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="text-slate-500 font-medium bg-white/50 px-4 py-1.5 rounded-full shadow-sm border border-white/60 text-sm">
            Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}
            <span class="text-slate-400">({{ $paginator->total() }} registros)</span>
        </div>
        <div class="flex items-center gap-2">
            @if ($paginator->onFirstPage())
                <span class="px-4 py-2 text-sm text-slate-400 rounded-full border border-slate-200">Anterior</span>
            @else
                <button type="button" wire:click="previousPage" class="px-4 py-2 text-sm font-semibold rounded-full border border-slate-200 bg-white hover:bg-slate-50 text-slate-700">Anterior</button>
            @endif
            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage" class="px-4 py-2 text-sm font-semibold rounded-full border border-slate-200 bg-white hover:bg-slate-50 text-slate-700">Siguiente</button>
            @else
                <span class="px-4 py-2 text-sm text-slate-400 rounded-full border border-slate-200">Siguiente</span>
            @endif
        </div>
    </div>
@endif
