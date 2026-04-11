<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import Icon from '@/components/Icon.vue';
import { formatCurrency } from '@/utils/currency';
import { formatDate } from '@/utils/date';

interface FeeVoucher {
    id: number;
    voucher_no: string;
    student: { id: number; name: string; registration_number: string; father_name?: string; phone?: string };
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
}

interface Props {
    voucher: FeeVoucher;
}

const props = defineProps<Props>();

// Print function
const printVoucher = () => {
    window.print();
};

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Vouchers', href: '/fee/vouchers' },
    { title: props.voucher.voucher_no, href: '#' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Print Voucher ${voucher.voucher_no}`" />

        <div class="print-container p-4 md:p-6">
            <!-- Print Button -->
            <div class="no-print mb-4 flex justify-end gap-2">
                <button
                    @click="printVoucher"
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                    <Icon icon="printer" class="h-4 w-4" />
                    Print Voucher
                </button>
                <a
                    :href="route('fee.vouchers.index')"
                    class="flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                >
                    <Icon icon="arrow-left" class="h-4 w-4" />
                    Back
                </a>
            </div>

            <!-- Printable Voucher -->
            <div class="max-w-3xl mx-auto bg-white border-2 border-gray-800 rounded-lg p-6 print:border-none print:p-0">
                <!-- Header -->
                <div class="text-center border-b-2 border-gray-800 pb-4 mb-4">
                    <h1 class="text-2xl font-bold uppercase tracking-wide">Fee Voucher</h1>
                    <p class="text-lg">{{ voucher.campus?.name || 'School Name' }}</p>
                    <p class="text-sm text-gray-600">Session: {{ voucher.voucher_year }}</p>
                </div>

                <!-- Voucher Info -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <p><strong>Voucher No:</strong> {{ voucher.voucher_no }}</p>
                        <p><strong>Issue Date:</strong> {{ formatDate(voucher.issue_date) }}</p>
                        <p><strong>Due Date:</strong> {{ formatDate(voucher.due_date) }}</p>
                    </div>
                    <div class="text-right">
                        <p><strong>Month:</strong> {{ voucher.voucher_month?.name }} {{ voucher.voucher_year }}</p>
                        <p><strong>Class:</strong> {{ voucher.class?.name }}{{ voucher.section ? ' - ' + voucher.section.name : '' }}</p>
                    </div>
                </div>

                <!-- Student Info -->
                <div class="mb-4 p-3 bg-gray-100 rounded">
                    <p><strong>Student Name:</strong> {{ voucher.student.name }}</p>
                    <p><strong>Registration No:</strong> {{ voucher.student.registration_number }}</p>
                    <p v-if="voucher.student.father_name"><strong>Father's Name:</strong> {{ voucher.student.father_name }}</p>
                </div>

                <!-- Fee Items Table -->
                <table class="w-full border-collapse border border-gray-800 mb-4">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="border border-gray-800 px-2 py-1 text-left">Sr#</th>
                            <th class="border border-gray-800 px-2 py-1 text-left">Description</th>
                            <th class="border border-gray-800 px-2 py-1 text-right">Amount</th>
                            <th class="border border-gray-800 px-2 py-1 text-right">Discount</th>
                            <th class="border border-gray-800 px-2 py-1 text-right">Net Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in voucher.items" :key="item.id">
                            <td class="border border-gray-800 px-2 py-1">{{ index + 1 }}</td>
                            <td class="border border-gray-800 px-2 py-1">
                                <div>{{ item.fee_head?.name }}</div>
                                <div class="text-xs text-gray-500">{{ item.description }}</div>
                            </td>
                            <td class="border border-gray-800 px-2 py-1 text-right">{{ formatCurrency(item.amount) }}</td>
                            <td class="border border-gray-800 px-2 py-1 text-right text-green-600">{{ formatCurrency(item.discount_amount) }}</td>
                            <td class="border border-gray-800 px-2 py-1 text-right font-medium">{{ formatCurrency(item.net_amount) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-200">
                            <td colspan="4" class="border border-gray-800 px-2 py-1 text-right font-bold">Total Amount</td>
                            <td class="border border-gray-800 px-2 py-1 text-right font-bold">{{ formatCurrency(voucher.net_amount) }}</td>
                        </tr>
                        <tr v-if="voucher.paid_amount > 0">
                            <td colspan="4" class="border border-gray-800 px-2 py-1 text-right">Paid Amount</td>
                            <td class="border border-gray-800 px-2 py-1 text-right text-green-600">{{ formatCurrency(voucher.paid_amount) }}</td>
                        </tr>
                        <tr v-if="voucher.balance_amount > 0" class="bg-yellow-100">
                            <td colspan="4" class="border border-gray-800 px-2 py-1 text-right font-bold">Balance Due</td>
                            <td class="border border-gray-800 px-2 py-1 text-right font-bold text-red-600">{{ formatCurrency(voucher.balance_amount) }}</td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Footer -->
                <div class="text-center text-sm text-gray-600 mt-8 pt-4 border-t border-gray-300">
                    <p>Please pay the fee before due date to avoid late payment charges.</p>
                    <p>For queries, contact the school administration.</p>
                </div>

                <!-- Payment Record (for school use) -->
                <div class="mt-8 pt-4 border-t-2 border-gray-800">
                    <h3 class="font-bold mb-2">Payment Record</h3>
                    <table class="w-full border-collapse border border-gray-800">
                        <thead>
                            <tr class="bg-gray-200">
                                <th class="border border-gray-800 px-2 py-1 text-left">Date</th>
                                <th class="border border-gray-800 px-2 py-1 text-left">Receipt No</th>
                                <th class="border border-gray-800 px-2 py-1 text-right">Amount</th>
                                <th class="border border-gray-800 px-2 py-1 text-left">Signature</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-gray-800 px-2 py-4"></td>
                                <td class="border border-gray-800 px-2 py-4"></td>
                                <td class="border border-gray-800 px-2 py-4"></td>
                                <td class="border border-gray-800 px-2 py-4"></td>
                            </tr>
                            <tr>
                                <td class="border border-gray-800 px-2 py-4"></td>
                                <td class="border border-gray-800 px-2 py-4"></td>
                                <td class="border border-gray-800 px-2 py-4"></td>
                                <td class="border border-gray-800 px-2 py-4"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


    </AppLayout>
</template>
