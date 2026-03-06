<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { alert } from '@/utils';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import type { BreadcrumbItem } from '@/types';
import InventoryTypeForm from '@/components/forms/InventoryTypeForm.vue';
import ItemForm from '@/components/forms/inventory/ItemForm.vue';

interface Props {
    inventoryTypes: any;
    inventoryItems: any;
    inventoryStocks: any;
    campuses: any;
}

const props = defineProps<Props>();
const activeTab = ref('stocks');

// Filter states
const showInactiveTypes = ref(false);
const campusFilter = ref('');
const lowStockOnly = ref(false);

// Data refs
const inventoryTypesData = ref(props.inventoryTypes.data || []);
const paginationTypes = ref(props.inventoryTypes);
const inventoryItemsData = ref(props.inventoryItems.data || []);
const paginationItems = ref(props.inventoryItems);
const stocksData = ref(props.inventoryStocks.data || []);
const paginationStocks = ref(props.inventoryStocks);

// Per page options
const perPageOptions = [25, 50, 75, 100];
const perPageTypes = ref(25);
const perPageItems = ref(25);
const perPageStocks = ref(25);

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory', href: '/inventory' },
    { title: 'Items & Stock', href: '/inventory/items-stock' },
];

// Type methods
const fetchInventoryTypes = () => {
    const params: Record<string, any> = { 
        per_page: perPageTypes.value,
        show_inactive: showInactiveTypes.value 
    };
    if (campusFilter.value) params.campus_id = campusFilter.value;
    
    axios.get('/inventory/types/paginated', { params }).then((response) => {
        inventoryTypesData.value = response.data.data;
        paginationTypes.value = response.data;
    });
};

const toggleActiveType = (type: any) => {
    const action = type.is_active ? 'inactivate' : 'activate';
    alert.confirm(`Are you sure you want to ${action} "${type.name}"?`, `${action.charAt(0).toUpperCase() + action.slice(1)} Inventory Type`)
        .then((result) => {
            if (result.isConfirmed) {
                router.patch(route('inventory.types.inactivate', type.id), {}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success(`Inventory type ${action}d successfully!`);
                        fetchInventoryTypes();
                    },
                });
            }
        });
};

// Item methods
const fetchInventoryItems = () => {
    const params: Record<string, any> = { per_page: perPageItems.value };
    if (campusFilter.value) params.campus_id = campusFilter.value;
    
    axios.get('/inventory/items/paginated', { params }).then((response) => {
        const paginatedData = response.data;
        inventoryItemsData.value = paginatedData.data || [];
        paginationItems.value = paginatedData;
    });
};

const toggleActiveItem = (item: any) => {
    const action = item.is_active ? 'inactivate' : 'activate';
    alert.confirm(`Are you sure you want to ${action} "${item.name}"?`, `${action.charAt(0).toUpperCase() + action.slice(1)} Inventory Item`)
        .then((result) => {
            if (result.isConfirmed) {
                router.patch(route('inventory.items.inactivate', item.id), {}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success(`Inventory item ${action}d successfully!`);
                        fetchInventoryItems();
                    },
                });
            }
        });
};

// Stock methods
const fetchStocks = () => {
    const params: Record<string, any> = { per_page: perPageStocks.value };
    if (campusFilter.value) params.campus_id = campusFilter.value;
    if (lowStockOnly.value) params.low_stock_only = 'true';
    
    axios.get('/inventory/stocks/all', { params }).then((response) => {
        const paginatedData = response.data;
        stocksData.value = paginatedData.data || [];
        paginationStocks.value = paginatedData;
    });
};

const getStockStatusBadge = (stock: any) => {
    if (stock.available_quantity <= 0) return { label: 'Critical', variant: 'destructive' as const };
    if (stock.available_quantity < stock.low_stock_threshold) return { label: 'Warning', variant: 'secondary' as const };
    return { label: 'Healthy', variant: 'default' as const };
};

// Summary stats - computed based on current data
const getSummaryStats = () => ({
    totalItems: inventoryItemsData.value.length,
    totalStock: stocksData.value.reduce((sum: number, s: any) => sum + (s.available_quantity || 0), 0),
    lowStock: stocksData.value.filter((s: any) => s.available_quantity < s.low_stock_threshold).length,
});

// Watch for tab changes - just for potential future use, no automatic fetching
watch(activeTab, () => {
    // Data is loaded from props initially, no need to fetch on tab change
});

// Pagination methods
const loadTypesPage = (url: string) => {
    axios.get(url).then((response: any) => {
        inventoryTypesData.value = response.data.data;
        paginationTypes.value = response.data;
    });
};

const loadItemsPage = (url: string) => {
    axios.get(url).then((response: any) => {
        const paginatedData = response.data;
        inventoryItemsData.value = paginatedData.data || [];
        paginationItems.value = paginatedData;
    });
};

