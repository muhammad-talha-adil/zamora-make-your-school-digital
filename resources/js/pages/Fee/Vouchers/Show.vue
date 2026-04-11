<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';
import { formatCurrency } from '@/utils/currency';
import { formatDate } from '@/utils/date';

interface FeeVoucher {
    id: number;
    voucher_no: string;
    student: { id: number; name: string; registration_number: string };
    voucher_month: { id: number; name: string };
    voucher_year: number;
    issue_date: string;
    due_date: string;
    status: string;
    gross_amount: number;
    discount_amount: number;
    fine_amount: number;
    net_amount: number;
    paid_amount: number;
    balance_amount: number;
    campus: { id: number; name: string };
    class: { id: number; name: string };
    section: { id: number; name: string };
    items: Array<{
        id: number;
        fee_head_id: number;
        fee_head: { id: number; name: string };
        description: string;
        amount: number;
        discount_amount: number;
        fine_amount: number;
        net_amount: number;
    }>;
    payments: Array<{
        id: number;
        payment_date: string;
        receipt_no: string;
        allocated_amount: number;
    }>;
}

interface Props {
    voucher: FeeVoucher;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Vouchers', href: '/fee/vouchers' },
    { title: props.voucher.voucher_no, href: '#' },
];

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        unpaid: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        partial: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        paid: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        overdue: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
        adjusted: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    };
    return colors[status] || colors.unpaid;
};

const canCancel = () => {
    return ['unpaid', 'partial', 'overdue'].includes(props.voucher.status);
};

