<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { alert, formatCurrency } from '@/utils';

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

// Types
interface Student {
    id: number;
    name: string;
    registration_number: string;
}

interface InventoryItem {
    id: number;
    name?: string;
    description?: string;
    sale_rate?: number;
    purchase_rate?: number;
    available_stock?: number;
    low_stock_threshold?: number;
    is_low_stock?: boolean;
}

interface Props {
    campuses: Array<{
        id: number;
        name: string;
    }>;
    students: Student[];
    inventoryItems: InventoryItem[];
    trigger?: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Assign to Student',
    variant: 'default',
    size: 'default',
    students: () => [],
    inventoryItems: () => [],
});

// Emits
const emit = defineEmits<{
    saved: [];
}>();

// Form data
const form = ref({
    campus_id: props.campuses[0]?.id || '',
    student_id: '',
    inventory_item_id: '',
    quantity: 1,
    assigned_date: new Date().toISOString().split('T')[0],
    discount_amount: 0,
    discount_percentage: 0,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

// Modal state
const dialogOpen = ref(false);

// Computed
const filteredStudents = computed(() => {
    return props.students;
});

const filteredItems = computed(() => {
    return props.inventoryItems;
});

const selectedItem = computed(() => {
    return props.inventoryItems.find(item => item.id === parseInt(form.value.inventory_item_id));
});

const finalPrice = computed(() => {
    if (!selectedItem.value) return 0;
    let price = selectedItem.value.sale_rate || 0;
    if (form.value.discount_percentage > 0) {
        price = price - (price * (form.value.discount_percentage / 100));
    } else if (form.value.discount_amount > 0) {
        price = price - form.value.discount_amount;
    }
    return Number(price.toFixed(2));
});

const totalValue = computed(() => {
    const price = parseFloat(String(finalPrice.value)) || 0;
    return (price * form.value.quantity).toFixed(2);
});

// Methods
// Handle discount amount change - auto-calculate percentage
const onDiscountAmountChange = () => {
    if (!selectedItem.value || !selectedItem.value.sale_rate) {
        form.value.discount_percentage = 0;
        return;
    }
    if (form.value.discount_amount > 0) {
        form.value.discount_percentage = Number(((form.value.discount_amount / selectedItem.value.sale_rate) * 100).toFixed(2));
    } else {
        form.value.discount_percentage = 0;
    }
};

// Handle discount percentage change - auto-calculate amount
const onDiscountPercentageChange = () => {
    if (!selectedItem.value || !selectedItem.value.sale_rate) {
        form.value.discount_amount = 0;
        return;
    }
    if (form.value.discount_percentage > 0) {
        form.value.discount_amount = Number((selectedItem.value.sale_rate * (form.value.discount_percentage / 100)).toFixed(2));
    } else {
        form.value.discount_amount = 0;
    }
};

const resetForm = () => {
    form.value = {
        campus_id: props.campuses[0]?.id || '',
        student_id: '',
        inventory_item_id: '',
        quantity: 1,
        assigned_date: new Date().toISOString().split('T')[0],
        discount_amount: 0,
        discount_percentage: 0,
    };
    errors.value = {};
};

const closeModal = () => {
    dialogOpen.value = false;
    resetForm();
};

const submitForm = () => {
    if (form.value.quantity > (selectedItem.value?.available_stock || 0)) {
        alert.error('Quantity exceeds available stock. Please reduce the quantity.');
        return;
    }

    processing.value = true;
    errors.value = {};

    router.post('/inventory/student-inventory/assign', form.value, {
        onSuccess: () => {
            alert.success('Inventory assigned to student successfully!');
            closeModal();
            emit('saved');
            router.reload({ only: ['studentInventories', 'inventoryItems'] });
        },
        onError: (err) => {
            errors.value = err as Record<string, string>;
            if (Object.keys(errors.value).length > 0) {
                const firstError = Object.values(errors.value)[0];
                alert.error(firstError);
            } else {
                alert.error('Failed to assign inventory. Please check the errors.');
            }
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogTrigger as-child>
            <Button :variant="props.variant" :size="props.size">
                <Icon icon="user-plus" class="mr-1" />
                {{ trigger }}
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                        <Icon icon="user-plus" class="h-5 w-5 text-blue-600" />
                    </div>
                    Assign Inventory to Student
                </DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submitForm" class="space-y-5">
                <!-- Form Card -->
                <div class="bg-card rounded-lg border p-5 space-y-4">
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
                        <Label for="student_id" class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="user" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Student <span class="text-red-500">*</span>
                        </Label>
                        <select
                            id="student_id"
                            v-model="form.student_id"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm h-11"
                            :class="{ 'border-red-500': errors.student_id }"
                            required
                        >
                            <option value="">Select Student</option>
                            <option v-for="student in filteredStudents" :key="student.id" :value="student.id">
                                {{ student.name }} ({{ student.registration_number }})
                            </option>
                        </select>
                        <InputError :message="errors.student_id" />
                    </div>

                    <!-- Inventory Item -->
                    <div class="space-y-2">
                        <Label for="inventory_item_id" class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="box" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Inventory Item <span class="text-red-500">*</span>
                        </Label>
                        <select
                            id="inventory_item_id"
                            v-model="form.inventory_item_id"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm h-11"
                            :class="{ 'border-red-500': errors.inventory_item_id }"
                            required
                        >
                            <option value="">Select Item</option>
                            <option v-for="item in filteredItems" :key="item.id" :value="item.id">
                                {{ item.name }} - {{ formatCurrency(item.sale_rate || 0) }} ({{ item.available_stock || 0 }} in stock)
                            </option>
                        </select>
                        <InputError :message="errors.inventory_item_id" />

                        <!-- Item Details -->
                        <div v-if="selectedItem" class="mt-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-sm">
                                <div class="font-medium">{{ selectedItem.name }}</div>
                                <div class="text-gray-500">{{ selectedItem.description }}</div>
                                <div class="mt-2 flex gap-4">
                                    <span>Sale Rate: <strong>{{ formatCurrency(selectedItem?.sale_rate || 0) }}</strong></span>
                                    <span>Available: <strong :class="selectedItem?.is_low_stock ? 'text-red-600' : 'text-green-600'">{{ selectedItem?.available_stock || 0 }}</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity and Date -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="quantity" class="flex items-center gap-2">
                                <div class="p-1 bg-muted rounded">
                                    <Icon icon="hash" class="h-3.5 w-3.5 text-muted-foreground" />
                                </div>
                                Quantity <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                id="quantity"
                                v-model.number="form.quantity"
                                type="number"
                                min="1"
                                :max="selectedItem?.available_stock || 999"
                                class="h-11"
                                :class="{ 'border-red-500': errors.quantity }"
                                required
                            />
                            <InputError :message="errors.quantity" />
                        </div>
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

                    <!-- Discounts -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="discount_amount" class="flex items-center gap-2">
                                <div class="p-1 bg-muted rounded">
                                    <Icon icon="dollar-sign" class="h-3.5 w-3.5 text-muted-foreground" />
                                </div>
                                Discount (PKR)
                            </Label>
                            <Input
                                id="discount_amount"
                                v-model.number="form.discount_amount"
                                @input="onDiscountAmountChange"
                                type="number"
                                step="0.01"
                                min="0"
                                class="h-11"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="discount_percentage" class="flex items-center gap-2">
                                <div class="p-1 bg-muted rounded">
                                    <Icon icon="percent" class="h-3.5 w-3.5 text-muted-foreground" />
                                </div>
                                Discount (%)
                            </Label>
                            <Input
                                id="discount_percentage"
                                v-model.number="form.discount_percentage"
                                @input="onDiscountPercentageChange"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                class="h-11"
                            />
                        </div>
                    </div>

                    <!-- Price Summary -->
                    <div v-if="selectedItem" class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                            <div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Unit Price: <span class="line-through">{{ formatCurrency(selectedItem?.sale_rate || 0) }}</span></div>
                                <div class="text-lg font-bold text-green-600">Final Price: {{ formatCurrency(finalPrice) }} / unit</div>
                            </div>
                            <div class="text-center sm:text-right">
                                <div class="text-sm text-gray-600 dark:text-gray-400">Total for {{ form.quantity }} units</div>
                                <div class="text-2xl font-bold text-green-600">{{ formatCurrency(totalValue) }}</div>
                            </div>
                        </div>
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
                    <Button type="submit" :disabled="processing" class="h-10">
                        <Icon v-if="processing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                        <Icon v-else icon="check" class="mr-2 h-4 w-4" />
                        Assign to Student
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
