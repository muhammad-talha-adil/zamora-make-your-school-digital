<script setup lang="ts">
import Accordion from '@/components/public/Accordion.vue';
import { useScrollReveal } from '@/composables/useScrollReveal';

interface Item {
    question: string;
    answer: string;
}

defineProps<{ items: Item[] }>();

const { target, revealed } = useScrollReveal();
</script>

<template>
    <section ref="target" class="bg-background py-20 md:py-28">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8" :class="revealed ? 'reveal-visible' : 'reveal'">
            <h2 class="text-center text-3xl font-bold tracking-tight text-foreground md:text-4xl">
                Frequently asked questions
            </h2>
            <div class="mt-10">
                <Accordion :items="items" />
            </div>
        </div>
    </section>
</template>
