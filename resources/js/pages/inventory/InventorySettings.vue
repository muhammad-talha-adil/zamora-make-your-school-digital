<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { alert, formatCurrency } from '@/utils';
import { ref, computed } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';
import InventoryTypeForm from '@/components/forms/InventoryTypeForm.vue';
import ItemForm from '@/components/forms/inventory/ItemForm.vue';

// Types Interface
interface InventoryTypeData {
    id: number;
    name: string;
    is_active: boolean;
    items_count: number;
    campus_id: number;
    campus_name: string;
    created_at: string;
}

interface InventoryItemData {
    id: number;
    name: string;
    description: string;
    purchase_rate: number;
    sale_rate: number;
    is_active: boolean;
    campus_id: number;
    campus_name: string;
    inventory_type_id: number;
    inventory_type_name: string;
    stock_quantity: number;
    created_at: string;
}

interface Props {
    inventoryTypes: {
        data: InventoryTypeData[];
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        from: number;
        to: number;
        total: number;
    };
    inventoryItems: {
        data: InventoryItemData[];
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
    filters?: {
        campus_id?: string;
    };
}

const props = defineProps<Props>();

// Tab state
const activeTab = ref('types');

// Types state
const showInactiveTypes = ref(false);
const campusFilter = ref(props.filters?.campus_id || '');
const inventoryTypesData = ref(props.inventoryTypes.data || []);
const paginationTypes = ref(props.inventoryTypes);
const perPageTypes = ref(25);

// Page size options
const perPageOptions = [25, 50, 75, 100];

// Items state
const showInactiveItems = ref(false);
const typeFilter = ref('');
const perPageItems = ref(25);
const inventoryItemsData = ref(props.inventoryItems.data || []);
const paginationItems = ref(props.inventoryItems);

// Editing state
const editingItem = ref<InventoryItemData | null>(null);
const showItemForm = ref(false);

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
        title: 'Settings',
        href: '/inventory/settings',
    },
];

// Types functions
const reloadTypes = () => {
    const params: Record<string, string | number> = {
        per_page: perPageTypes.value,
    };
    if (campusFilter.value) params.campus_id = campusFilter.value;
    if (showInactiveTypes.value) params.status = 'inactive';

    axios.get(`/inventory/types/paginated?${new URLSearchParams(params as any).toString()}`).then((response) => {
        inventoryTypesData.value = response.data.data;
        paginationTypes.value = response.data;
    });
};

const toggleShowInactiveTypes = () => {
    showInactiveTypes.value = !showInactiveTypes.value;
    reloadTypes();
};

const deleteType = (type: InventoryTypeData) => {
    alert
        .confirm(
            `Are you sure you want to delete "${type.name}"? This action can be undone by activating the record later.`,
            'Delete Inventory Type',
        )
        .then((result) => {
            if (result.isConfirmed) {
                axios.delete(`/inventory/types/${type.id}`).then(() => {
                    alert.success('Inventory type deleted successfully!');
                    reloadTypes();
                }).catch(() => {
                    alert.error('Failed to delete inventory type. Please try again.');
                });
            }
        });
};

const handleTypeSaved = () => {
    reloadTypes();
};

// Items functions
const reloadItems = () => {
    const params: Record<string, string | number> = {
        per_page: perPageItems.value,
    };
    const campusId = campusFilter.value || defaultCampusId.value;
    if (campusId) params.campus_id = campusId;
    if (typeFilter.value) params.type_id = typeFilter.value;
    if (showInactiveItems.value) params.status = 'inactive';

    axios.get(`/inventory/items/all?${new URLSearchParams(params as any).toString()}`).then((response) => {
        // Handle paginated response
        const paginatedData = response.data;
        const items = paginatedData.data || [];
        inventoryItemsData.value = items.map((item: any) => ({
            ...item,
            stock_quantity: item.stock_quantity || 0,
        }));
        paginationItems.value = {
            data: items,
            links: paginatedData.links || [],
            from: paginatedData.from || 1,
            to: paginatedData.to || items.length,
            total: paginatedData.total || items.length,
        };
    }).catch((err) => {
        console.error('Failed to load items:', err);
        inventoryItemsData.value = [];
    });
};

