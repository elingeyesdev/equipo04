@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-primary-900 font-heading">Foro de Sugerencias y Opiniones</h1>
        <p class="mt-2 text-gray-600">Comparte tus ideas para mejorar la gestión de desastres y apoya las iniciativas de otros ciudadanos.</p>
    </div>

    @if (session()->has('api_token'))
        <!-- Formulario para crear sugerencia -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-8">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Proponer una nueva idea</h2>
            <form action="{{ route('sugerencias.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="titulo" class="block text-sm font-medium text-gray-700">Título de la sugerencia</label>
                    <input type="text" id="titulo" name="titulo" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" placeholder="Ej. Mejorar la iluminación en el centro de acopio...">
                </div>
                <div class="mb-4">
                    <label for="contenido" class="block text-sm font-medium text-gray-700">Detalles</label>
                    <textarea id="contenido" name="contenido" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" placeholder="Explica tu idea brevemente..."></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Publicar Sugerencia
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8 text-center text-blue-800">
            <p>Para crear una sugerencia, debes <a href="{{ route('login') }}" class="font-bold underline hover:text-blue-900">iniciar sesión</a>.</p>
        </div>
    @endif

    <!-- Filtros y Ordenamiento -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800 font-heading">Ideas de la Comunidad</h2>
        <div class="flex space-x-2 text-sm">
            <a href="{{ route('sugerencias.index', ['sort' => 'likes']) }}" class="px-3 py-1 rounded-full {{ $sort == 'likes' ? 'bg-primary-100 text-primary-800 font-semibold' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">Más apoyadas</a>
            <a href="{{ route('sugerencias.index', ['sort' => 'recientes']) }}" class="px-3 py-1 rounded-full {{ $sort == 'recientes' ? 'bg-primary-100 text-primary-800 font-semibold' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">Más recientes</a>
        </div>
    </div>

    <!-- Lista de Sugerencias -->
    <div class="space-y-4">
        @forelse ($sugerencias as $sugerencia)
            <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-200 flex flex-col sm:flex-row gap-4 items-start">
                
                <!-- Botón de Like (A la izquierda) -->
                <div class="flex-shrink-0 flex flex-col items-center justify-center p-2 bg-gray-50 rounded-lg border border-gray-100 min-w-[60px]">
                    <form action="{{ route('sugerencias.like', $sugerencia->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-primary-600 transition-colors focus:outline-none" title="Apoyar esta idea">
                            <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                        </button>
                    </form>
                    <span class="text-lg font-bold text-gray-800">{{ $sugerencia->likes }}</span>
                </div>

                <!-- Contenido -->
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ $sugerencia->titulo }}</h3>
                    <p class="text-gray-600 text-sm mb-3">{{ $sugerencia->contenido }}</p>
                    <div class="text-xs text-gray-500 flex items-center gap-2">
                        <span class="font-medium text-gray-700">Por: {{ $sugerencia->autor_nombre }}</span>
                        <span>&bull;</span>
                        <span>{{ $sugerencia->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-10 text-gray-500 bg-white rounded-lg border border-gray-200">
                Aún no hay sugerencias. ¡Sé el primero en aportar una idea!
            </div>
        @endforelse
    </div>
</div>
@endsection
