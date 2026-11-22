<template>
    <div class="rounded-lg border border-border bg-card p-6">
        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
            <Icon icon="credit-card" class="h-5 w-5 text-primary" />
            Fee Structure
            <span class="text-destructive">*</span>
        </h2>

        <!-- No Class Selected Yet -->
        <div v-if="!classId || !sessionId || !campusId" class="text-center py-8 text-muted-foreground">
            <Icon icon="info" class="mx-auto mb-2 h-12 w-12 text-muted-foreground" />
            <p>Please select Session, Class, and Section if required to view the fee structure.</p>
        </div>

        <!-- Fee Structure Loading -->
        <div v-else-if="feeStructureLoading" class="text-center py-8">
            <Icon icon="loader" class="mx-auto h-8 w-8 animate-spin text-primary" />
            <p class="mt-2 text-muted-foreground">Loading fee structure...</p>
        </div>

        <!-- More than one structure matches: ask, never pick silently -->
        <div v-else-if="!feeStructure && structureCandidates.length > 1" class="rounded-lg border border-warning/40 bg-warning/10 p-4">
            <p class="font-medium text-warning">More than one fee structure matches this class</p>
            <p class="mt-1 text-sm text-warning">Choose the one to use for this student.</p>
            <select
                v-model="chosenStructureId"
                class="mt-3 h-11 w-full rounded-md border border-border bg-card px-3 text-foreground"
                @change="fetchFeeStructure()"
            >
                <option value="" disabled>Select a fee structure</option>
                <option v-for="candidate in structureCandidates" :key="candidate.id" :value="candidate.id">{{ candidate.title }}</option>
            </select>
        </div>

        <!-- No Fee Structure Found -->
        <div v-else-if="!feeStructure" class="text-center py-8">
            <div class="rounded-lg border border-warning/40 bg-warning/10 p-4">
                <Icon icon="alert-triangle" class="mx-auto mb-2 h-8 w-8 text-warning" />
                <p class="font-medium text-warning">No fee structure for this class/section</p>
                <p class="mt-1 text-sm text-warning">Add one to continue.</p>
                <Button type="button" variant="outline" size="sm" class="mt-3" @click="handleCreateFeeStructure">
                    <Icon icon="plus" class="mr-1 h-4 w-4" />
                    Add fee structure
                </Button>
            </div>
        </div>

        <!-- Fee Structure Found - Show Options -->
        <div v-else>
            <!-- Fee Mode Tabs -->
            <div class="mb-4 border-b border-border">
                <nav class="-mb-px flex space-x-4">
                    <button
                        type="button"
                        @click="feeMode = 'structure'"
                        :class="[
                            feeMode === 'structure'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                            'whitespace-nowrap border-b-2 px-1 py-2 text-sm font-medium transition-colors'
                        ]"
                    >
                        <Icon icon="check-circle" class="mr-1 inline h-4 w-4" />
                        Use Fee Structure
                    </button>
                    <button
                        type="button"
                        @click="feeMode = 'discount'"
                        :class="[
                            feeMode === 'discount'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                            'whitespace-nowrap border-b-2 px-1 py-2 text-sm font-medium transition-colors'
                        ]"
                    >
                        <Icon icon="percent" class="mr-1 inline h-4 w-4" />
                        Apply Discount
                    </button>
                    <button
                        type="button"
                        @click="feeMode = 'manual'"
                        :class="[
                            feeMode === 'manual'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                            'whitespace-nowrap border-b-2 px-1 py-2 text-sm font-medium transition-colors'
                        ]"
                    >
                        <Icon icon="edit" class="mr-1 inline h-4 w-4" />
                        Fee Heads
                    </button>
                </nav>
            </div>

            <select
                v-if="structureCandidates.length > 1"
                v-model="chosenStructureId"
                class="mb-3 h-11 w-full rounded-md border border-border bg-card px-3 text-foreground"
                @change="fetchFeeStructure()"
            >
                <option v-for="candidate in structureCandidates" :key="candidate.id" :value="candidate.id">{{ candidate.title }}</option>
            </select>

            <!-- Fee Structure Summary -->
            <div class="mb-4 rounded-lg bg-primary/10 p-4">
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <div>
                        <p class="font-medium text-primary">
                            {{ feeStructure.title }}
                        </p>
                        <p class="text-sm text-primary">
                            Monthly: <span class="font-semibold">Rs. {{ headerTotals.monthly.toLocaleString() }}</span> |
                            Annual: <span class="font-semibold">Rs. {{ headerTotals.yearly.toLocaleString() }}</span> |
                            One-time: <span class="font-semibold">Rs. {{ headerTotals.once.toLocaleString() }}</span>
                        </p>
                        <p v-if="headerTotals.yearly > 0 && feeStructure.annual_billed_in" class="mt-1 text-xs text-primary">
                            Annual fee is billed in {{ feeStructure.annual_billed_in }}. A voucher for any other month will not include it.
                        </p>
                        <p v-if="headerTotals.once > 0" class="mt-1 text-xs text-primary">
                            One-time fees go on the first voucher only.
                        </p>
                    </div>
                    <span class="rounded-full bg-success/10 px-3 py-1 text-xs font-medium text-success">
                        Active
                    </span>
                </div>
            </div>

            <!-- OPTION A: Use Fee Structure As-Is -->
            <div v-if="feeMode === 'structure'">
                <p class="mb-4 text-sm text-muted-foreground">
                    The student will be charged according to the fee structure above. No custom fees or discounts applied.
                </p>
            </div>

            <!-- OPTION B: Apply Discount -->
            <div v-else-if="feeMode === 'discount'">
                <p class="mb-4 text-sm text-muted-foreground">
                    Apply discounts to specific fee heads. The discount will persist for the entire academic year.
                </p>

                <!-- Discount Type Selection -->
                <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="discount_type">Discount Type</Label>
                        <SearchableSelect
                            id="discount_type"
                            v-model="selectedDiscountType"
                            :options="discountTypeOptions"
                            placeholder="Select Discount Type"
                            class="h-11"
                            clearable
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="discount_value_type">Discount In</Label>
                        <select
                            id="discount_value_type"
                            v-model="discountValueType"
                            class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="percent">Percentage (%)</option>
                            <option value="fixed">Fixed amount (Rs.)</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="discount_value">{{ discountValueType === 'percent' ? 'Percent off' : 'Rs. off each ticked fee' }}</Label>
                        <div class="flex items-center gap-2">
                            <Input
                                id="discount_value"
                                v-model.number="discountValue"
                                type="number"
                                min="0"
                                :max="discountValueType === 'percent' ? 100 : undefined"
                                step="0.01"
                                placeholder="0.00"
                            />
                            <span class="text-sm text-muted-foreground whitespace-nowrap">
                                {{ discountValueType === 'percent' ? '%' : 'Rs.' }}
                            </span>
                        </div>
                        <p v-if="discountValueType === 'percent' && Number(discountValue) > 100" class="text-xs text-destructive">Percentage can be 0 to 100 only.</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="discount_reason">Reason</Label>
                        <Input id="discount_reason" v-model="discountReason" maxlength="255" placeholder="e.g. Staff child, scholarship" />
                    </div>
                </div>

                <!-- Fee Heads with Discount Checkboxes -->
                <div class="space-y-3">
                    <Label>Select Fee Heads to Apply Discount</Label>
                    <div
                        v-for="item in feeStructure.items"
                        :key="item.id"
                        class="flex flex-wrap gap-2 items-center justify-between rounded-lg border border-border p-3"
                    >
                        <div class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                :id="'discount_' + item.fee_head_id"
                                v-model="discountSelectedFeeHeads"
                                :value="item.fee_head_id"
                                class="h-4 w-4 rounded border-border text-primary focus:ring-primary"
                            />
                            <div>
                                <Label :for="'discount_' + item.fee_head_id" class="cursor-pointer font-medium">
                                    {{ item.fee_head }}
                                </Label>
                                <p class="text-xs text-muted-foreground">
                                    Original: Rs. {{ item.amount?.toLocaleString() }} | {{ item.frequency }}
                                    <span v-if="item.is_optional" class="text-warning">(Optional)</span>
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium">Rs. {{ item.amount?.toLocaleString() }}</p>
                            <p class="text-xs text-muted-foreground">{{ item.frequency }}</p>
                        </div>
                    </div>
                </div>

                <!-- Applied Discounts Summary -->
                <div v-if="appliedDiscounts.length > 0" class="mt-4 rounded-lg bg-success/10 p-4">
                    <p class="mb-2 font-medium text-success">
                        Applied Discounts:
                    </p>
                    <ul class="space-y-1">
                        <li v-for="(discount, idx) in appliedDiscounts" :key="idx" class="text-sm text-success">
                            • {{ discount.fee_head }}: {{ discount.value }}{{ discount.value_type === 'percent' ? '%' : ' Rs.' }} off
                        </li>
                    </ul>
                    <p class="mt-2 text-sm font-semibold text-success">
                        Monthly: Rs. {{ discountPreview.originalMonthly.toLocaleString() }} - Rs. {{ discountPreview.monthlyOff.toLocaleString() }} discount
                        = Rs. {{ (discountPreview.originalMonthly - discountPreview.monthlyOff).toLocaleString() }}
                    </p>
                </div>
            </div>

            <!-- OPTION C: Manual Entry -->
            <div v-else-if="feeMode === 'manual'">
                <p class="mb-4 text-sm text-muted-foreground">
                    Every mandatory fee head from this class's structure is ticked with its default amount —
                    edit the amount for any student-specific adjustment. Tick an optional fee head to include it.
                </p>

                <!-- Manual Fee Entries -->
                <div class="space-y-3">
                    <Label>Select Fee Heads and Enter Custom Amounts</Label>
                    <div
                        v-for="item in feeStructure.items"
                        :key="item.id"
                        class="rounded-lg border border-border p-4"
                        :class="{ 'border-destructive/40 bg-destructive/10': !item.is_optional && !item.is_transport_related && !manualFeeEntries.some(e => e.fee_head_id === item.fee_head_id) }"
                    >
                        <div class="flex items-start gap-3">
                            <div class="mt-1 flex h-5 items-center">
                                <input
                                    type="checkbox"
                                    :id="'manual_' + item.fee_head_id"
                                    v-model="manualSelectedFeeHeads"
                                    :value="item.fee_head_id"
                                    class="h-4 w-4 rounded border-border text-primary focus:ring-primary"
                                />
                            </div>
                            <div class="flex-1">
                                <div class="mb-2 flex flex-wrap gap-2 items-center justify-between">
                                    <div>
                                        <Label :for="'manual_' + item.fee_head_id" class="cursor-pointer font-medium">
                                            {{ item.fee_head }}
                                        </Label>
                                        <span v-if="!item.is_optional && !item.is_transport_related" class="ml-2 text-xs text-destructive">*Mandatory</span>
                                        <span v-else class="ml-2 text-xs text-warning">(Optional)</span>
                                        <span v-if="item.is_transport_related" class="ml-2 text-xs text-warning">(Transport - tick only if the student uses it)</span>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-muted-foreground">
                                            Original: Rs. {{ item.amount?.toLocaleString() }}
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Custom Amount Input (only shown when selected) -->
                                <div v-if="manualSelectedFeeHeads.includes(item.fee_head_id)" class="mt-2">
                                    <div class="flex items-center gap-4">
                                        <div class="flex-1">
                                            <Input
                                                :id="'custom_amount_' + item.fee_head_id"
                                                :model-value="localManualAmounts[item.fee_head_id] ?? String(manualFeeAmounts[item.fee_head_id] ?? '')"
                                                @update:model-value="(value: string | number) => onManualAmountInput(item.fee_head_id, String(value))"
                                                @blur="formatManualAmount(item.fee_head_id)"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                placeholder="Enter custom amount"
                                                class="w-full"
                                            />
                                        </div>
                                        <div class="w-32 text-sm text-muted-foreground">
                                            <span v-if="(manualFeeAmounts[item.fee_head_id] || 0) < item.amount" class="text-success">
                                                {{ calculateDiscountPercentage(item.amount, manualFeeAmounts[item.fee_head_id] || 0) }}% off
                                            </span>
                                            <span v-else-if="(manualFeeAmounts[item.fee_head_id] || 0) > item.amount" class="text-destructive">
                                                +{{ calculateDiscountPercentage(item.amount, manualFeeAmounts[item.fee_head_id] || 0) }}% more
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Manual Entry Reason -->
                <div class="mt-4 space-y-2">
                    <Label for="manual_reason">Reason for Manual Entry</Label>
                    <textarea
                        id="manual_reason"
                        v-model="manualReason"
                        rows="2"
                        class="w-full rounded-md border border-border bg-card px-3 py-2 text-foreground"
                        placeholder="Explain why manual entry is being used..."
                    ></textarea>
                    <p v-if="manualReasonRequired && !manualReason.trim()" class="text-sm text-destructive">Please write the reason.</p>
                </div>

                <!-- Manual Entry Summary -->
                <div v-if="manualFeeEntries.length > 0" class="mt-4 rounded-lg bg-primary/10 p-4">
                    <p class="mb-2 font-medium text-primary">
                        Custom Fee Entries:
                    </p>
                    <ul class="space-y-1">
                        <li v-for="(entry, idx) in manualFeeEntries" :key="idx" class="text-sm text-primary">
                            • {{ entry.fee_head }}: Rs. {{ entry.amount?.toLocaleString() }}
                            <span v-if="entry.discount_percentage" class="text-success">({{ entry.discount_percentage }}% {{ entry.discount_percentage > 0 ? 'discount' : 'adjustment' }})</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, watch } from 'vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