const loadItemsPage = (url: string) => {
    axios.get(url).then((response: any) => {
        const paginatedData = response.data;
        const items = paginatedData.data || [];
        inventoryItemsData.value = items.map((item: any) => ({
            ...item,
            stock_quantity: item.stock_quantity || 0,
        }));
        paginationItems.value = {
            data: items,
            links: paginatedData.links || [],
            from: paginatedData.from || 1,
            to: paginatedData.to || items.length,
            total: paginatedData.total || items.length,
        };
    });
};

const toggleShowInactiveItems = () => {
    showInactiveItems.value = !showInactiveItems.value;
    reloadItems();
};

const openEditItem = (item: InventoryItemData) => {
    editingItem.value = item;
    showItemForm.value = true;
};


const deleteItem = (item: InventoryItemData) => {
    alert
        .confirm(
            `Are you sure you want to delete "${item.name}"? This action can be undone by activating the record later.`,
            'Delete Inventory Item',
        )
        .then((result) => {
            if (result.isConfirmed) {
                axios.delete(`/inventory/items/${item.id}`).then(() => {
                    alert.success('Inventory item deleted successfully!');
                    reloadItems();
                }).catch(() => {
                    alert.error('Failed to delete inventory item. Please try again.');
                });
            }
        });
};

const handleItemSaved = () => {
    showItemForm.value = false;
    editingItem.value = null;
    reloadItems();
};

const getStockStatus = (quantity: number) => {
    if (quantity <= 0) return { label: 'Out of Stock', variant: 'destructive' as const, color: 'text-red-600' };
    if (quantity < 10) return { label: 'Low Stock', variant: 'secondary' as const, color: 'text-amber-600' };
    return { label: 'In Stock', variant: 'default' as const, color: 'text-green-600' };
};

