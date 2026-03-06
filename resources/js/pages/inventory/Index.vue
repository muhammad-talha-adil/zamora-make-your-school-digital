<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import type { Ref } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import { formatDate } from '@/utils/date';
import InventoryTypeForm from '@/components/forms/InventoryTypeForm.vue';
import SupplierForm from '@/components/forms/inventory/SupplierForm.vue';
import ItemForm from '@/components/forms/inventory/ItemForm.vue';
import PurchaseForm from '@/components/forms/inventory/PurchaseForm.vue';
import AdjustmentForm from '@/components/forms/inventory/AdjustmentForm.vue';
import PurchaseReturnForm from '@/components/forms/inventory/PurchaseReturnForm.vue';
import ReturnForm from '@/components/forms/inventory/ReturnForm.vue';
import StudentInventoryAssignForm from '@/components/forms/inventory/StudentInventoryAssignForm.vue';

// Types
interface Stats {
    types: number;
    items: number;
    totalStock: number;
    availableStock: number;
    lowStockItems: number;
    purchases: number;
    totalPurchaseValue: number;
    assignedItems: number;
    pendingReturns: number;
}

interface RecentActivity {
    id: number;
    type: 'purchase' | 'assignment' | 'return';
    description: string;
    date: string;
    created_at: string;
}

interface LowStockItem {
    id: number;
    item_id: number;
    item_name: string;
    available_quantity: number;
    low_stock_threshold: number;
    campus_name: string;
}

interface Campus {
    id: number;
    name: string;
}

interface InventoryType {
    id: number;
    name: string;
    campus_id: number;
}

interface InventoryItem {
    id: number;
    campus_id: number;
    inventory_type_id: number;
    name: string;
    description?: string;
    current_stock?: number;
    purchase_rate: number;
    sale_rate: number;
    available_stock: number;
    low_stock_threshold?: number;
    is_low_stock?: boolean;
}

interface Student {
    id: number;
    name: string;
    registration_number: string;
}

interface Supplier {
    id: number;
    campus_id: number;
    name: string;
}

interface PurchaseItem {
    id: number;
    inventory_item_id: number;
    item_name: string;
    quantity: number;
    purchase_rate: number;
}

interface RecentPurchase {
    id: number;
    purchase_id: string;
    purchase_date: string;
    supplier_name: string;
    total_amount: number;
    items: PurchaseItem[];
}

interface Purchase {
    id: number;
    campus_id: number;
    supplier_id: number;
    supplier: {
        id: number;
        name: string;
    } | null;
    purchase_date: string;
    items?: Array<{
        id: number;
        inventory_item_id: number;
        item_name: string;
        purchase_rate: number;
    }>;
}

interface StudentInventoryReturn {
    id: number;
    campus_id: number;
    student_inventory_id: number;
    student_name: string;
    registration_number: string;
    item_name: string;
    quantity: number;
    returned_quantity: number;
    remaining: number;
    status: string;
    assigned_date: string;
    discount_amount: number;
    discount_percentage: number;
}

// Quick action button config
interface QuickAction {
    key: string;
    label: string;
    icon: string;
    color: string;
    colorBg: string;
}

const quickActions: QuickAction[] = [
    { key: 'types', label: 'Add Type', icon: 'tag', color: 'text-blue-600', colorBg: 'bg-blue-100' },
    { key: 'supplier', label: 'Add Supplier', icon: 'truck', color: 'text-emerald-600', colorBg: 'bg-emerald-100' },
    { key: 'item', label: 'Add Item', icon: 'box', color: 'text-violet-600', colorBg: 'bg-violet-100' },
    { key: 'purchase', label: 'New Purchase', icon: 'shopping-cart', color: 'text-amber-600', colorBg: 'bg-amber-100' },
    { key: 'assign', label: 'Assign', icon: 'user-plus', color: 'text-cyan-600', colorBg: 'bg-cyan-100' },
    { key: 'purchaseReturn', label: 'Purchase Return', icon: 'rotate-ccw', color: 'text-orange-600', colorBg: 'bg-orange-100' },
    { key: 'return', label: 'Process Return', icon: 'rotate-cw', color: 'text-rose-600', colorBg: 'bg-rose-100' },
    { key: 'adjustment', label: 'Adjust Stock', icon: 'sliders', color: 'text-slate-600', colorBg: 'bg-slate-100' },
];