import { useFeeStructure } from '@/composables/useFeeStructure';

interface Props {
    classId?: number | string;
    sessionId?: number | string;
    campusId?: number | string;
    sectionId?: number | string;
    enrollment?: {
        fee_structure_id?: number | null;
        fee_mode?: string | null;
        discounts?: Array<{
            fee_head_id: number;
            discount_type_id: number;
            value: number;
            value_type: string;
            reason?: string | null;
        }> | null;
        custom_fee_entries?: Array<{ fee_head_id: number; amount: number }> | null;
        manual_discount_percentage?: number | null;
        manual_discount_reason?: string | null;
    } | null;
}

const props = defineProps<Props>();

/**
 * Fired right before navigating away to the fee structure create screen, so
 * the parent admission form can snapshot its own in-progress data (see #65)
 * before the page unloads.
 */
const emit = defineEmits<{
    (e: 'before-create-fee-structure'): void;
}>();

// Use the fee structure composable
const {
    feeStructure,
    feeStructureLoading,
    feeMode,
    discountTypes,
    selectedDiscountType,
    discountValue,
    discountValueType,
    discountReason,
    discountSelectedFeeHeads,
    manualSelectedFeeHeads,
    manualFeeAmounts,
    manualReason,
    manualReasonRequired,
    structureCandidates,
    chosenStructureId,
    selectedDiscountTypeObj,
    appliedDiscounts,
    manualFeeEntries,
    setupFormWatchers,
    syncScopeRefs,
    fetchFeeStructure,
    fetchDiscountTypes,
    setManualFeeAmountDebounced,
    calculateDiscountPercentage,
    createFeeStructure,
    loadEnrollmentData,
    validateActiveMode,
    getSubmissionPayload,
} = useFeeStructure();

