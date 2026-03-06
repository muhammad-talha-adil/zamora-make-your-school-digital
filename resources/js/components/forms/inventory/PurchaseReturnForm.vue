<script setup lang="ts">
import axios from 'axios';
import { alert } from '@/utils';
import { ref, computed, reactive, watch } from 'vue';

// Components
import InputError from '@/components/InputError.vue';
import { Button, type ButtonVariants } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogClose,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';
import ComboboxInput from '@/components/ui/combobox/ComboboxInput.vue';

// Types
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
    name?: string; // For ComboboxInput compatibility
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
    initialSuppliers?: Array<{
        id: number;
        name: string;
    }>;
    initialPurchases?: Array<{
        id: number;
        name: string;
    }>;
    purchaseReturn?: {
        id: number;
        campus_id: number;
        supplier_id: number;
        supplier?: {
            id: number;
            name: string;
        } | null;
        purchase_id: number | null;
        return_date: string;
        note: string;
        items: Array<{
            id: number;
            inventory_item_id: number;
            quantity: number;
            unit_price: number;
            total: number;
            reason_id: number | null;
            reason?: string | null;
            inventory_item?: {
                id: number;
                name: string;
            } | null;
        }>;
    } | null;
    trigger?: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Add Return',
    variant: 'default',
    size: 'default',
    purchaseReturn: null,
});

// Check if we're in edit mode
const isEditMode = computed(() => !!props.purchaseReturn);

// Emits
const emit = defineEmits<{
    saved: [];
}>();

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

// Modal state
const dialogOpen = ref(false);

// Computed
const currentCampusId = computed(() => form.campus_id);

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

// Transform purchases for ComboboxInput (add name property for display)
const purchasesForCombobox = computed(() => {
    return purchases.value.map(p => ({
        ...p,
        name: p.display_text || `PUR-${p.id}`,
    }));
});

// Filtered purchases for display (kept for potential future use)
// const filteredPurchases = computed(() => {
//     let result = purchases.value;
//     if (form.campus_id) {
//         result = result.filter(p => p.campus_id === form.campus_id);
//     }
//     if (form.supplier_id) {
//         result = result.filter(p => p.supplier_id === form.supplier_id);
//     }
//     return result;
// });

