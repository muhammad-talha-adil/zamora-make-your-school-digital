<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { alert, formatCurrency } from '@/utils';
import axios from 'axios';
import { ref, computed, reactive, onMounted } from 'vue';

// Components
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';
import ComboboxInput from '@/components/ui/combobox/ComboboxInput.vue';
import type { BreadcrumbItem } from '@/types';

interface StudentInventoryItem {
    id: number;
    inventory_item_id: number;
    item_name_snapshot: string;
    description_snapshot: string;
    unit_price_snapshot: number;
    discount_amount: number;
    discount_percentage: number;
    quantity: number;
    returned_quantity: number;
    remaining_quantity: number;
    total_value: number;
}

interface StudentInventory {
    id: number;
    campus_id: number;
    campus_name: string;
    student_id: number;
    student_name: string;
    registration_number: string;
    class_name: string;
    section_name: string;
    total_amount: number;
    total_discount: number;
    final_amount: number;
    assigned_date: string;
    status: string;
    items: StudentInventoryItem[];
}

interface Reason {
    id: number;
    name: string;
}

interface Props {
    studentInventory: StudentInventory;
}

const props = defineProps<Props>();

// Breadcrumb - computed to avoid accessing props before they're available
const breadcrumbItems = computed((): BreadcrumbItem[] => [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory', href: '/inventory' },
    { title: 'Student Inventory', href: '/inventory/student-manage' },
    { title: 'View Assignment', href: `/inventory/student-inventory/${props.studentInventory.id}/view` },
    { title: 'Process Return', href: '#' },
]);

// Form data - initialized in onMounted to avoid accessing props before they're available
const form = reactive({
    student_inventory_record_id: 0,
    campus_id: 0,
    items: [] as Array<{
        student_inventory_item_id: number;
        selected: boolean;
        quantity: number;
        reason_id: number | null;
        custom_reason: string;
        return_price: number | null;
    }>,
    return_date: new Date().toISOString().split('T')[0],
    note: '',
});

// Reasons data
const reasonsData = ref<Reason[]>([]);
const otherReasonId = ref<number | null>(null);

// Initialize form with items that have remaining_quantity > 0
const initializeFormItems = () => {
    const itemsWithRemaining = props.studentInventory.items
        .filter(item => item.remaining_quantity > 0)
        .map(item => ({
            student_inventory_item_id: item.id,
            selected: false,
            quantity: 0,
            reason_id: null,
            custom_reason: '',
            return_price: item.unit_price_snapshot || null
        }));
    form.items = itemsWithRemaining;
};

// Fetch reasons from API
const fetchReasons = async () => {
    try {
        const response = await axios.get('/inventory/purchase-returns/reasons');
        reasonsData.value = response.data;
        
        const otherReason = reasonsData.value.find(r => r.name === 'Other');
        if (otherReason) {
            otherReasonId.value = otherReason.id;
        }
    } catch (err) {
        console.error('Failed to fetch reasons:', err);
        reasonsData.value = [];
    }
};

// Check if item reason is "Other"
const isOtherReason = (reasonId: number | null | undefined): boolean => {
    return reasonId === otherReasonId.value;
};

const errors = ref<Record<string, string>>({});
const processing = ref(false);

// Computed
const selectedItems = computed(() => {
    return form.items.filter(item => item.selected && item.quantity > 0);
});

// Checkbox states
const allSelected = computed(() => form.items.length > 0 && form.items.every(i => i.selected));
const someSelected = computed(() => form.items.some(i => i.selected) && !allSelected.value);

// Toggle select all
const toggleSelectAll = (checked: boolean) => {
    form.items.forEach(i => {
        i.selected = checked;
        if (!checked) {
            i.quantity = 0;
        }
    });
};


const totalRefund = computed(() => {
    let total = 0;
    for (const selected of selectedItems.value) {
        const item = props.studentInventory.items.find(i => i.id === selected.student_inventory_item_id);
        if (item) {
            const finalPrice = getFinalPrice(item, selected);
            total += finalPrice * selected.quantity;
        }
    }
    return total.toFixed(2);
});

const getFinalPrice = (item: StudentInventoryItem, formItem?: { return_price: number | null }): number => {
    let price = item.unit_price_snapshot || 0;
    
    // If return price is specified in form, use that
    if (formItem?.return_price !== null && formItem?.return_price !== undefined && formItem.return_price > 0) {
        return formItem.return_price;
    }
    
    if (item.discount_percentage && item.discount_percentage > 0) {
        price = price - (price * (item.discount_percentage / 100));
    } else if (item.discount_amount && item.discount_amount > 0) {
        price = price - item.discount_amount;
    }
    return price;
};

