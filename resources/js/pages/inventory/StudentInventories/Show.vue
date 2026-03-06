<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';
import { formatDate, formatCurrency } from '@/utils';
import { computed } from 'vue';

interface Props {
    studentInventory: {
        id: number;
        campus_id: number;
        campus_name: string;
        student_id: number;
        student_name: string;
        registration_number: string;
        class_name: string;
        section_name: string;
        total_amount: number;
        total_discount: number;
        final_amount: number;
        assigned_date: string;
        status: string;
        created_at: string;
        items: Array<{
            id: number;
            inventory_item_id: number;
            item_name_snapshot: string;
            description_snapshot: string;
            unit_price_snapshot: number;
            discount_amount: number;
            discount_percentage: number;
            quantity: number;
            returned_quantity: number;
            remaining_quantity: number;
            total_value: number;
            inventory_item: {
                id: number;
                name: string;
            } | null;
        }>;
    };
}

const props = defineProps<Props>();

// Compute totals from items
const totalQuantity = computed(() => props.studentInventory.items.reduce((sum, item) => sum + item.quantity, 0));
const totalReturned = computed(() => props.studentInventory.items.reduce((sum, item) => sum + item.returned_quantity, 0));
const totalRemaining = computed(() => props.studentInventory.items.reduce((sum, item) => sum + item.remaining_quantity, 0));
const totalValue = computed(() => props.studentInventory.items.reduce((sum, item) => sum + item.total_value, 0));

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory', href: '/inventory' },
    { title: 'Student Inventory', href: '/inventory/student-manage' },
    { title: 'View Assignment', href: '#' },
];

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'assigned':
            return { label: 'Assigned', variant: 'default' as const };
        case 'partial_return':
            return { label: 'Partial Return', variant: 'secondary' as const };
        case 'returned':
            return { label: 'Returned', variant: 'outline' as const };
        default:
            return { label: status, variant: 'default' as const };
    }
};

defineOptions({
    inheritAttrs: false
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="View Student Inventory" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Student Inventory Assignment
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        View inventory assignment details
                    </p>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <Button variant="outline" @click="router.visit('/inventory/student-manage')" class="w-full sm:w-auto">
                        <Icon icon="arrow-left" class="mr-2" />
                        Back to List
                    </Button>
                </div>
            </div>

            <!-- Summary Section at Top -->
            <div class="bg-card rounded-lg border p-4 md:p-6 space-y-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ studentInventory.student_name }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Reg # {{ studentInventory.registration_number }} | {{ studentInventory.class_name || 'N/A' }} {{ studentInventory.section_name ? '- ' + studentInventory.section_name : '' }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Created {{ formatDate(studentInventory.created_at) }}
                        </p>
                    </div>
                    <Badge :variant="getStatusBadge(studentInventory.status).variant" class="text-sm">
                        {{ getStatusBadge(studentInventory.status).label }}
                    </Badge>
                </div>

                <!-- Summary Stats -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Campus</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ studentInventory.campus_name || 'N/A' }}</div>
                    </div>
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Quantity</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ totalQuantity }}</div>
                    </div>
                    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Returned</div>
                        <div class="text-2xl font-bold text-amber-600">{{ totalReturned }}</div>
                    </div>
                    <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Remaining</div>
                        <div class="text-2xl font-bold text-purple-600">{{ totalRemaining }}</div>
                    </div>
                </div>

                <!-- Value Summary -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Amount</label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ formatCurrency(studentInventory.total_amount) }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Discount</label>
                        <p class="text-lg font-semibold text-green-600">
                            {{ formatCurrency(studentInventory.total_discount) }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Final Amount</label>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ formatCurrency(studentInventory.final_amount) }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Value</label>
                        <p class="text-xl font-bold text-green-600">
                            {{ formatCurrency(totalValue) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Items Table Below -->
            <div class="bg-card rounded-lg border overflow-hidden">
                <div class="px-4 md:px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Item Details
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Sr#</th>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Item</th>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Description</th>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Qty</th>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Returned</th>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Remaining</th>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Unit Price</th>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Total</th>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Assigned Date</th>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                            <tr v-for="(item, index) in studentInventory.items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-3 md:px-6 py-4 text-sm text-gray-900 dark:text-white">{{ index + 1 }}</td>
                                <td class="px-3 md:px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ item.item_name_snapshot }}
                                </td>
                                <td class="px-3 md:px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ item.description_snapshot || '-' }}
                                </td>
                                <td class="px-3 md:px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ item.quantity }}
                                </td>
                                <td class="px-3 md:px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ item.returned_quantity }}
                                </td>
                                <td class="px-3 md:px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ item.remaining_quantity }}
                                </td>
                                <td class="px-3 md:px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ formatCurrency(item.unit_price_snapshot) }}
                                </td>
                                <td class="px-3 md:px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">
                                    {{ formatCurrency(item.total_value) }}
                                </td>
                                <td class="px-3 md:px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ formatDate(studentInventory.assigned_date) }}
                                </td>
                                <td class="px-3 md:px-6 py-4">
                                    <Badge :variant="getStatusBadge(studentInventory.status).variant">
                                        {{ getStatusBadge(studentInventory.status).label }}
                                    </Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
