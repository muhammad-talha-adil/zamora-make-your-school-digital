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
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">{{ ledger.ledger_number }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Legacy finance ledger transaction detail.</p>
                </div>
                <Button variant="outline" @click="router.visit('/finance/transactions')">Back</Button>
            </div>

            <div class="grid gap-4 rounded-2xl border border-border bg-card p-6 shadow-sm md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <p class="text-sm text-muted-foreground">Type</p>
                    <p class="mt-1 font-semibold text-foreground capitalize">{{ ledger.transaction_type.toLowerCase() }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Date</p>
                    <p class="mt-1 font-semibold text-foreground">{{ ledger.transaction_date }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Amount</p>
                    <p class="mt-1 font-semibold text-foreground">{{ formatMoney(ledger.amount) }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Payment Method</p>
                    <p class="mt-1 font-semibold text-foreground">{{ ledger.payment_method || '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Category</p>
                    <p class="mt-1 font-semibold text-foreground">{{ ledger.category?.name || '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Campus</p>
                    <p class="mt-1 font-semibold text-foreground">{{ ledger.campus?.name || '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Student</p>
                    <p class="mt-1 font-semibold text-foreground">{{ ledger.student?.name || '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Supplier</p>
                    <p class="mt-1 font-semibold text-foreground">{{ ledger.supplier?.name || '-' }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                <p class="text-sm text-muted-foreground">Description</p>
                <p class="mt-2 text-foreground">{{ ledger.description || '-' }}</p>
            </div>
        </div>
    </AppLayout>
</template>
