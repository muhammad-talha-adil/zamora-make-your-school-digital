<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { formatCurrency } from '@/utils/currency';
import { formatDate } from '@/utils/date';

interface FeePayment {
    id: number;
    receipt_no: string;
    student: { id: number; name: string; registration_number: string };
    campus: { id: number; name: string };
    payment_date: string;
    payment_method: string;
    reference_no: string | null;
    bank_name: string | null;
    received_amount: number;
    allocated_amount: number;
    excess_amount: number;
    status: string;
    received_by: { id: number; name: string };
    allocations: Array<{
        id: number;
        voucher: {
            id: number;
            voucher_no: string;
            voucher_month: { name: string };
            voucher_year: number;
        };
        allocated_amount: number;
    }>;
    created_at: string;
}

interface Props {
    payment: FeePayment;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Payments', href: '/fee/payments' },
    { title: props.payment.receipt_no, href: '#' },
];

const getMethodColor = (method: string) => {
    const colors: Record<string, string> = {
        cash: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        bank: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        online: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
        jazzcash: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        easypaisa: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-400',
        cheque: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    };
    return colors[method] || colors.cash;
};

const formatMethod = (method: string) => {
    return method.charAt(0).toUpperCase() + method.slice(1);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Payment ${payment.receipt_no}`" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Payment Receipt
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Receipt #: {{ payment.receipt_no }}
                    </p>
                </div>
                <Button variant="outline" @click="router.visit(route('fee.payments.print-receipt', payment.id))">
                    <Icon icon="printer" class="mr-2 h-4 w-4" />
                    Print Receipt
                </Button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Payment Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Details Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-semibold mb-4">Payment Details</h2>
                        
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Student</p>
                                <p class="font-medium">{{ payment.student?.name || 'N/A' }}</p>
                                <p class="text-sm text-gray-500">{{ payment.student?.registration_number || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Campus</p>
                                <p class="font-medium">{{ payment.campus?.name || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Payment Date</p>
                                <p class="font-medium">{{ formatDate(payment.payment_date) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Payment Method</p>
                                <span :class="['inline-block px-2 py-1 text-xs font-medium rounded-full', getMethodColor(payment.payment_method)]">
                                    {{ formatMethod(payment.payment_method) }}
                                </span>
                            </div>
                            <div v-if="payment.reference_no">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Reference No</p>
                                <p class="font-medium">{{ payment.reference_no }}</p>
                            </div>
                            <div v-if="payment.bank_name">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Bank Name</p>
                                <p class="font-medium">{{ payment.bank_name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Received By</p>
                                <p class="font-medium">{{ payment.received_by?.name || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Allocations -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold">Voucher Allocations</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                            Voucher No
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                            Month
                                        </th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                            Amount
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="allocation in payment.allocations" :key="allocation.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="px-4 py-3 font-medium">{{ allocation.voucher.voucher_no }}</td>
                                        <td class="px-4 py-3">{{ allocation.voucher.voucher_month?.name }} {{ allocation.voucher.voucher_year }}</td>
                                        <td class="px-4 py-3 text-right">{{ formatCurrency(allocation.allocated_amount) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Summary Sidebar -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-semibold mb-4">Payment Summary</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Received Amount</span>
                                <span class="font-medium text-lg">{{ formatCurrency(payment.received_amount) }}</span>
                            </div>
                            <div class="flex justify-between text-blue-600">
                                <span>Allocated to Vouchers</span>
                                <span>- {{ formatCurrency(payment.allocated_amount) }}</span>
                            </div>
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-3 flex justify-between">
                                <span class="font-semibold">Advance/Wallet Credit</span>
                                <span :class="['font-semibold', payment.excess_amount > 0 ? 'text-green-600' : 'text-gray-600']">
                                    {{ formatCurrency(payment.excess_amount) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
