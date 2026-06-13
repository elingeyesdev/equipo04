@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Gestión de Flota Vehicular</h1>
            <p class="mt-1 text-sm text-gray-600">Registra y administra las ambulancias y vehículos de rescate.</p>
        </div>
        <div>
            <a href="{{ route('vehiculos.mapa', [], false) }}" class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 transition-colors">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 640 640"><path d="M541.9 139.5C546.4 127.7 543.6 114.3 534.7 105.4C525.8 96.5 512.4 93.6 500.6 98.2L84.6 258.2C71.9 263 63.7 275.2 64 288.7C64.3 302.2 73.1 314.1 85.9 318.3L262.7 377.2L321.6 554C325.9 566.8 337.7 575.6 351.2 575.9C364.7 576.2 376.9 568 381.8 555.4L541.8 139.4z"/></svg>
                Ver Mapa en Vivo
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Panel Izquierdo: Formulario -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 h-fit">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Registrar Nuevo Vehículo</h2>

            <form action="{{ route('vehiculos.store', [], false) }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Placa *</label>
                    <input type="text" name="placa" value="{{ old('placa') }}" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 uppercase">
                    @error('placa') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Vehículo *</label>
                    <select name="tipo" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="ambulancia">Ambulancia</option>
                        <option value="camion_rescate">Camión de Rescate</option>
                        <option value="camioneta">Camioneta</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Capacidad (Opcional)</label>
                        <input type="number" name="capacidad" value="{{ old('capacidad') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Ej. 2 personas">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                        <select name="estado" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="activo">Activo (Listo)</option>
                            <option value="inactivo" selected>Inactivo (No Operativo)</option>
                            <option value="mantenimiento">En Mantenimiento</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Conductor Asignado</label>
                    <select name="encargado_carnet" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">Sin Asignar</option>
                        @foreach($usuarios as $user)
                            <option value="{{ $user->carnet }}">{{ $user->name }} ({{ $user->carnet }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Base / Centro Asignado</label>
                    <select name="centro_asistencia_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">Ninguno</option>
                        @foreach($centros as $centro)
                            <option value="{{ $centro->id_centro }}">{{ $centro->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition-colors">
                    Registrar Vehículo
                </button>
            </form>
        </div>

        <!-- Panel Derecho: Lista -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800">Directorio de Vehículos</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Placa</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conductor</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Últ. Ubicación</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($vehiculos as $vehiculo)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3 font-medium text-gray-900">{{ $vehiculo->placa }}</td>
                                <td class="px-3 py-3 text-gray-600 capitalize">{{ str_replace('_', ' ', $vehiculo->tipo) }}</td>
                                <td class="px-3 py-3">
                                    @if($vehiculo->estado == 'activo')
                                        <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Activo</span>
                                    @elseif($vehiculo->estado == 'inactivo')
                                        <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">Inactivo</span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">Mantenimiento</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-gray-600">{{ $vehiculo->encargado ? $vehiculo->encargado->name : 'Sin asignar' }}</td>
                                <td class="px-3 py-3 text-xs text-gray-500">
                                    {{ $vehiculo->ultima_ubicacion_at ? $vehiculo->ultima_ubicacion_at->diffForHumans() : 'Nunca' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500 text-sm">No hay vehículos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
