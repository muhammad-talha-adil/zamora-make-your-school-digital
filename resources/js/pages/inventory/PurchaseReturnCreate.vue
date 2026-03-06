<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { alert } from '@/utils';
import axios from 'axios';
import { ref, computed, reactive, watch, onMounted } from 'vue';

// Components
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';
import ComboboxInput from '@/components/ui/combobox/ComboboxInput.vue';
import type { BreadcrumbItem } from '@/types';

interface Purchase {
    id: number;
    campus_id: number;
    supplier_id: number;
    supplier: {
        id: number;
        name: string;
    } | null;
    purchase_date: string;
    total_amount: number;
    display_text: string;
    name?: string;
}

interface PurchaseItem {
    id: number;
    inventory_item_id: number;
    item_name: string;
    quantity_purchased: number;
    current_stock: number;
    purchase_rate: number;
    available_for_return: number;
}

interface Supplier {
    id: number;
    campus_id: number | null;
    name: string;
}

interface Reason {
    id: number;
    name: string;
}

interface Props {
    campuses: Array<{
        id: number;
        name: string;
    }>;
    return?: {
        id: number;
        campus_id: number;
        supplier_id: number;
        supplier: {
            id: number;
            name: string;
        } | null;
        purchase_id: number | null;
        purchase: {
            id: number;
            purchase_id: string;
            purchase_date?: string;
            total_amount?: number;
            display_text?: string;
            name?: string;
        } | null;
        return_date: string;
        note: string;
        total_amount: number;
        items: Array<{
            id: number;
            inventory_item_id: number;
            quantity: number;
            unit_price: number;
            total: number;
            reason: string | null;
            inventory_item: {
                id: number;
                name: string;
            } | null;
        }>;
    };
}

const props = defineProps<Props>();

// Breadcrumb
const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory', href: '/inventory' },
    { title: 'Purchases', href: '/inventory/purchases-manage' },
    { title: 'Create Return', href: '#' },
];

// Form data
const form = reactive({
    campus_id: props.campuses[0]?.id || '',
    supplier_id: null as number | null,
    purchase_id: null as number | null,
    return_date: new Date().toISOString().split('T')[0],
    items: [] as Array<{
        inventory_item_id: number | null;
        purchase_item_id: number | null;
        quantity: number;
        unit_price: number;
        reason_id: number | null;
        custom_reason: string;
        available_for_return: number;
    }>,
    note: '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const loading = ref(false);

// Dynamic data
const suppliers = ref<Supplier[]>([]);
const purchases = ref<Purchase[]>([]);
const purchaseItems = ref<PurchaseItem[]>([]);
const reasons = ref<Reason[]>([]);
const otherReasonId = ref<number | null>(null);

// Computed
const currentCampusId = computed(() => form.campus_id);

// Check if we're in edit mode
const isEditMode = computed(() => !!props.return?.id);

// Supplier search URL
const supplierSearchUrl = computed(() => {
    if (!form.campus_id) {
        return '/inventory/purchase-returns/suppliers?campus_id=';
    }
    return `/inventory/purchase-returns/suppliers?campus_id=${currentCampusId.value}`;
});

// Purchase search URL
const purchaseSearchUrl = computed(() => {
    const params = new URLSearchParams();
    if (form.campus_id) params.append('campus_id', String(form.campus_id));
    if (form.supplier_id) params.append('supplier_id', String(form.supplier_id));
    return `/inventory/purchase-returns/purchases?${params.toString()}`;
});

// Transform purchases for ComboboxInput
const purchasesForCombobox = computed(() => {
    return purchases.value.map(p => ({
        ...p,
        name: p.display_text || `PUR-${p.id}`,
    }));
});

// Inventory items based on selected purchase
const availableItems = computed(() => {
    // In edit mode, use items from the existing return directly
    if (isEditMode.value && props.return?.items && props.return.items.length > 0) {
        // Map return items to match the expected format for the dropdown
        return props.return.items.map((item: any) => ({
            inventory_item_id: item.inventory_item_id,
            id: item.id,
            item_name: item.inventory_item?.name || 'Unknown Item',
            quantity_purchased: item.quantity,
            current_stock: item.quantity,
            purchase_rate: item.unit_price,
            available_for_return: item.quantity,
        }));
    }
    
    return purchaseItems.value;
});

// Total amount
const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + (item.quantity || 0) * (item.unit_price || 0);
    }, 0);
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
    }).format(amount);
};

