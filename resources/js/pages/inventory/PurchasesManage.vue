<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { alert, formatDate, formatCurrency } from '@/utils';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';
import SupplierForm from '@/components/forms/inventory/SupplierForm.vue';
import DateRangePicker from '@/components/ui/date-range-picker/DateRangePicker.vue';
import ComboboxInput from '@/components/ui/combobox/ComboboxInput.vue';

interface Props {
    purchases: any;
    suppliers: any;
    purchaseReturns: any;
    campuses: any;
}

const props = defineProps<Props>();
const activeTab = ref('purchases');
const campusFilter = ref('');

// Purchase filters
const searchQuery = ref('');
const dateRange = ref<{ from: string | null; to: string | null }>({ from: null, to: null });

// Purchase Return filters
const returnsSearchQuery = ref('');
const returnsDateRange = ref<{ from: string | null; to: string | null }>({ from: null, to: null });

// Pagination state
const perPage = ref(25);

// Data refs
const purchasesData = ref<any[]>([]);
const suppliersData = ref<any[]>([]);
const purchaseReturnsData = ref<any[]>([]);

// Pagination for each tab
const purchasesPagination = ref({ current_page: 1, last_page: 1, per_page: 25, total: 0, from: 0, to: 0 });
const suppliersPagination = ref({ current_page: 1, last_page: 1, per_page: 25, total: 0, from: 0, to: 0 });
const returnsPagination = ref({ current_page: 1, last_page: 1, per_page: 25, total: 0, from: 0, to: 0 });

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory', href: '/inventory' },
    { title: 'Purchases', href: '/inventory/purchases-manage' },
];

const fetchPurchases = () => {
    const params: Record<string, any> = {
        page: purchasesPagination.value.current_page,
        per_page: perPage.value,
    };
    if (campusFilter.value) params.campus_id = campusFilter.value;
    if (searchQuery.value) params.q = searchQuery.value;
    if (dateRange.value?.from) params.from_date = dateRange.value.from;
    if (dateRange.value?.to) params.to_date = dateRange.value.to;
    
    axios.get('/inventory/purchases/all', { params }).then((response) => {
        const result = response.data;
        purchasesData.value = result.data || [];
        if (result.pagination) {
            purchasesPagination.value = result.pagination;
        }
    });
};

const fetchSuppliers = () => {
    const params: Record<string, any> = {
        page: suppliersPagination.value.current_page,
        per_page: perPage.value,
    };
    if (campusFilter.value) params.campus_id = campusFilter.value;
    
    axios.get('/inventory/suppliers/all', { params }).then((response) => {
        const result = response.data;
        suppliersData.value = result.data || [];
        if (result.pagination) {
            suppliersPagination.value = result.pagination;
        }
    });
};

const fetchPurchaseReturns = () => {
    const params: Record<string, any> = {
        page: returnsPagination.value.current_page,
        per_page: perPage.value,
    };
    if (campusFilter.value) params.campus_id = campusFilter.value;
    if (returnsSearchQuery.value) params.q = returnsSearchQuery.value;
    if (returnsDateRange.value?.from) params.from_date = returnsDateRange.value.from;
    if (returnsDateRange.value?.to) params.to_date = returnsDateRange.value.to;
    
    axios.get('/inventory/purchase-returns/all', { params }).then((response) => {
        const result = response.data;
        purchaseReturnsData.value = result.data || [];
        if (result.pagination) {
            returnsPagination.value = result.pagination;
        }
    });
};

const toggleActiveSupplier = (supplier: any) => {
    const action = supplier.is_active ? 'inactivate' : 'activate';
    alert.confirm(`Are you sure you want to ${action} "${supplier.name}"?`, `${action.charAt(0).toUpperCase() + action.slice(1)} Supplier`)
        .then((result) => {
            if (result.isConfirmed) {
                router.patch(route(`inventory.suppliers.${action}`, supplier.id), {}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success(`Supplier ${action}d successfully!`);
                        fetchSuppliers();
                    },
                    onError: () => {
                        alert.error('Failed to update supplier. Please try again.');
                    },
                });
            }
        });
};

// Supplier edit state
const editingSupplier = ref<any>(null);

const handleSupplierSaved = () => {
    editingSupplier.value = null;
    fetchSuppliers();
};

const handleDeletePurchase = (purchase: any) => {
    alert.confirm(`Are you sure you want to delete purchase #${purchase.id}?`, 'Delete Purchase')
        .then((result) => {
            if (result.isConfirmed) {
                axios.delete(`/inventory/purchases/${purchase.id}`).then(() => {
                    alert.success('Purchase deleted successfully!');
                    fetchPurchases();
                }).catch((error) => {
                    alert.error(error.response?.data?.message || 'Failed to delete purchase');
                });
            }
        });
};

