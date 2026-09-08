<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { formatCurrency } from '@/utils/format';

// Types
interface Stats {
    totalVouchers: number;
    unpaidVouchers: number;
    overdueVouchers: number;
    totalCollected: number;
    totalOutstanding: number;
    monthlyCollection: number;
    activeDiscounts: number;
    pendingApprovals: number;
}

interface RecentPayment {
    id: number;
    receipt_no: string;
    student_name: string;
    amount: number;
    payment_date: string;
    payment_method: string;
}

interface OverdueVoucher {
    id: number;
    voucher_no: string;
    student_name: string;
    amount: number;
    due_date: string;
    days_overdue: number;
}

// Props
const props = defineProps<{
    stats?: Stats;
    recentPayments?: RecentPayment[];
    overdueVouchers?: OverdueVoucher[];
}>();

// State
const loading = ref(false);
const stats = ref<Stats>(props.stats || {
    totalVouchers: 0,
    unpaidVouchers: 0,
    overdueVouchers: 0,
    totalCollected: 0,
    totalOutstanding: 0,
    monthlyCollection: 0,
    activeDiscounts: 0,
    pendingApprovals: 0,
});
const recentPayments = ref<RecentPayment[]>(props.recentPayments || []);
const overdueVouchers = ref<OverdueVoucher[]>(props.overdueVouchers || []);

// Breadcrumbs
const breadcrumbItems: BreadcrumbItem[] = [
    { label: 'Fee Management', href: '/fee/dashboard' },
    { label: 'Dashboard' },
];

// Methods
const fetchDashboardData = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/fee/dashboard/stats');
        stats.value = response.data.stats;
        recentPayments.value = response.data.recentPayments;
        overdueVouchers.value = response.data.overdueVouchers;
    } catch (error) {
        console.error('Error fetching dashboard data:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    if (!props.stats) {
        fetchDashboardData();
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Fee Dashboard" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">
                        Fee Management Dashboard
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                        Overview of fee collection and outstanding payments
                    </p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Vouchers -->
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Total Vouchers</p>
                            <p class="text-2xl font-bold text-foreground mt-1">
                                {{ stats.totalVouchers }}
                            </p>
                        </div>
                        <div class="p-3 bg-primary/10 rounded-lg">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Unpaid Vouchers -->
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Unpaid</p>
                            <p class="text-2xl font-bold text-warning mt-1">
                                {{ stats.unpaidVouchers }}
                            </p>
                        </div>
                        <div class="p-3 bg-warning/10 rounded-lg">
                            <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Overdue Vouchers -->
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Overdue</p>
                            <p class="text-2xl font-bold text-destructive mt-1">
                                {{ stats.overdueVouchers }}
                            </p>
                        </div>
                        <div class="p-3 bg-destructive/10 rounded-lg">
                            <svg class="w-6 h-6 text-destructive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Collected -->
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="flex flex-wrap gap-2 items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Total Collected</p>
                            <p class="text-2xl font-bold text-success mt-1">
                                {{ formatCurrency(stats.totalCollected) }}
                            </p>
                        </div>
                        <div class="p-3 bg-success/10 rounded-lg">
                            <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Payments & Overdue Vouchers -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Recent Payments -->
                <div class="bg-card rounded-lg border border-border p-4">
                    <h3 class="text-lg font-semibold text-foreground mb-4">Recent Payments</h3>
                    <div class="space-y-3">
                        <div
                            v-for="payment in recentPayments"
                            :key="payment.id"
                            class="flex flex-wrap gap-2 items-center justify-between p-3 bg-muted rounded-lg"
                        >
                            <div>
                                <p class="font-medium text-foreground">{{ payment.student_name || 'N/A' }}</p>
                                <p class="text-sm text-muted-foreground">{{ payment.receipt_no }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-success">
                                    {{ formatCurrency(payment.amount) }}
                                </p>
                                <p class="text-xs text-muted-foreground">{{ payment.payment_date }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overdue Vouchers -->
                <div class="bg-card rounded-lg border border-border p-4">
                    <h3 class="text-lg font-semibold text-foreground mb-4">Overdue Vouchers</h3>
                    <div class="space-y-3">
                        <div
                            v-for="voucher in overdueVouchers"
                            :key="voucher.id"
                            class="flex flex-wrap gap-2 items-center justify-between p-3 bg-destructive/10 rounded-lg"
                        >
                            <div>
                                <p class="font-medium text-foreground">{{ voucher.student_name }}</p>
                                <p class="text-sm text-muted-foreground">{{ voucher.voucher_no }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-destructive">
                                    {{ formatCurrency(voucher.amount) }}
                                </p>
                                <p class="text-xs text-destructive">{{ voucher.days_overdue }} days overdue</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
