<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';
import { route } from 'ziggy-js';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Icon from '@/components/Icon.vue';
import { formatCurrency } from '@/utils/currency';

interface Student {
    id: number;
    name: string;
    registration_number: string;
}

interface Voucher {
    id: number;
    voucher_no: string;
    voucher_month: { name: string };
    voucher_year: number;
    net_amount: number;
    balance_amount: number;
}

interface Props {
    unpaidVouchers: Voucher[];
    studentId: number | null;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Payments', href: '/fee/payments' },
    { title: 'New Payment', href: '#' },
];

const form = reactive({
    student_id: props.studentId || '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'cash',
    reference_no: '',
    bank_name: '',
    received_amount: 0,
    vouchers: [] as Array<{ voucher_id: number; amount: number }>,
    remarks: '',
});

const searchQuery = reactive({ value: '' });
const searchResults = reactive<Student[]>([]);
const isSearching = reactive({ value: false });
const selectedStudent = reactive<Student | null>(null);

const paymentMethods = [
    { value: 'cash', label: 'Cash' },
    { value: 'bank', label: 'Bank Transfer' },
    { value: 'online', label: 'Online Payment' },
    { value: 'jazzcash', label: 'JazzCash' },
    { value: 'easypaisa', label: 'EasyPaisa' },
    { value: 'cheque', label: 'Cheque' },
];

let searchTimeout: ReturnType<typeof setTimeout>;

const searchStudents = () => {
    if (searchQuery.value.length < 2) {
        searchResults.length = 0;
        return;
    }
    
    isSearching.value = true;
    clearTimeout(searchTimeout);
    
    searchTimeout = setTimeout(() => {
        axios.get(route('students.search', { q: searchQuery.value }))
            .then((response) => {
                searchResults.splice(0, searchResults.length, ...response.data);
            })
            .catch(() => {
                searchResults.length = 0;
            })
            .finally(() => {
                isSearching.value = false;
            });
    }, 300);
};

const selectStudent = (student: Student) => {
    selectedStudent.value = student;
    form.student_id = student.id;
    searchQuery.value = student.name;
    searchResults.length = 0;
    
    // Fetch unpaid vouchers for this student
    axios.get(route('fee.vouchers.unpaid', { student_id: student.id }))
        .then((response) => {
            form.vouchers = response.data.map((v: Voucher) => ({
                voucher_id: v.id,
                amount: v.balance_amount,
            }));
        });
};

const totalAllocated = () => {
    return form.vouchers.reduce((sum, v) => sum + v.amount, 0);
};

const remainingAmount = () => {
    return form.received_amount - totalAllocated();
};

const isSubmitting = reactive({ value: false });

const submitForm = () => {
    isSubmitting.value = true;
    
    const data = {
        ...form,
        vouchers: form.vouchers.filter(v => v.amount > 0),
    };
    
    router.post(route('fee.payments.store'), data, {
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

watch(() => props.studentId, (newId) => {
    if (newId) {
        form.student_id = newId;
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Record Payment" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                    Record Fee Payment
                </h1>
                <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                    Record a new fee payment from a student
                </p>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6 max-w-4xl">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Student Search -->
                    <div class="md:col-span-2">
                        <Label for="student_search">Student *</Label>
                        <div class="relative">
                            <Input
                                id="student_search"
                                v-model="searchQuery"
                                @input="searchStudents"
                                placeholder="Search by name or registration number..."
                                autocomplete="off"
                            />
                            <div v-if="isSearching" class="absolute right-3 top-3">
                                <Icon icon="loader" class="h-4 w-4 animate-spin text-gray-400" />
                            </div>
                            <div v-if="searchResults.length > 0" class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-60 overflow-auto">
                                <button
                                    v-for="student in searchResults"
                                    :key="student.id"
                                    type="button"
                                    @click="selectStudent(student)"
                                    class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700"
                                >
                                    <div class="font-medium">{{ student.name }}</div>
                                    <div class="text-sm text-gray-500">{{ student.registration_number }}</div>
                                </button>
                            </div>
                        </div>
                        <input v-model="form.student_id" type="hidden" required />
                    </div>

                    <!-- Payment Date -->
                    <div>
                        <Label for="payment_date">Payment Date *</Label>
                        <Input
                            id="payment_date"
                            v-model="form.payment_date"
                            type="date"
                            required
                        />
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <Label for="payment_method">Payment Method *</Label>
                        <select
                            id="payment_method"
                            v-model="form.payment_method"
                            required
                            class="w-full mt-1 block rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                        >
                            <option v-for="method in paymentMethods" :key="method.value" :value="method.value">
                                {{ method.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Reference No -->
                    <div>
                        <Label for="reference_no">Reference No</Label>
                        <Input
                            id="reference_no"
                            v-model="form.reference_no"
                            placeholder="Bank transaction ID, cheque number, etc."
                        />
                    </div>

                    <!-- Bank Name -->
                    <div v-if="['bank', 'cheque'].includes(form.payment_method)">
                        <Label for="bank_name">Bank Name</Label>
                        <Input
                            id="bank_name"
                            v-model="form.bank_name"
                            placeholder="Bank name"
                        />
                    </div>

                    <!-- Received Amount -->
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

                <!-- Voucher Allocations -->
                <div v-if="form.vouchers.length > 0" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold">Select Vouchers to Pay</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left">
                                        <input type="checkbox" disabled class="rounded" checked />
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                        Voucher No
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                        Month
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                        Total
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                        Balance
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                        Pay Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="(voucher, index) in props.unpaidVouchers" :key="voucher.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3">
                                        <input 
                                            type="checkbox" 
                                            v-model="form.vouchers[index]" 
                                            :value="{ voucher_id: voucher.id, amount: form.vouchers[index]?.amount || 0 }"
                                            class="rounded"
                                        />
                                    </td>
                                    <td class="px-4 py-3 font-medium">{{ voucher.voucher_no }}</td>
                                    <td class="px-4 py-3">{{ voucher.voucher_month?.name }} {{ voucher.voucher_year }}</td>
                                    <td class="px-4 py-3 text-right">{{ formatCurrency(voucher.net_amount) }}</td>
                                    <td class="px-4 py-3 text-right">{{ formatCurrency(voucher.balance_amount) }}</td>
                                    <td class="px-4 py-3">
                                        <input
                                            type="number"
                                            v-model="form.vouchers[index].amount"
                                            :max="voucher.balance_amount"
                                            step="0.01"
                                            min="0"
                                            class="w-32 text-right rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary -->
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold">Payment Summary</h2>
                    </div>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <p class="text-sm text-gray-500">Received</p>
                            <p class="text-xl font-bold">{{ formatCurrency(form.received_amount) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Allocated</p>
                            <p class="text-xl font-bold text-blue-600">{{ formatCurrency(totalAllocated()) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Wallet Credit</p>
                            <p :class="['text-xl font-bold', remainingAmount() > 0 ? 'text-green-600' : 'text-gray-600']">
                                {{ formatCurrency(Math.max(0, remainingAmount())) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Remarks -->
                <div>
                    <Label for="remarks">Remarks</Label>
                    <textarea
                        id="remarks"
                        v-model="form.remarks"
                        rows="3"
                        class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                    ></textarea>
                </div>

                <!-- Actions -->
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
