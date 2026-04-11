<template>
    <div
        class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
    >
        <h2
            v-if="title"
            class="mb-4 flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white"
        >
            <Icon v-if="icon" :icon="icon" class="h-5 w-5 text-primary" />
            {{ title }}
        </h2>
        <slot />
    </div>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';

interface Props {
    title?: string;
    icon?: string;
}

defineProps<Props>();
</script>
