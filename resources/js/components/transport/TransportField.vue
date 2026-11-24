<script setup lang="ts">
import InputError from '@/components/InputError.vue';

defineProps<{
    label: string;
    error?: string;
    required?: boolean;
}>();
</script>

<template>
    <div :data-transport-error="error ? 'true' : undefined">
        <label class="mb-1.5 block text-sm font-medium text-muted-foreground">
            {{ label }} <span v-if="required" class="text-destructive">*</span>
        </label>
        <slot />
        <InputError :message="error" class="mt-1" />
    </div>
</template>
