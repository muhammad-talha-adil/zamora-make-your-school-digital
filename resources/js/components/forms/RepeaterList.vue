<script setup lang="ts">
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';

/**
 * Generic add/remove-row list editor for the simple JSON-array fields on
 * the School model (values, stats, FAQ entries, etc). Callers own the row
 * shape entirely via the default slot and `makeRow`; this component only
 * manages the array mechanics (add/remove) shared across all of them.
 */
interface Props {
    modelValue: Record<string, unknown>[];
    makeRow: () => Record<string, unknown>;
    addLabel?: string;
    emptyText?: string;
}

const props = withDefaults(defineProps<Props>(), {
    addLabel: 'Add row',
    emptyText: 'No entries yet.',
});

const emit = defineEmits<{ 'update:modelValue': [Record<string, unknown>[]] }>();

const addRow = () => {
    emit('update:modelValue', [...props.modelValue, props.makeRow()]);
};

const removeRow = (index: number) => {
    const next = [...props.modelValue];
    next.splice(index, 1);
    emit('update:modelValue', next);
};
</script>

<template>
    <div class="space-y-3">
        <p v-if="!modelValue.length" class="text-sm text-muted-foreground">{{ emptyText }}</p>

        <div
            v-for="(row, index) in modelValue"
            :key="index"
            class="relative space-y-3 rounded-md border border-border p-4"
        >
            <button
                type="button"
                class="absolute right-3 top-3 text-muted-foreground hover:text-destructive"
                @click="removeRow(index)"
            >
                <Icon icon="trash" class="h-4 w-4" />
                <span class="sr-only">Remove row</span>
            </button>

            <slot :row="row" :index="index" />
        </div>

        <Button type="button" variant="outline" size="sm" @click="addRow">
            <Icon icon="plus" class="mr-1 h-4 w-4" />
            {{ addLabel }}
        </Button>
    </div>
</template>
