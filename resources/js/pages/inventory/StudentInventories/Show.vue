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
                    <h1 class="text-2xl font-bold text-foreground">
                        Student Inventory Assignment
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
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
                        <h2 class="text-xl font-bold text-foreground">
                            {{ studentInventory.student_name }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Reg # {{ studentInventory.registration_number }} | {{ studentInventory.class_name || 'N/A' }} {{ studentInventory.section_name ? '- ' + studentInventory.section_name : '' }}
                        </p>
                        <p class="text-sm text-muted-foreground mt-1">
                            Created {{ formatDate(studentInventory.created_at) }}
                        </p>
                    </div>
                    <Badge :variant="getStatusBadge(studentInventory.status).variant" class="text-sm">
                        {{ getStatusBadge(studentInventory.status).label }}
                    </Badge>
                </div>

                <!-- Summary Stats -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="p-4 bg-primary/10 rounded-lg text-center">
                        <div class="text-sm text-muted-foreground">Campus</div>
                        <div class="text-lg font-semibold text-foreground">{{ studentInventory.campus_name || 'N/A' }}</div>
                    </div>
                    <div class="p-4 bg-success/10 rounded-lg text-center">
                        <div class="text-sm text-muted-foreground">Total Quantity</div>
                        <div class="text-2xl font-bold text-foreground">{{ totalQuantity }}</div>
                    </div>
                    <div class="p-4 bg-warning/10 rounded-lg text-center">
                        <div class="text-sm text-muted-foreground">Returned</div>
                        <div class="text-2xl font-bold text-warning">{{ totalReturned }}</div>
                    </div>
                    <div class="p-4 bg-primary/10 rounded-lg text-center">
                        <div class="text-sm text-muted-foreground">Remaining</div>
                        <div class="text-2xl font-bold text-primary">{{ totalRemaining }}</div>
                    </div>
                </div>

                <!-- Value Summary -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Total Amount</label>
                        <p class="text-lg font-semibold text-foreground">
                            {{ formatCurrency(studentInventory.total_amount) }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Total Discount</label>
                        <p class="text-lg font-semibold text-success">
                            {{ formatCurrency(studentInventory.total_discount) }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Final Amount</label>
                        <p class="text-xl font-bold text-foreground">
                            {{ formatCurrency(studentInventory.final_amount) }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Total Value</label>
                        <p class="text-xl font-bold text-success">
                            {{ formatCurrency(totalValue) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Items Table Below -->
            <div class="bg-card rounded-lg border overflow-hidden">
                <div class="px-4 md:px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-foreground">
                        Item Details
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Sr#</th>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Item</th>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Description</th>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Qty</th>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Returned</th>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Remaining</th>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Unit Price</th>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Total</th>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Assigned Date</th>
                                <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="(item, index) in studentInventory.items" :key="item.id" class="hover:bg-accent">
                                <td class="px-3 md:px-6 py-4 text-sm text-foreground">{{ index + 1 }}</td>
                                <td class="px-3 md:px-6 py-4 text-sm text-foreground">
                                    {{ item.item_name_snapshot }}
                                </td>
                                <td class="px-3 md:px-6 py-4 text-sm text-muted-foreground">
                                    {{ item.description_snapshot || '-' }}
                                </td>
                                <td class="px-3 md:px-6 py-4 text-sm text-foreground">
                                    {{ item.quantity }}
                                </td>
                                <td class="px-3 md:px-6 py-4 text-sm text-foreground">
                                    {{ item.returned_quantity }}
                                </td>
                                <td class="px-3 md:px-6 py-4 text-sm font-medium text-foreground">
                                    {{ item.remaining_quantity }}
                                </td>
                                <td class="px-3 md:px-6 py-4 text-sm text-foreground">
                                    {{ formatCurrency(item.unit_price_snapshot) }}
                                </td>
                                <td class="px-3 md:px-6 py-4 text-sm font-bold text-foreground">
                                    {{ formatCurrency(item.total_value) }}
                                </td>
                                <td class="px-3 md:px-6 py-4 text-sm text-muted-foreground">
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
