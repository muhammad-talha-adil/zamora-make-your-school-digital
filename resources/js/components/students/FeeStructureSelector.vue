<template>
    <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
            <Icon icon="credit-card" class="h-5 w-5 text-primary" />
            Tuition Fees
        </h2>

        <!-- No Class Selected Yet -->
        <div v-if="!classId || !sessionId || !campusId" class="text-center py-8 text-gray-500">
            <Icon icon="info" class="mx-auto mb-2 h-12 w-12 text-gray-400" />
            <p>Please select Campus, Session, and Class to view fee structure</p>
        </div>

        <!-- Fee Structure Loading -->
        <div v-else-if="feeStructureLoading" class="text-center py-8">
            <Icon icon="loader" class="mx-auto h-8 w-8 animate-spin text-primary" />
            <p class="mt-2 text-gray-500">Loading fee structure...</p>
        </div>

        <!-- No Fee Structure Found -->
        <div v-else-if="!feeStructure" class="text-center py-8">
            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20">
                <Icon icon="alert-triangle" class="mx-auto mb-2 h-8 w-8 text-yellow-600" />
                <p class="font-medium text-yellow-800 dark:text-yellow-100">No Fee Structure Found</p>
                <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                    No active fee structure exists for this class. Please create a fee structure first.
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
            <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-4">
                    <button
                        type="button"
                        @click="feeMode = 'structure'"
                        :class="[
                            feeMode === 'structure'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300',
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
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300',
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
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300',
                            'whitespace-nowrap border-b-2 px-1 py-2 text-sm font-medium transition-colors'
                        ]"
                    >
                        <Icon icon="edit" class="mr-1 inline h-4 w-4" />
                        Manual Entry
                    </button>
                </nav>
            </div>

            <!-- Fee Structure Summary -->
            <div class="mb-4 rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-blue-900 dark:text-blue-100">
                            {{ feeStructure.title }}
                        </p>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            Monthly: <span class="font-semibold">Rs. {{ feeStructure.monthly_fee?.toLocaleString() }}</span> | 
                            Annual: <span class="font-semibold">Rs. {{ feeStructure.annual_fee?.toLocaleString() }}</span>
                        </p>
                    </div>
                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                        Active
                    </span>
                </div>
            </div>

            <!-- OPTION A: Use Fee Structure As-Is -->
            <div v-if="feeMode === 'structure'">
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    The student will be charged according to the fee structure above. No custom fees or discounts applied.
                </p>
            </div>

            <!-- OPTION B: Apply Discount -->
            <div v-else-if="feeMode === 'discount'">
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
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
                            class="h-11 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
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
                                class="bg-gray-100 dark:bg-gray-700"
                            />
                            <span class="text-sm text-gray-500 whitespace-nowrap">
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
                        class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                    >
                        <div class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                :id="'discount_' + item.fee_head_id"
                                v-model="manualSelectedFeeHeads"
                                :value="item.fee_head_id"
                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                            />
                            <div>
                                <Label :for="'discount_' + item.fee_head_id" class="cursor-pointer font-medium">
                                    {{ item.fee_head }}
                                </Label>
                                <p class="text-xs text-gray-500">
                                    Original: Rs. {{ item.amount?.toLocaleString() }} | {{ item.frequency }}
                                    <span v-if="item.is_optional" class="text-orange-600">(Optional)</span>
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium">Rs. {{ item.amount?.toLocaleString() }}</p>
                            <p class="text-xs text-gray-500">{{ item.frequency }}</p>
                        </div>
                    </div>
                </div>

                <!-- Applied Discounts Summary -->
                <div v-if="appliedDiscounts.length > 0" class="mt-4 rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                    <p class="mb-2 font-medium text-green-800 dark:text-green-100">
                        Applied Discounts:
                    </p>
                    <ul class="space-y-1">
                        <li v-for="(discount, idx) in appliedDiscounts" :key="idx" class="text-sm text-green-700 dark:text-green-300">
                            • {{ discount.fee_head }}: {{ discount.value }}{{ discount.value_type === 'percent' ? '%' : ' Rs.' }} off
                        </li>
                    </ul>
                </div>
            </div>

            <!-- OPTION C: Manual Entry -->
            <div v-else-if="feeMode === 'manual'">
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    Enter custom fee amounts for each fee head. You must select fee heads from the structure.
                    For mandatory fee heads, selection is required. Optional fee heads can be skipped.
                </p>

                <!-- Manual Fee Entries -->
                <div class="space-y-3">
                    <Label>Select Fee Heads and Enter Custom Amounts</Label>
                    <div
                        v-for="item in feeStructure.items"
                        :key="item.id"
                        class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                        :class="{ 'border-red-300 bg-red-50 dark:bg-red-900/10': !item.is_optional && !manualFeeEntries.some(e => e.fee_head_id === item.fee_head_id) }"
                    >
                        <div class="flex items-start gap-3">
                            <div class="mt-1 flex h-5 items-center">
                                <input
                                    type="checkbox"
                                    :id="'manual_' + item.fee_head_id"
                                    v-model="manualSelectedFeeHeads"
                                    :value="item.fee_head_id"
                                    class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                />
                            </div>
                            <div class="flex-1">
                                <div class="mb-2 flex items-center justify-between">
                                    <div>
                                        <Label :for="'manual_' + item.fee_head_id" class="cursor-pointer font-medium">
                                            {{ item.fee_head }}
                                        </Label>
                                        <span v-if="!item.is_optional" class="ml-2 text-xs text-red-600">*Mandatory</span>
                                        <span v-else class="ml-2 text-xs text-orange-600">(Optional)</span>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-500">
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
                                        <div class="w-32 text-sm text-gray-500">
                                            <span v-if="(manualFeeAmounts[item.fee_head_id] || 0) < item.amount" class="text-green-600">
                                                {{ calculateDiscountPercentage(item.amount, manualFeeAmounts[item.fee_head_id] || 0) }}% off
                                            </span>
                                            <span v-else-if="(manualFeeAmounts[item.fee_head_id] || 0) > item.amount" class="text-red-600">
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
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        placeholder="Explain why manual entry is being used..."
                    ></textarea>
                </div>

                <!-- Manual Entry Summary -->
                <div v-if="manualFeeEntries.length > 0" class="mt-4 rounded-lg bg-purple-50 p-4 dark:bg-purple-900/20">
                    <p class="mb-2 font-medium text-purple-800 dark:text-purple-100">
                        Custom Fee Entries:
                    </p>
                    <ul class="space-y-1">
                        <li v-for="(entry, idx) in manualFeeEntries" :key="idx" class="text-sm text-purple-700 dark:text-purple-300">
                            • {{ entry.fee_head }}: Rs. {{ entry.amount?.toLocaleString() }}
                            <span v-if="entry.discount_percentage" class="text-green-600">({{ entry.discount_percentage }}% {{ entry.discount_percentage > 0 ? 'discount' : 'adjustment' }})</span>
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
    manualSelectedFeeHeads,
    manualFeeAmounts,
    manualReason,
    selectedDiscountTypeObj,
    appliedDiscounts,
    manualFeeEntries,
    formClassId,
    formSessionId,
    formCampusId,
    formSectionId,
    setupFormWatchers,
    fetchFeeStructure,
    fetchDiscountTypes,
    setManualFeeAmount,
    calculateDiscountPercentage,
    createFeeStructure,
    loadEnrollmentData,
} = useFeeStructure();

// Initialize on mount
onMounted(async () => {
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
    ([newClassId, newSessionId, newCampusId, newSectionId]) => {
        // Update the composable refs directly
        formClassId.value = newClassId ?? '';
        formSessionId.value = newSessionId ?? '';
        formCampusId.value = newCampusId ?? '';
        formSectionId.value = newSectionId ?? '';
        
        // The watcher in setupFormWatchers will trigger fetchFeeStructure
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
    onDiscountTypeChange,
});
</script>
