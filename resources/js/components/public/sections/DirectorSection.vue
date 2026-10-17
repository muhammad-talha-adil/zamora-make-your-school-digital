<script setup lang="ts">
import { useScrollReveal } from '@/composables/useScrollReveal';

interface Director {
    name: string;
    designation: string | null;
    department: string | null;
    photo_url: string | null;
}

defineProps<{ director: Director }>();

const { target, revealed } = useScrollReveal();
</script>

<template>
    <section ref="target" class="bg-background py-20 md:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" :class="revealed ? 'reveal-visible' : 'reveal'">
            <div class="grid items-center gap-10 lg:grid-cols-[0.8fr_1fr] lg:gap-16">
                <div class="relative mx-auto w-full max-w-sm">
                    <div class="absolute -inset-4 -z-10 rounded-[2rem] bg-primary/10 blur-2xl" aria-hidden="true" />
                    <div class="overflow-hidden rounded-[2rem] border border-border shadow-xl">
                        <img
                            v-if="director.photo_url"
                            :src="director.photo_url"
                            :alt="director.name"
                            class="aspect-4/5 w-full object-cover"
                        />
                        <div v-else class="flex aspect-4/5 w-full items-center justify-center bg-muted text-5xl font-bold text-muted-foreground">
                            {{ director.name.charAt(0) }}
                        </div>
                    </div>

                    <!-- Glass info card floating on the photo -->
                    <div class="absolute -bottom-6 left-1/2 w-[85%] -translate-x-1/2 rounded-2xl border border-white/20 bg-card/80 p-4 text-center shadow-lg backdrop-blur-md">
                        <p class="text-sm font-semibold text-foreground">{{ director.name }}</p>
                        <p class="text-xs text-muted-foreground">{{ director.designation }}</p>
                    </div>
                </div>

                <div class="pt-8 lg:pt-0">
                    <p class="text-sm font-semibold text-primary">A message from our leadership</p>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight text-foreground md:text-4xl">
                        Meet {{ director.name }}
                    </h2>
                    <p class="mt-5 max-w-xl text-base leading-relaxed text-muted-foreground">
                        Leading {{ director.department ? director.department : 'our school' }} with a focus on
                        academic excellence and the wellbeing of every student who walks through our doors.
                        Our doors are always open to families who want to be part of this journey.
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