// State
const loading = ref(true);
const selectedCampusId = ref<number | null>(null);
const stats = ref<Stats>({
    types: 0,
    items: 0,
    totalStock: 0,
    availableStock: 0,
    lowStockItems: 0,
    purchases: 0,
    totalPurchaseValue: 0,
    assignedItems: 0,
    pendingReturns: 0,
});
const recentActivities = ref<RecentActivity[]>([]);
const lowStockItems = ref<LowStockItem[]>([]);
const recentPurchases = ref<RecentPurchase[]>([]);
const campuses = ref<Campus[]>([]);
const inventoryTypes = ref<InventoryType[]>([]);
const inventoryItems = ref<InventoryItem[]>([]);
const students = ref<Student[]>([]);
const suppliers = ref<Supplier[]>([]);
const purchases = ref<Purchase[]>([]);
const studentInventories = ref<StudentInventoryReturn[]>([]);

// Refs for triggering modals
const typeFormRef = ref<any>(null);
const supplierFormRef = ref<any>(null);
const itemFormRef = ref<any>(null);
const purchaseFormRef = ref<any>(null);
const adjustmentFormRef = ref<any>(null);
const purchaseReturnFormRef = ref<any>(null);
const returnFormRef = ref<any>(null);
const assignFormRef = ref<any>(null);

// Form refs mapping
const formRefs: Record<string, Ref<any>> = {
    types: typeFormRef,
    supplier: supplierFormRef,
    item: itemFormRef,
    purchase: purchaseFormRef,
    adjustment: adjustmentFormRef,
    purchaseReturn: purchaseReturnFormRef,
    return: returnFormRef,
    assign: assignFormRef,
};

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory', href: '/inventory' },
];

// Get campus query param on mount
onMounted(async () => {
    // Get campuses first
    try {
        const campusesRes = await axios.get('/settings/campuses/all');
        campuses.value = campusesRes.data || [];
        
        // Check URL for campus_id param - use it if present, otherwise default to null (All Campuses)
        const urlParams = new URLSearchParams(window.location.search);
        const campusParam = urlParams.get('campus_id');
        if (campusParam) {
            selectedCampusId.value = parseInt(campusParam);
        }
        // Otherwise keep selectedCampusId as null (All Campuses) - don't auto-select
    } catch (error) {
        console.error('Failed to fetch campuses:', error);
    }
    
    // Fetch all data
    await fetchAllData();
});

// Watch for campus changes
watch(selectedCampusId, (newCampusId) => {
    fetchAllData();
    
    // Update URL without reload
    const url = new URL(window.location.href);
    if (newCampusId) {
        url.searchParams.set('campus_id', newCampusId.toString());
    } else {
        url.searchParams.delete('campus_id');
    }
    window.history.replaceState({}, '', url.toString());
});

// Computed stat cards
const statCards = computed(() => [
    {
        title: 'Types',
        value: stats.value.types,
        icon: 'tags',
        color: 'text-blue-600 dark:text-blue-400',
        bgColor: 'bg-blue-50 dark:bg-blue-900/30',
        href: '/inventory/types',
    },
    {
        title: 'Items',
        value: stats.value.items,
        icon: 'box',
        color: 'text-emerald-600 dark:text-emerald-400',
        bgColor: 'bg-emerald-50 dark:bg-emerald-900/30',
        href: '/inventory/items',
    },
    {
        title: 'Total Stock',
        value: stats.value.totalStock.toLocaleString(),
        icon: 'package',
        color: 'text-violet-600 dark:text-violet-400',
        bgColor: 'bg-violet-50 dark:bg-violet-900/30',
        href: '/inventory/stocks',
    },
    {
        title: 'Available',
        value: stats.value.availableStock.toLocaleString(),
        icon: 'check-circle',
        color: 'text-teal-600 dark:text-teal-400',
        bgColor: 'bg-teal-50 dark:bg-teal-900/30',
        href: '/inventory/stocks',
    },
    {
        title: 'Low Stock',
        value: stats.value.lowStockItems,
        icon: 'alert-triangle',
        color: stats.value.lowStockItems > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-500',
        bgColor: stats.value.lowStockItems > 0 ? 'bg-red-50 dark:bg-red-900/30' : 'bg-slate-50 dark:bg-slate-800',
        href: '/inventory/stocks?low_stock_only=true',
    },
    {
        title: 'Purchases',
        value: stats.value.purchases,
        icon: 'shopping-cart',
        color: 'text-amber-600 dark:text-amber-400',
        bgColor: 'bg-amber-50 dark:bg-amber-900/30',
        href: '/inventory/purchases',
    },
    {
        title: 'Value',
        value: `$${stats.value.totalPurchaseValue.toLocaleString()}`,
        icon: 'dollar-sign',
        color: 'text-emerald-600 dark:text-emerald-400',
        bgColor: 'bg-emerald-50 dark:bg-emerald-900/30',
        href: '/inventory/purchases',
    },
    {
        title: 'Assigned',
        value: stats.value.assignedItems,
        icon: 'user-check',
        color: 'text-cyan-600 dark:text-cyan-400',
        bgColor: 'bg-cyan-50 dark:bg-cyan-900/30',
        href: '/inventory/student-inventory',
    },
    {
        title: 'Returns',
        value: stats.value.pendingReturns,
        icon: 'rotate-ccw',
        color: 'text-rose-600 dark:text-rose-400',
        bgColor: 'bg-rose-50 dark:bg-rose-900/30',
        href: '/inventory/returns',
    },
]);

