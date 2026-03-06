<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { alert, formatCurrency } from '@/utils';
import { ref, watch } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';

interface Props {
    returns: {
        data: Array<{
            id: number;
            campus_id: number;
            campus_name: string;
            student_inventory_id: number;
            student_name: string;
            registration_number: string;
            item_name_snapshot: string;
            quantity: number;
            unit_price_snapshot: number;
            discount_snapshot: any;
            return_date: string;
            total_value: number;
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

const perPage = ref(10);
const campusFilter = ref('');
const returnsData = ref(props.returns.data || []);
const pagination = ref(props.returns);

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
        title: 'Returns',
        href: '/inventory/returns',
    },
];

const fetchReturns = (pageNum = 1) => {
    const params = new URLSearchParams({
        per_page: perPage.value.toString(),
        page: pageNum.toString(),
    });

    if (campusFilter.value) {
        params.append('campus_id', campusFilter.value);
    }

    axios.get(`/inventory/returns/all?${params}`).then((response) => {
        returnsData.value = response.data.data;
        pagination.value = response.data;
    });
};

watch([perPage, campusFilter], () => {
    fetchReturns();
});

watch(() => props.returns, (newReturns) => {
    returnsData.value = newReturns.data || [];
    pagination.value = newReturns;
}, { deep: true });

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const calculateFinalPrice = (item: any) => {
    let price = item.unit_price_snapshot;
    const discount = item.discount_snapshot;
    if (discount) {
        if (discount.discount_percentage > 0) {
            price = price - (price * (discount.discount_percentage / 100));
        } else if (discount.discount_amount > 0) {
            price = price - discount.discount_amount;
        }
    }
    return price.toFixed(2);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Inventory Returns" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                        Inventory Returns
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Track all returned inventory items from students.
                    </p>
                </div>
                <Button onclick="window.location.href='/inventory/returns/create'" class="min-h-11">
                    <Icon icon="rotate-ccw" class="mr-2" />
                    Process Return
                </Button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-sm text-gray-500">Total Returns</div>
                    <div class="text-2xl font-bold">{{ pagination.total }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-sm text-gray-500">Items Returned</div>
                    <div class="text-2xl font-bold">
                        {{ (returnsData || []).reduce((sum, r) => sum + (r.quantity || 0), 0).toLocaleString() }}
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-sm text-gray-500">Total Value</div>
                    <div class="text-2xl font-bold text-green-600">
                        {{ formatCurrency((returnsData || []).reduce((sum, r) => sum + (r.total_value || 0), 0)) }}
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <select v-model="campusFilter" class="w-full sm:w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-11">
                    <option value="">All Campuses</option>
                    <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                        {{ campus.name }}
                    </option>
                </select>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">#</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Student</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Item</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Qty</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Unit Price</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Total</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="(returnItem, index) in returnsData" :key="returnItem.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ (pagination.from || 0) + index }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ returnItem.student_name }}</div>
                                    <div class="text-xs text-gray-500">{{ returnItem.registration_number }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ returnItem.item_name_snapshot }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                                    {{ returnItem.quantity }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ formatCurrency(calculateFinalPrice(returnItem)) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600">
                                    {{ formatCurrency(returnItem.total_value) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ formatDate(returnItem.return_date) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

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
                        @click="link.url && fetchReturns(parseInt(link.url.split('=').pop() || '1'))"
                    >
                        <span v-html="link.label"></span>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
