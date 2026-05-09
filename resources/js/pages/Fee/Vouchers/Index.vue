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
import { formatCurrency } from '@/utils/currency';
import { formatDate } from '@/utils/date';
import { tableActionButtonClass } from '@/utils/table-actions';

interface FeeVoucher {
    id: number;
    voucher_no: string;
    student: { id: number; name: string; registration_number: string };
    voucher_month: { id: number; name: string };
    voucher_year: number;
    issue_date: string;
    due_date: string;
    status: string;
    gross_amount: number;
    discount_amount: number;
    fine_amount: number;
    net_amount: number;
    balance_amount: number;
}

interface Props {
    vouchers: {
        data: FeeVoucher[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        from: number;
        to: number;
    };
    months: Array<{ id: number; name: string }>;
    campuses: Array<{ id: number; name: string }>;
    classes: Array<{ id: number; name: string }>;
    sections: Array<{ id: number; name: string; class_id: number }>;
    filters?: {
        campus_id?: string;
        class_id?: string;
        section_id?: string;
        month_id?: string;
        year?: string;
        status?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

const vouchersData = ref<FeeVoucher[]>(props.vouchers?.data || []);
const pagination = ref(props.vouchers || { data: [], links: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1, per_page: 50 });
const perPage = ref(props.vouchers?.per_page || 50);
const selectedVouchers = ref<number[]>([]);
const selectAll = ref(false);
const isLoading = ref(false);

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Vouchers', href: '/fee/vouchers' },
];

// Current year for defaults
const currentYear = new Date().getFullYear();

// Available years (2000 to current year + 1)
const years = Array.from({ length: (currentYear + 1) - 2000 + 1 }, (_, i) => 2000 + i);

const filters = reactive({
    campus_id: props.filters?.campus_id || '',
    class_id: props.filters?.class_id || '',
    section_id: props.filters?.section_id || '',
    month_id: props.filters?.month_id || '',
    year: props.filters?.year || currentYear.toString(),
    status: props.filters?.status || '',
    search: props.filters?.search || '',
});

const perPageOptions = [
    { id: 25, name: '25' },
    { id: 50, name: '50' },
    { id: 100, name: '100' },
    { id: 250, name: '250' },
];

// Filtered sections based on selected class
const filteredSections = computed(() => {
    if (!filters.class_id) return [];
    return props.sections.filter(s => s.class_id === Number(filters.class_id));
});

// Watch for campus changes to reset class and section
watch(() => filters.campus_id, () => {
    filters.class_id = '';
    filters.section_id = '';
});

const buildParams = (page = 1) => {
    const params: Record<string, string | number> = {
        per_page: perPage.value,
        page: page,
    };

    if (filters.campus_id) params.campus_id = filters.campus_id;
    if (filters.class_id) params.class_id = filters.class_id;
    if (filters.section_id) params.section_id = filters.section_id;
    if (filters.month_id) params.month_id = filters.month_id;
    if (filters.year) params.year = filters.year;
    if (filters.status) params.status = filters.status;
    if (filters.search) params.search = filters.search;

    return params;
};

const syncSelection = () => {
    selectedVouchers.value = vouchersData.value.map((voucher) => voucher.id);
    selectAll.value = vouchersData.value.length > 0;
};

const fetchVouchers = (page = 1) => {
    const params = buildParams(page);
    const url = route('fee.vouchers.list');

    isLoading.value = true;

    axios.get(url, { params }).then((response) => {
        vouchersData.value = response.data?.data || [];
        pagination.value = response.data || { data: [], links: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1, per_page: 50 };
        syncSelection();
    }).catch((error) => {
        console.error('Failed to fetch vouchers:', error);
        vouchersData.value = [];
        pagination.value = { data: [], links: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1, per_page: 50 };
        selectedVouchers.value = [];
        selectAll.value = false;
    }).finally(() => {
        isLoading.value = false;
    });
};

// Load vouchers button handler
const loadVouchers = (page = 1) => {
    const params = buildParams(page);
    const queryString = new URLSearchParams(params).toString();
    const newUrl = route('fee.vouchers.index') + (queryString ? `?${queryString}` : '');
    window.history.pushState({}, '', newUrl);
    fetchVouchers(page);
};

// Check if filters exist in URL on page load and fetch vouchers
const urlParams = new URLSearchParams(window.location.search);
const hasFilters = urlParams.has('campus_id') || urlParams.has('class_id') || urlParams.has('section_id') || 
                   urlParams.has('month_id') || urlParams.has('year') || urlParams.has('status') || urlParams.has('search');

// If page was loaded with filters in URL, fetch vouchers automatically
if (hasFilters) {
    fetchVouchers();
} else {
    syncSelection();
}

watch(perPage, () => {
    loadVouchers(1);
});

// Watch for props changes (e.g., after create/delete)
watch(() => props.vouchers, (newVouchers) => {
    if (newVouchers && typeof newVouchers === 'object' && 'data' in newVouchers) {
        vouchersData.value = newVouchers.data || [];
        pagination.value = newVouchers || { data: [], links: [], from: 0, to: 0, total: 0, current_page: 1, last_page: 1, per_page: 50 };
    }
    syncSelection();
}, { deep: true });

// Toggle select all
const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedVouchers.value = vouchersData.value.map((voucher) => voucher.id);
    } else {
        selectedVouchers.value = [];
    }
};

