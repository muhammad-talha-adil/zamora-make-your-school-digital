<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { formatCurrency } from '@/utils';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import Icon from '@/components/Icon.vue';
import type { BreadcrumbItem } from '@/types';

interface Props {
    purchase: {
        id: number;
        purchase_id: string;
        purchase_date: string;
        total_amount: number;
        paid_amount: number;
        due_amount: number;
        payment_status: 'unpaid' | 'partial' | 'paid';
        due_date: string | null;
        note: string | null;
        campus: {
            id: number;
            name: string;
        } | null;
        supplier: {
            id: number;
            name: string;
        } | null;
        purchase_items: Array<{
            id: number;
            quantity: number;
            purchase_rate: number;
            sale_rate: number;
            total: number;
            inventory_item: {
                id: number;
                name: string;
            } | null;
        }>;
        created_at: string;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory', href: '/inventory' },
    { title: 'Purchases', href: '/inventory/purchases' },
    { title: props.purchase.purchase_id || `Purchase #${props.purchase.id}`, href: '#' },
];

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const paymentStatusVariant = (status: string) => {
    switch (status) {
        case 'paid':
            return 'default' as const;
        case 'partial':
            return 'secondary' as const;
        default:
            return 'destructive' as const;
    }
};

const goBack = () => {
    router.visit('/inventory/purchases');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="View Purchase" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">
                        {{ props.purchase.purchase_id || `Purchase #${props.purchase.id}` }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        View purchase order details
                    </p>
                </div>
                <Button variant="outline" @click="goBack" class="min-h-11">
                    <Icon icon="arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Purchase Info -->
                <div class="bg-card rounded-lg border border-border p-5 space-y-4">
                    <h2 class="text-lg font-semibold text-foreground">Purchase Information</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-muted-foreground">Purchase Date</span>
                            <p class="font-medium text-foreground">{{ formatDate(props.purchase.purchase_date) }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Campus</span>
                            <p class="font-medium text-foreground">{{ props.purchase.campus?.name || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Supplier</span>
                            <p class="font-medium text-foreground">{{ props.purchase.supplier?.name || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Payment Status</span>
                            <p class="font-medium">
                                <Badge :variant="paymentStatusVariant(props.purchase.payment_status)">
                                    {{ props.purchase.payment_status }}
                                </Badge>
                            </p>
                        </div>
                        <div v-if="props.purchase.due_date">
                            <span class="text-sm text-muted-foreground">Due Date</span>
                            <p class="font-medium text-foreground">{{ formatDate(props.purchase.due_date) }}</p>
                        </div>
                    </div>

                    <div v-if="props.purchase.note">
                        <span class="text-sm text-muted-foreground">Note</span>
                        <p class="font-medium text-foreground whitespace-pre-line">{{ props.purchase.note }}</p>
                    </div>
                </div>

                <!-- Summary -->
                <div class="bg-card rounded-lg border border-border p-5 space-y-4">
                    <h2 class="text-lg font-semibold text-foreground">Summary</h2>

                    <div class="space-y-3">
                        <div class="flex flex-wrap gap-2 justify-between items-center">
                            <span class="text-muted-foreground">Total Items</span>
                            <span class="font-medium text-foreground">{{ props.purchase.purchase_items.length }}</span>
                        </div>
                        <div class="flex flex-wrap gap-2 justify-between items-center">
                            <span class="text-muted-foreground">Total Quantity</span>
                            <span class="font-medium text-foreground">
                                {{ props.purchase.purchase_items.reduce((sum, item) => sum + item.quantity, 0) }}
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-2 justify-between items-center">
                            <span class="text-muted-foreground">Paid Amount</span>
                            <span class="font-medium text-success">{{ formatCurrency(props.purchase.paid_amount || 0) }}</span>
                        </div>
                        <div class="flex flex-wrap gap-2 justify-between items-center">
                            <span class="text-muted-foreground">Due Amount</span>
                            <span class="font-medium text-destructive">{{ formatCurrency(props.purchase.due_amount || 0) }}</span>
                        </div>
                        <div class="border-t border-border pt-3 flex flex-wrap gap-2 justify-between items-center">
                            <span class="text-lg font-semibold text-foreground">Total Amount</span>
                            <span class="text-2xl font-bold text-success">
                                {{ formatCurrency(props.purchase.total_amount) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="bg-card rounded-lg border border-border p-5 space-y-4">
                <h2 class="text-lg font-semibold text-foreground">Purchase Items</h2>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Item</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Quantity</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Purchase Rate</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Sale Rate</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="(item, index) in props.purchase.purchase_items" :key="item.id" class="hover:bg-accent">
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ index + 1 }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-foreground">
                                    {{ item.inventory_item?.name || 'Unknown Item' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ item.quantity }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ formatCurrency(item.purchase_rate) }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ formatCurrency(item.sale_rate) }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-foreground">{{ formatCurrency(item.total) }}</td>
                            </tr>
                            <tr v-if="props.purchase.purchase_items.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-muted-foreground">No items found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
