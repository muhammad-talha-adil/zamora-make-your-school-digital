<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { useScrollReveal } from '@/composables/useScrollReveal';

interface Program {
    icon: string;
    title: string;
    description: string;
}

defineProps<{ programs: Program[] }>();

const { target, revealed } = useScrollReveal();

/** Theme-driven palette (chart tokens come from the admin's color settings, not hardcoded hex). */
const cardColors = ['bg-chart-1', 'bg-chart-2', 'bg-chart-3', 'bg-chart-4'];
</script>

<template>
    <section id="academics" ref="target" class="bg-muted/30 py-20 md:py-28">
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold tracking-tight text-foreground md:text-4xl" :class="revealed ? 'reveal-visible' : 'reveal'">
                Academic Excellence
            </h2>

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4" :class="revealed ? 'reveal-visible' : 'reveal'">
                <article
                    v-for="(program, index) in programs"
                    :key="program.title"
                    class="flex flex-col rounded-2xl p-7 text-left text-white shadow-md transition-transform duration-300 hover:-translate-y-1"
                    :class="cardColors[index % cardColors.length]"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                        <Icon :icon="program.icon" class="h-6 w-6 text-white" />
                    </div>
                    <h3 class="mt-5 text-lg font-semibold">{{ program.title }}</h3>
                    <p class="mt-2 flex-1 text-sm leading-relaxed text-white/85">{{ program.description }}</p>
                    <button type="button" class="mt-6 inline-flex h-9 w-fit items-center justify-center rounded-full bg-white px-4 text-xs font-semibold text-foreground transition-opacity hover:opacity-90">
                        Explore Program
                    </button>
                </article>
            </div>
        </div>
    </section>
</template>
