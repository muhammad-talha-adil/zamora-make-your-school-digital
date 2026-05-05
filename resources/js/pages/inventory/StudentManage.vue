<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';
import { formatDate } from '@/utils/date';
import { formatCurrency } from '@/utils/currency';

interface Props {
    studentInventories: any;
    returns: any;
    campuses: Array<{ id: number; name: string }>;
    students: Array<{ id: number; name: string; registration_number: string }>;
    inventoryItems: Array<{ id: number; name?: string; description?: string; sale_rate?: number; available_stock?: number; is_low_stock?: boolean }>;
}

const props = defineProps<Props>();
const activeTab = ref('assigned');
const campusFilter = ref(props.campuses[0]?.id || '');

// Pagination state
const studentInventoriesPagination = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0, from: 0, to: 0 });
const returnsPagination = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0, from: 0, to: 0 });

const studentInventoriesData = ref(props.studentInventories.data || []);
const returnsData = ref(props.returns.data || []);

// Initialize pagination from props
if (props.studentInventories.pagination) {
    studentInventoriesPagination.value = props.studentInventories.pagination;
}
if (props.returns.pagination) {
    returnsPagination.value = props.returns.pagination;
}

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory', href: '/inventory' },
    { title: 'Student Inventory', href: '/inventory/student-manage' },
];

const fetchStudentInventories = () => {
    const params: Record<string, any> = {
        page: studentInventoriesPagination.value.current_page,
        per_page: studentInventoriesPagination.value.per_page,
    };
    if (campusFilter.value) params.campus_id = campusFilter.value;
    
    axios.get('/inventory/student-inventory/all', { params }).then((response) => {
        const result = response.data;
        studentInventoriesData.value = result.data || result || [];
        if (result.pagination) {
            studentInventoriesPagination.value = result.pagination;
        }
    }).catch((error) => {
        console.error('Error fetching student inventories:', error);
    });
};

const fetchReturns = () => {
    const params: Record<string, any> = {
        page: returnsPagination.value.current_page,
        per_page: returnsPagination.value.per_page,
    };
    if (campusFilter.value) params.campus_id = campusFilter.value;
    
    axios.get('/inventory/returns/all', { params }).then((response) => {
        const result = response.data;
        returnsData.value = result.data || result || [];
        if (result.pagination) {
            returnsPagination.value = result.pagination;
        }
    }).catch((error) => {
        console.error('Error fetching returns:', error);
    });
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'assigned':
            return { label: 'Assigned', variant: 'default' as const };
        case 'partial_return':
            return { label: 'Partial Return', variant: 'secondary' as const };
        case 'returned':
            return { label: 'Returned', variant: 'outline' as const };
        default:
            return { label: status, variant: 'default' as const };
    }
};

// Get total items count from items array
const getTotalItemsCount = (record: any) => {
    if (record.items && Array.isArray(record.items)) {
        return record.items.reduce((sum: number, item: any) => sum + (item.quantity || 0), 0);
    }
    return record.quantity || 0;
};

// Get first item name or show count
const getItemDisplayText = (record: any) => {
    if (record.items && Array.isArray(record.items)) {
        if (record.items.length === 1) {
            return record.items[0].item_name_snapshot || record.items[0].inventory_item?.name || 'N/A';
        }
        const firstItem = record.items[0].item_name_snapshot || record.items[0].inventory_item?.name || 'Item';
        return `${firstItem} +${record.items.length - 1} more`;
    }
    return record.item_name_snapshot || record.inventory_item?.name || 'N/A';
};

// Watch for tab changes and fetch data
watch(activeTab, (newTab) => {
    if (newTab === 'assigned') {
        fetchStudentInventories();
    } else if (newTab === 'returns') {
        fetchReturns();
    }
});

// Watch for campus filter changes
watch(campusFilter, () => {
    if (activeTab.value === 'assigned') {
        studentInventoriesPagination.value.current_page = 1;
        fetchStudentInventories();
    } else if (activeTab.value === 'returns') {
        returnsPagination.value.current_page = 1;
        fetchReturns();
    }
});

