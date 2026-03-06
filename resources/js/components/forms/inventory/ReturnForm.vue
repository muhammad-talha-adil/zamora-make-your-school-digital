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
interface StudentInventory {
    id: number;
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

interface Props {
    campuses: Array<{
        id: number;
        name: string;
    }>;
    studentInventories: StudentInventory[];
    trigger?: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Process Return',
    variant: 'default',
    size: 'default',
    studentInventories: () => [],
});

// Emits
const emit = defineEmits<{
    saved: [];
}>();

// Form data
const form = ref({
    campus_id: props.campuses[0]?.id || '',
    student_inventory_id: '',
    quantity: 1,
    return_date: new Date().toISOString().split('T')[0],
    note: '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

// Modal state
const dialogOpen = ref(false);

// Computed
const filteredInventories = computed(() => {
    return props.studentInventories.filter(inv => inv.status !== 'returned');
});

const selectedInventory = computed(() => {
    return props.studentInventories.find(inv => inv.id === parseInt(form.value.student_inventory_id));
});

const maxReturnable = computed(() => {
    return selectedInventory.value?.remaining || 0;
});

const finalPrice = computed(() => {
    if (!selectedInventory.value) return '0.00';
    let price = 1; // Default sale rate placeholder
    if (selectedInventory.value.discount_percentage > 0) {
        price = price - (price * (selectedInventory.value.discount_percentage / 100));
    } else if (selectedInventory.value.discount_amount > 0) {
        price = price - selectedInventory.value.discount_amount;
    }
    return price.toFixed(2);
});

const totalRefund = computed(() => {
    return (parseFloat(finalPrice.value) * form.value.quantity).toFixed(2);
});

// Methods
const resetForm = () => {
    form.value = {
        campus_id: props.campuses[0]?.id || '',
        student_inventory_id: '',
        quantity: 1,
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
    if (form.value.quantity > maxReturnable.value) {
        alert.error(`You can only return up to ${maxReturnable.value} items.`);
        return;
    }

    processing.value = true;
    errors.value = {};

    router.post('/inventory/returns', form.value, {
        onSuccess: () => {
            alert.success('Return processed successfully!');
            closeModal();
            emit('saved');
            router.reload({ only: ['returns', 'studentInventories'] });
        },
        onError: (err) => {
            errors.value = err as Record<string, string>;
            if (Object.keys(errors.value).length > 0) {
                const firstError = Object.values(errors.value)[0];
                alert.error(firstError);
            } else {
                alert.error('Failed to process return. Please check the errors.');
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
                <Icon icon="rotate-ccw" class="mr-1" />
                {{ trigger }}
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <div class="p-2 bg-green-100 dark:bg-green-900/20 rounded-lg">
                        <Icon icon="rotate-ccw" class="h-5 w-5 text-green-600" />
                    </div>
                    Process Inventory Return
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

                    <!-- Student Assignment -->
                    <div class="space-y-2">
                        <Label for="student_inventory_id" class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="user" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Student Assignment <span class="text-red-500">*</span>
                        </Label>
                        <select
                            id="student_inventory_id"
                            v-model="form.student_inventory_id"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm h-11"
                            :class="{ 'border-red-500': errors.student_inventory_id }"
                            required
                        >
                            <option value="">Select Assignment</option>
                            <option v-for="inv in filteredInventories" :key="inv.id" :value="inv.id">
                                {{ inv.student_name }} ({{ inv.registration_number }}) - {{ inv.item_name }} ({{ inv.remaining }} remaining)
                            </option>
                        </select>
                        <InputError :message="errors.student_inventory_id" />
                    </div>

                    <!-- Selected Inventory Info -->
                    <div v-if="selectedInventory" class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-sm text-gray-500">Student</div>
                                <div class="font-medium">{{ selectedInventory.student_name }}</div>
                                <div class="text-xs text-gray-400">{{ selectedInventory.registration_number }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Item</div>
                                <div class="font-medium">{{ selectedInventory.item_name }}</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 md:gap-4 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-center">
                                <div class="text-sm text-gray-500">Total</div>
                                <div class="font-bold">{{ selectedInventory.quantity }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm text-gray-500">Returned</div>
                                <div class="font-bold text-amber-600">{{ selectedInventory.returned_quantity }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm text-gray-500">Remaining</div>
                                <div class="font-bold text-green-600">{{ selectedInventory.remaining }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity -->
                    <div class="space-y-2">
                        <Label for="quantity" class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="hash" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Return Quantity <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            id="quantity"
                            v-model.number="form.quantity"
                            type="number"
                            min="1"
                            :max="maxReturnable"
                            class="h-11"
                            :class="{ 'border-red-500': errors.quantity }"
                            required
                        />
                        <InputError :message="errors.quantity" />
                        <p v-if="maxReturnable > 0" class="text-sm text-gray-500">Maximum: {{ maxReturnable }} items</p>
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

                    <!-- Refund Info -->
                    <div v-if="selectedInventory" class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                            <div>
                                <div class="text-sm text-gray-500">Refund per item</div>
                                <div class="text-lg font-bold text-green-600">{{ formatCurrency(finalPrice) }}</div>
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
                    <Button type="submit" :disabled="processing" class="h-10">
                        <Icon v-if="processing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                        <Icon v-else icon="check" class="mr-2 h-4 w-4" />
                        Process Return
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
