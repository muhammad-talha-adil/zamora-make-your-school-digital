<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref, computed } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Icon from '@/components/Icon.vue';
import axios from 'axios';

interface FeeVoucherItem {
    id: number;
    fee_head_id: number;
    fee_head?: { id: number; name: string; code: string };
    description: string;
    quantity: number;
    unit_price: number;
    amount: number;
    discount_amount: number;
    net_amount: number;
}

interface FeeVoucherAdjustment {
    id: number;
    type: string;
    amount: number;
    description: string;
    created_at: string;
}

interface FeeVoucher {
    id: number;
    voucher_no: string;
    student_id: number;
    class_id: number;
    section_id: number;
    voucher_month_id: number;
    voucher_year: number;
    issue_date: string;
    due_date: string;
    status: string;
    gross_amount: number;
    discount_amount: number;
    fine_amount: number;
    paid_amount: number;
    net_amount: number;
    balance_amount: number;
    advance_adjusted_amount: number;
    notes: string;
    student?: { id: number; name: string; registration_number: string; father_name: string };
    voucherMonth?: { id: number; name: string };
    schoolClass?: { id: number; name: string };
    section?: { id: number; name: string };
    items: FeeVoucherItem[];
    adjustments: FeeVoucherAdjustment[];
}

interface FeeHead {
    id: number;
    name: string;
    code: string;
    category: string;
}

interface Props {
    voucher: FeeVoucher;
    feeHeads?: FeeHead[];
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Fee Vouchers', href: '/fee/vouchers' },
    { title: props.voucher.voucher_no, href: `/fee/vouchers/${props.voucher.id}` },
    { title: 'Edit', href: `/fee/vouchers/${props.voucher.id}/edit` },
];

const form = reactive({
    due_date: props.voucher.due_date || '',
    notes: props.voucher.notes || '',
});

const errors = ref<Record<string, string>>({});
const isSubmitting = ref(false);

// Fee head management
const showAddItemModal = ref(false);
const editingItemId = ref<number | null>(null);
const isLoadingFeeHeads = ref(false);
const availableFeeHeads = ref<FeeHead[]>([]);

const newItemForm = reactive({
    fee_head_id: '' as string | number,
    description: '',
    amount: 0,
    discount_amount: 0,
});

const editItemForm = reactive({
    description: '',
    amount: 0,
    discount_amount: 0,
});

// Check if voucher is paid
const isVoucherPaid = computed(() => {
    return props.voucher.status === 'paid';
});

// Get existing fee head IDs on this voucher
const existingFeeHeadIds = computed(() => {
    return props.voucher.items.map(item => item.fee_head_id);
});

// Filter out fee heads that are already on the voucher
const filteredFeeHeads = computed(() => {
    if (!props.feeHeads) return availableFeeHeads.value;
    return props.feeHeads.filter(fh => !existingFeeHeadIds.value.includes(fh.id));
});

const validateForm = () => {
    errors.value = {};
    
    return Object.keys(errors.value).length === 0;
};

const submitForm = () => {
    if (!validateForm()) return;
    
    isSubmitting.value = true;
    
    router.put(route('fee.vouchers.update', props.voucher.id), {
        due_date: form.due_date || null,
        notes: form.notes,
    }, {
        onSuccess: () => {
            isSubmitting.value = false;
        },
        onError: (err) => {
            isSubmitting.value = false;
            errors.value = err;
        },
    });
};

const cancel = () => {
    router.visit(route('fee.vouchers.show', props.voucher.id));
};

