@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 font-heading">Mi Perfil</h1>
        <p class="mt-1 text-sm text-gray-500">Gestiona tus datos personales y revisa el estado de tus donaciones.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Datos Personales -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">Datos Personales</h2>
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Carnet de Identidad</label>
                        <input type="text" disabled value="{{ $user->carnet }}" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm text-gray-500 sm:text-sm">
                        <p class="text-xs text-gray-400 mt-1">El carnet no puede ser modificado.</p>
                    </div>

                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>

                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>

                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Actualizar Perfil
                    </button>
                </form>
            </div>
        </div>

        <!-- Mis Aportes -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-medium text-gray-900">Mis Aportes / Donaciones</h2>
                    <p class="text-sm text-gray-500">Rastrea el destino de tu ayuda gracias al sistema de trazabilidad.</p>
                </div>
                
                <ul class="divide-y divide-gray-200">
                    @forelse($aportes as $aporte)
                        <li class="p-6">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-md font-semibold text-gray-900">{{ ucfirst($aporte->categoria) }}</h3>
                                <span class="text-sm text-gray-500">{{ $aporte->created_at->format('d/m/Y') }}</span>
                            </div>
                            <p class="text-gray-700 text-sm mb-3">{{ $aporte->descripcion }}</p>
                            <p class="text-xs text-gray-500 mb-4"><span class="font-medium text-gray-700">Centro de Acopio:</span> {{ $aporte->centro->nombre }}</p>
                            
                            <div class="bg-gray-50 rounded p-3 border border-gray-100">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Estado Actual:</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                        {{ $aporte->status == 'recibido' ? 'bg-yellow-100 text-yellow-800' : 
                                          ($aporte->status == 'en_inventario' ? 'bg-blue-100 text-blue-800' : 
                                          ('bg-green-100 text-green-800')) }}">
                                        {{ strtoupper($aporte->status) }}
                                    </span>
                                </div>
                                @if($aporte->usage_details)
                                    <p class="text-xs text-gray-600 italic">"{{ $aporte->usage_details }}"</p>
                                @endif
                            </div>
                        </li>
                    @empty
                        <div class="p-6 text-center text-gray-500">
                            Aún no has registrado donaciones bajo este perfil.
                        </div>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
