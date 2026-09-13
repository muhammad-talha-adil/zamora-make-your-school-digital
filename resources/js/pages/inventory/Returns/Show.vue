<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { formatCurrency } from '@/utils';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import Icon from '@/components/Icon.vue';
import type { BreadcrumbItem } from '@/types';

interface Props {
    return: {
        id: number;
        campus: {
            id: number;
            name: string;
        } | null;
        student: {
            id: number;
            name: string | null;
            registration_no: string | null;
        } | null;
        student_inventory_id: number;
        item_name: string | null;
        description: string | null;
        quantity: number;
        unit_price: number | null;
        final_unit_price: number;
        discount: {
            discount_amount?: number;
            discount_percentage?: number;
        } | null;
        total_value: number;
        return_date: string;
        note: string | null;
        is_partial_return: boolean;
        created_at: string;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory', href: '/inventory' },
    { title: 'Returns', href: '/inventory/returns' },
    { title: `Return #${props.return.id}`, href: '#' },
];

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const goBack = () => {
    router.visit('/inventory/returns');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="View Inventory Return" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">
                        Return #{{ props.return.id }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        View returned inventory item details
                    </p>
                </div>
                <Button variant="outline" @click="goBack" class="min-h-11">
                    <Icon icon="arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Return Info -->
                <div class="bg-card rounded-lg border border-border p-5 space-y-4">
                    <h2 class="text-lg font-semibold text-foreground">Return Information</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-muted-foreground">Return Date</span>
                            <p class="font-medium text-foreground">{{ formatDate(props.return.return_date) }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Campus</span>
                            <p class="font-medium text-foreground">{{ props.return.campus?.name || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Student</span>
                            <p class="font-medium text-foreground">{{ props.return.student?.name || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Registration No.</span>
                            <p class="font-medium text-foreground">{{ props.return.student?.registration_no || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Return Type</span>
                            <p class="font-medium text-foreground">
                                <Badge :variant="props.return.is_partial_return ? 'secondary' : 'default'">
                                    {{ props.return.is_partial_return ? 'Partial Return' : 'Full Return' }}
                                </Badge>
                            </p>
                        </div>
                    </div>

                    <div v-if="props.return.note">
                        <span class="text-sm text-muted-foreground">Reason / Note</span>
                        <p class="font-medium text-foreground">{{ props.return.note }}</p>
                    </div>
                </div>

                <!-- Summary -->
                <div class="bg-card rounded-lg border border-border p-5 space-y-4">
                    <h2 class="text-lg font-semibold text-foreground">Summary</h2>

                    <div class="space-y-3">
                        <div class="flex flex-wrap gap-2 justify-between items-center">
                            <span class="text-muted-foreground">Quantity Returned</span>
                            <span class="font-medium text-foreground">{{ props.return.quantity }}</span>
                        </div>
                        <div class="flex flex-wrap gap-2 justify-between items-center">
                            <span class="text-muted-foreground">Unit Price</span>
                            <span class="font-medium text-foreground">{{ formatCurrency(props.return.unit_price || 0) }}</span>
                        </div>
                        <div v-if="props.return.discount?.discount_percentage || props.return.discount?.discount_amount" class="flex flex-wrap gap-2 justify-between items-center">
                            <span class="text-muted-foreground">Discount</span>
                            <span class="font-medium text-destructive">
                                {{ props.return.discount?.discount_percentage ? `${props.return.discount.discount_percentage}%` : formatCurrency(props.return.discount?.discount_amount || 0) }}
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-2 justify-between items-center">
                            <span class="text-muted-foreground">Final Unit Price</span>
                            <span class="font-medium text-foreground">{{ formatCurrency(props.return.final_unit_price) }}</span>
                        </div>
                        <div class="border-t border-border pt-3 flex flex-wrap gap-2 justify-between items-center">
                            <span class="text-lg font-semibold text-foreground">Total Value</span>
                            <span class="text-2xl font-bold text-success">
                                {{ formatCurrency(props.return.total_value) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Item Details -->
            <div class="bg-card rounded-lg border border-border p-5 space-y-4">
                <h2 class="text-lg font-semibold text-foreground">Item Details</h2>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Item</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Description</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Quantity</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr class="hover:bg-accent">
                                <td class="px-4 py-3 text-sm font-medium text-foreground">
                                    {{ props.return.item_name || 'Unknown Item' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">
                                    {{ props.return.description || '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ props.return.quantity }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-foreground">
                                    {{ formatCurrency(props.return.total_value) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