const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const getStatusColor = (status: string): string => {
    const colors: Record<string, string> = {
        unpaid: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        partial: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        paid: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        overdue: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

// Open add item modal
const openAddItemModal = async () => {
    showAddItemModal.value = true;
    newItemForm.fee_head_id = '';
    newItemForm.description = '';
    newItemForm.amount = 0;
    newItemForm.discount_amount = 0;
    
    // Load fee heads if not provided
    if (!props.feeHeads) {
        isLoadingFeeHeads.value = true;
        try {
            const response = await axios.get(route('fee.heads.all'));
            availableFeeHeads.value = response.data;
        } catch (error) {
            console.error('Error loading fee heads:', error);
        } finally {
            isLoadingFeeHeads.value = false;
        }
    }
};

// Close add item modal
const closeAddItemModal = () => {
    showAddItemModal.value = false;
};

// Add new item to voucher
const addItem = async () => {
    if (!newItemForm.fee_head_id || !newItemForm.amount) {
        return;
    }
    
    isSubmitting.value = true;
    
    try {
        await axios.post(route('fee.vouchers.add-item', props.voucher.id), {
            fee_head_id: newItemForm.fee_head_id,
            description: newItemForm.description || undefined,
            amount: newItemForm.amount,
            discount_amount: newItemForm.discount_amount || 0,
        });
        
        // Reload the page to get updated data
        router.reload({ only: ['voucher'] });
        closeAddItemModal();
    } catch (error: any) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            alert('Failed to add fee item');
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Start editing an item
const startEditItem = (item: FeeVoucherItem) => {
    editingItemId.value = item.id;
    editItemForm.description = item.description;
    editItemForm.amount = item.amount;
    editItemForm.discount_amount = item.discount_amount;
};

// Cancel editing
const cancelEditItem = () => {
    editingItemId.value = null;
};

// Save edited item
const saveEditItem = async (item: FeeVoucherItem) => {
    isSubmitting.value = true;
    
    try {
        await axios.put(route('fee.vouchers.update-item', { voucher: props.voucher.id, item: item.id }), {
            description: editItemForm.description || undefined,
            amount: editItemForm.amount,
            discount_amount: editItemForm.discount_amount || 0,
        });
        
        // Reload the page to get updated data
        router.reload({ only: ['voucher'] });
        cancelEditItem();
    } catch (error: any) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            alert('Failed to update fee item');
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Remove item from voucher
const removeItem = async (item: FeeVoucherItem) => {
    if (!confirm(`Are you sure you want to remove \"${item.fee_head?.name || 'this item'}\" from the voucher?`)) {
        return;
    }
    
    isSubmitting.value = true;
    
    try {
        await axios.delete(route('fee.vouchers.remove-item', { voucher: props.voucher.id, item: item.id }));
        
        // Reload the page to get updated data
        router.reload({ only: ['voucher'] });
    } catch {
        alert('Failed to remove fee item');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Edit Fee Voucher" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Edit Fee Voucher
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Voucher #{{ props.voucher.voucher_no }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="cancel">
                        <Icon icon="x" class="mr-2 h-4 w-4" />
                        Cancel
                    </Button>
                    <Button @click="submitForm" :disabled="isSubmitting">
                        <Icon icon="check" class="mr-2 h-4 w-4" />
                        {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </div>

            <!-- Voucher Info -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <!-- Student Info Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Student</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ props.voucher.student?.name || 'N/A' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ props.voucher.student?.registration_number }}
                    </p>
                </div>

                <!-- Month/Year Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Month</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ props.voucher.voucherMonth?.name }} {{ props.voucher.voucher_year }}
                    </p>
                </div>

                <!-- Status Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Status</p>
                    <span :class="['inline-flex items-center px-2 py-1 rounded-full text-xs font-medium', getStatusColor(props.voucher.status)]">
                        {{ props.voucher.status }}
                    </span>
                </div>

                <!-- Balance Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Balance</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ formatCurrency(props.voucher.balance_amount) }}
                    </p>
                </div>
            </div>

            <!-- Amount Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Amount Summary</h3>
                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-5">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Gross Amount</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ formatCurrency(props.voucher.gross_amount) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Discount</p>
                        <p class="text-sm font-medium text-green-600 dark:text-green-400">-{{ formatCurrency(props.voucher.discount_amount) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Fine</p>
                        <p class="text-sm font-medium text-red-600 dark:text-red-400">+{{ formatCurrency(props.voucher.fine_amount) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Paid</p>
                        <p class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ formatCurrency(props.voucher.paid_amount) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Net Amount</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ formatCurrency(props.voucher.net_amount) }}</p>
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Edit Voucher Details</h3>
                
                <form @submit.prevent="submitForm" class="space-y-4">
                    <!-- Due Date -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="due_date">Due Date</Label>
                            <Input
                                id="due_date"
                                v-model="form.due_date"
                                type="date"
                                :class="{ 'border-red-500': errors.due_date }"
                                :disabled="isVoucherPaid"
                            />
                            <p v-if="errors.due_date" class="text-sm text-red-500">{{ errors.due_date }}</p>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-2">
                        <Label for="notes">Notes</Label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                            placeholder="Additional notes..."
                            :disabled="isVoucherPaid"
                        ></textarea>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <Button type="button" variant="outline" @click="cancel">
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="isSubmitting || isVoucherPaid">
                            <Icon icon="check" class="mr-2 h-4 w-4" />
                            {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </div>
                </form>
            </div>

            <!-- Items -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Voucher Items</h3>
                    <Button 
                        v-if="!isVoucherPaid" 
                        size="sm" 
                        @click="openAddItemModal"
                    >
                        <Icon icon="plus" class="mr-2 h-4 w-4" />
                        Add Fee Head
                    </Button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 px-3 text-gray-500 dark:text-gray-400">Fee Head</th>
                                <th class="text-left py-2 px-3 text-gray-500 dark:text-gray-400">Description</th>
                                <th class="text-right py-2 px-3 text-gray-500 dark:text-gray-400">Qty</th>
                                <th class="text-right py-2 px-3 text-gray-500 dark:text-gray-400">Unit Price</th>
                                <th class="text-right py-2 px-3 text-gray-500 dark:text-gray-400">Amount</th>
                                <th class="text-right py-2 px-3 text-gray-500 dark:text-gray-400">Discount</th>
                                <th class="text-right py-2 px-3 text-gray-500 dark:text-gray-400">Net</th>
                                <th v-if="!isVoucherPaid" class="text-right py-2 px-3 text-gray-500 dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in props.voucher.items" :key="item.id" class="border-b border-gray-100 dark:border-gray-700">
                                <template v-if="editingItemId === item.id">
                                    <!-- Edit Mode -->
                                    <td class="py-2 px-3 text-gray-900 dark:text-white">{{ item.fee_head?.name || 'N/A' }}</td>
                                    <td class="py-2 px-3">
                                        <Input
                                            v-model="editItemForm.description"
                                            type="text"
                                            class="h-8 text-sm"
                                        />
                                    </td>
                                    <td class="py-2 px-3 text-right text-gray-600 dark:text-gray-300">{{ item.quantity }}</td>
                                    <td class="py-2 px-3 text-right text-gray-600 dark:text-gray-300">
                                        <Input
                                            v-model.number="editItemForm.amount"
                                            type="number"
                                            step="0.01"
                                            class="h-8 text-sm w-24 text-right"
                                        />
                                    </td>
                                    <td class="py-2 px-3 text-right text-gray-900 dark:text-white font-medium">{{ formatCurrency(item.amount) }}</td>
                                    <td class="py-2 px-3 text-right">
                                        <Input
                                            v-model.number="editItemForm.discount_amount"
                                            type="number"
                                            step="0.01"
                                            class="h-8 text-sm w-24 text-right"
                                        />
                                    </td>
                                    <td class="py-2 px-3 text-right text-gray-900 dark:text-white font-medium">{{ formatCurrency(item.net_amount) }}</td>
                                    <td class="py-2 px-3 text-right">
                                        <div class="flex justify-end gap-1">
                                            <Button size="sm" variant="ghost" @click="saveEditItem(item)">
                                                <Icon icon="check" class="h-4 w-4 text-green-600" />
                                            </Button>
                                            <Button size="sm" variant="ghost" @click="cancelEditItem">
                                                <Icon icon="x" class="h-4 w-4 text-red-600" />
                                            </Button>
                                        </div>
                                    </td>
                                </template>
                                <template v-else>
                                    <!-- Display Mode -->
                                    <td class="py-2 px-3 text-gray-900 dark:text-white">{{ item.fee_head?.name || 'N/A' }}</td>
                                    <td class="py-2 px-3 text-gray-600 dark:text-gray-300">{{ item.description }}</td>
                                    <td class="py-2 px-3 text-right text-gray-600 dark:text-gray-300">{{ item.quantity }}</td>
                                    <td class="py-2 px-3 text-right text-gray-600 dark:text-gray-300">{{ formatCurrency(item.unit_price) }}</td>
                                    <td class="py-2 px-3 text-right text-gray-900 dark:text-white font-medium">{{ formatCurrency(item.amount) }}</td>
                                    <td class="py-2 px-3 text-right text-green-600 dark:text-green-400">-{{ formatCurrency(item.discount_amount) }}</td>
                                    <td class="py-2 px-3 text-right text-gray-900 dark:text-white font-medium">{{ formatCurrency(item.net_amount) }}</td>
                                    <td v-if="!isVoucherPaid" class="py-2 px-3 text-right">
                                        <div class="flex justify-end gap-1">
                                            <Button size="sm" variant="ghost" @click="startEditItem(item)">
                                                <Icon icon="pencil" class="h-4 w-4" />
                                            </Button>
                                            <Button size="sm" variant="ghost" @click="removeItem(item)">
                                                <Icon icon="trash-2" class="h-4 w-4 text-red-600" />
                                            </Button>
                                        </div>
                                    </td>
                                </template>
                            </tr>
                            <tr v-if="props.voucher.items.length === 0">
                                <td :colspan="isVoucherPaid ? 7 : 8" class="py-4 text-center text-gray-500">
                                    No fee items found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Adjustments -->
            <div v-if="props.voucher.adjustments && props.voucher.adjustments.length > 0" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Adjustments</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 px-3 text-gray-500 dark:text-gray-400">Type</th>
                                <th class="text-left py-2 px-3 text-gray-500 dark:text-gray-400">Description</th>
                                <th class="text-right py-2 px-3 text-gray-500 dark:text-gray-400">Amount</th>
                                <th class="text-left py-2 px-3 text-gray-500 dark:text-gray-400">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="adj in props.voucher.adjustments" :key="adj.id" class="border-b border-gray-100 dark:border-gray-700">
                                <td class="py-2 px-3">
                                    <span :class="adj.type === 'debit' ? 'text-red-600' : 'text-green-600'">
                                        {{ adj.type }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-gray-600 dark:text-gray-300">{{ adj.description }}</td>
                                <td class="py-2 px-3 text-right" :class="adj.type === 'debit' ? 'text-red-600' : 'text-green-600'">
                                    {{ adj.type === 'debit' ? '+' : '-' }}{{ formatCurrency(adj.amount) }}
                                </td>
                                <td class="py-2 px-3 text-gray-600 dark:text-gray-300">{{ new Date(adj.created_at).toLocaleDateString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Item Modal -->
        <div v-if="showAddItemModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-md p-6 m-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add Fee Head</h3>
                    <Button variant="ghost" size="sm" @click="closeAddItemModal">
                        <Icon icon="x" class="h-5 w-5" />
                    </Button>
                </div>

                <form @submit.prevent="addItem" class="space-y-4">
                    <!-- Fee Head Selection -->
                    <div class="space-y-2">
                        <Label for="fee_head_id">Fee Head <span class="text-red-500">*</span></Label>
                        <select
                            id="fee_head_id"
                            v-model="newItemForm.fee_head_id"
                            class="h-11 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            :disabled="isLoadingFeeHeads"
                            required
                        >
                            <option value="">Select Fee Head</option>
                            <option
                                v-for="fh in filteredFeeHeads"
                                :key="fh.id"
                                :value="fh.id"
                            >
                                {{ fh.name }} ({{ fh.code }})
                            </option>
                        </select>
                        <p v-if="errors.fee_head_id" class="text-sm text-red-500">{{ errors.fee_head_id }}</p>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <Label for="description">Description</Label>
                        <Input
                            id="description"
                            v-model="newItemForm.description"
                            type="text"
                            placeholder="Optional description"
                        />
                    </div>

                    <!-- Amount -->
                    <div class="space-y-2">
                        <Label for="amount">Amount <span class="text-red-500">*</span></Label>
                        <Input
                            id="amount"
                            v-model.number="newItemForm.amount"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            required
                        />
                        <p v-if="errors.amount" class="text-sm text-red-500">{{ errors.amount }}</p>
                    </div>

                    <!-- Discount Amount -->
                    <div class="space-y-2">
                        <Label for="discount_amount">Discount Amount</Label>
                        <Input
                            id="discount_amount"
                            v-model.number="newItemForm.discount_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                        />
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4">
                        <Button type="button" variant="outline" @click="closeAddItemModal">
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="isSubmitting || !newItemForm.fee_head_id || !newItemForm.amount">
                            {{ isSubmitting ? 'Adding...' : 'Add Item' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
