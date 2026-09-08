<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import Icon from '@/components/Icon.vue';
import type { BreadcrumbItem } from '@/types';
import { formatDate } from '@/utils/date';
import { formatCurrency } from '@/utils/currency';

interface ReturnItem {
    id: number;
    item_name_snapshot: string;
    description_snapshot: string | null;
    quantity: number;
    unit_price: number;
    return_price: number | null;
    total_amount: number;
    reason_id: number | null;
    custom_reason: string | null;
}

interface ReturnRecord {
    id: number;
    return_id: string;
    campus_id: number;
    campus_name: string;
    student_id: number;
    student_name: string;
    registration_number: string;
    total_quantity: number;
    total_amount: number;
    status: string;
    return_date: string;
    note: string | null;
    created_at: string;
    items: ReturnItem[];
}

interface Props {
    returnRecord: ReturnRecord;
}

const props = defineProps<Props>();

const breadcrumbItems = computed((): BreadcrumbItem[] => [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory', href: '/inventory' },
    { title: 'Student Inventory', href: '/inventory/student-manage' },
    { title: 'Returns', href: '/inventory/student-manage?tab=returns' },
    { title: `Return #${props.returnRecord.return_id}`, href: '#' },
]);

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'returned':
            return { label: 'Returned', variant: 'default' as const };
        case 'partial':
            return { label: 'Partial Return', variant: 'secondary' as const };
        default:
            return { label: status, variant: 'default' as const };
    }
};

const goBack = () => {
    router.visit('/inventory/student-manage?tab=returns');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Return Details" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">
                        Return Details
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Return ID: {{ props.returnRecord.return_id }}
                    </p>
                </div>
                <Button variant="outline" @click="goBack" class="w-full sm:w-auto">
                    <Icon icon="arrow-left" class="mr-2 h-4 w-4" />
                    Back to Returns
                </Button>
            </div>

            <!-- Return Info Card -->
            <div class="bg-card rounded-lg border p-4 md:p-5 space-y-4">
                <h2 class="text-lg font-semibold">Return Information</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Return ID</label>
                        <p class="text-lg font-semibold text-foreground">
                            {{ props.returnRecord.return_id }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Student Name</label>
                        <p class="text-lg font-semibold text-foreground">
                            {{ props.returnRecord.student_name }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Registration #</label>
                        <p class="text-lg font-semibold text-foreground">
                            {{ props.returnRecord.registration_number }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Campus</label>
                        <p class="text-lg font-semibold text-foreground">
                            {{ props.returnRecord.campus_name }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Return Date</label>
                        <p class="text-lg font-semibold text-foreground">
                            {{ formatDate(props.returnRecord.return_date) }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Status</label>
                        <p class="mt-1">
                            <Badge :variant="getStatusBadge(props.returnRecord.status).variant">
                                {{ getStatusBadge(props.returnRecord.status).label }}
                            </Badge>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Total Quantity</label>
                        <p class="text-lg font-semibold text-foreground">
                            {{ props.returnRecord.total_quantity }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Total Amount</label>
                        <p class="text-lg font-bold text-success">
                            {{ formatCurrency(props.returnRecord.total_amount) }}
                        </p>
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="props.returnRecord.note" class="mt-4 pt-4 border-t">
                    <label class="text-sm font-medium text-muted-foreground">Notes</label>
                    <p class="text-foreground mt-1">{{ props.returnRecord.note }}</p>
                </div>
            </div>

            <!-- Items Table -->
            <div class="bg-card rounded-lg border p-4 md:p-5 space-y-4">
                <h2 class="text-lg font-semibold">Returned Items</h2>

                <div class="border rounded-lg overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Sr#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Item</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Unit Price</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Return Price</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Reason</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="(item, index) in props.returnRecord.items" :key="item.id" class="hover:bg-accent">
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ index + 1 }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-foreground">{{ item.item_name_snapshot }}</div>
                                    <div class="text-xs text-muted-foreground" v-if="item.description_snapshot">{{ item.description_snapshot }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-foreground">{{ item.quantity }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-foreground">{{ formatCurrency(item.unit_price) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-foreground">
                                    {{ item.return_price ? formatCurrency(item.return_price) : '-' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-foreground">{{ formatCurrency(item.total_amount) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-foreground">
                                    <span v-if="item.custom_reason">{{ item.custom_reason }}</span>
                                    <span v-else-if="item.reason_id">Reason #{{ item.reason_id }}</span>
                                    <span v-else>-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
