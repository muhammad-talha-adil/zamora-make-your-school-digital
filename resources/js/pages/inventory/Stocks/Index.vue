<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import { formatCurrency } from '@/utils';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import type { BreadcrumbItem } from '@/types';

interface Props {
    inventoryStocks: {
        data: Array<{
            id: number;
            campus_id: number;
            campus_name: string;
            inventory_item_id: number;
            item_name: string;
            inventory_type_name: string;
            quantity: number;
            reserved_quantity: number;
            available_quantity: number;
            low_stock_threshold: number;
            is_low_stock: boolean;
            stock_status: string;
            purchase_rate: number;
            sale_rate: number;
            updated_at: string;
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
}

const props = defineProps<Props>();

// Filter states
const perPage = ref(25);
const campusFilter = ref('');
const lowStockOnly = ref(false);
const stocksData = ref(props.inventoryStocks.data || []);
const pagination = ref(props.inventoryStocks);

// Page size options
const perPageOptions = [25, 50, 75, 100];

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
        title: 'Stocks',
        href: '/inventory/stocks',
    },
];

const fetchStocks = (pageNum = 1) => {
    const params: Record<string, string | number> = {
        per_page: perPage.value,
        page: pageNum,
    };

    const campusId = campusFilter.value || defaultCampusId.value;
    if (campusId) params.campus_id = campusId;
    if (lowStockOnly.value) params.low_stock_only = 'true';

    axios.get(`/inventory/stocks/all?${new URLSearchParams(params as any).toString()}`).then((response) => {
        // Handle paginated response
        const paginatedData = response.data;
        const stocks = paginatedData.data || [];
        stocksData.value = stocks.map((stock: any) => ({
            ...stock,
            quantity: stock.quantity || 0,
            reserved_quantity: stock.reserved_quantity || 0,
            available_quantity: stock.available_quantity || 0,
        }));
        pagination.value = {
            data: stocks,
            links: paginatedData.links || [],
            from: paginatedData.from || 1,
            to: paginatedData.to || stocks.length,
            total: paginatedData.total || stocks.length,
        };
    });
};

watch([perPage, campusFilter, lowStockOnly], () => {
    fetchStocks();
});

watch(() => props.inventoryStocks, (newStocks) => {
    stocksData.value = newStocks.data || [];
    pagination.value = newStocks;
}, { deep: true });

const getStockStatusBadge = (status: string) => {
    switch (status) {
        case 'critical':
            return { label: 'Critical', variant: 'destructive' as const };
        case 'warning':
            return { label: 'Warning', variant: 'secondary' as const };
        default:
            return { label: 'Healthy', variant: 'default' as const };
    }
};