const loadTypesPage = (url: string) => {
    axios.get(url).then((response: any) => {
        inventoryTypesData.value = response.data.data;
        paginationTypes.value = response.data;
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Inventory Settings" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                    Inventory Settings
                </h1>
                <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                    Manage inventory types and items for your school.
                </p>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-4 md:space-x-8 overflow-x-auto">
                    <button
                        @click="activeTab = 'types'"
                        :class="[
                            activeTab === 'types'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                            'border-b-2 px-2 md:px-1 py-3 text-sm font-medium whitespace-nowrap',
                        ]"
                    >
                        Types
                    </button>
                    <button
                        @click="activeTab = 'items'"
                        :class="[
                            activeTab === 'items'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                            'border-b-2 px-2 md:px-1 py-3 text-sm font-medium whitespace-nowrap',
                        ]"
                    >
                        Items
                    </button>
                </nav>
            </div>

            <!-- Types Tab -->
            <div v-if="activeTab === 'types'" class="space-y-4 md:space-y-6">
                <!-- Types Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <h2 class="text-lg md:text-xl font-semibold text-gray-900 dark:text-white">
                            Inventory Types
                        </h2>
                        <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                            Manage inventory categories and types.
                        </p>
                    </div>
                    <InventoryTypeForm 
                        :campuses="props.campuses" 
                        @saved="handleTypeSaved"
                    />
                </div>

                <!-- Types Filters -->
                <div class="flex flex-col sm:flex-row gap-2 items-center">
                    <select 
                        v-model="campusFilter" 
                        @change="reloadTypes" 
                        class="w-full sm:w-44 md:w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Campuses</option>
                        <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                            {{ campus.name }}
                        </option>
                    </select>
                    <div class="flex-1"></div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600 dark:text-gray-400">Show:</label>
                        <select v-model="perPageTypes" @change="reloadTypes" class="w-20 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-2 text-sm min-h-11">
                            <option v-for="option in perPageOptions" :key="option" :value="option">
                                {{ option }}
                            </option>
                        </select>
                    </div>
                    <Button
                        :variant="showInactiveTypes ? 'ghost' : 'default'"
                        size="sm"
                        @click="toggleShowInactiveTypes"
                        class="min-h-10 md:min-h-11"
                    >
                        <Icon :icon="showInactiveTypes ? 'eye' : 'eye-off'" class="mr-1.5" />
                        {{ showInactiveTypes ? 'Show Active' : 'Show Inactive' }}
                    </Button>
                </div>

                <!-- Mobile Card View -->
                <div class="block lg:hidden space-y-3">
                    <div 
                        v-for="type in inventoryTypesData" 
                        :key="type.id" 
                        class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2"
                    >
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ type.name }}</div>
                                <div class="text-xs text-gray-500">{{ type.campus_name || 'N/A' }}</div>
                            </div>
                            <Badge :variant="type.is_active ? 'default' : 'destructive'">
                                {{ type.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <Badge variant="secondary">{{ type.items_count }} items</Badge>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <Button 
                                variant="outline" 
                                size="sm" 
                                @click="() => {}" 
                                class="flex-1"
                            >
                                <Icon icon="edit" class="mr-1" />Edit
                            </Button>
                            <Button 
                                variant="destructive" 
                                size="sm" 
                                @click="deleteType(type)" 
                                class="flex-1"
                            >
                                <Icon icon="trash" class="mr-1" />Delete
                            </Button>
                        </div>
                    </div>
                    <div v-if="inventoryTypesData.length === 0" class="text-center py-8 text-gray-500">
                        No types found.
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Sr#</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Name</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Campus</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Items</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Status</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                <tr v-for="(type, index) in inventoryTypesData" :key="type.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                        {{ (paginationTypes.from || 0) + index }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ type.name }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                        {{ type.campus_name || 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <Badge variant="secondary">{{ type.items_count }} items</Badge>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <Badge :variant="type.is_active ? 'default' : 'destructive'">
                                            {{ type.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                        <div class="flex flex-wrap gap-2">
                                            <InventoryTypeForm 
                                                :inventory-type="type"
                                                :campuses="props.campuses"
                                                trigger=""
                                                variant="outline"
                                                size="sm"
                                                @saved="handleTypeSaved"
                                            />
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                @click="deleteType(type)"
                                                class="min-h-8"
                                            >
                                                <Icon icon="trash" class="mr-1" />Delete
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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

            <!-- Items Tab -->
            <div v-if="activeTab === 'items'" class="space-y-4 md:space-y-6">
                <!-- Items Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <h2 class="text-lg md:text-xl font-semibold text-gray-900 dark:text-white">
                            Inventory Items
                        </h2>
                        <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                            Manage individual inventory items.
                        </p>
                    </div>
                    <ItemForm
                        v-model:open="showItemForm"
                        :inventory-item="editingItem"
                        :campuses="props.campuses"
                        :inventory-types="props.inventoryTypes.data"
                        trigger="Add Item"
                        @saved="handleItemSaved"
                    />
                </div>

                <!-- Items Filters -->
                <div class="flex flex-col sm:flex-row gap-2 items-center">
                    <select 
                        v-model="campusFilter" 
                        @change="reloadItems" 
                        class="w-full sm:w-44 md:w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Campuses</option>
                        <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                            {{ campus.name }}
                        </option>
                    </select>
                    <select 
                        v-model="typeFilter" 
                        @change="reloadItems" 
                        class="w-full sm:w-44 md:w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Types</option>
                        <option v-for="type in props.inventoryTypes.data" :key="type.id" :value="type.id">
                            {{ type.name }}
                        </option>
                    </select>
                    <div class="flex-1"></div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600 dark:text-gray-400">Show:</label>
                        <select v-model="perPageItems" @change="reloadItems" class="w-20 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-2 text-sm min-h-11">
                            <option v-for="option in perPageOptions" :key="option" :value="option">
                                {{ option }}
                            </option>
                        </select>
                    </div>
                    <Button
                        :variant="showInactiveItems ? 'ghost' : 'default'"
                        size="sm"
                        @click="toggleShowInactiveItems"
                        class="min-h-10 md:min-h-11"
                    >
                        <Icon :icon="showInactiveItems ? 'eye' : 'eye-off'" class="mr-1.5" />
                        {{ showInactiveItems ? 'Show Active' : 'Show Inactive' }}
                    </Button>
                </div>

                <!-- Mobile Card View -->
                <div class="block lg:hidden space-y-3">
                    <div 
                        v-for="item in inventoryItemsData" 
                        :key="item.id" 
                        class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2"
                    >
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ item.name }}</div>
                                <div class="text-xs text-gray-500 truncate">{{ item.description || 'No description' }}</div>
                            </div>
                            <Badge :variant="item.is_active ? 'default' : 'destructive'">
                                {{ item.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <Badge variant="secondary">{{ item.inventory_type_name || 'N/A' }}</Badge>
                            <div class="flex items-center gap-2">
                                <span :class="['font-bold', getStockStatus(item.stock_quantity).color]">
                                    {{ item.stock_quantity }}
                                </span>
                                <Badge :variant="getStockStatus(item.stock_quantity).variant" size="sm">
                                    {{ getStockStatus(item.stock_quantity).label }}
                                </Badge>
                            </div>
                        </div>
                        <div class="flex justify-between text-sm text-gray-500">
                            <span>Buy: {{ formatCurrency(item.purchase_rate) }}</span>
                            <span>Sell: {{ formatCurrency(item.sale_rate) }}</span>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <Button variant="outline" size="sm" @click="openEditItem(item)" class="flex-1">
                                <Icon icon="edit" class="mr-1" />Edit
                            </Button>
                            <Button variant="destructive" size="sm" @click="deleteItem(item)" class="flex-1">
                                <Icon icon="trash" class="mr-1" />Delete
                            </Button>
                        </div>
                    </div>
                    <div v-if="inventoryItemsData.length === 0" class="text-center py-8 text-gray-500">
                        No items found.
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Sr#</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Item</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Type</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Rates</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Stock</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Status</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                <tr v-for="(item, index) in inventoryItemsData" :key="item.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                        {{ (paginationItems.from || 0) + index }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ item.name }}</div>
                                        <div class="text-xs text-gray-500 truncate max-w-xs">{{ item.description || 'No description' }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <Badge variant="secondary">{{ item.inventory_type_name || 'N/A' }}</Badge>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                                        <div><span class="text-gray-500">Buy:</span> {{ formatCurrency(item.purchase_rate) }}</div>
                                        <div><span class="text-gray-500">Sell:</span> {{ formatCurrency(item.sale_rate) }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span :class="['font-bold', getStockStatus(item.stock_quantity).color]">
                                                {{ item.stock_quantity }}
                                            </span>
                                            <Badge :variant="getStockStatus(item.stock_quantity).variant" size="sm">
                                                {{ getStockStatus(item.stock_quantity).label }}
                                            </Badge>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <Badge :variant="item.is_active ? 'default' : 'destructive'">
                                            {{ item.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                        <div class="flex flex-wrap gap-2">
                                            <Button variant="outline" size="sm" @click="openEditItem(item)" class="min-h-8">
                                                <Icon icon="edit" class="mr-1" />Edit
                                            </Button>
                                            <Button variant="destructive" size="sm" @click="deleteItem(item)" class="min-h-8">
                                                <Icon icon="trash" class="mr-1" />Delete
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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
        </div>
    </AppLayout>
</template>
