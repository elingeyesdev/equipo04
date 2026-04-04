@extends('layouts.app')

@section('content')
    <div class="max-w-xl">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-semibold">Crear reporte</h1>
            <a href="{{ route('reports.index') }}" class="text-sm hover:underline">Volver</a>
        </div>

        <form method="POST" action="{{ route('reports.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1" for="latitude">Latitude</label>
                    <input id="latitude" name="latitude" type="number" step="any" value="{{ old('latitude') }}" class="w-full rounded-md border border-gray-300 px-3 py-2" required>
                    @error('latitude')
                        <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="longitude">Longitude</label>
                    <input id="longitude" name="longitude" type="number" step="any" value="{{ old('longitude') }}" class="w-full rounded-md border border-gray-300 px-3 py-2" required>
                    @error('longitude')
                        <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="address">Dirección (opcional)</label>
                <input id="address" name="address" type="text" value="{{ old('address') }}" class="w-full rounded-md border border-gray-300 px-3 py-2">
                @error('address')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="severity">Severidad</label>
                <select id="severity" name="severity" class="w-full rounded-md border border-gray-300 px-3 py-2" required>
                    @php($sev = old('severity', 'medium'))
                    <option value="low" @selected($sev === 'low')>low</option>
                    <option value="medium" @selected($sev === 'medium')>medium</option>
                    <option value="high" @selected($sev === 'high')>high</option>
                </select>
                @error('severity')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="description">Descripción</label>
                <textarea id="description" name="description" rows="5" class="w-full rounded-md border border-gray-300 px-3 py-2" required>{{ old('description') }}</textarea>
                @error('description')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-md bg-gray-900 text-white px-4 py-2 text-sm">Crear</button>
            </div>
        </form>
    </div>
@endsection
