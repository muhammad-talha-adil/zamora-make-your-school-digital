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
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Return Details
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
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
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Return ID</label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ props.returnRecord.return_id }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Student Name</label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ props.returnRecord.student_name }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Registration #</label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ props.returnRecord.registration_number }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Campus</label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ props.returnRecord.campus_name }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Return Date</label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ formatDate(props.returnRecord.return_date) }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
                        <p class="mt-1">
                            <Badge :variant="getStatusBadge(props.returnRecord.status).variant">
                                {{ getStatusBadge(props.returnRecord.status).label }}
                            </Badge>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Quantity</label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ props.returnRecord.total_quantity }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Amount</label>
                        <p class="text-lg font-bold text-green-600">
                            {{ formatCurrency(props.returnRecord.total_amount) }}
                        </p>
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="props.returnRecord.note" class="mt-4 pt-4 border-t">
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Notes</label>
                    <p class="text-gray-900 dark:text-white mt-1">{{ props.returnRecord.note }}</p>
                </div>
            </div>

            <!-- Items Table -->
            <div class="bg-card rounded-lg border p-4 md:p-5 space-y-4">
                <h2 class="text-lg font-semibold">Returned Items</h2>

                <div class="border rounded-lg overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Sr#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Item</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Unit Price</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Return Price</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Reason</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="(item, index) in props.returnRecord.items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ index + 1 }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ item.item_name_snapshot }}</div>
                                    <div class="text-xs text-gray-500" v-if="item.description_snapshot">{{ item.description_snapshot }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ item.quantity }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ formatCurrency(item.unit_price) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ item.return_price ? formatCurrency(item.return_price) : '-' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">{{ formatCurrency(item.total_amount) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
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
