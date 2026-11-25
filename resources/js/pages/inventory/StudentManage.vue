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

// Initial fetch on mount
fetchStudentInventories();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Student Inventory" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-foreground">
                    Student Inventory
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Manage inventory assigned to students and their returns.
                </p>
            </div>

            <!-- Tabs -->
            <div class="border-b border-border">
                <nav class="-mb-px flex space-x-8 overflow-x-auto flex-nowrap [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <button
                        @click="activeTab = 'assigned'"
                        :class="[
                            activeTab === 'assigned'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
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
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                            'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                        ]"
                    >
                        <Icon icon="rotate-ccw" class="inline mr-1" />
                        Returns
                    </button>
                </nav>
            </div>

            <!-- Filters & Actions Bar -->
            <div class="flex flex-wrap items-center justify-end gap-3">
                <Button v-if="activeTab === 'assigned'" @click="router.get('/inventory/student-inventory/create')" class="min-h-11">
                    <Icon icon="user-plus" class="mr-2" />
                    Assign to Student
                </Button>
            </div>

            <!-- ==================== ASSIGNED ITEMS TAB ==================== -->
            <div v-if="activeTab === 'assigned'">
                <div class="overflow-x-auto rounded-lg border border-border bg-card shadow-sm">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase whitespace-nowrap">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase whitespace-nowrap">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase whitespace-nowrap hidden 2xl:table-cell">Reg #</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase whitespace-nowrap">Items</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase whitespace-nowrap hidden 2xl:table-cell">Total Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase whitespace-nowrap">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase whitespace-nowrap">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase whitespace-nowrap">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-if="studentInventoriesData.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-muted-foreground">{{ 'No items assigned yet.' }}</td>
                            </tr>
                            <tr v-for="(record, index) in studentInventoriesData" :key="record.id" class="hover:bg-accent">
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ String(Number(studentInventoriesPagination.from || 1) + Number(index)) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-foreground">
                                    <div>{{ record.student_name }}</div>
                                    <div class="text-xs text-muted-foreground" v-if="record.class_name || record.section_name">
                                        {{ record.class_name }}{{ record.class_name && record.section_name ? ' - ' : '' }}{{ record.section_name }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm hidden 2xl:table-cell text-muted-foreground">{{ record.registration_number || '-' }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-foreground">
                                    <div class="flex flex-col">
                                        <span>{{ getItemDisplayText(record) }}</span>
                                        <span v-if="record.items && record.items.length > 1" class="text-xs text-muted-foreground">
                                            {{ record.items.length }} different items
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm hidden 2xl:table-cell text-muted-foreground">{{ getTotalItemsCount(record) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-foreground">{{ formatCurrency(Math.abs(record.final_amount)) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ formatDate(record.assigned_date) }}</td>
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
                <div class="overflow-x-auto rounded-lg border border-border bg-card shadow-sm">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase whitespace-nowrap">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase whitespace-nowrap hidden 2xl:table-cell">Return ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase whitespace-nowrap">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase whitespace-nowrap hidden 2xl:table-cell">Reg #</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase whitespace-nowrap hidden 2xl:table-cell">Total Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase whitespace-nowrap">Total Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase whitespace-nowrap">Return Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase whitespace-nowrap">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-if="returnsData.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-muted-foreground">{{ 'No returns yet.' }}</td>
                            </tr>
                            <tr v-for="(returnItem, returnIndex) in returnsData" :key="returnItem.id" class="hover:bg-accent">
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ returnIndex + 1 }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm hidden 2xl:table-cell font-medium text-foreground">{{ returnItem.return_id || 'N/A' }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-foreground">
                                    {{ returnItem.student_name || returnItem.student?.name || 'N/A' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm hidden 2xl:table-cell text-muted-foreground">
                                    {{ returnItem.registration_number || returnItem.student?.registration_number || '-' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm hidden 2xl:table-cell text-muted-foreground">{{ returnItem.total_quantity }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-foreground">{{ formatCurrency(returnItem.total_amount) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ formatDate(returnItem.return_date) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <Badge :variant="getStatusBadge(returnItem.status).variant">
                                        {{ getStatusBadge(returnItem.status).label }}
                                    </Badge>
                                </td>
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