// Inventory items based on selected purchase
const availableItems = computed(() => {
    // In edit mode, use items from the existing return directly
    if (isEditMode.value && props.purchaseReturn?.items && props.purchaseReturn.items.length > 0) {
        // Map return items to match the expected format for the dropdown
        return props.purchaseReturn.items.map((item: any) => ({
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
        
        // Find "Other" reason ID
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
        
        // Auto-populate items from selected purchase
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
    fetchSuppliers();
    fetchPurchases();
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

const resetForm = () => {
    form.campus_id = props.campuses[0]?.id || '';
    form.supplier_id = null;
    form.purchase_id = null;
    form.return_date = new Date().toISOString().split('T')[0];
    form.items = [];
    form.note = '';
    errors.value = {};
    suppliers.value = [];
    purchases.value = [];
    purchaseItems.value = [];
};

const closeModal = () => {
    dialogOpen.value = false;
    setTimeout(() => {
        resetForm();
    }, 300);
};

const initializeForm = async () => {
    await fetchReasons();
    await fetchSuppliers();
    await fetchPurchases();
};

// Populate form for edit mode
const populateEditForm = () => {
    if (!props.purchaseReturn) return;
    
    form.campus_id = props.purchaseReturn.campus_id;
    form.supplier_id = props.purchaseReturn.supplier_id;
    form.purchase_id = props.purchaseReturn.purchase_id;
    
    // Format the return date properly for the date input
    if (props.purchaseReturn.return_date) {
        const date = new Date(props.purchaseReturn.return_date);
        form.return_date = date.toISOString().split('T')[0];
    }
    
    form.note = props.purchaseReturn.note || '';
    
    // Populate items from the existing return
    if (props.purchaseReturn.items && props.purchaseReturn.items.length > 0) {
        // Populate purchaseItems for the dropdown
        purchaseItems.value = props.purchaseReturn.items.map((item: any) => ({
            inventory_item_id: item.inventory_item_id,
            id: item.id,
            item_name: item.inventory_item?.name || 'Unknown Item',
            quantity_purchased: item.quantity,
            current_stock: item.quantity,
            purchase_rate: item.unit_price,
            available_for_return: item.quantity,
        }));
        
        // Populate form.items
        form.items = props.purchaseReturn.items.map((item: any) => ({
            inventory_item_id: item.inventory_item_id,
            purchase_item_id: item.id,
            quantity: item.quantity,
            unit_price: item.unit_price,
            reason_id: item.reason_id || null,
            custom_reason: item.reason || '',
            available_for_return: item.quantity,
        }));
    }
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
        
        // If "Other" reason is selected, custom reason is required
        if (item.reason_id === otherReasonId.value && !item.custom_reason.trim()) {
            alert.error(`Please specify the reason for row ${i + 1}`);
            return false;
        }
    }
    return true;
};

const submitForm = () => {
    if (!validateItems()) {
        return;
    }
    
    processing.value = true;
    errors.value = {};

    // Prepare items data
    const itemsData = form.items.map(item => ({
        inventory_item_id: item.inventory_item_id,
        purchase_item_id: item.purchase_item_id,
        quantity: item.quantity,
        unit_price: item.unit_price,
        reason_id: item.reason_id === otherReasonId.value ? null : item.reason_id, // Don't save "Other" reason_id, will use custom_reason
        custom_reason: item.reason_id === otherReasonId.value ? item.custom_reason : null, // Save custom reason when "Other" is selected
    }));

    const apiUrl = isEditMode.value 
        ? `/inventory/purchase-returns/${props.purchaseReturn!.id}`
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
            closeModal();
            emit('saved');
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

// Watch for purchaseReturn prop changes (when editing)
watch(() => props.purchaseReturn, async (newReturn) => {
    if (newReturn) {
        console.log('PurchaseReturn data received for edit:', newReturn);
        dialogOpen.value = true; // Open dialog when return is provided for editing
    }
}, { immediate: true });

// Watch for dialog open
watch(dialogOpen, async (isOpen) => {
    if (isOpen) {
        await initializeForm();
        
        // If in edit mode, populate form with existing data
        if (props.purchaseReturn) {
            populateEditForm();
        }
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
// Note: Form initialization is now handled by watch on purchaseReturn prop
// initializeForm();
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogTrigger as-child>
            <Button :variant="props.variant" :size="props.size">
                <Icon icon="rotate-ccw" class="mr-1" />
                {{ trigger }}
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-5xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <div class="p-2 bg-red-100 dark:bg-red-900/20 rounded-lg">
                        <Icon icon="rotate-ccw" class="h-5 w-5 text-red-600" />
                    </div>
                    {{ isEditMode ? 'Edit Purchase Return' : 'New Purchase Return' }}
                </DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submitForm" class="space-y-5">
                <!-- Form Card -->
                <div class="bg-card rounded-lg border p-5 space-y-4">
                    <!-- Campus, Supplier, Purchase Row -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Campus -->
                        <div class="space-y-2">
                            <Label for="campus_id" class="flex items-center gap-2">
                                <div class="p-1 bg-muted rounded">
                                    <Icon icon="building" class="h-3.5 w-3.5 text-muted-foreground" />
                                </div>
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
                                <div class="p-1 bg-muted rounded">
                                    <Icon icon="truck" class="h-3.5 w-3.5 text-muted-foreground" />
                                </div>
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
                                <div class="p-1 bg-muted rounded">
                                    <Icon icon="shopping-cart" class="h-3.5 w-3.5 text-muted-foreground" />
                                </div>
                                Original Purchase <span class="text-sm text-muted-foreground font-normal">(Optional)</span>
                            </Label>
                            <ComboboxInput
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
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="calendar" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Return Date <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            id="return_date"
                            v-model="form.return_date"
                            type="date"
                            class="h-11"
                            :class="{ 'border-red-500': errors.return_date }"
                            required
                        />
                        <InputError :message="errors.return_date" />
                    </div>

                    <!-- Return Items -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <Label class="flex items-center gap-2">
                                <div class="p-1 bg-muted rounded">
                                    <Icon icon="list" class="h-3.5 w-3.5 text-muted-foreground" />
                                </div>
                                Return Items <span class="text-red-500">*</span>
                            </Label>
                            <Button type="button" variant="outline" size="sm" @click="addItem" class="h-8">
                                <Icon icon="plus" class="mr-1 h-3 w-3" />
                                Add Item
                            </Button>
                        </div>

                        <div class="border rounded-lg overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-12">Sr#</th>
                                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Item</th>
                                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Available</th>
                                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Quantity <span class="text-red-500">*</span></th>
                                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Unit Price <span class="text-red-500">*</span></th>
                                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-32">Total</th>
                                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reason</th>
                                            <th class="px-3 py-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="(item, index) in form.items" :key="index">
                                            <td class="px-2 py-3 text-sm text-gray-500 dark:text-gray-400">
                                                {{ index + 1 }}
                                            </td>
                                            <td class="px-3 py-3">
                                                <select
                                                    v-model="item.inventory_item_id"
                                                    @change="() => {
                                                        const pi = purchaseItems.find(p => p.inventory_item_id === item.inventory_item_id);
                                                        if (pi) {
                                                            item.available_for_return = pi.available_for_return;
                                                            item.unit_price = pi.purchase_rate;
                                                        }
                                                    }"
                                                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-1.5 text-sm h-10"
                                                    required
                                                >
                                                    <option :value="null">Select Item</option>
                                                    <option v-for="invItem in availableItems" :key="invItem.inventory_item_id" :value="invItem.inventory_item_id">
                                                        {{ invItem.item_name }}
                                                    </option>
                                                </select>
                                            </td>
                                            <td class="px-3 py-3 text-sm">
                                                <span :class="item.available_for_return > 0 ? 'text-green-600' : 'text-red-500'">
                                                    {{ item.available_for_return }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-3">
                                                <div class="flex items-center gap-1">
                                                    <Input
                                                        v-model.number="item.quantity"
                                                        type="number"
                                                        min="1"
                                                        :max="item.available_for_return"
                                                        class="w-20 h-10 text-sm"
                                                        :title="'Max: ' + item.available_for_return"
                                                        required
                                                    />
                                                    <span v-if="item.quantity > item.available_for_return" class="text-xs text-red-500 whitespace-nowrap">
                                                        (Max: {{ item.available_for_return }})
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-3 py-3">
                                                <div class="flex items-center gap-1">
                                                    <Input
                                                        v-model.number="item.unit_price"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        class="w-24 h-10 text-sm"
                                                        :title="'Original: ' + formatCurrency(getItemPurchaseRate(index))"
                                                        required
                                                    />
                                                    <span class="text-xs text-muted-foreground whitespace-nowrap">
                                                        ({{ formatCurrency(getItemPurchaseRate(index)) }})
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-3 py-3 text-sm font-medium text-right whitespace-nowrap">
                                                {{ formatCurrency(item.quantity * item.unit_price) }}
                                            </td>
                                            <td class="px-3 py-3 overflow-visible">
                                                <!-- Show custom reason input when "Other" is selected -->
                                                <div v-if="isOtherReason(item.reason_id)">
                                                    <Input
                                                        v-model="item.custom_reason"
                                                        type="text"
                                                        placeholder="Please specify reason..."
                                                        class="h-10 text-sm"
                                                        required
                                                    />
                                                </div>
                                                <!-- Show combobox for other cases -->
                                                <div v-else class="overflow-visible">
                                                    <ComboboxInput
                                                        v-model="item.reason_id"
                                                        :initial-items="reasons"
                                                        placeholder="Select reason"
                                                        value-type="id"
                                                    />
                                                </div>
                                            </td>
                                            <td class="px-3 py-3">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="removeItem(index)"
                                                    class="h-8 w-8 p-0"
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
                                                <div v-else>
                                                    No items available. Select a purchase order or click "Add Item".
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <InputError :message="errors.items" />
                    </div>

                    <!-- Total -->
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 flex justify-end">
                        <div class="text-right">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Return Amount</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(totalAmount) }}</div>
                        </div>
                    </div>

                    <!-- Note -->
                    <div class="space-y-2">
                        <Label for="note" class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="align-left" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
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
                <div class="flex justify-end gap-3 pt-2">
                    <DialogClose as-child>
                        <Button type="button" variant="outline" @click="resetForm" class="h-10">
                            <Icon icon="x" class="mr-2 h-4 w-4" />
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button type="submit" :disabled="processing || loading || form.items.length === 0" class="h-10">
                        <Icon v-if="processing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                        <Icon v-else icon="check" class="mr-2 h-4 w-4" />
                        {{ isEditMode ? 'Update Return' : 'Create Return' }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
