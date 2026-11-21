<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Props {
    staff: { id: number; name?: string | null; employee_no: string };
    attendance: {
        month: string;
        summary: { present: number; absent: number; leave: number; late: number; expected_days: number; marked_days: number };
    };
    pendingLeaves: { id: number; type?: string | null; from_date?: string | null; to_date?: string | null; days: number }[];
    latestPayroll: { id: number; period: string; net_salary: number; amount_paid: number; status: string } | null;
    teaching: { id: number; class?: string | null; section?: string | null; subject?: string | null; is_class_teacher: boolean }[];
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Staff Portal', href: route('staff.index') },
    { title: 'My Profile', href: route('staff.me') },
    { title: 'My Dashboard', href: route('staff.me.dashboard') },
];

const money = (amount: number) =>
    new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', minimumFractionDigits: 0 }).format(amount);

const stats = [
    { label: 'Present', value: props.attendance.summary.present },
    { label: 'Absent', value: props.attendance.summary.absent },
    { label: 'On leave', value: props.attendance.summary.leave },
    { label: 'Late', value: props.attendance.summary.late },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="My Dashboard" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">{{ props.staff.name }}</h1>
                    <p class="text-sm text-muted-foreground">{{ props.staff.employee_no }}</p>
                </div>
                <Link :href="route('staff.me')" class="text-sm text-primary hover:underline">Full profile</Link>
            </div>

            <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                <h2 class="text-sm font-semibold">Attendance - {{ props.attendance.month }}</h2>
                <div class="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div v-for="stat in stats" :key="stat.label">
                        <p class="text-2xl font-bold">{{ stat.value }}</p>
                        <p class="text-xs text-muted-foreground">{{ stat.label }}</p>
                    </div>
                </div>
            </section>

            <div class="grid gap-4 md:grid-cols-2">
                <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="text-sm font-semibold">Pending leave</h2>
                    <p v-if="!props.pendingLeaves.length" class="mt-3 text-sm text-muted-foreground">No pending requests.</p>
                    <ul v-else class="mt-3 flex flex-col gap-2 text-sm">
                        <li v-for="leave in props.pendingLeaves" :key="leave.id">
                            {{ leave.type }}: {{ leave.from_date }} to {{ leave.to_date }} ({{ leave.days }} day(s))
                        </li>
                    </ul>
                </section>

                <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="text-sm font-semibold">Latest salary</h2>
                    <p v-if="!props.latestPayroll" class="mt-3 text-sm text-muted-foreground">No payroll yet.</p>
                    <div v-else class="mt-3 flex flex-col gap-1 text-sm">
                        <p>{{ props.latestPayroll.period }} - <span class="capitalize">{{ props.latestPayroll.status }}</span></p>
                        <p class="text-xl font-bold">{{ money(props.latestPayroll.net_salary) }}</p>
                        <Link :href="route('staff.payroll.items.slip', props.latestPayroll.id)" class="text-primary hover:underline">View salary slip</Link>
                    </div>
                </section>
            </div>

            <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                <h2 class="text-sm font-semibold">My classes</h2>
                <p v-if="!props.teaching.length" class="mt-3 text-sm text-muted-foreground">No teaching assignments this session.</p>
                <ul v-else class="mt-3 flex flex-col gap-2 text-sm">
                    <li v-for="row in props.teaching" :key="row.id">
                        {{ row.class }}{{ row.section ? ` - ${row.section}` : '' }}: {{ row.subject || 'Class teacher' }}
                        <span v-if="row.is_class_teacher" class="ml-1 text-xs text-muted-foreground">(class teacher)</span>
                    </li>
                </ul>
            </section>
        </div>
    </AppLayout>
</template>
