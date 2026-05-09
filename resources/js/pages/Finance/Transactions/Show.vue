<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

interface LedgerDetails {
    id: number;
    ledger_number: string;
    transaction_type: string;
    transaction_date: string;
    amount: number;
    description?: string | null;
    payment_method?: string | null;
    reference_number?: string | null;
    category?: { name: string } | null;
    campus?: { name: string } | null;
    student?: { name: string } | null;
    supplier?: { name: string } | null;
    creator?: { name: string } | null;
}

const props = defineProps<{
    ledger: LedgerDetails;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Finance', href: '/finance' },
    { title: 'Transactions', href: '/finance/transactions' },
    { title: props.ledger.ledger_number, href: '#' },
];

const formatMoney = (amount: number | null | undefined) => {
    if (amount === null || amount === undefined) return 'Rs 0';

    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(amount);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Transaction ${ledger.ledger_number}`" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ ledger.ledger_number }}</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Legacy finance ledger transaction detail.</p>
                </div>
                <Button variant="outline" @click="router.visit('/finance/transactions')">Back</Button>
            </div>

            <div class="grid gap-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Type</p>
                    <p class="mt-1 font-semibold text-gray-900 capitalize dark:text-white">{{ ledger.transaction_type.toLowerCase() }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Date</p>
                    <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ ledger.transaction_date }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Amount</p>
                    <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ formatMoney(ledger.amount) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Payment Method</p>
                    <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ ledger.payment_method || '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Category</p>
                    <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ ledger.category?.name || '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Campus</p>
                    <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ ledger.campus?.name || '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Student</p>
                    <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ ledger.student?.name || '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Supplier</p>
                    <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ ledger.supplier?.name || '-' }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Description</p>
                <p class="mt-2 text-gray-900 dark:text-white">{{ ledger.description || '-' }}</p>
            </div>
        </div>
    </AppLayout>
</template>
