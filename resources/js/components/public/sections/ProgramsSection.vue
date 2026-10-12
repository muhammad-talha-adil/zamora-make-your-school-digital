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
</script>

<template>
    <section id="academics" ref="target" class="bg-muted/30 py-20 md:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 class="max-w-xl text-3xl font-bold tracking-tight text-foreground md:text-4xl" :class="revealed ? 'reveal-visible' : 'reveal'">
                A curriculum that grows with every student
            </h2>

            <div class="mt-12 grid gap-5 md:grid-cols-3" :class="revealed ? 'reveal-visible' : 'reveal'">
                <article
                    v-if="programs[0]"
                    class="group relative flex flex-col justify-end overflow-hidden rounded-2xl border border-border p-7 transition-transform duration-300 hover:-translate-y-1 md:col-span-2 md:row-span-2 md:min-h-[320px]"
                >
                    <img
                        src="https://picsum.photos/seed/classroom-learning/1000/800"
                        alt=""
                        class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />
                    <div class="relative">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/15 backdrop-blur-sm">
                            <Icon :icon="programs[0].icon" class="h-5 w-5 text-white" />
                        </div>
                        <h3 class="mt-4 text-xl font-semibold text-white">{{ programs[0].title }}</h3>
                        <p class="mt-2 max-w-sm text-sm leading-relaxed text-white/85">{{ programs[0].description }}</p>
                    </div>
                </article>

                <article
                    v-for="program in programs.slice(1, 4)"
                    :key="program.title"
                    class="flex flex-col rounded-2xl border border-border bg-card p-6 transition-transform duration-300 hover:-translate-y-1"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                        <Icon :icon="program.icon" class="h-5 w-5 text-primary" />
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-foreground">{{ program.title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ program.description }}</p>
                </article>
            </div>
        </div>
    </section>
</template>
