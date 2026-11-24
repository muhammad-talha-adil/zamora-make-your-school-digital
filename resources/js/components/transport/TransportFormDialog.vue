<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

defineProps<{
    open: boolean;
    title: string;
    description?: string;
    submitting?: boolean;
    submitLabel?: string;
}>();

const emit = defineEmits<{
    (event: 'update:open', value: boolean): void;
    (event: 'submit'): void;
}>();
</script>

<template>
    <Dialog :open="open" @update:open="(value: boolean) => emit('update:open', value)">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">{{ description }}</DialogDescription>
            </DialogHeader>
            <form novalidate class="space-y-4" @submit.prevent="emit('submit')">
                <slot />
                <DialogFooter class="gap-2 sm:justify-end">
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Cancel</Button>
                    <Button type="submit" class="text-white" :class="{ 'opacity-70': submitting }">
                        {{ submitting ? 'Saving...' : (submitLabel ?? 'Save') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
