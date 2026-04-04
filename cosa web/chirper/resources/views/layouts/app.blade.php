<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Flood Reports') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-white text-gray-900">
    <header class="border-b border-gray-200">
        <div class="mx-auto max-w-5xl px-4 py-3 flex items-center justify-between">
            <a href="{{ route('reports.index') }}" class="font-semibold">{{ config('app.name', 'Flood Reports') }}</a>

            <nav class="flex items-center gap-4 text-sm">
                @php($apiUser = (array) session('api_user', []))
                @php($apiRole = (string) ($apiUser['role'] ?? ''))

                @if (session()->has('api_token'))
                    <a href="{{ route('reports.index') }}" class="hover:underline">Reportes</a>
                    <a href="{{ route('reports.create') }}" class="hover:underline">Crear</a>
                    <span class="text-gray-600">
                        {{ (string) ($apiUser['name'] ?? '') }}
                        @if ($apiRole !== '')
                            ({{ $apiRole }})
                        @endif
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="hover:underline">Salir</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:underline">Login</a>
                    <a href="{{ route('register') }}" class="hover:underline">Registro</a>
                @endif
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-6">
        @if (session('status'))
            <div class="mb-4 rounded-md border border-gray-200 bg-gray-50 p-3 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm">
                <div class="font-medium mb-1">Revisá los errores:</div>
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
