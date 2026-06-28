@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-md">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-semibold tracking-tight">Entrar</h1>
            <p class="mt-1 text-sm text-gray-600">Accedé para crear y ver reportes.</p>

            <form method="POST" action="{{ route('login.store', [], false) }}" class="mt-6 space-y-4">
            @csrf

            <input type="hidden" name="intended" value="{{ old('intended', (string) ($intended ?? '')) }}">

            <div>
                <label class="block text-sm font-medium mb-1" for="login">Carnet o email</label>
                <input id="login" name="login" type="text" value="{{ old('login') }}" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900" autocomplete="username" required>
                @error('login')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="password">Clave</label>
                <div class="relative">
                    <input id="password" name="password" type="password" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 pr-10 shadow-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900" autocomplete="current-password" required>
                    <button type="button" onclick="const input = document.getElementById('password'); const isPwd = input.type === 'password'; input.type = isPwd ? 'text' : 'password'; this.children[0].style.display = isPwd ? 'none' : 'block'; this.children[1].style.display = isPwd ? 'block' : 'none';" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" style="display:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        <svg class="w-5 h-5" style="display:none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                    </button>
                </div>
                @error('password')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex items-center justify-between gap-3 pt-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                    Entrar
                </button>
                <a class="text-sm text-gray-700 hover:text-gray-900 hover:underline underline-offset-4" href="{{ route('register', [], false) }}">Crear cuenta</a>
            </div>
            
            <div class="mt-8 pt-6 border-t border-gray-200">
                <p class="text-sm text-gray-600 mb-3 text-center">¿Estás en una emergencia y necesitas reportar una inundación de inmediato?</p>
                <a href="{{ route('reports.rapido', [], false) }}" class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Hacer Reporte Rápido (Sin Sesión)
                </a>
            </div>
            </form>
        </div>
    </div>
@endsection
