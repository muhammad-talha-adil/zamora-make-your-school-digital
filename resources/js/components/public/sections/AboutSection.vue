<script setup lang="ts">
import { useScrollReveal } from '@/composables/useScrollReveal';

interface Highlight {
    label: string;
    value: string;
}

defineProps<{
    missionStatement: string;
    highlights: Highlight[];
}>();

const { target, revealed } = useScrollReveal();
</script>

<template>
    <section id="about" ref="target" class="bg-background py-20 md:py-28">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
            <div class="order-2 lg:order-1" :class="revealed ? 'reveal-visible' : 'reveal'">
                <h2 class="text-3xl font-bold tracking-tight text-foreground md:text-4xl">
                    A school built on knowing every child
                </h2>
                <p class="mt-5 text-base leading-relaxed text-muted-foreground">
                    {{ missionStatement }}
                </p>

                <dl class="mt-10 grid grid-cols-3 gap-6 border-t border-border pt-8">
                    <div v-for="highlight in highlights" :key="highlight.label">
                        <dt class="text-2xl font-bold text-foreground md:text-3xl">{{ highlight.value }}</dt>
                        <dd class="mt-1 text-sm text-muted-foreground">{{ highlight.label }}</dd>
                    </div>
                </dl>
            </div>

            <div class="order-1 overflow-hidden rounded-2xl border border-border lg:order-2" :class="revealed ? 'reveal-visible' : 'reveal'">
                <img
                    src="https://picsum.photos/seed/school-building-facade/900/700"
                    alt="School campus building"
                    class="aspect-[4/3] w-full object-cover"
                    loading="lazy"
                />
            </div>
        </div>
    </section>
</template>
