<template>
    <div class="rounded-lg border border-border bg-card p-6">
        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
            <Icon icon="credit-card" class="h-5 w-5 text-primary" />
            Fee Structure
        </h2>

        <!-- No Class Selected Yet -->
        <div v-if="!classId || !sessionId || !campusId" class="text-center py-8 text-muted-foreground">
            <Icon icon="info" class="mx-auto mb-2 h-12 w-12 text-muted-foreground" />
            <p>Please select Branch, Session, Class, and Section if required to view the fee structure.</p>
        </div>

        <!-- Fee Structure Loading -->
        <div v-else-if="feeStructureLoading" class="text-center py-8">
            <Icon icon="loader" class="mx-auto h-8 w-8 animate-spin text-primary" />
            <p class="mt-2 text-muted-foreground">Loading fee structure...</p>
        </div>

        <!-- No Fee Structure Found -->
        <div v-else-if="!feeStructure" class="text-center py-8">
            <div class="rounded-lg border border-warning/40 bg-warning/10 p-4">
                <Icon icon="alert-triangle" class="mx-auto mb-2 h-8 w-8 text-warning" />
                <p class="font-medium text-warning">No Fee Structure Found</p>
                <p class="mt-1 text-sm text-warning">
                    No active fee structure exists for the selected branch, session, class, and section combination.
                    Please create the matching fee structure first.
                </p>
                <Button type="button" variant="outline" size="sm" class="mt-3" @click="createFeeStructure">
                    <Icon icon="plus" class="mr-1 h-4 w-4" />
                    Create Fee Structure
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
                        Manual Entry
                    </button>
                </nav>
            </div>

            <!-- Fee Structure Summary -->
            <div class="mb-4 rounded-lg bg-primary/10 p-4">
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <div>
                        <p class="font-medium text-primary">
                            {{ feeStructure.title }}
                        </p>
                        <p class="text-sm text-primary">
                            Monthly: <span class="font-semibold">Rs. {{ feeStructure.monthly_fee?.toLocaleString() }}</span> | 
                            Annual: <span class="font-semibold">Rs. {{ feeStructure.annual_fee?.toLocaleString() }}</span>
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
                        <select
                            id="discount_type"
                            v-model="selectedDiscountType"
                            @change="onDiscountTypeChange"
                            class="h-11 w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground"
                        >
                            <option value="">Select Discount Type</option>
                            <option
                                v-for="dtype in discountTypes"
                                :key="dtype.id"
                                :value="dtype.id"
                            >
                                {{ dtype.name }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="discount_value">Discount Value</Label>
                        <div class="flex items-center gap-2">
                            <Input
                                id="discount_value"
                                v-model.number="discountValue"
                                type="number"
                                min="0"
                                :max="selectedDiscountTypeObj?.value_type === 'percent' ? 100 : undefined"
                                step="0.01"
                                placeholder="0.00"
                                readonly
                                class="bg-muted"
                            />
                            <span class="text-sm text-muted-foreground whitespace-nowrap">
                                {{ selectedDiscountTypeObj?.value_type === 'percent' ? '%' : 'Rs.' }}
                            </span>
                        </div>
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
                </div>
            </div>

            <!-- OPTION C: Manual Entry -->
            <div v-else-if="feeMode === 'manual'">
                <p class="mb-4 text-sm text-muted-foreground">
                    Enter custom fee amounts for each fee head. You must select fee heads from the structure.
                    For mandatory fee heads, selection is required. Optional fee heads can be skipped.
                </p>

                <!-- Manual Fee Entries -->
                <div class="space-y-3">
                    <Label>Select Fee Heads and Enter Custom Amounts</Label>
                    <div
                        v-for="item in feeStructure.items"
                        :key="item.id"
                        class="rounded-lg border border-border p-4"
                        :class="{ 'border-destructive/40 bg-destructive/10': !item.is_optional && !manualFeeEntries.some(e => e.fee_head_id === item.fee_head_id) }"
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
                                        <span v-if="!item.is_optional" class="ml-2 text-xs text-destructive">*Mandatory</span>
                                        <span v-else class="ml-2 text-xs text-warning">(Optional)</span>
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
                                                :value="manualFeeAmounts[item.fee_head_id] || ''"
                                                @input="(e: any) => setManualFeeAmount(item.fee_head_id, parseFloat(e.target.value) || 0)"
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
import { onMounted, watch } from 'vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
        }> | null;
        custom_fee_entries?: Array<{ fee_head_id: number; amount: number }> | null;
        manual_discount_percentage?: number | null;
        manual_discount_reason?: string | null;
    } | null;
}

const props = defineProps<Props>();

// Use the fee structure composable
const {
    feeStructure,
    feeStructureLoading,
    feeMode,
    discountTypes,
    selectedDiscountType,
    discountValue,
    discountSelectedFeeHeads,
    manualSelectedFeeHeads,
    manualFeeAmounts,
    manualReason,
    selectedDiscountTypeObj,
    appliedDiscounts,
    manualFeeEntries,
    setupFormWatchers,
    syncScopeRefs,
    fetchFeeStructure,
    fetchDiscountTypes,
    setManualFeeAmount,
    calculateDiscountPercentage,
    createFeeStructure,
    loadEnrollmentData,
    validateActiveMode,
    getSubmissionPayload,
} = useFeeStructure();

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

// Handle discount type change - auto-populate the value
const onDiscountTypeChange = () => {
    if (selectedDiscountTypeObj.value) {
        discountValue.value = selectedDiscountTypeObj.value.default_value;
    } else {
        discountValue.value = 0;
    }
};

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
