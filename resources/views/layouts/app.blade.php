<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @include('partials.vite-assets')

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        @php
            $hasLocalHotReload = app()->environment('local') && file_exists(public_path('hot'));
            $hasBuiltAssets = file_exists(public_path('build/manifest.json'));
        @endphp

        @unless ($hasLocalHotReload || $hasBuiltAssets)
            <div class="vite-warning">
                Frontend assets are not built yet. Run <code>npm run dev</code> or <code>npm run build</code>, then refresh this page.
            </div>
        @endunless

        <x-banner />

        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @livewire('navigation-menu')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @stack('modals')

        @livewireScripts
    </body>
</html>
