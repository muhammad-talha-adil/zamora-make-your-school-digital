<script setup lang="ts">
import { computed } from 'vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';

export interface FeeItemOptionValues {
    frequency: string;
    is_optional: boolean;
    applicable_on_admission: boolean;
    is_transport_related: boolean;
    starts_from_month_id: number | null;
    ends_at_month_id: number | null;
    billing_month_id: number | null;
    billing_year: number | null;
}

const props = defineProps<{
    modelValue: FeeItemOptionValues;
    months: Array<{ id: number; name: string; month_number: number }>;
}>();

const emit = defineEmits<{ (e: 'update:modelValue', value: FeeItemOptionValues): void }>();

const update = (patch: Partial<FeeItemOptionValues>) => emit('update:modelValue', { ...props.modelValue, ...patch });
const toId = (value: string): number | null => (value === '' ? null : Number(value));

const frequencies = [
    { value: 'monthly', label: 'Every month' },
    { value: 'yearly', label: 'Once a year' },
    { value: 'once', label: 'One time only' },
];

const selectClass = 'h-9 w-full rounded-md border border-border bg-card px-2 text-sm text-foreground';
const showBilling = computed(() => props.modelValue.frequency === 'yearly');
</script>

<template>
    <div class="space-y-3">
        <div class="space-y-2">
            <Label>How often</Label>
            <select
                :value="modelValue.frequency"
                :class="selectClass"
                @change="update({ frequency: ($event.target as HTMLSelectElement).value })"
            >
                <option v-for="freq in frequencies" :key="freq.value" :value="freq.value">{{ freq.label }}</option>
            </select>
        </div>

        <details class="rounded-md border border-border bg-card px-3 py-2">
            <summary class="cursor-pointer text-sm text-muted-foreground">More options</summary>
            <div class="mt-3 space-y-3">
                <div class="flex flex-wrap gap-x-6 gap-y-2">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" class="h-4 w-4 rounded border-border text-primary" :checked="modelValue.is_optional" @change="update({ is_optional: ($event.target as HTMLInputElement).checked })" />
                        Optional (parent can skip it)
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" class="h-4 w-4 rounded border-border text-primary" :checked="modelValue.applicable_on_admission" @change="update({ applicable_on_admission: ($event.target as HTMLInputElement).checked })" />
                        Charged at admission
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" class="h-4 w-4 rounded border-border text-primary" :checked="modelValue.is_transport_related" @change="update({ is_transport_related: ($event.target as HTMLInputElement).checked })" />
                        Transport fee
                    </label>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="space-y-1">
                        <Label class="text-xs">Starts from month</Label>
                        <select :value="modelValue.starts_from_month_id ?? ''" :class="selectClass" @change="update({ starts_from_month_id: toId(($event.target as HTMLSelectElement).value) })">
                            <option value="">Whole year</option>
                            <option v-for="month in months" :key="month.id" :value="month.id">{{ month.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <Label class="text-xs">Ends at month</Label>
                        <select :value="modelValue.ends_at_month_id ?? ''" :class="selectClass" @change="update({ ends_at_month_id: toId(($event.target as HTMLSelectElement).value) })">
                            <option value="">Whole year</option>
                            <option v-for="month in months" :key="month.id" :value="month.id">{{ month.name }}</option>
                        </select>
                    </div>
                </div>

                <div v-if="showBilling" class="grid gap-3 sm:grid-cols-2">
                    <div class="space-y-1">
                        <Label class="text-xs">Billed in month</Label>
                        <select :value="modelValue.billing_month_id ?? ''" :class="selectClass" @change="update({ billing_month_id: toId(($event.target as HTMLSelectElement).value) })">
                            <option value="">First month of the session</option>
                            <option v-for="month in months" :key="month.id" :value="month.id">{{ month.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <Label class="text-xs">Billed in year</Label>
                        <Input
                            type="number"
                            min="2000"
                            max="2100"
                            :model-value="modelValue.billing_year ?? ''"
                            placeholder="Session year"
                            @update:model-value="(value: string | number) => update({ billing_year: value === '' ? null : Number(value) })"
                        />
                    </div>
                </div>
            </div>
        </details>
    </div>
</template>
