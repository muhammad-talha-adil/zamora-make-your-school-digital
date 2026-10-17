<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { useScrollReveal } from '@/composables/useScrollReveal';

interface Facility {
    icon: string;
    label: string;
}

defineProps<{ facilities: Facility[] }>();

const { target, revealed } = useScrollReveal();
</script>

<template>
    <section id="campus-life" ref="target" class="bg-background py-20 md:py-28">
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8" :class="revealed ? 'reveal-visible' : 'reveal'">
            <h2 class="text-3xl font-bold tracking-tight text-foreground md:text-4xl">
                State-of-the-Art Campus
            </h2>

            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="facility in facilities"
                    :key="facility.label"
                    class="flex flex-col items-start rounded-2xl border border-border bg-card p-6 text-left shadow-sm transition-transform duration-300 hover:-translate-y-1"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10">
                        <Icon :icon="facility.icon" class="h-6 w-6 text-primary" />
                    </div>
                    <h3 class="mt-5 text-base font-semibold uppercase tracking-wide text-foreground">{{ facility.label }}</h3>
                    <button type="button" class="mt-4 inline-flex h-9 items-center justify-center rounded-full bg-primary px-4 text-xs font-semibold text-primary-foreground transition-opacity hover:opacity-90">
                        View Facilities
                    </button>
                </article>
            </div>
        </div>
    </section>
</template>
