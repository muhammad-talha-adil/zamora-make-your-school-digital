<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Stock Adjustments" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Stock Adjustments</h1>
                    <p class="text-gray-500">Track and manage stock corrections</p>
                </div>
                <Link
                    :href="`/inventory/adjustments/create`"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors min-h-11 flex items-center justify-center"
                >
                    New Adjustment
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                        <select
                            v-model="filters.campus_id"
                            @change="router.visit(`/inventory/adjustments?campus_id=${filters.campus_id || ''}`)"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-11"
                        >
                            <option value="">All Campuses</option>
                            <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                                {{ campus.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Item</label>
                        <select
                            v-model="filters.item_id"
                            @change="router.visit(`/inventory/adjustments?campus_id=${filters.campus_id || ''}&item_id=${filters.item_id || ''}`)"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-11"
                        >
                            <option value="">All Items</option>
                            <option v-for="item in props.inventoryItems" :key="item.id" :value="item.id">
                                {{ item.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select
                            v-model="filters.type"
                            @change="router.visit(`/inventory/adjustments?campus_id=${filters.campus_id || ''}&type=${filters.type || ''}`)"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-11"
                        >
                            <option value="">All Types</option>
                            <option value="add">Add Stock</option>
                            <option value="subtract">Subtract Stock</option>
                            <option value="set">Set Quantity</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input
                            v-model="filters.from_date"
                            type="date"
                            @change="router.visit(`/inventory/adjustments?campus_id=${filters.campus_id || ''}&from_date=${filters.from_date || ''}`)"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-11"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input
                            v-model="filters.to_date"
                            type="date"
                            @change="router.visit(`/inventory/adjustments?campus_id=${filters.campus_id || ''}&to_date=${filters.to_date || ''}`)"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-11"
                        />
                    </div>
                </div>
            </div>

            <!-- Adjustments Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Item
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Quantity Change
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Reason
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                By
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="adjustment in props.adjustments.data" :key="adjustment.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ new Date(adjustment.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ adjustment.inventory_item?.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs font-medium rounded-full',
                                        adjustment.type === 'add' ? 'bg-green-100 text-green-800' :
                                        adjustment.type === 'subtract' ? 'bg-red-100 text-red-800' :
                                        'bg-blue-100 text-blue-800'
                                    ]"
                                >
                                    {{ adjustment.type === 'add' ? 'Add' : adjustment.type === 'subtract' ? 'Subtract' : 'Set' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span v-if="adjustment.type === 'add'" class="text-green-600 font-medium">
                                    +{{ adjustment.quantity }}
                                </span>
                                <span v-else-if="adjustment.type === 'subtract'" class="text-red-600 font-medium">
                                    -{{ adjustment.quantity }}
                                </span>
                                <span v-else class="text-blue-600 font-medium">
                                    → {{ adjustment.quantity }}
                                </span>
                                <div class="text-xs text-gray-500">
                                    {{ adjustment.previous_quantity }} → {{ adjustment.new_quantity }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                                {{ adjustment.reason }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ adjustment.user?.name || 'System' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <Link
                                    :href="`/inventory/adjustments/${adjustment.id}`"
                                    class="text-blue-600 hover:text-blue-900"
                                >
                                    View
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="props.adjustments.data.length === 0" class="px-6 py-12 text-center">
                    <p class="text-gray-500">No adjustments found.</p>
                </div>

                <!-- Pagination -->
                <div v-if="props.adjustments.links" class="px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="text-sm text-gray-600">
                        Showing {{ props.adjustments.from }} to {{ props.adjustments.to }} of {{ props.adjustments.total }} entries
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <Link
                            v-for="link in props.adjustments.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-2 text-sm rounded-md transition-colors min-h-11 flex items-center justify-center',
                                link.active
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                            ]"
                            preserve-state
                        >
                            <span v-html="link.label"></span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Props {
    adjustments: {
        data: Array<{
            id: number;
            type: string;
            quantity: number;
            previous_quantity: number;
            new_quantity: number;
            reason: string;
            created_at: string;
            inventory_item?: {
                name: string;
            };
            user?: {
                name: string;
            };
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
    inventoryItems: Array<{
        id: number;
        name: string;
    }>;
    filters?: {
        campus_id?: string;
        item_id?: string;
        type?: string;
        from_date?: string;
        to_date?: string;
    };
}

const props = defineProps<Props>();

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
        title: 'Stock Adjustments',
        href: '/inventory/adjustments',
    },
];

const filters = reactive({
    campus_id: props.filters?.campus_id || '',
    item_id: props.filters?.item_id || '',
    type: props.filters?.type || '',
    from_date: props.filters?.from_date || '',
    to_date: props.filters?.to_date || '',
});
</script>
