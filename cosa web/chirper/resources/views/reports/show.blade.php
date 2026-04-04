@extends('layouts.app')

@section('content')
    @php($apiUser = (array) session('api_user', []))
    @php($apiRole = (string) ($apiUser['role'] ?? ''))

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-lg font-semibold">Reporte #{{ $report['id'] ?? '' }}</h1>
        <a href="{{ route('reports.index') }}" class="text-sm hover:underline">Volver</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="rounded-md border border-gray-200 p-3">
            <div class="text-xs text-gray-600">Severidad</div>
            <div class="font-medium">{{ $report['severity'] ?? '' }}</div>
        </div>
        <div class="rounded-md border border-gray-200 p-3">
            <div class="text-xs text-gray-600">Estado</div>
            <div class="font-medium">{{ $report['status'] ?? '' }}</div>
        </div>
        <div class="rounded-md border border-gray-200 p-3">
            <div class="text-xs text-gray-600">Creado</div>
            <div class="font-medium">{{ $report['created_at'] ?? '' }}</div>
        </div>
    </div>

    <div class="rounded-md border border-gray-200 p-4 mb-6">
        <div class="text-sm text-gray-600 mb-1">Ubicación</div>
        <div class="text-sm">Lat: {{ $report['latitude'] ?? '' }} / Lng: {{ $report['longitude'] ?? '' }}</div>
        @if (!empty($report['address']))
            <div class="text-sm text-gray-600 mt-2">Dirección</div>
            <div class="text-sm">{{ $report['address'] }}</div>
        @endif
    </div>

    <div class="rounded-md border border-gray-200 p-4 mb-6">
        <div class="text-sm text-gray-600 mb-1">Descripción</div>
        <div class="text-sm whitespace-pre-wrap">{{ $report['description'] ?? '' }}</div>
    </div>

    @if ($apiRole === 'authority')
        <div class="rounded-md border border-gray-200 p-4 mb-6">
            <div class="font-medium mb-3">Acciones de autoridad</div>

            <form method="POST" action="{{ route('reports.status.update', ['id' => $report['id']]) }}" class="flex items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1" for="status">Cambiar estado</label>
                    @php($current = (string) ($report['status'] ?? 'open'))
                    <select id="status" name="status" class="w-full rounded-md border border-gray-300 px-3 py-2" required>
                        <option value="open" @selected($current === 'open')>open</option>
                        <option value="in_progress" @selected($current === 'in_progress')>in_progress</option>
                        <option value="resolved" @selected($current === 'resolved')>resolved</option>
                        <option value="closed" @selected($current === 'closed')>closed</option>
                    </select>
                    @error('status')
                        <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="rounded-md bg-gray-900 text-white px-4 py-2 text-sm">Guardar</button>
            </form>

            <div class="border-t border-gray-200 my-4"></div>

            <form method="POST" action="{{ route('reports.responses.store', ['id' => $report['id']]) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1" for="message">Responder</label>
                    <textarea id="message" name="message" rows="3" class="w-full rounded-md border border-gray-300 px-3 py-2" required>{{ old('message') }}</textarea>
                    @error('message')
                        <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="rounded-md bg-gray-900 text-white px-4 py-2 text-sm">Enviar respuesta</button>
            </form>
        </div>
    @endif

    <div class="rounded-md border border-gray-200 p-4">
        <div class="font-medium mb-3">Respuestas</div>

        @php($responses = (array) ($report['responses'] ?? []))

        @if (count($responses) === 0)
            <div class="text-sm text-gray-600">Sin respuestas.</div>
        @else
            <div class="space-y-3">
                @foreach ($responses as $response)
                    <div class="rounded-md border border-gray-200 p-3">
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
