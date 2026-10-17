<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';
import { useLenis } from '@/composables/useLenis';

interface HeroStat {
    icon: string;
    label: string;
    value: string;
}

interface SocialProof {
    avatarUrls: string[];
    line: string;
}

interface Props {
    headline: string;
    subtext: string;
    primaryCta: { label: string; href: string };
    secondaryCta?: { label: string; href: string };
    imageSeed: string;
    stats?: HeroStat[];
    /** Authority cue, e.g. "25+ Years of Excellence" - the hero's one allowed eyebrow slot. */
    eyebrow?: string;
    /** Real staff faces + a live count, not generated avatars - see Section 9.D of the design skill. */
    socialProof?: SocialProof;
    /** Honest time-anchor from the real active session, e.g. "New session begins 1 Apr 2026." */
    sessionNote?: string;
}

defineProps<Props>();

/** A `#section` CTA scrolls within the page; anything else is a real route. */
const isAnchor = (href: string) => href.startsWith('#');

const { scrollTo } = useLenis();
</script>

<template>
    <section class="relative isolate overflow-hidden pt-16 pb-28 md:pt-24 md:pb-36">
        <!-- Full-bleed background photo, dark-tinted so light text and the glass panels read clearly over it.
             Direct Unsplash CDN URL (free license, verified), not picsum.photos: picsum's "seed" only
             guarantees a *consistent* random photo, never a *school-themed* one. -->
        <img
            src="https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=1920&q=75"
            :alt="headline"
            class="absolute inset-0 -z-20 h-full w-full object-cover"
            loading="eager"
        />
        <!-- Light overall tint plus a stronger fade behind the text panel's corner, so the photo stays
             visible on the right while the left side (text panel) still has enough contrast underneath. -->
        <div class="absolute inset-0 -z-10 bg-sidebar/30" aria-hidden="true" />
        <div class="absolute inset-0 -z-10 bg-linear-to-r from-sidebar/70 via-sidebar/20 to-transparent" aria-hidden="true" />

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Glass panel holding the message, approximating frosted glass (backdrop-filter), not Apple's native Liquid Glass -->
            <div class="max-w-xl rounded-4xl border border-white/15 bg-sidebar/40 p-6 shadow-2xl backdrop-blur-xl sm:p-8">
                <p v-if="eyebrow" class="mb-3 inline-flex items-center gap-1.5 text-sm font-semibold text-sidebar-foreground">
                    <Icon icon="award" class="h-4 w-4 text-primary" />
                    {{ eyebrow }}
                </p>
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

            <!-- Floating glass cards, anchored to the background photo rather than a boxed-in side image. -->
            <div class="mt-10 flex flex-wrap items-start gap-4">
                <div class="w-fit rounded-2xl border border-white/15 bg-sidebar/40 p-4 shadow-xl backdrop-blur-xl sm:w-64">
                    <div class="flex items-center gap-2">
                        <Icon icon="shield-check" class="h-5 w-5 text-primary" />
                        <p class="text-xs font-semibold text-sidebar-foreground">Admissions Open</p>
                    </div>
                    <p class="mt-1 text-xs text-sidebar-foreground/70">{{ sessionNote ?? 'Apply for the upcoming session today.' }}</p>
                </div>

                <!-- Social proof: real staff faces (not generated avatars) plus a live, real count. -->
                <div v-if="socialProof" class="flex w-fit items-center gap-3 rounded-2xl border border-white/15 bg-sidebar/40 p-4 shadow-xl backdrop-blur-xl">
                    <div class="flex -space-x-3">
                        <img
                            v-for="url in socialProof.avatarUrls"
                            :key="url"
                            :src="url"
                            alt=""
                            class="h-9 w-9 rounded-full border-2 border-sidebar object-cover"
                        />
                    </div>
                    <p class="max-w-36 text-xs font-medium text-sidebar-foreground">{{ socialProof.line }}</p>
                </div>
            </div>

            <!-- Glass stat cards over the open right side of the background photo (desktop only; the left
                 panel already fills this role on mobile where there's no spare width for them). -->
            <div v-if="stats?.length" class="pointer-events-none absolute inset-y-0 right-4 hidden w-56 flex-col justify-center gap-5 lg:right-8 lg:flex xl:right-12">
                <div
                    v-for="(stat, index) in stats"
                    :key="stat.label"
                    class="pointer-events-auto rounded-2xl border border-white/15 bg-sidebar/40 p-4 shadow-xl backdrop-blur-xl"
                    :class="index % 2 === 1 ? 'ml-10' : ''"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/20">
                            <Icon :icon="stat.icon" class="h-4.5 w-4.5 text-primary" />
                        </div>
                        <div>
                            <p class="text-lg font-bold text-sidebar-foreground">{{ stat.value }}</p>
                            <p class="text-xs text-sidebar-foreground/70">{{ stat.label }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wave divider -->
        <svg class="absolute bottom-0 left-0 w-full text-background" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true">
            <path fill="currentColor" d="M0,64 C240,120 480,0 720,32 C960,64 1200,120 1440,64 L1440,120 L0,120 Z" />
        </svg>
    </section>
</template>
