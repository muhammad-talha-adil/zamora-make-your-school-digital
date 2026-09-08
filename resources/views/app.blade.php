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

        {{-- Custom theme styles --}}
        @php
            /**
             * Emits the palette slots the Theme Settings screen owns.
             *
             * Only the surface colours are read from the palette; contrast
             * tokens (border, muted, ring) are mixed from them so a custom
             * palette keeps a readable hierarchy instead of collapsing into a
             * single flat colour. Slots left blank fall through to the
             * defaults in resources/css/app.css.
             */
            $themeVars = function ($setting) {
                $c = $setting?->colors ?? [];

                $surface = $c['card_bg'] ?? null;
                $onSurface = $c['card_text'] ?? null;
                $page = $c['content_bg'] ?? null;
                $onPage = $c['content_text'] ?? null;

                $mix = fn (?string $a, ?string $b, int $pct) => $a && $b
                    ? "color-mix(in srgb, {$a} {$pct}%, {$b})"
                    : null;

                return array_filter([
                    '--primary' => $c['primary'] ?? null,
                    '--primary-foreground' => $c['primary_text'] ?? null,
                    '--destructive' => $c['danger'] ?? null,
                    '--destructive-foreground' => $c['danger_text'] ?? null,
                    '--success' => $c['success'] ?? null,
                    '--success-foreground' => $c['success_text'] ?? null,
                    '--warning' => $c['warning'] ?? null,
                    '--warning-foreground' => $c['warning_text'] ?? null,
                    '--info' => $c['info'] ?? null,
                    '--info-foreground' => $c['info_text'] ?? null,
                    '--background' => $page,
                    '--foreground' => $onPage,
                    '--card' => $surface,
                    '--card-foreground' => $onSurface,
                    '--popover' => $surface,
                    '--popover-foreground' => $onSurface,
                    // Secondary surfaces sit a few percent toward the text
                    // colour so they read as raised against the card.
                    '--muted' => $mix($onSurface, $surface, 6),
                    '--muted-foreground' => $mix($onSurface, $surface, 60),
                    '--secondary' => $mix($onSurface, $surface, 6),
                    '--secondary-foreground' => $onSurface,
                    '--accent' => $mix($onSurface, $surface, 8),
                    '--accent-foreground' => $onSurface,
                    '--border' => $mix($onSurface, $surface, 14),
                    '--input' => $mix($onSurface, $surface, 14),
                    '--ring' => $mix($onSurface, $surface, 40),
                    '--sidebar-background' => $c['sidebar_bg'] ?? null,
                    '--sidebar-foreground' => $c['sidebar_text'] ?? null,
                    '--sidebar-primary' => $c['sidebar_active_bg'] ?? null,
                    '--sidebar-primary-foreground' => $c['sidebar_active_text'] ?? null,
                    '--sidebar-accent' => $c['sidebar_active_bg'] ?? null,
                    '--sidebar-accent-foreground' => $c['sidebar_active_text'] ?? null,
                    '--sidebar-border' => $mix($c['sidebar_text'] ?? null, $c['sidebar_bg'] ?? null, 20),
                    '--header-bg' => $c['header_bg'] ?? null,
                    '--header-text' => $c['header_text'] ?? null,
                    '--content-bg' => $page,
                    '--content-text' => $onPage,
                    '--card-bg' => $surface,
                    '--card-text' => $onSurface,
                ], fn ($v) => $v !== null && $v !== '');
            };

            $lightVars = $themeVars($themes['light'] ?? null);
            $darkVars = $themeVars($themes['dark'] ?? null);
        @endphp

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

        {{--
          Emitted after the bundle so these win over the `:root` defaults in
          app.css, which carry the same specificity and would otherwise take
          precedence by document order.
        --}}
        @if($lightVars || $darkVars)
        <style>
            @if($lightVars)
            :root {
                @foreach($lightVars as $name => $value){{ $name }}: {{ $value }};
                @endforeach
            }
            @endif
            @if($darkVars)
            .dark {
                @foreach($darkVars as $name => $value){{ $name }}: {{ $value }};
                @endforeach
            }
            @endif
        </style>
        @endif

        <style>
            html { background-color: var(--background); }
        </style>
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
