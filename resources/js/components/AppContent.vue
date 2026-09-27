<script setup lang="ts">
import { SidebarInset } from '@/components/ui/sidebar';
import { computed } from 'vue';

interface Props {
    variant?: 'header' | 'sidebar';
    class?: string;
}

const props = defineProps<Props>();
const className = computed(() => props.class);
</script>

<template>
    <SidebarInset v-if="props.variant === 'sidebar'" :class="['p-4 md:p-6 lg:p-8', className]">
        <main id="main-content" class="flex-1">
            <slot />
        </main>
    </SidebarInset>
    <main
        v-else
        id="main-content"
        class="mx-auto flex min-h-[100dvh] w-full max-w-7xl flex-1 flex-col p-4 md:p-6 lg:p-8"
        :class="className"
    >
        <slot />
    </main>
</template>