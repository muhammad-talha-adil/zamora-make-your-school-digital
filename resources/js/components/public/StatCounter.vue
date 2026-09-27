<script setup lang="ts">
import { ref, onMounted } from 'vue';

interface Stat {
    label: string;
    value: number;
    suffix?: string;
    prefix?: string;
}

interface StatCounterProps {
    stats: Stat[];
    title?: string;
    subtitle?: string;
}

const props = withDefaults(defineProps<StatCounterProps>(), {
    title: 'Our school by the numbers',
    subtitle: 'A growing community built on strong academics and personal attention.',
});

const displayValues = ref<number[]>([]);

const animateCount = (target: number, index: number) => {
    const duration = 2000;
    const startTime = performance.now();
    const startValue = 0;

    const update = (currentTime: number) => {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = Math.floor(startValue + (target - startValue) * eased);
        displayValues.value[index] = current;

        if (progress < 1) {
            requestAnimationFrame(update);
        }
    };

    requestAnimationFrame(update);
};

onMounted(() => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                props.stats.forEach((stat, index) => {
                    animateCount(stat.value, index);
                });
                observer.disconnect();
            }
        });
    }, { threshold: 0.5 });

    const element = document.querySelector('.stat-counter-section');
    if (element) observer.observe(element);
});
</script>

<template>
    <section class="stat-counter-section py-16 md:py-24 bg-primary text-primary-foreground" aria-labelledby="stats-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <header class="text-center max-w-3xl mx-auto mb-16">
                <h2 id="stats-heading" class="text-3xl md:text-4xl font-semibold tracking-tight mb-4">
                    {{ props.title }}
                </h2>
                <p class="text-base text-primary-foreground/70 leading-relaxed max-w-[65ch] mx-auto">
                    {{ props.subtitle }}
                </p>
            </header>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div v-for="(stat, index) in props.stats" :key="stat.label"
                     class="text-center"
                     style="animation: fadeInUp 0.6s ease-out forwards; opacity: 0;"
                     :style="{ animationDelay: `${index * 60}ms` }">
                    <div class="text-4xl md:text-5xl lg:text-6xl font-bold tracking-tighter leading-none mb-2">
                        {{ stat.prefix || '' }}{{ displayValues[index] || 0 }}{{ stat.suffix || '' }}
                    </div>
                    <div class="text-lg text-primary-foreground/70">{{ stat.label }}</div>
                </div>
            </div>
        </div>
    </section>
</template>

<style>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(24px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>