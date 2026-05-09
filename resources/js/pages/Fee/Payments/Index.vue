<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Icon from '@/components/Icon.vue';
import { formatCurrency } from '@/utils/currency';
import { formatDate } from '@/utils/date';
import { tableActionButtonClass } from '@/utils/table-actions';

interface FeePayment {
    id: number;
    receipt_no: string;
    student?: { id: number; name: string; registration_number: string } | null;
    campus?: { id: number; name: string } | null;
    payment_date: string;
    payment_method: string;
    received_amount: number;
    allocated_amount: number;
    excess_amount?: number;
    status: string;
}

interface PaymentsPagination {
    data: FeePayment[];
    links?: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    from?: number;
    to?: number;
    total?: number;
    current_page?: number;
}

interface Props {
    payments: FeePayment[] | PaymentsPagination;
    campuses: Array<{ id: number; name: string }>;
    filters?: {
        campus_id?: string;
        date_from?: string;
        date_to?: string;
        payment_method?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

const getPaymentsPagination = (payments: Props['payments']): PaymentsPagination => {
    if (Array.isArray(payments)) {
        return {
            data: payments.filter((payment) => payment !== null),
            links: [],
            from: payments.length ? 1 : 0,
            to: payments.length,
            total: payments.length,
            current_page: 1,
        };
    }

    if (payments && typeof payments === 'object' && 'data' in payments) {
        return {
            ...payments,
            data: (payments.data || []).filter((payment) => payment !== null),
        };
    }

    return {
        data: [],
        links: [],
        from: 0,
        to: 0,
        total: 0,
        current_page: 1,
    };
};

const paymentsPagination = computed(() => getPaymentsPagination(props.payments));
const paymentsData = computed(() => paymentsPagination.value.data || []);

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Payments', href: '/fee/payments' },
];

const filters = reactive({
    campus_id: props.filters?.campus_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    payment_method: props.filters?.payment_method || '',
    search: props.filters?.search || '',
});

const buildFilterPayload = (page = 1) => {
    const payload: Record<string, string | number> = { page };

    if (filters.campus_id) payload.campus_id = filters.campus_id;
    if (filters.date_from) payload.date_from = filters.date_from;
    if (filters.date_to) payload.date_to = filters.date_to;
    if (filters.payment_method) payload.payment_method = filters.payment_method;
    if (filters.search.trim()) payload.search = filters.search.trim();

    return payload;
};

const loadPayments = (page = 1) => {
    router.get(route('fee.payments.index'), buildFilterPayload(page), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const getPaymentMethodLabel = (method: string) => {
    const labels: Record<string, string> = {
        cash: 'Cash',
        bank: 'Bank Transfer',
        online: 'Online Payment',
        jazzcash: 'JazzCash',
        easypaisa: 'EasyPaisa',
        cheque: 'Cheque',
    };
    return labels[method] || method;
};

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        posted: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        reversed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    };

    return colors[status] || colors.pending;
};

const getPageFromUrl = (url: string | null) => {
    if (!url) {
        return 1;
    }

    const page = new URL(url, window.location.origin).searchParams.get('page');
    return page ? Number.parseInt(page, 10) : 1;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Fee Payments" />

        <div class="space-y-4 p-4 md:space-y-6 md:p-6">
            <div class="flex flex-col items-start justify-between gap-3 md:gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-lg font-bold text-gray-900 dark:text-white md:text-2xl">
                        Fee Payments
                    </h1>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 md:text-sm">
                        View and record student fee payments
                    </p>
                </div>
                <Button @click="router.visit(route('fee.payments.create'))">
                    <Icon icon="plus" class="mr-2 h-4 w-4" />
                    Record Payment
                </Button>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                    <div class="space-y-2">
                        <Label for="filter-campus">Campus</Label>
                        <select
                            id="filter-campus"
                            v-model="filters.campus_id"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">All Campuses</option>
                            <option v-for="campus in props.campuses" :key="campus.id" :value="String(campus.id)">
                                {{ campus.name }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="filter-date-from">From Date</Label>
                        <Input
                            id="filter-date-from"
                            v-model="filters.date_from"
                            type="date"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="filter-date-to">To Date</Label>
                        <Input
                            id="filter-date-to"
                            v-model="filters.date_to"
                            type="date"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="filter-method">Payment Method</Label>
                        <select
                            id="filter-method"
                            v-model="filters.payment_method"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
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
                    <div class="space-y-2 md:col-span-5">
                        <Label for="search-payment">Search</Label>
                        <Input
                            id="search-payment"
                            v-model="filters.search"
                            placeholder="Search by receipt no, student name, or registration no..."
                        />
                    </div>
                    <div class="flex items-end md:col-span-5">
                        <Button @click="loadPayments" class="w-full md:w-auto">
                            <Icon icon="search" class="mr-2 h-4 w-4" />
                            Load Payments
                        </Button>
                    </div>
                </div>
            </div>

            <div class="block space-y-3 lg:hidden">
                <div
                    v-for="(payment, index) in paymentsData"
                    :key="payment.id"
                    class="space-y-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                Sr# {{ ((paymentsPagination.from || 1) - 1) + index + 1 }}
                            </div>
                            <div class="font-medium text-gray-900 dark:text-white">
                                {{ payment.receipt_no }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ payment.student?.name || 'N/A' }}
                            </div>
                        </div>
                        <span :class="['rounded-full px-2 py-1 text-xs font-medium', getStatusColor(payment.status)]">
                            {{ payment.status }}
                        </span>
                    </div>
                    <div class="space-y-1 border-t border-gray-100 pt-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">
                        <div>Campus: {{ payment.campus?.name || 'N/A' }}</div>
                        <div>Date: {{ formatDate(payment.payment_date) }}</div>
                        <div>Method: {{ getPaymentMethodLabel(payment.payment_method) }}</div>
                        <div>Amount: {{ formatCurrency(payment.received_amount) }}</div>
                        <div>Allocated: {{ formatCurrency(payment.allocated_amount) }}</div>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :class="tableActionButtonClass.view"
                            @click="router.visit(route('fee.payments.show', payment.id))"
                        >
                            <Icon icon="eye" class="mr-1 h-3 w-3" />View
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :class="tableActionButtonClass.print"
                            @click="router.visit(route('fee.payments.receipt', payment.id))"
                        >
                            <Icon icon="printer" class="mr-1 h-3 w-3" />Receipt
                        </Button>
                    </div>
                </div>
                <div v-if="paymentsData.length === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
                    No payments found.
                </div>
            </div>

            <div class="hidden overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900 lg:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Sr#
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Receipt No
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Student
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Campus
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Date
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Method
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Amount
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Status
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr
                                v-for="(payment, index) in paymentsData"
                                :key="payment.id"
                                class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                            >
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    {{ ((paymentsPagination.from || 1) - 1) + index + 1 }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ payment.receipt_no }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ payment.student?.name || 'N/A' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ payment.student?.registration_number || 'N/A' }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ payment.campus?.name || 'N/A' }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ formatDate(payment.payment_date) }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ getPaymentMethodLabel(payment.payment_method) }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="text-sm font-semibold text-green-600 dark:text-green-400">
                                        {{ formatCurrency(payment.received_amount) }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Allocated: {{ formatCurrency(payment.allocated_amount) }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span :class="['rounded-full px-2 py-1 text-xs font-medium', getStatusColor(payment.status)]">
                                        {{ payment.status }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            :class="tableActionButtonClass.view"
                                            @click="router.visit(route('fee.payments.show', payment.id))"
                                        >
                                            <Icon icon="eye" class="mr-1 h-3 w-3" />View
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            :class="tableActionButtonClass.print"
                                            @click="router.visit(route('fee.payments.receipt', payment.id))"
                                        >
                                            <Icon icon="printer" class="mr-1 h-3 w-3" />Receipt
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="paymentsData.length === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
                    No payments found.
                </div>
            </div>

            <div v-if="paymentsPagination.links?.length" class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                <div class="text-xs text-gray-600 dark:text-gray-400 md:text-sm">
                    Showing {{ paymentsPagination.from || 0 }} to {{ paymentsPagination.to || 0 }} of {{ paymentsPagination.total || 0 }} entries
                </div>
                <div class="flex flex-wrap gap-1">
                    <button
                        v-for="link in paymentsPagination.links"
                        :key="`${link.label}-${link.url || 'disabled'}`"
                        type="button"
                        :disabled="!link.url"
                        :class="[
                            'min-h-10 items-center justify-center rounded-md px-3 py-2 text-sm transition-colors',
                            link.active
                                ? 'bg-blue-600 text-white'
                                : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                        ]"
                        @click="link.url ? loadPayments(getPageFromUrl(link.url)) : null"
                    >
                        <span v-html="link.label"></span>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
