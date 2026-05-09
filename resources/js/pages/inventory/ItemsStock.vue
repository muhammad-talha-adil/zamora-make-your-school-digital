<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import axios from 'axios';
import { alert } from '@/utils';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem } from '@/types';
import { tableActionButtonClass } from '@/utils/table-actions';
import InventoryTypeForm from '@/components/forms/InventoryTypeForm.vue';
import ItemForm from '@/components/forms/inventory/ItemForm.vue';
import AdjustmentForm from '@/components/forms/inventory/AdjustmentForm.vue';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedResponse<T> {
    data: T[];
    from?: number;
    to?: number;
    total?: number;
    links?: PaginationLink[];
}

interface Campus {
    id: number;
    name: string;
}

interface InventoryTypeRow {
    id: number;
    name: string;
    campus_id: number | null;
    campus_name: string;
    items_count: number;
    is_active: boolean;
}

interface InventoryItemRow {
    id: number;
    name: string;
    description?: string | null;
    campus_id: number | null;
    campus_name: string;
    inventory_type_id: number | null;
    inventory_type_name: string;
    is_active: boolean;
    current_stock?: number;
}

interface InventoryStockRow {
    id: number;
    campus_id: number;
    campus_name: string;
    inventory_item_id: number;
    item_name: string;
    quantity: number;
    reserved_quantity: number;
    available_quantity: number;
    low_stock_threshold: number;
    is_low_stock: boolean;
    stock_status: 'critical' | 'warning' | 'healthy';
}

interface InventoryAdjustmentRow {
    id: number;
    campus_id: number;
    campus_name: string;
    inventory_item_id: number;
    item_name: string;
    type: 'add' | 'subtract' | 'set';
    quantity: number;
    previous_quantity: number;
    new_quantity: number;
    reason: string;
    reference_number?: string | null;
    created_at: string;
}

interface Props {
    inventoryTypes: PaginatedResponse<InventoryTypeRow>;
    inventoryItems: PaginatedResponse<InventoryItemRow>;
    inventoryStocks: PaginatedResponse<InventoryStockRow>;
    allInventoryTypes: InventoryTypeRow[];
    allInventoryItems: InventoryItemRow[];
    campuses: Campus[];
}

const props = defineProps<Props>();

const activeTab = ref<'stocks' | 'items' | 'adjustments' | 'types'>('stocks');
const campusFilter = ref('');
const statusFilter = ref<'active' | 'inactive'>('active');
const searchQuery = ref('');
const lowStockOnly = ref(false);
const adjustmentTypeFilter = ref('');
const adjustmentItemFilter = ref('');

const perPageOptions = [25, 50, 75, 100];
const perPageTypes = ref(25);
const perPageItems = ref(25);
const perPageStocks = ref(25);

const inventoryTypesData = ref(props.inventoryTypes.data || []);
const paginationTypes = ref(props.inventoryTypes);
const inventoryItemsData = ref(props.inventoryItems.data || []);
const paginationItems = ref(props.inventoryItems);
const stocksData = ref(props.inventoryStocks.data || []);
const paginationStocks = ref(props.inventoryStocks);
const adjustmentsData = ref<InventoryAdjustmentRow[]>([]);
const loadingAdjustments = ref(false);

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory', href: '/inventory' },
    { title: 'Items & Stock', href: '/inventory/items-stock' },
];

const adjustmentItems = computed(() => {
    if (!campusFilter.value) {
        return props.allInventoryItems;
    }

    return props.allInventoryItems.filter((item) => String(item.campus_id) === campusFilter.value);
});

const summaryStats = computed(() => ({
    totalItems: stocksData.value.length,
    totalStock: stocksData.value.reduce((sum, stock) => sum + Number(stock.quantity || 0), 0),
    availableStock: stocksData.value.reduce((sum, stock) => sum + Number(stock.available_quantity || 0), 0),
    lowStock: stocksData.value.filter((stock) => stock.is_low_stock).length,
}));

