<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { alert, formatCurrency } from '@/utils';
import { ref, watch } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';

interface Props {
    studentInventories: {
        data: Array<{
            id: number;
            student_id: number;
            student_name: string;
            registration_number: string;
            campus_id: number;
            campus_name: string;
            inventory_item_id: number;
            item_name_snapshot: string;
            description_snapshot: string;
            unit_price_snapshot: number;
            discount_amount: number;
            discount_percentage: number;
            quantity: number;
            returned_quantity: number;
            status: string;
            assigned_date: string;
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
}

const props = defineProps<Props>();

// Filter states
const perPage = ref(10);
const campusFilter = ref('');
const statusFilter = ref('');
const studentInventoriesData = ref(props.studentInventories.data || []);
const pagination = ref(props.studentInventories);

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
        title: 'Student Inventory',
        href: '/inventory/student-inventory',
    },
];

const fetchStudentInventories = (pageNum = 1) => {
    const params = new URLSearchParams({
        per_page: perPage.value.toString(),
        page: pageNum.toString(),
    });

    if (campusFilter.value) {
        params.append('campus_id', campusFilter.value);
    }
    if (statusFilter.value) {
        params.append('status', statusFilter.value);
    }

    axios.get(`/inventory/student-inventory/all?${params}`).then((response) => {
        studentInventoriesData.value = response.data.data;
        pagination.value = response.data;
    });
};

watch([perPage, campusFilter, statusFilter], () => {
    fetchStudentInventories();
});

watch(() => props.studentInventories, (newData) => {
    studentInventoriesData.value = newData.data || [];
    pagination.value = newData;
}, { deep: true });

const openAssignPage = () => {
    router.get('/inventory/student-inventory/create');
};

const openReturnPage = (item: any) => {
    router.get(`/inventory/student-inventory/${item.id}/return`);
};

const deleteAssignment = (item: any) => {
    alert
        .confirm(
            `Are you sure you want to delete this assignment?`,
            'Delete Assignment'
        )
        .then((result) => {
            if (result.isConfirmed) {
                router.delete(`/inventory/student-inventory/${item.id}`, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success('Assignment deleted successfully!');
                        fetchStudentInventories();
                    },
                    onError: () => {
                        alert.error('Failed to delete assignment. Please try again.');
                    },
                });
            }
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
            return { label: status, variant: 'secondary' as const };
    }
};

const calculateFinalPrice = (item: any) => {
    let price = item.unit_price_snapshot;
    if (item.discount_percentage > 0) {
        price = price - (price * (item.discount_percentage / 100));
    } else if (item.discount_amount > 0) {
        price = price - item.discount_amount;
    }
    return price.toFixed(2);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Student Inventory" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                        Student Inventory Assignments
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Track inventory items assigned to students.
                    </p>
                </div>
                <Button @click="openAssignPage" class="min-h-11">
                    <Icon icon="user-plus" class="mr-2" />
                    Assign to Student
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <select v-model="campusFilter" class="w-full sm:w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-11">
                    <option value="">All Campuses</option>
                    <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                        {{ campus.name }}
                    </option>
                </select>
                <select v-model="statusFilter" class="w-full sm:w-40 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-11">
                    <option value="">All Status</option>
                    <option value="assigned">Assigned</option>
                    <option value="partial_return">Partial Return</option>
                    <option value="returned">Returned</option>
                </select>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    #
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Student
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Item
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Quantity
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Price
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Date
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="(item, index) in studentInventoriesData" :key="item.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ (pagination.from || 0) + index }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ item.student_name }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ item.registration_number }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ item.item_name_snapshot }}
                                        </div>
                                        <div class="text-xs text-gray-500 truncate max-w-xs">
                                            {{ item.description_snapshot || 'No description' }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        <span class="font-bold">{{ item.quantity - item.returned_quantity }}</span>
                                        <span class="text-gray-500"> / {{ item.quantity }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        <span class="line-through text-gray-400">{{ formatCurrency(item.unit_price_snapshot) }}</span>
                                        <span class="font-bold text-green-600 ml-2">{{ formatCurrency(calculateFinalPrice(item)) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Badge :variant="getStatusBadge(item.status).variant">
                                        {{ getStatusBadge(item.status).label }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ formatDate(item.assigned_date) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                    <div class="flex flex-wrap gap-2">
                                        <Button
                                            v-if="item.status !== 'returned'"
                                            variant="outline"
                                            size="sm"
                                            @click="openReturnPage(item)"
                                            class="min-h-11"
                                        >
                                            <Icon icon="rotate-ccw" class="mr-1" />Return
                                        </Button>
                                        <Button variant="destructive" size="sm" @click="deleteAssignment(item)" class="min-h-11">
                                            <Icon icon="trash" class="mr-1" />Delete
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
                        @click="link.url && fetchStudentInventories(parseInt(link.url.split('=').pop() || '1'))"
                    >
                        <span v-html="link.label"></span>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
