<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatMoney } from '@/composables/useTransportForm';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    summary: {
        vehicles: number;
        routes: number;
        assigned_students: number;
        month_dues: number;
        month_expenses: number;
    };
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transport', href: '/transport' },
];

const cards = (summary: { vehicles: number; routes: number; assigned_students: number; month_dues: number; month_expenses: number }) => [
    { label: 'Vehicles', value: String(summary.vehicles), href: route('transport.vehicles.index'), tone: 'text-foreground' },
    { label: 'Routes', value: String(summary.routes), href: route('transport.routes.index'), tone: 'text-primary' },
    { label: 'Assigned students', value: String(summary.assigned_students), href: route('students.transport.index'), tone: 'text-success' },
    { label: "This month's dues", value: formatMoney(summary.month_dues), href: route('students.transport-dues.index'), tone: 'text-foreground' },
    { label: "This month's expenses", value: formatMoney(summary.month_expenses), href: route('transport.expenses.index'), tone: 'text-destructive' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Transport" />

        <div class="space-y-6 p-4 md:p-6">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Transport</h1>
                <p class="mt-1 text-sm text-muted-foreground">A quick look at the fleet, routes, students and money for this campus.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
                <Link
                    v-for="card in cards(summary)"
                    :key="card.label"
                    :href="card.href"
                    class="rounded-2xl border border-border bg-card p-5 shadow-sm transition hover:border-primary"
                >
                    <p class="text-sm text-muted-foreground">{{ card.label }}</p>
                    <p class="mt-2 text-2xl font-bold" :class="card.tone">{{ card.value }}</p>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
