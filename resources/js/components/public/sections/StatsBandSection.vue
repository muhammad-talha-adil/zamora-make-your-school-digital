<script setup lang="ts">
import { computed, watch } from 'vue';
import Icon from '@/components/Icon.vue';
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

const statIcons = ['graduation-cap', 'book-open', 'users', 'trophy', 'school'];

const formatted = computed(() => props.stats.map((stat, index) => ({
    label: stat.label,
    suffix: stat.suffix ?? '',
    value: counts[index]?.value.value ?? 0,
    icon: statIcons[index % statIcons.length],
})));
</script>

<template>
    <section ref="target" class="relative z-10 -mt-16 bg-transparent">
        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-4 px-4 sm:px-6 md:grid-cols-5 lg:px-8">
            <div
                v-for="stat in formatted"
                :key="stat.label"
                class="flex flex-col items-center gap-2 rounded-xl border border-border bg-card/95 px-4 py-6 text-center shadow-lg shadow-black/5 backdrop-blur-sm"
            >
                <Icon :icon="stat.icon" class="h-7 w-7 text-primary" />
                <p class="text-2xl font-bold text-foreground md:text-3xl">
                    {{ stat.value.toLocaleString() }}{{ stat.suffix }}
                </p>
                <p class="text-xs font-medium text-muted-foreground">{{ stat.label }}</p>
            </div>
        </div>
    </section>
</template>
