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
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Purchase Return #{{ props.return.id }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
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
                            <span class="text-sm text-gray-500 dark:text-gray-400">Return Number</span>
                            <p class="font-medium">{{ props.return.return_number || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Return Date</span>
                            <p class="font-medium">{{ formatDate(props.return.return_date) }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Campus</span>
                            <p class="font-medium">{{ props.return.campus?.name || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Supplier</span>
                            <p class="font-medium">{{ props.return.supplier?.name || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Original Purchase</span>
                            <p class="font-medium">{{ props.return.purchase?.purchase_id || 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Created By</span>
                            <p class="font-medium">{{ props.return.user?.name || 'N/A' }}</p>
                        </div>
                    </div>

                    <div v-if="props.return.note">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Note</span>
                        <p class="font-medium">{{ props.return.note }}</p>
                    </div>
                </div>

                <!-- Total -->
                <div class="bg-card rounded-lg border p-5 space-y-4">
                    <h2 class="text-lg font-semibold">Summary</h2>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 dark:text-gray-400">Total Items</span>
                            <span class="font-medium">{{ props.return.items.length }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 dark:text-gray-400">Total Quantity</span>
                            <span class="font-medium">{{ props.return.items.reduce((sum, item) => sum + item.quantity, 0) }}</span>
                        </div>
                        <div class="border-t pt-3 flex justify-between items-center">
                            <span class="text-lg font-semibold">Total Amount</span>
                            <span class="text-2xl font-bold text-green-600 dark:text-green-400">
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
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Item</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Quantity</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Unit Price</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reason</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                            <tr v-for="(item, index) in props.return.items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ index + 1 }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ item.inventory_item?.name || 'Unknown Item' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ item.quantity }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ formatCurrency(item.unit_price) }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ formatCurrency(item.total) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ item.reason || '-' }}</td>
                            </tr>
                            <tr v-if="props.return.items.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No items found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
