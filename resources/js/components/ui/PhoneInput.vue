<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { formatPhone, PHONE_PLACEHOLDER } from '@/utils/phone';

defineOptions({ inheritAttrs: false });

const props = defineProps<{ modelValue?: string | number | null }>();

const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>();

const onInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const formatted = formatPhone(target.value);

    target.value = formatted;
    emit('update:modelValue', formatted);
};
</script>

<template>
    <Input
        type="tel"
        inputmode="tel"
        autocomplete="tel"
        :placeholder="PHONE_PLACEHOLDER"
        maxlength="20"
        v-bind="$attrs"
        :model-value="formatPhone(props.modelValue)"
        @input="onInput"
        @update:model-value="() => {}"
    />
</template>
