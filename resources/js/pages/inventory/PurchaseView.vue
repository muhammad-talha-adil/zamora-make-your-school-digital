<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { formatDate, formatCurrency } from '@/utils';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
// import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';

interface Props {
    purchaseId: number;
}

const props = defineProps<Props>();

const purchase = ref<any>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const fetchPurchase = async () => {
    loading.value = true;
    error.value = null;
    
    try {
        const response = await axios.get(`/inventory/purchases/${props.purchaseId}`);
        purchase.value = response.data;
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Failed to load purchase details';
        console.error('Error fetching purchase:', err);
    } finally {
        loading.value = false;
    }
};

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory', href: '/inventory' },
    { title: 'Purchases', href: '/inventory/purchases-manage' },
    { title: 'View Purchase', href: '#' },
];

onMounted(() => {
    fetchPurchase();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="View Purchase" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Purchase Details
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        View purchase information and items
                    </p>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <Button variant="outline" @click="router.visit('/inventory/purchases-manage')" class="w-full sm:w-auto">
                        <Icon icon="arrow-left" class="mr-2" />
                        Back to Purchases
                    </Button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-12">
                <Icon icon="loader" class="h-8 w-8 animate-spin text-primary" />
                <span class="ml-2">Loading purchase details...</span>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-center gap-2 text-red-600 dark:text-red-400">
                    <Icon icon="alert-circle" class="h-5 w-5" />
                    <span>{{ error }}</span>
                </div>
            </div>

            <!-- Purchase Details -->
            <div v-else-if="purchase" class="space-y-6">
                <!-- Purchase Info Card -->
                <div class="bg-card rounded-lg border p-4 md:p-6 space-y-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b pb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ purchase.purchase_id }}
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Created {{ formatDate(purchase.created_at) }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Purchase Date</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ formatDate(purchase.purchase_date) }}
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Campus</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ purchase.campus_name || 'All Campuses' }}
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Supplier</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ purchase.supplier?.name || 'N/A' }}
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Amount</label>
                            <p class="text-2xl font-bold text-green-600">
                                {{ formatCurrency(purchase.total_amount) }}
                            </p>
                        </div>
                    </div>

                    <div v-if="purchase.note">
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Notes</label>
                        <p class="text-gray-900 dark:text-white">{{ purchase.note }}</p>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="bg-card rounded-lg border overflow-hidden">
                    <div class="px-4 md:px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Purchase Items ({{ purchase.items?.length || 0 }})
                        </h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Item</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Quantity</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Purchase Rate</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Sale Rate</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                                <tr v-for="item in purchase.items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-3 md:px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ item.inventory_item?.name || 'Unknown Item' }}
                                    </td>
                                    <td class="px-3 md:px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ item.quantity }}
                                    </td>
                                    <td class="px-3 md:px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ formatCurrency(item.purchase_rate) }}
                                    </td>
                                    <td class="px-3 md:px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ item.sale_rate ? formatCurrency(item.sale_rate) : '-' }}
                                    </td>
                                    <td class="px-3 md:px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">
                                        {{ formatCurrency(item.quantity * item.purchase_rate) }}
                                    </td>
                                </tr>
                                <tr v-if="!purchase.items || purchase.items.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                        No items found
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="purchase.items && purchase.items.length > 0" class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <td colspan="4" class="px-3 md:px-6 py-4 text-sm font-bold text-gray-900 dark:text-white text-right">
                                        Grand Total:
                                    </td>
                                    <td class="px-3 md:px-6 py-4 text-lg font-bold text-green-600">
                                        {{ formatCurrency(purchase.total_amount) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