// Helper method to get quantity for an item
const getItemQuantity = (itemId: number): number => {
    const item = form.items.find(f => f.student_inventory_item_id === itemId);
    return item?.quantity ?? 0;
};

// Helper method to update quantity for an item
const updateItemQuantity = (itemId: number, value: number) => {
    const item = form.items.find(f => f.student_inventory_item_id === itemId);
    if (item) {
        item.quantity = value;
    }
};

// Helper method to toggle selection
const toggleItemSelection = (itemId: number) => {
    const item = form.items.find(f => f.student_inventory_item_id === itemId);
    if (item) {
        item.selected = !item.selected;
        if (!item.selected) {
            item.quantity = 0;
        }
    }
};


// Methods
const goBack = () => {
    router.visit(`/inventory/student-inventory/${props.studentInventory.id}/view`);
};

const submitForm = () => {
    // Filter to only items with quantity > 0
    const itemsToReturn = selectedItems.value;
    
    if (itemsToReturn.length === 0) {
        alert.error('Please select at least one item to return.');
        return;
    }

    processing.value = true;
    errors.value = {};

    const itemsData = form.items
        .filter(item => item.selected && item.quantity > 0)
        .map(item => ({
            student_inventory_item_id: item.student_inventory_item_id,
            quantity: item.quantity,
            return_price: item.return_price,
            reason_id: item.reason_id === otherReasonId.value ? null : item.reason_id,
            custom_reason: item.reason_id === otherReasonId.value ? item.custom_reason : null,
        }));

    axios.post('/inventory/student-inventory/return', {
        student_inventory_record_id: form.student_inventory_record_id,
        campus_id: form.campus_id,
        items: itemsData,
        return_date: form.return_date,
        note: form.note,
    }, {
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(() => {
        alert.success('Return processed successfully!');
        goBack();
    })
    .catch((err) => {
        const response = err.response;
        if (response?.data?.message) {
            alert.error(response.data.message);
        } else if (response?.data?.errors) {
            errors.value = response.data.errors;
            const firstError = Object.values(response.data.errors)[0];
            alert.error(Array.isArray(firstError) ? firstError[0] : firstError);
        } else {
            alert.error('Failed to process return. Please check the errors.');
        }
    })
    .finally(() => {
        processing.value = false;
    });
};

// Initialize items with quantities
onMounted(async () => {
    // Set form values from props
    form.student_inventory_record_id = props.studentInventory.id;
    form.campus_id = props.studentInventory.campus_id;
    
    // Fetch reasons
    await fetchReasons();
    
    initializeFormItems();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Process Return" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Process Return
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Return inventory items from student assignment
                    </p>
                </div>
                <Button variant="outline" @click="goBack" class="w-full sm:w-auto">
                    <Icon icon="arrow-left" class="mr-2 h-4 w-4" />
                    Back to Details
                </Button>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Student Info Card -->
                <div class="bg-card rounded-lg border p-4 md:p-5 space-y-4">
                    <h2 class="text-lg font-semibold">Student Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Student Name</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ props.studentInventory.student_name }}
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Registration #</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ props.studentInventory.registration_number }}
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Class - Section</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ props.studentInventory.class_name }} - {{ props.studentInventory.section_name }}
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Campus</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ props.studentInventory.campus_name }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Items Selection Card -->
                <div class="bg-card rounded-lg border p-4 md:p-5 space-y-4">
                    <h2 class="text-lg font-semibold">Select Items to Return</h2>

                    <div class="border rounded-lg overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 whitespace-nowrap">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-10">
                                        <input 
                                            type="checkbox" 
                                            :checked="allSelected"
                                            :indeterminate="someSelected"
                                            @change="toggleSelectAll(($event.target as HTMLInputElement).checked)"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                    </th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Unit Price</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Qty Assigned</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Already Returned</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Remaining</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Return Qty</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Return Price</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reason</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Refund</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="item in props.studentInventory.items.filter(i => i.remaining_quantity > 0)" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-3 py-4">
                                        <input 
                                            type="checkbox" 
                                            :checked="form.items.find(f => f.student_inventory_item_id === item.id)?.selected || false"
                                            @change="toggleItemSelection(item.id)"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white break-words max-w-[200px]">
                                            {{ item.item_name_snapshot }}
                                        </div>
                                        <div class="text-xs text-gray-500 break-words max-w-[200px]" v-if="item.description_snapshot">
                                            {{ item.description_snapshot }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ formatCurrency(item.unit_price_snapshot) }}
                                    </td>
                                    <td class="px-3 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ item.quantity }}
                                    </td>
                                    <td class="px-3 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ item.returned_quantity }}
                                    </td>
                                    <td class="px-3 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ item.remaining_quantity }}
                                    </td>
                                    <td class="px-3 py-4">
                                        <Input
                                            :model-value="getItemQuantity(item.id)"
                                            @update:model-value="(val) => updateItemQuantity(item.id, Number(val))"
                                            type="number"
                                            min="0"
                                            :max="item.remaining_quantity"
                                            :disabled="!form.items.find(f => f.student_inventory_item_id === item.id)?.selected"
                                            class="w-20 h-9"
                                        />
                                    </td>
                                    <td class="px-3 py-4">
                                        <Input
                                            :model-value="form.items.find(f => f.student_inventory_item_id === item.id)?.return_price || ''"
                                            @update:model-value="(val) => {
                                                const formItem = form.items.find(f => f.student_inventory_item_id === item.id);
                                                if (formItem) formItem.return_price = val ? Number(val) : null;
                                            }"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="Price"
                                            :disabled="!form.items.find(f => f.student_inventory_item_id === item.id)?.selected"
                                            class="w-24 h-9 text-sm"
                                        />
                                    </td>
                                    <td class="px-3 py-4">
                                        <div v-if="isOtherReason(form.items.find(f => f.student_inventory_item_id === item.id)?.reason_id)">
                                            <Input
                                                :model-value="form.items.find(f => f.student_inventory_item_id === item.id)?.custom_reason || ''"
                                                @update:model-value="(val: string | number) => {
                                                    const formItem = form.items.find(f => f.student_inventory_item_id === item.id);
                                                    if (formItem) formItem.custom_reason = String(val);
                                                }"
                                                type="text"
                                                placeholder="Reason..."
                                                :disabled="!form.items.find(f => f.student_inventory_item_id === item.id)?.selected"
                                                class="w-32 h-9 text-sm"
                                            />
                                        </div>
                                        <div v-else>
                                            <ComboboxInput
                                                :model-value="form.items.find(f => f.student_inventory_item_id === item.id)?.reason_id"
                                                @update:model-value="(val: string | number | null) => {
                                                    const formItem = form.items.find(f => f.student_inventory_item_id === item.id);
                                                    if (formItem) formItem.reason_id = val !== null ? (typeof val === 'number' ? val : parseInt(String(val))) : null;
                                                }"
                                                :initial-items="reasonsData"
                                                placeholder="Reason"
                                                value-type="id"
                                                :disabled="!form.items.find(f => f.student_inventory_item_id === item.id)?.selected"
                                                class-min-width="min-w-[150px]"
                                            />
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ formatCurrency(getFinalPrice(item, form.items.find(f => f.student_inventory_item_id === item.id)) * getItemQuantity(item.id)) }}
                                    </td>
                                </tr>
                                <tr v-if="props.studentInventory.items.every(i => i.remaining_quantity === 0)">
                                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                        All items have been returned.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Return Details Card -->
                <div class="bg-card rounded-lg border p-4 md:p-5 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Return Date -->
                        <div class="space-y-2">
                            <Label for="return_date" class="flex items-center gap-2">
                                <Icon icon="calendar" class="h-4 w-4" />
                                Return Date <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                id="return_date"
                                v-model="form.return_date"
                                type="date"
                                class="h-11"
                            />
                            <InputError :message="errors.return_date" />
                        </div>

                        <!-- Total Refund (Read-only) -->
                        <div class="space-y-2">
                            <Label class="flex items-center gap-2">
                                <Icon icon="dollar-sign" class="h-4 w-4" />
                                Total Refund
                            </Label>
                            <div class="h-11 flex items-center px-3 bg-gray-100 dark:bg-gray-800 rounded-md">
                                <span class="text-xl font-bold text-green-600">
                                    {{ formatCurrency(totalRefund) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-2">
                        <Label for="note" class="flex items-center gap-2">
                            <Icon icon="align-left" class="h-4 w-4" />
                            Notes <span class="text-sm text-muted-foreground font-normal">(Optional)</span>
                        </Label>
                        <textarea
                            id="note"
                            v-model="form.note"
                            rows="3"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2"
                            placeholder="Reason for return or additional notes..."
                        ></textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row justify-end gap-3 pt-2">
                    <Button type="button" variant="outline" @click="goBack" class="w-full sm:w-auto h-10">
                        <Icon icon="x" class="mr-2 h-4 w-4" />
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing || selectedItems.length === 0" class="w-full sm:w-auto h-10">
                        <Icon v-if="processing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                        <Icon v-else icon="check" class="mr-2 h-4 w-4" />
                        Process Return
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
