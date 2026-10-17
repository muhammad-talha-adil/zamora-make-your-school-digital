<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';

interface Props {
    headline: string;
    subtext: string;
    primaryCta: { label: string; href: string };
    secondaryCta?: { label: string; href: string };
    imageSeed: string;
}

defineProps<Props>();

/** A `#section` CTA scrolls within the page; anything else is a real route. */
const isAnchor = (href: string) => href.startsWith('#');

const scrollTo = (href: string) => {
    document.querySelector(href)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};
</script>

<template>
    <section class="relative overflow-hidden bg-primary pt-16 pb-28 md:pt-24 md:pb-36">
        <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-[1.1fr_1fr] lg:gap-16 lg:px-8">
            <div class="max-w-xl">
                <h1 class="text-4xl font-extrabold uppercase leading-[1.05] tracking-tight text-primary-foreground md:text-5xl lg:text-6xl">
                    {{ headline }}
                </h1>
                <p class="mt-5 max-w-md text-base leading-relaxed text-primary-foreground/75 md:text-lg">
                    {{ subtext }}
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <button
                        v-if="isAnchor(primaryCta.href)"
                        type="button"
                        class="group inline-flex h-11 items-center justify-center gap-2 rounded-full bg-background px-6 text-sm font-semibold text-primary shadow-lg shadow-black/20 transition-opacity hover:opacity-90"
                        @click="scrollTo(primaryCta.href)"
                    >
                        {{ primaryCta.label }}
                        <Icon icon="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                    </button>
                    <Link
                        v-else
                        :href="primaryCta.href"
                        class="group inline-flex h-11 items-center justify-center gap-2 rounded-full bg-background px-6 text-sm font-semibold text-primary shadow-lg shadow-black/20 transition-opacity hover:opacity-90"
                    >
                        {{ primaryCta.label }}
                        <Icon icon="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                    </Link>

                    <!-- Outline style: bg-secondary/bg-accent resolve to near-background gray in this
                         app's default palette (same value as each other), so a solid fill here would be
                         indistinguishable from the primary button and could fail contrast depending on
                         the admin's chosen theme. An outline against primary-foreground is contrast-safe
                         under any palette. -->
                    <button
                        v-if="secondaryCta && isAnchor(secondaryCta.href)"
                        type="button"
                        class="inline-flex h-11 items-center justify-center rounded-full border-2 border-primary-foreground/50 px-6 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary-foreground/10"
                        @click="scrollTo(secondaryCta.href)"
                    >
                        {{ secondaryCta.label }}
                    </button>
                    <Link
                        v-else-if="secondaryCta"
                        :href="secondaryCta.href"
                        class="inline-flex h-11 items-center justify-center rounded-full border-2 border-primary-foreground/50 px-6 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary-foreground/10"
                    >
                        {{ secondaryCta.label }}
                    </Link>
                </div>
            </div>

            <div class="relative aspect-5/4 w-full overflow-hidden rounded-2xl lg:aspect-4/3">
                <img
                    :src="`https://picsum.photos/seed/${imageSeed}/1200/900`"
                    :alt="headline"
                    class="h-full w-full scale-105 rounded-2xl object-cover opacity-0 shadow-2xl transition-[opacity,transform] duration-700 ease-out"
                    style="animation: hero-image-in 0.8s ease-out 0.15s forwards"
                />
            </div>
        </div>

        <!-- Wave divider -->
        <svg class="absolute bottom-0 left-0 w-full text-background" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true">
            <path fill="currentColor" d="M0,64 C240,120 480,0 720,32 C960,64 1200,120 1440,64 L1440,120 L0,120 Z" />
        </svg>
    </section>
</template>

<style>
@keyframes hero-image-in {
    from {
        opacity: 0;
        transform: scale(1.05);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@media (prefers-reduced-motion: reduce) {
    [style*='hero-image-in'] {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }
}
</style>
