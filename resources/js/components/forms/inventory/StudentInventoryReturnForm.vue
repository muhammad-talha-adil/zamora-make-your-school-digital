<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { alert, formatCurrency } from '@/utils';

// Components
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
import axios from 'axios';

// Types
interface StudentInventoryItem {
    id: number;
    inventory_item_id: number;
    item_name_snapshot: string;
    description_snapshot?: string;
    unit_price_snapshot: number;
    quantity: number;
    returned_quantity: number;
    remaining_quantity: number;
    discount_amount?: number;
    discount_percentage?: number;
}

interface StudentInventory {
    id: number;
    campus_id: number;
    student_id: number;
    student_name: string;
    registration_number: string;
    class_name?: string;
    section_name?: string;
    items: StudentInventoryItem[];
}

interface Props {
    studentInventory: StudentInventory;
    trigger?: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Process Return',
    variant: 'default',
    size: 'default',
});

// Emits
const emit = defineEmits<{
    saved: [];
}>();

// Form data - supports multiple items
const form = ref({
    student_inventory_record_id: props.studentInventory.id,
    campus_id: props.studentInventory.campus_id,
    items: [] as Array<{
        student_inventory_item_id: number;
        quantity: number;
    }>,
    return_date: new Date().toISOString().split('T')[0],
    note: '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

// Modal state
const dialogOpen = ref(false);

// Initialize items with quantities when dialog opens
watch(dialogOpen, (isOpen) => {
    if (isOpen) {
        // Initialize with items that have remaining quantity
        form.value.items = props.studentInventory.items
            .filter(item => item.remaining_quantity > 0)
            .map(item => ({
                student_inventory_item_id: item.id,
                quantity: 0 // Start with 0, user selects
            }));
    }
});

// Computed
const selectedItems = computed(() => {
    return form.value.items.filter(item => item.quantity > 0);
});

const totalReturnQuantity = computed(() => {
    return selectedItems.value.reduce((sum, item) => sum + item.quantity, 0);
});

const totalRefund = computed(() => {
    let total = 0;
    for (const selected of selectedItems.value) {
        const item = props.studentInventory.items.find(i => i.id === selected.student_inventory_item_id);
        if (item) {
            const finalPrice = getFinalPrice(item);
            total += finalPrice * selected.quantity;
        }
    }
    return total.toFixed(2);
});

const getFinalPrice = (item: StudentInventoryItem): number => {
    let price = item.unit_price_snapshot || 0;
    if (item.discount_percentage && item.discount_percentage > 0) {
        price = price - (price * (item.discount_percentage / 100));
    } else if (item.discount_amount && item.discount_amount > 0) {
        price = price - item.discount_amount;
    }
    return price;
};

// Methods
const resetForm = () => {
    form.value = {
        student_inventory_record_id: props.studentInventory.id,
        campus_id: props.studentInventory.campus_id,
        items: props.studentInventory.items
            .filter(item => item.remaining_quantity > 0)
            .map(item => ({
                student_inventory_item_id: item.id,
                quantity: 0
            })),
        return_date: new Date().toISOString().split('T')[0],
        note: '',
    };
    errors.value = {};
};

const closeModal = () => {
    dialogOpen.value = false;
    resetForm();
};

const submitForm = () => {
    // Filter to only items with quantity > 0
    const itemsToReturn = selectedItems.value;
    
    if (itemsToReturn.length === 0) {
        alert.error('Please select at least one item to return.');
        return;
    }

    processing.value = true;
    errors.value = {};

    axios.post('/inventory/student-inventory/return', {
        student_inventory_record_id: form.value.student_inventory_record_id,
        campus_id: form.value.campus_id,
        items: itemsToReturn,
        return_date: form.value.return_date,
        note: form.value.note,
    }, {
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(() => {
        alert.success('Return processed successfully!');
        closeModal();
        emit('saved');
        router.reload({ only: ['studentInventories'] });
    })
    .catch((err) => {
        const response = err.response;
        if (response?.data?.message) {
            alert.error(response.data.message);
        } else if (response?.data?.errors) {
            errors.value = response.data.errors;
            const firstError = Object.values(response.data.errors)[0];
            alert.error(Array.isArray(firstError) ? firstError[0] : firstError);
        } else {
            alert.error('Failed to process return. Please check the errors.');
        }
    })
    .finally(() => {
        processing.value = false;
    });
};
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogTrigger as-child>
            <Button :variant="props.variant" :size="props.size">
                <Icon icon="rotate-ccw" class="mr-1" />
                {{ trigger }}
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <div class="p-2 bg-green-100 dark:bg-green-900/20 rounded-lg">
                        <Icon icon="rotate-ccw" class="h-5 w-5 text-green-600" />
                    </div>
                    Process Return
                </DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submitForm" class="space-y-5">
                <!-- Form Card -->
                <div class="bg-card rounded-lg border p-5 space-y-4">
                    <!-- Student Info -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-sm text-gray-500">Student</div>
                                <div class="font-medium">{{ props.studentInventory.student_name }}</div>
                                <div class="text-xs text-gray-400">{{ props.studentInventory.registration_number }}</div>
                                <div class="text-xs text-gray-400" v-if="props.studentInventory.class_name">
                                    {{ props.studentInventory.class_name }} - {{ props.studentInventory.section_name }}
                                </div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Campus ID</div>
                                <div class="font-medium">{{ props.studentInventory.campus_id }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Selection -->
                    <div class="space-y-3">
                        <Label class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="box" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Select Items to Return
                        </Label>
                        
                        <div class="border rounded-lg divide-y max-h-64 overflow-y-auto">
                            <div 
                                v-for="item in props.studentInventory.items.filter(i => i.remaining_quantity > 0)" 
                                :key="item.id"
                                class="p-3 flex items-center justify-between gap-4 hover:bg-gray-50 dark:hover:bg-gray-800"
                            >
                                <div class="flex items-center gap-3">
                                    <input 
                                        type="checkbox"
                                        :id="'item-' + item.id"
                                        :checked="(form.items.find(f => f.student_inventory_item_id === item.id)?.quantity ?? 0) > 0"
                                        @change="(e) => {
                                            const target = e.target as HTMLInputElement;
                                            const formItem = form.items.find(f => f.student_inventory_item_id === item.id);
                                            if (formItem) {
                                                formItem.quantity = target.checked ? item.remaining_quantity : 0;
                                            }
                                        }"
                                        class="h-4 w-4 rounded border-gray-300"
                                    />
                                    <div>
                                        <Label :for="'item-' + item.id" class="font-medium cursor-pointer">
                                            {{ item.item_name_snapshot }}
                                        </Label>
                                        <div class="text-xs text-gray-500">
                                            Price: {{ formatCurrency(getFinalPrice(item)) }} | 
                                            Remaining: {{ item.remaining_quantity }}
                                        </div>
                                    </div>
                                </div>
                                <div class="w-24">
                                    <Input 
                                        v-model.number="form.items.find(f => f.student_inventory_item_id === item.id)!.quantity"
                                        type="number"
                                        min="0"
                                        :max="item.remaining_quantity"
                                        class="h-8"
                                        :disabled="!form.items.find(f => f.student_inventory_item_id === item.id)?.quantity"
                                    />
                                </div>
                            </div>
                        </div>
                        <p v-if="props.studentInventory.items.every(i => i.remaining_quantity === 0)" class="text-sm text-gray-500">
                            All items have been returned.
                        </p>
                    </div>

                    <!-- Return Date -->
                    <div class="space-y-2">
                        <Label for="return_date" class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="calendar" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Return Date
                        </Label>
                        <Input
                            id="return_date"
                            v-model="form.return_date"
                            type="date"
                            class="h-11"
                        />
                    </div>

                    <!-- Notes -->
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
                            placeholder="Reason for return or additional notes..."
                        ></textarea>
                    </div>

                    <!-- Refund Summary -->
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                            <div>
                                <div class="text-sm text-gray-500">Items to Return</div>
                                <div class="text-lg font-bold text-green-600">{{ selectedItems.length }} items</div>
                            </div>
                            <div class="text-center sm:text-right">
                                <div class="text-sm text-gray-500">Total Quantity</div>
                                <div class="text-xl font-bold text-amber-600">{{ totalReturnQuantity }}</div>
                            </div>
                            <div class="text-center sm:text-right">
                                <div class="text-sm text-gray-500">Total Refund</div>
                                <div class="text-2xl font-bold text-green-600">{{ formatCurrency(totalRefund) }}</div>
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
                    <Button type="submit" :disabled="processing || selectedItems.length === 0" class="h-10">
                        <Icon v-if="processing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                        <Icon v-else icon="check" class="mr-2 h-4 w-4" />
                        Process Return
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
