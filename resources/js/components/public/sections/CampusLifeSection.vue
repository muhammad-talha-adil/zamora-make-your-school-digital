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
    <section id="campus-life" ref="target" class="relative overflow-hidden py-24 md:py-32">
        <img
            src="https://picsum.photos/seed/school-campus-grounds/1920/1080"
            alt="Students on campus"
            class="absolute inset-0 h-full w-full object-cover"
            loading="lazy"
        />
        <div class="absolute inset-0 bg-background/85" />

        <div class="relative mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8" :class="revealed ? 'reveal-visible' : 'reveal'">
            <h2 class="mx-auto max-w-2xl text-3xl font-bold tracking-tight text-foreground md:text-4xl">
                More than textbooks and timetables
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-base leading-relaxed text-muted-foreground">
                Sports, arts, and clubs run alongside academics, building confidence and friendships that last well past graduation.
            </p>

            <div class="mx-auto mt-10 flex max-w-2xl flex-wrap items-center justify-center gap-x-8 gap-y-5">
                <div v-for="facility in facilities" :key="facility.label" class="flex items-center gap-2 text-sm font-medium text-foreground">
                    <Icon :icon="facility.icon" class="h-4 w-4 text-primary" />
                    {{ facility.label }}
                </div>
            </div>
        </div>
    </section>
</template>