// Instant per-keystroke display, decoupled from the (debounced) reactive
// state that drives the discount/total recalculation, so typing never lags
// even though the recalculation itself is deferred (issue #67).
const discountTypeOptions = computed(() => discountTypes.value.map((dtype) => ({ value: dtype.id, label: dtype.name })));

const localManualAmounts = reactive<Record<number, string>>({});

function onManualAmountInput(feeHeadId: number, rawValue: string) {
    localManualAmounts[feeHeadId] = rawValue;
    setManualFeeAmountDebounced(feeHeadId, parseFloat(rawValue) || 0);
}

watch(feeStructure, () => {
    Object.keys(localManualAmounts).forEach((key) => delete localManualAmounts[Number(key)]);
});

/** Tidies the typed amount only once the user leaves the field. */
function formatManualAmount(feeHeadId: number) {
    const raw = localManualAmounts[feeHeadId];
    if (raw === undefined) {
        return;
    }
    const amount = parseFloat(raw) || 0;
    localManualAmounts[feeHeadId] = String(Math.round(amount * 100) / 100);
    setManualFeeAmountDebounced(feeHeadId, amount);
}

/**
 * Header totals follow what will really be charged: ticked fee heads at the
 * typed amount in Fee Heads mode, otherwise every compulsory head. Optional
 * heads that are not ticked are left out.
 */
