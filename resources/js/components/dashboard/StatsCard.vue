<template>
    <div class="bg-card p-6 rounded-lg shadow-lg border border-border hover:shadow-xl transition-shadow">
        <div class="flex items-center">
            <div class="p-3 bg-accent rounded-full">
                <component :is="icon" class="w-6 h-6 text-primary" />
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-muted-foreground">{{ title }}</h3>
                <p class="text-2xl font-bold text-card-foreground">{{ value }}</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { UsersIcon, CurrencyDollarIcon, BookOpenIcon, UserGroupIcon } from '@heroicons/vue/24/outline';

interface Props {
    title: string;
    value: string;
    icon: typeof UsersIcon;
}

defineProps<Props>();
</script>
