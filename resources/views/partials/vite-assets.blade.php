@php
    $viteHotFile = public_path('hot');
    $viteManifest = public_path('build/manifest.json');
    $hasLocalHotReload = app()->environment('local') && file_exists($viteHotFile);
    $hasBuiltAssets = file_exists($viteManifest);
    $hasViteAssets = $hasLocalHotReload || $hasBuiltAssets;
@endphp

@if ($hasViteAssets)
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@else
    <style>
        .vite-warning {
            margin: 1rem 0 0;
            padding: 0.9rem 1rem;
            border: 1px solid #fed7aa;
            border-radius: 0.75rem;
            background: #fff7ed;
            color: #9a3412;
            font: 600 0.95rem/1.5 Arial, Helvetica, sans-serif;
        }

        .vite-warning code {
            font-family: "Courier New", Courier, monospace;
            font-size: 0.95em;
        }
    </style>
@endif