const loadStocksPage = (url: string) => {
    axios.get(url).then((response: any) => {
        const paginatedData = response.data;
        stocksData.value = paginatedData.data || [];
        paginationStocks.value = paginatedData;
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Items & Stock" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Items & Stock
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Manage inventory types, items, and stock levels.
                </p>
            </div>

            <!-- Tabs - Horizontal scroll on mobile -->
            <div class="border-b border-gray-200 overflow-x-auto">
                <nav class="-mb-px flex min-w-full space-x-6">
                    <button
                        @click="activeTab = 'stocks'"
                        :class="[
                            activeTab === 'stocks'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                            'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                        ]"
                    >
                        <Icon icon="warehouse" class="inline mr-1" />
                        Stocks
                    </button>
                    <button
                        @click="activeTab = 'items'"
                        :class="[
                            activeTab === 'items'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                            'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                        ]"
                    >
                        <Icon icon="package" class="inline mr-1" />
                        Items
                    </button>
                    <button
                        @click="activeTab = 'adjustments'"
                        :class="[
                            activeTab === 'adjustments'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                            'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                        ]"
                    >
                        <Icon icon="sliders" class="inline mr-1" />
                        Adjustments
                    </button>
                    <button
                        @click="activeTab = 'types'"
                        :class="[
                            activeTab === 'types'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                            'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                        ]"
                    >
                        <Icon icon="tags" class="inline mr-1" />
                        Types
                    </button>
                </nav>
            </div>

            <!-- Filters & Actions Bar -->
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div class="flex flex-wrap gap-3 flex-1">
                    <select v-model="campusFilter" @change="() => {
                        if (activeTab === 'types') fetchInventoryTypes();
                        if (activeTab === 'items') fetchInventoryItems();
                        if (activeTab === 'stocks') fetchStocks();
                    }" class="w-full sm:w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm">
                        <option value="">All Campuses</option>
                        <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                            {{ campus.name }}
                        </option>
                    </select>
                    
                    <Button v-if="activeTab === 'stocks'" :variant="lowStockOnly ? 'default' : 'outline'" size="sm" @click="lowStockOnly = !lowStockOnly; fetchStocks()">
                        <Icon :icon="lowStockOnly ? 'eye-off' : 'eye'" class="mr-1" />
                        Low Stock
                    </Button>
                </div>
                
                <InventoryTypeForm v-if="activeTab === 'types'" :campuses="props.campuses" @saved="fetchInventoryTypes" />
                <ItemForm v-if="activeTab === 'items'" :campuses="props.campuses" :inventory-types="inventoryTypesData" @saved="fetchInventoryItems" />
                <div v-if="activeTab === 'types'" class="flex items-center gap-2">
                    <label class="text-sm text-gray-600 dark:text-gray-400">Show:</label>
                    <select v-model="perPageTypes" @change="fetchInventoryTypes" class="w-20 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-2 text-sm">
                        <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }}</option>
                    </select>
                </div>
                <div v-if="activeTab === 'items'" class="flex items-center gap-2">
                    <label class="text-sm text-gray-600 dark:text-gray-400">Show:</label>
                    <select v-model="perPageItems" @change="fetchInventoryItems" class="w-20 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-2 text-sm">
                        <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }}</option>
                    </select>
                </div>
                <div v-if="activeTab === 'stocks'" class="flex items-center gap-2">
                    <label class="text-sm text-gray-600 dark:text-gray-400">Show:</label>
                    <select v-model="perPageStocks" @change="fetchStocks" class="w-20 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-2 text-sm">
                        <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }}</option>
                    </select>
                </div>
            </div>

            <!-- ==================== TYPES TAB ==================== -->
            <div v-if="activeTab === 'types'">
                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900 -mx-4 md:mx-0">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Sr#</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Name</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Campus</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Items</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Status</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="(type, index) in inventoryTypesData" :key="type.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 md:px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ (paginationTypes.from || 0) + index }}</td>
                                <td class="px-4 md:px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ type.name }}</td>
                                <td class="px-4 md:px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ type.campus_name }}</td>
                                <td class="px-4 md:px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <Badge variant="secondary">{{ type.items_count }} items</Badge>
                                </td>
                                <td class="px-4 md:px-6 py-4">
                                    <Badge :variant="type.is_active ? 'default' : 'destructive'">
                                        {{ type.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </td>
                                <td class="px-4 md:px-6 py-4">
                                    <div class="flex gap-2">
                                        <InventoryTypeForm :inventory-type="type" :campuses="props.campuses" trigger="" variant="outline" size="sm" @saved="fetchInventoryTypes" />
                                        <Button :variant="type.is_active ? 'destructive' : 'default'" size="sm" @click="toggleActiveType(type)">
                                            <Icon :icon="type.is_active ? 'eye-off' : 'eye'" class="mr-1" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Types Pagination -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Showing {{ paginationTypes.from }} to {{ paginationTypes.to }} of {{ paginationTypes.total }} entries
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <Button
                            v-for="link in paginationTypes.links"
                            :key="link.label"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            :disabled="!link.url"
                            @click="link.url && loadTypesPage(link.url)"
                            class="min-h-8"
                        >
                            <span v-html="link.label"></span>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- ==================== ITEMS TAB ==================== -->
            <div v-if="activeTab === 'items'">
                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900 -mx-4 md:mx-0">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Sr#</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Name</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Type</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Campus</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Status</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="(item, index) in inventoryItemsData" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 md:px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ (paginationItems.from || 0) + index }}</td>
                                <td class="px-4 md:px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ item.name }}</td>
                                <td class="px-4 md:px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ item.inventory_type_name }}</td>
                                <td class="px-4 md:px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ item.campus_name }}</td>
                                <td class="px-4 md:px-6 py-4">
                                    <Badge :variant="item.is_active ? 'default' : 'destructive'">
                                        {{ item.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </td>
                                <td class="px-4 md:px-6 py-4">
                                    <div class="flex gap-2">
                                        <ItemForm 
                                            :inventory-item="item" 
                                            :campuses="props.campuses" 
                                            :inventory-types="inventoryTypesData" 
                                            trigger="" 
                                            variant="outline" 
                                            size="sm" 
                                            @saved="fetchInventoryItems" 
                                        />
                                        <Button :variant="item.is_active ? 'destructive' : 'default'" size="sm" @click="toggleActiveItem(item)">
                                            <Icon :icon="item.is_active ? 'eye-off' : 'eye'" class="mr-1" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Items Pagination -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Showing {{ paginationItems.from }} to {{ paginationItems.to }} of {{ paginationItems.total }} entries
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <Button
                            v-for="link in paginationItems.links"
                            :key="link.label"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            :disabled="!link.url"
                            @click="link.url && loadItemsPage(link.url)"
                            class="min-h-8"
                        >
                            <span v-html="link.label"></span>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- ==================== STOCKS TAB ==================== -->
            <div v-if="activeTab === 'stocks'">
                <!-- Summary Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <Card><CardContent class="p-4"><div class="text-2xl font-bold">{{ getSummaryStats().totalItems }}</div><div class="text-xs text-gray-500">Total Items</div></CardContent></Card>
                    <Card><CardContent class="p-4"><div class="text-2xl font-bold">{{ getSummaryStats().totalStock }}</div><div class="text-xs text-gray-500">Total Stock</div></CardContent></Card>
                    <Card><CardContent class="p-4"><div class="text-2xl font-bold text-amber-600">{{ getSummaryStats().lowStock }}</div><div class="text-xs text-gray-500">Low Stock</div></CardContent></Card>
                    <Card><CardContent class="p-4"><div class="text-2xl font-bold text-green-600">{{ getSummaryStats().totalItems - getSummaryStats().lowStock }}</div><div class="text-xs text-gray-500">Healthy</div></CardContent></Card>
                </div>
                
                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900 -mx-4 md:mx-0">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Sr#</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Item</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Campus</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Quantity</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Available</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Reserved</th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 whitespace-nowrap">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="(stock, index) in stocksData" :key="stock.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 md:px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ (paginationStocks.from || 0) + index }}</td>
                                <td class="px-4 md:px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ stock.item_name }}</td>
                                <td class="px-4 md:px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ stock.campus_name }}</td>
                                <td class="px-4 md:px-6 py-4 text-sm text-gray-900 dark:text-white">{{ stock.quantity }}</td>
                                <td class="px-4 md:px-6 py-4 text-sm font-bold" :class="stock.available_quantity <= 0 ? 'text-red-600' : stock.available_quantity < stock.low_stock_threshold ? 'text-amber-600' : 'text-green-600'">
                                    {{ stock.available_quantity }}
                                </td>
                                <td class="px-4 md:px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ stock.reserved_quantity || 0 }}</td>
                                <td class="px-4 md:px-6 py-4">
                                    <Badge :variant="getStockStatusBadge(stock).variant">{{ getStockStatusBadge(stock).label }}</Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Stocks Pagination -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Showing {{ paginationStocks.from }} to {{ paginationStocks.to }} of {{ paginationStocks.total }} entries
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <Button
                            v-for="link in paginationStocks.links"
                            :key="link.label"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            :disabled="!link.url"
                            @click="link.url && loadStocksPage(link.url)"
                            class="min-h-8"
                        >
                            <span v-html="link.label"></span>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- ==================== ADJUSTMENTS TAB ==================== -->
            <div v-if="activeTab === 'adjustments'">
                <div class="text-center py-12 text-gray-500">
                    <Icon icon="sliders" class="w-12 h-12 mx-auto mb-4 text-gray-400" />
                    <p>Stock Adjustments</p>
                    <p class="text-sm">Manage manual stock adjustments here.</p>
                    <Button class="mt-4">
                        <Icon icon="plus" class="mr-1" /> New Adjustment
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