const headerTotals = computed(() => {
    const totals = { monthly: 0, yearly: 0, once: 0 };
    for (const item of feeStructure.value?.items ?? []) {
        let amount: number;
        if (feeMode.value === 'manual') {
            if (!manualSelectedFeeHeads.value.includes(item.fee_head_id)) {
                continue;
            }
            amount = Number(manualFeeAmounts.value[item.fee_head_id] ?? item.amount) || 0;
        } else {
            if (item.is_optional || item.is_transport_related) {
                continue;
            }
            amount = Number(item.amount) || 0;
        }
        if (item.frequency === 'monthly') {
            totals.monthly += amount;
        } else if (item.frequency === 'yearly') {
            totals.yearly += amount;
        } else if (item.frequency === 'once') {
            totals.once += amount;
        }
    }
    return totals;
});

// Initialize on mount
onMounted(async () => {
    syncScopeRefs({
        classId: props.classId,
        sessionId: props.sessionId,
        campusId: props.campusId,
        sectionId: props.sectionId,
    });

    // Setup watchers for form changes FIRST
    setupFormWatchers();
    
    await fetchDiscountTypes();
    
    // Load enrollment data if provided (edit mode)
    if (props.enrollment && props.enrollment.fee_structure_id) {
        loadEnrollmentData(props.enrollment);
    } else if (props.classId && props.sessionId && props.campusId) {
        // Also fetch if all required props are available (create mode)
        await fetchFeeStructure();
    }
});

