@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 font-heading">Inventario: {{ $centro->nombre }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $centro->direccion }}</p>
        </div>
        <a href="{{ route('inventario.index') }}" class="mt-4 sm:mt-0 text-sm font-medium text-primary-600 hover:text-primary-500">&larr; Volver a Centros</a>
    </div>

    <!-- Formulario Agregar Item -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Registrar Nuevo Ítem</h2>
        <form action="{{ route('inventario.store', $centro->id_centro) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="categoria" class="block text-sm font-medium text-gray-700">Categoría</label>
                    <select id="categoria" name="categoria" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="comida">Comida / Víveres</option>
                        <option value="bebida">Agua / Bebidas</option>
                        <option value="ropa">Ropa / Abrigo</option>
                        <option value="medicamentos">Medicamentos</option>
                        <option value="dinero">Dinero</option>
                        <option value="otros">Otros</option>
                    </select>
                </div>
                <div>
                    <label for="is_anonymous" class="block text-sm font-medium text-gray-700">Registrar como Anónimo</label>
                    <select id="is_anonymous" name="is_anonymous" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="0">No (Registrar bajo mi Carnet)</option>
                        <option value="1">Sí (Donante Anónimo)</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción Detallada</label>
                    <textarea id="descripcion" name="descripcion" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" placeholder="Ej. 50 botellas de agua de 2L..."></textarea>
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                    Guardar Registro
                </button>
            </div>
        </form>
    </div>

    <!-- Lista de Inventario -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Ítems en Inventario</h3>
        </div>
        <ul class="divide-y divide-gray-200">
            @forelse($inventario as $item)
            <li class="p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        {{ $item->categoria == 'comida' ? 'bg-orange-100 text-orange-800' : 
                          ($item->categoria == 'bebida' ? 'bg-blue-100 text-blue-800' : 
                          ($item->categoria == 'ropa' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800')) }}">
                        {{ ucfirst($item->categoria) }}
                    </span>
                    <span class="text-sm text-gray-500">{{ $item->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <p class="text-gray-900 font-medium mb-1">{{ $item->descripcion }}</p>
                <p class="text-sm text-gray-500 mb-4">
                    Registrado por: 
                    @if($item->is_anonymous)
                        <span class="italic">Anónimo</span>
                    @else
                        {{ $item->donor ? $item->donor->name : 'Desconocido' }}
                    @endif
                </p>

                <!-- Trazabilidad / Estado actual -->
                <div class="bg-gray-50 rounded-md p-4 mb-4 border border-gray-100">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado Actual:</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                            {{ strtoupper($item->status) }}
                        </span>
                    </div>

                    <form action="{{ route('inventario.updateStatus', $item->id) }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="block w-full sm:w-auto rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value="recibido" {{ $item->status == 'recibido' ? 'selected' : '' }}>Recibido (En espera)</option>
                            <option value="en_inventario" {{ $item->status == 'en_inventario' ? 'selected' : '' }}>En Inventario (Almacenado)</option>
                            <option value="entregado" {{ $item->status == 'entregado' ? 'selected' : '' }}>Entregado / Despachado</option>
                        </select>
                        <input type="text" name="usage_details" placeholder="Observación o destino..." value="{{ $item->usage_details }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <button type="submit" class="px-3 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 whitespace-nowrap">Actualizar</button>
                    </form>
                </div>

                <!-- Historial de Trazabilidad -->
                @if($item->trazabilidad->count() > 0)
                <div class="mt-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Historial de Transparencia</h4>
                    <ul class="text-sm text-gray-600 space-y-2 border-l-2 border-gray-200 pl-4 ml-2">
                        @foreach($item->trazabilidad as $t)
                        <li class="relative">
                            <span class="absolute -left-[21px] top-1.5 w-2.5 h-2.5 rounded-full bg-primary-400"></span>
                            <span class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($t->fecha_actualizacion)->format('d/m/Y H:i') }}</span> &mdash;
                            Cambió a <span class="font-semibold">{{ $t->estado_nuevo }}</span>: 
                            <span class="italic">{{ $t->observacion ?? 'Sin observaciones' }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </li>
            @empty
            <li class="p-6 text-center text-gray-500">No hay ítems registrados en este centro aún.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
