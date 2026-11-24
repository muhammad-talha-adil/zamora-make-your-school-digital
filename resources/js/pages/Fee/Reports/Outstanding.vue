<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';

interface Props {
    summary: {
        total_outstanding: number;
        total_overdue: number;
        voucher_count: number;
        student_count: number;
    };
    byClass: Array<{
        class_id: number;
        section_id?: number | null;
        total: number;
        count: number;
        class?: { id: number; name: string };
    }>;
    topDefaulters: Array<{
        student_id: number;
        total_outstanding: number;
        overdue: number;
        voucher_count: number;
        student?: { id: number; name: string; registration_number: string };
    }>;
    classOptions: Array<{ id: number; name: string }>;
    monthOptions: Array<{ id: number; name: string }>;
    filters: {
        campus_id?: string;
        class_id?: string;
        voucher_month_id?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Reports', href: '/fee/reports' },
    { title: 'Outstanding Report', href: '/fee/reports/outstanding' },
];

const form = reactive({
    campus_id: props.filters.campus_id || '',
    class_id: props.filters.class_id || '',
    voucher_month_id: props.filters.voucher_month_id || '',
});

const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const applyFilters = () => {
    router.get(route('fee.reports.outstanding'), {
        campus_id: form.campus_id || undefined,
        class_id: form.class_id || undefined,
        voucher_month_id: form.voucher_month_id || undefined,
    });
};

const resetFilters = () => {
    form.campus_id = '';
    form.class_id = '';
    form.voucher_month_id = '';
    router.get(route('fee.reports.outstanding'));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Outstanding Report" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-foreground">
                    Outstanding Report
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Unpaid and partially paid vouchers summary
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
                    <div class="flex items-end gap-2">
                        <Button @click="applyFilters">Apply Filters</Button>
                        <Button variant="outline" @click="resetFilters">Reset</Button>
                        <a :href="route('fee.reports.outstanding', { ...props.filters, export: 1 })" class="inline-flex h-9 items-center rounded-md border border-input px-3 text-sm text-foreground">Export CSV</a>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <div class="bg-card rounded-lg border border-border p-4">
                    <p class="text-sm text-muted-foreground">Total Outstanding</p>
                    <p class="text-2xl font-bold text-destructive">{{ formatCurrency(props.summary.total_outstanding) }}</p>
                </div>
                <div class="bg-card rounded-lg border border-border p-4">
                    <p class="text-sm text-muted-foreground">Overdue</p>
                    <p class="text-2xl font-bold text-destructive">{{ formatCurrency(props.summary.total_overdue) }}</p>
                </div>
                <div class="bg-card rounded-lg border border-border p-4">
                    <p class="text-sm text-muted-foreground">Total Vouchers</p>
                    <p class="text-2xl font-bold text-foreground">{{ props.summary.voucher_count }}</p>
                </div>
                <div class="bg-card rounded-lg border border-border p-4">
                    <p class="text-sm text-muted-foreground">Students with Dues</p>
                    <p class="text-2xl font-bold text-foreground">{{ props.summary.student_count }}</p>
                </div>
            </div>

            <!-- By Class -->
            <div class="bg-card rounded-lg border border-border p-4">
                <h3 class="text-lg font-semibold text-foreground mb-4">Outstanding by Class</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left py-2 text-muted-foreground">Class</th>
                                <th class="text-right py-2 text-muted-foreground">Vouchers</th>
                                <th class="text-right py-2 text-muted-foreground">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in props.byClass" :key="`${item.class_id}-${item.section_id}`" class="border-b border-border">
                                <td class="py-2 text-foreground">{{ item.class?.name || 'N/A' }}</td>
                                <td class="py-2 text-right text-muted-foreground">{{ item.count }}</td>
                                <td class="py-2 text-right text-destructive font-medium">{{ formatCurrency(item.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="props.byClass.length === 0" class="text-center text-muted-foreground py-4">
                        No outstanding vouchers
                    </div>
                </div>
            </div>

            <!-- Top Defaulters -->
            <div class="bg-card rounded-lg border border-border p-4">
                <h3 class="text-lg font-semibold text-foreground mb-4">Top Defaulters</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left py-2 text-muted-foreground">#</th>
                                <th class="text-left py-2 text-muted-foreground">Student</th>
                                <th class="text-right py-2 text-muted-foreground">Vouchers</th>
                                <th class="text-right py-2 text-muted-foreground">Overdue</th>
                                <th class="text-right py-2 text-muted-foreground">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(defaulter, defaulterIndex) in props.topDefaulters" :key="defaulter.student_id" class="border-b border-border">
                                <td class="py-2 text-muted-foreground">{{ defaulterIndex + 1 }}</td>
                                <td class="py-2">
                                    <div class="text-foreground font-medium">
                                        {{ defaulter.student?.name || 'N/A' }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ defaulter.student?.registration_number }}
                                    </div>
                                </td>
                                <td class="py-2 text-right text-muted-foreground">{{ defaulter.voucher_count }}</td>
                                <td class="py-2 text-right text-muted-foreground">{{ formatCurrency(defaulter.overdue) }}</td>
                                <td class="py-2 text-right text-destructive font-medium">{{ formatCurrency(defaulter.total_outstanding) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="props.topDefaulters.length === 0" class="text-center text-muted-foreground py-4">
                        No defaulters found
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
