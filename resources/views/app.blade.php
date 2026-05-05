<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

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

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        {{-- Custom theme styles --}}
        @if(! empty($theme ?? null))
        <style>
            @if(($theme_mode ?? 'light') === 'light')
            :root {
                --background: {{ $theme->colors['card_bg'] ?? 'hsl(0 0% 100%)' }};
                --foreground: {{ $theme->colors['card_text'] ?? 'hsl(0 0% 3.9%)' }};
                --card: {{ $theme->colors['card_bg'] ?? 'hsl(0 0% 100%)' }};
                --card-foreground: {{ $theme->colors['card_text'] ?? 'hsl(0 0% 3.9%)' }};
                --popover: {{ $theme->colors['card_bg'] ?? 'hsl(0 0% 100%)' }};
                --popover-foreground: {{ $theme->colors['card_text'] ?? 'hsl(0 0% 3.9%)' }};
                --input: {{ $theme->colors['card_bg'] ?? 'hsl(0 0% 100%)' }};
                --muted: {{ $theme->colors['card_bg'] ?? 'hsl(0 0% 96.1%)' }};
                --muted-foreground: {{ $theme->colors['card_text'] ?? 'hsl(0 0% 45.1%)' }};
                --accent: {{ $theme->colors['card_bg'] ?? 'hsl(0 0% 96.1%)' }};
                --accent-foreground: {{ $theme->colors['card_text'] ?? 'hsl(0 0% 9%)' }};
                --primary: {{ $theme->colors['card_bg'] ?? 'hsl(0 0% 9%)' }};
                --primary-foreground: {{ $theme->colors['card_text'] ?? 'hsl(0 0% 98%)' }};
                --sidebar-background: {{ $theme->colors['sidebar_bg'] ?? 'hsl(0 0% 98%)' }};
                --sidebar-foreground: {{ $theme->colors['sidebar_text'] ?? 'hsl(240 5.3% 26.1%)' }};
                --sidebar-primary: {{ $theme->colors['sidebar_active_bg'] ?? 'hsl(0 0% 10%)' }};
                --sidebar-primary-foreground: {{ $theme->colors['sidebar_active_text'] ?? 'hsl(0 0% 98%)' }};
                --header-bg: {{ $theme->colors['header_bg'] ?? 'hsl(0 0% 100%)' }};
                --header-text: {{ $theme->colors['header_text'] ?? 'hsl(0 0% 3.9%)' }};
            }
            @else
            .dark {
                --background: {{ $theme->colors['card_bg'] ?? 'hsl(0 0% 3.9%)' }};
                --foreground: {{ $theme->colors['card_text'] ?? 'hsl(0 0% 98%)' }};
                --card: {{ $theme->colors['card_bg'] ?? 'hsl(0 0% 3.9%)' }};
                --card-foreground: {{ $theme->colors['card_text'] ?? 'hsl(0 0% 98%)' }};
                --popover: hsl(0 0% 3.9%);
                --popover-foreground: hsl(0 0% 98%);
                --input: {{ $theme->colors['card_bg'] ?? 'hsl(0 0% 3.9%)' }};
                --muted: {{ $theme->colors['card_bg'] ?? 'hsl(0 0% 16.08%)' }};
                --muted-foreground: {{ $theme->colors['card_text'] ?? 'hsl(0 0% 63.9%)' }};
                --accent: {{ $theme->colors['card_bg'] ?? 'hsl(0 0% 14.9%)' }};
                --accent-foreground: {{ $theme->colors['card_text'] ?? 'hsl(0 0% 98%)' }};
                --primary: {{ $theme->colors['card_bg'] ?? 'hsl(0 0% 98%)' }};
                --primary-foreground: {{ $theme->colors['card_text'] ?? 'hsl(0 0% 9%)' }};
                --sidebar-background: {{ $theme->colors['sidebar_bg'] ?? 'hsl(0 0% 7%)' }};
                --sidebar-foreground: {{ $theme->colors['sidebar_text'] ?? 'hsl(0 0% 95.9%)' }};
                --sidebar-primary: {{ $theme->colors['sidebar_active_bg'] ?? 'hsl(360, 100%, 100%)' }};
                --sidebar-primary-foreground: {{ $theme->colors['sidebar_active_text'] ?? 'hsl(0 0% 100%)' }};
                --header-bg: {{ $theme->colors['header_bg'] ?? 'hsl(0 0% 3.9%)' }};
                --header-text: {{ $theme->colors['header_text'] ?? 'hsl(0 0% 98%)' }};
            }
            @endif
        </style>
        @endif

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/sample-logo.png" sizes="any">
        <link rel="apple-touch-icon" href="/sample-logo.png">

        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @routes
        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
