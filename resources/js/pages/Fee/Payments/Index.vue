<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { route } from 'ziggy-js';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Icon from '@/components/Icon.vue';
import { formatCurrency } from '@/utils/format';

interface FeePayment {
    id: number;
    receipt_no: string;
    student: { id: number; name: string; registration_number: string };
    payment_date: string;
    payment_method: string;
    received_amount: number;
    allocated_amount: number;
    status: string;
}

interface Props {
    payments: FeePayment[];
    filters?: {
        date_from?: string;
        date_to?: string;
        payment_method?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

// Handle both paginated and array formats and filter null values
const getPaymentsArray = (payments: Props['payments']): FeePayment[] => {
    if (Array.isArray(payments)) {
        return (payments || []).filter(p => p !== null);
    }
    if (payments && typeof payments === 'object' && 'data' in payments) {
        return ((payments as { data: FeePayment[] }).data || []).filter(p => p !== null);
    }
    return [];
};

const paymentsData = ref<FeePayment[]>(getPaymentsArray(props.payments));

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Payments', href: '/fee/payments' },
];

const filters = reactive({
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    payment_method: props.filters?.payment_method || '',
    search: props.filters?.search || '',
});

let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

const onSearchInput = () => {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }
    searchDebounceTimer = window.setTimeout(() => {
        applyFilters();
    }, 300);
};

const applyFilters = () => {
    const params = new URLSearchParams();
    if (filters.date_from) params.append('date_from', filters.date_from);
    if (filters.date_to) params.append('date_to', filters.date_to);
    if (filters.payment_method) params.append('payment_method', filters.payment_method);
    if (filters.search) params.append('search', filters.search);
    
    const queryString = params.toString();
    const newUrl = route('fee.payments.index') + (queryString ? `?${queryString}` : '');
    window.history.pushState({}, '', newUrl);
    fetchPayments();
};

const fetchPayments = () => {
    const params = new URLSearchParams();
    if (filters.date_from) params.append('date_from', filters.date_from);
    if (filters.date_to) params.append('date_to', filters.date_to);
    if (filters.payment_method) params.append('payment_method', filters.payment_method);
    if (filters.search) params.append('search', filters.search);

    axios.get(route('fee.payments.index') + `?${params.toString()}`).then((response) => {
        const data = response.data.data || response.data;
        paymentsData.value = getPaymentsArray(data);
    });
};

const getPaymentMethodLabel = (method: string) => {
    const labels = {
        cash: 'Cash',
        bank: 'Bank Transfer',
        online: 'Online Payment',
        jazzcash: 'JazzCash',
        easypaisa: 'EasyPaisa',
        cheque: 'Cheque',
    };
    return labels[method] || method;
};

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Fee Payments" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Fee Payments
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        View and record student fee payments
                    </p>
                </div>
                <Button @click="router.visit(route('fee.payments.create'))">
                    <Icon icon="plus" class="mr-2 h-4 w-4" />
                    Record Payment
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-2 md:gap-3 flex-wrap">
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="filter-date-from" class="sr-only">From Date</Label>
                    <Input
                        id="filter-date-from"
                        v-model="filters.date_from"
                        @change="applyFilters"
                        type="date"
                        placeholder="From Date"
                        class="min-h-10 md:min-h-11"
                    />
                </div>
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="filter-date-to" class="sr-only">To Date</Label>
                    <Input
                        id="filter-date-to"
                        v-model="filters.date_to"
                        @change="applyFilters"
                        type="date"
                        placeholder="To Date"
                        class="min-h-10 md:min-h-11"
                    />
                </div>
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="filter-method" class="sr-only">Payment Method</Label>
                    <select
                        id="filter-method"
                        v-model="filters.payment_method"
                        @change="applyFilters"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Methods</option>
                        <option value="cash">Cash</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="online">Online Payment</option>
                        <option value="jazzcash">JazzCash</option>
                        <option value="easypaisa">EasyPaisa</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="search-payment" class="sr-only">Search</Label>
                    <Input
                        id="search-payment"
                        v-model="filters.search"
                        @input="onSearchInput"
                        placeholder="Search payments..."
                        class="min-h-10 md:min-h-11"
                    />
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Receipt No
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Student
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Date
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Method
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Amount
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="payment in paymentsData" :key="payment?.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ payment.receipt_no }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ payment.student?.name || 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">{{ payment.student?.registration_number || 'N/A' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ payment.payment_date }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ getPaymentMethodLabel(payment.payment_method) }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-green-600 dark:text-green-400">{{ formatCurrency(payment.received_amount) }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                    <div class="flex gap-2 justify-end">
                                        <Button variant="outline" size="sm" @click="router.visit(route('fee.payments.show', payment.id))">
                                            <Icon icon="eye" class="mr-1 h-3 w-3" />View
                                        </Button>
                                        <Button variant="outline" size="sm" @click="router.visit(route('fee.payments.receipt', payment.id))">
                                            <Icon icon="printer" class="mr-1 h-3 w-3" />Receipt
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
