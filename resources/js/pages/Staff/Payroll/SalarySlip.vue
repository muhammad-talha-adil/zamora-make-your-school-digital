<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

interface Props {
    school: { name?: string | null; address?: string | null; phone?: string | null };
    slip: {
        id: number;
        period: string;
        staff_name?: string | null;
        employee_no: string;
        designation?: string | null;
        campus?: string | null;
        gross_salary: number;
        allowance_amount: number;
        deduction_amount: number;
        advance_deduction_amount: number;
        net_salary: number;
        amount_paid: number;
        amount_due: number;
        status: string;
        payment_method?: string | null;
        reference_no?: string | null;
        paid_at?: string | null;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Staff', href: '/staff' },
    { title: 'Salary Slip', href: '#' },
];

const printSlip = () => {
    window.print();
};

const money = (amount: number) =>
    new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', minimumFractionDigits: 0 }).format(amount);

const info = [
    { label: 'Name', value: props.slip.staff_name },
    { label: 'Employee no.', value: props.slip.employee_no },
    { label: 'Designation', value: props.slip.designation || '-' },
    { label: 'Campus', value: props.slip.campus || 'School-wide' },
];
const earnings = [
    { label: 'Basic salary', value: props.slip.gross_salary },
    { label: 'Allowances', value: props.slip.allowance_amount },
];
const deductions = [
    { label: 'Deductions', value: props.slip.deduction_amount },
    { label: 'Advance instalment', value: props.slip.advance_deduction_amount },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Salary Slip - ${props.slip.period}`" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex justify-end print:hidden">
                <Button @click="printSlip">
                    <Icon icon="printer" class="mr-2 h-4 w-4" />
                    Print / Save as PDF
                </Button>
            </div>

            <div class="mx-auto max-w-2xl rounded-lg border border-border bg-card p-6 md:p-8 print:max-w-none print:border-0 print:p-0 print:shadow-none">
                <div class="border-b border-border pb-4 text-center">
                    <h1 class="text-xl font-bold text-foreground">{{ props.school.name }}</h1>
                    <p v-if="props.school.address" class="text-xs text-muted-foreground">{{ props.school.address }}</p>
                    <p class="mt-2 text-sm font-semibold uppercase tracking-wide">Salary Slip - {{ props.slip.period }}</p>
                </div>

                <dl class="space-y-2 py-4 text-sm">
                    <div v-for="row in info" :key="row.label" class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">{{ row.label }}</dt>
                        <dd class="text-right font-medium">{{ row.value }}</dd>
                    </div>
                </dl>

                <div class="grid gap-6 border-t border-border pt-4 grid-cols-1 2xl:grid-cols-2">
                    <div>
                        <h2 class="mb-2 text-sm font-semibold">Earnings</h2>
                        <div v-for="row in earnings" :key="row.label" class="flex justify-between gap-4 py-1 text-sm">
                            <span class="text-muted-foreground">{{ row.label }}</span>
                            <span>{{ money(row.value) }}</span>
                        </div>
                    </div>
                    <div>
                        <h2 class="mb-2 text-sm font-semibold">Deductions</h2>
                        <div v-for="row in deductions" :key="row.label" class="flex justify-between gap-4 py-1 text-sm">
                            <span class="text-muted-foreground">{{ row.label }}</span>
                            <span>{{ money(row.value) }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 space-y-1 border-t border-border pt-4 text-sm">
                    <div class="flex justify-between text-base font-bold">
                        <span>Net salary</span><span>{{ money(props.slip.net_salary) }}</span>
                    </div>
                    <div class="flex justify-between"><span class="text-muted-foreground">Paid</span><span>{{ money(props.slip.amount_paid) }}</span></div>
                    <div class="flex justify-between"><span class="text-muted-foreground">Balance due</span><span>{{ money(props.slip.amount_due) }}</span></div>
                    <div class="flex justify-between capitalize"><span class="text-muted-foreground">Status</span><span>{{ props.slip.status }}</span></div>
                    <div v-if="props.slip.paid_at" class="flex justify-between">
                        <span class="text-muted-foreground">Paid on</span>
                        <span>{{ props.slip.paid_at }}{{ props.slip.payment_method ? ` (${props.slip.payment_method})` : '' }}</span>
                    </div>
                    <div v-if="props.slip.reference_no" class="flex justify-between">
                        <span class="text-muted-foreground">Reference</span><span>{{ props.slip.reference_no }}</span>
                    </div>
                </div>

                <p class="mt-6 text-center text-xs text-muted-foreground">Computer-generated slip, no signature required.</p>
            </div>
        </div>
    </AppLayout>
</template>
