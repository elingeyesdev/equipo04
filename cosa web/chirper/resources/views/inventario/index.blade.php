@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight font-heading">Centros de Acopio e Inventario</h1>
            <p class="mt-2 text-sm text-gray-500">Gestione el inventario de suministros, donaciones y recursos logísticos en cada centro.</p>
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
                    <p><span class="font-semibold text-gray-700">Ítems Registrados:</span> {{ $centro->inventario->count() }}</p>
                </div>
                
                <div class="mt-6">
                    <a href="{{ route('inventario.show', $centro->id_centro) }}" class="inline-flex w-full justify-center items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition ease-in-out duration-150">
                        Gestionar Inventario
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
