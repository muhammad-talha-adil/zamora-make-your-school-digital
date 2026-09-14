<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import LineChart from '@/components/charts/LineChart.vue';
import type { BreadcrumbItem } from '@/types';

interface Props {
    today_summary?: {
        income: number;
        expense: number;
        balance: number;
    };
    month_summary?: {
        income: number;
        expense: number;
        balance: number;
    };
    student_receivables?: {
        total_open: number;
        fee_open: number;
        inventory_open: number;
        transport_open: number;
    };
    operations_summary?: {
        pending_salary_payable: number;
        transport_expense_month: number;
    };
    campuses?: Array<{ id: number; name: string }>;
    selected_campus?: number;
    weekly_trend?: Array<{ date: string; income: number; expense: number }>;
}

const props = defineProps<Props>();

// Computed values
const todaySummary = computed(() => props.today_summary || { income: 0, expense: 0, balance: 0 });
const monthSummary = computed(() => props.month_summary || { income: 0, expense: 0, balance: 0 });
const studentReceivables = computed(() => props.student_receivables || { total_open: 0, fee_open: 0, inventory_open: 0, transport_open: 0 });
const operationsSummary = computed(() => props.operations_summary || { pending_salary_payable: 0, transport_expense_month: 0 });
const campuses = computed(() => props.campuses || []);
const weeklyTrend = computed(() => props.weekly_trend || []);
const incomeTrendPoints = computed(() =>
    weeklyTrend.value.map((point) => ({
        label: new Date(point.date).toLocaleDateString('en-US', { weekday: 'short' }),
        value: point.income,
    })),
);
const expenseTrendPoints = computed(() =>
    weeklyTrend.value.map((point) => ({
        label: new Date(point.date).toLocaleDateString('en-US', { weekday: 'short' }),
        value: point.expense,
    })),
);

// Form state
const selectedCampus = ref(props.selected_campus);

// Breadcrumbs
const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Finance', href: '/finance' },
    { title: 'Dashboard' },
];

// Format currency
const formatMoney = (amount: number | null | undefined) => {
    if (amount === null || amount === undefined) return 'Rs 0';
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(amount);
};

// Change campus
const changeCampus = () => {
    const params: Record<string, string> = {};
    if (selectedCampus.value) params.campus_id = String(selectedCampus.value);
    router.get('/finance', params);
};

