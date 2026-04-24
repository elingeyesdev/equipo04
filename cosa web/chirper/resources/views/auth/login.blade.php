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
                <label class="block text-sm font-medium mb-1" for="password">Password</label>
                <input id="password" name="password" type="password" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900" autocomplete="current-password" required>
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
            </form>
        </div>
    </div>
@endsection
