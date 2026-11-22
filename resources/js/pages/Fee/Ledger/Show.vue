<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { formatCurrency } from '@/utils';
import { formatDate } from '@/utils/date';

interface LedgerRow {
    date: string;
    type: string;
    reference: string;
    description: string;
    debit: number;
    credit: number;
    balance: number;
}

const props = defineProps<{
    student: { id: number; name: string; registration_number: string };
    rows: LedgerRow[];
    closingBalance: number;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Students', href: '/students' },
    { title: 'Fee ledger', href: '#' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Fee Ledger" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h1 class="text-xl font-bold text-foreground md:text-2xl">Fee ledger</h1>
                    <p class="text-sm text-muted-foreground">{{ props.student.name }} ({{ props.student.registration_number }})</p>
                </div>
                <Link :href="route('students.show', props.student.id)" class="text-sm text-primary hover:underline">Back to student</Link>
            </div>

            <div class="rounded-lg border border-border bg-card p-4">
                <p class="text-sm text-muted-foreground">Balance due</p>
                <p class="text-2xl font-bold text-foreground" data-testid="closing-balance">{{ formatCurrency(props.closingBalance) }}</p>
            </div>

            <div class="overflow-x-auto rounded-lg border border-border bg-card">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border text-muted-foreground">
                            <th class="px-3 py-2 text-left">Date</th>
                            <th class="px-3 py-2 text-left">Entry</th>
                            <th class="px-3 py-2 text-left">Reference</th>
                            <th class="px-3 py-2 text-left">Note</th>
                            <th class="px-3 py-2 text-right">Charged</th>
                            <th class="px-3 py-2 text-right">Paid</th>
                            <th class="px-3 py-2 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in props.rows" :key="index" class="border-b border-border">
                            <td class="px-3 py-2">{{ formatDate(row.date) }}</td>
                            <td class="px-3 py-2">{{ row.type }}</td>
                            <td class="px-3 py-2">{{ row.reference }}</td>
                            <td class="px-3 py-2 text-muted-foreground">{{ row.description }}</td>
                            <td class="px-3 py-2 text-right">{{ row.debit ? formatCurrency(row.debit) : '' }}</td>
                            <td class="px-3 py-2 text-right">{{ row.credit ? formatCurrency(row.credit) : '' }}</td>
                            <td class="px-3 py-2 text-right font-medium">{{ formatCurrency(row.balance) }}</td>
                        </tr>
                        <tr v-if="props.rows.length === 0">
                            <td colspan="7" class="px-3 py-6 text-center text-muted-foreground">No fee entries yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
