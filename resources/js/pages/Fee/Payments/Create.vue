<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Icon from '@/components/Icon.vue';
import { alert, formatCurrency } from '@/utils';

interface StudentSearchResult {
    id: number;
    name: string;
    registration_number: string;
    admission_no?: string | null;
    matched_voucher_no?: string | null;
}

interface Voucher {
    id: number;
    voucher_no: string;
    voucher_month: { name: string } | null;
    voucher_year: number;
    net_amount: number;
    balance_amount: number;
    items: VoucherDueItem[];
}

interface VoucherDueItem {
    id: number;
    student_account_charge_id: number | null;
    fee_head_id: number | null;
    fee_head_name?: string | null;
    description: string;
    source_module: string;
    source_type: string;
    amount: number;
    net_amount: number;
    balance_amount: number;
    status: string;
}

interface ChargeAllocation {
    voucher_id: number;
    fee_voucher_item_id: number;
    student_account_charge_id: number | null;
    source_module: string;
    amount: number;
    enabled: boolean;
}

interface Props {
    unpaidVouchers: Voucher[];
    studentId: number | null;
    selectedStudent?: StudentSearchResult | null;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Payments', href: '/fee/payments' },
    { title: 'New Payment', href: '#' },
];

const paymentMethods = [
    { value: 'cash', label: 'Cash' },
    { value: 'bank', label: 'Bank Transfer' },
    { value: 'online', label: 'Online Payment' },
    { value: 'jazzcash', label: 'JazzCash' },
    { value: 'easypaisa', label: 'EasyPaisa' },
    { value: 'cheque', label: 'Cheque' },
];

const availableVouchers = ref<Voucher[]>(props.unpaidVouchers || []);
const searchQuery = ref(props.selectedStudent?.name || '');
const searchResults = ref<StudentSearchResult[]>([]);
const isSearching = ref(false);
const isLoadingVouchers = ref(false);
const isSubmitting = ref(false);
const selectedStudent = ref<StudentSearchResult | null>(props.selectedStudent || null);

const form = reactive({
    student_id: props.studentId || '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'cash',
    reference_no: '',
    bank_name: '',
    received_amount: 0,
    charges: [] as ChargeAllocation[],
    remarks: '',
});

const initializeVouchers = (vouchers: Voucher[]) => {
    availableVouchers.value = vouchers;
    form.charges = vouchers.flatMap((voucher) =>
        (voucher.items || []).map((item) => ({
            voucher_id: voucher.id,
            fee_voucher_item_id: item.id,
            student_account_charge_id: item.student_account_charge_id,
            source_module: item.source_module || 'fee',
            amount: Number(item.balance_amount),
            enabled: true,
        }))
    );
};

