<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { alert, formatCurrency } from '@/utils';
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';
import ItemForm from '@/components/forms/inventory/ItemForm.vue';

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
    inventoryTypes: Array<{
        id: number;
        name: string;
    }>;
    filters?: {
        campus_id?: string;
        active_only?: boolean;
    };
}

const props = defineProps<Props>();

// Editing state
const editingItem = ref<InventoryItemData | null>(null);
const showItemForm = ref(false);

// Filter states
const showDeleted = ref(false);
const campusFilter = ref(props.filters?.campus_id || '');
const typeFilter = ref('');
const perPage = ref(25);
const inventoryItemsData = ref(props.inventoryItems.data || []);
const pagination = ref(props.inventoryItems);

// Page size options
const perPageOptions = [25, 50, 75, 100];

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
        title: 'Items',
        href: '/inventory-items',
    },
];

// Watch for filter changes and reload data
watch([campusFilter, typeFilter, perPage], () => {
    reloadWithFilters();
});

const reloadWithFilters = () => {
    const params: Record<string, string> = {
        per_page: perPage.value.toString(),
    };
    if (campusFilter.value) params.campus_id = campusFilter.value;
    if (typeFilter.value) params.type_id = typeFilter.value;
    if (showDeleted.value) params.status = 'deleted';
    
    router.get('/inventory/items', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleItemSaved = () => {
    showItemForm.value = false;
    editingItem.value = null;
    reloadWithFilters();
};

const openEditPage = (item: InventoryItemData) => {
    editingItem.value = item;
    showItemForm.value = true;
};

const toggleActive = (item: InventoryItemData) => {
    const action = item.is_active ? 'inactivate' : 'activate';
    alert
        .confirm(
            `Are you sure you want to ${action} "${item.name}"?`,
            `${action.charAt(0).toUpperCase() + action.slice(1)} Inventory Item`,
        )
        .then((result) => {
            if (result.isConfirmed) {
                router.patch(`/inventory/items/${item.id}/${action === 'inactivate' ? 'inactivate' : 'activate'}`, {}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success(`Inventory item ${action}d successfully!`);
                        reloadWithFilters();
                    },
                    onError: () => {
                        alert.error(`Failed to ${action} inventory item. Please try again.`);
                    },
                });
            }
        });
};


const restoreItem = (item: InventoryItemData) => {
    router.patch(`/inventory/items/${item.id}/restore`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            alert.success('Inventory item restored successfully!');
            reloadWithFilters();
        },
        onError: () => {
            alert.error('Failed to restore inventory item. Please try again.');
        },
    });
};

const forceDeleteItem = (item: InventoryItemData) => {
    alert
        .confirm(
            `Are you sure you want to permanently delete "${item.name}"? This action cannot be undone.`,
            'Delete Inventory Item',
        )
        .then((result) => {
            if (result.isConfirmed) {
                router.delete(`/inventory/items/${item.id}/force`, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success('Inventory item permanently deleted successfully!');
                        reloadWithFilters();
                    },
                    onError: () => {
                        alert.error('Failed to permanently delete inventory item. Please try again.');
                    },
                });
            }
        });
};

const toggleDeleted = () => {
    showDeleted.value = !showDeleted.value;
    reloadWithFilters();
};

const getStockStatus = (quantity: number) => {
    if (quantity <= 0) return { label: 'Out of Stock', variant: 'destructive' as const, color: 'text-red-600' };
    if (quantity < 10) return { label: 'Low Stock', variant: 'secondary' as const, color: 'text-amber-600' };
    return { label: 'In Stock', variant: 'default' as const, color: 'text-green-600' };
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Inventory Items" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                        Inventory Items
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Manage individual inventory items across all categories.
                    </p>
                </div>
                <ItemForm
                    v-model:open="showItemForm"
                    :inventory-item="editingItem"
                    :campuses="props.campuses"
                    :inventory-types="props.inventoryTypes"
                    trigger="Add Item"
                    @saved="handleItemSaved"
                />
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-2 items-center">
                <select v-model="campusFilter" class="w-full sm:w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2.5 text-sm min-h-11">
                    <option value="">All Campuses</option>
                    <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                        {{ campus.name }}
                    </option>
                </select>
                <select v-model="typeFilter" class="w-full sm:w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2.5 text-sm min-h-11">
                    <option value="">All Types</option>
                    <option v-for="type in props.inventoryTypes" :key="type.id" :value="type.id">
                        {{ type.name }}
                    </option>
                </select>
                <div class="flex-1"></div>
                <Button
                    :variant="showDeleted ? 'ghost' : 'default'"
                    size="sm"
                    @click="toggleDeleted"
                    class="min-h-11"
                >
                    <Icon :icon="showDeleted ? 'arrow-left' : 'trash'" class="mr-1.5" />
                    {{ showDeleted ? 'Active Items' : 'Deleted Items' }}
                </Button>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600 dark:text-gray-400">Show:</label>
                    <select v-model="perPage" class="w-20 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-2 text-sm min-h-11">
                        <option v-for="option in perPageOptions" :key="option" :value="option">
                            {{ option }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Sr#
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Item
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Type
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Rates
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Stock
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="(item, index) in inventoryItemsData" :key="item.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ (pagination.from || 0) + index }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ item.name }}
                                        </div>
                                        <div class="text-xs text-gray-500 truncate max-w-xs">
                                            {{ item.description || 'No description' }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Badge variant="secondary">
                                        {{ item.inventory_type_name || 'N/A' }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        <span class="text-gray-500">Buy:</span>
                                        <span class="font-medium ml-1">{{ formatCurrency(item.purchase_rate) }}</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="text-gray-500">Sell:</span>
                                        <span class="font-medium ml-1">{{ formatCurrency(item.sale_rate) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span :class="['font-bold', getStockStatus(item.stock_quantity).color]">
                                            {{ item.stock_quantity }}
                                        </span>
                                        <Badge :variant="getStockStatus(item.stock_quantity).variant" size="sm">
                                            {{ getStockStatus(item.stock_quantity).label }}
                                        </Badge>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Badge :variant="item.is_active ? 'default' : 'destructive'">
                                        {{ item.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                    <div class="flex flex-wrap gap-2" v-if="!showDeleted">
                                        <Button variant="outline" size="sm" title="Edit" @click="openEditPage(item)" class="min-h-11 min-w-11">
                                            <Icon icon="edit" class="mr-1" />Edit
                                        </Button>
                                        <Button
                                            :variant="item.is_active ? 'destructive' : 'default'"
                                            size="sm"
                                            :title="item.is_active ? 'Inactivate' : 'Activate'"
                                            @click="toggleActive(item)"
                                            class="min-h-11"
                                        >
                                            <Icon :icon="item.is_active ? 'eye-off' : 'eye'" class="mr-1" />
                                            {{ item.is_active ? 'Inactivate' : 'Activate' }}
                                        </Button>
                                    </div>
                                    <div class="flex flex-wrap gap-2" v-else>
                                        <Button variant="default" size="sm" @click="restoreItem(item)" class="min-h-11">
                                            <Icon icon="refresh" class="mr-1" />Restore
                                        </Button>
                                        <Button variant="destructive" size="sm" @click="forceDeleteItem(item)" class="min-h-11">
                                            <Icon icon="x" class="mr-1" />Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                </div>
                <div class="flex flex-wrap gap-1">
                    <Button
                        v-for="link in pagination.links"
                        :key="link.label"
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        :disabled="!link.url"
                        @click="link.url && router.visit(link.url, { preserveScroll: true })"
                        class="min-h-9"
                    >
                        <span v-html="link.label"></span>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
