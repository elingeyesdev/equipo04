@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- Cabecera --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Registro de Víctimas</h1>
            <p class="mt-1 text-sm text-gray-500">Consulta y filtrado de personas afectadas por inundaciones.</p>
        </div>
        @if(isset($role) && $role === 'authority')
            <a href="{{ route('victimas.create', [], false) }}"
               class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Registrar Víctima
            </a>
        @endif
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════
         FILTROS — 100% client-side, sin recarga de página
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L13 10.414V17a1 1 0 01-.553.894l-4 2A1 1 0 017 19v-8.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
                </svg>
                Filtros
            </h2>
            {{-- Botón limpiar (solo visible cuando hay algo activo) --}}
            <button id="btn-limpiar"
                    class="hidden text-xs text-gray-500 hover:text-gray-700 underline underline-offset-2 transition-colors">
                × Limpiar filtros
            </button>
        </div>

        <div class="p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                {{-- Filtro: Inundación --}}
                <div>
                    <label for="filtro-inundacion" class="block text-xs font-medium text-gray-600 mb-1">Inundación</label>
                    <select id="filtro-inundacion"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">— Todas —</option>
                        @foreach($inundaciones as $inundacion)
                            @php
                                $muni  = $inundacion->municipio?->nombre ?? '?';
                                $prov  = $inundacion->municipio?->provincia?->nombre ?? '?';
                                $fecha = $inundacion->created_at
                                    ? \Carbon\Carbon::parse($inundacion->created_at)->format('d/m/Y H:i')
                                    : '?';
                            @endphp
                            <option value="{{ $inundacion->id }}"
                                data-municipio-id="{{ $inundacion->municipio_id }}"
                                data-provincia-id="{{ $inundacion->municipio?->provincia_id }}">
                                N°{{ $inundacion->id }} · {{ $fecha }} · {{ $prov }} / {{ $muni }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filtro: Provincia --}}
                <div>
                    <label for="filtro-provincia" class="block text-xs font-medium text-gray-600 mb-1">Provincia</label>
                    <select id="filtro-provincia"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">— Todas —</option>
                        @foreach($provincias as $prov)
                            <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filtro: Municipio --}}
                <div>
                    <label for="filtro-municipio" class="block text-xs font-medium text-gray-600 mb-1">Municipio</label>
                    <select id="filtro-municipio"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">— Todos —</option>
                        @foreach($municipios as $muni)
                            <option value="{{ $muni->id }}" data-provincia-id="{{ $muni->provincia_id }}">
                                {{ $muni->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filtro: Nombre --}}
                <div>
                    <label for="filtro-nombre" class="block text-xs font-medium text-gray-600 mb-1">Nombre de la víctima</label>
                    <input type="text" id="filtro-nombre"
                           placeholder="Buscar por nombre..."
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         TABLA DE VÍCTIMAS
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700">
                Resultados
                <span id="contador-badge"
                      class="ml-2 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">
                    {{ $victimas->count() }} víctima(s)
                </span>
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200 text-gray-600">
                    <tr>
                        <th class="text-left font-semibold px-4 py-3 w-16">Foto</th>
                        <th class="text-left font-semibold px-4 py-3">Nombre Completo</th>
                        <th class="text-left font-semibold px-4 py-3">Carnet</th>
                        <th class="text-left font-semibold px-4 py-3">Estado</th>
                        <th class="text-left font-semibold px-4 py-3">Inundación</th>
                        <th class="text-left font-semibold px-4 py-3">Provincia / Municipio</th>
                        <th class="text-left font-semibold px-4 py-3 w-20"></th>
                    </tr>
                </thead>
                <tbody id="tabla-victimas" class="divide-y divide-gray-100">
                    @forelse ($victimas as $victima)
                        @php
                            $provinciaId = $victima->inundacion?->municipio?->provincia_id ?? '';
                            $municipioId = $victima->inundacion?->municipio_id ?? '';
                            $provincia   = $victima->inundacion?->municipio?->provincia?->nombre ?? '—';
                            $municipio   = $victima->inundacion?->municipio?->nombre ?? '—';
                        @endphp
                        <tr class="hover:bg-blue-50 transition-colors victima-row"
                            data-inundacion-id="{{ $victima->inundacion_id }}"
                            data-provincia-id="{{ $provinciaId }}"
                            data-municipio-id="{{ $municipioId }}"
                            data-nombre="{{ mb_strtolower($victima->nombre_completo) }}">

                            {{-- Foto --}}
                            <td class="px-4 py-3">
                                @if ($victima->foto_path)
                                    <img src="{{ asset('storage/' . $victima->foto_path) }}"
                                         alt="Foto de {{ $victima->nombre_completo }}"
                                         class="h-10 w-10 rounded-full object-cover border-2 border-gray-200 shadow-sm">
                                @else
                                    <div class="h-10 w-10 rounded-full bg-gray-100 border-2 border-gray-200 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                @endif
                            </td>

                            {{-- Nombre --}}
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $victima->nombre_completo }}</td>

                            {{-- Carnet --}}
                            <td class="px-4 py-3 text-gray-600">{{ $victima->carnet ?? '—' }}</td>

                            {{-- Estado --}}
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $victima->estadoBadgeClass() }}">
                                    {{ $victima->estadoLabel() }}
                                </span>
                            </td>

                            {{-- Inundación --}}
                            <td class="px-4 py-3 text-gray-600">
                                N°{{ $victima->inundacion_id }}
                                @if($victima->inundacion?->created_at)
                                    <span class="block text-xs text-gray-400">
                                        {{ \Carbon\Carbon::parse($victima->inundacion->created_at)->format('d/m/Y H:i') }}
                                    </span>
                                @endif
                            </td>

                            {{-- Provincia / Municipio --}}
                            <td class="px-4 py-3 text-gray-600">
                                <span class="font-medium">{{ $provincia }}</span>
                                <span class="block text-xs text-gray-400">{{ $municipio }}</span>
                            </td>

                            {{-- Acciones --}}
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <a href="{{ route('victimas.show', ['id' => $victima->id], false) }}"
                                       class="inline-flex justify-center items-center w-24 rounded-md border border-gray-300 px-2 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-400 transition-colors">
                                        Ver ficha
                                    </a>
                                    @if(isset($role) && $role === 'authority')
                                        <a href="{{ route('victimas.edit', ['id' => $victima->id], false) }}"
                                           class="inline-flex justify-center items-center w-24 rounded-md border border-blue-300 px-2 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors">
                                            Editar
                                        </a>
                                        <form method="POST"
                                              action="{{ route('victimas.destroy', ['id' => $victima->id], false) }}"
                                              onsubmit="return confirm('¿Eliminar a {{ addslashes($victima->nombre_completo) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex justify-center items-center w-24 rounded-md border border-red-300 px-2 py-1.5 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 transition-colors">
                                                Eliminar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        {{-- Si no hay datos en BD, mostramos el estado vacío siempre --}}
                        <tr id="fila-sin-datos">
                            <td colspan="7" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                    </svg>
                                    <p class="text-gray-500 text-sm">No hay víctimas registradas.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    {{-- Fila "sin resultados" del filtro JS (oculta por defecto) --}}
                    <tr id="fila-filtro-vacio" style="display:none">
                        <td colspan="7" class="px-4 py-10 text-center">
                            <p class="text-gray-400 text-sm">No hay víctimas que coincidan con los filtros aplicados.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     JAVASCRIPT — Filtrado client-side instantáneo
     Escucha change/input en los 4 controles y filtra las filas del DOM
     sin realizar ninguna petición al servidor.
