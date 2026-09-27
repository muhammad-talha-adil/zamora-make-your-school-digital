<script setup lang="ts">
interface TimelineItem {
    year: string;
    title: string;
    description: string;
}

interface TimelineProps {
    items: TimelineItem[];
    title?: string;
    subtitle?: string;
}

const props = defineProps<TimelineProps>();
</script>

<template>
    <section class="py-16 md:py-24" :aria-labelledby="props.title ? 'timeline-heading' : undefined">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <header v-if="props.title" class="text-center max-w-3xl mx-auto mb-16">
                <h2 id="timeline-heading" class="text-3xl md:text-4xl font-semibold tracking-tight text-foreground mb-4">
                    {{ props.title }}
                </h2>
                <p v-if="props.subtitle" class="text-base text-muted-foreground leading-relaxed max-w-[65ch] mx-auto">
                    {{ props.subtitle }}
                </p>
            </header>

            <div class="max-w-3xl mx-auto">
                <ol class="relative" role="list">
                    <li v-for="(item, index) in props.items" :key="item.year"
                        class="relative pl-8 pb-12 last:pb-0"
                        style="animation: fadeInLeft 0.6s ease-out forwards; opacity: 0;"
                        :style="{ animationDelay: `${index * 100}ms` }">
                        <!-- Timeline line -->
                        <div class="absolute left-3 top-0 bottom-0 w-0.5 bg-border" v-if="index < props.items.length - 1" />
                        <div class="absolute left-3 top-0 w-1.5 h-1.5 rounded-full bg-primary border-4 border-background" />

                        <!-- Content -->
                        <div class="bg-card border border-border rounded-[var(--radius-lg)] p-6">
                            <div class="flex items-baseline gap-3 mb-2">
                                <time class="text-sm font-semibold text-primary" :datetime="item.year">{{ item.year }}</time>
                                <h3 class="text-lg font-semibold text-foreground">{{ item.title }}</h3>
                            </div>
                            <p class="text-muted-foreground leading-relaxed">{{ item.description }}</p>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </section>
</template>

<style>
@keyframes fadeInLeft {
    from { opacity: 0; transform: translateX(-24px); }
    to { opacity: 1; transform: translateX(0); }
}
</style>