const handleDeletePurchaseReturn = (returnItem: any) => {
    alert.confirm(`Are you sure you want to delete purchase return #${returnItem.id}?`, 'Delete Purchase Return')
        .then((result) => {
            if (result.isConfirmed) {
                axios.delete(`/inventory/purchase-returns/${returnItem.id}`).then(() => {
                    alert.success('Purchase return deleted successfully!');
                    fetchPurchaseReturns();
                }).catch((error) => {
                    alert.error(error.response?.data?.message || 'Failed to delete purchase return');
                });
            }
        });
};

// Watch for tab changes and fetch data
watch(activeTab, (newTab) => {
    if (newTab === 'purchases') {
        fetchPurchases();
    } else if (newTab === 'suppliers') {
        fetchSuppliers();
    } else if (newTab === 'returns') {
        fetchPurchaseReturns();
    }
});

// Watch for perPage changes
watch(perPage, () => {
    if (activeTab.value === 'purchases') {
        purchasesPagination.value.current_page = 1;
        fetchPurchases();
    } else if (activeTab.value === 'suppliers') {
        suppliersPagination.value.current_page = 1;
        fetchSuppliers();
    } else if (activeTab.value === 'returns') {
        returnsPagination.value.current_page = 1;
        fetchPurchaseReturns();
    }
});

