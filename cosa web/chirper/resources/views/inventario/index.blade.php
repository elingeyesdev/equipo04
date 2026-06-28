@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight font-heading">Centros de Acopio e Inventario</h1>
            <p class="mt-2 text-sm text-gray-500">Gestione el inventario de suministros, donaciones y recursos logísticos en cada centro.</p>
        </div>
    </div>

    <!-- Estadísticas -->
    <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border border-gray-100">
            <dt class="truncate text-sm font-medium text-gray-500">Centros Activos</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($stats['total_centros']) }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border border-gray-100">
            <dt class="truncate text-sm font-medium text-gray-500">Ítems Registrados (Total)</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-primary-600">{{ number_format($stats['total_items']) }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border border-gray-100">
            <dt class="truncate text-sm font-medium text-gray-500">Ingresos de Hoy</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-green-600">{{ number_format($stats['items_hoy']) }}</dd>
        </div>
    </dl>

    <!-- Buscador -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="p-6 bg-gray-50/50">
            <form method="GET" action="{{ route('inventario.index') }}" class="max-w-xl relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ $search ?? '' }}" class="block w-full rounded-md border-0 py-2 pl-10 pr-20 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6" placeholder="Buscar centro por nombre o dirección...">
                <div class="absolute inset-y-0 right-0 flex items-center">
                    <button type="submit" class="h-full rounded-r-md border-0 bg-transparent py-0 pl-2 pr-4 text-gray-500 hover:text-gray-900 sm:text-sm font-medium">Buscar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($centros as $centro)
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 truncate">{{ $centro->nombre }}</h3>
                </div>
                <div class="text-sm text-gray-500 mb-4">
                    <p class="mb-1"><span class="font-semibold text-gray-700">Dirección:</span> {{ $centro->direccion }}</p>
                    <p class="mb-1"><span class="font-semibold text-gray-700">Contacto:</span> {{ $centro->contacto }}</p>
                </div>
                
                <div class="mt-4 mb-4 flex items-center p-3 bg-primary-50 rounded-lg">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-primary-900">
                            Ítems Registrados: <span class="font-bold text-lg">{{ $centro->inventario->count() }}</span>
                        </p>
                    </div>
                </div>
                
                <div class="mt-6">
                    <a href="{{ route('inventario.show', $centro->id_centro) }}" class="inline-flex w-full justify-center items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition ease-in-out duration-150">
                        @if(session('api_user') && (session('api_user')['role'] ?? '') === 'citizen')
                            Ver Donaciones
                        @else
                            Gestionar Inventario
                        @endif
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