// Summary stats
const summaryStats = computed(() => {
    const stocks = stocksData.value || [];
    return {
        totalItems: stocks.length,
        totalQuantity: stocks.reduce((sum, s) => sum + (s.quantity || 0), 0),
        totalAvailable: stocks.reduce((sum, s) => sum + (s.available_quantity || 0), 0),
        lowStockCount: stocks.filter(s => s.is_low_stock).length,
        criticalCount: stocks.filter(s => s.stock_status === 'critical').length,
    };
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Inventory Stocks" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <Icon icon="package" class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                            Inventory Stocks
                        </h1>
                        <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                            View and manage stock levels across all items.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2 md:gap-4">
                <Card class="min-h-20">
                    <CardContent class="p-3 md:p-4">
                        <div class="text-lg md:text-2xl font-bold">{{ summaryStats.totalItems }}</div>
                        <div class="text-xs text-gray-500">Total Items</div>
                    </CardContent>
                </Card>
                <Card class="min-h-20">
                    <CardContent class="p-3 md:p-4">
                        <div class="text-lg md:text-2xl font-bold">{{ summaryStats.totalQuantity.toLocaleString() }}</div>
                        <div class="text-xs text-gray-500">Total Quantity</div>
                    </CardContent>
                </Card>
                <Card class="min-h-20">
                    <CardContent class="p-3 md:p-4">
                        <div class="text-lg md:text-2xl font-bold text-green-600">{{ summaryStats.totalAvailable.toLocaleString() }}</div>
                        <div class="text-xs text-gray-500">Available</div>
                    </CardContent>
                </Card>
                <Card class="min-h-20">
                    <CardContent class="p-3 md:p-4">
                        <div class="text-lg md:text-2xl font-bold text-amber-600">{{ summaryStats.lowStockCount }}</div>
                        <div class="text-xs text-gray-500">Low Stock</div>
                    </CardContent>
                </Card>
                <Card class="min-h-20">
                    <CardContent class="p-3 md:p-4">
                        <div class="text-lg md:text-2xl font-bold text-red-600">{{ summaryStats.criticalCount }}</div>
                        <div class="text-xs text-gray-500">Critical</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-2 md:gap-3 items-center">
                <select 
                    v-model="campusFilter" 
                    @change="() => fetchStocks()"
                    class="w-full sm:w-44 md:w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                >
                    <option value="">All Campuses</option>
                    <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                        {{ campus.name }}
                    </option>
                </select>
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 min-h-10 px-2">
                    <input 
                        type="checkbox" 
                        v-model="lowStockOnly" 
                        class="rounded border-gray-300 text-primary focus:ring-primary w-4 h-4" 
                    />
                    <span class="whitespace-nowrap">Low stock only</span>
                </label>
                <div class="flex items-center gap-2 ml-auto">
                    <label class="text-sm text-gray-600 dark:text-gray-400">Show:</label>
                    <select v-model="perPage" class="w-20 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-2 text-sm min-h-11">
                        <option v-for="option in perPageOptions" :key="option" :value="option">
                            {{ option }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Mobile Card View (visible only on small screens) -->
            <div class="block lg:hidden space-y-3">
                <div 
                    v-for="stock in stocksData" 
                    :key="stock.id" 
                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ stock.item_name }}</div>
                            <div class="text-xs text-gray-500">{{ stock.inventory_type_name }}</div>
                        </div>
                        <Badge :variant="getStockStatusBadge(stock.stock_status).variant">
                            {{ getStockStatusBadge(stock.stock_status).label }}
                        </Badge>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-500">Qty:</span>
                            <span class="font-medium ml-1">{{ stock.quantity }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Available:</span>
                            <span 
                                class="font-medium ml-1"
                                :class="stock.available_quantity <= 0 ? 'text-red-600' : stock.available_quantity < stock.low_stock_threshold ? 'text-amber-600' : 'text-green-600'"
                            >
                                {{ stock.available_quantity }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500">Reserved:</span>
                            <span class="font-medium ml-1">{{ stock.reserved_quantity || 0 }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Buy:</span>
                            <span class="font-medium ml-1">{{ formatCurrency(stock.purchase_rate) }}</span>
                        </div>
                    </div>
                    <div class="text-xs text-gray-400">{{ stock.campus_name }}</div>
                </div>
                <div v-if="stocksData.length === 0" class="text-center py-8 text-gray-500">
                    No stock records found.
                </div>
            </div>

            <!-- Desktop Table View (visible only on large screens) -->
            <div class="hidden lg:block overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Sr#
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Item
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Campus
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Qty
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Available
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Reserved
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Status
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Rates
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="(stock, index) in stocksData" :key="stock.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ (pagination.from || 0) + index }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ stock.item_name }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ stock.inventory_type_name }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ stock.campus_name }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ stock.quantity }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div :class="[
                                        'text-sm font-bold',
                                        stock.available_quantity <= 0 ? 'text-red-600' :
                                        stock.available_quantity < stock.low_stock_threshold ? 'text-amber-600' : 'text-green-600'
                                    ]">
                                        {{ stock.available_quantity }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ stock.reserved_quantity || 0 }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <Badge :variant="getStockStatusBadge(stock.stock_status).variant">
                                        {{ getStockStatusBadge(stock.stock_status).label }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm">
                                        <span class="text-gray-500">Buy:</span> {{ formatCurrency(stock.purchase_rate) }}
                                    </div>
                                    <div class="text-sm">
                                        <span class="text-gray-500">Sell:</span> {{ formatCurrency(stock.sale_rate) }}
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
                <div class="flex flex-wrap gap-1">
                    <Button
                        v-for="link in pagination.links"
                        :key="link.label"
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        :disabled="!link.url"
                        @click="link.url && fetchStocks(parseInt(link.url.split('=').pop() || '1'))"
                        class="min-h-9"
                    >
                        <span v-html="link.label"></span>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