// Check if item reason is "Other"
const isOtherReason = (reasonId: number | null): boolean => {
    return reasonId === otherReasonId.value;
};

// Methods
const fetchSuppliers = async () => {
    try {
        const params = new URLSearchParams();
        if (form.campus_id) params.append('campus_id', String(form.campus_id));
        
        const response = await axios.get(`/inventory/purchase-returns/suppliers?${params.toString()}`);
        suppliers.value = response.data;
    } catch (err) {
        console.error('Failed to fetch suppliers:', err);
        suppliers.value = [];
    }
};

const fetchPurchases = async () => {
    try {
        const params = new URLSearchParams();
        if (form.campus_id) params.append('campus_id', String(form.campus_id));
        if (form.supplier_id) params.append('supplier_id', String(form.supplier_id));
        
        const response = await axios.get(`/inventory/purchase-returns/purchases?${params.toString()}`);
        purchases.value = response.data;
    } catch (err) {
        console.error('Failed to fetch purchases:', err);
        purchases.value = [];
    }
};

const fetchReasons = async () => {
    try {
        const response = await axios.get('/inventory/purchase-returns/reasons');
        reasons.value = response.data;
        
        const otherReason = reasons.value.find(r => r.name === 'Other');
        if (otherReason) {
            otherReasonId.value = otherReason.id;
        }
    } catch (err) {
        console.error('Failed to fetch reasons:', err);
        reasons.value = [];
    }
};

const fetchPurchaseItems = async () => {
    if (!form.purchase_id) {
        purchaseItems.value = [];
        form.items = [];
        return;
    }
    
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (form.campus_id) params.append('campus_id', String(form.campus_id));
        
        const response = await axios.get(`/inventory/purchase-returns/purchase/${form.purchase_id}/items?${params.toString()}`);
        purchaseItems.value = response.data.items;
        
        form.items = purchaseItems.value.map(item => ({
            inventory_item_id: item.inventory_item_id,
            purchase_item_id: item.id,
            quantity: 1,
            unit_price: item.purchase_rate,
            reason_id: null,
            custom_reason: '',
            available_for_return: item.available_for_return,
        }));
    } catch (err) {
        console.error('Failed to fetch purchase items:', err);
        purchaseItems.value = [];
    } finally {
        loading.value = false;
    }
};

const onCampusChange = () => {
    form.supplier_id = null;
    form.purchase_id = null;
    purchaseItems.value = [];
    form.items = [];
    suppliers.value = [];
    purchases.value = [];
    fetchSuppliers();
};

const onSupplierChange = () => {
    form.purchase_id = null;
    purchaseItems.value = [];
    form.items = [];
    purchases.value = [];
    if (form.supplier_id) {
        fetchPurchases();
    }
};

const addItem = () => {
    form.items.push({
        inventory_item_id: null,
        purchase_item_id: null,
        quantity: 1,
        unit_price: 0,
        reason_id: null,
        custom_reason: '',
        available_for_return: 0,
    });
};

const removeItem = (index: number) => {
    form.items.splice(index, 1);
};

const getItemMaxQuantity = (itemIndex: number): number => {
    const item = form.items[itemIndex];
    if (!item.inventory_item_id) return 0;
    
    const purchaseItem = purchaseItems.value.find(pi => pi.inventory_item_id === item.inventory_item_id);
    return purchaseItem?.available_for_return ?? 0;
};

const getItemPurchaseRate = (itemIndex: number): number => {
    const item = form.items[itemIndex];
    if (!item.inventory_item_id) return 0;
    
    const purchaseItem = purchaseItems.value.find(pi => pi.inventory_item_id === item.inventory_item_id);
    return purchaseItem?.purchase_rate ?? 0;
};

const goBack = () => {
    router.visit('/inventory/purchases-manage');
};

const validateItems = (): boolean => {
    for (let i = 0; i < form.items.length; i++) {
        const item = form.items[i];
        
        if (!item.inventory_item_id) {
            alert.error(`Please select an item for row ${i + 1}`);
            return false;
        }
        
        if (item.quantity <= 0) {
            alert.error(`Please enter a valid quantity for row ${i + 1}`);
            return false;
        }
        
        const maxQty = getItemMaxQuantity(i);
        if (item.quantity > maxQty) {
            alert.error(`Quantity for row ${i + 1} cannot exceed available stock (${maxQty})`);
            return false;
        }
        
        if (item.unit_price <= 0) {
            alert.error(`Please enter a valid unit price for row ${i + 1}`);
            return false;
        }
        
        if (item.reason_id === otherReasonId.value && !item.custom_reason.trim()) {
            alert.error(`Please specify the reason for row ${i + 1}`);
            return false;
        }
    }
    return true;
};

