<script setup lang="ts">
import { useScrollReveal } from '@/composables/useScrollReveal';

interface Highlight {
    label: string;
    value: string;
}

defineProps<{
    missionStatement: string;
    /** Real admin-managed stats (Settings > Website Content), not fixed copy - same source StatsBandSection/Hero use. */
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

                <dl class="mt-10 grid gap-3" :class="highlights.length >= 3 ? 'grid-cols-3' : 'grid-cols-2'">
                    <div
                        v-for="highlight in highlights"
                        :key="highlight.label"
                        class="rounded-xl border border-border bg-card p-4 text-center shadow-sm"
                    >
                        <dt class="text-2xl font-bold text-primary md:text-3xl">{{ highlight.value }}</dt>
                        <dd class="mt-1 text-xs text-muted-foreground md:text-sm">{{ highlight.label }}</dd>
                    </div>
                </dl>
            </div>

            <div class="order-1 relative lg:order-2" :class="revealed ? 'reveal-visible' : 'reveal'">
                <div class="absolute -inset-6 -z-10 rounded-[2.5rem] bg-primary/10 blur-3xl" aria-hidden="true" />
                <div class="overflow-hidden rounded-2xl border border-border shadow-lg">
                    <img
                        src="https://images.unsplash.com/photo-1770172410691-acea3d3bfef0?auto=format&fit=crop&w=900&q=75"
                        alt="School campus building"
                        class="aspect-4/3 w-full object-cover"
                        loading="lazy"
                    />
                </div>
                <div class="absolute -bottom-8 -left-8 hidden w-40 overflow-hidden rounded-2xl border border-white/20 shadow-xl sm:block">
                    <img
                        src="https://images.unsplash.com/photo-1741637335289-c99652d3155f?auto=format&fit=crop&w=400&q=75"
                        alt="Students at school"
                        class="aspect-square w-full object-cover"
                        loading="lazy"
                    />
                </div>
            </div>
        </div>
    </section>
</template>
