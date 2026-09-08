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
        voucher_count: number;
        student_count: number;
    };
    byClass: Array<{
        class_id: number;
        total: number;
        count: number;
        class?: { id: number; name: string };
    }>;
    topDefaulters: Array<{
        student_id: number;
        total_outstanding: number;
        voucher_count: number;
        student?: { id: number; name: string; registration_number: string };
    }>;
    filters: {
        campus_id?: string;
        class_id?: string;
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
    });
};

const resetFilters = () => {
    form.campus_id = '';
    form.class_id = '';
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
                <div class="grid gap-4 md:grid-cols-3">
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
                    <div class="flex items-end gap-2">
                        <Button @click="applyFilters">Apply Filters</Button>
                        <Button variant="outline" @click="resetFilters">Reset</Button>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <div class="bg-card rounded-lg border border-border p-4">
                    <p class="text-sm text-muted-foreground">Total Outstanding</p>
                    <p class="text-2xl font-bold text-destructive">{{ formatCurrency(props.summary.total_outstanding) }}</p>
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
                            <tr v-for="item in props.byClass" :key="item.class_id" class="border-b border-border">
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
                                <th class="text-left py-2 text-muted-foreground">Student</th>
                                <th class="text-right py-2 text-muted-foreground">Vouchers</th>
                                <th class="text-right py-2 text-muted-foreground">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="defaulter in props.topDefaulters" :key="defaulter.student_id" class="border-b border-border">
                                <td class="py-2">
                                    <div class="text-foreground font-medium">
                                        {{ defaulter.student?.name || 'N/A' }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ defaulter.student?.registration_number }}
                                    </div>
                                </td>
                                <td class="py-2 text-right text-muted-foreground">{{ defaulter.voucher_count }}</td>
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