const handleCancel = () => {
    if (confirm('Are you sure you want to cancel this voucher?')) {
        router.patch(route('fee.vouchers.cancel', props.voucher.id));
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Voucher ${voucher.voucher_no}`" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4">
                <div class="w-full sm:w-auto">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
                        Voucher: {{ voucher.voucher_no }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ voucher.student.name }} - {{ voucher.student.registration_number }}
                    </p>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <Button variant="outline" size="sm" class="flex-1 sm:flex-none" @click="router.visit(route('fee.vouchers.print', voucher.id))">
                        <Icon icon="printer" class="mr-2 h-4 w-4" />
                        <span class="hidden sm:inline">Print</span>
                    </Button>
                    <Button v-if="canCancel()" variant="destructive" size="sm" class="flex-1 sm:flex-none" @click="handleCancel">
                        <Icon icon="x-circle" class="mr-2 h-4 w-4" />
                        <span class="hidden sm:inline">Cancel</span>
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Voucher Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Status Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                            <h2 class="text-lg font-semibold">Voucher Details</h2>
                            <span :class="['px-3 py-1 text-sm font-medium rounded-full w-fit', getStatusColor(voucher.status)]">
                                {{ voucher.status.toUpperCase() }}
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            <div>
                                <Label class="text-gray-500 dark:text-gray-400 text-xs md:text-sm">Month</Label>
                                <p class="font-medium text-sm md:text-base">{{ voucher.voucher_month?.name }} {{ voucher.voucher_year }}</p>
                            </div>
                            <div>
                                <Label class="text-gray-500 dark:text-gray-400 text-xs md:text-sm">Issue Date</Label>
                                <p class="font-medium text-sm md:text-base">{{ formatDate(voucher.issue_date) }}</p>
                            </div>
                            <div>
                                <Label class="text-gray-500 dark:text-gray-400 text-xs md:text-sm">Due Date</Label>
                                <p class="font-medium text-sm md:text-base">{{ formatDate(voucher.due_date) }}</p>
                            </div>
                            <div>
                                <Label class="text-gray-500 dark:text-gray-400 text-xs md:text-sm">Campus</Label>
                                <p class="font-medium text-sm md:text-base">{{ voucher.campus?.name || 'N/A' }}</p>
                            </div>
                            <div>
                                <Label class="text-gray-500 dark:text-gray-400 text-xs md:text-sm">Class</Label>
                                <p class="font-medium text-sm md:text-base">{{ voucher.class?.name || 'N/A' }}</p>
                            </div>
                            <div>
                                <Label class="text-gray-500 dark:text-gray-400 text-xs md:text-sm">Section</Label>
                                <p class="font-medium text-sm md:text-base">{{ voucher.section?.name || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Voucher Items -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold">Fee Items</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-2 md:px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 w-12">
                                            Sr#
                                        </th>
                                        <th class="px-2 md:px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                            Description
                                        </th>
                                        <th class="px-2 md:px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                            Amount
                                        </th>
                                        <th class="px-2 md:px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                            Discount
                                        </th>
                                        <th class="px-2 md:px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                            Fine
                                        </th>
                                        <th class="px-2 md:px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                            Net
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="(item, index) in voucher.items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="px-2 md:px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                            {{ index + 1 }}
                                        </td>
                                        <td class="px-2 md:px-4 py-3">
                                            <div class="font-medium text-sm">{{ item.fee_head?.name }}</div>
                                            <div class="text-xs text-gray-500">{{ item.description }}</div>
                                        </td>
                                        <td class="px-2 md:px-4 py-3 text-right text-sm">{{ formatCurrency(item.amount) }}</td>
                                        <td class="px-2 md:px-4 py-3 text-right text-sm text-green-600">{{ formatCurrency(item.discount_amount) }}</td>
                                        <td class="px-2 md:px-4 py-3 text-right text-sm text-red-600">{{ formatCurrency(item.fine_amount) }}</td>
                                        <td class="px-2 md:px-4 py-3 text-right text-sm font-medium">{{ formatCurrency(item.net_amount) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <td class="px-2 md:px-4 py-3 font-semibold text-sm" colspan="2">Total</td>
                                        <td class="px-2 md:px-4 py-3 text-right font-semibold text-sm">{{ formatCurrency(voucher.gross_amount) }}</td>
                                        <td class="px-2 md:px-4 py-3 text-right font-semibold text-sm text-green-600">{{ formatCurrency(voucher.discount_amount) }}</td>
                                        <td class="px-2 md:px-4 py-3 text-right font-semibold text-sm text-red-600">{{ formatCurrency(voucher.fine_amount) }}</td>
                                        <td class="px-2 md:px-4 py-3 text-right font-semibold text-sm">{{ formatCurrency(voucher.net_amount) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Payment History -->
                    <div v-if="voucher.payments && voucher.payments.length > 0" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold">Payment History</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-2 md:px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 w-12">
                                            Sr#
                                        </th>
                                        <th class="px-2 md:px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                            Receipt No
                                        </th>
                                        <th class="px-2 md:px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                            Date
                                        </th>
                                        <th class="px-2 md:px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                            Amount
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="(payment, index) in voucher.payments" :key="payment.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="px-2 md:px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                            {{ index + 1 }}
                                        </td>
                                        <td class="px-2 md:px-4 py-3 font-medium text-sm">{{ payment.receipt_no }}</td>
                                        <td class="px-2 md:px-4 py-3 text-sm">{{ formatDate(payment.payment_date) }}</td>
                                        <td class="px-2 md:px-4 py-3 text-right text-sm text-green-600">{{ formatCurrency(payment.allocated_amount) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- No Payments Message -->
                    <div v-else class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                        <p class="text-gray-500 dark:text-gray-400 text-center">No payments recorded yet.</p>
                    </div>
                </div>

                <!-- Summary Sidebar -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                        <h2 class="text-lg font-semibold mb-4">Payment Summary</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400 text-sm">Gross Amount</span>
                                <span class="font-medium text-sm">{{ formatCurrency(voucher.gross_amount) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-green-600">
                                <span class="text-sm">Discount</span>
                                <span class="text-sm">- {{ formatCurrency(voucher.discount_amount) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-red-600">
                                <span class="text-sm">Fine</span>
                                <span class="text-sm">+ {{ formatCurrency(voucher.fine_amount) }}</span>
                            </div>
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-3 flex justify-between items-center">
                                <span class="font-semibold text-sm">Net Amount</span>
                                <span class="font-semibold text-base md:text-lg">{{ formatCurrency(voucher.net_amount) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-green-600">
                                <span class="text-sm">Paid</span>
                                <span class="text-sm">- {{ formatCurrency(voucher.paid_amount) }}</span>
                            </div>
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-3 flex justify-between items-center">
                                <span class="font-semibold text-sm">Balance</span>
                                <span :class="['font-semibold text-base md:text-lg', voucher.balance_amount > 0 ? 'text-red-600' : 'text-green-600']">
                                    {{ formatCurrency(voucher.balance_amount) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                        <h2 class="text-lg font-semibold mb-4">Quick Actions</h2>
                        <div class="space-y-2">
                            <Button variant="outline" class="w-full justify-start" size="sm" @click="router.visit(route('fee.vouchers.print', voucher.id))">
                                <Icon icon="printer" class="mr-2 h-4 w-4" />
                                Print Voucher
                            </Button>
                            <Button v-if="voucher.status !== 'paid'" variant="outline" class="w-full justify-start" size="sm">
                                <Icon icon="credit-card" class="mr-2 h-4 w-4" />
                                Record Payment
                            </Button>
                            <Button variant="outline" class="w-full justify-start" size="sm" @click="router.visit(route('fee.vouchers.edit', voucher.id))">
                                <Icon icon="edit" class="mr-2 h-4 w-4" />
                                Edit Voucher
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
