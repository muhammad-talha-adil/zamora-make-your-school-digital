<script setup lang="ts">
import { ChevronDownIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

interface AccordionItem {
    question: string;
    answer: string;
}

interface AccordionProps {
    items: AccordionItem[];
    title?: string;
    subtitle?: string;
}

const props = defineProps<AccordionProps>();

const openIndex = ref<number | null>(null);

const toggleItem = (index: number) => {
    openIndex.value = openIndex.value === index ? null : index;
};
</script>

<template>
    <section class="py-16 md:py-24" :aria-labelledby="props.title ? 'accordion-heading' : undefined">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <header v-if="props.title" class="text-center max-w-3xl mx-auto mb-16">
                <h2 id="accordion-heading" class="text-3xl md:text-4xl font-semibold tracking-tight text-foreground mb-4">
                    {{ props.title }}
                </h2>
                <p v-if="props.subtitle" class="text-base text-muted-foreground leading-relaxed max-w-[65ch] mx-auto">
                    {{ props.subtitle }}
                </p>
            </header>

            <div class="max-w-3xl mx-auto space-y-4">
                <details v-for="(item, index) in props.items" :key="index"
                         class="group bg-card border border-border rounded-[var(--radius-lg)] overflow-hidden"
                         :open="openIndex === index"
                         @toggle="toggleItem(index)">
                    <summary class="flex items-center justify-between p-6 cursor-pointer list-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                             @click.prevent="toggleItem(index)">
                        <h3 class="text-lg font-medium text-foreground pr-10">{{ item.question }}</h3>
                        <ChevronDownIcon class="flex-shrink-0 w-5 h-5 text-muted-foreground transition-transform duration-200 group-open:rotate-180" aria-hidden="true" />
                    </summary>
                    <div class="px-6 pb-6 pt-0 text-muted-foreground leading-relaxed"
                         style="animation: slideDown 0.3s ease-out;">
                        {{ item.answer }}
                    </div>
                </details>
            </div>
        </div>
    </section>
</template>

<style>
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}

details > summary {
    list-style: none;
}
details > summary::-webkit-details-marker {
    display: none;
}
</style>