<script setup lang="ts">
import axios from 'axios';
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { alert, formatCurrency } from '@/utils';

// Layout
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

// Components
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';
import ComboboxInput from '@/components/ui/combobox/ComboboxInput.vue';

// Types
interface InventoryType {
    id: number;
    name: string;
}

interface InventoryItem {
    id: number;
    name: string;
    description?: string;
    sale_rate?: number;
    purchase_rate?: number;
    available_stock?: number;
    inventory_type_id?: number;
    inventory_type_name?: string;
}

interface Student {
    id: number;
    name: string;
    registration_number: string;
    display_text: string;
    class_name?: string;
    section_name?: string;
    class_id?: number;
    section_id?: number;
}

interface Props {
    campuses: Array<{
        id: number;
        name: string;
    }>;
}

const props = defineProps<Props>();

// Breadcrumb
const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory', href: '/inventory' },
    { title: 'Student Inventory', href: '/inventory/student-inventory' },
    { title: 'Assign to Student', href: '#' },
];

// Form data
const form = ref({
    campus_id: '',
    student_id: null as number | null,
    assigned_date: new Date().toISOString().split('T')[0],
    items: [] as Array<{
        inventory_type_id: number | null;
        inventory_item_id: number | null;
        quantity: number;
        discount_amount: number;
        discount_percentage: number;
    }>,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

// Data states
const inventoryTypes = ref<InventoryType[]>([]);
const inventoryItems = ref<InventoryItem[]>([]);
const loadingTypes = ref(false);
const loadingItems = ref(false);

// Per-row item cache - stores items for each item row based on type selection
const itemSpecificItems = ref<Map<number, InventoryItem[]>>(new Map());

// Selected type (kept for reference but not used in header)
const selectedTypeId = ref<number | null>(null);

// Student data for display
const selectedStudent = ref<Student | null>(null);

// Watch for campus changes
watch(() => form.value.campus_id, async (newCampusId) => {
    console.log('Campus changed:', newCampusId);
    if (newCampusId) {
        // Fetch inventory types for the campus
        await fetchInventoryTypes(newCampusId);
        // Fetch all items for the campus
        await fetchInventoryItems(newCampusId);
        
        // Reset student selection
        form.value.student_id = null;
        selectedStudent.value = null;
        
        // Clear items when campus changes
        form.value.items = [];
        itemSpecificItems.value.clear();
        addItem();
    }
});

// Initial data fetch on mount
onMounted(() => {
    if (form.value.campus_id) {
        fetchInventoryTypes(form.value.campus_id);
        fetchInventoryItems(form.value.campus_id);
    }
    // Add initial item row if none exist
    if (form.value.items.length === 0) {
        addItem();
    }
});

// Watch for student selection
watch(() => form.value.student_id, (newStudentId) => {
    if (newStudentId) {
        // Find selected student from the data
        // The student data will be loaded via the combobox
    } else {
        selectedStudent.value = null;
    }
});

// Fetch inventory types
const fetchInventoryTypes = async (campusId: number | string) => {
    loadingTypes.value = true;
    try {
        const response = await axios.get(`/inventory/student-inventory/types?campus_id=${campusId}`);
        console.log('Inventory types response:', response.data);
        inventoryTypes.value = response.data || [];
    } catch (err: any) {
        console.error('Failed to fetch inventory types:', err);
        if (err.response) {
            console.error('Error response:', err.response.data);
        }
        inventoryTypes.value = [];
    } finally {
        loadingTypes.value = false;
    }
};

// Fetch all inventory items for campus
const fetchInventoryItems = async (campusId: number | string) => {
    loadingItems.value = true;
    try {
        const response = await axios.get(`/inventory/student-inventory/items?campus_id=${campusId}`);
        console.log('Inventory items response:', response.data);
        inventoryItems.value = response.data || [];
    } catch (err: any) {
        console.error('Failed to fetch inventory items:', err);
        if (err.response) {
            console.error('Error response:', err.response.data);
        }
        inventoryItems.value = [];
    } finally {
        loadingItems.value = false;
    }
};

// Handle type selection change for a specific row
const onTypeChange = async (item: typeof form.value.items[0]) => {
    item.inventory_item_id = null;
    item.discount_amount = 0;
    item.discount_percentage = 0;
    if (item.inventory_type_id && form.value.campus_id) {
        await fetchInventoryItemsForType(form.value.campus_id, item.inventory_type_id);
    }
};

// Watch for type changes - fetch items for a specific type
const fetchInventoryItemsForType = async (campusId: number | string, typeId: number | null) => {
    loadingItems.value = true;
    try {
        let url = `/inventory/student-inventory/items?campus_id=${campusId}`;
        if (typeId) {
            url += `&type_id=${typeId}`;
        }
        const response = await axios.get(url);
        // Store items with typeId as key
        const key = typeId || 0;
        itemSpecificItems.value.set(key, response.data || []);
    } catch (err) {
        console.error('Failed to fetch inventory items:', err);
    } finally {
        loadingItems.value = false;
    }
};

// Get items for a specific item row
const getItemsForRow = (item: typeof form.value.items[0]): InventoryItem[] => {
    // If the row has a type selected, check if we have cached items for that type
    if (item.inventory_type_id) {
        const typeItems = itemSpecificItems.value.get(item.inventory_type_id);
        // Return type-specific items if available, otherwise fall back to all items
        if (typeItems) {
            return typeItems;
        }
        return inventoryItems.value;
    }
    // Otherwise return all items
    return inventoryItems.value;
};

// Computed
const studentSearchUrl = computed(() => {
    if (!form.value.campus_id) return '';
    return `/inventory/student-inventory/students?campus_id=${form.value.campus_id}`;
});

const canSubmit = computed(() => {
    return form.value.campus_id && 
           form.value.student_id && 
           form.value.items.some(item => item.inventory_item_id && item.quantity > 0);
});

// Calculate item total (quantity * sale_rate - discount on total)
const calculateItemTotal = (item: typeof form.value.items[0]) => {
    const inventoryItem = getItemsForRow(item).find(i => i.id === item.inventory_item_id);
    if (!inventoryItem) return 0;
    
    // Calculate subtotal (quantity * sale_rate)
    const subtotal = (inventoryItem.sale_rate || 0) * item.quantity;
    
    // Apply discount on subtotal
    let discount = 0;
    if (item.discount_percentage > 0) {
        discount = subtotal * (item.discount_percentage / 100);
    } else if (item.discount_amount > 0) {
        // discount_amount is per unit, so multiply by quantity
        discount = item.discount_amount * item.quantity;
    }
    
    return subtotal - discount;
};

// Calculate discount from amount (when user enters discount amount, calculate % based on total)
const onDiscountAmountChange = (item: typeof form.value.items[0]) => {
    if (!item.inventory_item_id) {
        item.discount_percentage = 0;
        return;
    }
    const inventoryItem = getItemsForRow(item).find(i => i.id === item.inventory_item_id);
    if (inventoryItem && inventoryItem.sale_rate && item.discount_amount > 0) {
        // Calculate based on total (quantity * sale_rate), not single unit
        const totalPrice = inventoryItem.sale_rate * item.quantity;
        item.discount_percentage = parseFloat(((item.discount_amount / totalPrice) * 100).toFixed(2));
    } else if (item.discount_amount === 0) {
        item.discount_percentage = 0;
    }
};

// Calculate discount from percentage (when user enters discount %, calculate amount based on total)
const onDiscountPercentageChange = (item: typeof form.value.items[0]) => {
    if (!item.inventory_item_id) {
        item.discount_amount = 0;
        return;
    }
    const inventoryItem = getItemsForRow(item).find(i => i.id === item.inventory_item_id);
    if (inventoryItem && inventoryItem.sale_rate && item.discount_percentage > 0) {
        // Calculate based on total (quantity * sale_rate), not single unit
        const totalPrice = inventoryItem.sale_rate * item.quantity;
        item.discount_amount = parseFloat((totalPrice * (item.discount_percentage / 100)).toFixed(2));
    } else if (item.discount_percentage === 0) {
        item.discount_amount = 0;
    }
};

// Get sale price before discount
const getItemSalePrice = (item: typeof form.value.items[0]): number => {
    const inventoryItem = getItemsForRow(item).find(i => i.id === item.inventory_item_id);
    return inventoryItem?.sale_rate || 0;
};

const totalOriginalAmount = computed(() => {
    return form.value.items.reduce((sum, item) => {
        const inventoryItem = getItemsForRow(item).find(i => i.id === item.inventory_item_id);
        if (!inventoryItem) return sum;
        return sum + ((inventoryItem.sale_rate || 0) * item.quantity);
    }, 0);
});

const totalDiscount = computed(() => {
    return form.value.items.reduce((sum, item) => {
        const inventoryItem = getItemsForRow(item).find(i => i.id === item.inventory_item_id);
        if (!inventoryItem) return sum;
        
        // Calculate discount based on total (quantity * sale_rate)
        const subtotal = (inventoryItem.sale_rate || 0) * item.quantity;
        let discount = 0;
        if (item.discount_percentage > 0) {
            discount = subtotal * (item.discount_percentage / 100);
        } else if (item.discount_amount > 0) {
            discount = item.discount_amount * item.quantity;
        }
        return sum + discount;
    }, 0);
});

const totalAmount = computed(() => {
    return totalOriginalAmount.value - totalDiscount.value;
});

// Methods
const addItem = () => {
    form.value.items.push({
        inventory_type_id: null,
        inventory_item_id: null,
        quantity: 1,
        discount_amount: 0,
        discount_percentage: 0,
    });
};

const removeItem = (index: number) => {
    form.value.items.splice(index, 1);
};

const getItemDetails = (item: typeof form.value.items[0]) => {
    return getItemsForRow(item).find(i => i.id === item.inventory_item_id);
};

const resetForm = () => {
    form.value = {
        campus_id: '',
        student_id: null,
        assigned_date: new Date().toISOString().split('T')[0],
        items: [{ inventory_type_id: null, inventory_item_id: null, quantity: 1, discount_amount: 0, discount_percentage: 0 }],
    };
    errors.value = {};
    selectedStudent.value = null;
    selectedTypeId.value = null;
    // Clear item-specific cache
    itemSpecificItems.value.clear();
};

const handleStudentChange = (value: number | string | null) => {
    form.value.student_id = value as number | null;
    if (!value) {
        selectedStudent.value = null;
    }
};

const submitForm = () => {
    // Filter valid items
    const validItems = form.value.items.filter(item => 
        item.inventory_item_id && item.quantity > 0
    );
    
    if (validItems.length === 0) {
        alert.error('Please add at least one item to assign.');
        return;
    }

    if (!form.value.student_id) {
        alert.error('Please select a student.');
        return;
    }

    // Validate quantities against stock
    for (const item of validItems) {
        const itemDetails = getItemDetails(item);
        if (itemDetails && item.quantity > (itemDetails.available_stock || 0)) {
            alert.error(`Quantity exceeds available stock for ${itemDetails.name}. Available: ${itemDetails.available_stock}`);
            return;
        }
    }

    processing.value = true;
    errors.value = {};

    // Submit data in the format expected by the controller
    const submitData = {
        campus_id: form.value.campus_id,
        student_id: form.value.student_id,
        assigned_date: form.value.assigned_date,
        items: validItems.map(item => ({
            inventory_item_id: item.inventory_item_id,
            quantity: item.quantity,
            discount_amount: item.discount_amount,
            discount_percentage: item.discount_percentage,
        })),
    };

    axios.post('/inventory/student-inventory/assign', submitData)
        .then(() => {
            alert.success('Inventory assigned to student successfully!');
            router.get('/inventory/student-manage');
        })
        .catch((err) => {
            if (err.response?.data?.errors) {
                errors.value = err.response.data.errors;
                const firstError = Object.values(err.response.data.errors)[0] as string;
                alert.error(firstError);
            } else if (err.response?.data?.message) {
                alert.error(err.response.data.message);
            } else {
                alert.error('Failed to assign inventory. Please check the errors.');
            }
        })
        .finally(() => {
            processing.value = false;
        });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Assign Inventory to Student" />

        <div class="container mx-auto py-6 px-4 max-w-6xl">
        <!-- Header -->
        <div class="mb-6">
            <Button variant="ghost" size="sm" @click="router.get('/inventory/student-inventory')" class="mb-2">
                <Icon icon="arrow-left" class="mr-1 h-4 w-4" />
                Back to Student Inventory
            </Button>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Assign Inventory to Student
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                Assign inventory items to a student
            </p>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="space-y-6">
            <!-- Basic Info Card -->
            <div class="bg-card rounded-lg border p-6 space-y-4">
                <h2 class="text-lg font-semibold mb-4">Assignment Details</h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
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

                    <!-- Student -->
                    <div class="space-y-2">
                        <Label class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="user" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Student <span class="text-red-500">*</span>
                        </Label>
                        <ComboboxInput
                            v-model="form.student_id"
                            :search-url="studentSearchUrl"
                            placeholder="Search student by name or reg. no"
                            value-type="id"
                            @update:model-value="handleStudentChange"
                        />
                        <InputError :message="errors.student_id" />
                        
                        <!-- Student Info -->
                        <div v-if="selectedStudent" class="mt-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-sm">
                                <div class="font-medium">{{ selectedStudent.name }}</div>
                                <div class="text-gray-500">{{ selectedStudent.registration_number }}</div>
                                <div v-if="selectedStudent.class_name" class="text-gray-500">
                                    Class: {{ selectedStudent.class_name }}
                                    <span v-if="selectedStudent.section_name"> - {{ selectedStudent.section_name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assignment Date -->
                    <div class="space-y-2">
                        <Label for="assigned_date" class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="calendar" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Assignment Date
                        </Label>
                        <Input
                            id="assigned_date"
                            v-model="form.assigned_date"
                            type="date"
                            class="h-11"
                        />
                    </div>
                </div>
            </div>

            <!-- Inventory Items Card -->
            <div class="bg-card rounded-lg border p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Inventory Items</h2>
                    <Button type="button" variant="outline" size="sm" @click="addItem">
                        <Icon icon="plus" class="mr-1 h-4 w-4" />
                        Add Item
                    </Button>
                </div>

                <!-- No items message -->
                <div v-if="form.items.length === 0" class="text-center py-8 text-gray-500 border rounded-lg">
                    <Icon icon="box" :size="48" class="mx-auto mb-2 text-gray-300" />
                    <p>No items added yet. Click "Add Item" to start.</p>
                </div>

                <!-- Items List -->
                <div v-else class="space-y-4">
                    <div v-for="(item, index) in form.items" :key="index" class="flex flex-col md:flex-row md:items-start gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex-1 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 lg:grid-cols-6 gap-3 w-full">
                            <!-- Item Type -->
                            <div class="col-span-2 sm:col-span-3 md:col-span-2 space-y-2">
                                <Label class="text-xs">Item Type</Label>
                                <select
                                    v-model="item.inventory_type_id"
                                    @change="onTypeChange(item)"
                                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm h-10"
                                >
                                    <option :value="null">Select Type</option>
                                    <option v-for="type in inventoryTypes" :key="type.id" :value="type.id">
                                        {{ type.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Item Selection -->
                            <div class="col-span-2 sm:col-span-3 md:col-span-2 space-y-2">
                                <Label class="text-xs">Item <span class="text-red-500">*</span></Label>
                                <select
                                    v-model="item.inventory_item_id"
                                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm h-10"
                                    :class="{ 'border-red-500': errors[`items.${index}.inventory_item_id`] }"
                                    required
                                >
                                    <option :value="null">
                                        {{ loadingItems ? 'Loading...' : 'Select Item' }}
                                    </option>
                                    <option 
                                        v-for="invItem in getItemsForRow(item)" 
                                        :key="invItem.id" 
                                        :value="invItem.id"
                                        :disabled="invItem.available_stock === 0"
                                    >
                                        {{ invItem.name }} - {{ formatCurrency(invItem.sale_rate || 0) }} ({{ invItem.available_stock }} in stock)
                                    </option>
                                </select>
                                <InputError :message="errors[`items.${index}.inventory_item_id`]" />
                            </div>

                            <!-- Quantity -->
                            <div class="col-span-1 sm:col-span-1 md:col-span-1 space-y-2">
                                <Label class="text-xs">Qty <span class="text-red-500">*</span></Label>
                                <Input
                                    v-model.number="item.quantity"
                                    type="number"
                                    min="1"
                                    :max="getItemDetails(item)?.available_stock || 0"
                                    class="h-10"
                                    required
                                />
                            </div>

                            <!-- Sale Price (Read-only) -->
                            <div class="col-span-1 sm:col-span-1 md:col-span-1 space-y-2">
                                <Label class="text-xs">Price</Label>
                                <div class="h-10 px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-md text-sm flex items-center overflow-hidden">
                                    <span v-if="item.inventory_item_id" class="font-medium text-green-600 text-xs">
                                        {{ formatCurrency(getItemSalePrice(item)) }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </div>
                            </div>

                            <!-- Discount Amount -->
                            <div class="col-span-1 sm:col-span-1 md:col-span-1 space-y-2">
                                <Label class="text-xs">Disc. PKR</Label>
                                <Input
                                    v-model.number="item.discount_amount"
                                    @input="onDiscountAmountChange(item)"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="h-10"
                                />
                            </div>

                            <!-- Discount Percentage -->
                            <div class="col-span-1 sm:col-span-1 md:col-span-1 space-y-2">
                                <Label class="text-xs">Disc. %</Label>
                                <Input
                                    v-model.number="item.discount_percentage"
                                    @input="onDiscountPercentageChange(item)"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    class="h-10"
                                />
                            </div>
                        </div>

                        <!-- Item Total & Remove -->
                        <div class="flex flex-row md:flex-col items-center justify-between md:justify-start md:items-end gap-2 pt-0 md:pt-5 min-w-24 border-t md:border-t-0 border-gray-200 md:border-0 pt-3 md:pt-0">
                            <div class="text-right">
                                <div class="text-sm font-bold">{{ formatCurrency(calculateItemTotal(item)) }}</div>
                            </div>
                            <Button 
                                type="button" 
                                variant="ghost" 
                                size="sm" 
                                @click="removeItem(index)" 
                                class="h-8 w-8 p-0 text-red-500 hover:text-red-700 hover:bg-red-50"
                                :title="form.items.length === 1 ? 'Cannot delete the only item' : 'Remove this item'"
                            >
                                <Icon icon="trash" class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                    
                    <!-- No items available -->
                    <div v-if="inventoryItems.length === 0 && !loadingItems && form.campus_id" class="text-center py-4 text-amber-600 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                        <Icon icon="alert-triangle" class="mr-2 h-4 w-4 inline" />
                        No inventory items found for the selected campus and type.
                    </div>
                </div>
            </div>

            <!-- Price Summary -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Original Amount:</span>
                    <span class="text-lg font-semibold">{{ formatCurrency(totalOriginalAmount) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Total Discount:</span>
                    <span class="text-lg font-semibold text-red-500">- {{ formatCurrency(totalDiscount) }}</span>
                </div>
                <div class="border-t pt-3 flex justify-between items-center">
                    <span class="text-xl font-bold">Total Amount:</span>
                    <span class="text-2xl font-bold text-green-600">{{ formatCurrency(totalAmount) }}</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-2">
                <Button type="button" variant="outline" @click="resetForm" class="h-10">
                    <Icon icon="refresh-cw" class="mr-2 h-4 w-4" />
                    Reset
                </Button>
                <Button type="submit" :disabled="processing || !canSubmit" class="h-10">
                    <Icon v-if="processing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                    <Icon v-else icon="check" class="mr-2 h-4 w-4" />
                    Assign to Student
                </Button>
            </div>
        </form>
        </div>
    </AppLayout>
</template>
