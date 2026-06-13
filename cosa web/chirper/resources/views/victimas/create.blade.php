@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- ══════════════════════════════════════════════════════════════════
         CABECERA
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('victimas.index', [], false) }}"
           class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Volver al listado
        </a>
    </div>

    <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Registrar Víctima</h1>
        <p class="mt-1 text-sm text-gray-500">Complete el formulario para registrar a una persona afectada.</p>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         FORMULARIO
    ══════════════════════════════════════════════════════════════════ --}}
    <form method="POST"
          action="{{ route('victimas.store', [], false) }}"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf

        {{-- ── Selección de Inundación ─────────────────────────────────────── --}}
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
                    'selectedId'   => old('inundacion_id'),
                ])
            </div>
        </div>


        {{-- ── Datos Personales ────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-700">2. Datos personales de la víctima</h2>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">

                {{-- Nombre Completo --}}
                <div class="sm:col-span-2">
                    <label for="nombre_completo" class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre Completo <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nombre_completo" id="nombre_completo"
                           value="{{ old('nombre_completo') }}"
                           placeholder="Ej. Juan Pérez García"
                           required maxlength="255"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 @error('nombre_completo') border-red-300 @enderror">
                    @error('nombre_completo')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Carnet --}}
                <div>
                    <label for="carnet" class="block text-sm font-medium text-gray-700 mb-1">
                        Carnet de Identidad
                    </label>
                    <input type="text" name="carnet" id="carnet"
                           value="{{ old('carnet') }}"
                           placeholder="Ej. 1234567"
                           maxlength="20"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 @error('carnet') border-red-300 @enderror">
                    @error('carnet')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Fecha de Nacimiento --}}
                <div>
                    <label for="fecha_nacimiento" class="block text-sm font-medium text-gray-700 mb-1">
                        Fecha de Nacimiento
                    </label>
                    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                           value="{{ old('fecha_nacimiento') }}"
                           max="{{ date('Y-m-d', strtotime('-1 day')) }}"
                           class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 @error('fecha_nacimiento') border-red-300 @enderror">
                    @error('fecha_nacimiento')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ── Estado y Descripción ────────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-700">3. Estado y descripción</h2>
            </div>
            <div class="p-5 space-y-5">

                {{-- Estado (botones de radio estilizados) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Estado de la víctima <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @php
                            $estadoConfig = [
                                'perdido'    => ['icon' => '<svg class="w-4 h-4 mb-1 inline-block fill-current" viewBox="0 0 640 640"><path d="M480 272C480 317.9 465.1 360.3 440 394.7L566.6 521.4C579.1 533.9 579.1 554.2 566.6 566.7C554.1 579.2 533.8 579.2 521.3 566.7L394.7 440C360.3 465.1 317.9 480 272 480C157.1 480 64 386.9 64 272C64 157.1 157.1 64 272 64C386.9 64 480 157.1 480 272zM272 416C351.5 416 416 351.5 416 272C416 192.5 351.5 128 272 128C192.5 128 128 192.5 128 272C128 351.5 192.5 416 272 416z"/></svg>', 'bg' => 'bg-yellow-50 border-yellow-300 text-yellow-800', 'checked' => 'ring-2 ring-yellow-400'],
                                'encontrado' => ['icon' => '<svg class="w-4 h-4 mb-1 inline-block fill-current" viewBox="0 0 640 640"><path d="M530.8 134.1C545.1 144.5 548.3 164.5 537.9 178.8L281.9 530.8C276.4 538.4 267.9 543.1 258.5 543.9C249.1 544.7 240 541.2 233.4 534.6L105.4 406.6C92.9 394.1 92.9 373.8 105.4 361.3C117.9 348.8 138.2 348.8 150.7 361.3L252.2 462.8L486.2 141.1C496.6 126.8 516.6 123.6 530.9 134z"/></svg>', 'bg' => 'bg-green-50 border-green-300 text-green-800', 'checked' => 'ring-2 ring-green-400'],
                                'herido'     => ['icon' => '<svg class="w-4 h-4 mb-1 inline-block fill-current" viewBox="0 0 640 640"><path d="M338.7 144L430 144C419.3 119.4 400.5 99.1 377.1 86.4L338.7 144zM337.8 73.3C332 72.4 326 72 320 72C270.8 72 228.5 101.6 210 144L290.6 144L337.7 73.3zM320 312C386.3 312 440 258.3 440 192L200 192C200 258.3 253.7 312 320 312zM194.7 405.8C145.3 434.2 112 487.5 112 548.6C112 563.7 124.3 576 139.4 576L290.4 576L194.6 405.8zM239.8 388.1L282.5 464L368 464C412.2 464 448 499.8 448 544C448 555.4 445.6 566.2 441.3 576L500.5 576C515.6 576 527.9 563.7 527.9 548.6C527.9 457.7 454.2 384 363.3 384L276.4 384C263.8 384 251.5 385.4 239.7 388.1zM309.5 512L345.5 576L368 576C385.7 576 400 561.7 400 544C400 526.3 385.7 512 368 512L309.5 512z"/></svg>', 'bg' => 'bg-orange-50 border-orange-300 text-orange-800', 'checked' => 'ring-2 ring-orange-400'],
                                'fallecido'  => ['icon' => '<svg class="w-4 h-4 mb-1 inline-block fill-current" viewBox="0 0 640 640"><path d="M304 64C277.5 64 256 85.5 256 112L256 192L176 192C149.5 192 128 213.5 128 240L128 272C128 298.5 149.5 320 176 320L256 320L256 528C256 554.5 277.5 576 304 576L336 576C362.5 576 384 554.5 384 528L384 320L464 320C490.5 320 512 298.5 512 272L512 240C512 213.5 490.5 192 464 192L384 192L384 112C384 85.5 362.5 64 336 64L304 64z"/></svg>', 'bg' => 'bg-red-50 border-red-300 text-red-800', 'checked' => 'ring-2 ring-red-400'],
                            ];
                        @endphp
                        @foreach($estadoLabels as $val => $label)
                            @php $cfg = $estadoConfig[$val] ?? []; @endphp
                            <label for="estado_{{ $val }}"
                                   class="flex flex-col items-center justify-center p-3 rounded-lg border-2 cursor-pointer transition-all
                                          {{ $cfg['bg'] ?? 'border-gray-200' }}
                                          estado-radio-label {{ (old('estado', 'perdido') === $val) ? ($cfg['checked'] ?? '') : '' }}"
                                   data-checked-class="{{ $cfg['checked'] ?? '' }}">
                                <input type="radio" name="estado" id="estado_{{ $val }}"
                                       value="{{ $val }}"
                                       class="sr-only"
                                       {{ old('estado', 'perdido') === $val ? 'checked' : '' }}>
                                <span class="text-2xl mb-1">{!! $cfg['icon'] ?? '' !!}</span>
                                <span class="text-xs font-semibold">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('estado')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Descripción --}}
                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">
                        Descripción adicional
                    </label>
                    <textarea name="descripcion" id="descripcion" rows="4"
                              maxlength="2000"
                              placeholder="Condición de la víctima, lugar donde fue encontrada, información relevante..."
                              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 @error('descripcion') border-red-300 @enderror">{{ old('descripcion') }}</textarea>
                    @error('descripcion')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ── Fotografía ──────────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-700">4. Fotografía (opcional)</h2>
            </div>
            <div class="p-5">
                <div class="flex items-start gap-5">
                    {{-- Preview --}}
                    <div id="foto-preview-container"
                         class="flex-shrink-0 w-24 h-24 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center overflow-hidden">
                        <svg id="foto-placeholder" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                        </svg>
                        <img id="foto-preview" src="" alt="Preview" class="hidden w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        <label for="foto" class="block text-sm font-medium text-gray-700 mb-1">Subir foto</label>
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

        {{-- ── Botones de acción ───────────────────────────────────────────── --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('victimas.index', [], false) }}"
               class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                Cancelar
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Registrar Víctima
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    'use strict';

    // ── Radios de estado: estilos visuales ──────────────────────────────
    const radioLabels = document.querySelectorAll('.estado-radio-label');
    const radios      = document.querySelectorAll('input[name="estado"]');

    radios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            radioLabels.forEach(function (lbl) {
                lbl.classList.remove(...(lbl.dataset.checkedClass || '').split(' ').filter(Boolean));
            });
            const parentLabel = radio.closest('label');
            if (parentLabel && radio.checked) {
                const cls = (parentLabel.dataset.checkedClass || '').split(' ').filter(Boolean);
                parentLabel.classList.add(...cls);
            }
        });
    });

    // ── Preview de foto ──────────────────────────────────────────────────
    const inputFoto    = document.getElementById('foto');
    const previewImg   = document.getElementById('foto-preview');
    const placeholder  = document.getElementById('foto-placeholder');

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
})();
</script>
@endsection
