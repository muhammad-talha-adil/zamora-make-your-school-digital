<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import type { BreadcrumbItem } from '@/types';

interface RecentJoiner {
    id: number;
    employee_no: string;
    hire_date?: string | null;
    user?: { name: string } | null;
    campus?: { name: string } | null;
    designation?: { name: string } | null;
}

interface Props {
    recentJoiners: RecentJoiner[];
    summary: {
        active_staff: number;
        monthly_salary: number | null;
        pending_payroll: number | null;
        present_today: number;
        on_leave_today: number;
        pending_leave_requests: number;
        documents_expiring_soon: number;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Staff', href: '/staff' },
];

const formatMoney = (amount: number | string | null | undefined) => {
    if (amount === null || amount === undefined) {
        return '—';
    }

    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(Number(amount));
};

const formatDate = (value?: string | null) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleDateString('en-PK', { year: 'numeric', month: 'short', day: 'numeric' });
};

const quickLinks = [
    { label: 'Staff Directory', description: 'Every record, jobs and personal files', icon: 'users', href: () => route('staff.people.index') },
    { label: 'Teaching Assignments', description: 'Who teaches which class', icon: 'graduation-cap', href: () => route('staff.teaching.page') },
    { label: 'Payroll', description: 'Generate a run and release salaries', icon: 'wallet', href: () => route('staff.payroll.page') },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Staff" />

        <div class="space-y-6 p-4 md:p-6">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Staff</h1>
                <p class="mt-1 text-sm text-muted-foreground">A snapshot of who works here, today.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <p class="text-sm text-muted-foreground">Active Staff</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ props.summary.active_staff }}</p>
                </div>
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <p class="text-sm text-muted-foreground">Present Today</p>
                    <p class="mt-2 text-2xl font-bold text-success">{{ props.summary.present_today }}</p>
                </div>
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <p class="text-sm text-muted-foreground">On Leave Today</p>
                    <p class="mt-2 text-2xl font-bold text-warning">{{ props.summary.on_leave_today }}</p>
                </div>
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <p class="text-sm text-muted-foreground">Leave Awaiting Decision</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ props.summary.pending_leave_requests }}</p>
                </div>
            </div>

            <div v-if="props.summary.monthly_salary !== null" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <p class="text-sm text-muted-foreground">Monthly Salary Cost</p>
                    <p class="mt-2 text-2xl font-bold text-primary">{{ formatMoney(props.summary.monthly_salary) }}</p>
                </div>
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <p class="text-sm text-muted-foreground">Pending Payroll Payable</p>
                    <p class="mt-2 text-2xl font-bold text-destructive">{{ formatMoney(props.summary.pending_payroll) }}</p>
                </div>
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <p class="text-sm text-muted-foreground">Documents Expiring (30 days)</p>
                    <p class="mt-2 text-2xl font-bold text-warning">{{ props.summary.documents_expiring_soon }}</p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <Link
                    v-for="link in quickLinks"
                    :key="link.label"
                    :href="link.href()"
                    class="flex items-start gap-3 rounded-2xl border border-border bg-card p-5 shadow-sm transition hover:border-primary hover:shadow-md"
                >
                    <Icon :icon="link.icon" class="mt-1 h-5 w-5 shrink-0 text-primary" />
                    <div>
                        <p class="font-semibold text-foreground">{{ link.label }}</p>
                        <p class="mt-1 text-sm text-muted-foreground">{{ link.description }}</p>
                    </div>
                </Link>
            </div>

            <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                <div class="border-b border-border px-5 py-4">
                    <h2 class="text-lg font-semibold text-foreground">Recently Joined</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Designation</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Campus</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Hired</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="member in props.recentJoiners" :key="member.id" class="hover:bg-accent">
                                <td class="px-4 py-3">
                                    <Link :href="route('staff.people.show', member.id)" class="font-medium text-foreground hover:underline">
                                        {{ member.user?.name || '-' }}
                                    </Link>
                                    <div class="text-xs text-muted-foreground">{{ member.employee_no }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ member.designation?.name || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ member.campus?.name || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ formatDate(member.hire_date) }}</td>
                            </tr>
                            <tr v-if="props.recentJoiners.length === 0">
                                <td colspan="4" class="px-4 py-10 text-center text-sm text-muted-foreground">No staff records yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
