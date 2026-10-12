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
    <section class="relative overflow-hidden bg-background pt-20 pb-16 md:pt-24 md:pb-24">
        <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-[1.1fr_1fr] lg:gap-16 lg:px-8">
            <div class="max-w-xl">
                <h1 class="text-4xl font-bold leading-[1.05] tracking-tight text-foreground md:text-5xl lg:text-6xl">
                    {{ headline }}
                </h1>
                <p class="mt-5 max-w-md text-base leading-relaxed text-muted-foreground md:text-lg">
                    {{ subtext }}
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <button v-if="isAnchor(primaryCta.href)" type="button" class="btn-primary group h-11 px-6" @click="scrollTo(primaryCta.href)">
                        {{ primaryCta.label }}
                        <Icon icon="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                    </button>
                    <Link v-else :href="primaryCta.href" class="btn-primary group h-11 px-6">
                        {{ primaryCta.label }}
                        <Icon icon="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                    </Link>

                    <button v-if="secondaryCta && isAnchor(secondaryCta.href)" type="button" class="btn-outline h-11 px-6" @click="scrollTo(secondaryCta.href)">
                        {{ secondaryCta.label }}
                    </button>
                    <Link v-else-if="secondaryCta" :href="secondaryCta.href" class="btn-outline h-11 px-6">
                        {{ secondaryCta.label }}
                    </Link>
                </div>
            </div>

            <div class="relative aspect-[5/4] w-full overflow-hidden rounded-2xl border border-border lg:aspect-[4/3]">
                <img
                    :src="`https://picsum.photos/seed/${imageSeed}/1200/900`"
                    :alt="headline"
                    class="h-full w-full scale-105 object-cover opacity-0 transition-[opacity,transform] duration-700 ease-out"
                    style="animation: hero-image-in 0.8s ease-out 0.15s forwards"
                />
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-background/30 via-transparent to-transparent" />
            </div>
        </div>
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
