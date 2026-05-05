<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref, computed } from 'vue';
import { route } from 'ziggy-js';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Icon from '@/components/Icon.vue';

interface FeeHead {
    id: number;
    name: string;
    code: string;
    description?: string;
    category: string;
    default_frequency: string;
    is_active: boolean;
    is_optional: boolean;
    sort_order: number;
}

interface Props {
    feeHeads: {
        data: FeeHead[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters?: {
        search?: string;
        category?: string;
        is_active?: string;
    };
    categories: Array<{ value: string; label: string }>;
}

const props = defineProps<Props>();

const feeHeadsData = ref<FeeHead[]>(props.feeHeads.data);

// Sort fee heads by sort_order ascending
const sortedFeeHeads = computed(() => {
    return [...feeHeadsData.value].sort((a, b) => {
        return (a.sort_order || 0) - (b.sort_order || 0);
    });
});

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Fee Heads', href: '/fee/heads' },
];

const filters = reactive({
    search: props.filters?.search || '',
    category: props.filters?.category || '',
    is_active: props.filters?.is_active !== undefined && props.filters?.is_active !== null 
        ? String(props.filters.is_active) 
        : '',
});

let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

const onSearchInput = () => {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }
    searchDebounceTimer = window.setTimeout(() => {
        applyFilters();
    }, 300);
};

const applyFilters = () => {
    const params = new URLSearchParams();
    if (filters.search) params.append('search', filters.search);
    if (filters.category) params.append('category', filters.category);
    if (filters.is_active !== '') params.append('is_active', filters.is_active);
    
    const queryString = params.toString();
    const newUrl = route('fee.heads.index') + (queryString ? `?${queryString}` : '');
    window.history.pushState({}, '', newUrl);
    fetchFeeHeads();
};

const fetchFeeHeads = () => {
    const params = new URLSearchParams();
    if (filters.search) params.append('search', filters.search);
    if (filters.category) params.append('category', filters.category);
    if (filters.is_active !== '') params.append('is_active', filters.is_active);

    axios.get(route('fee.heads.index') + `?${params.toString()}`).then((response) => {
        feeHeadsData.value = response.data.feeHeads?.data || response.data.feeHeads || [];
    });
};

const toggleActive = (feeHead: FeeHead) => {
    axios.post(route('fee.heads.toggle-active', feeHead.id), {})
        .then(() => {
            feeHead.is_active = !feeHead.is_active;
        })
        .catch(console.error);
};

const deleteFeeHead = (feeHead: FeeHead) => {
    if (!confirm(`Are you sure you want to delete \"${feeHead.name}\"?`)) {
        return;
    }
    axios.delete(route('fee.heads.destroy', feeHead.id))
        .then(() => {
            fetchFeeHeads();
        })
        .catch(console.error);
};

const getCategoryColor = (category: string) => {
    const colors = {
        tuition: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        transport: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        hostel: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
        library: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        examination: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        other: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
    };
    return colors[category] || colors.other;
};

const getCategoryLabel = (category: string) => {
    const labels: Record<string, string> = {
        tuition: 'Tuition',
        transport: 'Transport',
        hostel: 'Hostel',
        library: 'Library',
        examination: 'Examination',
        other: 'Other',
    };
    return labels[category] || category;
};

const getFrequencyLabel = (frequency: string) => {
    const labels: Record<string, string> = {
        monthly: 'Monthly',
        yearly: 'Yearly',
        once: 'One Time',
    };
    return labels[frequency] || frequency;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Fee Heads" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Fee Heads
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Manage fee categories and heads
                    </p>
                </div>
                <Button @click="router.visit(route('fee.heads.create'))">
                    <Icon icon="plus" class="mr-2 h-4 w-4" />
                    Create Fee Head
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-2 md:gap-3 flex-wrap">
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="search" class="sr-only">Search</Label>
                    <Input
                        id="search"
                        v-model="filters.search"
                        @input="onSearchInput"
                        placeholder="Search fee heads..."
                        class="min-h-10 md:min-h-11"
                    />
                </div>
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="filter-category" class="sr-only">Category</Label>
                    <select
                        id="filter-category"
                        v-model="filters.category"
                        @change="applyFilters"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Categories</option>
                        <option v-for="cat in props.categories" :key="cat.value" :value="cat.value">
                            {{ cat.label }}
                        </option>
                    </select>
                </div>
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="filter-active" class="sr-only">Status</Label>
                    <select
                        id="filter-active"
                        v-model="filters.is_active"
                        @change="applyFilters"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Status</option>
                        <option value="true">Active</option>
                        <option value="false">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Mobile Card View -->
            <div class="block lg:hidden space-y-3">
                <div
                    v-for="(feeHead, index) in sortedFeeHeads"
                    :key="feeHead.id"
                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-xs text-gray-500">Sr# {{ index + 1 }}</div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ feeHead.name }}</div>
                            <div class="text-xs text-gray-500">Code: {{ feeHead.code }}</div>
                        </div>
                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', getCategoryColor(feeHead.category)]">
                            {{ getCategoryLabel(feeHead.category) }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1 pt-2 border-t border-gray-100 dark:border-gray-700">
                        <div>Frequency: {{ getFrequencyLabel(feeHead.default_frequency) }}</div>
                        <div>Order: {{ feeHead.sort_order }}</div>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <Button @click="toggleActive(feeHead)" :variant="feeHead.is_active ? 'outline' : 'default'" size="sm" class="flex-1">
                            {{ feeHead.is_active ? 'Deactivate' : 'Activate' }}
                        </Button>
                        <Button variant="outline" size="sm" @click="router.visit(route('fee.heads.edit', feeHead.id))">
                            <Icon icon="edit" class="mr-1" />Edit
                        </Button>
                        <Button variant="destructive" size="sm" @click="deleteFeeHead(feeHead)" class="flex-1">
                            <Icon icon="trash" class="mr-1" />Delete
                        </Button>
                    </div>
                    <div v-if="feeHeadsData.length === 0" class="text-center py-8 text-gray-500">
                        No fee heads found.
                    </div>
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden lg:block overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Sr#
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Order
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Code
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Name
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Category
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Frequency
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Status
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="(feeHead, index) in sortedFeeHeads" :key="feeHead.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ index + 1 }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ feeHead.sort_order }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ feeHead.code }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ feeHead.name }}</div>
                                    <div v-if="feeHead.description" class="text-xs text-gray-500">{{ feeHead.description }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getCategoryColor(feeHead.category)]">
                                        {{ getCategoryLabel(feeHead.category) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ getFrequencyLabel(feeHead.default_frequency) }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', feeHead.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400']">
                                        {{ feeHead.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                    <div class="flex gap-2 justify-end">
                                        <Button @click="toggleActive(feeHead)" :variant="feeHead.is_active ? 'outline' : 'default'" size="sm" class="min-w-[80px]">
                                            {{ feeHead.is_active ? 'Deactivate' : 'Activate' }}
                                        </Button>
                                        <Button variant="outline" size="sm" @click="router.visit(route('fee.heads.edit', feeHead.id))">
                                            <Icon icon="edit" class="mr-1 h-3 w-3" />Edit
                                        </Button>
                                        <Button variant="destructive" size="sm" @click="deleteFeeHead(feeHead)">
                                            <Icon icon="trash" class="mr-1 h-3 w-3" />Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>