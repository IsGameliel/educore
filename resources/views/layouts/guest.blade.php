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
    <body>
        @unless (file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
            <div class="vite-warning">
                Frontend assets are not built yet. Run <code>npm run dev</code> or <code>npm run build</code>, then refresh this page.
            </div>
        @endunless

        <div class="font-sans text-gray-900 dark:text-gray-100 antialiased">
            {{ $slot }}
        </div>

        @livewireScripts
    </body>
</html>