// Watch for prop changes and update the composable state
watch(
    () => [props.classId, props.sessionId, props.campusId, props.sectionId],
    async ([newClassId, newSessionId, newCampusId, newSectionId]) => {
        syncScopeRefs({
            classId: newClassId,
            sessionId: newSessionId,
            campusId: newCampusId,
            sectionId: newSectionId,
        });

        if (newClassId && newSessionId && newCampusId) {
            await fetchFeeStructure();
        }
    }
);

function handleCreateFeeStructure() {
    emit('before-create-fee-structure');
    createFeeStructure();
}

/** Monthly fee before and discount from the ticked heads, for the preview. */
const discountPreview = computed(() => {
    let originalMonthly = 0;
    let monthlyOff = 0;
    for (const item of feeStructure.value?.items ?? []) {
        if (item.frequency !== 'monthly' || item.is_optional || item.is_transport_related) {
            continue;
        }
        const amount = Number(item.amount) || 0;
        originalMonthly += amount;
        if (discountSelectedFeeHeads.value.includes(item.fee_head_id) && selectedDiscountType.value) {
            const value = Number(discountValue.value) || 0;
            monthlyOff += Math.min(amount, discountValueType.value === 'percent' ? (amount * Math.min(100, value)) / 100 : value);
        }
    }
    return { originalMonthly, monthlyOff: Math.round(monthlyOff * 100) / 100 };
});

// Handle discount type change - suggest the type's kind and value (still editable)
const onDiscountTypeChange = () => {
    if (selectedDiscountTypeObj.value) {
        discountValueType.value = selectedDiscountTypeObj.value.value_type === 'fixed' ? 'fixed' : 'percent';
        discountValue.value = selectedDiscountTypeObj.value.default_value;
    } else {
        discountValue.value = 0;
    }
};

watch(selectedDiscountType, () => {
    const saved = props.enrollment?.discounts?.[0];
    if (saved && saved.discount_type_id === selectedDiscountType.value && Number(saved.value) === Number(discountValue.value)) {
        return;
    }
    onDiscountTypeChange();
});

// Expose fee data for parent component access
defineExpose({
    feeStructure,
    feeMode,
    appliedDiscounts,
    manualFeeEntries,
    manualFeeAmounts,
    manualReason,
    validateActiveMode,
    getSubmissionPayload,
    onDiscountTypeChange,
});
</script>
