import { computed, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

export type ResolvedAppearance = 'light' | 'dark';
type Appearance = ResolvedAppearance | 'system';

const cssVarMapping: Record<string, string> = {
    '--background': 'content_bg',
    '--foreground': 'content_text',
    '--card': 'card_bg',
    '--card-foreground': 'card_text',
    '--popover': 'card_bg',
    '--popover-foreground': 'card_text',
    '--primary': 'sidebar_active_bg',
    '--primary-foreground': 'sidebar_active_text',
    '--secondary': 'header_bg',
    '--secondary-foreground': 'header_text',
    '--muted': 'content_bg',
    '--muted-foreground': 'content_text',
    '--accent': 'sidebar_bg',
    '--accent-foreground': 'sidebar_text',
    '--destructive': '#ef4444',
    '--destructive-foreground': '#ffffff',
    '--border': 'content_bg',
    '--input': '#d1d5db',
    '--ring': 'sidebar_active_bg',
    '--chart-1': '#8884d8',
    '--chart-2': '#82ca9d',
    '--chart-3': '#ffc658',
    '--chart-4': '#ff7c7c',
    '--chart-5': '#8dd1e1',
    '--sidebar-background': 'sidebar_bg',
    '--sidebar-foreground': 'sidebar_text',
    '--sidebar-primary': 'sidebar_active_bg',
    '--sidebar-primary-foreground': 'sidebar_active_text',
    '--sidebar-accent': 'header_bg',
    '--sidebar-accent-foreground': 'header_text',
    '--sidebar-border': 'sidebar_bg',
    '--sidebar-ring': 'sidebar_active_bg',
    '--sidebar': 'sidebar_bg',
    '--radius': '0.5rem',
};

function applyTheme(theme: any) {
    Object.entries(cssVarMapping).forEach(([cssVar, slot]) => {
        let value: string;
        if (slot.startsWith('#')) {
            value = slot;
        } else if (theme && theme.colors_json && theme.colors_json[slot]) {
            value = theme.colors_json[slot];
        } else {
            // Default values when no theme or slot not found
            const defaults: Record<string, string> = {
                '--background': '#ffffff',
                '--foreground': '#000000',
                '--card': '#ffffff',
                '--card-foreground': '#000000',
                '--popover': '#ffffff',
                '--popover-foreground': '#000000',
                '--primary': '#000000',
                '--primary-foreground': '#ffffff',
                '--secondary': '#f3f4f6',
                '--secondary-foreground': '#000000',
                '--muted': '#f9fafb',
                '--muted-foreground': '#6b7280',
                '--accent': '#f3f4f6',
                '--accent-foreground': '#000000',
                '--destructive': '#ef4444',
                '--destructive-foreground': '#ffffff',
                '--border': '#e5e7eb',
                '--input': '#ffffff',
                '--ring': '#000000',
                '--chart-1': '#8884d8',
                '--chart-2': '#82ca9d',
                '--chart-3': '#ffc658',
                '--chart-4': '#ff7c7c',
                '--chart-5': '#8dd1e1',
                '--sidebar-background': '#f9fafb',
                '--sidebar-foreground': '#000000',
                '--sidebar-primary': '#000000',
                '--sidebar-primary-foreground': '#ffffff',
                '--sidebar-accent': '#f3f4f6',
                '--sidebar-accent-foreground': '#000000',
                '--sidebar-border': '#e5e7eb',
                '--sidebar-ring': '#000000',
                '--sidebar': '#f9fafb',
                '--radius': '0.5rem',
            };
            value = defaults[cssVar] || '#000000';
        }
        document.documentElement.style.setProperty(cssVar, value);
    });
}

export function updateTheme(value: Appearance) {
    if (typeof window === 'undefined') {
        return;
    }

    const page = usePage();
    const themes = page.props.themes as Record<string, any>;

    if (value === 'system') {
        const mediaQueryList = window.matchMedia(
            '(prefers-color-scheme: dark)',
        );
        const systemTheme = mediaQueryList.matches ? 'dark' : 'light';

        document.documentElement.classList.toggle(
            'dark',
            systemTheme === 'dark',
        );
        applyTheme(themes ? themes[systemTheme] : null);
    } else {
        document.documentElement.classList.toggle('dark', value === 'dark');
        applyTheme(themes ? themes[value] : null);
    }
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
    const savedAppearance = getStoredAppearance();
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
