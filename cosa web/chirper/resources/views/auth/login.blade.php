@extends('layouts.app')

@section('content')
    <div class="max-w-md">
        <h1 class="text-lg font-semibold mb-4">Login</h1>

        <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
            @csrf

            <input type="hidden" name="intended" value="{{ old('intended', (string) ($intended ?? '')) }}">

            <div>
                <label class="block text-sm font-medium mb-1" for="login">Carnet o email</label>
                <input id="login" name="login" type="text" value="{{ old('login') }}" class="w-full rounded-md border border-gray-300 px-3 py-2" autocomplete="username" required>
                @error('login')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="password">Password</label>
                <input id="password" name="password" type="password" class="w-full rounded-md border border-gray-300 px-3 py-2" autocomplete="current-password" required>
                @error('password')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-md bg-gray-900 text-white px-4 py-2 text-sm">Entrar</button>
                <a class="text-sm hover:underline" href="{{ route('register') }}">Crear cuenta</a>
            </div>
        </form>
    </div>
@endsection
