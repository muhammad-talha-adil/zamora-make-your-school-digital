<script setup lang="ts">
import axios from 'axios';
import { ref, computed, reactive, watch, nextTick } from 'vue';
import { alert, formatCurrency } from '@/utils';
import Swal from 'sweetalert2';

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
import SupplierForm from './SupplierForm.vue';

// Types
interface InventoryItem {
    id: number;
    campus_id: number;
    name: string;
    purchase_rate?: number;
    sale_rate?: number;
    available_stock: number;
}

interface Purchase {
    id: number;
    campus_id: number;
    supplier_id: number;
    supplier?: {
        id: number;
        name: string;
    };
    purchase_date: string;
    note: string;
    items: Array<{
        id: number;
        inventory_item_id: number;
        quantity: number;
        purchase_rate: number;
        sale_rate: number;
        inventory_item?: {
            id: number;
            name: string;
            campus_id?: number;
            stock_quantity?: number;
        };
    }>;
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
    purchase?: Purchase | null;
    open?: boolean;
    trigger?: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Add Purchase',
    variant: 'default',
    size: 'default',
    purchase: null,
    open: undefined,
});

// Check if we're in edit mode
const isEditMode = computed(() => !!props.purchase);

// Emits
const emit = defineEmits<{
    saved: [];
    'update:open': [value: boolean];
}>();

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

// Dynamic suppliers list (to include newly created ones)
// This is now a computed that combines props.suppliers with any additional suppliers added
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
    // If All Campuses is selected (empty string), don't filter by campus in search
    if (!form.campus_id) {
        return `/inventory/suppliers/all?active_only=false`;
    }
    return `/inventory/suppliers/all?campus_id=${currentCampusId.value}&active_only=false`;
});

// Filtered suppliers based on campus
const filteredLocalSuppliers = computed(() => {
    // In edit mode, always show all suppliers (including the purchase's supplier)
    if (isEditMode.value && props.purchase) {
        return localSuppliers.value;
    }
    
    const campusId = form.campus_id;
    
    // If All Campuses is selected, show all
    if (!campusId) {
        return localSuppliers.value;
    }
    
    const numericCampusId = parseInt(String(campusId));
    
    // Filter suppliers by campus or those without campus_id (All Campuses suppliers)
    return localSuppliers.value.filter(s => 
        s.campus_id === numericCampusId || s.campus_id === null
    );
});

// Handle new supplier created
const handleSupplierCreated = (supplier?: { id: number; name: string }) => {
    if (!supplier) return;
    // Add the new supplier to local list if not already present
    if (!localSuppliers.value.find(s => s.id === supplier.id)) {
        localSuppliers.value.push(supplier);
    }
    // Select the newly created supplier
    form.supplier_id = supplier.id;
    supplierModalOpen.value = false;
};

// Modal state
const dialogOpen = ref(false);

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
        
        // Create a map to avoid duplicates
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