const getStockStatusBadge = (stock: InventoryStockRow) => {
    if (stock.available_quantity <= 0) {
        return { label: 'Critical', className: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' };
    }

    if (stock.available_quantity < stock.low_stock_threshold) {
        return { label: 'Warning', className: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' };
    }

    return { label: 'Healthy', className: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' };
};

const getStatusBadge = (isActive: boolean) => {
    return isActive
        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
        : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
};

const getAdjustmentBadge = (type: string) => {
    const map: Record<string, string> = {
        add: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        subtract: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        set: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    };

    return map[type] || map.set;
};

const formatAdjustmentType = (type: string) => {
    const map: Record<string, string> = {
        add: 'Add',
        subtract: 'Subtract',
        set: 'Set',
    };

    return map[type] || type;
};

const formatDateTime = (value: string) => {
    return new Date(value).toLocaleString('en-PK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const fetchInventoryTypes = (url?: string) => {
    const params: Record<string, string | number> = {
        per_page: perPageTypes.value,
        status: statusFilter.value,
    };

    if (campusFilter.value) params.campus_id = campusFilter.value;
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim();

    axios.get(url || route('inventory.types.paginated'), { params }).then((response) => {
        inventoryTypesData.value = response.data.data || [];
        paginationTypes.value = response.data;
    });
};

const fetchInventoryItems = (url?: string) => {
    const params: Record<string, string | number> = {
        per_page: perPageItems.value,
        status: statusFilter.value,
    };

    if (campusFilter.value) params.campus_id = campusFilter.value;
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim();

    axios.get(url || route('inventory.items.paginated'), { params }).then((response) => {
        inventoryItemsData.value = response.data.data || [];
        paginationItems.value = response.data;
    });
};

const fetchStocks = (url?: string) => {
    const params: Record<string, string | number | boolean> = {
        per_page: perPageStocks.value,
    };

    if (campusFilter.value) params.campus_id = campusFilter.value;
    if (lowStockOnly.value) params.low_stock_only = true;
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim();

    axios.get(url || route('inventory.stocks.all'), { params }).then((response) => {
        stocksData.value = response.data.data || [];
        paginationStocks.value = response.data;
    });
};

const fetchAdjustments = () => {
    loadingAdjustments.value = true;

    const params: Record<string, string> = {};
    if (campusFilter.value) params.campus_id = campusFilter.value;
    if (adjustmentTypeFilter.value) params.type = adjustmentTypeFilter.value;
    if (adjustmentItemFilter.value) params.item_id = adjustmentItemFilter.value;

    axios.get(route('inventory.adjustments.all'), { params }).then((response) => {
        adjustmentsData.value = response.data || [];
    }).finally(() => {
        loadingAdjustments.value = false;
    });
};

const applyFilters = () => {
    if (activeTab.value === 'types') {
        fetchInventoryTypes();
        return;
    }

    if (activeTab.value === 'items') {
        fetchInventoryItems();
        return;
    }

    if (activeTab.value === 'stocks') {
        fetchStocks();
        return;
    }

    fetchAdjustments();
};

const onTabChange = (tab: 'stocks' | 'items' | 'adjustments' | 'types') => {
    activeTab.value = tab;
    searchQuery.value = '';
    statusFilter.value = 'active';

    if (tab === 'adjustments' && adjustmentsData.value.length === 0) {
        fetchAdjustments();
    }
};

const toggleTypeStatus = (type: InventoryTypeRow) => {
    const action = type.is_active ? 'inactivate' : 'activate';
    const endpoint = type.is_active ? route('inventory.types.inactivate', type.id) : route('inventory.types.activate', type.id);

    alert.confirm(
        `Are you sure you want to ${action} "${type.name}"?`,
        `${action.charAt(0).toUpperCase() + action.slice(1)} Inventory Type`,
    ).then((result) => {
        if (!result.isConfirmed) return;

        axios.patch(endpoint).then(() => {
            alert.success(`Inventory type ${action}d successfully!`);
            fetchInventoryTypes();
        }).catch(() => {
            alert.error('Failed to update inventory type status.');
        });
    });
};

const toggleItemStatus = (item: InventoryItemRow) => {
    const action = item.is_active ? 'inactivate' : 'activate';
    const endpoint = item.is_active ? route('inventory.items.inactivate', item.id) : route('inventory.items.activate', item.id);

    alert.confirm(
        `Are you sure you want to ${action} "${item.name}"?`,
        `${action.charAt(0).toUpperCase() + action.slice(1)} Inventory Item`,
    ).then((result) => {
        if (!result.isConfirmed) return;

        axios.patch(endpoint).then(() => {
            alert.success(`Inventory item ${action}d successfully!`);
            fetchInventoryItems();
        }).catch(() => {
            alert.error('Failed to update inventory item status.');
        });
    });
};

const loadTypesPage = (url: string) => fetchInventoryTypes(url);
const loadItemsPage = (url: string) => fetchInventoryItems(url);
const loadStocksPage = (url: string) => fetchStocks(url);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Items & Stock" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-lg font-bold text-gray-900 dark:text-white md:text-2xl">
                        Items & Stock
                    </h1>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 md:text-sm">
                        Manage inventory types, items, live stock levels, and stock adjustments.
                    </p>
                </div>
            </div>

            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px grid grid-cols-2 gap-x-4 gap-y-1 md:grid-cols-4">
                    <button
                        type="button"
                        @click="onTabChange('stocks')"
                        :class="[
                            activeTab === 'stocks'
                                ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                            'flex items-center justify-center border-b-2 px-2 py-3 text-sm font-medium whitespace-nowrap'
                        ]"
                    >
                        <Icon icon="warehouse" class="mr-2 h-4 w-4" />
                        Stocks
                    </button>
                    <button
                        type="button"
                        @click="onTabChange('items')"
                        :class="[
                            activeTab === 'items'
                                ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                            'flex items-center justify-center border-b-2 px-2 py-3 text-sm font-medium whitespace-nowrap'
                        ]"
                    >
                        <Icon icon="package" class="mr-2 h-4 w-4" />
                        Items
                    </button>
                    <button
                        type="button"
                        @click="onTabChange('adjustments')"
                        :class="[
                            activeTab === 'adjustments'
                                ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                            'flex items-center justify-center border-b-2 px-2 py-3 text-sm font-medium whitespace-nowrap'
                        ]"
                    >
                        <Icon icon="sliders" class="mr-2 h-4 w-4" />
                        Adjustments
                    </button>
                    <button
                        type="button"
                        @click="onTabChange('types')"
                        :class="[
                            activeTab === 'types'
                                ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                            'flex items-center justify-center border-b-2 px-2 py-3 text-sm font-medium whitespace-nowrap'
                        ]"
                    >
                        <Icon icon="tags" class="mr-2 h-4 w-4" />
                        Types
                    </button>
                </nav>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <div class="space-y-2">
                        <Label for="campus-filter">Campus</Label>
                        <select
                            id="campus-filter"
                            v-model="campusFilter"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">All Campuses</option>
                            <option v-for="campus in props.campuses" :key="campus.id" :value="String(campus.id)">
                                {{ campus.name }}
                            </option>
                        </select>
                    </div>

                    <div v-if="['types', 'items'].includes(activeTab)" class="space-y-2">
                        <Label for="status-filter">Status</Label>
                        <select
                            id="status-filter"
                            v-model="statusFilter"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div v-if="activeTab === 'stocks'" class="space-y-2">
                        <Label for="stock-visibility">Stock View</Label>
                        <select
                            id="stock-visibility"
                            v-model="lowStockOnly"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option :value="false">All Stock</option>
                            <option :value="true">Low Stock Only</option>
                        </select>
                    </div>

                    <div v-if="activeTab === 'stocks'" class="space-y-2 md:col-span-2 xl:col-span-2">
                        <Label for="stock-search-filter">Search</Label>
                        <Input
                            id="stock-search-filter"
                            v-model="searchQuery"
                            placeholder="Search stock by item name..."
                        />
                    </div>

                    <div v-if="activeTab === 'adjustments'" class="space-y-2">
                        <Label for="adjustment-type-filter">Adjustment Type</Label>
                        <select
                            id="adjustment-type-filter"
                            v-model="adjustmentTypeFilter"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">All Types</option>
                            <option value="add">Add</option>
                            <option value="subtract">Subtract</option>
                            <option value="set">Set</option>
                        </select>
                    </div>

                    <div v-if="activeTab === 'adjustments'" class="space-y-2 md:col-span-2">
                        <Label for="adjustment-item-filter">Item</Label>
                        <select
                            id="adjustment-item-filter"
                            v-model="adjustmentItemFilter"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">All Items</option>
                            <option v-for="item in adjustmentItems" :key="item.id" :value="String(item.id)">
                                {{ item.name }}
                            </option>
                        </select>
                    </div>

                    <div v-if="['types', 'items'].includes(activeTab)" class="space-y-2 md:col-span-3">
                        <Label for="search-filter">Search</Label>
                        <Input
                            id="search-filter"
                            v-model="searchQuery"
                            :placeholder="activeTab === 'types' ? 'Search inventory types...' : 'Search inventory items...'"
                        />
                    </div>

                    <div class="flex flex-col gap-3 md:col-span-2 xl:col-span-5 xl:flex-row xl:items-end xl:justify-between">
                        <div class="flex flex-wrap items-end gap-2">
                            <Button @click="applyFilters">
                                <Icon icon="search" class="mr-2 h-4 w-4" />
                                Load {{ activeTab.charAt(0).toUpperCase() + activeTab.slice(1) }}
                            </Button>
                            <div v-if="activeTab === 'types'" class="flex items-center gap-2">
                                <Label for="per-page-types" class="text-sm">Show</Label>
                                <select
                                    id="per-page-types"
                                    v-model="perPageTypes"
                                    class="w-20 rounded-md border border-gray-300 bg-white px-2 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }}</option>
                                </select>
                            </div>
                            <div v-if="activeTab === 'items'" class="flex items-center gap-2">
                                <Label for="per-page-items" class="text-sm">Show</Label>
                                <select
                                    id="per-page-items"
                                    v-model="perPageItems"
                                    class="w-20 rounded-md border border-gray-300 bg-white px-2 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }}</option>
                                </select>
                            </div>
                            <div v-if="activeTab === 'stocks'" class="flex items-center gap-2">
                                <Label for="per-page-stocks" class="text-sm">Show</Label>
                                <select
                                    id="per-page-stocks"
                                    v-model="perPageStocks"
                                    class="w-20 rounded-md border border-gray-300 bg-white px-2 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <InventoryTypeForm
                                v-if="activeTab === 'types'"
                                :campuses="props.campuses"
                                @saved="fetchInventoryTypes()"
                            />
                            <ItemForm
                                v-if="activeTab === 'items'"
                                :campuses="props.campuses"
                                :inventory-types="props.allInventoryTypes"
                                @saved="fetchInventoryItems()"
                            />
                            <AdjustmentForm
                                v-if="activeTab === 'adjustments'"
                                :campuses="props.campuses"
                                :inventory-items="props.allInventoryItems"
                                @saved="fetchAdjustments()"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'stocks'" class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ summaryStats.totalItems }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Tracked Stock Rows</div>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ summaryStats.totalStock }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Total Quantity</div>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                    <div class="text-2xl font-bold text-green-600">{{ summaryStats.availableStock }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Available Quantity</div>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                    <div class="text-2xl font-bold text-amber-600">{{ summaryStats.lowStock }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Low Stock Alerts</div>
                </div>
            </div>

            <div v-if="activeTab === 'types'" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Sr#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Campus</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Items</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="(type, index) in inventoryTypesData" :key="type.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ ((paginationTypes.from || 1) - 1) + index + 1 }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ type.name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ type.campus_name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ type.items_count }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['rounded-full px-2 py-1 text-xs font-medium', getStatusBadge(type.is_active)]">
                                        {{ type.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <InventoryTypeForm
                                            :inventory-type="type"
                                            :campuses="props.campuses"
                                            trigger="Edit"
                                            variant="outline"
                                            size="sm"
                                            @saved="fetchInventoryTypes()"
                                        />
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            :class="type.is_active ? tableActionButtonClass.deactivate : tableActionButtonClass.activate"
                                            @click="toggleTypeStatus(type)"
                                        >
                                            <Icon :icon="type.is_active ? 'eye-off' : 'eye'" class="mr-1 h-3 w-3" />
                                            {{ type.is_active ? 'Inactive' : 'Active' }}
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="activeTab === 'items'" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Sr#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Campus</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Current Stock</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="(item, index) in inventoryItemsData" :key="item.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ ((paginationItems.from || 1) - 1) + index + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ item.name }}</div>
                                    <div v-if="item.description" class="text-xs text-gray-500">{{ item.description }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ item.inventory_type_name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ item.campus_name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ item.current_stock ?? 0 }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['rounded-full px-2 py-1 text-xs font-medium', getStatusBadge(item.is_active)]">
                                        {{ item.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <ItemForm
                                            :inventory-item="item"
                                            :campuses="props.campuses"
                                            :inventory-types="props.allInventoryTypes"
                                            trigger="Edit"
                                            variant="outline"
                                            size="sm"
                                            @saved="fetchInventoryItems()"
                                        />
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            :class="item.is_active ? tableActionButtonClass.deactivate : tableActionButtonClass.activate"
                                            @click="toggleItemStatus(item)"
                                        >
                                            <Icon :icon="item.is_active ? 'eye-off' : 'eye'" class="mr-1 h-3 w-3" />
                                            {{ item.is_active ? 'Inactive' : 'Active' }}
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="activeTab === 'stocks'" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Sr#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Item</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Campus</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Quantity</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Available</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Reserved</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Threshold</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="(stock, index) in stocksData" :key="stock.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ ((paginationStocks.from || 1) - 1) + index + 1 }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ stock.item_name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ stock.campus_name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ stock.quantity }}</td>
                                <td class="px-4 py-3 text-sm font-semibold" :class="stock.available_quantity <= 0 ? 'text-red-600 dark:text-red-400' : stock.is_low_stock ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400'">
                                    {{ stock.available_quantity }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ stock.reserved_quantity }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ stock.low_stock_threshold }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['rounded-full px-2 py-1 text-xs font-medium', getStockStatusBadge(stock).className]">
                                        {{ getStockStatusBadge(stock).label }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="activeTab === 'adjustments'" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Item</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Campus</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Before / After</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Reference</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Reason</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="adjustment in adjustmentsData" :key="adjustment.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ formatDateTime(adjustment.created_at) }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ adjustment.item_name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ adjustment.campus_name }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['rounded-full px-2 py-1 text-xs font-medium', getAdjustmentBadge(adjustment.type)]">
                                        {{ formatAdjustmentType(adjustment.type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ adjustment.quantity }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ adjustment.previous_quantity }} / {{ adjustment.new_quantity }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ adjustment.reference_number || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ adjustment.reason }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="loadingAdjustments" class="py-8 text-center text-gray-500 dark:text-gray-400">
                    Loading adjustments...
                </div>
                <div v-else-if="adjustmentsData.length === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
                    No adjustments found.
                </div>
            </div>

            <div v-if="activeTab === 'types'" class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                <div class="text-xs text-gray-600 dark:text-gray-400 md:text-sm">
                    Showing {{ paginationTypes.from || 0 }} to {{ paginationTypes.to || 0 }} of {{ paginationTypes.total || 0 }} entries
                </div>
                <div class="flex flex-wrap gap-1">
                    <button
                        v-for="link in paginationTypes.links || []"
                        :key="`${link.label}-${link.url || 'disabled'}`"
                        type="button"
                        :disabled="!link.url"
                        :class="[
                            'min-h-10 rounded-md px-3 py-2 text-sm transition-colors',
                            link.active
                                ? 'bg-blue-600 text-white'
                                : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                        ]"
                        @click="link.url && loadTypesPage(link.url)"
                    >
                        <span v-html="link.label"></span>
                    </button>
                </div>
            </div>

            <div v-if="activeTab === 'items'" class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                <div class="text-xs text-gray-600 dark:text-gray-400 md:text-sm">
                    Showing {{ paginationItems.from || 0 }} to {{ paginationItems.to || 0 }} of {{ paginationItems.total || 0 }} entries
                </div>
                <div class="flex flex-wrap gap-1">
                    <button
                        v-for="link in paginationItems.links || []"
                        :key="`${link.label}-${link.url || 'disabled'}`"
                        type="button"
                        :disabled="!link.url"
                        :class="[
                            'min-h-10 rounded-md px-3 py-2 text-sm transition-colors',
                            link.active
                                ? 'bg-blue-600 text-white'
                                : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                        ]"
                        @click="link.url && loadItemsPage(link.url)"
                    >
                        <span v-html="link.label"></span>
                    </button>
                </div>
            </div>

            <div v-if="activeTab === 'stocks'" class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                <div class="text-xs text-gray-600 dark:text-gray-400 md:text-sm">
                    Showing {{ paginationStocks.from || 0 }} to {{ paginationStocks.to || 0 }} of {{ paginationStocks.total || 0 }} entries
                </div>
                <div class="flex flex-wrap gap-1">
                    <button
                        v-for="link in paginationStocks.links || []"
                        :key="`${link.label}-${link.url || 'disabled'}`"
                        type="button"
                        :disabled="!link.url"
                        :class="[
                            'min-h-10 rounded-md px-3 py-2 text-sm transition-colors',
                            link.active
                                ? 'bg-blue-600 text-white'
                                : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                        ]"
                        @click="link.url && loadStocksPage(link.url)"
                    >
                        <span v-html="link.label"></span>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
