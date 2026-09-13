<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { alert } from '@/utils';
import { tableActionButtonClass } from '@/utils/table-actions';
import type { BreadcrumbItem } from '@/types';

interface Campus {
    id: number;
    name: string;
}

interface MonthOption {
    id: number;
    name: string;
    month_number: number;
}

interface PayrollItem {
    id: number;
    staff_profile_id: number;
    gross_salary: number | string;
    allowance_amount: number | string;
    deduction_amount: number | string;
    net_salary: number | string;
    status: string;
    payment_method?: string | null;
    reference_no?: string | null;
    notes?: string | null;
    staff_profile?: { employee_no: string; user?: { name: string } | null } | null;
}

interface PayrollRun {
    id: number;
    title: string;
    campus_id?: number | null;
    payroll_year: number;
    status: string;
    total_gross: number | string;
    total_deductions: number | string;
    total_net: number | string;
    processed_at?: string | null;
    campus?: { name: string } | null;
    month?: { name: string } | null;
    items: PayrollItem[];
}

interface Props {
    campuses: Campus[];
    months: MonthOption[];
    payrollRuns: PayrollRun[];
    can: { approve: boolean };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Staff', href: route('staff.index') },
    { title: 'Payroll', href: route('staff.payroll.page') },
];

const selectClass = 'w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground';

const expandedRunId = ref<number | null>(null);
const campusFilter = ref('');

const payrollForm = reactive({
    campus_id: '',
    payroll_month_id: props.months.find((m) => m.month_number === new Date().getMonth() + 1)?.id?.toString() ?? '',
    payroll_year: String(new Date().getFullYear()),
    title: '',
});

const formatMoney = (amount: number | string | null | undefined) => {
    return new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', minimumFractionDigits: 0 }).format(Number(amount || 0));
};

const formatDate = (value?: string | null) => {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('en-PK', { year: 'numeric', month: 'short', day: 'numeric' });
};

const filteredRuns = computed(() => {
    if (!campusFilter.value) return props.payrollRuns;
    return props.payrollRuns.filter((run) => String(run.campus_id ?? '') === campusFilter.value);
});

const generatePayroll = async () => {
    try {
        await axios.post(route('staff.payroll.generate'), { ...payrollForm, campus_id: payrollForm.campus_id || null });
        alert.success('Payroll generated successfully.');
        router.reload({ only: ['payrollRuns'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to generate payroll.');
    }
};

const markPayrollPaid = async (item: PayrollItem) => {
    if (item.status === 'paid') return;

    const result = await alert.confirm(
        `Mark salary payment as paid for ${item.staff_profile?.user?.name ?? 'this staff member'}?`,
        'Mark Salary Paid',
        'Mark Paid',
    );
    if (!result.isConfirmed) return;

    try {
        await axios.post(route('staff.payroll.items.pay', item.id), {
            payment_method: item.payment_method || 'bank',
            reference_no: item.reference_no || null,
        });
        alert.success('Salary payment marked successfully.');
        router.reload({ only: ['payrollRuns'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to mark salary as paid.');
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Payroll" />

        <div class="space-y-6 p-4 md:p-6">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Payroll</h1>
                <p class="mt-1 text-sm text-muted-foreground">Generate a monthly run, then release it. Posts straight into the finance journals.</p>
            </div>

            <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Campus</label>
                        <select v-model="payrollForm.campus_id" :class="selectClass">
                            <option value="">All Campuses</option>
                            <option v-for="campus in props.campuses" :key="campus.id" :value="String(campus.id)">{{ campus.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Month</label>
                        <select v-model="payrollForm.payroll_month_id" :class="selectClass">
                            <option value="">Select month</option>
                            <option v-for="month in props.months" :key="month.id" :value="String(month.id)">{{ month.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Year</label>
                        <Input v-model="payrollForm.payroll_year" type="number" min="2020" max="2100" />
                    </div>
                    <div class="xl:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Title</label>
                        <Input v-model="payrollForm.title" placeholder="Optional payroll title" />
                    </div>
                </div>
                <div class="mt-4">
                    <Button @click="generatePayroll">
                        <Icon icon="wallet" class="h-4 w-4" />
                        Generate Payroll
                    </Button>
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-muted-foreground">Filter by Campus</label>
                <select v-model="campusFilter" :class="selectClass" style="max-width: 260px">
                    <option value="">All Campuses</option>
                    <option v-for="campus in props.campuses" :key="campus.id" :value="String(campus.id)">{{ campus.name }}</option>
                </select>
            </div>

            <div class="space-y-4">
                <div v-for="run in filteredRuns" :key="run.id" class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                    <div class="flex flex-col gap-4 border-b border-border px-5 py-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-foreground">{{ run.title }}</h3>
                            <p class="text-sm text-muted-foreground">{{ run.month?.name || 'Month' }} {{ run.payroll_year }} | {{ run.campus?.name || 'All Campuses' }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-sm">
                            <span :class="run.status === 'paid' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'" class="inline-flex rounded-full px-2.5 py-1 font-medium uppercase">{{ run.status }}</span>
                            <span class="font-medium text-muted-foreground">Net: {{ formatMoney(run.total_net) }}</span>
                            <Button variant="outline" size="sm" @click="expandedRunId = expandedRunId === run.id ? null : run.id">
                                {{ expandedRunId === run.id ? 'Hide Items' : 'Show Items' }}
                            </Button>
                        </div>
                    </div>

                    <div class="grid gap-4 border-b border-border px-5 py-4 text-sm md:grid-cols-3">
                        <div>Gross: <span class="font-medium text-foreground">{{ formatMoney(run.total_gross) }}</span></div>
                        <div>Deductions: <span class="font-medium text-destructive">{{ formatMoney(run.total_deductions) }}</span></div>
                        <div>Processed: <span class="font-medium text-foreground">{{ formatDate(run.processed_at) }}</span></div>
                    </div>

                    <div v-if="expandedRunId === run.id" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Staff</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Gross</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Deduction</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Net</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-for="item in run.items" :key="item.id" class="hover:bg-accent">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-foreground">{{ item.staff_profile?.user?.name || '-' }}</div>
                                        <div class="text-xs text-muted-foreground">{{ item.staff_profile?.employee_no || '-' }}</div>
                                        <div v-if="item.notes" class="text-xs text-warning">{{ item.notes }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">{{ formatMoney(item.gross_salary) }}</td>
                                    <td class="px-4 py-3 text-sm text-destructive">{{ formatMoney(item.deduction_amount) }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-primary">{{ formatMoney(item.net_salary) }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="item.status === 'paid' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'" class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium uppercase">{{ item.status }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div v-if="props.can.approve" class="flex flex-wrap justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                :class="item.status === 'paid' ? tableActionButtonClass.view : tableActionButtonClass.activate"
                                                :disabled="item.status === 'paid'"
                                                @click="markPayrollPaid(item)"
                                            >
                                                <Icon :icon="item.status === 'paid' ? 'check' : 'wallet'" class="h-3.5 w-3.5" />
                                                {{ item.status === 'paid' ? 'Paid' : 'Mark Paid' }}
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="filteredRuns.length === 0" class="rounded-2xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                    No payroll runs found yet.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
