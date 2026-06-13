@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- ══════════════════════════════════════════════════════════════════
         CABECERA Y NAVEGACIÓN
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('victimas.index', [], false) }}"
           class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Volver al listado
        </a>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         FICHA PRINCIPAL
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">

        {{-- Cabecera con foto y datos clave --}}
        <div class="flex flex-col sm:flex-row gap-6 p-6 border-b border-gray-100">
            {{-- Foto --}}
            <div class="flex-shrink-0">
                @if ($victima->foto_path)
                    <img src="{{ asset('storage/' . $victima->foto_path) }}"
                         alt="Foto de {{ $victima->nombre_completo }}"
                         class="clickable-image w-32 h-32 sm:w-40 sm:h-40 rounded-xl object-cover border-2 border-gray-200 shadow-md cursor-pointer hover:opacity-95 transition-all duration-200"
                         title="Clic para ampliar">
                @else
                    <div class="w-32 h-32 sm:w-40 sm:h-40 rounded-xl bg-gray-100 border-2 border-gray-200 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Datos principales --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
                            {{ $victima->nombre_completo }}
                        </h1>
                        @if($victima->carnet)
                            <p class="text-sm text-gray-500 mt-0.5">CI: {{ $victima->carnet }}</p>
                        @endif
                    </div>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold {{ $victima->estadoBadgeClass() }} flex-shrink-0">
                        {{ $victima->estadoLabel() }}
                    </span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                    @if($victima->fecha_nacimiento)
                        <div>
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide block mb-0.5">Fecha de Nacimiento</span>
                            <span class="text-gray-800">{{ $victima->fecha_nacimiento->format('d/m/Y') }}</span>
                        </div>
                    @endif
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide block mb-0.5">Registrado el</span>
                        <span class="text-gray-800">{{ $victima->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($victima->registrador)
                        <div>
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide block mb-0.5">Registrado por</span>
                            <span class="text-gray-800">{{ $victima->registrador->name ?? $victima->registrado_por }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Descripción --}}
        @if($victima->descripcion)
            <div class="px-6 py-5 border-b border-gray-100">
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Descripción</h2>
                <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $victima->descripcion }}</p>
            </div>
        @endif

        {{-- Inundación asociada --}}
        <div class="px-6 py-5">
            <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Inundación Asociada</h2>
            @php
                $inundacion = $victima->inundacion;
                $municipio  = $inundacion?->municipio;
                $provincia  = $municipio?->provincia;
            @endphp
            @if($inundacion)
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 flex items-start gap-4">
                    <div class="flex-shrink-0 rounded-full bg-blue-100 p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="flex-1 grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                        <div>
                            <span class="text-xs font-semibold text-blue-500 block">ID</span>
                            <span class="text-blue-900 font-bold">N°{{ $inundacion->id }}</span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-blue-500 block">Estado</span>
                            <span class="text-blue-900">{{ ucfirst($inundacion->estado) }}</span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-blue-500 block">Provincia</span>
                            <span class="text-blue-900">{{ $provincia?->nombre ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-blue-500 block">Municipio</span>
                            <span class="text-blue-900">{{ $municipio?->nombre ?? '—' }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-xs font-semibold text-blue-500 block">Fecha del evento</span>
                            <span class="text-blue-900">
                                {{ $inundacion->created_at ? \Carbon\Carbon::parse($inundacion->created_at)->format('d/m/Y H:i') : '—' }}
                            </span>
                        </div>
                        @if($inundacion->latitud && $inundacion->longitud)
                            <div class="col-span-2">
                                <span class="text-xs font-semibold text-blue-500 block">Coordenadas</span>
                                <span class="text-blue-900 font-mono text-xs">
                                    {{ $inundacion->latitud }}, {{ $inundacion->longitud }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('reports.show', ['id' => $inundacion->id], false) }}"
                       class="text-sm text-blue-600 hover:text-blue-800 font-medium hover:underline transition-colors">
                        Ver ficha completa de la inundación →
                    </a>
                </div>
            @else
                <p class="text-sm text-gray-500">Sin inundación asociada.</p>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         ACCIONES (solo authority)
    ══════════════════════════════════════════════════════════════════ --}}
    @if(isset($role) && $role === 'authority')
        <div class="flex items-center justify-between">
            {{-- Eliminar --}}
            <form method="POST"
                  action="{{ route('victimas.destroy', ['id' => $victima->id], false) }}"
                  onsubmit="return confirm('¿Eliminar definitivamente a {{ addslashes($victima->nombre_completo) }}? Esta acción no se puede deshacer.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 hover:border-red-400 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    Eliminar
                </button>
            </form>

            {{-- Editar --}}
            <a href="{{ route('victimas.edit', ['id' => $victima->id], false) }}"
               class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                </svg>
                Editar víctima
            </a>
        </div>
    @endif

</div>
@endsection
