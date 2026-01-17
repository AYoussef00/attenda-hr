<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Preload critical fonts to improve LCP --}}
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin />
        <link rel="dns-prefetch" href="https://fonts.bunny.net" />

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Critical CSS for LCP - inline hero section styles --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
            
            /* Critical CSS for hero section - improves LCP */
            .hero-section {
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }
        </style>

        <title inertia>{{ config('app.name', 'attenda.') }}</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @if(file_exists(public_path('asset/logo.png')))
            <link rel="icon" href="{{ asset('asset/logo.png') }}" type="image/png">
            <link rel="shortcut icon" href="{{ asset('asset/logo.png') }}" type="image/png">
        @endif

        {{-- Load fonts with font-display: swap for better LCP --}}
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet" media="print" onload="this.media='all'" />
        <noscript>
            <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet" />
        </noscript>

        {{-- Defer Google Analytics to improve LCP --}}
        <script>
            // Defer Google Analytics loading until after LCP
            window.addEventListener('load', function() {
                // Load gtag.js asynchronously after page load
                const script = document.createElement('script');
                script.async = true;
                script.src = 'https://www.googletagmanager.com/gtag/js?id=G-PJ2Z87M36D';
                document.head.appendChild(script);

                script.onload = function() {
                    window.dataLayer = window.dataLayer || [];
                    function gtag(){dataLayer.push(arguments);}
                    gtag('js', new Date());
                    gtag('config', 'G-PJ2Z87M36D');
                };
            });
        </script>

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
