<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { CNIC_PLACEHOLDER, formatCnic } from '@/utils/cnic';

defineOptions({ inheritAttrs: false });

const props = defineProps<{ modelValue?: string | number | null }>();

const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>();

const onInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const formatted = formatCnic(target.value);

    target.value = formatted;
    emit('update:modelValue', formatted);
};
</script>

<template>
    <Input
        inputmode="numeric"
        :placeholder="CNIC_PLACEHOLDER"
        maxlength="15"
        v-bind="$attrs"
        :model-value="formatCnic(props.modelValue)"
        @input="onInput"
        @update:model-value="() => {}"
    />
</template>
