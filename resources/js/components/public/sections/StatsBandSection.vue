<script setup lang="ts">
import { computed, watch } from 'vue';
import { useScrollReveal } from '@/composables/useScrollReveal';
import { useCountUp } from '@/composables/useCountUp';

interface Stat {
    label: string;
    value: number;
    suffix?: string;
}

const props = defineProps<{ stats: Stat[] }>();

const { target, revealed } = useScrollReveal(0.3);

const counts = props.stats.map((stat) => useCountUp(stat.value));

watch(revealed, (value) => {
    if (value) {
        counts.forEach((counter) => counter.start());
    }
});

const formatted = computed(() => props.stats.map((stat, index) => ({
    label: stat.label,
    suffix: stat.suffix ?? '',
    value: counts[index]?.value.value ?? 0,
})));
</script>

<template>
    <section ref="target" class="border-y border-border bg-muted/40">
        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-6 px-4 py-12 sm:px-6 md:grid-cols-4 lg:px-8">
            <div v-for="stat in formatted" :key="stat.label" class="text-center">
                <p class="text-3xl font-bold text-primary md:text-4xl">
                    {{ stat.value.toLocaleString() }}{{ stat.suffix }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">{{ stat.label }}</p>
            </div>
        </div>
    </section>
</template>
