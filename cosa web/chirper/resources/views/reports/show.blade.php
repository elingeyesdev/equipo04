@extends('layouts.app')

@section('content')
    @php($apiUser = (array) session('api_user', []))
    @php($apiRole = (string) ($apiUser['role'] ?? ''))

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">Reporte #{{ $report['id'] ?? '' }}</h1>
            <p class="mt-1 text-sm text-gray-600">Detalle y seguimiento del reporte.</p>
        </div>
        <a href="{{ route('reports.index', [], false) }}" class="rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">Volver</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs text-gray-600">Severidad</div>
            <div class="font-medium">{{ $report['severity'] ?? '' }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs text-gray-600">Estado</div>
            <div class="font-medium">{{ $report['status'] ?? '' }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs text-gray-600">Creado</div>
            <div class="font-medium">{{ $report['created_at'] ?? '' }}</div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm mb-6">
        <div class="text-sm font-medium mb-3">Ubicación</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div class="rounded-md bg-gray-50 p-3">
                <div class="text-xs text-gray-600">Latitud</div>
                <div class="font-medium">{{ $report['latitude'] ?? '' }}</div>
            </div>
            <div class="rounded-md bg-gray-50 p-3">
                <div class="text-xs text-gray-600">Longitud</div>
                <div class="font-medium">{{ $report['longitude'] ?? '' }}</div>
            </div>
        </div>
        @if (!empty($report['address']))
            <div class="mt-4 text-sm text-gray-600">Dirección</div>
            <div class="text-sm">{{ $report['address'] }}</div>
        @endif
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm mb-6">
        <div class="text-sm font-medium mb-3">Descripción</div>
        <div class="text-sm whitespace-pre-wrap text-gray-800">{{ $report['description'] ?? '' }}</div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm mb-6">
        <div class="text-sm font-medium mb-3">Predicción básica de llegada</div>
        @if (!empty($eta))
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                <div class="rounded-md bg-gray-50 p-3">
                    <div class="text-xs text-gray-600">Centro más cercano</div>
                    <div class="font-medium">{{ $eta['name'] }}</div>
                </div>
                <div class="rounded-md bg-gray-50 p-3">
                    <div class="text-xs text-gray-600">Distancia aproximada</div>
                    <div class="font-medium">{{ number_format((float) $eta['distance_km'], 2) }} km</div>
                </div>
                <div class="rounded-md bg-gray-50 p-3">
                    <div class="text-xs text-gray-600">Tiempo estimado</div>
                    <div class="font-medium">{{ (int) $eta['eta_minutes'] }} min</div>
                </div>
            </div>
            <p class="mt-3 text-xs text-gray-500">
                Estimación simple basada en distancia en línea recta (sin considerar rutas, tráfico ni bloqueos).
            </p>
        @else
            <div class="text-sm text-gray-600">
                No se pudo calcular el tiempo de llegada (faltan coordenadas o centros registrados).
            </div>
        @endif
    </div>

    @if ($apiRole === 'authority')
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm mb-6">
            <div class="font-medium mb-4">Acciones de autoridad</div>

            <form method="POST" action="{{ route('reports.status.update', ['id' => $report['id']], false) }}" class="flex items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1" for="status">Cambiar estado</label>
                    @php($current = (string) ($report['status'] ?? 'open'))
                    <select id="status" name="status" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900" required>
                        <option value="open" @selected($current === 'open')>open</option>
                        <option value="in_progress" @selected($current === 'in_progress')>in_progress</option>
                        <option value="resolved" @selected($current === 'resolved')>resolved</option>
                        <option value="closed" @selected($current === 'closed')>closed</option>
                        <option value="false_report" @selected($current === 'false_report')>false_report</option>
                    </select>
                    @error('status')
                        <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">Guardar</button>
            </form>

            <div class="border-t border-gray-200 my-4"></div>

            <form method="POST" action="{{ route('reports.responses.store', ['id' => $report['id']], false) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1" for="message">Responder</label>
                    <textarea id="message" name="message" rows="3" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900" required>{{ old('message') }}</textarea>
                    @error('message')
                        <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">Enviar respuesta</button>
            </form>
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <div class="font-medium mb-4">Respuestas</div>

        @php($responses = (array) ($report['responses'] ?? []))

        @if (count($responses) === 0)
            <div class="text-sm text-gray-600">Sin respuestas.</div>
        @else
            <div class="space-y-3">
                @foreach ($responses as $response)
                    <div class="rounded-md border border-gray-200 p-4">
                        <div class="text-sm whitespace-pre-wrap">{{ $response['message'] ?? '' }}</div>
                        <div class="text-xs text-gray-600 mt-2">
                            {{ $response['created_at'] ?? '' }}
                            @php($authority = (array) ($response['authority'] ?? []))
                            @if (!empty($authority))
                                — {{ (string) ($authority['name'] ?? '') }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
