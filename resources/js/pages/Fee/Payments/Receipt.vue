<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';

interface FeePaymentAllocation {
    id: number;
    fee_voucher_id: number;
    allocated_amount: number;
    allocation_date: string;
    voucher?: {
        id: number;
        voucher_no: string;
        voucherMonth?: { id: number; name: string };
        net_amount: number;
        paid_amount: number;
        balance_amount: number;
    };
}

interface FeePayment {
    id: number;
    receipt_no: string;
    student_id: number;
    payment_date: string;
    payment_method: string;
    reference_no?: string;
    bank_name?: string;
    received_amount: number;
    allocated_amount: number;
    excess_amount: number;
    status: string;
    notes?: string;
    student?: {
        id: number;
        name: string;
        registration_number: string;
        father_name: string;
        phone?: string;
    };
    allocations: FeePaymentAllocation[];
    receivedBy?: { id: number; name: string };
}

interface Props {
    payment: FeePayment;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Payments', href: '/fee/payments' },
    { title: props.payment.receipt_no, href: `/fee/payments/${props.payment.id}` },
    { title: 'Receipt', href: `/fee/payments/${props.payment.id}/receipt` },
];

const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('en-PK', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const printReceipt = () => {
    window.print();
};

const getPaymentMethodLabel = (method: string): string => {
    const labels: Record<string, string> = {
        cash: 'Cash',
        bank: 'Bank Transfer',
        online: 'Online',
        jazzcash: 'JazzCash',
        easypaisa: 'EasyPaisa',
        cheque: 'Cheque',
    };
    return labels[method] || method;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Payment Receipt" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Actions -->
            <div class="flex justify-between items-center print:hidden">
                <Button variant="outline" @click="router.visit(route('fee.payments.index'))">
                    <Icon icon="arrow-left" class="mr-2 h-4 w-4" />
                    Back to Payments
                </Button>
                <Button @click="printReceipt">
                    <Icon icon="printer" class="mr-2 h-4 w-4" />
                    Print Receipt
                </Button>
            </div>

            <!-- Receipt -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 md:p-8 max-w-2xl mx-auto print:border-0 print:p-0">
                <!-- Header -->
                <div class="text-center mb-8">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Fee Payment Receipt</h1>
                    <p class="text-gray-600 dark:text-gray-400">Receipt No: {{ props.payment.receipt_no }}</p>
                </div>

                <!-- Payment Info -->
                <div class="grid gap-4 mb-6">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Payment Date:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ formatDate(props.payment.payment_date) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Payment Method:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ getPaymentMethodLabel(props.payment.payment_method) }}</span>
                    </div>
                    <div v-if="props.payment.reference_no" class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Reference No:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ props.payment.reference_no }}</span>
                    </div>
                    <div v-if="props.payment.bank_name" class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Bank Name:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ props.payment.bank_name }}</span>
                    </div>
                </div>

                <!-- Student Info -->
                <div class="border-t border-b border-gray-200 dark:border-gray-700 py-4 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Student Information</h2>
                    <div class="grid gap-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Name:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ props.payment.student?.name || 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Admission No:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ props.payment.student?.registration_number || 'N/A' }}</span>
                        </div>
                        <div v-if="props.payment.student?.father_name" class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Father Name:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ props.payment.student?.father_name || 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Vouchers -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Vouchers Paid</h2>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 text-gray-500 dark:text-gray-400">Voucher No</th>
                                <th class="text-left py-2 text-gray-500 dark:text-gray-400">Month</th>
                                <th class="text-right py-2 text-gray-500 dark:text-gray-400">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="allocation in props.payment.allocations" :key="allocation.id" class="border-b border-gray-100 dark:border-gray-700">
                                <td class="py-2 text-gray-900 dark:text-white">{{ allocation.voucher?.voucher_no }}</td>
                                <td class="py-2 text-gray-600 dark:text-gray-300">{{ allocation.voucher?.voucherMonth?.name }}</td>
                                <td class="py-2 text-right text-gray-900 dark:text-white font-medium">{{ formatCurrency(allocation.allocated_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600 dark:text-gray-400">Total Received:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(props.payment.received_amount) }}</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600 dark:text-gray-400">Allocated to Vouchers:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(props.payment.allocated_amount) }}</span>
                    </div>
                    <div v-if="props.payment.excess_amount > 0" class="flex justify-between mb-2">
                        <span class="text-gray-600 dark:text-gray-400">Excess (Wallet):</span>
                        <span class="font-medium text-green-600 dark:text-green-400">{{ formatCurrency(props.payment.excess_amount) }}</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="font-semibold text-gray-900 dark:text-white">Net Paid:</span>
                        <span class="font-bold text-lg text-gray-900 dark:text-white">{{ formatCurrency(props.payment.allocated_amount) }}</span>
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="props.payment.notes" class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Notes: {{ props.payment.notes }}</p>
                </div>

                <!-- Footer -->
                <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    <p>Thank you for your payment!</p>
                    <p>Generated on {{ formatDate(new Date().toISOString()) }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
