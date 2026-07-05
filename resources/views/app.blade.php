<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/Pages/{$page['component']}.tsx"])

        {{-- Per-client theme — loaded AFTER the compiled CSS so its :root overrides
             the design-system defaults baked into app.css (@layer base). --}}
        <link rel="stylesheet" href="{{ route('branding-css') }}?v={{ substr(md5(implode(',', config('company.theme'))), 0, 8) }}">
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
