<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref, computed, watch } from 'vue';
import { route } from 'ziggy-js';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Icon from '@/components/Icon.vue';
import { alert } from '@/utils';

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
const pagination = ref(props.feeHeads || { data: [], links: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1, per_page: 10 });
const perPage = ref(props.feeHeads.per_page || 10);

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
    const newUrl = queryString ? `${window.location.pathname}?${queryString}` : window.location.pathname;
    window.history.pushState({}, '', newUrl);
    fetchFeeHeads(1);
};

const perPageOptions = [
    { id: 10, name: '10' },
    { id: 25, name: '25' },
    { id: 50, name: '50' },
    { id: 100, name: '100' },
];

const fetchFeeHeads = (page = 1) => {
    const params = new URLSearchParams({
        per_page: perPage.value.toString(),
        page: page.toString(),
    });
    if (filters.search) params.append('search', filters.search);
    if (filters.category) params.append('category', filters.category);
    if (filters.is_active !== '') params.append('is_active', filters.is_active);

    axios.get(route('fee.heads.all') + `?${params}`).then((response) => {
        feeHeadsData.value = response.data?.data || [];
        pagination.value = response.data || { data: [], links: [], from: 0, to: 0, total: 0 };
    }).catch((error) => {
        console.error('Failed to fetch fee heads:', error);
        alert.error('Failed to load fee heads. Please try again.');
    });
};

watch([perPage], () => {
    fetchFeeHeads(1);
});

// Watch for props changes (e.g., after create/update/delete)
watch(() => props.feeHeads, (newFeeHeads) => {
    feeHeadsData.value = newFeeHeads?.data || [];
    pagination.value = newFeeHeads || { data: [], links: [], from: 0, to: 0, total: 0 };
}, { deep: true });

const toggleActive = (feeHead: FeeHead) => {
    const actionText = feeHead.is_active ? 'deactivate' : 'activate';
    const confirmButtonText = feeHead.is_active ? 'Yes, deactivate it!' : 'Yes, activate it!';
    
    alert
        .confirm(
            `Are you sure you want to ${actionText} "${feeHead.name}"?`,
            actionText.charAt(0).toUpperCase() + actionText.slice(1) + ' Fee Head',
            confirmButtonText,
        )
        .then((result) => {
            if (result.isConfirmed) {
                axios.post(route('fee.heads.toggle-active', feeHead.id), {})
                    .then(() => {
                        feeHead.is_active = !feeHead.is_active;
                        alert.success(`Fee head ${actionText}d successfully!`);
                    })
                    .catch(() => {
                        alert.error('Failed to update status. Please try again.');
                    });
            }
        });
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
        tuition: 'bg-primary/10 text-primary',
        transport: 'bg-warning/10 text-warning',
        hostel: 'bg-primary/10 text-primary',
        library: 'bg-success/10 text-success',
        examination: 'bg-destructive/10 text-destructive',
        other: 'bg-muted text-foreground',
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
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">
                        Fee Heads
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
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
                        class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm min-h-10 md:min-h-11"
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
                        class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm min-h-10 md:min-h-11"
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
                    class="bg-card rounded-lg border border-border p-4 space-y-2"
                >
                    <div class="flex flex-wrap gap-2 justify-between items-start">
                        <div>
                            <div class="text-xs text-muted-foreground">Sr# {{ ((pagination.from || 1) - 1) + index + 1 }}</div>
                            <div class="font-medium text-foreground">{{ feeHead.name }}</div>
                            <div class="text-xs text-muted-foreground">Code: {{ feeHead.code }}</div>
                        </div>
                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', getCategoryColor(feeHead.category)]">
                            {{ getCategoryLabel(feeHead.category) }}
                        </span>
                    </div>
                    <div class="text-sm text-muted-foreground space-y-1 pt-2 border-t border-border">
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
                    <div v-if="feeHeadsData.length === 0" class="text-center py-8 text-muted-foreground">
                        No fee heads found.
                    </div>
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden lg:block overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">
                                    Sr#
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">
                                    Order
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">
                                    Code
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">
                                    Name
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">
                                    Category
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">
                                    Frequency
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">
                                    Status
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-muted-foreground uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="(feeHead, index) in sortedFeeHeads" :key="feeHead.id" class="transition-colors hover:bg-accent">
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-foreground">{{ ((pagination.from || 1) - 1) + index + 1 }}</div>
                            </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-foreground">{{ feeHead.sort_order }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-foreground">{{ feeHead.code }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-foreground">{{ feeHead.name }}</div>
                                    <div v-if="feeHead.description" class="text-xs text-muted-foreground">{{ feeHead.description }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getCategoryColor(feeHead.category)]">
                                        {{ getCategoryLabel(feeHead.category) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm text-muted-foreground">{{ getFrequencyLabel(feeHead.default_frequency) }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', feeHead.is_active ? 'bg-success/10 text-success' : 'bg-muted text-foreground']">
                                        {{ feeHead.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                    <div class="flex flex-wrap gap-2 justify-end">
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

            <!-- Pagination -->
            <div class="flex flex-wrap gap-2 justify-between items-center pt-4">
                <div class="flex items-center gap-4">
                    <div class="text-sm text-muted-foreground">
                        Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                    </div>
                    <select v-model="perPage" class="rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm min-h-10 w-20">
                        <option v-for="option in perPageOptions" :key="option.id" :value="option.id">
                            {{ option.name }}
                        </option>
                    </select>
                </div>
                <div class="flex gap-1">
                    <Button
                        v-for="link in pagination.links"
                        :key="link.label"
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        :disabled="!link.url"
                        @click="link.url ? fetchFeeHeads(parseInt(link.url.match(/page=(\d+)/)?.[1] || '1')) : null"
                    >
                        <span v-html="link.label"></span>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>