// Toggle single voucher
const toggleVoucher = (voucherId: number) => {
    const index = selectedVouchers.value.indexOf(voucherId);
    if (index === -1) {
        selectedVouchers.value.push(voucherId);
    } else {
        selectedVouchers.value.splice(index, 1);
    }

    selectAll.value = vouchersData.value.length > 0 && selectedVouchers.value.length === vouchersData.value.length;
};

// Check if voucher is selected
const isSelected = (voucherId: number) => {
    return selectedVouchers.value.includes(voucherId);
};

// Print selected vouchers
const printSelectedVouchers = () => {
    if (selectedVouchers.value.length === 0) {
        alert('Please select at least one voucher to print');
        return;
    }
    
    // Open batch print page in new window with selected voucher IDs (public route - no auth needed)
    const url = '/fee/print-voucher/batch?voucher_ids=' + selectedVouchers.value.join(',');
    window.open(url, '_blank');
};

// Print single voucher
const printVoucher = (voucherId: number) => {
    window.open('/fee/print-voucher/' + voucherId, '_blank');
};

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        unpaid: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        partial: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        paid: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        overdue: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
    };
    return colors[status] || colors.unpaid;
};

const isOverdueVoucher = (voucher: FeeVoucher) => {
    if (voucher.status === 'paid' || voucher.status === 'cancelled') {
        return false;
    }

    return new Date(voucher.due_date) < new Date() && voucher.balance_amount > 0;
};

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Fee Vouchers" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Fee Vouchers
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        View and manage student fee vouchers
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button 
                        v-if="selectedVouchers.length > 0"
                        @click="printSelectedVouchers"
                        variant="outline"
                        :class="tableActionButtonClass.print"
                    >
                        <Icon icon="printer" class="mr-2 h-4 w-4" />
                        Print Selected ({{ selectedVouchers.length }})
                    </Button>
                    <Button @click="router.visit(route('fee.vouchers.generate.form'))">
                        <Icon icon="file-plus" class="mr-2 h-4 w-4" />
                        Generate Vouchers
                    </Button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="space-y-2">
                    <Label for="filter-campus">Campus</Label>
                    <select
                        id="filter-campus"
                        v-model="filters.campus_id"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
                    >
                        <option value="">All Campuses</option>
                        <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                            {{ campus.name }}
                        </option>
                    </select>
                    </div>
                    <div class="space-y-2">
                    <Label for="filter-class">Class</Label>
                    <select
                        id="filter-class"
                        v-model="filters.class_id"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
                    >
                        <option value="">All Classes</option>
                        <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">
                            {{ cls.name }}
                        </option>
                    </select>
                    </div>
                    <div class="space-y-2">
                    <Label for="filter-section">Section</Label>
                    <select
                        id="filter-section"
                        v-model="filters.section_id"
                        :disabled="!filters.class_id"
                        :class="[
                            'w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white',
                            !filters.class_id ? 'opacity-50 cursor-not-allowed' : '',
                        ]"
                    >
                        <option value="">All Sections</option>
                        <option v-for="section in filteredSections" :key="section.id" :value="section.id">
                            {{ section.name }}
                        </option>
                    </select>
                    </div>
                    <div class="space-y-2">
                    <Label for="filter-month">Month</Label>
                    <select
                        id="filter-month"
                        v-model="filters.month_id"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
                    >
                        <option value="">All Months</option>
                        <option v-for="month in props.months" :key="month.id" :value="month.id">
                            {{ month.name }}
                        </option>
                    </select>
                    </div>
                    <div class="space-y-2">
                    <Label for="filter-year">Year</Label>
                    <select
                        id="filter-year"
                        v-model="filters.year"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
                    >
                        <option value="">All Years</option>
                        <option v-for="year in years" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                    </div>
                    <div class="space-y-2">
                    <Label for="filter-status">Status</Label>
                    <select
                        id="filter-status"
                        v-model="filters.status"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
                    >
                        <option value="">All Status</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="partial">Partial</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    </div>
                    <div class="space-y-2 md:col-span-4">
                    <Label for="search-voucher">Search</Label>
                    <Input
                        id="search-voucher"
                        v-model="filters.search"
                        placeholder="Search vouchers..."
                    />
                    </div>
                    <div class="flex items-end">
                    <Button @click="loadVouchers" class="w-full md:w-auto">
                        <Icon icon="search" class="mr-2 h-4 w-4" />
                        Load Vouchers
                    </Button>
                    </div>
                </div>
            </div>

            <!-- Mobile Card View -->
            <div class="block lg:hidden space-y-3">
                <div
                    v-for="(voucher, index) in vouchersData"
                    :key="voucher?.id"
                    :class="[
                        'rounded-lg border p-4 space-y-2',
                        isOverdueVoucher(voucher)
                            ? 'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950/20'
                            : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800'
                    ]"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-xs text-gray-500">Sr# {{ ((pagination.from || 1) - 1) + index + 1 }}</div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ voucher.voucher_no }}</div>
                            <div class="text-xs text-gray-500">{{ voucher.student?.name || 'N/A' }}</div>
                        </div>
                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(voucher.status)]">
                            {{ voucher.status }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1 pt-2 border-t border-gray-100 dark:border-gray-700">
                        <div>Month: {{ voucher.voucher_month?.name || 'N/A' }} {{ voucher.voucher_year }}</div>
                        <div>Due: {{ formatDate(voucher.due_date) }}</div>
                        <div>Amount: {{ formatCurrency(voucher.net_amount) }}</div>
                        <div>Balance: {{ formatCurrency(voucher.balance_amount) }}</div>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <Button variant="outline" size="sm" :class="tableActionButtonClass.view" @click="router.visit(route('fee.vouchers.show', voucher.id))">
                            <Icon icon="eye" class="mr-1" />View
                        </Button>
                        <Button variant="outline" size="sm" :class="tableActionButtonClass.print" @click="printVoucher(voucher.id)">
                            <Icon icon="printer" class="mr-1" />Print
                        </Button>
                    </div>
                </div>
                <div v-if="isLoading" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    Loading vouchers...
                </div>
                <div v-else-if="vouchersData.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    No vouchers found.
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden lg:block overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-2 py-3 text-center text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    <input
                                        type="checkbox"
                                        :checked="selectAll"
                                        @change="toggleSelectAll"
                                        class="w-4 h-4 rounded"
                                    />
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Sr#
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Voucher No
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Student
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Month/Year
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Due Date
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Amount
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Balance
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Status
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr
                                v-for="(voucher, index) in vouchersData"
                                :key="voucher.id"
                                :class="[
                                    'transition-colors',
                                    isOverdueVoucher(voucher)
                                        ? 'bg-red-50 hover:bg-red-100 dark:bg-red-950/15 dark:hover:bg-red-950/25'
                                        : 'hover:bg-gray-50 dark:hover:bg-gray-800'
                                ]"
                            >
                                <td class="px-2 py-3 whitespace-nowrap text-center">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected(voucher.id)"
                                        @change="toggleVoucher(voucher.id)"
                                        class="w-4 h-4 rounded"
                                    />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ ((pagination.from || 1) - 1) + index + 1 }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ voucher.voucher_no }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ voucher.student?.name || 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">{{ voucher.student?.registration_number || 'N/A' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ voucher.voucher_month?.name || 'N/A' }} {{ voucher.voucher_year }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ formatDate(voucher.due_date) }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ formatCurrency(voucher.net_amount) }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ formatCurrency(voucher.balance_amount) }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(voucher.status)]">
                                        {{ voucher.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                    <div class="flex gap-2 justify-end">
                                        <Button variant="outline" size="sm" :class="tableActionButtonClass.view" @click="router.visit(route('fee.vouchers.show', voucher.id))">
                                            <Icon icon="eye" class="mr-1 h-3 w-3" />View
                                        </Button>
                                        <Button variant="outline" size="sm" :class="tableActionButtonClass.print" @click="printVoucher(voucher.id)">
                                            <Icon icon="printer" class="mr-1 h-3 w-3" />Print
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="isLoading" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    Loading vouchers...
                </div>
                <div v-else-if="vouchersData.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    No vouchers found.
                </div>
            </div>

            <!-- Pagination -->
            <div class="flex justify-between items-center pt-4">
                <div class="flex items-center gap-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                    </div>
                    <select v-model="perPage" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 w-20">
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
                        @click="link.url ? loadVouchers(parseInt(link.url.match(/page=(\d+)/)?.[1] || '1')) : null"
                    >
                        <span v-html="link.label"></span>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
