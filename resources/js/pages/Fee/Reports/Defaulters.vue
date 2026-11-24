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
    student_id: number;
    class_name: string;
    overdue: number;
    outstanding: number;
    not_yet_due: number;
    overdue_since: string | null;
    days_overdue: number;
    voucher_count: number;
    student?: {
        id: number;
        name: string;
        registration_number: string;
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
    classOptions: Array<{ id: number; name: string }>;
    monthOptions: Array<{ id: number; name: string }>;
    totals: { overdue: number; outstanding: number };
    filters: {
        campus_id?: string;
        class_id?: string;
        voucher_month_id?: string;
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
    voucher_month_id: props.filters.voucher_month_id || '',
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

const applyFilters = () => {
    router.get(route('fee.reports.defaulters'), {
        campus_id: form.campus_id || undefined,
        class_id: form.class_id || undefined,
        voucher_month_id: form.voucher_month_id || undefined,
        min_days_overdue: form.min_days_overdue || undefined,
    });
};

const resetFilters = () => {
    form.campus_id = '';
    form.class_id = '';
    form.voucher_month_id = '';
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
                <div class="grid gap-4 md:grid-cols-5">
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
                        <select id="class_id" v-model="form.class_id" class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">All classes</option>
                            <option v-for="c in props.classOptions" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="voucher_month_id">Month</Label>
                        <select id="voucher_month_id" v-model="form.voucher_month_id" class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">All months</option>
                            <option v-for="m in props.monthOptions" :key="m.id" :value="String(m.id)">{{ m.name }}</option>
                        </select>
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
                        <a :href="route('fee.reports.defaulters', { ...props.filters, export: 1 })" class="inline-flex h-9 items-center rounded-md border border-input px-3 text-sm text-foreground">Export CSV</a>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="bg-card rounded-lg border border-border p-4">
                <p class="text-sm text-muted-foreground">
                    Total Defaulters: <span class="font-medium text-foreground">{{ props.defaulters.total }}</span>
                    &middot; Overdue <span class="font-medium text-foreground">{{ formatCurrency(props.totals.overdue) }}</span>
                    &middot; Total outstanding <span class="font-medium text-foreground">{{ formatCurrency(props.totals.outstanding) }}</span>
                </p>
            </div>

            <!-- Defaulters Table -->
            <div class="bg-card rounded-lg border border-border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-muted">
                            <tr>
                                <th class="text-left py-3 px-4 text-muted-foreground">#</th>
                                <th class="text-left py-3 px-4 text-muted-foreground">Student</th>
                                <th class="text-left py-3 px-4 text-muted-foreground">Class</th>
                                <th class="text-right py-3 px-4 text-muted-foreground">Overdue Since</th>
                                <th class="text-right py-3 px-4 text-muted-foreground">Days Overdue</th>
                                <th class="text-right py-3 px-4 text-muted-foreground">Overdue Amount</th>
                                <th class="text-right py-3 px-4 text-muted-foreground">Total Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(defaulter, defaulterIndex) in props.defaulters.data" :key="defaulter.id" class="border-t border-border">
                                <td class="py-3 px-4 text-muted-foreground">
                                    {{ (props.defaulters.current_page - 1) * props.defaulters.per_page + defaulterIndex + 1 }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="text-foreground font-medium">{{ defaulter.student?.name || 'N/A' }}</div>
                                    <div class="text-xs text-muted-foreground">{{ defaulter.student?.registration_number }}</div>
                                </td>
                                <td class="py-3 px-4 text-muted-foreground">{{ defaulter.class_name }}</td>
                                <td class="py-3 px-4 text-right text-muted-foreground">{{ defaulter.overdue_since ? formatDate(defaulter.overdue_since) : '-' }}</td>
                                <td class="py-3 px-4 text-right"><span class="text-warning font-medium">{{ defaulter.days_overdue }} days</span></td>
                                <td class="py-3 px-4 text-right text-destructive font-medium">{{ formatCurrency(defaulter.overdue) }}</td>
                                <td class="py-3 px-4 text-right text-foreground">{{ formatCurrency(defaulter.outstanding) }}</td>
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