// Computed stock status - based on actual items with stock data
const stockStatus = computed(() => {
    const items = inventoryItems.value;
    if (items.length === 0) {
        return { healthy: 0, low: 0, outOfStock: 0 };
    }
    
    const healthy = items.filter(item => (item.available_stock ?? 0) > (item.low_stock_threshold ?? 10)).length;
    const low = items.filter(item => (item.available_stock ?? 0) > 0 && (item.available_stock ?? 0) <= (item.low_stock_threshold ?? 10)).length;
    const outOfStock = items.filter(item => (item.available_stock ?? 0) <= 0).length;
    
    return {
        healthy: Math.round((healthy / items.length) * 100),
        low: Math.round((low / items.length) * 100),
        outOfStock: Math.round((outOfStock / items.length) * 100),
    };
});

// Computed top moving items - fixed to use quantity instead of purchase_rate
const topMovingItems = computed(() => {
    const itemCounts: Record<number, { name: string; quantity: number; purchaseCount: number }> = {};
    
    recentPurchases.value.forEach(purchase => {
        purchase.items?.forEach(item => {
            if (!itemCounts[item.inventory_item_id]) {
                itemCounts[item.inventory_item_id] = {
                    name: item.item_name,
                    quantity: 0,
                    purchaseCount: 0,
                };
            }
            // Fixed: Use quantity instead of purchase_rate
            itemCounts[item.inventory_item_id].quantity += item.quantity || 0;
            itemCounts[item.inventory_item_id].purchaseCount += 1;
        });
    });
    
    return Object.values(itemCounts)
        .sort((a, b) => b.quantity - a.quantity)
        .slice(0, 5);
});

// Activity icons
const getActivityIcon = (type: string) => {
    switch (type) {
        case 'purchase': return 'shopping-cart';
        case 'assignment': return 'user-plus';
        case 'return': return 'rotate-ccw';
        default: return 'activity';
    }
};

const getActivityColor = (type: string) => {
    switch (type) {
        case 'purchase': return 'text-indigo-600 bg-indigo-100 dark:text-indigo-400 dark:bg-indigo-900/50';
        case 'assignment': return 'text-emerald-600 bg-emerald-100 dark:text-emerald-400 dark:bg-emerald-900/50';
        case 'return': return 'text-amber-600 bg-amber-100 dark:text-amber-400 dark:bg-amber-900/50';
        default: return 'text-slate-600 bg-slate-100 dark:text-slate-400 dark:bg-slate-800';
    }
};