// Navigate to route
const navigateTo = (path: string) => {
    router.get(path);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Finance Dashboard" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-foreground">
                        Finance Dashboard
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Overview of your financial transactions
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <select v-model="selectedCampus" @change="changeCampus" class="rounded-md border-border">
                        <option :value="undefined">All Campuses</option>
                        <option v-for="campus in campuses" :key="campus.id" :value="campus.id">{{ campus.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Today's Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-card rounded-lg border border-border p-6">
                    <p class="text-sm text-muted-foreground">Today's Income</p>
                    <p class="text-2xl font-bold text-success">{{ formatMoney(todaySummary.income) }}</p>
                </div>
                <div class="bg-card rounded-lg border border-border p-6">
                    <p class="text-sm text-muted-foreground">Today's Expense</p>
                    <p class="text-2xl font-bold text-destructive">{{ formatMoney(todaySummary.expense) }}</p>
                </div>
                <div class="bg-card rounded-lg border border-border p-6">
                    <p class="text-sm text-muted-foreground">Today's Balance</p>
                    <p class="text-2xl font-bold" :class="todaySummary.balance >= 0 ? 'text-success' : 'text-destructive'">
                        {{ formatMoney(todaySummary.balance) }}
                    </p>
                </div>
            </div>

            <!-- Month Summary -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-lg font-semibold text-foreground mb-4">This Month</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-muted-foreground">Total Income</p>
                        <p class="text-xl font-bold text-success">{{ formatMoney(monthSummary.income) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Total Expense</p>
                        <p class="text-xl font-bold text-destructive">{{ formatMoney(monthSummary.expense) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-muted-foreground">Net Balance</p>
                        <p class="text-xl font-bold" :class="monthSummary.balance >= 0 ? 'text-success' : 'text-destructive'">
                            {{ formatMoney(monthSummary.balance) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Weekly Income vs Expense -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-card rounded-lg border border-border p-4 md:p-6">
                    <h3 class="text-lg font-semibold text-foreground mb-4">Income (Last 7 Days)</h3>
                    <LineChart
                        v-if="incomeTrendPoints.length > 0"
                        :points="incomeTrendPoints"
                        :format-value="(value) => formatMoney(value)"
                        color-var="--success"
                    />
                </div>
                <div class="bg-card rounded-lg border border-border p-4 md:p-6">
                    <h3 class="text-lg font-semibold text-foreground mb-4">Expense (Last 7 Days)</h3>
                    <LineChart
                        v-if="expenseTrendPoints.length > 0"
                        :points="expenseTrendPoints"
                        :format-value="(value) => formatMoney(value)"
                        color-var="--destructive"
                    />
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-card rounded-lg border border-border p-4 md:p-6">
                <h2 class="text-lg font-semibold text-foreground mb-4">Student Receivables</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-sm text-muted-foreground">Total Outstanding</p>
                    <p class="text-xl font-bold text-warning">{{ formatMoney(studentReceivables.total_open) }}</p>
                </div>
                <div>
                        <p class="text-sm text-muted-foreground">Fee Dues</p>
                        <p class="text-xl font-bold text-primary">{{ formatMoney(studentReceivables.fee_open) }}</p>
                    </div>
                <div>
                    <p class="text-sm text-muted-foreground">Inventory Dues</p>
                    <p class="text-xl font-bold text-warning">{{ formatMoney(studentReceivables.inventory_open) }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Transport Dues</p>
                    <p class="text-xl font-bold text-primary">{{ formatMoney(studentReceivables.transport_open) }}</p>
                </div>
            </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-card rounded-lg border border-border p-4 md:p-6">
                    <h3 class="text-lg font-semibold text-foreground mb-4">Quick Actions</h3>
                    <div class="flex flex-wrap gap-2">
                        <Button @click="navigateTo('/finance/receive-payment')" size="sm">Receive Payment</Button>
                        <Button @click="navigateTo('/finance/make-payment')" size="sm">Make Payment</Button>
                        <Button variant="outline" @click="navigateTo('/finance/transactions')" size="sm">View Transactions</Button>
                        <Button variant="outline" @click="navigateTo('/finance/student-account-statement')" size="sm">Student Statement</Button>
                    </div>
                </div>
                <div class="bg-card rounded-lg border border-border p-4 md:p-6">
                    <h3 class="text-lg font-semibold text-foreground mb-4">Reports</h3>
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" @click="navigateTo('/finance/reports/cash-book')" size="sm">Cash Book</Button>
                        <Button variant="outline" @click="navigateTo('/finance/reports/income')" size="sm">Income Statement</Button>
                        <Button variant="outline" @click="navigateTo('/finance/reports/expense')" size="sm">Expense Statement</Button>
                    </div>
                </div>
                <div class="bg-card rounded-lg border border-border p-4 md:p-6">
                    <h3 class="text-lg font-semibold text-foreground mb-4">Operations</h3>
                    <div class="space-y-3 text-sm text-muted-foreground">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <span>Pending Salary Payable</span>
                            <span class="font-semibold text-destructive">{{ formatMoney(operationsSummary.pending_salary_payable) }}</span>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <span>Transport Expense This Month</span>
                            <span class="font-semibold text-warning">{{ formatMoney(operationsSummary.transport_expense_month) }}</span>
                        </div>
                        <div class="flex flex-wrap gap-2 pt-2">
                            <Button variant="outline" @click="navigateTo('/staff')" size="sm">Staff Module</Button>
                            <Button variant="outline" @click="navigateTo('/transport')" size="sm">Transport Module</Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