══════════════════════════════════════════════════════════════════ --}}
<script>
(function () {
    'use strict';

    // ── Referencias a los controles de filtro ────────────────────────────
    const selInundacion = document.getElementById('filtro-inundacion');
    const selProvincia  = document.getElementById('filtro-provincia');
    const selMunicipio  = document.getElementById('filtro-municipio');
    const inputNombre   = document.getElementById('filtro-nombre');
    const btnLimpiar    = document.getElementById('btn-limpiar');
    const contadorBadge = document.getElementById('contador-badge');
    const filaVacia     = document.getElementById('fila-filtro-vacio');

    // Todas las filas de datos de víctimas
    const filas = Array.from(document.querySelectorAll('tr.victima-row'));

    // Opciones originales de municipio (para restaurar al cambiar provincia)
    const opcionesMunicipio = Array.from(selMunicipio.querySelectorAll('option'));

    // ── Función principal de filtrado ────────────────────────────────────
    function filtrar() {
        const inundacionId = selInundacion.value;
        const provinciaId  = selProvincia.value;
        const municipioId  = selMunicipio.value;
        const nombre       = inputNombre.value.toLowerCase().trim();

        let visibles = 0;

        filas.forEach(function (fila) {
            const coincide =
                (!inundacionId || fila.dataset.inundacionId === inundacionId) &&
                (!provinciaId  || fila.dataset.provinciaId  === provinciaId)  &&
                (!municipioId  || fila.dataset.municipioId  === municipioId)   &&
                (!nombre       || fila.dataset.nombre.includes(nombre));

            fila.style.display = coincide ? '' : 'none';
            if (coincide) visibles++;
        });

        // Actualizar contador
        contadorBadge.textContent = visibles + ' víctima(s)';

        // Mostrar fila de "sin resultados" solo si hay filas pero ninguna visible
        if (filas.length > 0) {
            filaVacia.style.display = visibles === 0 ? '' : 'none';
        }

        // Mostrar/ocultar botón "Limpiar filtros"
        const hayFiltros = inundacionId || provinciaId || municipioId || nombre;
        btnLimpiar.classList.toggle('hidden', !hayFiltros);
    }

    // ── Filtrado de municipios por provincia ─────────────────────────────
    function filtrarMunicipios(provinciaId) {
        selMunicipio.innerHTML = '';
        opcionesMunicipio.forEach(function (opt) {
            const optProv = opt.dataset.provinciaId;
            if (!provinciaId || !optProv || optProv === provinciaId) {
                selMunicipio.appendChild(opt.cloneNode(true));
            }
        });
    }

    // ── Event listeners ──────────────────────────────────────────────────

    // Al elegir una inundación → autocompletar provincia y municipio
    selInundacion.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        const municipioId = opt.dataset.municipioId || '';
        const provinciaId = opt.dataset.provinciaId || '';

        if (this.value === '') {
            selProvincia.value = '';
            filtrarMunicipios(null);
            selMunicipio.value = '';
        } else {
            if (provinciaId) selProvincia.value = provinciaId;
            filtrarMunicipios(provinciaId || null);
            if (municipioId) selMunicipio.value = municipioId;
        }

        filtrar();
    });

    // Al elegir provincia → filtrar municipios y limpiar inundación
    selProvincia.addEventListener('change', function () {
        selInundacion.value = '';
        filtrarMunicipios(this.value || null);
        selMunicipio.value = '';
        filtrar();
    });

    // Al elegir municipio → solo filtrar
    selMunicipio.addEventListener('change', filtrar);

    // Al escribir nombre → debounce de 250 ms para no disparar en cada tecla
    let debounceTimer = null;
    inputNombre.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(filtrar, 250);
    });

    // Botón Limpiar
    btnLimpiar.addEventListener('click', function () {
        selInundacion.value = '';
        selProvincia.value  = '';
        filtrarMunicipios(null);
        selMunicipio.value  = '';
        inputNombre.value   = '';
        filtrar();
    });

    // ── Leer parámetros de la URL ──────────────────────────────────────────
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('inundacion_id')) {
        selInundacion.value = urlParams.get('inundacion_id');
        // Simular el change event para que se aplique y actualice provincias/municipios
        selInundacion.dispatchEvent(new Event('change'));
    }
})();
</script>
@endsection
