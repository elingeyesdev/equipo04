@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Cabecera --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('victimas.show', ['id' => $victima->id], false) }}"
           class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Volver a la ficha
        </a>
    </div>

    <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Editar Víctima</h1>
        <p class="mt-1 text-sm text-gray-500">Modifique los datos de <strong>{{ $victima->nombre_completo }}</strong>.</p>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         FORMULARIO DE EDICIÓN (PUT)
         IMPORTANTE: El formulario de eliminar está FUERA de este form,
         más abajo, para evitar el bug de forms HTML anidados.
    ══════════════════════════════════════════════════════════════════ --}}
    <form id="form-editar"
          method="POST"
          action="{{ route('victimas.update', ['id' => $victima->id], false) }}"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf
        @method('PUT')

        {{-- ── 1. Inundación asociada ──────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-blue-50 border-b border-blue-100">
                <h2 class="text-sm font-semibold text-blue-800">1. Inundación asociada</h2>
                <p class="text-xs text-blue-600 mt-0.5">
                    Busca y selecciona el evento de inundación al que pertenece esta víctima.
                    <span class="font-medium">Haz clic una vez para ver el mapa, dos veces para confirmar.</span>
                </p>
            </div>
            <div class="p-5">
                @include('victimas._flood_picker', [
                    'inundaciones' => $inundaciones,
                    'selectedId'   => old('inundacion_id', $victima->inundacion_id),
                ])
            </div>
        </div>

        {{-- ── 2. Datos personales ─────────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-700">2. Datos personales de la víctima</h2>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label for="nombre_completo" class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre Completo <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nombre_completo" id="nombre_completo"
                           value="{{ old('nombre_completo', $victima->nombre_completo) }}"
                           required maxlength="255"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 @error('nombre_completo') border-red-300 @enderror">
                    @error('nombre_completo')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="carnet" class="block text-sm font-medium text-gray-700 mb-1">Carnet de Identidad</label>
                    <input type="text" name="carnet" id="carnet"
                           value="{{ old('carnet', $victima->carnet) }}"
                           maxlength="20"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 @error('carnet') border-red-300 @enderror">
                    @error('carnet')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="fecha_nacimiento" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                           value="{{ old('fecha_nacimiento', $victima->fecha_nacimiento?->format('Y-m-d')) }}"
                           max="{{ date('Y-m-d', strtotime('-1 day')) }}"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 @error('fecha_nacimiento') border-red-300 @enderror">
                    @error('fecha_nacimiento')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ── 3. Estado y descripción ─────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-700">3. Estado y descripción</h2>
            </div>
            <div class="p-5 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Estado de la víctima <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @php
                            $estadoConfig = [
                                'perdido'    => ['icon' => '<svg class="w-5 h-5 mb-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>', 'bg' => 'bg-yellow-50 border-yellow-300 text-yellow-800', 'checked' => 'ring-2 ring-yellow-400'],
                                'encontrado' => ['icon' => '<svg class="w-4 h-4 mb-1 inline-block fill-current" viewBox="0 0 640 640"><path d="M530.8 134.1C545.1 144.5 548.3 164.5 537.9 178.8L281.9 530.8C276.4 538.4 267.9 543.1 258.5 543.9C249.1 544.7 240 541.2 233.4 534.6L105.4 406.6C92.9 394.1 92.9 373.8 105.4 361.3C117.9 348.8 138.2 348.8 150.7 361.3L252.2 462.8L486.2 141.1C496.6 126.8 516.6 123.6 530.9 134z"/></svg>', 'bg' => 'bg-green-50 border-green-300 text-green-800', 'checked' => 'ring-2 ring-green-400'],
                                'herido'     => ['icon' => '<svg class="w-5 h-5 mb-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>', 'bg' => 'bg-orange-50 border-orange-300 text-orange-800', 'checked' => 'ring-2 ring-orange-400'],
                                'fallecido'  => ['icon' => '<svg class="w-5 h-5 mb-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-3-4h6"></path></svg>', 'bg' => 'bg-red-50 border-red-300 text-red-800', 'checked' => 'ring-2 ring-red-400'],
                            ];
                            $estadoActual = old('estado', $victima->estado);
                        @endphp
                        @foreach($estadoLabels as $val => $label)
                            @php $cfg = $estadoConfig[$val] ?? []; @endphp
                            <label for="estado_{{ $val }}"
                                   class="flex flex-col items-center justify-center p-3 rounded-lg border-2 cursor-pointer transition-all
                                          {{ $cfg['bg'] ?? 'border-gray-200' }}
                                          estado-radio-label {{ ($estadoActual === $val) ? ($cfg['checked'] ?? '') : '' }}"
                                   data-checked-class="{{ $cfg['checked'] ?? '' }}">
                                <input type="radio" name="estado" id="estado_{{ $val }}"
                                       value="{{ $val }}"
                                       class="sr-only"
                                       {{ $estadoActual === $val ? 'checked' : '' }}>
                                <span class="text-2xl mb-1">{!! $cfg['icon'] ?? '' !!}</span>
                                <span class="text-xs font-semibold">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('estado')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción adicional</label>
                    <textarea name="descripcion" id="descripcion" rows="4" maxlength="2000"
                              placeholder="Condición de la víctima, lugar donde fue encontrada..."
                              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 @error('descripcion') border-red-300 @enderror">{{ old('descripcion', $victima->descripcion) }}</textarea>
                    @error('descripcion')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ── 4. Fotografía ───────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-700">4. Fotografía</h2>
            </div>
            <div class="p-5 space-y-4">
                @if ($victima->foto_path)
                    <div class="flex items-center gap-4 p-3 rounded-lg border border-gray-200 bg-gray-50">
                        <img src="{{ asset('storage/' . $victima->foto_path) }}"
                             alt="Foto actual"
                             id="foto-actual-img"
                             class="h-20 w-20 rounded-lg object-cover border border-gray-300 shadow-sm transition-all duration-200">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Foto actual</p>
                            <label class="mt-2 flex items-center gap-2 cursor-pointer text-sm text-red-600 hover:text-red-800">
                                <input type="checkbox" name="eliminar_foto" id="eliminar_foto" value="1"
                                       class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                Eliminar foto actual
                            </label>
                        </div>
                    </div>
                @endif

                <div class="flex items-start gap-5">
                    <div id="foto-preview-container"
                         class="flex-shrink-0 w-24 h-24 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center overflow-hidden">
                        <svg id="foto-placeholder" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                        </svg>
                        <img id="foto-preview" src="" alt="Nueva foto" class="hidden w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        <label for="foto" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $victima->foto_path ? 'Reemplazar foto' : 'Subir foto' }}
                        </label>
                        <input type="file" name="foto" id="foto"
                               accept="image/jpeg,image/png,image/webp"
                               class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 @error('foto') border-red-300 @enderror">
                        <p class="mt-1 text-xs text-gray-400">JPG, PNG o WebP · Máximo 4 MB</p>
                        @error('foto')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Botones de guardado (DENTRO del form de edición) ────────────── --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('victimas.show', ['id' => $victima->id], false) }}"
               class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                Cancelar
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Guardar cambios
            </button>
        </div>
    </form>{{-- FIN del formulario de edición --}}

    {{-- ══════════════════════════════════════════════════════════════════
         ZONA DE PELIGRO — formulario SEPARADO (hermano, no hijo del form anterior)
         Esto evita el bug de HTML con forms anidados.
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="rounded-xl border border-red-200 bg-red-50 overflow-hidden">
        <div class="px-5 py-3 bg-red-100 border-b border-red-200">
            <h3 class="text-sm font-semibold text-red-700">Zona de peligro</h3>
        </div>
        <div class="px-5 py-4 flex items-center justify-between gap-4">
            <p class="text-sm text-red-600">
                Eliminar esta víctima es una acción permanente y no se puede deshacer.
            </p>
            <form method="POST"
                  action="{{ route('victimas.destroy', ['id' => $victima->id], false) }}"
                  onsubmit="return confirm('¿Eliminar definitivamente a {{ addslashes($victima->nombre_completo) }}? Esta acción no se puede deshacer.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex flex-shrink-0 items-center gap-2 rounded-lg border border-red-400 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-600 hover:text-white hover:border-red-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    Eliminar víctima
                </button>
            </form>
        </div>
    </div>

</div>

<script>
(function () {
    'use strict';

    // ── Radios de estado ─────────────────────────────────────────────────
    const radioLabels = document.querySelectorAll('.estado-radio-label');
    document.querySelectorAll('input[name="estado"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            radioLabels.forEach(function (lbl) {
                lbl.classList.remove(...(lbl.dataset.checkedClass || '').split(' ').filter(Boolean));
            });
            if (radio.checked) {
                const cls = (radio.closest('label').dataset.checkedClass || '').split(' ').filter(Boolean);
                radio.closest('label').classList.add(...cls);
            }
        });
    });

    // ── Preview de nueva foto ────────────────────────────────────────────
    const inputFoto   = document.getElementById('foto');
    const previewImg  = document.getElementById('foto-preview');
    const placeholder = document.getElementById('foto-placeholder');

    if (inputFoto) {
        inputFoto.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) {
                previewImg.classList.add('hidden');
                placeholder.classList.remove('hidden');
                return;
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
                previewImg.classList.remove('hidden');
                placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        });
    }

    // ── Checkbox "Eliminar foto": efecto visual sobre la foto actual ─────
    const chkEliminar   = document.getElementById('eliminar_foto');
    const fotoActualImg = document.getElementById('foto-actual-img');

    if (chkEliminar && fotoActualImg) {
        chkEliminar.addEventListener('change', function () {
            fotoActualImg.style.opacity = this.checked ? '0.3' : '1';
            fotoActualImg.style.filter  = this.checked ? 'grayscale(100%)' : 'none';
        });
    }
})();
</script>
@endsection