// Watch for campus changes to fetch items
watch(() => form.campus_id, (newCampusId, oldCampusId) => {
    // Check if there are items with data entered (not just empty rows)
    const hasEnteredItems = form.purchase_items.some(item => 
        item.inventory_item_id > 0 && item.quantity > 0 && item.purchase_rate > 0
    );
    
    if (hasEnteredItems && oldCampusId && oldCampusId !== newCampusId) {
        // Show confirmation dialog
        Swal.fire({
            title: 'Change Campus?',
            text: 'By changing campus, the entered items will be lost because they are not saved. Do you want to continue?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Change Campus',
            cancelButtonText: 'No, Keep Items',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            backdrop: false,
        }).then((result) => {
            if (result.isConfirmed) {
                // User confirmed - change campus and reset items
                fetchInventoryItems();
            } else {
                // User cancelled - revert campus selection
                form.campus_id = oldCampusId;
            }
        });
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

// Initialize form - handle edit mode
const initializeForm = () => {
    if (props.purchase) {
        // Edit mode - populate form with purchase data
        form.campus_id = props.purchase.campus_id;
        form.supplier_id = props.purchase.supplier_id;
        // Handle purchase_date - convert to YYYY-MM-DD format
        if (props.purchase.purchase_date) {
            const date = new Date(props.purchase.purchase_date);
            form.purchase_date = date.toISOString().split('T')[0];
        }
        form.note = props.purchase.note || '';
        // Map items with proper data
        form.purchase_items = props.purchase.items.map((item: any) => ({
            inventory_item_id: item.inventory_item_id,
            quantity: item.quantity,
            purchase_rate: item.purchase_rate,
            sale_rate: item.sale_rate || 0,
        }));
        
        // Fetch inventory items for the purchase's campus
        fetchInventoryItems();
    } else {
        // Create mode - reset to defaults
        form.campus_id = props.campuses[0]?.id || '';
        form.supplier_id = null;
        form.purchase_date = new Date().toISOString().split('T')[0];
        form.note = '';
        form.purchase_items = [];
        addItem();
    }
    errors.value = {};
};

// Watch for purchase prop changes (when editing)
watch(() => props.purchase, async (newPurchase) => {
    if (newPurchase) {
        console.log('Purchase data received for edit:', newPurchase);
        initializeForm();
        dialogOpen.value = true; // Open dialog when purchase is provided for editing
        
        // Force update after DOM update to ensure supplier is displayed
        await nextTick();
        // Re-set the supplier_id to trigger ComboboxInput update
        if (props.purchase?.supplier_id) {
            form.supplier_id = props.purchase.supplier_id;
        }
    }
}, { immediate: true });

// Watch for open prop changes (external control)
watch(() => props.open, (isOpen) => {
    if (isOpen !== undefined) {
        dialogOpen.value = isOpen;
    }
});

// Watch for dialog open/close to emit updates
watch(dialogOpen, (isOpen) => {
    if (props.open !== undefined) {
        emit('update:open', isOpen);
    }
    if (isOpen) {
        fetchInventoryItems();
        // Generate idempotency key when dialog opens for new purchases
        if (!isEditMode.value) {
            idempotencyKey.value = `purchase_${Date.now()}_${Math.random().toString(36).substring(2, 11)}`;
        }
    }
});

// Computed
const availableItems = computed(() => {
    return inventoryItems.value;
});

const calculateTotal = computed(() => {
    return form.purchase_items.reduce((sum, item) => {
        return sum + (item.quantity * item.purchase_rate);
    }, 0);
});

// Calculate estimated profit (sale_rate - purchase_rate) * quantity
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

const resetForm = () => {
    form.campus_id = '';
    form.supplier_id = null;
    form.purchase_date = new Date().toISOString().split('T')[0];
    form.note = '';
    form.purchase_items = [];
    errors.value = {};
    // Reset idempotency key for new purchase
    idempotencyKey.value = null;
};

const closeModal = () => {
    dialogOpen.value = false;
    setTimeout(() => {
        resetForm();
    }, 300);
};

const submitForm = () => {
    // Filter out empty/incomplete items (items without valid item selected)
    const validItems = form.purchase_items.filter(item => item.inventory_item_id > 0);
    
    if (validItems.length === 0) {
        alert.error('Please add at least one item to the purchase.');
        return;
    }

    // Check if all valid items have required fields
    const hasInvalidItems = validItems.some(item => 
        !item.inventory_item_id || item.quantity <= 0 || item.purchase_rate <= 0
    );
    
    if (hasInvalidItems) {
        alert.error('Please fill in all required fields for each item (Item, Quantity, Purchase Rate).');
        return;
    }

    processing.value = true;
    errors.value = {};

    // Submit with filtered items
    const submitData = {
        ...form,
        purchase_items: validItems
    };
    
    const headers = idempotencyKey.value ? { 'X-Idempotency-Key': idempotencyKey.value } : {};
    
    if (isEditMode.value) {
        // Update existing purchase
        axios.put(`/inventory/purchases/${props.purchase!.id}`, submitData)
            .then(() => {
                alert.success('Purchase updated successfully!');
                closeModal();
                emit('saved');
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
        // Create new purchase
        axios.post('/inventory/purchases', submitData, { headers })
            .then(() => {
                alert.success('Purchase created successfully! Stock has been updated.');
                closeModal();
                emit('saved');
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
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogTrigger as-child>
            <Button :variant="props.variant" :size="props.size">
                <Icon icon="shopping-cart" class="mr-1" />
                {{ isEditMode ? 'Edit Purchase' : trigger }}
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <div class="p-2 bg-green-100 dark:bg-green-900/20 rounded-lg">
                        <Icon icon="shopping-cart" class="h-5 w-5 text-green-600" />
                    </div>
                    {{ isEditMode ? 'Edit Purchase Order' : 'Create Purchase Order' }}
                </DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submitForm" class="space-y-5">
                <!-- Form Card -->
                <div class="bg-card rounded-lg border p-5 space-y-4">
                    <!-- Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                                <div class="p-1 bg-muted rounded">
                                    <Icon icon="truck" class="h-3.5 w-3.5 text-muted-foreground" />
                                </div>
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
                                    class="h-11 px-3"
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
                                <div class="p-1 bg-muted rounded">
                                    <Icon icon="calendar" class="h-3.5 w-3.5 text-muted-foreground" />
                                </div>
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

                    <!-- Purchase Items -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <Label class="flex items-center gap-2">
                                <div class="p-1 bg-muted rounded">
                                    <Icon icon="list" class="h-3.5 w-3.5 text-muted-foreground" />
                                </div>
                                Purchase Items <span class="text-red-500">*</span>
                            </Label>
                            <Button type="button" variant="outline" size="sm" @click="addItem" class="h-8">
                                <Icon icon="plus" class="mr-1 h-3 w-3" />
                                Add Item
                            </Button>
                        </div>

                        <div v-if="form.purchase_items.length === 0" class="text-center py-8 text-gray-500 border rounded-lg">
                            <Icon icon="shopping-cart" :size="48" class="mx-auto mb-2 text-gray-300" />
                            <p>No items added yet. Click "Add Item" to start.</p>
                        </div>

                        <div v-else class="space-y-4">
                            <div v-for="(item, index) in form.purchase_items" :key="index" class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div class="space-y-2">
                                        <Label class="text-xs">Item <span class="text-red-500">*</span></Label>
                                        <div class="relative">
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
                                            <Icon 
                                                v-if="loadingItems" 
                                                icon="loader" 
                                                class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 animate-spin text-muted-foreground" 
                                            />
                                        </div>
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
                                <div class="flex items-center gap-2 pt-6">
                                    <div class="text-right min-w-20">
                                        <div class="text-sm font-bold">{{ formatCurrency(item.quantity * item.purchase_rate) }}</div>
                                    </div>
                                    <Button type="button" variant="ghost" size="sm" @click="removeItem(index)" class="h-8 w-8 p-0">
                                        <Icon icon="trash" class="text-red-500" />
                                    </Button>
                                </div>
                            </div>
                            
                            <!-- No items available message -->
                            <div v-if="availableItems.length === 0 && !loadingItems" class="text-center py-4 text-amber-600 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                                <Icon icon="alert-triangle" class="mr-2 h-4 w-4 inline" />
                                No inventory items found. Please add items in Inventory Settings first.
                            </div>
                        </div>
                    </div>

                    <!-- Total & Estimated Profit -->
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2 flex justify-between">
                        <div class="flex justify-end items-center gap-4">
                            <span class="text-lg font-semibold">Total Amount:</span>
                            <span class="text-2xl font-bold text-green-600">{{ formatCurrency(calculateTotal) }}</span>
                        </div>
                        <div class="flex justify-end items-center gap-4">
                            <span class="text-lg font-semibold">Estimated Profit:</span>
                            <span 
                                class="text-2xl font-bold"
                                :class="calculateEstimatedProfit >= 0 ? 'text-blue-600' : 'text-red-600'"
                            >
                                {{ formatCurrency(calculateEstimatedProfit) }}
                            </span>
                        </div>
                    </div>

                    <!-- Note -->
                    <div class="space-y-2">
                        <Label for="note" class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="align-left" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
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
                <div class="flex justify-end gap-3 pt-2">
                    <DialogClose as-child>
                        <Button type="button" variant="outline" @click="resetForm" class="h-10">
                            <Icon icon="x" class="mr-2 h-4 w-4" />
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button type="submit" :disabled="processing || loadingItems" class="h-10">
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
        </DialogContent>
    </Dialog>
</template>
