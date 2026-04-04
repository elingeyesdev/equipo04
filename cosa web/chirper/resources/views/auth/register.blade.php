@extends('layouts.app')

@section('content')
    <div class="max-w-md">
        <h1 class="text-lg font-semibold mb-4">Registro</h1>

        <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-1" for="carnet">Carnet</label>
                <input id="carnet" name="carnet" type="text" value="{{ old('carnet') }}" class="w-full rounded-md border border-gray-300 px-3 py-2" required>
                @error('carnet')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="name">Nombre</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" class="w-full rounded-md border border-gray-300 px-3 py-2" required>
                @error('name')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="phone">Teléfono</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone') }}" class="w-full rounded-md border border-gray-300 px-3 py-2" required>
                @error('phone')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="address">Dirección</label>
                <input id="address" name="address" type="text" value="{{ old('address') }}" class="w-full rounded-md border border-gray-300 px-3 py-2" required>
                @error('address')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="email">Email (opcional)</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" class="w-full rounded-md border border-gray-300 px-3 py-2" autocomplete="email">
                @error('email')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="password">Password</label>
                <input id="password" name="password" type="password" class="w-full rounded-md border border-gray-300 px-3 py-2" autocomplete="new-password" required>
                @error('password')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-md bg-gray-900 text-white px-4 py-2 text-sm">Crear</button>
                <a class="text-sm hover:underline" href="{{ route('login') }}">Ya tengo cuenta</a>
            </div>
        </form>
    </div>
@endsection