// Data fetching - now with campus filter
const fetchAllData = async () => {
    loading.value = true;
    
    try {
        const campusParam = selectedCampusId.value ? `?campus_id=${selectedCampusId.value}` : '';
        
        // Fetch dashboard data with campus filter
        const dashboardRes = await axios.get(`/inventory/dashboard-data${campusParam}`);
        const dashboardData = dashboardRes.data;
        
        stats.value = dashboardData.stats || {
            types: 0,
            items: 0,
            totalStock: 0,
            availableStock: 0,
            lowStockItems: 0,
            purchases: 0,
            totalPurchaseValue: 0,
            assignedItems: 0,
            pendingReturns: 0,
        };
        
        // Ensure lowStockItems is always an array
        recentActivities.value = Array.isArray(dashboardData.recent_activities) ? dashboardData.recent_activities : [];
        lowStockItems.value = Array.isArray(dashboardData.low_stock_items) ? dashboardData.low_stock_items : [];
        recentPurchases.value = Array.isArray(dashboardData.recent_purchases) ? dashboardData.recent_purchases : [];
        
        // Fetch common data with campus filter
        const campusQuery = selectedCampusId.value ? `?campus_id=${selectedCampusId.value}` : '';
        const results = await Promise.all([
            axios.get(`/inventory/types/all${campusQuery}`),
            axios.get(`/inventory/items/all${campusQuery}`),
            axios.get(`/inventory/student-inventory/students/with-inventory${campusQuery}`),
            axios.get(`/inventory/suppliers/all${campusQuery}`),
            axios.get(`/inventory/purchases/all${campusQuery}`),
            axios.get(`/inventory/returns/all${campusQuery}`),
        ]).catch(errors => {
            console.warn('Some API calls failed:', errors);
            return [null, null, null, null, null, null];
        });
        
        const [typesRes, itemsRes, studentsRes, suppliersRes, purchasesRes, returnsRes] = results;
        
        inventoryTypes.value = Array.isArray(typesRes?.data) ? typesRes.data : [];
        inventoryItems.value = ((itemsRes?.data || []) as Array<{id: number; campus_id: number; inventory_type_id?: number; name: string; description?: string; current_stock: number; purchase_rate?: number; sale_rate?: number; available_stock?: number; low_stock_threshold?: number; is_low_stock?: boolean}>).map(item => ({
            id: item.id,
            campus_id: item.campus_id,
            inventory_type_id: item.inventory_type_id || 0,
            name: item.name,
            description: item.description || '',
            purchase_rate: item.purchase_rate || 0,
            sale_rate: item.sale_rate || 0,
            available_stock: item.available_stock ?? item.current_stock ?? 0,
            low_stock_threshold: item.low_stock_threshold,
            is_low_stock: item.is_low_stock,
        }));
        students.value = Array.isArray(studentsRes?.data) ? studentsRes.data : [];
        suppliers.value = Array.isArray(suppliersRes?.data) ? suppliersRes.data : [];
        purchases.value = Array.isArray(purchasesRes?.data) ? purchasesRes.data : [];
        studentInventories.value = ((returnsRes?.data || []) as Array<{id: number; campus_id: number; student_inventory_id: number; student_name: string; registration_number: string; item_name: string; quantity: number; returned_quantity: number; remaining: number; status: string; assigned_date: string; discount_amount: number; discount_percentage: number}>).map(item => ({
            id: item.id,
            campus_id: item.campus_id,
            student_inventory_id: item.student_inventory_id,
            student_name: item.student_name,
            registration_number: item.registration_number,
            item_name: item.item_name,
            quantity: item.quantity,
            returned_quantity: item.returned_quantity,
            remaining: item.remaining,
            status: item.status,
            assigned_date: item.assigned_date,
            discount_amount: item.discount_amount,
            discount_percentage: item.discount_percentage,
        }));
    } catch (error) {
        console.error('Failed to fetch data:', error);
    } finally {
        loading.value = false;
    }
};

const handleSaved = () => {
    fetchAllData();
};

const openModal = (key: string) => {
    // For assign, navigate to the new create page instead of opening a modal
    if (key === 'assign') {
        router.get('/inventory/student-inventory/create');
        return;
    }
    
    const ref = formRefs[key];
    if (ref.value?.$el) {
        const button = ref.value.$el.querySelector('button') as HTMLButtonElement;
        if (button) {
            button.click();
        }
    }
};

const handleCampusChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    selectedCampusId.value = target.value ? parseInt(target.value) : null;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Inventory Dashboard" />

        <div class="space-y-6 p-4 md:p-6 lg:p-8 max-w-screen-2xl mx-auto">
            <!-- Header -->
            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
                        Inventory Management
                    </h1>
                    <p class="mt-1 text-sm md:text-base text-gray-600 dark:text-gray-400">
                        Track and manage your school inventory, purchases, and distributions.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Campus Selector -->
                    <div class="flex items-center gap-2">
                        <label for="campus-select" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Campus:
                        </label>
                        <select
                            id="campus-select"
                            :value="selectedCampusId || ''"
                            @change="handleCampusChange"
                            class="px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary/50 focus:border-primary"
                        >
                            <option value="">All Campuses</option>
                            <option v-for="campus in campuses" :key="campus.id" :value="campus.id">
                                {{ campus.name }}
                            </option>
                        </select>
                    </div>
                    <Button variant="outline" size="sm" @click="fetchAllData" class="gap-2">
                        <Icon icon="refresh" class="h-4 w-4" />
                        <span class="hidden sm:inline">Refresh</span>
                    </Button>
                </div>
            </header>

            <!-- Stats Cards Grid -->
            <section aria-label="Inventory Statistics">
                <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3 md:gap-4">
                    <article 
                        v-for="stat in statCards" 
                        :key="stat.title"
                        class="group bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 md:p-5 hover:shadow-lg hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-200"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs md:text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                    {{ stat.title }}
                                </p>
                                <p class="mt-2 text-2xl md:text-3xl font-bold" :class="stat.color">
                                    {{ loading ? '...' : stat.value }}
                                </p>
                            </div>
                            <div :class="['p-2.5 rounded-lg transition-transform group-hover:scale-110', stat.bgColor]">
                                <Icon :icon="stat.icon" :class="`h-5 w-5 md:h-6 md:w-6 ${stat.color}`" />
                            </div>
                        </div>
                        <a 
                            :href="stat.href" 
                            class="mt-3 inline-flex items-center text-xs md:text-sm text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-primary/50 rounded"
                        >
                            View details
                            <Icon icon="arrow-right" class="ml-1 h-3 w-3" />
                        </a>
                    </article>
                </div>
            </section>

            <!-- Quick Actions -->
            <section aria-label="Quick Actions" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Quick Actions
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2 md:gap-3">
                    <div 
                        v-for="action in quickActions" 
                        :key="action.key"
                        class="relative"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            class="w-full h-10 md:h-11 justify-center gap-1.5 px-2 text-xs md:text-sm truncate border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 focus:ring-2 focus:ring-primary/50"
                            @click="openModal(action.key)"
                            :title="action.label"
                        >
                            <Icon :icon="action.icon" :class="`h-4 w-4 shrink-0 ${action.color}`" />
                            <span class="truncate">{{ action.label }}</span>
                        </Button>
                    </div>
                </div>
            </section>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
                <!-- Recent Activity -->
                <section aria-labelledby="activity-heading" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                    <header class="flex items-center justify-between mb-4">
                        <h2 id="activity-heading" class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <Icon icon="clock" class="h-5 w-5 text-gray-500" />
                            Recent Activity
                        </h2>
                    </header>
                    
                    <div v-if="loading" class="flex justify-center py-12" aria-label="Loading activities">
                        <Icon icon="loader" class="animate-spin h-8 w-8 text-gray-400" />
                    </div>
                    
                    <div v-else-if="recentActivities.length === 0" class="text-center py-12 text-gray-500">
                        <Icon icon="activity" :size="48" class="mx-auto mb-3 text-gray-300 dark:text-gray-600" />
                        <p>No recent activity</p>
                    </div>
                    
                    <ul v-else class="space-y-3" role="list">
                        <li 
                            v-for="activity in recentActivities" 
                            :key="activity.id"
                            class="flex items-start gap-3 p-3 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors focus-within:bg-gray-50 dark:focus-within:bg-gray-700/50 focus-within:ring-2 focus-within:ring-primary/20 rounded-md"
                        >
                            <div :class="['p-2 rounded-full shrink-0', getActivityColor(activity.type)]">
                                <Icon :icon="getActivityIcon(activity.type)" :size="16" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ activity.description }}
                                </p>
                                <time class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatDate(activity.date) }}
                                </time>
                            </div>
                        </li>
                    </ul>
                </section>

                <!-- Low Stock Alerts -->
                <section aria-labelledby="alerts-heading" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                    <header class="flex items-center justify-between mb-4">
                        <h2 id="alerts-heading" class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <Icon icon="alert-triangle" :class="`h-5 w-5 ${stats.lowStockItems > 0 ? 'text-red-500' : 'text-gray-500'}`" />
                            Low Stock Alerts
                        </h2>
                    </header>
                    
                    <div v-if="loading" class="flex justify-center py-12" aria-label="Loading alerts">
                        <Icon icon="loader" class="animate-spin h-8 w-8 text-gray-400" />
                    </div>
                    
                    <div v-else-if="lowStockItems.length === 0" class="text-center py-12 text-gray-500">
                        <Icon icon="check-circle" :size="48" class="mx-auto mb-3 text-emerald-400" />
                        <p>All items are well stocked!</p>
                    </div>
                    
                    <div v-else class="space-y-3">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 bg-red-50 dark:bg-red-900/20 rounded-lg gap-3 border border-red-100 dark:border-red-900/30">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-red-100 dark:bg-red-900/40 rounded-full">
                                    <Icon icon="alert-triangle" class="h-5 w-5 text-red-600 dark:text-red-400" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ lowStockItems.length }} items need restocking
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Review and update stock levels
                                    </p>
                                </div>
                            </div>
                            <a 
                                href="/inventory/stocks?low_stock_only=true" 
                                class="inline-flex items-center gap-1 text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 focus:outline-none focus:ring-2 focus:ring-red-500/50 rounded px-3 py-1.5"
                            >
                                View all
                                <Icon icon="arrow-right" class="h-4 w-4" />
                            </a>
                        </div>
                        
                        <!-- List actual low stock items -->
                        <div class="mt-3 space-y-2">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Items below threshold:</h3>
                            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                                <li 
                                    v-for="item in lowStockItems.slice(0, 5)" 
                                    :key="item.id"
                                    class="py-2 flex justify-between items-center"
                                >
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ item.item_name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Threshold: {{ item.low_stock_threshold }}
                                        </p>
                                    </div>
                                    <div class="text-right ml-4">
                                        <p class="text-sm font-semibold text-red-600 dark:text-red-400">
                                            {{ item.available_quantity }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">available</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Overview Section -->
            <section aria-labelledby="overview-heading" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                <h2 id="overview-heading" class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                    Inventory Overview
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
                    <!-- By Type -->
                    <article class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                            <Icon icon="tags" class="h-4 w-4" />
                            By Type
                        </h3>
                        <div v-if="loading" class="space-y-3" aria-label="Loading types">
                            <div v-for="i in 3" :key="i" class="h-10 bg-gray-200 dark:bg-gray-600 rounded animate-pulse"></div>
                        </div>
                        <div v-else-if="inventoryTypes.length === 0" class="text-center py-6 text-gray-500 text-sm">
                            <Icon icon="inbox" :size="32" class="mx-auto mb-2 text-gray-300" />
                            <p>No types found</p>
                        </div>
                        <div v-else class="space-y-2">
                            <div class="flex justify-between items-center p-2 rounded bg-white dark:bg-gray-800">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Total Types</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ inventoryTypes.length }}</span>
                            </div>
                            <div 
                                v-for="type in inventoryTypes.slice(0, 4)" 
                                :key="type.id"
                                class="flex justify-between items-center p-2 rounded bg-white dark:bg-gray-800"
                            >
                                <span class="text-sm text-gray-600 dark:text-gray-400 truncate max-w-37.5" :title="type.name">{{ type.name }}</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ inventoryItems.filter(item => item.inventory_type_id === type.id).length }}
                                </span>
                            </div>
                        </div>
                    </article>

                    <!-- Stock Status -->
                    <article class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                            <Icon icon="package" class="h-4 w-4" />
                            Stock Status
                        </h3>
                        <div v-if="loading" class="space-y-4" aria-label="Loading stock status">
                            <div v-for="i in 3" :key="i" class="h-6 bg-gray-200 dark:bg-gray-600 rounded animate-pulse"></div>
                        </div>
                        <div v-else-if="inventoryItems.length === 0" class="text-center py-6 text-gray-500 text-sm">
                            <Icon icon="inbox" :size="32" class="mx-auto mb-2 text-gray-300" />
                            <p>No items found</p>
                        </div>
                        <template v-else>
                            <!-- Healthy -->
                            <div class="mb-4">
                                <div class="flex justify-between text-sm mb-1.5">
                                    <span class="text-gray-600 dark:text-gray-400">Healthy</span>
                                    <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ stockStatus.healthy }}%</span>
                                </div>
                                <div class="h-2.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" :style="{ width: stockStatus.healthy + '%' }"></div>
                                </div>
                            </div>
                            <!-- Low Stock -->
                            <div class="mb-4">
                                <div class="flex justify-between text-sm mb-1.5">
                                    <span class="text-gray-600 dark:text-gray-400">Low Stock</span>
                                    <span class="font-medium text-amber-600 dark:text-amber-400">{{ stockStatus.low }}%</span>
                                </div>
                                <div class="h-2.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                    <div class="h-full bg-amber-500 rounded-full transition-all duration-500" :style="{ width: stockStatus.low + '%' }"></div>
                                </div>
                            </div>
                            <!-- Out of Stock -->
                            <div>
                                <div class="flex justify-between text-sm mb-1.5">
                                    <span class="text-gray-600 dark:text-gray-400">Out of Stock</span>
                                    <span class="font-medium text-red-600 dark:text-red-400">{{ stockStatus.outOfStock }}%</span>
                                </div>
                                <div class="h-2.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                    <div class="h-full bg-red-500 rounded-full transition-all duration-500" :style="{ width: stockStatus.outOfStock + '%' }"></div>
                                </div>
                            </div>
                        </template>
                    </article>

                    <!-- Top Moving Items -->
                    <article class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                            <Icon icon="trending-up" class="h-4 w-4" />
                            Top Moving Items
                        </h3>
                        <div v-if="loading" class="space-y-3" aria-label="Loading top items">
                            <div v-for="i in 5" :key="i" class="h-12 bg-gray-200 dark:bg-gray-600 rounded animate-pulse"></div>
                        </div>
                        <div v-else-if="topMovingItems.length === 0" class="text-center py-6 text-gray-500 text-sm">
                            <Icon icon="bar-chart" :size="32" class="mx-auto mb-2 text-gray-300" />
                            <p>No data available</p>
                            <p class="text-xs mt-1">Start recording purchases</p>
                        </div>
                        <ul v-else class="space-y-2" role="list">
                            <li 
                                v-for="(item, index) in topMovingItems" 
                                :key="index"
                                class="flex items-center justify-between p-2 rounded bg-white dark:bg-gray-800"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center text-xs font-bold text-primary shrink-0">
                                        {{ index + 1 }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ item.name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ item.purchaseCount }} purchases</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ item.quantity }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">units</p>
                                </div>
                            </li>
                        </ul>
                    </article>
                </div>
            </section>

            <!-- Form Modals - Positioned off-screen but still rendered in DOM -->
            <Teleport to="body">
                <div class="fixed -top-full left-0" aria-hidden="true">
                    <div ref="typeFormRef">
                        <InventoryTypeForm :campuses="campuses" trigger="Add Type" @saved="handleSaved" />
                    </div>
                    <div ref="supplierFormRef">
                        <SupplierForm :campuses="campuses" trigger="Add Supplier" @saved="handleSaved" />
                    </div>
                    <div ref="itemFormRef">
                        <ItemForm :campuses="campuses" :inventory-types="inventoryTypes" trigger="Add Item" @saved="handleSaved" />
                    </div>
                    <div ref="purchaseFormRef">
                        <PurchaseForm :campuses="campuses" :suppliers="suppliers" :inventory-items="inventoryItems" trigger="New Purchase" @saved="handleSaved" />
                    </div>
                    <div ref="assignFormRef">
                        <StudentInventoryAssignForm :campuses="campuses" :students="students" :inventory-items="inventoryItems" trigger="Assign" @saved="handleSaved" />
                    </div>
                    <div ref="purchaseReturnFormRef">
                        <PurchaseReturnForm :campuses="campuses" :suppliers="suppliers" :purchases="purchases" trigger="Purchase Return" @saved="handleSaved" />
                    </div>
                    <div ref="returnFormRef">
                        <ReturnForm :campuses="campuses" :student-inventories="studentInventories" trigger="Process Return" @saved="handleSaved" />
                    </div>
                    <div ref="adjustmentFormRef">
                        <AdjustmentForm :campuses="campuses" :inventory-items="inventoryItems" trigger="Adjust Stock" @saved="handleSaved" />
                    </div>
                </div>
            </Teleport>
        </div>
    </AppLayout>
</template>
