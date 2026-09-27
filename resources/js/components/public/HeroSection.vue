<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRightIcon } from '@heroicons/vue/24/outline';

interface HeroSectionProps {
    headline: string;
    subtext: string;
    primaryCta: {
        label: string;
        href: string;
    };
    secondaryCta?: {
        label: string;
        href: string;
    };
    illustrationSeed: string;
}

const props = withDefaults(defineProps<HeroSectionProps>(), {
    secondaryCta: undefined,
});
</script>

<template>
    <section class="relative min-h-[90dvh] flex items-center pt-20 pb-16 md:pt-32 md:pb-24 lg:pt-40 lg:pb-32">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <div class="max-w-xl mx-auto lg:mx-0 text-center lg:text-left" style="animation: fadeInUp 0.6s ease-out forwards; opacity: 0;">
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold tracking-tighter leading-none text-foreground mb-6" style="animation: fadeInUp 0.6s ease-out 0.1s forwards; opacity: 0;">
                        {{ props.headline }}
                    </h1>
                    <p class="text-base md:text-lg text-muted-foreground leading-relaxed max-w-[65ch] mb-8 mx-auto lg:mx-0" style="animation: fadeInUp 0.6s ease-out 0.2s forwards; opacity: 0;">
                        {{ props.subtext }}
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4" style="animation: fadeInUp 0.6s ease-out 0.3s forwards; opacity: 0;">
                        <Link :href="props.primaryCta.href" class="btn-primary w-full sm:w-auto justify-center">
                            {{ props.primaryCta.label }}
                            <ChevronRightIcon class="ml-2 h-4 w-4" />
                        </Link>
                        <Link v-if="props.secondaryCta" :href="props.secondaryCta.href" class="btn-outline w-full sm:w-auto justify-center">
                            {{ props.secondaryCta.label }}
                        </Link>
                    </div>
                </div>

                <div class="relative mx-auto lg:mx-0" style="animation: fadeInScale 0.8s ease-out 0.4s forwards; opacity: 0;">
                    <div class="relative aspect-[4/3] max-w-lg mx-auto rounded-[var(--radius-xl)] bg-gradient-to-br from-primary/5 via-background to-primary/5 border border-border overflow-hidden">
                        <img :src="`https://picsum.photos/seed/${props.illustrationSeed}/800/600`" :alt="`${props.headline} illustration`" class="absolute inset-0 w-full h-full object-cover opacity-60 dark:opacity-40" />
                        <div class="absolute inset-0 bg-gradient-to-t from-background/80 via-background/20 to-transparent" />
                    </div>
                    <div class="absolute -top-4 -right-4 w-32 h-32 rounded-full bg-primary/10 blur-3xl" aria-hidden="true" />
                    <div class="absolute -bottom-8 -left-8 w-24 h-24 rounded-full bg-primary/10 blur-3xl" aria-hidden="true" />
                </div>
            </div>
        </div>
    </section>
</template>

<style>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(24px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInScale {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>