// Initial fetch
fetchPurchases();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Purchases" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Purchases & Suppliers
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Manage purchases, suppliers, and purchase returns.
                </p>
            </div>

            <!-- Tabs - Horizontal scroll on mobile -->
            <div class="border-b border-gray-200 overflow-x-auto dark:border-gray-700">
                <nav class="-mb-px flex min-w-full space-x-8">
                    <button
                        @click="activeTab = 'purchases'"
                        :class="[
                            activeTab === 'purchases'
                                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                            'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap transition-colors',
                        ]"
                    >
                        <Icon icon="shopping-cart" class="inline mr-1" />
                        Purchases
                    </button>
                    <button
                        @click="activeTab = 'suppliers'"
                        :class="[
                            activeTab === 'suppliers'
                                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                            'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap transition-colors',
                        ]"
                    >
                        <Icon icon="truck" class="inline mr-1" />
                        Suppliers
                    </button>
                    <button
                        @click="activeTab = 'returns'"
                        :class="[
                            activeTab === 'returns'
                                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                            'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap transition-colors',
                        ]"
                    >
                        <Icon icon="corner-up-left" class="inline mr-1" />
                        Purchase Returns
                    </button>
                </nav>
            </div>

            <!-- Filters & Actions Bar -->
            <div class="flex flex-col gap-3 mb-4">
                <!-- Filters Row with horizontal scroll on mobile -->
                <div class="overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0">
                    <div class="flex items-center gap-2 min-w-max">
                        <!-- Campus Dropdown -->
                        <select 
                            v-model="campusFilter" 
                            @change="() => {
                                if (activeTab === 'purchases') fetchPurchases();
                                if (activeTab === 'suppliers') fetchSuppliers();
                                if (activeTab === 'returns') fetchPurchaseReturns();
                            }" 
                            class="w-32 md:w-36 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm h-9 flex-shrink-0"
                        >
                            <option value="">All Campuses</option>
                            <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                                {{ campus.name }}
                            </option>
                        </select>

                        <!-- Date Range Filter (Purchases) -->
                        <DateRangePicker
                            v-if="activeTab === 'purchases'"
                            v-model="dateRange"
                            class="flex-shrink-0"
                            @update:modelValue="fetchPurchases"
                        />

                        <!-- Date Range Filter (Purchase Returns) -->
                        <DateRangePicker
                            v-if="activeTab === 'returns'"
                            v-model="returnsDateRange"
                            class="flex-shrink-0"
                            @update:modelValue="fetchPurchaseReturns"
                        />

                        <!-- Search Filter (Purchases) -->
                        <div v-if="activeTab === 'purchases'" class="min-w-[150px] md:min-w-[200px] flex-shrink-0">
                            <ComboboxInput
                                v-model="searchQuery"
                                placeholder="Search..."
                                :search-url="'/inventory/purchases/all?limit=1'"
                                :debounce="300"
                                @update:modelValue="fetchPurchases"
                            />
                        </div>

                        <!-- Search Filter (Purchase Returns) -->
                        <div v-if="activeTab === 'returns'" class="min-w-[150px] md:min-w-[200px] flex-shrink-0">
                            <ComboboxInput
                                v-model="returnsSearchQuery"
                                placeholder="Search..."
                                :search-url="'/inventory/purchase-returns/all?limit=1'"
                                :debounce="300"
                                @update:modelValue="fetchPurchaseReturns"
                            />
                        </div>
                    </div>
                </div>
                
                <!-- Add Button Row -->
                <div class="flex justify-start sm:justify-end">
                    <Button v-if="activeTab === 'purchases'" @click="router.visit('/inventory/purchases/create')" class="w-full sm:w-auto">
                        <Icon icon="plus" class="mr-1" />
                        Add Purchase
                    </Button>
                    <SupplierForm v-if="activeTab === 'suppliers'" :campuses="props.campuses" @saved="fetchSuppliers" />
                    <Button v-if="activeTab === 'returns'" @click="router.visit('/inventory/purchase-returns/create')" class="w-full sm:w-auto">
                        <Icon icon="plus" class="mr-1" />
                        Add Return
                    </Button>
                </div>
            </div>

            <!-- ==================== PURCHASES TAB ==================== -->
            <div v-if="activeTab === 'purchases'">
                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm -mx-4 md:mx-0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Sr#</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Purchase ID</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Date</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Campus</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Supplier</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Items</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Total</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                                <tr v-for="(purchase, index) in purchasesData" :key="purchase.id" class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ purchasesPagination.from + index }}</td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-600 dark:text-blue-400">{{ purchase.purchase_id || '#' + purchase.id }}</td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ formatDate(purchase.purchase_date) }}</td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ purchase.campus_name || 'All Campuses' }}</td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ purchase.supplier?.name || '-' }}</td>
                                    <td class="px-3 md:px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        <span v-if="purchase.items_count > 0">{{ purchase.item_names || '-' }}</span>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">{{ formatCurrency(purchase.total_amount) }}</td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-wrap gap-1 md:gap-2">
                                            <Button variant="outline" size="sm" @click="router.visit(`/inventory/purchases/${purchase.id}/view`)" class="text-xs md:text-sm">
                                                <Icon icon="eye" class="mr-1" /> <span class="hidden sm:inline">View</span>
                                            </Button>
                                            <Button variant="outline" size="sm" @click="router.visit(`/inventory/purchases/${purchase.id}/edit`)" class="text-xs md:text-sm">
                                                <Icon icon="edit" class="mr-1" /> <span class="hidden sm:inline">Edit</span>
                                            </Button>
                                            <Button variant="destructive" size="sm" @click="handleDeletePurchase(purchase)" class="text-xs md:text-sm">
                                                <Icon icon="trash-2" class="mr-1" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="purchasesData.length === 0">
                                    <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">No purchases found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4 px-2">
                    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-400 order-2 sm:order-1">
                        <span>Show</span>
                        <select 
                            v-model="perPage" 
                            class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2 py-1 text-sm"
                        >
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="75">75</option>
                            <option :value="100">100</option>
                        </select>
                        <span>entries</span>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center gap-2 order-1 sm:order-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400 text-center sm:text-left">
                            Showing {{ purchasesPagination.from || 0 }} to {{ purchasesPagination.to || 0 }} of {{ purchasesPagination.total }}
                        </span>
                        <div class="flex gap-1">
                            <Button 
                                variant="outline" 
                                size="sm" 
                                :disabled="purchasesPagination.current_page <= 1"
                                @click="purchasesPagination.current_page--; fetchPurchases()"
                            >
                                <Icon icon="chevron-left" class="w-4 h-4" />
                            </Button>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                :disabled="purchasesPagination.current_page >= purchasesPagination.last_page"
                                @click="purchasesPagination.current_page++; fetchPurchases()"
                            >
                                <Icon icon="chevron-right" class="w-4 h-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== SUPPLIERS TAB ==================== -->
            <div v-if="activeTab === 'suppliers'">
                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm -mx-4 md:mx-0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Sr#</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Name</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Contact</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Phone</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Email</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Campus</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Status</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                                <tr v-for="(supplier, index) in suppliersData" :key="supplier.id" class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ suppliersPagination.from + index }}</td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ supplier.name }}</td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ supplier.contact_person || '-' }}</td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ supplier.phone || '-' }}</td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ supplier.email || '-' }}</td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ supplier.campus?.name || 'All Campuses' }}</td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                                        <Badge :variant="supplier.is_active ? 'default' : 'destructive'">
                                            {{ supplier.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-wrap gap-1 md:gap-2">
                                            <Button variant="outline" size="sm" @click="editingSupplier = supplier" class="text-xs md:text-sm">
                                                <Icon icon="edit" class="mr-1" />
                                            </Button>
                                            <Button :variant="supplier.is_active ? 'destructive' : 'default'" size="sm" @click="toggleActiveSupplier(supplier)" class="text-xs md:text-sm">
                                                <Icon :icon="supplier.is_active ? 'eye-off' : 'eye'" class="mr-1" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="suppliersData.length === 0">
                                    <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">No suppliers found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4 px-2">
                    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-400 order-2 sm:order-1">
                        <span>Show</span>
                        <select 
                            v-model="perPage" 
                            class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2 py-1 text-sm"
                        >
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="75">75</option>
                            <option :value="100">100</option>
                        </select>
                        <span>entries</span>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center gap-2 order-1 sm:order-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400 text-center sm:text-left">
                            Showing {{ suppliersPagination.from || 0 }} to {{ suppliersPagination.to || 0 }} of {{ suppliersPagination.total }}
                        </span>
                        <div class="flex gap-1">
                            <Button 
                                variant="outline" 
                                size="sm" 
                                :disabled="suppliersPagination.current_page <= 1"
                                @click="suppliersPagination.current_page--; fetchSuppliers()"
                            >
                                <Icon icon="chevron-left" class="w-4 h-4" />
                            </Button>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                :disabled="suppliersPagination.current_page >= suppliersPagination.last_page"
                                @click="suppliersPagination.current_page++; fetchSuppliers()"
                            >
                                <Icon icon="chevron-right" class="w-4 h-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== PURCHASE RETURNS TAB ==================== -->
            <div v-if="activeTab === 'returns'">
                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm -mx-4 md:mx-0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Sr#</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Return ID</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Date</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Campus</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Supplier</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Items</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Total</th>
                                    <th class="px-3 md:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                                <tr v-for="(returnItem, index) in purchaseReturnsData" :key="returnItem.id" class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ returnsPagination.from + index }}</td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-600 dark:text-blue-400">{{ returnItem.purchase_return_id || '#' + returnItem.id }}</td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ formatDate(returnItem.return_date) }}</td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ returnItem.campus_name || 'All Campuses' }}</td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ returnItem.supplier?.name || '-' }}</td>
                                    <td class="px-3 md:px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        <span v-if="returnItem.items_count > 0">{{ returnItem.item_names || '-' }}</span>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">{{ formatCurrency(returnItem.total_amount) }}</td>
                                    <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-wrap gap-1 md:gap-2">
                                            <Button variant="outline" size="sm" @click="router.visit(`/inventory/purchase-returns/${returnItem.id}`)" class="text-xs md:text-sm">
                                                <Icon icon="eye" class="mr-1" /> <span class="hidden sm:inline">View</span>
                                            </Button>
                                            <Button variant="outline" size="sm" @click="router.visit(`/inventory/purchase-returns/${returnItem.id}/edit`)" class="text-xs md:text-sm">
                                                <Icon icon="edit" class="mr-1" /> <span class="hidden sm:inline">Edit</span>
                                            </Button>
                                            <Button variant="destructive" size="sm" @click="handleDeletePurchaseReturn(returnItem)" class="text-xs md:text-sm">
                                                <Icon icon="trash-2" class="mr-1" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="purchaseReturnsData.length === 0">
                                    <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">No purchase returns found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4 px-2">
                    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-400 order-2 sm:order-1">
                        <span>Show</span>
                        <select 
                            v-model="perPage" 
                            class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2 py-1 text-sm"
                        >
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="75">75</option>
                            <option :value="100">100</option>
                        </select>
                        <span>entries</span>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center gap-2 order-1 sm:order-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400 text-center sm:text-left">
                            Showing {{ returnsPagination.from || 0 }} to {{ returnsPagination.to || 0 }} of {{ returnsPagination.total }}
                        </span>
                        <div class="flex gap-1">
                            <Button 
                                variant="outline" 
                                size="sm" 
                                :disabled="returnsPagination.current_page <= 1"
                                @click="returnsPagination.current_page--; fetchPurchaseReturns()"
                            >
                                <Icon icon="chevron-left" class="w-4 h-4" />
                            </Button>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                :disabled="returnsPagination.current_page >= returnsPagination.last_page"
                                @click="returnsPagination.current_page++; fetchPurchaseReturns()"
                            >
                                <Icon icon="chevron-right" class="w-4 h-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Supplier Modal -->
        <SupplierForm
            v-if="editingSupplier"
            :supplier="editingSupplier"
            :campuses="props.campuses"
            :open="!!editingSupplier"
            @update:open="editingSupplier = $event ? editingSupplier : null"
            @saved="handleSupplierSaved"
        />
    </AppLayout>
</template>
