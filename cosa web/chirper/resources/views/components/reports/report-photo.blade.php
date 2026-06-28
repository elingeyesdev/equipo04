@props(['report'])

@php
    /** @var \App\Models\Reporte $report */
    $fotoUrl = !empty($report->foto_path) ? asset('storage/' . $report->foto_path) : null;
@endphp

<div {{ $attributes->merge(['class' => 'w-[4.5rem] md:w-24 flex-shrink-0 relative bg-white/50 border border-white/60 rounded-xl overflow-hidden h-[4.5rem] md:h-24 shadow-sm']) }}>
    @if($fotoUrl)
        <img
            src="{{ $fotoUrl }}"
            alt="Foto del reporte N°{{ $report->id }}"
            onclick="openImageModal('{{ $fotoUrl }}')"
            class="w-full h-full object-cover cursor-pointer hover:opacity-90 transition-opacity"
        >
        <button
            type="button"
            onclick="openImageModal('{{ $fotoUrl }}')"
            class="report-photo-expand-btn"
            title="Ampliar imagen"
            aria-label="Ampliar imagen del reporte N°{{ $report->id }}"
        >
            <x-reports.icon name="ampliar" class="w-3 h-3" />
        </button>
    @else
        <div class="flex flex-col items-center justify-center p-1 w-full h-full">
            <svg class="w-6 h-6 opacity-20 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            <span class="text-[8px] font-bold text-slate-400 uppercase text-center leading-tight">Sin foto</span>
        </div>
    @endif
</div>
