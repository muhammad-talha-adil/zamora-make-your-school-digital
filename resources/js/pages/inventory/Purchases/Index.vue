<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { alert, formatCurrency } from '@/utils';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';
import PurchaseForm from '@/components/forms/inventory/PurchaseForm.vue';

interface Props {
    purchases: {
        data: Array<{
            id: number;
            supplier_id: number;
            supplier: {
                id: number;
                name: string;
            } | null;
            purchase_date: string;
            total_amount: number;
            campus_id: number;
            campus_name: string;
            items_count: number;
            created_at: string;
        }>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        from: number;
        to: number;
        total: number;
    };
    campuses: Array<{
        id: number;
        name: string;
    }>;
    suppliers: Array<{
        id: number;
        name: string;
    }>;
}

const props = defineProps<Props>();

// Filter states
const perPage = ref(10);
const campusFilter = ref('');
const purchasesData = ref(props.purchases.data || []);
const pagination = ref(props.purchases);

// Modal state
const showPurchaseForm = ref(false);

// Default campus ID (first campus)
const defaultCampusId = computed(() => props.campuses[0]?.id || '');

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Inventory',
        href: '/inventory',
    },
    {
        title: 'Purchases',
        href: '/inventory/purchases',
    },
];

const fetchPurchases = (pageNum = 1) => {
    const params: Record<string, string | number> = {
        per_page: perPage.value,
        page: pageNum,
    };

    const campusId = campusFilter.value || defaultCampusId.value;
    if (campusId) params.campus_id = campusId;

    axios.get(`/inventory/purchases/all?${new URLSearchParams(params as any).toString()}`).then((response) => {
        const purchases = Array.isArray(response.data) ? response.data : [];
        purchasesData.value = purchases.map((p: any) => ({
            ...p,
            items_count: p.items_count || 0,
            total_amount: p.total_amount || 0,
        }));
        pagination.value = {
            data: purchases,
            links: [],
            from: 1,
            to: purchases.length,
            total: purchases.length,
        };
    });
};

watch([perPage, campusFilter], () => {
    fetchPurchases();
});

watch(() => props.purchases, (newPurchases) => {
    purchasesData.value = newPurchases.data || [];
    pagination.value = newPurchases;
}, { deep: true });

const viewPurchase = (purchase: any) => {
    window.location.href = `/inventory/purchases/${purchase.id}`;
};

const cancelPurchase = (purchase: any) => {
    alert
        .confirm(
            `Are you sure you want to cancel purchase #${purchase.id}?`,
            'Cancel Purchase'
        )
        .then((result) => {
            if (result.isConfirmed) {
                const reason = prompt('Please provide a reason for cancellation:');
                if (reason) {
                    axios.post('/inventory/purchases/cancel', {
                        purchase_id: purchase.id,
                        cancellation_reason: reason,
                        reverse_stock: true,
                    }).then(() => {
                        alert.success('Purchase cancelled successfully!');
                        fetchPurchases();
                    }).catch((err) => {
                        alert.error(err.response?.data?.message || 'Failed to cancel purchase. Please try again.');
                    });
                }
            }
        });
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const handlePurchaseSaved = () => {
    showPurchaseForm.value = false;
    fetchPurchases();
};

// Summary stats
const summaryStats = computed(() => {
    const purchases = purchasesData.value || [];
    return {
        totalPurchases: purchases.length,
        totalValue: purchases.reduce((sum, p) => sum + (p.total_amount || 0), 0),
        totalItems: purchases.reduce((sum, p) => sum + (p.items_count || 0), 0),
    };
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Inventory Purchases" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Inventory Purchases
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Track and manage purchase orders and stock additions.
                    </p>
                </div>
                <PurchaseForm
                    v-model:open="showPurchaseForm"
                    :campuses="props.campuses"
                    :suppliers="props.suppliers"
                    trigger="New Purchase"
                    @saved="handlePurchaseSaved"
                />
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 md:gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-xs md:text-sm text-gray-500">Total Purchases</div>
                    <div class="text-xl md:text-2xl font-bold">{{ summaryStats.totalPurchases }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-xs md:text-sm text-gray-500">Total Value</div>
                    <div class="text-xl md:text-2xl font-bold text-green-600">
                        {{ formatCurrency(summaryStats.totalValue) }}
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-xs md:text-sm text-gray-500">Total Items</div>
                    <div class="text-xl md:text-2xl font-bold">
                        {{ summaryStats.totalItems.toLocaleString() }}
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-2 md:gap-3">
                <select 
                    v-model="campusFilter" 
                    @change="() => fetchPurchases()"
                    class="w-full sm:w-44 md:w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                >
                    <option value="">All Campuses</option>
                    <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                        {{ campus.name }}
                    </option>
                </select>
            </div>

            <!-- Mobile Card View (visible only on small screens) -->
            <div class="block lg:hidden space-y-3">
                <div 
                    v-for="purchase in purchasesData" 
                    :key="purchase.id" 
                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ purchase.supplier?.name || 'N/A' }}</div>
                            <div class="text-xs text-gray-500">#{{ purchase.id }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-green-600">{{ formatCurrency(purchase.total_amount) }}</div>
                            <div class="text-xs text-gray-500">{{ purchase.items_count }} items</div>
                        </div>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">{{ formatDate(purchase.purchase_date) }}</span>
                        <Badge variant="secondary">{{ purchase.campus_name }}</Badge>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <Button variant="outline" size="sm" @click="viewPurchase(purchase)" class="flex-1">
                            <Icon icon="eye" class="mr-1" />View
                        </Button>
                        <Button variant="destructive" size="sm" @click="cancelPurchase(purchase)" class="flex-1">
                            <Icon icon="x-circle" class="mr-1" />Cancel
                        </Button>
                    </div>
                </div>
                <div v-if="purchasesData.length === 0" class="text-center py-8 text-gray-500">
                    No purchases found.
                </div>
            </div>

            <!-- Desktop Table View (visible only on large screens) -->
            <div class="hidden lg:block overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    #
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Supplier
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Date
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Campus
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Items
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Total
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="purchase in purchasesData" :key="purchase.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ purchase.id }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ purchase.supplier?.name || 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ formatDate(purchase.purchase_date) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <Badge variant="secondary">
                                        {{ purchase.campus_name }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ purchase.items_count }} items
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-bold text-green-600">
                                        {{ formatCurrency(purchase.total_amount) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                    <div class="flex flex-wrap gap-2">
                                        <Button variant="outline" size="sm" @click="viewPurchase(purchase)" class="min-h-8">
                                            <Icon icon="eye" class="mr-1" />View
                                        </Button>
                                        <Button variant="destructive" size="sm" @click="cancelPurchase(purchase)" class="min-h-8">
                                            <Icon icon="x-circle" class="mr-1" />Cancel
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div class="text-xs md:text-sm text-gray-600">
                    Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                </div>
                <div class="text-xs md:text-sm text-gray-500">
                    {{ purchasesData.length }} purchases loaded
                </div>
            </div>
        </div>
    </AppLayout>
</template>