initializeVouchers(props.unpaidVouchers || []);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const searchStudents = () => {
    if (searchQuery.value.trim().length < 2) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;

    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = window.setTimeout(() => {
        axios.get(route('fee.payments.search-students'), {
            params: { q: searchQuery.value.trim() },
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then((response) => {
            searchResults.value = response.data || [];
        }).catch(() => {
            searchResults.value = [];
        }).finally(() => {
            isSearching.value = false;
        });
    }, 250);
};

const fetchUnpaidVouchers = (studentId: number) => {
    isLoadingVouchers.value = true;

    axios.get(route('fee.vouchers.unpaid'), {
        params: { student_id: studentId },
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    }).then((response) => {
        initializeVouchers(response.data || []);
    }).catch(() => {
        initializeVouchers([]);
        alert.error('Failed to load unpaid vouchers for the selected student.');
    }).finally(() => {
        isLoadingVouchers.value = false;
    });
};

const selectStudent = (student: StudentSearchResult) => {
    selectedStudent.value = student;
    form.student_id = student.id;
    searchQuery.value = student.name;
    searchResults.value = [];
    fetchUnpaidVouchers(student.id);
};

const getChargeAllocation = (voucherId: number, itemId: number) => {
    return form.charges.find((charge) => charge.voucher_id === voucherId && charge.fee_voucher_item_id === itemId);
};

const toggleCharge = (voucherId: number, item: VoucherDueItem) => {
    const allocation = getChargeAllocation(voucherId, item.id);

    if (!allocation) {
        return;
    }

    allocation.enabled = !allocation.enabled;
    allocation.amount = allocation.enabled ? Number(item.balance_amount) : 0;
};

const totalAllocated = computed(() => {
    return form.charges.reduce((sum, charge) => {
        if (!charge.enabled) {
            return sum;
        }

        return sum + Number(charge.amount || 0);
    }, 0);
});

const remainingAmount = computed(() => form.received_amount - totalAllocated.value);

watch(() => props.studentId, (newId) => {
    if (newId) {
        form.student_id = newId;
    }
});

watch(() => form.payment_method, (method) => {
    if (method === 'cash') {
        form.reference_no = '';
        form.bank_name = '';
    } else if (!['bank', 'cheque'].includes(method)) {
        form.bank_name = '';
    }
});

const submitForm = () => {
    if (!form.student_id) {
        alert.error('Please select a student first.');
        return;
    }

    const charges = form.charges
        .filter((charge) => charge.enabled && Number(charge.amount) > 0)
        .map((charge) => ({
            voucher_id: charge.voucher_id,
            fee_voucher_item_id: charge.fee_voucher_item_id,
            student_account_charge_id: charge.student_account_charge_id,
            source_module: charge.source_module,
            amount: Number(charge.amount),
        }));

    if (charges.length === 0) {
        alert.error('Please select at least one due item with a payment amount.');
        return;
    }

    if (totalAllocated.value > Number(form.received_amount)) {
        alert.error('Allocated voucher amount cannot be greater than the received amount.');
        return;
    }

    if (form.payment_method !== 'cash' && !form.reference_no.trim()) {
        alert.error('Reference number is required for non-cash payments.');
        return;
    }

    isSubmitting.value = true;

    router.post(route('fee.payments.store'), {
        ...form,
        reference_no: form.payment_method === 'cash' ? '' : form.reference_no,
        bank_name: ['bank', 'cheque'].includes(form.payment_method) ? form.bank_name : '',
        charges,
    }, {
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Record Payment" />

        <div class="space-y-6 p-4 md:p-6">
            <div>
                <h1 class="text-lg font-bold text-gray-900 dark:text-white md:text-2xl">
                    Record Fee Payment
                </h1>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 md:text-sm">
                    Record a new fee payment from a student
                </p>
            </div>

            <form @submit.prevent="submitForm" class="max-w-5xl space-y-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <Label for="student_search">Student or Voucher No *</Label>
                        <div class="relative">
                            <Input
                                id="student_search"
                                v-model="searchQuery"
                                @input="searchStudents"
                                placeholder="Search by student name, registration no, admission no, or voucher no..."
                                autocomplete="off"
                            />
                            <div v-if="isSearching" class="absolute right-3 top-3">
                                <Icon icon="loader" class="h-4 w-4 animate-spin text-gray-400" />
                            </div>
                            <div
                                v-if="searchResults.length > 0"
                                class="absolute z-10 mt-1 max-h-72 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                            >
                                <button
                                    v-for="student in searchResults"
                                    :key="student.id"
                                    type="button"
                                    @click="selectStudent(student)"
                                    class="w-full px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-gray-700"
                                >
                                    <div class="font-medium text-gray-900 dark:text-white">{{ student.name }}</div>
                                    <div class="text-sm text-gray-500">
                                        {{ student.registration_number }}
                                        <span v-if="student.admission_no"> | {{ student.admission_no }}</span>
                                    </div>
                                    <div v-if="student.matched_voucher_no" class="text-xs text-blue-600 dark:text-blue-400">
                                        Matched voucher: {{ student.matched_voucher_no }}
                                    </div>
                                </button>
                            </div>
                        </div>
                        <div v-if="selectedStudent" class="mt-2 rounded-md bg-blue-50 px-3 py-2 text-sm text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">
                            Selected: {{ selectedStudent.name }} ({{ selectedStudent.registration_number }})
                        </div>
                        <input v-model="form.student_id" type="hidden" required />
                    </div>

                    <div>
                        <Label for="payment_date">Payment Date *</Label>
                        <Input
                            id="payment_date"
                            v-model="form.payment_date"
                            type="date"
                            required
                        />
                    </div>

                    <div>
                        <Label for="payment_method">Payment Method *</Label>
                        <select
                            id="payment_method"
                            v-model="form.payment_method"
                            required
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option v-for="method in paymentMethods" :key="method.value" :value="method.value">
                                {{ method.label }}
                            </option>
                        </select>
                    </div>

                    <div v-if="form.payment_method !== 'cash'">
                        <Label for="reference_no">Reference No *</Label>
                        <Input
                            id="reference_no"
                            v-model="form.reference_no"
                            placeholder="Bank transaction ID, cheque number, etc."
                            required
                        />
                    </div>

                    <div v-if="['bank', 'cheque'].includes(form.payment_method)">
                        <Label for="bank_name">Bank Name *</Label>
                        <Input
                            id="bank_name"
                            v-model="form.bank_name"
                            placeholder="Bank name"
                            required
                        />
                    </div>

                    <div class="md:col-span-2">
                        <Label for="received_amount">Received Amount *</Label>
                        <Input
                            id="received_amount"
                            v-model="form.received_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                        />
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Voucher Dues</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Select exactly which fee or inventory dues you want to settle for this student.
                        </p>
                    </div>

                    <div v-if="isLoadingVouchers" class="py-10 text-center text-gray-500 dark:text-gray-400">
                        Loading unpaid dues...
                    </div>

                    <div v-else-if="availableVouchers.length === 0" class="py-10 text-center text-gray-500 dark:text-gray-400">
                        No unpaid voucher dues found for the selected student.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                        Select
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                        Voucher No
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                        Month
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                        Module
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                        Due Item
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                        Due
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">
                                        Pay Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr
                                    v-for="voucher in availableVouchers"
                                    :key="voucher.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-800"
                                >
                                    <td colspan="6" class="px-0 py-0">
                                        <div class="border-b border-gray-200 bg-gray-100 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/40">
                                            <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ voucher.voucher_no }}
                                                    <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                                                        {{ voucher.voucher_month?.name || 'N/A' }} {{ voucher.voucher_year }}
                                                    </span>
                                                </div>
                                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                                    Voucher balance: {{ formatCurrency(voucher.balance_amount) }}
                                                </div>
                                            </div>
                                        </div>
                                        <table class="min-w-full">
                                            <tbody>
                                                <tr
                                                    v-for="item in voucher.items"
                                                    :key="item.id"
                                                    class="border-t border-gray-100 dark:border-gray-800"
                                                >
                                                    <td class="px-4 py-3 align-top">
                                                        <input
                                                            type="checkbox"
                                                            :checked="getChargeAllocation(voucher.id, item.id)?.enabled"
                                                            class="rounded"
                                                            @change="toggleCharge(voucher.id, item)"
                                                        />
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-sm text-gray-600 dark:text-gray-300">
                                                        {{ voucher.voucher_month?.name || 'N/A' }} {{ voucher.voucher_year }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top">
                                                        <span
                                                            :class="item.source_module === 'inventory'
                                                                ? 'inline-flex rounded-full bg-orange-100 px-2 py-1 text-xs font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-300'
                                                                : 'inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'"
                                                        >
                                                            {{ item.source_module === 'inventory' ? 'Inventory' : 'Fee' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 align-top">
                                                        <div class="font-medium text-gray-900 dark:text-white">
                                                            {{ item.fee_head_name || item.description }}
                                                        </div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                                            {{ item.description }}
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-right text-gray-600 dark:text-gray-300">
                                                        {{ formatCurrency(item.balance_amount) }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top">
                                                        <Input
                                                            :model-value="String(getChargeAllocation(voucher.id, item.id)?.amount ?? item.balance_amount)"
                                                            @update:model-value="(value) => { const allocation = getChargeAllocation(voucher.id, item.id); if (allocation) allocation.amount = Number(value); }"
                                                            type="number"
                                                            :max="item.balance_amount"
                                                            step="0.01"
                                                            min="0"
                                                            :disabled="!getChargeAllocation(voucher.id, item.id)?.enabled"
                                                            class="ml-auto w-36 text-right"
                                                        />
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-6 dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Payment Summary</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-4 text-center md:grid-cols-3">
                        <div>
                            <p class="text-sm text-gray-500">Received</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(form.received_amount) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Allocated</p>
                            <p class="text-xl font-bold text-blue-600">{{ formatCurrency(totalAllocated) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Wallet Credit</p>
                            <p :class="['text-xl font-bold', remainingAmount > 0 ? 'text-green-600' : 'text-gray-600 dark:text-gray-300']">
                                {{ formatCurrency(Math.max(0, remainingAmount)) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <Label for="remarks">Remarks</Label>
                    <textarea
                        id="remarks"
                        v-model="form.remarks"
                        rows="3"
                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <Button type="button" variant="outline" @click="router.visit(route('fee.payments.index'))">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="isSubmitting || !form.student_id || form.received_amount <= 0">
                        <Icon v-if="isSubmitting" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                        {{ isSubmitting ? 'Processing...' : 'Record Payment' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
