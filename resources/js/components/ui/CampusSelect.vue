<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, watch } from 'vue';
import SearchableSelect, { type SearchableSelectOption } from '@/components/ui/searchable-select/SearchableSelect.vue';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    modelValue?: string | number | null;
    options: SearchableSelectOption[];
}>();

const emit = defineEmits<{ 'update:modelValue': [value: string | number | null] }>();

const page = usePage();
const scope = computed(() => (page.props as any).academicScope as { campusId: number | null; isCampusRestricted: boolean } | undefined);

/**
 * The campus nobody needs to be asked about: the one header campus in view,
 * or the only campus on offer.
 */
const defaultCampusId = computed<string | number | null>(() => {
    const current = scope.value?.campusId;
    if (current != null && props.options.some((option) => String(option.value) === String(current))) {
        return current;
    }

    return props.options.length === 1 ? props.options[0].value : null;
});

const isLocked = computed(() => props.options.length <= 1 || (scope.value?.isCampusRestricted ?? false));

const selectedLabel = computed(
    () => props.options.find((option) => String(option.value) === String(props.modelValue))?.label ?? '',
);

const applyDefault = () => {
    if ((props.modelValue === null || props.modelValue === undefined || props.modelValue === '') && defaultCampusId.value !== null) {
        emit('update:modelValue', defaultCampusId.value);
    } else if (isLocked.value && defaultCampusId.value !== null && String(props.modelValue) !== String(defaultCampusId.value)) {
        emit('update:modelValue', defaultCampusId.value);
    }
};

onMounted(applyDefault);
watch(() => props.options, applyDefault);
watch(
    () => props.modelValue,
    (value) => {
        if (value === null || value === undefined || value === '') {
            applyDefault();
        }
    },
);
</script>

<template>
    <div
        v-if="isLocked && selectedLabel"
        class="flex h-9 w-full items-center rounded-md border border-input bg-muted/40 px-3 text-sm text-foreground"
    >
        {{ selectedLabel }}
    </div>
    <SearchableSelect
        v-else
        v-bind="$attrs"
        :model-value="props.modelValue"
        :options="props.options"
        @update:model-value="(value) => emit('update:modelValue', value as string | number | null)"
    />
</template>
