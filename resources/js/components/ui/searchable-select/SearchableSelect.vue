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
    /** Single mode: string | number | null. Multiple mode (`multiple: true`): an array of values. */
    modelValue?: string | number | null | (string | number)[];
    options: SearchableSelectOption[];
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    disabled?: boolean;
    clearable?: boolean;
    class?: string;
    /** Picks more than one option — modelValue becomes an array, the trigger shows a count/list instead of one label. */
    multiple?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    placeholder: 'Select an option',
    searchPlaceholder: 'Search...',
    emptyText: 'No results found.',
    disabled: false,
    clearable: false,
    class: '',
    multiple: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string | number | null | (string | number)[]];
}>();

const modelValueAsArray = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : []));

// Loosely compared: a model value can arrive as a string (e.g. straight off
// a session value or a route param) while option values come from freshly
// cast model ids — a strict `===` here silently fails to match and falls
// back to the placeholder/empty state even though the right option exists.
const selectedOption = computed(() =>
    props.multiple ? null : props.options.find((option) => String(option.value) === String(props.modelValue)) ?? null
);

const selectedOptions = computed(() =>
    props.multiple
        ? props.options.filter((option) => modelValueAsArray.value.some((v) => String(v) === String(option.value)))
        : []
);

const triggerLabel = computed(() => {
    if (!props.multiple) {
        return selectedOption.value ? selectedOption.value.label : props.placeholder;
    }

    if (selectedOptions.value.length === 0) {
        return props.placeholder;
    }

    if (selectedOptions.value.length <= 2) {
        return selectedOptions.value.map((o) => o.label).join(', ');
    }

    return `${selectedOptions.value.length} selected`;
});

// Single mode: ComboboxRoot's own v-model, bridged to the string|number modelValue.
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

// Multiple mode: ComboboxRoot's v-model as an array of strings, bridged to the array of real values.
const modelValueAsStringArray = computed({
    get: () => modelValueAsArray.value.map((v) => String(v)),
    set: (values: string[]) => {
        const matched = values.map((value) => {
            const option = props.options.find((o) => String(o.value) === value);
            return option ? option.value : value;
        });
        emit('update:modelValue', matched);
    },
});

const clearSelection = (): void => {
    emit('update:modelValue', props.multiple ? [] : null);
};

const isSelected = (optionValue: string | number): boolean =>
    props.multiple && modelValueAsArray.value.includes(optionValue);
</script>

<template>
    <ComboboxRoot
        v-if="props.multiple"
        v-model="modelValueAsStringArray"
        :multiple="true"
        :disabled="props.disabled"
        class="relative"
        v-slot="{ open }"
    >
        <ComboboxAnchor
            class="flex h-10 w-full items-center justify-between gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            :class="props.class"
        >
            <ComboboxTrigger class="flex min-w-0 flex-1 items-center overflow-hidden text-left">
                <span
                    class="truncate"
                    :class="selectedOptions.length ? 'text-foreground' : 'text-muted-foreground'"
                >
                    {{ triggerLabel }}
                </span>
            </ComboboxTrigger>

            <button
                v-if="props.clearable && selectedOptions.length"
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
                        :display-value="() => ''"
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
                        <Icon v-if="isSelected(option.value)" icon="check" class="h-4 w-4 text-primary" />
                    </ComboboxItem>
                </ComboboxViewport>
            </ComboboxContent>
        </ComboboxPortal>
    </ComboboxRoot>

    <ComboboxRoot
        v-else
        v-model="modelValueAsString"
        :disabled="props.disabled"
        class="relative"
        v-slot="{ open }"
    >
        <ComboboxAnchor
            class="flex h-10 w-full items-center justify-between gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            :class="props.class"
        >
            <ComboboxTrigger class="flex min-w-0 flex-1 items-center overflow-hidden text-left">
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
                        :display-value="() => ''"
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
