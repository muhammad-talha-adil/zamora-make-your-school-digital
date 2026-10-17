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
    <section class="relative isolate overflow-hidden pt-16 pb-28 md:pt-24 md:pb-36">
        <!-- Full-bleed background photo, dark-tinted so light text and the glass panels read clearly over it. -->
        <img
            :src="`https://picsum.photos/seed/${imageSeed}/1920/1080`"
            :alt="headline"
            class="absolute inset-0 -z-20 h-full w-full object-cover"
            loading="eager"
        />
        <div class="absolute inset-0 -z-10 bg-sidebar/85" aria-hidden="true" />
        <div class="absolute inset-0 -z-10 bg-linear-to-t from-sidebar via-sidebar/40 to-sidebar/70" aria-hidden="true" />

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Glass panel holding the message, approximating frosted glass (backdrop-filter), not Apple's native Liquid Glass -->
            <div class="max-w-xl rounded-4xl border border-white/15 bg-sidebar/40 p-6 shadow-2xl backdrop-blur-xl sm:p-8">
                <h1 class="text-4xl font-extrabold uppercase leading-[1.05] tracking-tight text-sidebar-foreground md:text-5xl lg:text-6xl">
                    {{ headline }}
                </h1>
                <p class="mt-5 max-w-md text-base leading-relaxed text-sidebar-foreground/75 md:text-lg">
                    {{ subtext }}
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <button
                        v-if="isAnchor(primaryCta.href)"
                        type="button"
                        class="group inline-flex h-11 items-center justify-center gap-2 rounded-full bg-primary px-6 text-sm font-semibold text-primary-foreground shadow-lg shadow-black/20 transition-opacity hover:opacity-90"
                        @click="scrollTo(primaryCta.href)"
                    >
                        {{ primaryCta.label }}
                        <Icon icon="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                    </button>
                    <Link
                        v-else
                        :href="primaryCta.href"
                        class="group inline-flex h-11 items-center justify-center gap-2 rounded-full bg-primary px-6 text-sm font-semibold text-primary-foreground shadow-lg shadow-black/20 transition-opacity hover:opacity-90"
                    >
                        {{ primaryCta.label }}
                        <Icon icon="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                    </Link>

                    <!-- Outline style against the sidebar surface (not another filled color): keeps the
                         second button visually distinct from the primary one under any admin-chosen
                         primary color, without guessing a second brand hue that might clash or wash out. -->
                    <button
                        v-if="secondaryCta && isAnchor(secondaryCta.href)"
                        type="button"
                        class="inline-flex h-11 items-center justify-center rounded-full border-2 border-sidebar-foreground/40 px-6 text-sm font-semibold text-sidebar-foreground transition-colors hover:bg-sidebar-foreground/10"
                        @click="scrollTo(secondaryCta.href)"
                    >
                        {{ secondaryCta.label }}
                    </button>
                    <Link
                        v-else-if="secondaryCta"
                        :href="secondaryCta.href"
                        class="inline-flex h-11 items-center justify-center rounded-full border-2 border-sidebar-foreground/40 px-6 text-sm font-semibold text-sidebar-foreground transition-colors hover:bg-sidebar-foreground/10"
                    >
                        {{ secondaryCta.label }}
                    </Link>
                </div>
            </div>

            <!-- Floating glass stat card, anchored to the background photo rather than a boxed-in side image. -->
            <div class="mt-10 w-fit rounded-2xl border border-white/15 bg-sidebar/40 p-4 shadow-xl backdrop-blur-xl sm:w-64">
                <div class="flex items-center gap-2">
                    <Icon icon="shield-check" class="h-5 w-5 text-primary" />
                    <p class="text-xs font-semibold text-sidebar-foreground">Admissions Open</p>
                </div>
                <p class="mt-1 text-xs text-sidebar-foreground/70">Apply for the upcoming session today.</p>
            </div>
        </div>

        <!-- Wave divider -->
        <svg class="absolute bottom-0 left-0 w-full text-background" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true">
            <path fill="currentColor" d="M0,64 C240,120 480,0 720,32 C960,64 1200,120 1440,64 L1440,120 L0,120 Z" />
        </svg>
    </section>
</template>
