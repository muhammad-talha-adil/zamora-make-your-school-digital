<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { alert } from '@/utils';
import { ref, watch } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';
import InventoryTypeForm from '@/components/forms/InventoryTypeForm.vue';

interface Props {
    inventoryTypes: {
        data: Array<{
            id: number;
            name: string;
            is_active: boolean;
            items_count: number;
            campus_id: number;
            campus_name: string;
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
    filters?: {
        campus_id?: string;
        show_inactive?: boolean;
    };
}

const props = defineProps<Props>();

// Filter states
const showInactive = ref(props.filters?.show_inactive || false);
const perPage = ref(25);
const campusFilter = ref(props.filters?.campus_id || '');
const inventoryTypesData = ref(props.inventoryTypes.data || []);
const pagination = ref(props.inventoryTypes);

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
        title: 'Types',
        href: '/inventory-types',
    },
];

const fetchInventoryTypes = (pageNum = 1) => {
    const params = new URLSearchParams({
        per_page: perPage.value.toString(),
        page: pageNum.toString(),
        show_inactive: showInactive.value.toString(),
    });

    if (campusFilter.value) {
        params.append('campus_id', campusFilter.value);
    }

    axios.get(`/inventory/types/paginated?${params}`).then((response) => {
        inventoryTypesData.value = response.data.data;
        pagination.value = response.data;
    });
};

watch([perPage, campusFilter], () => {
    fetchInventoryTypes();
});

watch(() => props.inventoryTypes, (newTypes) => {
    inventoryTypesData.value = newTypes.data || [];
    pagination.value = newTypes;
}, { deep: true });

watch(showInactive, () => {
    fetchInventoryTypes(1);
});

const toggleInactive = () => {
    showInactive.value = !showInactive.value;
};

const toggleActive = (type: typeof props.inventoryTypes.data[0]) => {
    const action = type.is_active ? 'inactivate' : 'activate';
    alert
        .confirm(
            `Are you sure you want to ${action} "${type.name}"?`,
            `${action.charAt(0).toUpperCase() + action.slice(1)} Inventory Type`,
        )
        .then((result) => {
            if (result.isConfirmed) {
                router.patch(route('inventory.types.inactivate', type.id), {}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success(`Inventory type ${action}d successfully!`);
                        fetchInventoryTypes();
                    },
                    onError: () => {
                        alert.error(`Failed to ${action} inventory type. Please try again.`);
                    },
                });
            }
        });
};

const restoreType = (type: typeof props.inventoryTypes.data[0]) => {
    router.patch(route('inventory.types.restore', type.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            alert.success('Inventory type restored successfully!');
            fetchInventoryTypes();
        },
        onError: () => {
            alert.error('Failed to restore inventory type. Please try again.');
        },
    });
};

const forceDeleteType = (type: typeof props.inventoryTypes.data[0]) => {
    alert
        .confirm(
            `Are you sure you want to permanently delete "${type.name}"? This action cannot be undone.`,
            'Delete Inventory Type',
        )
        .then((result) => {
            if (result.isConfirmed) {
                router.delete(route('inventory.types.destroy', type.id) + '/force', {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success('Inventory type permanently deleted successfully!');
                        fetchInventoryTypes();
                    },
                    onError: () => {
                        alert.error('Failed to permanently delete inventory type. Please try again.');
                    },
                });
            }
        });
};

const handleSaved = () => {
    fetchInventoryTypes();
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Inventory Types" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                        Inventory Types
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Manage inventory categories and types for your school.
                    </p>
                </div>
                <InventoryTypeForm 
                    :campuses="props.campuses" 
                    @saved="handleSaved"
                />
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 items-center">
                <select v-model="campusFilter" class="w-full sm:w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-11">
                    <option value="">All Campuses</option>
                    <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                        {{ campus.name }}
                    </option>
                </select>
                <Button
                    :variant="showInactive ? 'default' : 'outline'"
                    size="sm"
                    @click="toggleInactive"
                    class="min-h-11"
                >
                    <Icon :icon="showInactive ? 'arrow-left' : 'eye'" class="mr-1" />
                    {{ showInactive ? 'Back to Active' : 'Inactive' }}
                </Button>
                <div class="flex items-center gap-2 ml-auto">
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
                                    Name
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Campus
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Items
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
                            <tr v-for="(type, index) in inventoryTypesData" :key="type.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ (pagination.from || 0) + index }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ type.name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ type.campus_name || 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Badge variant="secondary">
                                        {{ type.items_count }} items
                                    </Badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Badge :variant="type.is_active ? 'default' : 'destructive'">
                                        {{ type.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                    <div class="flex flex-wrap gap-2" v-if="!showInactive">
                                        <InventoryTypeForm 
                                            :inventory-type="type"
                                            :campuses="props.campuses"
                                            trigger=""
                                            variant="outline"
                                            size="sm"
                                            @saved="handleSaved"
                                        />
                                        <Button
                                            :variant="type.is_active ? 'destructive' : 'default'"
                                            size="sm"
                                            :title="type.is_active ? 'Inactivate' : 'Activate'"
                                            @click="toggleActive(type)"
                                            class="min-h-11"
                                        >
                                            <Icon :icon="type.is_active ? 'eye-off' : 'eye'" class="mr-1" />
                                            {{ type.is_active ? 'Inactivate' : 'Activate' }}
                                        </Button>
                                    </div>
                                    <div class="flex flex-wrap gap-2" v-else>
                                        <Button variant="default" size="sm" @click="restoreType(type)" class="min-h-11">
                                            <Icon icon="refresh" class="mr-1" />Restore
                                        </Button>
                                        <Button variant="destructive" size="sm" @click="forceDeleteType(type)" class="min-h-11">
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
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="text-sm text-gray-600">
                    Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                </div>
                <div class="flex flex-wrap gap-1">
                    <Button
                        v-for="link in pagination.links"
                        :key="link.label"
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        :disabled="!link.url"
                        @click="link.url && fetchInventoryTypes(parseInt(link.url.split('=').pop() || '1'))"
                    >
                        <span v-html="link.label"></span>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