const submitForm = () => {
    // Validate required fields
    if (!form.campus_id) {
        alert.error('Please select a campus');
        return;
    }
    
    if (!form.supplier_id) {
        alert.error('Please select a supplier');
        return;
    }
    
    if (!form.purchase_id) {
        alert.error('Please select an original purchase order');
        return;
    }
    
    if (!validateItems()) {
        return;
    }
    
    processing.value = true;
    errors.value = {};

    const itemsData = form.items.map(item => ({
        inventory_item_id: item.inventory_item_id,
        purchase_item_id: item.purchase_item_id,
        quantity: item.quantity,
        unit_price: item.unit_price,
        reason_id: item.reason_id === otherReasonId.value ? null : item.reason_id,
        custom_reason: item.reason_id === otherReasonId.value ? item.custom_reason : null,
    }));

    const apiUrl = isEditMode.value 
        ? `/inventory/purchase-returns/${props.return?.id}`
        : '/inventory/purchase-returns';
    const method = isEditMode.value ? 'put' : 'post';

    axios[method](apiUrl, {
        campus_id: form.campus_id,
        supplier_id: form.supplier_id,
        purchase_id: form.purchase_id,
        return_date: form.return_date,
        items: itemsData,
        note: form.note,
    })
        .then(() => {
            alert.success(isEditMode.value ? 'Purchase return updated successfully!' : 'Purchase return created successfully!');
            goBack();
        })
        .catch((err) => {
            if (err.response?.data?.errors) {
                errors.value = err.response.data.errors;
                const errorValues = Object.values(err.response.data.errors) as string[];
                const firstError = errorValues[0] || 'An error occurred';
                alert.error(firstError);
            } else if (err.response?.data?.message) {
                alert.error(err.response.data.message);
            } else {
                alert.error(isEditMode.value ? 'Failed to update return. Please check the errors.' : 'Failed to create return. Please check the errors.');
            }
        })
        .finally(() => {
            processing.value = false;
        });
};

// Watch for supplier selection changes
watch(() => form.supplier_id, (newSupplierId) => {
    if (newSupplierId) {
        onSupplierChange();
    } else {
        form.purchase_id = null;
        purchaseItems.value = [];
        form.items = [];
        purchases.value = [];
    }
});

// Watch for purchase selection changes
watch(() => form.purchase_id, (newPurchaseId) => {
    if (newPurchaseId) {
        fetchPurchaseItems();
    } else {
        purchaseItems.value = [];
        form.items = [];
    }
});

