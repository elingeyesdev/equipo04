@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-lg font-semibold">Reportes</h1>
        <a href="{{ route('reports.create') }}" class="rounded-md bg-gray-900 text-white px-4 py-2 text-sm">Nuevo reporte</a>
    </div>

    @if (!empty($error))
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm">
            {{ $error }}
        </div>
    @endif

    <div class="overflow-hidden rounded-md border border-gray-200">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left font-medium px-3 py-2">ID</th>
                    <th class="text-left font-medium px-3 py-2">Severidad</th>
                    <th class="text-left font-medium px-3 py-2">Estado</th>
                    <th class="text-left font-medium px-3 py-2">Creado</th>
                    <th class="text-left font-medium px-3 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    @php($id = $report['id'] ?? null)
                    <tr class="border-t border-gray-200">
                        <td class="px-3 py-2">{{ $id }}</td>
                        <td class="px-3 py-2">{{ $report['severity'] ?? '' }}</td>
                        <td class="px-3 py-2">{{ $report['status'] ?? '' }}</td>
                        <td class="px-3 py-2">{{ $report['created_at'] ?? '' }}</td>
                        <td class="px-3 py-2 text-right">
                            @if ($id !== null)
                                <a class="hover:underline" href="{{ route('reports.show', ['id' => $id]) }}">Ver</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr class="border-t border-gray-200">
                        <td class="px-3 py-6 text-gray-600" colspan="5">No hay reportes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @php($currentPage = (int) ($meta['current_page'] ?? 1))
    @php($lastPage = (int) ($meta['last_page'] ?? 1))

    @if ($lastPage > 1)
        <div class="mt-4 flex items-center justify-between text-sm">
            <div class="text-gray-600">Página {{ $currentPage }} de {{ $lastPage }}</div>
            <div class="flex items-center gap-3">
                @if ($currentPage > 1)
                    <a class="hover:underline" href="{{ route('reports.index', ['page' => $currentPage - 1]) }}">Anterior</a>
                @endif
                @if ($currentPage < $lastPage)
                    <a class="hover:underline" href="{{ route('reports.index', ['page' => $currentPage + 1]) }}">Siguiente</a>
                @endif
            </div>
        </div>
    @endif
@endsection
