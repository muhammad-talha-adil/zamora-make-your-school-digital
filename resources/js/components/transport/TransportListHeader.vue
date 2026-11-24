<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineProps<{
    title: string;
    description: string;
    addLabel?: string;
    searchPlaceholder?: string;
}>();

const search = defineModel<string>('search', { default: '' });
const emit = defineEmits<{ (event: 'add'): void }>();
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-foreground">{{ title }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">{{ description }}</p>
            </div>
            <Button v-if="addLabel" type="button" class="text-white" @click="emit('add')">
                <Icon icon="plus" class="mr-2 h-4 w-4" />
                {{ addLabel }}
            </Button>
        </div>
        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-border bg-card p-4 shadow-sm">
            <Input v-model="search" :placeholder="searchPlaceholder ?? 'Search...'" class="w-full sm:max-w-md" />
            <slot name="filters" />
        </div>
    </div>
</template>
