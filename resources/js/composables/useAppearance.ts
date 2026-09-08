import { computed, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

export type ResolvedAppearance = 'light' | 'dark';
type Appearance = ResolvedAppearance | 'system';

/** Palette slots stored per mode by the Theme Settings screen. */
type PaletteColors = Partial<
    Record<
        | 'card_bg'
        | 'card_text'
        | 'content_bg'
        | 'content_text'
        | 'header_bg'
        | 'header_text'
        | 'sidebar_bg'
        | 'sidebar_text'
        | 'sidebar_active_bg'
        | 'sidebar_active_text'
        | 'primary'
        | 'primary_text'
        | 'success'
        | 'success_text'
        | 'danger'
        | 'danger_text'
        | 'warning'
        | 'warning_text'
        | 'info'
        | 'info_text',
        string
    >
>;

type ThemeRecord = { colors_json?: PaletteColors } | null | undefined;

/**
 * Tokens this function is allowed to write, so a stale inline value can be
 * cleared instead of lingering on `documentElement` across a mode switch.
 */
const MANAGED_TOKENS = [
    '--primary',
    '--primary-foreground',
    '--destructive',
    '--destructive-foreground',
    '--success',
    '--success-foreground',
    '--warning',
    '--warning-foreground',
    '--info',
    '--info-foreground',
    '--background',
    '--foreground',
    '--card',
    '--card-foreground',
    '--popover',
    '--popover-foreground',
    '--muted',
    '--muted-foreground',
    '--secondary',
    '--secondary-foreground',
    '--accent',
    '--accent-foreground',
    '--border',
    '--input',
    '--ring',
    '--sidebar-background',
    '--sidebar-foreground',
    '--sidebar-primary',
    '--sidebar-primary-foreground',
    '--sidebar-accent',
    '--sidebar-accent-foreground',
    '--sidebar-border',
    '--header-bg',
    '--header-text',
    '--content-bg',
    '--content-text',
    '--card-bg',
    '--card-text',
] as const;

const mix = (a: string | undefined, b: string | undefined, pct: number) =>
    a && b ? `color-mix(in srgb, ${a} ${pct}%, ${b})` : undefined;

/**
 * Maps a saved palette onto the design tokens.
 *
 * Mirrors the derivation in resources/views/app.blade.php: only surface
 * colours come from the palette, and the contrast tokens (border, muted,
 * ring) are mixed from them. Mapping those to a palette slot directly — as an
 * earlier version did, pointing `--border` at `content_bg` — collapses the
 * hierarchy and makes every border invisible.
 */
function paletteTokens(colors: PaletteColors): Record<string, string> {
    const surface = colors.card_bg;
    const onSurface = colors.card_text;
    const page = colors.content_bg;
    const onPage = colors.content_text;

    const tokens: Record<string, string | undefined> = {
        '--primary': colors.primary,
        '--primary-foreground': colors.primary_text,
        '--destructive': colors.danger,
        '--destructive-foreground': colors.danger_text,
        '--success': colors.success,
        '--success-foreground': colors.success_text,
        '--warning': colors.warning,
        '--warning-foreground': colors.warning_text,
        '--info': colors.info,
        '--info-foreground': colors.info_text,
        '--background': page,
        '--foreground': onPage,
        '--card': surface,
        '--card-foreground': onSurface,
        '--popover': surface,
        '--popover-foreground': onSurface,
        '--muted': mix(onSurface, surface, 6),
        '--muted-foreground': mix(onSurface, surface, 60),
        '--secondary': mix(onSurface, surface, 6),
        '--secondary-foreground': onSurface,
        '--accent': mix(onSurface, surface, 8),
        '--accent-foreground': onSurface,
        '--border': mix(onSurface, surface, 14),
        '--input': mix(onSurface, surface, 14),
        '--ring': mix(onSurface, surface, 40),
        '--sidebar-background': colors.sidebar_bg,
        '--sidebar-foreground': colors.sidebar_text,
        '--sidebar-primary': colors.sidebar_active_bg,
        '--sidebar-primary-foreground': colors.sidebar_active_text,
        '--sidebar-accent': colors.sidebar_active_bg,
        '--sidebar-accent-foreground': colors.sidebar_active_text,
        '--sidebar-border': mix(colors.sidebar_text, colors.sidebar_bg, 20),
        '--header-bg': colors.header_bg,
        '--header-text': colors.header_text,
        '--content-bg': page,
        '--content-text': onPage,
        '--card-bg': surface,
        '--card-text': onSurface,
    };

    return Object.fromEntries(
        Object.entries(tokens).filter(([, v]) => Boolean(v)),
    ) as Record<string, string>;
}

/**
 * Applies a saved palette as inline overrides on the root element.
 *
 * Slots the palette leaves blank are removed rather than defaulted, so the
 * `:root` / `.dark` values in app.css stay in charge of them.
 */
function applyTheme(theme: ThemeRecord) {
    const root = document.documentElement.style;
    const tokens = paletteTokens(theme?.colors_json ?? {});

    for (const name of MANAGED_TOKENS) {
        const value = tokens[name];

        if (value) {
            root.setProperty(name, value);
        } else {
            root.removeProperty(name);
        }
    }
}

export function updateTheme(value: Appearance) {
    if (typeof window === 'undefined') {
        return;
    }

    const page = usePage();
    const themes = page.props.themes as
        | Record<string, ThemeRecord>
        | undefined;

    const mode =
        value === 'system'
            ? window.matchMedia('(prefers-color-scheme: dark)').matches
                ? 'dark'
                : 'light'
            : value;

    document.documentElement.classList.toggle('dark', mode === 'dark');
    applyTheme(themes?.[mode] ?? null);
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const mediaQuery = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const getStoredAppearance = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return localStorage.getItem('appearance') as Appearance | null;
};

const prefersDark = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const handleSystemThemeChange = () => {
    const currentAppearance = getStoredAppearance();

    updateTheme(currentAppearance || 'system');
};

export function initializeTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    // Initialize theme from saved preference or default to system...
    // Note: updateTheme will be called in useAppearance onMounted when props are available

    // Set up system theme change listener...
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

const appearance = ref<Appearance>('system');

export function useAppearance() {
    onMounted(() => {
        const savedAppearance = localStorage.getItem(
            'appearance',
        ) as Appearance | null;

        if (savedAppearance) {
            appearance.value = savedAppearance;
        }

        // Apply initial theme after props are loaded
        updateTheme(appearance.value || 'system');
    });

    const resolvedAppearance = computed<ResolvedAppearance>(() => {
        if (appearance.value === 'system') {
            return prefersDark() ? 'dark' : 'light';
        }

        return appearance.value;
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;

        // Store in localStorage for client-side persistence...
        localStorage.setItem('appearance', value);

        // Store in cookie for SSR...
        setCookie('appearance', value);

        updateTheme(value);
    }

    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
    };
}
