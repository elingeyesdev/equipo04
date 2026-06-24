@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-10 text-center">
        <h1 class="text-3xl font-bold text-primary-900 font-heading">Contáctanos</h1>
        <p class="mt-2 text-gray-600">Información de contacto oficial en caso de emergencias y reportes institucionales.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Tarjeta de Teléfonos -->
        <div class="bg-white p-8 rounded-lg shadow-sm border border-gray-200">
            <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                Teléfonos de Emergencia
            </h2>
            <ul class="space-y-4">
                @foreach(config('contact.emergency_numbers') as $number)
                <li class="flex justify-between items-center bg-gray-50 px-4 py-3 rounded-md">
                    <span class="font-medium text-gray-700">{{ $number['name'] }}</span>
                    <span class="font-bold text-primary-700 text-lg">{{ $number['number'] }}</span>
                </li>
                @endforeach
            </ul>
        </div>

        <!-- Tarjeta de Información -->
        <div class="bg-white p-8 rounded-lg shadow-sm border border-gray-200">
            <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                Oficina Central
            </h2>
            <div class="space-y-5 text-gray-600">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Dirección</h3>
                    <p class="mt-1">{{ config('contact.address') }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Correo Electrónico</h3>
                    <p class="mt-1"><a href="mailto:{{ config('contact.email') }}" class="text-primary-600 hover:underline">{{ config('contact.email') }}</a></p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Horario</h3>
                    <p class="mt-1">{{ config('contact.schedule') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
