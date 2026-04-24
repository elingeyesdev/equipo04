<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @php($apiUser = (array) session('api_user', []))
    @php($apiRole = (string) ($apiUser['role'] ?? ''))

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Flood Reports') }}</title>
    <meta name="api-user-role" content="{{ $apiRole }}">
    @if ($apiRole === 'authority')
        <meta name="reports-notifications-endpoint" content="{{ route('reports.notifications.latest', [], false) }}">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto max-w-5xl px-4 py-3 flex items-center justify-between gap-4">
            <a href="{{ route('reports.index', [], false) }}"
                class="font-semibold tracking-tight hover:underline underline-offset-4">
                {{ config('app.name', 'Flood Reports') }}
            </a>

            <nav class="flex items-center gap-1 text-sm">
                @if (session()->has('api_token'))
                    <a href="{{ route('reports.index', [], false) }}"
                        class="rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                        Reportes
                    </a>
                    <a href="{{ route('maps.index', [], false) }}"
                        class="rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                        Mapas
                    </a>
                    <a href="{{ route('logistica.index', [], false) }}"
                        class="rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                        Logística
                    </a>
                    <a href="{{ route('reports.create', [], false) }}"
                        class="rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                        Crear
                    </a>
                    <span class="hidden sm:inline-flex items-center gap-1 rounded-md px-3 py-2 text-gray-600">
                        <span class="truncate max-w-[14rem]">{{ (string) ($apiUser['name'] ?? '') }}</span>
                        @if ($apiRole !== '')
                            <span class="text-gray-400">·</span>
                            <span class="text-gray-500">{{ $apiRole }}</span>
                        @endif
                    </span>
                    <form method="POST" action="{{ route('logout', [], false) }}">
                        @csrf
                        <button type="submit"
                            class="rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                            Salir
                        </button>
                    </form>
                @else
                    <a href="{{ route('login', [], false) }}"
                        class="rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900">Login</a>
                    <a href="{{ route('register', [], false) }}"
                        class="rounded-md bg-gray-900 px-3 py-2 font-medium text-white hover:bg-gray-800">Registro</a>
                @endif
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8">
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