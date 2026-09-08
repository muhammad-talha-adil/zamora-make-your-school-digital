<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
// import { ref } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import type { BreadcrumbItem } from '@/types';

interface Props {
    return: {
        id: number;
        return_number: string;
        return_date: string;
        note: string;
        total_amount: number;
        campus: {
            id: number;
            name: string;
        } | null;
        supplier: {
            id: number;
            name: string;
        } | null;
        user: {
            id: number;
            name: string;
        } | null;
        purchase: {
            id: number;
            purchase_id: string;
        } | null;
        items: Array<{
            id: number;
            quantity: number;
            unit_price: number;
            total: number;
            reason: string | null;
            inventory_item: {
                id: number;
                name: string;
            } | null;
        }>;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory', href: '/inventory' },
    { title: 'Purchases', href: '/inventory/purchases-manage' },
    { title: 'Purchase Returns', href: '/inventory/purchases-manage?tab=returns' },
    { title: `Return #${props.return.id}`, href: '#' },
];

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
    }).format(amount);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-PK', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const goBack = () => {
    router.visit('/inventory/purchases-manage?tab=returns');
};

const editReturn = () => {
    router.visit(`/inventory/purchase-returns/${props.return.id}/edit`);
};

const deleteReturn = () => {
    if (confirm(`Are you sure you want to delete purchase return #${props.return.id}?`)) {
        axios.delete(`/inventory/purchase-returns/${props.return.id}`)
            .then(() => {
                alert('Purchase return deleted successfully!');
                goBack();
            })
            .catch((error) => {
                alert(error.response?.data?.message || 'Failed to delete purchase return');
            });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="View Purchase Return" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-wrap gap-2 items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">
                        Purchase Return #{{ props.return.id }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        View purchase return details
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="goBack">
                        <Icon icon="arrow-left" class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                    <Button @click="editReturn">
                        <Icon icon="edit" class="mr-2 h-4 w-4" />
                        Edit
                    </Button>
                    <Button variant="destructive" @click="deleteReturn">
                        <Icon icon="trash-2" class="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Return Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Info -->
                <div class="bg-card rounded-lg border p-5 space-y-4">
                    <h2 class="text-lg font-semibold">Return Information</h2>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-muted-foreground">Return Number</span>
                            <p class="font-medium">{{ props.return.return_number || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Return Date</span>
                            <p class="font-medium">{{ formatDate(props.return.return_date) }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Campus</span>
                            <p class="font-medium">{{ props.return.campus?.name || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Supplier</span>
                            <p class="font-medium">{{ props.return.supplier?.name || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Original Purchase</span>
                            <p class="font-medium">{{ props.return.purchase?.purchase_id || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-muted-foreground">Created By</span>
                            <p class="font-medium">{{ props.return.user?.name || 'N/A' }}</p>
                        </div>
                    </div>

                    <div v-if="props.return.note">
                        <span class="text-sm text-muted-foreground">Note</span>
                        <p class="font-medium">{{ props.return.note }}</p>
                    </div>
                </div>

                <!-- Total -->
                <div class="bg-card rounded-lg border p-5 space-y-4">
                    <h2 class="text-lg font-semibold">Summary</h2>
                    
                    <div class="space-y-3">
                        <div class="flex flex-wrap gap-2 justify-between items-center">
                            <span class="text-muted-foreground">Total Items</span>
                            <span class="font-medium">{{ props.return.items.length }}</span>
                        </div>
                        <div class="flex flex-wrap gap-2 justify-between items-center">
                            <span class="text-muted-foreground">Total Quantity</span>
                            <span class="font-medium">{{ props.return.items.reduce((sum, item) => sum + item.quantity, 0) }}</span>
                        </div>
                        <div class="border-t pt-3 flex flex-wrap gap-2 justify-between items-center">
                            <span class="text-lg font-semibold">Total Amount</span>
                            <span class="text-2xl font-bold text-success">
                                {{ formatCurrency(props.return.total_amount) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="bg-card rounded-lg border p-5 space-y-4">
                <h2 class="text-lg font-semibold">Return Items</h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Item</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Quantity</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Unit Price</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Reason</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="(item, index) in props.return.items" :key="item.id" class="hover:bg-accent">
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ index + 1 }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-foreground">
                                    {{ item.inventory_item?.name || 'Unknown Item' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ item.quantity }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ formatCurrency(item.unit_price) }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-foreground">{{ formatCurrency(item.total) }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ item.reason || '-' }}</td>
                            </tr>
                            <tr v-if="props.return.items.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-muted-foreground">No items found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
