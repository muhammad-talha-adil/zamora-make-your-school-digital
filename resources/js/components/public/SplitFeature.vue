<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRightIcon } from '@heroicons/vue/24/outline';

interface SplitFeatureProps {
    title: string;
    description: string;
    features: string[];
    cta: { label: string; href: string };
    imageSeed: string;
    reverse?: boolean;
}

const props = withDefaults(defineProps<SplitFeatureProps>(), {
    reverse: false,
});
</script>

<template>
    <section class="py-16 md:py-24" aria-labelledby="split-feature-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center" :class="{ 'lg:flex-row-reverse': props.reverse }">
                <!-- Image Side -->
                <div class="relative aspect-[4/3] rounded-[var(--radius-xl)] overflow-hidden bg-muted"
                     style="animation: fadeInSlide 0.8s ease-out forwards; opacity: 0;"
                     :style="{ animationDelay: props.reverse ? '200ms' : '0ms' }">
                    <img :src="`https://picsum.photos/seed/${props.imageSeed}/800/600`" 
                         :alt="`${props.title} module screenshot`"
                         class="absolute inset-0 w-full h-full object-cover" />
                    <div class="absolute inset-0 bg-gradient-to-r from-background/60 via-transparent to-transparent" />
                    <div class="absolute bottom-6 left-6 right-6 flex flex-wrap gap-2">
                        <span v-for="feat in props.features" :key="feat"
                              class="px-3 py-1.5 bg-card/90 backdrop-blur-sm border border-border rounded-full text-sm text-foreground">
                            {{ feat }}
                        </span>
                    </div>
                </div>

                <!-- Content Side -->
                <div class="max-w-xl"
                     style="animation: fadeInSlide 0.8s ease-out 100ms forwards; opacity: 0;"
                     :style="{ animationDelay: props.reverse ? '0ms' : '200ms' }">
                    <h2 id="split-feature-heading" class="text-3xl md:text-4xl font-semibold tracking-tight text-foreground mb-4">
                        {{ props.title }}
                    </h2>
                    <p class="text-base text-muted-foreground leading-relaxed max-w-[65ch] mb-8">
                        {{ props.description }}
                    </p>
                    <ul class="space-y-3 mb-8" role="list">
                        <li v-for="feat in props.features" :key="feat"
                            class="flex items-start gap-3 text-sm text-muted-foreground">
                            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-primary/10 flex items-center justify-center mt-0.5">
                                <svg class="w-3 h-3 text-primary" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            {{ feat }}
                        </li>
                    </ul>
                    <Link :href="props.cta.href" class="btn-primary inline-flex items-center gap-2">
                        {{ props.cta.label }}
                        <ChevronRightIcon class="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>

<style>
@keyframes fadeInSlide {
    from { opacity: 0; transform: translateY(24px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>