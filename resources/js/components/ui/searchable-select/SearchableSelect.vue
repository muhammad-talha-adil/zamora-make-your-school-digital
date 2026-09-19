<script setup lang="ts">
import { computed } from 'vue';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxPortal,
    ComboboxRoot,
    ComboboxTrigger,
    ComboboxViewport,
} from 'reka-ui';
import Icon from '@/components/Icon.vue';

export interface SearchableSelectOption {
    value: string | number;
    label: string;
    disabled?: boolean;
}

interface Props {
    modelValue?: string | number | null;
    options: SearchableSelectOption[];
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    disabled?: boolean;
    clearable?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    placeholder: 'Select an option',
    searchPlaceholder: 'Search...',
    emptyText: 'No results found.',
    disabled: false,
    clearable: false,
    class: '',
});

const emit = defineEmits<{
    'update:modelValue': [value: string | number | null];
}>();

const selectedOption = computed(() =>
    props.options.find((option) => option.value === props.modelValue) ?? null
);

const modelValueAsString = computed({
    get: () => (props.modelValue === null || props.modelValue === undefined ? undefined : String(props.modelValue)),
    set: (value: string | undefined) => {
        if (value === undefined) {
            emit('update:modelValue', null);
            return;
        }

        const matched = props.options.find((option) => String(option.value) === value);
        emit('update:modelValue', matched ? matched.value : value);
    },
});

const clearSelection = (): void => {
    emit('update:modelValue', null);
};
</script>

<template>
    <ComboboxRoot
        v-model="modelValueAsString"
        :disabled="props.disabled"
        class="relative"
        v-slot="{ open }"
    >
        <ComboboxAnchor
            class="flex h-10 w-full items-center justify-between gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            :class="props.class"
        >
            <ComboboxTrigger class="flex flex-1 items-center overflow-hidden text-left">
                <span
                    class="truncate"
                    :class="selectedOption ? 'text-foreground' : 'text-muted-foreground'"
                >
                    {{ selectedOption ? selectedOption.label : props.placeholder }}
                </span>
            </ComboboxTrigger>

            <button
                v-if="props.clearable && selectedOption"
                type="button"
                class="text-muted-foreground hover:text-foreground"
                @click.stop="clearSelection"
            >
                <Icon icon="x" class="h-3.5 w-3.5" />
            </button>

            <Icon
                icon="chevron-down"
                class="h-4 w-4 shrink-0 text-muted-foreground transition-transform"
                :class="{ 'rotate-180': open }"
            />
        </ComboboxAnchor>

        <ComboboxPortal>
            <ComboboxContent
                class="z-[100] mt-1 max-h-60 w-[--reka-combobox-trigger-width] overflow-hidden rounded-md border border-border bg-card shadow-lg"
                position="popper"
            >
                <div class="sticky top-0 border-b border-border bg-card p-1.5">
                    <ComboboxInput
                        auto-focus
                        :placeholder="props.searchPlaceholder"
                        class="flex h-8 w-full rounded-sm border-0 bg-transparent px-2 text-sm text-foreground placeholder:text-muted-foreground focus-visible:outline-none"
                    />
                </div>

                <ComboboxViewport class="max-h-48 overflow-auto p-1">
                    <ComboboxEmpty class="px-3 py-2 text-sm text-muted-foreground">
                        {{ props.emptyText }}
                    </ComboboxEmpty>

                    <ComboboxItem
                        v-for="option in props.options"
                        :key="option.value"
                        :value="String(option.value)"
                        :disabled="option.disabled"
                        class="relative flex cursor-pointer select-none items-center justify-between rounded-sm px-3 py-2 text-sm text-foreground outline-none data-[highlighted]:bg-accent data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
                    >
                        <span class="truncate">{{ option.label }}</span>
                        <ComboboxItemIndicator>
                            <Icon icon="check" class="h-4 w-4 text-primary" />
                        </ComboboxItemIndicator>
                    </ComboboxItem>
                </ComboboxViewport>
            </ComboboxContent>
        </ComboboxPortal>
    </ComboboxRoot>
</template>