// Initialize on mount
onMounted(async () => {
    await fetchReasons();
    
    // If in edit mode, populate form with existing data
    if (props.return) {
        form.campus_id = props.return.campus_id;
        form.supplier_id = props.return.supplier_id;
        form.purchase_id = props.return.purchase_id;
        
        // Format the return date properly for the date input
        if (props.return.return_date) {
            const date = new Date(props.return.return_date);
            form.return_date = date.toISOString().split('T')[0];
        }
        
        form.note = props.return.note || '';
        
        // Populate items from the existing return - use simple mapping like PurchaseCreate
        // Also populate purchaseItems for the dropdown
        if (props.return.items && props.return.items.length > 0) {
            // First, populate purchaseItems (used by the dropdown)
            purchaseItems.value = props.return.items.map((item: any) => ({
                inventory_item_id: item.inventory_item_id,
                id: item.id,
                item_name: item.inventory_item?.name || 'Unknown Item',
                quantity_purchased: item.quantity,
                current_stock: item.quantity,
                purchase_rate: item.unit_price,
                available_for_return: item.quantity,
            }));
            
            // Then, populate form.items (used by the form)
            form.items = props.return.items.map((item: any) => ({
                inventory_item_id: item.inventory_item_id,
                purchase_item_id: item.id,
                quantity: item.quantity,
                unit_price: item.unit_price,
                reason_id: item.reason_id || null,
                custom_reason: item.reason || '',
                available_for_return: item.quantity,
            }));
        }
        
        // In edit mode, fetch suppliers for the campus
        await fetchSuppliers();
    } else {
        await fetchSuppliers();
        await fetchPurchases();
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Create Purchase Return" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ isEditMode ? 'Edit Purchase Return' : 'New Purchase Return' }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ isEditMode ? 'Edit an existing purchase return order' : 'Create a new purchase return order' }}
                    </p>
                </div>
                <Button variant="outline" @click="goBack" class="w-full sm:w-auto">
                    <Icon icon="arrow-left" class="mr-2 h-4 w-4" />
                    Back to Purchases
                </Button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Basic Info Card -->
                <div class="bg-card rounded-lg border p-4 md:p-5 space-y-4">
                    <h2 class="text-lg font-semibold">Return Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Campus -->
                        <div class="space-y-2">
                            <Label for="campus_id" class="flex items-center gap-2">
                                <Icon icon="building" class="h-4 w-4" />
                                Campus <span class="text-red-500">*</span>
                            </Label>
                            <select
                                id="campus_id"
                                v-model="form.campus_id"
                                @change="onCampusChange"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm h-11"
                                :class="{ 'border-red-500': errors.campus_id }"
                                required
                            >
                                <option value="">Select Campus</option>
                                <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                                    {{ campus.name }}
                                </option>
                            </select>
                            <InputError :message="errors.campus_id" />
                        </div>

                        <!-- Supplier -->
                        <div class="space-y-2">
                            <Label for="supplier_id" class="flex items-center gap-2">
                                <Icon icon="truck" class="h-4 w-4" />
                                Supplier <span class="text-red-500">*</span>
                            </Label>
                            <ComboboxInput
                                v-model="form.supplier_id"
                                :initial-items="suppliers"
                                :search-url="supplierSearchUrl"
                                placeholder="Select supplier"
                                value-type="id"
                            />
                            <InputError :message="errors.supplier_id" />
                        </div>

                        <!-- Original Purchase -->
                        <div class="space-y-2">
                            <Label for="purchase_id" class="flex items-center gap-2">
                                <Icon icon="shopping-cart" class="h-4 w-4" />
                                Original Purchase <span class="text-red-500">*</span>
                            </Label>
                            <!-- In edit mode, show a simple disabled input with the purchase details -->
                            <div v-if="isEditMode && props.return?.purchase">
                                <Input
                                    :modelValue="props.return.purchase.display_text || props.return.purchase.purchase_id || 'Purchase #' + props.return.purchase.id"
                                    disabled
                                    class="h-11 bg-gray-100 dark:bg-gray-700"
                                />
                            </div>
                            <!-- In create mode, show the combobox -->
                            <ComboboxInput
                                v-else
                                v-model="form.purchase_id"
                                :initial-items="purchasesForCombobox"
                                :search-url="purchaseSearchUrl"
                                placeholder="Select purchase order"
                                value-type="id"
                            />
                            <InputError :message="errors.purchase_id" />
                        </div>
                    </div>

                    <!-- Return Date -->
                    <div class="space-y-2">
                        <Label for="return_date" class="flex items-center gap-2">
                            <Icon icon="calendar" class="h-4 w-4" />
                            Return Date <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            id="return_date"
                            v-model="form.return_date"
                            type="date"
                            class="h-11 w-full sm:max-w-md"
                            :class="{ 'border-red-500': errors.return_date }"
                            required
                        />
                        <InputError :message="errors.return_date" />
                    </div>
                </div>

                <!-- Return Items Card -->
                <div class="bg-card rounded-lg border p-4 md:p-5 space-y-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold">Return Items</h2>
                        <Button type="button" variant="outline" size="sm" @click="addItem" class="h-8 w-full sm:w-auto">
                            <Icon icon="plus" class="mr-1 h-3 w-3" />
                            Add Item
                        </Button>
                    </div>

                    <div class="border rounded-lg overflow-x-auto">
                        <div class="min-w-[900px]">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Item</th>
                                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Available</th>
                                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Quantity</th>
                                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Unit Price</th>
                                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Original Price</th>
                                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</th>
                                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reason</th>
                                        <th class="px-2 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="(item, index) in form.items" :key="index">
                                        <td class="px-2 py-3">
                                            <div class="flex flex-col gap-1">
                                                <select
                                                    v-model="item.inventory_item_id"
                                                    @change="() => {
                                                        const pi = purchaseItems.find(p => p.inventory_item_id === item.inventory_item_id);
                                                        if (pi) {
                                                            item.available_for_return = pi.available_for_return;
                                                            item.unit_price = pi.purchase_rate;
                                                        }
                                                    }"
                                                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-1.5 text-sm h-10 min-w-[140px]"
                                                    required
                                                >
                                                    <option :value="null">Select</option>
                                                    <option v-for="invItem in availableItems" :key="invItem.inventory_item_id" :value="invItem.inventory_item_id">
                                                        {{ invItem.item_name }}
                                                    </option>
                                                </select>
                                            </div>
                                        </td>
                                        <td class="px-2 py-3">
                                            <div class="flex flex-col gap-1 h-10 justify-center">
                                                <span :class="item.available_for_return > 0 ? 'text-green-600' : 'text-red-500'">
                                                    {{ item.available_for_return }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-2 py-3">
                                            <div class="flex flex-col gap-1">
                                                <Input
                                                    v-model.number="item.quantity"
                                                    type="number"
                                                    min="1"
                                                    :max="item.available_for_return"
                                                    class="w-16 h-10 text-sm"
                                                    required
                                                />
                                                <span v-if="item.quantity > item.available_for_return" class="text-xs text-red-500 whitespace-nowrap">
                                                    Max: {{ item.available_for_return }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-2 py-3">
                                            <div class="flex flex-col gap-1">
                                                <Input
                                                    v-model.number="item.unit_price"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="w-24 h-10 text-sm"
                                                    required
                                                />
                                            </div>
                                        </td>
                                        <td class="px-2 py-3">
                                            <div class="flex flex-col gap-1 h-10 justify-center">
                                                <span class="text-sm text-muted-foreground">
                                                    {{ formatCurrency(getItemPurchaseRate(index)) }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-2 py-3">
                                            <div class="flex flex-col gap-1 h-10 justify-center">
                                                <span class="text-sm font-medium">
                                                    {{ formatCurrency(item.quantity * item.unit_price) }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-2 py-3">
                                            <div v-if="isOtherReason(item.reason_id)" class="flex flex-col gap-1">
                                                <Input
                                                    v-model="item.custom_reason"
                                                    type="text"
                                                    placeholder="Reason..."
                                                    class="h-10 text-sm min-w-[120px]"
                                                    required
                                                />
                                            </div>
                                            <div v-else class="flex flex-col gap-1">
                                                <ComboboxInput
                                                    v-model="item.reason_id"
                                                    :initial-items="reasons"
                                                    placeholder="Reason"
                                                    value-type="id"
                                                    class-min-width="min-w-[150px]"
                                                />
                                            </div>
                                        </td>
                                        <td class="px-2 py-3">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                @click="removeItem(index)"
                                                class="h-10 w-8 p-0 flex-shrink-0"
                                            >
                                                <Icon icon="trash" class="h-4 w-4 text-red-500" />
                                            </Button>
                                        </td>
                                    </tr>
                                    <tr v-if="form.items.length === 0">
                                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                            <div v-if="loading">
                                                <Icon icon="loader" class="h-6 w-6 animate-spin mx-auto mb-2" />
                                                Loading items...
                                            </div>
                                            <div v-else-if="!form.supplier_id">
                                                <p class="text-sm text-gray-500">Please select a supplier first</p>
                                            </div>
                                            <div v-else-if="purchases.length === 0">
                                                <p class="text-sm text-gray-500">No purchases found for selected supplier</p>
                                            </div>
                                            <div v-else>
                                                <p class="text-sm text-gray-500">Select a purchase order to load items</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <InputError :message="errors.items" />
                </div>

                <!-- Total & Notes Card -->
                <div class="bg-card rounded-lg border p-4 md:p-5 space-y-4">
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="text-right">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Return Amount</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(totalAmount) }}</div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="note" class="flex items-center gap-2">
                            <Icon icon="align-left" class="h-4 w-4" />
                            Note <span class="text-sm text-muted-foreground font-normal">(Optional)</span>
                        </Label>
                        <textarea
                            id="note"
                            v-model="form.note"
                            rows="2"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 min-h-20"
                            placeholder="Additional notes..."
                        ></textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row justify-end gap-3 pt-2">
                    <Button type="button" variant="outline" @click="goBack" class="w-full sm:w-auto h-10">
                        <Icon icon="x" class="mr-2 h-4 w-4" />
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing || loading || form.items.length === 0" class="w-full sm:w-auto h-10">
                        <Icon v-if="processing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                        <Icon v-else icon="check" class="mr-2 h-4 w-4" />
                        {{ isEditMode ? 'Update Return' : 'Create Return' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
