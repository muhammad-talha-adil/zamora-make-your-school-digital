<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import TransportListHeader from '@/components/transport/TransportListHeader.vue';
import { formatMoney } from '@/composables/useTransportForm';
import type { BreadcrumbItem } from '@/types';

interface ReportRow {
    id: number;
    name: string;
    vehicle_no?: string | null;
    monthly_fee: number;
    students: number;
    dues: number;
    expenses: number;
}

const props = defineProps<{ rows: ReportRow[]; unassigned_expenses: number }>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transport', href: '/transport' },
    { title: 'Reports', href: '/transport/reports' },
];

const search = ref('');
const filtered = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.rows.filter((row) => !query || [row.name, row.vehicle_no].some((value) => String(value ?? '').toLowerCase().includes(query)));
});
const totals = computed(() => ({
    students: filtered.value.reduce((sum, row) => sum + row.students, 0),
    dues: filtered.value.reduce((sum, row) => sum + row.dues, 0),
    expenses: filtered.value.reduce((sum, row) => sum + row.expenses, 0),
}));
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Transport Reports" />

        <div class="space-y-6 p-4 md:p-6">
            <TransportListHeader
                v-model:search="search"
                title="Reports"
                description="Students, dues and expenses for each route."
                search-placeholder="Search by route or vehicle..."
            />

            <div class="overflow-x-auto rounded-2xl border border-border bg-card shadow-sm">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Route</th>
                            <th class="px-4 py-3 font-medium">Vehicle</th>
                            <th class="px-4 py-3 text-right font-medium">Students</th>
                            <th class="px-4 py-3 text-right font-medium">Dues</th>
                            <th class="px-4 py-3 text-right font-medium">Expenses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in filtered" :key="row.id" class="border-t border-border">
                            <td class="px-4 py-3 font-medium text-foreground">{{ row.name }}</td>
                            <td class="px-4 py-3 text-foreground">{{ row.vehicle_no || '-' }}</td>
                            <td class="px-4 py-3 text-right text-foreground">{{ row.students }}</td>
                            <td class="px-4 py-3 text-right text-foreground">{{ formatMoney(row.dues) }}</td>
                            <td class="px-4 py-3 text-right text-foreground">{{ formatMoney(row.expenses) }}</td>
                        </tr>
                        <tr v-if="filtered.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">No routes found.</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="filtered.length > 0" class="border-t border-border bg-muted/30 font-semibold">
                        <tr>
                            <td class="px-4 py-3 text-foreground" colspan="2">Total</td>
                            <td class="px-4 py-3 text-right text-foreground">{{ totals.students }}</td>
                            <td class="px-4 py-3 text-right text-foreground">{{ formatMoney(totals.dues) }}</td>
                            <td class="px-4 py-3 text-right text-foreground">{{ formatMoney(totals.expenses) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p v-if="unassigned_expenses > 0" class="text-sm text-muted-foreground">
                Expenses not tied to any vehicle: {{ formatMoney(unassigned_expenses) }}
            </p>
        </div>
    </AppLayout>
</template>
