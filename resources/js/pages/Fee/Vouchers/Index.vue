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
    vouchers: FeeVoucher[] | { data: FeeVoucher[] };
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

// Handle both paginated and array formats and filter null values
const getVouchersArray = (vouchers: Props['vouchers']): FeeVoucher[] => {
    if (Array.isArray(vouchers)) {
        return vouchers.filter(v => v !== null);
    }
    if (vouchers && typeof vouchers === 'object' && 'data' in vouchers) {
        return ((vouchers as { data: FeeVoucher[] }).data || []).filter(v => v !== null);
    }
    return [];
};

const vouchersData = ref<FeeVoucher[]>(getVouchersArray(props.vouchers));
const selectedVouchers = ref<number[]>([]);
const selectAll = ref(false);

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

// Fetch vouchers from API
const fetchVouchers = () => {
    const params = new URLSearchParams();
    if (filters.campus_id) params.append('campus_id', filters.campus_id);
    if (filters.class_id) params.append('class_id', filters.class_id);
    if (filters.section_id) params.append('section_id', filters.section_id);
    if (filters.month_id) params.append('month_id', filters.month_id);
    if (filters.year) params.append('year', filters.year);
    if (filters.status) params.append('status', filters.status);
    if (filters.search) params.append('search', filters.search);

    const url = route('fee.vouchers.list') + `?${params.toString()}`;
    console.log('Fetching vouchers from:', url);
    
    axios.get(url).then((response) => {
        console.log('Response:', response.data);
        // Handle paginated response from Laravel
        const vouchers = response.data.data;
        // Filter out null vouchers
        vouchersData.value = (vouchers || []).filter(v => v !== null);
        
        // Auto-select all vouchers after loading
        selectedVouchers.value = vouchersData.value.map(v => v.id);
        selectAll.value = vouchersData.value.length > 0;
    }).catch((error) => {
        console.error('Error fetching vouchers:', error);
    });
};

// Load vouchers button handler
const loadVouchers = () => {
    const params = new URLSearchParams();
    if (filters.campus_id) params.append('campus_id', filters.campus_id);
    if (filters.class_id) params.append('class_id', filters.class_id);
    if (filters.section_id) params.append('section_id', filters.section_id);
    if (filters.month_id) params.append('month_id', filters.month_id);
    if (filters.year) params.append('year', filters.year);
    if (filters.status) params.append('status', filters.status);
    if (filters.search) params.append('search', filters.search);
    
    const queryString = params.toString();
    const newUrl = route('fee.vouchers.index') + (queryString ? `?${queryString}` : '');
    window.history.pushState({}, '', newUrl);
    fetchVouchers();
};

// Check if filters exist in URL on page load and fetch vouchers
const urlParams = new URLSearchParams(window.location.search);
const hasFilters = urlParams.has('campus_id') || urlParams.has('class_id') || urlParams.has('section_id') || 
                   urlParams.has('month_id') || urlParams.has('year') || urlParams.has('status') || urlParams.has('search');

// If page was loaded with filters in URL, fetch vouchers automatically
if (hasFilters) {
    fetchVouchers();
}

// Toggle select all
const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedVouchers.value = vouchersData.value.map(v => v.id);
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
                <Button 
                    v-if="selectedVouchers.length > 0"
                    @click="printSelectedVouchers"
                    class="mr-2"
                >
                    <Icon icon="printer" class="mr-2 h-4 w-4" />
                    Print Selected ({{ selectedVouchers.length }})
                </Button>
                <Button @click="router.visit(route('fee.vouchers.generate.form'))">
                    <Icon icon="file-plus" class="mr-2 h-4 w-4" />
                    Generate Vouchers
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-2 md:gap-3 flex-wrap">
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="filter-campus" class="sr-only">Filter by Campus</Label>
                    <select
                        id="filter-campus"
                        v-model="filters.campus_id"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Campuses</option>
                        <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                            {{ campus.name }}
                        </option>
                    </select>
                </div>
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="filter-class" class="sr-only">Filter by Class</Label>
                    <select
                        id="filter-class"
                        v-model="filters.class_id"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Classes</option>
                        <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">
                            {{ cls.name }}
                        </option>
                    </select>
                </div>
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="filter-section" class="sr-only">Filter by Section</Label>
                    <select
                        id="filter-section"
                        v-model="filters.section_id"
                        :disabled="!filters.class_id"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11 disabled:opacity-50"
                    >
                        <option value="">All Sections</option>
                        <option v-for="section in filteredSections" :key="section.id" :value="section.id">
                            {{ section.name }}
                        </option>
                    </select>
                </div>
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="filter-month" class="sr-only">Filter by Month</Label>
                    <select
                        id="filter-month"
                        v-model="filters.month_id"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Months</option>
                        <option v-for="month in props.months" :key="month.id" :value="month.id">
                            {{ month.name }}
                        </option>
                    </select>
                </div>
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="filter-year" class="sr-only">Filter by Year</Label>
                    <select
                        id="filter-year"
                        v-model="filters.year"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Years</option>
                        <option v-for="year in years" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="filter-status" class="sr-only">Filter by Status</Label>
                    <select
                        id="filter-status"
                        v-model="filters.status"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
                    >
                        <option value="">All Status</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="partial">Partial</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="w-full sm:w-44 md:w-48">
                    <Label for="search-voucher" class="sr-only">Search</Label>
                    <Input
                        id="search-voucher"
                        v-model="filters.search"
                        placeholder="Search vouchers..."
                        class="min-h-10 md:min-h-11"
                    />
                </div>
                <div class="flex items-end">
                    <Button @click="loadVouchers" class="min-h-10 md:min-h-11">
                        <Icon icon="search" class="mr-2 h-4 w-4" />
                        Load Vouchers
                    </Button>
                </div>
            </div>

            <!-- Mobile Card View -->
            <div class="block lg:hidden space-y-3">
                <div
                    v-for="(voucher, index) in vouchersData"
                    :key="voucher?.id"
                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-xs text-gray-500">Sr# {{ index + 1 }}</div>
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
                        <Button variant="outline" size="sm" @click="router.visit(route('fee.vouchers.show', voucher.id))">
                            <Icon icon="eye" class="mr-1" />View
                        </Button>
                        <Button variant="outline" size="sm" @click="printVoucher(voucher.id)">
                            <Icon icon="printer" class="mr-1" />Print
                        </Button>
                    </div>
                </div>
                <div v-if="vouchersData.length === 0" class="text-center py-8 text-gray-500">
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
                            <tr v-for="(voucher, index) in vouchersData" :key="voucher?.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-2 py-3 whitespace-nowrap text-center">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected(voucher.id)"
                                        @change="toggleVoucher(voucher.id)"
                                        class="w-4 h-4 rounded"
                                    />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ index + 1 }}
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
                                        <Button variant="outline" size="sm" @click="router.visit(route('fee.vouchers.show', voucher.id))">
                                            <Icon icon="eye" class="mr-1 h-3 w-3" />View
                                        </Button>
                                        <Button variant="outline" size="sm" @click="printVoucher(voucher.id)">
                                            <Icon icon="printer" class="mr-1 h-3 w-3" />Print
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