// Initial fetch on mount
fetchStudentInventories();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Student Inventory" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Student Inventory
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Manage inventory assigned to students and their returns.
                </p>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 overflow-x-auto">
                    <button
                        @click="activeTab = 'assigned'"
                        :class="[
                            activeTab === 'assigned'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                            'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                        ]"
                    >
                        <Icon icon="user-check" class="inline mr-1" />
                        Assigned Items
                    </button>
                    <button
                        @click="activeTab = 'returns'"
                        :class="[
                            activeTab === 'returns'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                            'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                        ]"
                    >
                        <Icon icon="rotate-ccw" class="inline mr-1" />
                        Returns
                    </button>
                </nav>
            </div>

            <!-- Filters & Actions Bar -->
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div class="flex flex-wrap gap-3 flex-1">
                    <select v-model="campusFilter" @change="() => {
                        if (activeTab === 'assigned') fetchStudentInventories();
                        if (activeTab === 'returns') fetchReturns();
                    }" class="w-full sm:w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm">
                        <option value="">All Campuses</option>
                        <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                            {{ campus.name }}
                        </option>
                    </select>
                </div>
                
                <Button v-if="activeTab === 'assigned'" @click="router.get('/inventory/student-inventory/create')" class="min-h-11">
                    <Icon icon="user-plus" class="mr-2" />
                    Assign to Student
                </Button>
            </div>

            <!-- ==================== ASSIGNED ITEMS TAB ==================== -->
            <div v-if="activeTab === 'assigned'">
                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Sr#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Reg #</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Campus</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Items</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Total Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="(record, index) in studentInventoriesData" :key="record.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ String(Number(studentInventoriesPagination.from || 1) + Number(index)) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    <div>{{ record.student_name }}</div>
                                    <div class="text-xs text-gray-500" v-if="record.class_name || record.section_name">
                                        {{ record.class_name }}{{ record.class_name && record.section_name ? ' - ' : '' }}{{ record.section_name }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ record.registration_number || '-' }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ record.campus_name || '-' }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <div class="flex flex-col">
                                        <span>{{ getItemDisplayText(record) }}</span>
                                        <span v-if="record.items && record.items.length > 1" class="text-xs text-gray-500">
                                            {{ record.items.length }} different items
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ getTotalItemsCount(record) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">{{ formatCurrency(Math.abs(record.final_amount)) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ formatDate(record.assigned_date) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <Badge :variant="getStatusBadge(record.status).variant">
                                        {{ getStatusBadge(record.status).label }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex gap-1">
                                        <Button variant="outline" size="sm" @click="router.visit(`/inventory/student-inventory/${record.id}/view`)">
                                            <Icon icon="eye" class="h-4 w-4" />
                                        </Button>
                                        <Button v-if="record.status !== 'returned'" variant="outline" size="sm" @click="router.visit(`/inventory/student-inventory/${record.id}/return`)">
                                            <Icon icon="rotate-ccw" class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ==================== RETURNS TAB ==================== -->
            <div v-if="activeTab === 'returns'">
                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Return ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Reg #</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Total Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Total Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Return Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Campus</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="returnItem in returnsData" :key="returnItem.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ returnItem.return_id || 'N/A' }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ returnItem.student_name || returnItem.student?.name || 'N/A' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ returnItem.registration_number || returnItem.student?.registration_number || '-' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ returnItem.total_quantity }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">{{ formatCurrency(returnItem.total_amount) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ formatDate(returnItem.return_date) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <Badge :variant="getStatusBadge(returnItem.status).variant">
                                        {{ getStatusBadge(returnItem.status).label }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ returnItem.campus_name }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <Button variant="outline" size="sm" @click="router.visit(`/inventory/student-inventory/return/${returnItem.id}/view`)">
                                        <Icon icon="eye" class="h-4 w-4" />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
