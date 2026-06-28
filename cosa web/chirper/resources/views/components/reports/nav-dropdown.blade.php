@props([
    'apiRole' => '',
])

@php
    $isReportsSection = request()->routeIs('reports.*');
    $pendingCount = $reportsPendientesCount ?? 0;
    $isAuthority = $apiRole === 'authority';
@endphp

<div class="relative group">
    <a href="{{ route('reports.index', [], false) }}" class="px-4 py-2 rounded-md transition-colors inline-flex items-center gap-1 {{ $isReportsSection ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
        Reportes
        @if($pendingCount > 0 && $isAuthority)
            <span class="min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-red-600 text-[10px] font-bold text-white flex items-center justify-center">{{ $pendingCount > 99 ? '99+' : $pendingCount }}</span>
        @endif
        <svg class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
    </a>
    <div class="absolute left-0 top-full pt-1 hidden group-hover:block group-focus-within:block z-50 min-w-[15rem]">
        <div class="bg-white border border-gray-200 rounded-lg shadow-lg py-1 text-sm">
            <a href="{{ route('reports.index', [], false) }}" class="block px-4 py-2 hover:bg-gray-50 {{ request()->routeIs('reports.index') ? 'text-primary-700 font-semibold bg-primary-50/50' : 'text-gray-700' }}">Vista general</a>
            <a href="{{ route('reports.mis', [], false) }}" class="block px-4 py-2 hover:bg-gray-50 {{ request()->routeIs('reports.mis') ? 'text-primary-700 font-semibold bg-primary-50/50' : 'text-gray-700' }}">Mis reportes</a>
            @if($isAuthority)
                <a href="{{ route('reports.pendientes', [], false) }}" class="block px-4 py-2 hover:bg-gray-50 {{ request()->routeIs('reports.pendientes') ? 'text-primary-700 font-semibold bg-primary-50/50' : 'text-gray-700' }}">
                    Pendientes de validación
                    @if($pendingCount > 0)
                        <span class="ml-1 text-[10px] font-bold text-red-600">({{ $pendingCount }})</span>
                    @endif
                </a>
                <a href="{{ route('reports.rechazados', [], false) }}" class="block px-4 py-2 hover:bg-gray-50 {{ request()->routeIs('reports.rechazados') ? 'text-primary-700 font-semibold bg-primary-50/50' : 'text-gray-700' }}">Reportes rechazados</a>
            @endif
            <a href="{{ route('reports.historial', [], false) }}" class="block px-4 py-2 hover:bg-gray-50 {{ request()->routeIs('reports.historial') ? 'text-primary-700 font-semibold bg-primary-50/50' : 'text-gray-700' }}">Historial de inundaciones</a>
        </div>
    </div>
</div>
