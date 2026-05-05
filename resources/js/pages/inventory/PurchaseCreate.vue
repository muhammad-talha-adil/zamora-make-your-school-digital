<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { ref, computed, reactive, watch } from 'vue';
import { alert, formatCurrency } from '@/utils';
import axios from 'axios';

// Components
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';
import ComboboxInput from '@/components/ui/combobox/ComboboxInput.vue';
import SupplierForm from '@/components/forms/inventory/SupplierForm.vue';
import type { BreadcrumbItem } from '@/types';

interface InventoryItem {
    id: number;
    campus_id: number;
    name: string;
    purchase_rate?: number;
    sale_rate?: number;
    available_stock: number;
}

interface Props {
    campuses: Array<{
        id: number;
        name: string;
    }>;
    suppliers: Array<{
        id: number;
        name: string;
        campus_id?: number | null;
    }>;
    purchase?: any;
}

const props = defineProps<Props>();

// Check if we're in edit mode
const isEditMode = computed(() => !!props.purchase);

// Breadcrumb
const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory', href: '/inventory' },
    { title: 'Purchases', href: '/inventory/purchases-manage' },
    { title: isEditMode.value ? 'Edit Purchase' : 'Create Purchase', href: '#' },
];

// Form data
const form = reactive({
    campus_id: '' as string | number,
    supplier_id: null as number | null,
    purchase_date: new Date().toISOString().split('T')[0],
    note: '',
    purchase_items: [] as Array<{
        inventory_item_id: number;
        quantity: number;
        purchase_rate: number;
        sale_rate: number;
    }>,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const loadingItems = ref(false);

// Generate unique idempotency key for this form session
const idempotencyKey = ref<string | null>(null);

// Supplier modal state
const supplierModalOpen = ref(false);

// Dynamic suppliers list
const localSuppliers = computed(() => {
    const baseSuppliers = [...props.suppliers];
    
    // In edit mode, always include the current purchase's supplier
    if (props.purchase && props.purchase.supplier) {
        const supplierExists = baseSuppliers.some(s => s.id === props.purchase!.supplier!.id);
        if (!supplierExists) {
            baseSuppliers.push(props.purchase.supplier);
        }
    }
    
    return baseSuppliers;
});

// Get the current campus_id for search
const currentCampusId = computed(() => form.campus_id);

// Search URL for supplier combobox
const supplierSearchUrl = computed(() => {
    if (!form.campus_id) {
        return `/inventory/suppliers/all?active_only=false`;
    }
    return `/inventory/suppliers/all?campus_id=${currentCampusId.value}&active_only=false`;
});

// Filtered suppliers based on campus
const filteredLocalSuppliers = computed(() => {
    if (isEditMode.value && props.purchase) {
        return localSuppliers.value;
    }
    
    const campusId = form.campus_id;
    
    if (!campusId) {
        return localSuppliers.value;
    }
    
    const numericCampusId = parseInt(String(campusId));
    
    return localSuppliers.value.filter(s => 
        s.campus_id === numericCampusId || s.campus_id === null
    );
});

// Handle new supplier created
const handleSupplierCreated = (supplier?: { id: number; name: string }) => {
    if (!supplier) return;
    if (!localSuppliers.value.find(s => s.id === supplier.id)) {
        localSuppliers.value.push(supplier);
    }
    form.supplier_id = supplier.id;
    supplierModalOpen.value = false;
};

// Dynamic inventory items
const inventoryItems = ref<InventoryItem[]>([]);

// Fetch inventory items based on selected campus and supplier
const fetchInventoryItems = async () => {
    const campusId = form.campus_id;
    const supplierId = form.supplier_id;
    loadingItems.value = true;
    
    try {
        const params = new URLSearchParams();
        if (campusId) params.append('campus_id', String(campusId));
        if (supplierId) params.append('supplier_id', String(supplierId));
        
        const response = await axios.get(`/inventory/items/all?${params.toString()}`);
        const items = Array.isArray(response.data) ? response.data : [];
        
        const itemsMap = new Map();
        inventoryItems.value.forEach(item => itemsMap.set(item.id, item));
        items.forEach((item: any) => {
            if (!itemsMap.has(item.id)) {
                itemsMap.set(item.id, {
                    id: item.id,
                    campus_id: item.campus_id,
                    name: item.name,
                    available_stock: item.stock_quantity || 0,
                });
            }
        });
        
        // If in edit mode with purchase items, add those items too
        if (props.purchase && props.purchase.items) {
            props.purchase.items.forEach((item: any) => {
                if (item.inventory_item && !itemsMap.has(item.inventory_item_id)) {
                    itemsMap.set(item.inventory_item_id, {
                        id: item.inventory_item_id,
                        campus_id: item.inventory_item?.campus_id || campusId,
                        name: item.inventory_item?.name || 'Unknown Item',
                        available_stock: item.quantity || 0,
                    });
                }
            });
        }
        
        inventoryItems.value = Array.from(itemsMap.values());
    } catch (err) {
        console.error('Failed to fetch inventory items:', err);
        inventoryItems.value = [];
    } finally {
        loadingItems.value = false;
    }
};

// Watch for campus changes
watch(() => form.campus_id, (newCampusId, oldCampusId) => {
    const hasEnteredItems = form.purchase_items.some(item => 
        item.inventory_item_id > 0 && item.quantity > 0 && item.purchase_rate > 0
    );
    
    if (hasEnteredItems && oldCampusId && oldCampusId !== newCampusId) {
        if (confirm('By changing campus, the entered items will be lost. Do you want to continue?')) {
            fetchInventoryItems();
        } else {
            form.campus_id = oldCampusId;
        }
    } else {
        fetchInventoryItems();
    }
});

// Watch for supplier changes to fetch items related to the supplier
watch(() => form.supplier_id, (newSupplierId, oldSupplierId) => {
    // Only fetch items when supplier changes (not on initial load)
    if (oldSupplierId !== null && newSupplierId !== oldSupplierId) {
        fetchInventoryItems();
    }
});

// Initialize form
const initializeForm = () => {
    if (props.purchase) {
        form.campus_id = props.purchase.campus_id;
        form.supplier_id = props.purchase.supplier_id;
        if (props.purchase.purchase_date) {
            const date = new Date(props.purchase.purchase_date);
            form.purchase_date = date.toISOString().split('T')[0];
        }
        form.note = props.purchase.note || '';
        form.purchase_items = props.purchase.items.map((item: any) => ({
            inventory_item_id: item.inventory_item_id,
            quantity: item.quantity,
            purchase_rate: item.purchase_rate,
            sale_rate: item.sale_rate || 0,
        }));
        
        fetchInventoryItems();
    } else {
        form.campus_id = '';
        form.supplier_id = null;
        form.purchase_date = new Date().toISOString().split('T')[0];
        form.note = '';
        form.purchase_items = [];
        addItem();
    }
    errors.value = {};
};

// Computed
const availableItems = computed(() => {
    return inventoryItems.value;
});

const calculateTotal = computed(() => {
    return form.purchase_items.reduce((sum, item) => {
        return sum + (item.quantity * item.purchase_rate);
    }, 0);
});

const calculateEstimatedProfit = computed(() => {
    return form.purchase_items.reduce((sum, item) => {
        const profit = (item.sale_rate - item.purchase_rate) * item.quantity;
        return sum + profit;
    }, 0);
});

// Methods
const addItem = () => {
    form.purchase_items.push({
        inventory_item_id: 0,
        quantity: 1,
        purchase_rate: 0,
        sale_rate: 0,
    });
};

const removeItem = (index: number) => {
    form.purchase_items.splice(index, 1);
};

const goBack = () => {
    router.visit('/inventory/purchases-manage');
};

const submitForm = () => {
    const validItems = form.purchase_items.filter(item => item.inventory_item_id > 0);
    
    if (validItems.length === 0) {
        alert.error('Please add at least one item to the purchase.');
        return;
    }

    const hasInvalidItems = validItems.some(item => 
        !item.inventory_item_id || item.quantity <= 0 || item.purchase_rate <= 0
    );
    
    if (hasInvalidItems) {
        alert.error('Please fill in all required fields for each item (Item, Quantity, Purchase Rate).');
        return;
    }

    processing.value = true;
    errors.value = {};

    const submitData = {
        ...form,
        purchase_items: validItems
    };
    
    const headers = idempotencyKey.value ? { 'X-Idempotency-Key': idempotencyKey.value } : {};
    
    if (isEditMode.value) {
        axios.put(`/inventory/purchases/${props.purchase!.id}`, submitData)
            .then(() => {
                alert.success('Purchase updated successfully!');
                goBack();
            })
            .catch((err) => {
                if (err.response?.data?.errors) {
                    errors.value = err.response.data.errors;
                    const firstError = Object.values(err.response.data.errors)[0] as string;
                    alert.error(firstError);
                } else if (err.response?.data?.message) {
                    alert.error(err.response.data.message);
                } else {
                    alert.error('Failed to update purchase. Please check the errors.');
                }
            })
            .finally(() => {
                processing.value = false;
            });
    } else {
        axios.post('/inventory/purchases', submitData, { headers })
            .then(() => {
                alert.success('Purchase created successfully! Stock has been updated.');
                goBack();
            })
            .catch((err) => {
                if (err.response?.data?.errors) {
                    errors.value = err.response.data.errors;
                    const firstError = Object.values(err.response.data.errors)[0] as string;
                    alert.error(firstError);
                } else if (err.response?.data?.message) {
                    alert.error(err.response.data.message);
                } else {
                    alert.error('Failed to create purchase. Please check the errors.');
                }
            })
            .finally(() => {
                processing.value = false;
            });
    }
};

// Initialize with one empty item only in create mode
watch(() => form.purchase_items.length, (len) => {
    if (!isEditMode.value && len === 0) {
        addItem();
    }
}, { immediate: true });

// Initialize on mount
initializeForm();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="isEditMode ? 'Edit Purchase' : 'Create Purchase'" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ isEditMode ? 'Edit Purchase Order' : 'Create Purchase Order' }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ isEditMode ? 'Update purchase details and items' : 'Create a new purchase order' }}
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
                    <h2 class="text-lg font-semibold">Basic Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-2">
                            <Label for="campus_id" class="flex items-center gap-2">
                                <Icon icon="building" class="h-4 w-4" />
                                Campus <span class="text-red-500">*</span>
                            </Label>
                            <select
                                id="campus_id"
                                v-model="form.campus_id"
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
                        <div class="space-y-2">
                            <Label for="supplier_id" class="flex items-center gap-2">
                                <Icon icon="truck" class="h-4 w-4" />
                                Supplier <span class="text-red-500">*</span>
                            </Label>
                            <div class="flex gap-2">
                                <div class="flex-1">
                                    <ComboboxInput
                                        v-model="form.supplier_id"
                                        :initial-items="filteredLocalSuppliers"
                                        :search-url="supplierSearchUrl"
                                        placeholder="Select supplier"
                                        value-type="id"
                                    />
                                </div>
                                <Button 
                                    type="button" 
                                    variant="outline" 
                                    size="sm" 
                                    class="h-11 px-3 flex-shrink-0"
                                    @click="supplierModalOpen = true"
                                    title="Add New Supplier"
                                >
                                    <Icon icon="plus" class="h-4 w-4" />
                                </Button>
                            </div>
                            <InputError :message="errors.supplier_id" />
                        </div>
                        <div class="space-y-2">
                            <Label for="purchase_date" class="flex items-center gap-2">
                                <Icon icon="calendar" class="h-4 w-4" />
                                Purchase Date <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                id="purchase_date"
                                v-model="form.purchase_date"
                                type="date"
                                class="h-11"
                                :class="{ 'border-red-500': errors.purchase_date }"
                                required
                            />
                            <InputError :message="errors.purchase_date" />
                        </div>
                    </div>
                </div>

                <!-- Purchase Items Card -->
                <div class="bg-card rounded-lg border p-4 md:p-5 space-y-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold">Purchase Items</h2>
                        <Button type="button" variant="outline" size="sm" @click="addItem" class="h-8 w-full sm:w-auto">
                            <Icon icon="plus" class="mr-1 h-3 w-3" />
                            Add Item
                        </Button>
                    </div>

                    <div v-if="form.purchase_items.length === 0" class="text-center py-8 text-gray-500 border rounded-lg">
                        <Icon icon="shopping-cart" :size="48" class="mx-auto mb-2 text-gray-300" />
                        <p>No items added yet. Click "Add Item" to start.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="(item, index) in form.purchase_items" :key="index" class="p-3 md:p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="flex flex-col gap-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                                    <div class="space-y-2">
                                        <Label class="text-xs">Item <span class="text-red-500">*</span></Label>
                                        <select
                                            v-model="item.inventory_item_id"
                                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm h-10"
                                            :class="{ 'border-red-500': errors[`purchase_items.${index}.inventory_item_id`] }"
                                            required
                                        >
                                            <option value="0">
                                                {{ loadingItems ? 'Loading...' : 'Select Item' }}
                                            </option>
                                            <option 
                                                v-for="invItem in availableItems" 
                                                :key="invItem.id" 
                                                :value="invItem.id"
                                            >
                                                {{ invItem.name }} ({{ invItem.available_stock }} in stock)
                                            </option>
                                        </select>
                                        <InputError :message="errors[`purchase_items.${index}.inventory_item_id`]" />
                                    </div>
                                    <div class="space-y-2">
                                        <Label class="text-xs">Quantity <span class="text-red-500">*</span></Label>
                                        <Input
                                            v-model.number="item.quantity"
                                            type="number"
                                            min="1"
                                            class="h-10"
                                            required
                                        />
                                    </div>
                                    <div class="space-y-2">
                                        <Label class="text-xs">Purchase Rate <span class="text-red-500">*</span></Label>
                                        <Input
                                            v-model.number="item.purchase_rate"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="h-10"
                                            required
                                        />
                                    </div>
                                    <div class="space-y-2">
                                        <Label class="text-xs">Sale Rate</Label>
                                        <Input
                                            v-model.number="item.sale_rate"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="h-10"
                                        />
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ formatCurrency(item.quantity * item.purchase_rate) }}
                                    </div>
                                    <Button type="button" variant="ghost" size="sm" @click="removeItem(index)" class="h-8 w-8 p-0 flex-shrink-0">
                                        <Icon icon="trash" class="text-red-500" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                        
                        <div v-if="availableItems.length === 0 && !loadingItems" class="text-center py-4 text-amber-600 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                            <Icon icon="alert-triangle" class="mr-2 h-4 w-4 inline" />
                            No inventory items found. Please add items in Inventory Settings first.
                        </div>
                    </div>
                </div>

                <!-- Total & Notes Card -->
                <div class="bg-card rounded-lg border p-4 md:p-5 space-y-4">
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-3">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                            <span class="text-lg font-semibold">Total Amount:</span>
                            <span class="text-2xl font-bold text-green-600">{{ formatCurrency(calculateTotal) }}</span>
                        </div>
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                            <span class="text-lg font-semibold">Estimated Profit:</span>
                            <span 
                                class="text-2xl font-bold"
                                :class="calculateEstimatedProfit >= 0 ? 'text-blue-600' : 'text-red-600'"
                            >
                                {{ formatCurrency(calculateEstimatedProfit) }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="note" class="flex items-center gap-2">
                            <Icon icon="align-left" class="h-4 w-4" />
                            Notes <span class="text-sm text-muted-foreground font-normal">(Optional)</span>
                        </Label>
                        <textarea
                            id="note"
                            v-model="form.note"
                            rows="2"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 min-h-20"
                            placeholder="Additional notes or remarks..."
                        ></textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row justify-end gap-3 pt-2">
                    <Button type="button" variant="outline" @click="goBack" class="w-full sm:w-auto h-10">
                        <Icon icon="x" class="mr-2 h-4 w-4" />
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing || loadingItems" class="w-full sm:w-auto h-10">
                        <Icon v-if="processing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                        <Icon v-else icon="check" class="mr-2 h-4 w-4" />
                        {{ isEditMode ? 'Update Purchase' : 'Create Purchase' }}
                    </Button>
                </div>
            </form>

            <!-- Add New Supplier Modal -->
            <SupplierForm
                :campuses="props.campuses"
                :open="supplierModalOpen"
                @update:open="supplierModalOpen = $event"
                @saved="handleSupplierCreated"
            />
        </div>
    </AppLayout>
</template>
