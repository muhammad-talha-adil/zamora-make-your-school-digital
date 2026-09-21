import { Vibrant } from 'node-vibrant/browser';
import { argbFromHex, hexFromArgb, themeFromSourceColor } from '@material/material-color-utilities';
import type { Scheme } from '@material/material-color-utilities';

/** Palette slots the `theme_settings` table stores per mode (see ThemeSettingsController). */
export interface GeneratedPaletteColors {
    sidebar_bg: string;
    sidebar_text: string;
    sidebar_active_bg: string;
    sidebar_active_text: string;
    header_bg: string;
    header_text: string;
    content_bg: string;
    content_text: string;
    card_bg: string;
    card_text: string;
    primary: string;
    primary_text: string;
    danger: string;
    danger_text: string;
}

export interface GeneratedTheme {
    light: GeneratedPaletteColors;
    dark: GeneratedPaletteColors;
}

function schemeToPalette(scheme: Scheme): GeneratedPaletteColors {
    return {
        content_bg: hexFromArgb(scheme.background),
        content_text: hexFromArgb(scheme.onBackground),
        card_bg: hexFromArgb(scheme.surface),
        card_text: hexFromArgb(scheme.onSurface),
        header_bg: hexFromArgb(scheme.surface),
        header_text: hexFromArgb(scheme.onSurface),
        sidebar_bg: hexFromArgb(scheme.inverseSurface),
        sidebar_text: hexFromArgb(scheme.inverseOnSurface),
        sidebar_active_bg: hexFromArgb(scheme.primary),
        sidebar_active_text: hexFromArgb(scheme.onPrimary),
        primary: hexFromArgb(scheme.primary),
        primary_text: hexFromArgb(scheme.onPrimary),
        danger: hexFromArgb(scheme.error),
        danger_text: hexFromArgb(scheme.onError),
    };
}

/**
 * Extracts the logo's most vibrant/dominant color and expands it into a full
 * light + dark palette matching the shape `theme_settings.colors_json` uses,
 * via Google's Material You algorithm.
 *
 * Returns null (never throws) on any extraction failure, so a bad/unreadable
 * image degrades to "no auto-theme" instead of blocking the logo upload.
 */
export async function generateThemeFromLogo(file: File): Promise<GeneratedTheme | null> {
    let objectUrl: string | null = null;

    try {
        objectUrl = URL.createObjectURL(file);

        const palette = await Vibrant.from(objectUrl).getPalette();

        const seedHex =
            palette.Vibrant?.hex ||
            palette.DarkVibrant?.hex ||
            palette.LightVibrant?.hex ||
            palette.Muted?.hex;

        if (!seedHex) {
            return null;
        }

        const theme = themeFromSourceColor(argbFromHex(seedHex));

        return {
            light: schemeToPalette(theme.schemes.light),
            dark: schemeToPalette(theme.schemes.dark),
        };
    } catch {
        return null;
    } finally {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
        }
    }
}
