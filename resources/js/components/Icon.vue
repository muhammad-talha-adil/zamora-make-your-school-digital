<script setup lang="ts">
import { cn } from '@/lib/utils';
import * as icons from 'lucide-vue-next';
import { LucideIcon } from 'lucide-vue-next';
import { computed, ref, watchEffect } from 'vue';

interface Props {
    icon: string | LucideIcon | undefined;
    class?: string;
    size?: number | string;
    color?: string;
    strokeWidth?: number | string;
}

const props = withDefaults(defineProps<Props>(), {
    class: '',
    size: 16,
    strokeWidth: 2,
});

const className = computed(() => cn('h-4 w-4', props.class));

/**
 * Known brand/platform names always render their real simple-icons logo
 * (defined below), even though Lucide also ships same-named generic outline
 * glyphs for a few of these (facebook, instagram, twitter, github, google,
 * apple) - those generic outlines aren't the actual brand mark, so brand
 * names take priority over Lucide here. Everything else (the vast majority
 * of icon usages app-wide) is untouched and still resolves through Lucide.
 */
const BRAND_ICON_SLUGS: Record<string, string> = {
    facebook: 'facebook',
    instagram: 'instagram',
    threads: 'threads',
    // Only the unambiguous "twitter" name maps to the X brand mark - a bare
    // "x" is used app-wide as the generic Lucide close/clear cross (dialogs,
    // SearchableSelect's clear button, etc.) and must keep resolving there.
    twitter: 'x',
    youtube: 'youtube',
    tiktok: 'tiktok',
    whatsapp: 'whatsapp',
    pinterest: 'pinterest',
    snapchat: 'snapchat',
    telegram: 'telegram',
    github: 'github',
    google: 'google',
    apple: 'apple',
    vimeo: 'vimeo',
    discord: 'discord',
};

/**
 * One loader per curated slug (not a glob over all ~3200 simple-icons SVGs -
 * that produced 3000+ extra build chunks for icons this app will never
 * request). Each entry still only loads its tiny SVG on first actual use.
 */
const brandIconLoaders: Record<string, () => Promise<string>> = {
    facebook: () => import('simple-icons/icons/facebook.svg?raw').then((m) => m.default),
    instagram: () => import('simple-icons/icons/instagram.svg?raw').then((m) => m.default),
    threads: () => import('simple-icons/icons/threads.svg?raw').then((m) => m.default),
    x: () => import('simple-icons/icons/x.svg?raw').then((m) => m.default),
    youtube: () => import('simple-icons/icons/youtube.svg?raw').then((m) => m.default),
    tiktok: () => import('simple-icons/icons/tiktok.svg?raw').then((m) => m.default),
    whatsapp: () => import('simple-icons/icons/whatsapp.svg?raw').then((m) => m.default),
    pinterest: () => import('simple-icons/icons/pinterest.svg?raw').then((m) => m.default),
    snapchat: () => import('simple-icons/icons/snapchat.svg?raw').then((m) => m.default),
    telegram: () => import('simple-icons/icons/telegram.svg?raw').then((m) => m.default),
    github: () => import('simple-icons/icons/github.svg?raw').then((m) => m.default),
    google: () => import('simple-icons/icons/google.svg?raw').then((m) => m.default),
    apple: () => import('simple-icons/icons/apple.svg?raw').then((m) => m.default),
    vimeo: () => import('simple-icons/icons/vimeo.svg?raw').then((m) => m.default),
    discord: () => import('simple-icons/icons/discord.svg?raw').then((m) => m.default),
};

const brandSvg = ref<string | null>(null);

watchEffect(async () => {
    brandSvg.value = null;

    if (typeof props.icon !== 'string') {
        return;
    }

    const slug = BRAND_ICON_SLUGS[props.icon.toLowerCase()];
    const loader = slug ? brandIconLoaders[slug] : undefined;
    if (!loader) {
        return;
    }

    try {
        brandSvg.value = await loader();
    } catch {
        // Asset failed to load - fall through to the Lucide branch below instead of rendering nothing.
    }
});

/** Lucide lookup, skipped entirely for names handled above so a brand glyph is never overridden by a generic same-named outline icon. */
const iconComponent = computed(() => {
    if (typeof props.icon === 'string') {
        if (BRAND_ICON_SLUGS[props.icon.toLowerCase()]) {
            return null;
        }
        // Convert kebab-case to PascalCase for Lucide icons
        const iconName = props.icon
            .split('-')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join('');
        return (icons as Record<string, any>)[iconName];
    }
    return null;
});
</script>

<template>
    <img
        v-if="typeof icon === 'string' && icon.startsWith('/')"
        :src="icon"
        :alt="typeof icon === 'string' ? icon : ''"
        :class="props.class"
        :style="{ width: size + 'px', height: size + 'px' }"
        class="inline-block"
    />
    <component
        v-else-if="iconComponent"
        :is="typeof icon === 'string' ? iconComponent : icon"
        :class="className"
        :size="size"
        :stroke-width="strokeWidth"
        :color="color"
    />
    <span
        v-else-if="brandSvg"
        :class="className"
        class="inline-flex [&_svg]:h-full [&_svg]:w-full [&_svg]:fill-current"
        v-html="brandSvg"
    />
</template>
