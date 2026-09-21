<script setup lang="ts">
import { Switch } from '@/components/ui/switch';

interface Props {
    active: boolean;
    disabled?: boolean;
    activeLabel?: string;
    inactiveLabel?: string;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    activeLabel: 'Active',
    inactiveLabel: 'Inactive',
});

const emit = defineEmits<{ toggle: [] }>();
</script>

<template>
    <div class="flex items-center gap-2">
        <Switch :model-value="props.active" :disabled="props.disabled" @update:model-value="emit('toggle')" />
        <span :class="['text-xs font-medium', props.active ? 'text-primary' : 'text-muted-foreground']">
            {{ props.active ? props.activeLabel : props.inactiveLabel }}
        </span>
    </div>
</template>
