<template>
    <div class="bg-card p-5 rounded-xl border border-border shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-primary/10 rounded-lg shrink-0">
                <component :is="icon" class="w-6 h-6 text-primary" />
            </div>
            <div class="min-w-0">
                <h3 class="text-sm text-muted-foreground truncate">{{ title }}</h3>
                <p class="text-2xl font-bold text-foreground">{{ value }}</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { UsersIcon } from '@heroicons/vue/24/outline';

interface Props {
    title: string;
    value: string;
    icon: typeof UsersIcon;
}

defineProps<Props>();
</script>
