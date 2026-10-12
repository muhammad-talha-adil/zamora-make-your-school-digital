<script setup lang="ts">
import { ref } from 'vue';
import Icon from '@/components/Icon.vue';

interface Item {
    question: string;
    answer: string;
}

defineProps<{ items: Item[] }>();

const openIndex = ref<number | null>(0);

const toggle = (index: number) => {
    openIndex.value = openIndex.value === index ? null : index;
};
</script>

<template>
    <div class="divide-y divide-border rounded-2xl border border-border bg-card">
        <div v-for="(item, index) in items" :key="item.question">
            <button
                type="button"
                class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left"
                :aria-expanded="openIndex === index"
                @click="toggle(index)"
            >
                <span class="font-medium text-foreground">{{ item.question }}</span>
                <Icon
                    icon="chevron-down"
                    class="h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200"
                    :class="{ 'rotate-180': openIndex === index }"
                />
            </button>
            <div
                class="grid overflow-hidden transition-[grid-template-rows] duration-300 ease-out"
                :style="{ gridTemplateRows: openIndex === index ? '1fr' : '0fr' }"
            >
                <div class="min-h-0">
                    <p class="px-6 pb-5 text-sm leading-relaxed text-muted-foreground">{{ item.answer }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
