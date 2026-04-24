@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">Reportes</h1>
            <p class="mt-1 text-sm text-gray-600">Listado de reportes enviados.</p>
        </div>
        <a href="{{ route('reports.create', [], false) }}" class="inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
            Nuevo reporte
        </a>
    </div>

    @if (!empty($error))
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm">
            {{ $error }}
        </div>
    @endif

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-700">
                <tr>
                    <th class="text-left font-medium px-3 py-2">ID</th>
                    <th class="text-left font-medium px-3 py-2">Severidad</th>
                    <th class="text-left font-medium px-3 py-2">Estado</th>
                    <th class="text-left font-medium px-3 py-2">Creado</th>
                    <th class="text-left font-medium px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($reports as $report)
                    @php($id = data_get($report, 'id'))
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2">{{ $id }}</td>
                        <td class="px-3 py-2">{{ data_get($report, 'severity', '') }}</td>
                        <td class="px-3 py-2">{{ data_get($report, 'status', '') }}</td>
                        <td class="px-3 py-2">{{ data_get($report, 'created_at', '') }}</td>
                        <td class="px-3 py-2 text-right">
                            @if ($id !== null)
                                <a class="text-gray-700 hover:text-gray-900 hover:underline underline-offset-4" href="{{ route('reports.show', ['id' => $id], false) }}">Ver</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-3 py-6 text-gray-600" colspan="5">No hay reportes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @php($currentPage = (int) ($meta['current_page'] ?? 1))
    @php($lastPage = (int) ($meta['last_page'] ?? 1))

    @if ($lastPage > 1)
        <div class="mt-5 flex items-center justify-between text-sm">
            <div class="text-gray-600">Página {{ $currentPage }} de {{ $lastPage }}</div>
            <div class="flex items-center gap-3">
                @if ($currentPage > 1)
                    <a class="rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900" href="{{ route('reports.index', ['page' => $currentPage - 1], false) }}">Anterior</a>
                @endif
                @if ($currentPage < $lastPage)
                    <a class="rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900" href="{{ route('reports.index', ['page' => $currentPage + 1], false) }}">Siguiente</a>
                @endif
            </div>
        </div>
    @endif
@endsection
