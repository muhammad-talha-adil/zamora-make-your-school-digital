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
    { key: 'types', label: 'Add Type', icon: 'tag', color: 'text-primary', colorBg: 'bg-primary/10' },
    { key: 'supplier', label: 'Add Supplier', icon: 'truck', color: 'text-success', colorBg: 'bg-success/10' },
    { key: 'item', label: 'Add Item', icon: 'box', color: 'text-primary', colorBg: 'bg-primary/10' },
    { key: 'purchase', label: 'New Purchase', icon: 'shopping-cart', color: 'text-warning', colorBg: 'bg-warning/10' },
    { key: 'assign', label: 'Assign', icon: 'user-plus', color: 'text-info', colorBg: 'bg-info/10' },
    { key: 'purchaseReturn', label: 'Purchase Return', icon: 'rotate-ccw', color: 'text-warning', colorBg: 'bg-warning/10' },
    { key: 'return', label: 'Process Return', icon: 'rotate-cw', color: 'text-destructive', colorBg: 'bg-destructive/10' },
    { key: 'adjustment', label: 'Adjust Stock', icon: 'sliders', color: 'text-muted-foreground', colorBg: 'bg-muted' },
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
        color: 'text-primary',
        bgColor: 'bg-primary/10',
        href: '/inventory/types',
    },
    {
        title: 'Items',
        value: stats.value.items,
        icon: 'box',
        color: 'text-success',
        bgColor: 'bg-success/10',
        href: '/inventory/items',
    },
    {
        title: 'Total Stock',
        value: stats.value.totalStock.toLocaleString(),
        icon: 'package',
        color: 'text-primary',
        bgColor: 'bg-primary/10',
        href: '/inventory/stocks',
    },
    {
        title: 'Available',
        value: stats.value.availableStock.toLocaleString(),
        icon: 'check-circle',
        color: 'text-success',
        bgColor: 'bg-success/10',
        href: '/inventory/stocks',
    },
    {
        title: 'Low Stock',
        value: stats.value.lowStockItems,
        icon: 'alert-triangle',
        color: stats.value.lowStockItems > 0 ? 'text-destructive' : 'text-muted-foreground',
        bgColor: stats.value.lowStockItems > 0 ? 'bg-destructive/10' : 'bg-muted',
        href: '/inventory/stocks?low_stock_only=true',
    },
    {
        title: 'Purchases',
        value: stats.value.purchases,
        icon: 'shopping-cart',
        color: 'text-warning',
        bgColor: 'bg-warning/10',
        href: '/inventory/purchases',
    },
    {
        title: 'Value',
        value: `$${stats.value.totalPurchaseValue.toLocaleString()}`,
        icon: 'dollar-sign',
        color: 'text-success',
        bgColor: 'bg-success/10',
        href: '/inventory/purchases',
    },
    {
        title: 'Assigned',
        value: stats.value.assignedItems,
        icon: 'user-check',
        color: 'text-info',
        bgColor: 'bg-info/10',
        href: '/inventory/student-inventory',
    },
    {
        title: 'Returns',
        value: stats.value.pendingReturns,
        icon: 'rotate-ccw',
        color: 'text-destructive',
        bgColor: 'bg-destructive/10',
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
        case 'purchase': return 'text-primary bg-primary/10';
        case 'assignment': return 'text-success bg-success/10';
        case 'return': return 'text-warning bg-warning/10';
        default: return 'text-muted-foreground bg-muted';
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
                    <h1 class="text-2xl md:text-3xl font-bold text-foreground">
                        Inventory Management
                    </h1>
                    <p class="mt-1 text-sm md:text-base text-muted-foreground">
                        Track and manage your school inventory, purchases, and distributions.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Campus Selector -->
                    <div class="flex items-center gap-2">
                        <label for="campus-select" class="text-sm font-medium text-muted-foreground">
                            Campus:
                        </label>
                        <select
                            id="campus-select"
                            :value="selectedCampusId || ''"
                            @change="handleCampusChange"
                            class="px-3 py-2 text-sm rounded-lg border border-border bg-card text-foreground focus:ring-2 focus:ring-primary/50 focus:border-primary"
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
                        class="group bg-card rounded-xl border border-border p-4 md:p-5 hover:shadow-lg hover:border-border transition-all duration-200"
                    >
                        <div class="flex flex-wrap gap-2 items-start justify-between">
                            <div>
                                <p class="text-xs md:text-sm font-medium text-muted-foreground uppercase tracking-wide">
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
            <section aria-label="Quick Actions" class="bg-card rounded-xl border border-border p-4 md:p-6">
                <h2 class="text-lg font-semibold text-foreground mb-4">
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
                            class="w-full h-10 md:h-11 justify-center gap-1.5 px-2 text-xs md:text-sm truncate border-border hover:bg-accent focus:ring-2 focus:ring-primary/50"
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
                <section aria-labelledby="activity-heading" class="bg-card rounded-xl border border-border p-4 md:p-6">
                    <header class="flex flex-wrap gap-2 items-center justify-between mb-4">
                        <h2 id="activity-heading" class="text-lg font-semibold text-foreground flex items-center gap-2">
                            <Icon icon="clock" class="h-5 w-5 text-muted-foreground" />
                            Recent Activity
                        </h2>
                    </header>
                    
                    <div v-if="loading" class="flex justify-center py-12" aria-label="Loading activities">
                        <Icon icon="loader" class="animate-spin h-8 w-8 text-muted-foreground" />
                    </div>
                    
                    <div v-else-if="recentActivities.length === 0" class="text-center py-12 text-muted-foreground">
                        <Icon icon="activity" :size="48" class="mx-auto mb-3 text-muted-foreground" />
                        <p>No recent activity</p>
                    </div>
                    
                    <ul v-else class="space-y-3" role="list">
                        <li 
                            v-for="activity in recentActivities" 
                            :key="activity.id"
                            class="flex items-start gap-3 p-3 rounded-md hover:bg-accent transition-colors focus-within:bg-accent focus-within:ring-2 focus-within:ring-primary/20 rounded-md"
                        >
                            <div :class="['p-2 rounded-full shrink-0', getActivityColor(activity.type)]">
                                <Icon :icon="getActivityIcon(activity.type)" :size="16" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-foreground truncate">
                                    {{ activity.description }}
                                </p>
                                <time class="text-xs text-muted-foreground">
                                    {{ formatDate(activity.date) }}
                                </time>
                            </div>
                        </li>
                    </ul>
                </section>

                <!-- Low Stock Alerts -->
                <section aria-labelledby="alerts-heading" class="bg-card rounded-xl border border-border p-4 md:p-6">
                    <header class="flex flex-wrap gap-2 items-center justify-between mb-4">
                        <h2 id="alerts-heading" class="text-lg font-semibold text-foreground flex items-center gap-2">
                            <Icon icon="alert-triangle" :class="`h-5 w-5 ${stats.lowStockItems > 0 ? 'text-destructive' : 'text-muted-foreground'}`" />
                            Low Stock Alerts
                        </h2>
                    </header>
                    
                    <div v-if="loading" class="flex justify-center py-12" aria-label="Loading alerts">
                        <Icon icon="loader" class="animate-spin h-8 w-8 text-muted-foreground" />
                    </div>
                    
                    <div v-else-if="lowStockItems.length === 0" class="text-center py-12 text-muted-foreground">
                        <Icon icon="check-circle" :size="48" class="mx-auto mb-3 text-success" />
                        <p>All items are well stocked!</p>
                    </div>
                    
                    <div v-else class="space-y-3">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 bg-destructive/10 rounded-lg gap-3 border border-destructive/40">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-destructive/10 rounded-full">
                                    <Icon icon="alert-triangle" class="h-5 w-5 text-destructive" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-foreground">
                                        {{ lowStockItems.length }} items need restocking
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Review and update stock levels
                                    </p>
                                </div>
                            </div>
                            <a 
                                href="/inventory/stocks?low_stock_only=true" 
                                class="inline-flex items-center gap-1 text-sm font-medium text-destructive hover:text-destructive focus:outline-none focus:ring-2 focus:ring-destructive/50 rounded px-3 py-1.5"
                            >
                                View all
                                <Icon icon="arrow-right" class="h-4 w-4" />
                            </a>
                        </div>
                        
                        <!-- List actual low stock items -->
                        <div class="mt-3 space-y-2">
                            <h3 class="text-sm font-medium text-muted-foreground">Items below threshold:</h3>
                            <ul class="divide-y divide-border">
                                <li 
                                    v-for="item in lowStockItems.slice(0, 5)" 
                                    :key="item.id"
                                    class="py-2 flex flex-wrap gap-2 justify-between items-center"
                                >
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-foreground truncate">
                                            {{ item.item_name }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            Threshold: {{ item.low_stock_threshold }}
                                        </p>
                                    </div>
                                    <div class="text-right ml-4">
                                        <p class="text-sm font-semibold text-destructive">
                                            {{ item.available_quantity }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">available</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Overview Section -->
            <section aria-labelledby="overview-heading" class="bg-card rounded-xl border border-border p-4 md:p-6">
                <h2 id="overview-heading" class="text-lg font-semibold text-foreground mb-6">
                    Inventory Overview
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
                    <!-- By Type -->
                    <article class="bg-muted rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-muted-foreground mb-4 flex items-center gap-2">
                            <Icon icon="tags" class="h-4 w-4" />
                            By Type
                        </h3>
                        <div v-if="loading" class="space-y-3" aria-label="Loading types">
                            <div v-for="i in 3" :key="i" class="h-10 bg-muted rounded animate-pulse"></div>
                        </div>
                        <div v-else-if="inventoryTypes.length === 0" class="text-center py-6 text-muted-foreground text-sm">
                            <Icon icon="inbox" :size="32" class="mx-auto mb-2 text-muted-foreground" />
                            <p>No types found</p>
                        </div>
                        <div v-else class="space-y-2">
                            <div class="flex flex-wrap gap-2 justify-between items-center p-2 rounded bg-card">
                                <span class="text-sm text-muted-foreground">Total Types</span>
                                <span class="text-sm font-semibold text-foreground">{{ inventoryTypes.length }}</span>
                            </div>
                            <div 
                                v-for="type in inventoryTypes.slice(0, 4)" 
                                :key="type.id"
                                class="flex flex-wrap gap-2 justify-between items-center p-2 rounded bg-card"
                            >
                                <span class="text-sm text-muted-foreground truncate max-w-37.5" :title="type.name">{{ type.name }}</span>
                                <span class="text-sm font-semibold text-foreground">
                                    {{ inventoryItems.filter(item => item.inventory_type_id === type.id).length }}
                                </span>
                            </div>
                        </div>
                    </article>

                    <!-- Stock Status -->
                    <article class="bg-muted rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-muted-foreground mb-4 flex items-center gap-2">
                            <Icon icon="package" class="h-4 w-4" />
                            Stock Status
                        </h3>
                        <div v-if="loading" class="space-y-4" aria-label="Loading stock status">
                            <div v-for="i in 3" :key="i" class="h-6 bg-muted rounded animate-pulse"></div>
                        </div>
                        <div v-else-if="inventoryItems.length === 0" class="text-center py-6 text-muted-foreground text-sm">
                            <Icon icon="inbox" :size="32" class="mx-auto mb-2 text-muted-foreground" />
                            <p>No items found</p>
                        </div>
                        <template v-else>
                            <!-- Healthy -->
                            <div class="mb-4">
                                <div class="flex flex-wrap gap-2 justify-between text-sm mb-1.5">
                                    <span class="text-muted-foreground">Healthy</span>
                                    <span class="font-medium text-success">{{ stockStatus.healthy }}%</span>
                                </div>
                                <div class="h-2.5 bg-muted rounded-full overflow-hidden">
                                    <div class="h-full bg-success rounded-full transition-all duration-500" :style="{ width: stockStatus.healthy + '%' }"></div>
                                </div>
                            </div>
                            <!-- Low Stock -->
                            <div class="mb-4">
                                <div class="flex flex-wrap gap-2 justify-between text-sm mb-1.5">
                                    <span class="text-muted-foreground">Low Stock</span>
                                    <span class="font-medium text-warning">{{ stockStatus.low }}%</span>
                                </div>
                                <div class="h-2.5 bg-muted rounded-full overflow-hidden">
                                    <div class="h-full bg-warning rounded-full transition-all duration-500" :style="{ width: stockStatus.low + '%' }"></div>
                                </div>
                            </div>
                            <!-- Out of Stock -->
                            <div>
                                <div class="flex flex-wrap gap-2 justify-between text-sm mb-1.5">
                                    <span class="text-muted-foreground">Out of Stock</span>
                                    <span class="font-medium text-destructive">{{ stockStatus.outOfStock }}%</span>
                                </div>
                                <div class="h-2.5 bg-muted rounded-full overflow-hidden">
                                    <div class="h-full bg-destructive rounded-full transition-all duration-500" :style="{ width: stockStatus.outOfStock + '%' }"></div>
                                </div>
                            </div>
                        </template>
                    </article>

                    <!-- Top Moving Items -->
                    <article class="bg-muted rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-muted-foreground mb-4 flex items-center gap-2">
                            <Icon icon="trending-up" class="h-4 w-4" />
                            Top Moving Items
                        </h3>
                        <div v-if="loading" class="space-y-3" aria-label="Loading top items">
                            <div v-for="i in 5" :key="i" class="h-12 bg-muted rounded animate-pulse"></div>
                        </div>
                        <div v-else-if="topMovingItems.length === 0" class="text-center py-6 text-muted-foreground text-sm">
                            <Icon icon="bar-chart" :size="32" class="mx-auto mb-2 text-muted-foreground" />
                            <p>No data available</p>
                            <p class="text-xs mt-1">Start recording purchases</p>
                        </div>
                        <ul v-else class="space-y-2" role="list">
                            <li 
                                v-for="(item, index) in topMovingItems" 
                                :key="index"
                                class="flex flex-wrap gap-2 items-center justify-between p-2 rounded bg-card"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center text-xs font-bold text-primary shrink-0">
                                        {{ index + 1 }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-foreground truncate">{{ item.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ item.purchaseCount }} purchases</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-semibold text-foreground">{{ item.quantity }}</p>
                                    <p class="text-xs text-muted-foreground">units</p>
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
