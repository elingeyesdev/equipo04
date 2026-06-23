@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 font-heading">Detalle de Donación #{{ $inventario->id }}</h1>
            <p class="text-sm text-gray-500 mt-1">Centro de Acopio: {{ $inventario->centro->nombre }}</p>
        </div>
        <a href="{{ route('inventario.show', $inventario->centro_id) }}" class="mt-4 sm:mt-0 text-sm font-medium text-primary-600 hover:text-primary-500">&larr; Volver al Inventario</a>
    </div>

    <!-- Tarjeta Principal -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden mb-8">
        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Información General</h3>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold 
                {{ $inventario->categoria == 'comida' ? 'bg-orange-100 text-orange-800' : 
                  ($inventario->categoria == 'bebida' ? 'bg-blue-100 text-blue-800' : 
                  ($inventario->categoria == 'ropa' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800')) }}">
                {{ strtoupper($inventario->categoria) }}
            </span>
        </div>
        <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <p class="text-sm font-medium text-gray-500">Descripción</p>
                <p class="mt-1 text-sm text-gray-900">{{ $inventario->descripcion ?: 'Sin descripción detallada.' }}</p>
            </div>
            @if($inventario->cantidad)
            <div>
                <p class="text-sm font-medium text-gray-500">Cantidad</p>
                <p class="mt-1 text-lg font-bold text-gray-900">{{ $inventario->cantidad }} {{ $inventario->unidad_medida }}</p>
            </div>
            @endif
            <div>
                <p class="text-sm font-medium text-gray-500">Estado Actual</p>
                <p class="mt-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                        {{ strtoupper(str_replace('_', ' ', $inventario->status)) }}
                    </span>
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Donado por</p>
                <p class="mt-1 text-sm text-gray-900">
                    @if($inventario->is_anonymous)
                        <span class="italic text-gray-400">Anónimo</span>
                    @else
                        {{ $inventario->donor_carnet ? ($inventario->donor ? $inventario->donor->name : 'C.I. ' . $inventario->donor_carnet . ' (No Registrado)') : 'Desconocido' }}
                    @endif
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Registrado por (Autoridad)</p>
                <p class="mt-1 text-sm text-gray-900">
                    {{ $inventario->registrador ? $inventario->registrador->name : 'N/A' }}
                </p>
            </div>
            <div class="sm:col-span-2">
                <p class="text-sm font-medium text-gray-500">Fecha de Ingreso</p>
                <p class="mt-1 text-sm text-gray-900">{{ $inventario->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>

    <!-- Trazabilidad (Timeline) -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden mb-8">
        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Historial de Trazabilidad</h3>
            <p class="text-sm text-gray-500">Seguimiento de las etapas por las que pasó esta donación.</p>
        </div>
        <div class="p-6">
            <div class="flow-root">
                <ul role="list" class="-mb-8">
                    @foreach($inventario->trazabilidad as $index => $trace)
                    <li>
                        <div class="relative pb-8">
                            @if(!$loop->last)
                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white 
                                        {{ $trace->estado_nuevo == 'entregado' ? 'bg-green-500' : 'bg-primary-500' }}">
                                        @if($trace->estado_nuevo == 'entregado')
                                            <!-- Check icon -->
                                            <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                              <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                            </svg>
                                        @else
                                            <div class="h-2.5 w-2.5 bg-white rounded-full"></div>
                                        @endif
                                    </span>
                                </div>
                                <div class="flex flex-col flex-1 pt-1.5 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm text-gray-500">
                                            Cambió a <span class="font-bold text-gray-900">{{ strtoupper(str_replace('_', ' ', $trace->estado_nuevo)) }}</span>
                                        </p>
                                        <div class="text-right text-xs text-gray-500 whitespace-nowrap">
                                            <time datetime="{{ $trace->fecha_actualizacion }}">{{ \Carbon\Carbon::parse($trace->fecha_actualizacion)->format('d M, Y H:i') }}</time>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-700 italic">"{{ $trace->observacion ?? 'Sin observaciones.' }}"</p>
                                    <p class="text-xs text-gray-500">Actualizado por: <span class="font-medium text-gray-700">{{ $trace->registrador ? $trace->registrador->name : 'Sistema/Autoridad' }}</span></p>
                                    
                                    @if($trace->photo_path)
                                    <div class="mt-2">
                                        <p class="text-xs font-semibold text-gray-500 mb-1">Evidencia Fotográfica:</p>
                                        <a href="{{ Storage::url($trace->photo_path) }}" target="_blank">
                                            <img src="{{ Storage::url($trace->photo_path) }}" alt="Evidencia de entrega" class="w-48 h-auto rounded-lg border border-gray-200 shadow-sm hover:opacity-90 transition-opacity">
                                        </a>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
