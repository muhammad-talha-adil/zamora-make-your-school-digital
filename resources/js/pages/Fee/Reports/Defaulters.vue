<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';

interface Defaulter {
    id: number;
    voucher_no: string;
    student_id: number;
    due_date: string;
    net_amount: number;
    paid_amount: number;
    balance_amount: number;
    status: string;
    student?: {
        id: number;
        name: string;
        registration_number: string;
        father_name: string;
    };
    voucherMonth?: {
        id: number;
        name: string;
    };
}

interface Props {
    defaulters: {
        data: Defaulter[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        campus_id?: string;
        class_id?: string;
        min_days_overdue?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Reports', href: '/fee/reports' },
    { title: 'Defaulters List', href: '/fee/reports/defaulters' },
];

const form = reactive({
    campus_id: props.filters.campus_id || '',
    class_id: props.filters.class_id || '',
    min_days_overdue: props.filters.min_days_overdue || '',
});

const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('en-PK');
};

const getDaysOverdue = (dueDate: string): number => {
    const due = new Date(dueDate);
    const today = new Date();
    const diffTime = Math.abs(today.getTime() - due.getTime());
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
};

const getStatusColor = (status: string): string => {
    const colors: Record<string, string> = {
        unpaid: 'bg-destructive/10 text-destructive',
        partial: 'bg-warning/10 text-warning',
        overdue: 'bg-warning/10 text-warning',
    };
    return colors[status] || 'bg-muted text-foreground';
};

const applyFilters = () => {
    router.get(route('fee.reports.defaulters'), {
        campus_id: form.campus_id || undefined,
        class_id: form.class_id || undefined,
        min_days_overdue: form.min_days_overdue || undefined,
    });
};

const resetFilters = () => {
    form.campus_id = '';
    form.class_id = '';
    form.min_days_overdue = '';
    router.get(route('fee.reports.defaulters'));
};

const changePage = (page: number) => {
    router.get(route('fee.reports.defaulters'), {
        ...props.filters,
        page,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Defaulters List" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-foreground">
                    Defaulters List
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Students with overdue fee payments
                </p>
            </div>

            <!-- Filters -->
            <div class="bg-card rounded-lg border border-border p-4">
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="space-y-2">
                        <Label for="campus_id">Campus</Label>
                        <Input
                            id="campus_id"
                            v-model="form.campus_id"
                            type="text"
                            placeholder="Filter by campus"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="class_id">Class</Label>
                        <Input
                            id="class_id"
                            v-model="form.class_id"
                            type="text"
                            placeholder="Filter by class"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="min_days_overdue">Min Days Overdue</Label>
                        <Input
                            id="min_days_overdue"
                            v-model="form.min_days_overdue"
                            type="number"
                            min="0"
                            placeholder="e.g. 30"
                        />
                    </div>
                    <div class="flex items-end gap-2">
                        <Button @click="applyFilters">Apply Filters</Button>
                        <Button variant="outline" @click="resetFilters">Reset</Button>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="bg-card rounded-lg border border-border p-4">
                <p class="text-sm text-muted-foreground">
                    Total Defaulters: <span class="font-medium text-foreground">{{ props.defaulters.total }}</span>
                </p>
            </div>

            <!-- Defaulters Table -->
            <div class="bg-card rounded-lg border border-border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-muted">
                            <tr>
                                <th class="text-left py-3 px-4 text-muted-foreground">Student</th>
                                <th class="text-left py-3 px-4 text-muted-foreground">Voucher No</th>
                                <th class="text-left py-3 px-4 text-muted-foreground">Month</th>
                                <th class="text-right py-3 px-4 text-muted-foreground">Due Date</th>
                                <th class="text-right py-3 px-4 text-muted-foreground">Days Overdue</th>
                                <th class="text-right py-3 px-4 text-muted-foreground">Amount</th>
                                <th class="text-center py-3 px-4 text-muted-foreground">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="defaulter in props.defaulters.data" :key="defaulter.id" class="border-t border-border">
                                <td class="py-3 px-4">
                                    <div class="text-foreground font-medium">
                                        {{ defaulter.student?.name || 'N/A' }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ defaulter.student?.registration_number }}
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-foreground">{{ defaulter.voucher_no }}</td>
                                <td class="py-3 px-4 text-muted-foreground">{{ defaulter.voucherMonth?.name }}</td>
                                <td class="py-3 px-4 text-right text-muted-foreground">{{ formatDate(defaulter.due_date) }}</td>
                                <td class="py-3 px-4 text-right">
                                    <span class="text-warning font-medium">
                                        {{ getDaysOverdue(defaulter.due_date) }} days
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right text-destructive font-medium">
                                    {{ formatCurrency(defaulter.balance_amount) }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span :class="['inline-flex items-center px-2 py-1 rounded-full text-xs font-medium', getStatusColor(defaulter.status)]">
                                        {{ defaulter.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="props.defaulters.data.length === 0" class="text-center text-muted-foreground py-8">
                        No defaulters found
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="props.defaulters.last_page > 1" class="flex justify-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="props.defaulters.current_page === 1"
                    @click="changePage(props.defaulters.current_page - 1)"
                >
                    Previous
                </Button>
                <span class="flex items-center px-4 text-sm text-muted-foreground">
                    Page {{ props.defaulters.current_page }} of {{ props.defaulters.last_page }}
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="props.defaulters.current_page === props.defaulters.last_page"
                    @click="changePage(props.defaulters.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
