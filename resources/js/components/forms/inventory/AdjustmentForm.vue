<script setup lang="ts">
import axios from 'axios';
import { alert } from '@/utils';
import { ref, computed, reactive } from 'vue';

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
interface InventoryItem {
    id: number;
    campus_id?: number;
    name?: string;
    current_stock?: number;
}

interface Props {
    campuses: Array<{
        id: number;
        name: string;
    }>;
    inventoryItems: InventoryItem[];
    trigger?: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Add Adjustment',
    variant: 'default',
    size: 'default',
    inventoryItems: () => [],
});

// Emits
const emit = defineEmits<{
    saved: [];
}>();

// Form data
const form = reactive({
    campus_id: props.campuses[0]?.id || '',
    inventory_item_id: '',
    type: 'add' as 'add' | 'subtract' | 'set',
    quantity: 0,
    reason: '',
    reference_number: '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

// Modal state
const dialogOpen = ref(false);

// Computed
const filteredItems = computed(() => {
    if (!form.campus_id) return props.inventoryItems;
    return props.inventoryItems.filter(item => item.campus_id === form.campus_id);
});

const selectedItem = computed(() => {
    if (!form.inventory_item_id) return undefined;
    return props.inventoryItems.find(item => item.id === Number(form.inventory_item_id));
});

// Methods
const onCampusChange = () => {
    form.inventory_item_id = '';
};

const resetForm = () => {
    form.campus_id = props.campuses[0]?.id || '';
    form.inventory_item_id = '';
    form.type = 'add';
    form.quantity = 0;
    form.reason = '';
    form.reference_number = '';
    errors.value = {};
};

const closeModal = () => {
    dialogOpen.value = false;
    resetForm();
};

const submitForm = () => {
    processing.value = true;
    errors.value = {};

    axios.post('/inventory/adjustments', form)
        .then(() => {
            alert.success('Stock adjustment created successfully!');
            closeModal();
            emit('saved');
        })
        .catch((err) => {
            if (err.response?.data?.errors) {
                errors.value = err.response.data.errors;
                const firstError = Object.values(err.response.data.errors)[0];
                alert.error(firstError);
            } else if (err.response?.data?.message) {
                alert.error(err.response.data.message);
            } else {
                alert.error('Failed to create adjustment. Please check the errors.');
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
                <Icon icon="sliders" class="mr-1" />
                {{ trigger }}
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <Icon icon="sliders" class="h-5 w-5 text-primary" />
                    </div>
                    New Stock Adjustment
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
                            @change="onCampusChange"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm h-11"
                            required
                        >
                            <option value="">Select Campus</option>
                            <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                                {{ campus.name }}
                            </option>
                        </select>
                        <InputError :message="errors.campus_id" />
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
                            required
                        >
                            <option value="">Select Item</option>
                            <option v-for="item in filteredItems" :key="item.id" :value="item.id">
                                {{ item.name }} (Current Stock: {{ item.current_stock }})
                            </option>
                        </select>
                        <InputError :message="errors.inventory_item_id" />
                    </div>

                    <!-- Adjustment Type -->
                    <div class="space-y-2">
                        <Label class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="git-branch" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Adjustment Type <span class="text-red-500">*</span>
                        </Label>
                        <div class="grid grid-cols-3 gap-2">
                            <label
                                :class="[
                                    'border rounded-lg p-3 cursor-pointer transition-colors text-center',
                                    form.type === 'add' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800'
                                ]"
                            >
                                <input type="radio" v-model="form.type" value="add" class="sr-only" />
                                <div class="text-xl mb-1">➕</div>
                                <div class="font-medium text-sm">Add Stock</div>
                            </label>
                            <label
                                :class="[
                                    'border rounded-lg p-3 cursor-pointer transition-colors text-center',
                                    form.type === 'subtract' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800'
                                ]"
                            >
                                <input type="radio" v-model="form.type" value="subtract" class="sr-only" />
                                <div class="text-xl mb-1">➖</div>
                                <div class="font-medium text-sm">Subtract</div>
                            </label>
                            <label
                                :class="[
                                    'border rounded-lg p-3 cursor-pointer transition-colors text-center',
                                    form.type === 'set' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800'
                                ]"
                            >
                                <input type="radio" v-model="form.type" value="set" class="sr-only" />
                                <div class="text-xl mb-1">🎯</div>
                                <div class="font-medium text-sm">Set</div>
                            </label>
                        </div>
                    </div>

                    <!-- Quantity -->
                    <div class="space-y-2">
                        <Label :for="`quantity-${form.type}`" class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="hash" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            {{ form.type === 'set' ? 'New Quantity' : 'Quantity' }} <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            :id="`quantity-${form.type}`"
                            v-model.number="form.quantity"
                            type="number"
                            min="0"
                            class="h-11"
                            :class="{ 'border-red-500': errors.quantity }"
                            required
                        />
                        <InputError :message="errors.quantity" />

                        <!-- Preview -->
                        <div v-if="selectedItem && form.quantity > 0" class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <span v-if="form.type === 'add'">
                                    Current: <strong>{{ selectedItem.current_stock }}</strong> → 
                                    After: <strong class="text-green-600">{{ selectedItem.current_stock + form.quantity }}</strong>
                                </span>
                                <span v-else-if="form.type === 'subtract'">
                                    Current: <strong>{{ selectedItem.current_stock }}</strong> → 
                                    After: <strong class="text-red-600">{{ Math.max(0, selectedItem.current_stock - form.quantity) }}</strong>
                                </span>
                                <span v-else>
                                    Current: <strong>{{ selectedItem.current_stock }}</strong> → 
                                    After: <strong class="text-blue-600">{{ form.quantity }}</strong>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="space-y-2">
                        <Label for="reason" class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="align-left" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Reason <span class="text-red-500">*</span>
                        </Label>
                        <textarea
                            id="reason"
                            v-model="form.reason"
                            rows="3"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 min-h-20"
                            :class="{ 'border-red-500': errors.reason }"
                            placeholder="Provide a detailed reason for this adjustment..."
                            required
                        ></textarea>
                        <InputError :message="errors.reason" />
                    </div>

                    <!-- Reference Number -->
                    <div class="space-y-2">
                        <Label for="reference_number" class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="hash" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Reference Number <span class="text-sm text-muted-foreground font-normal">(Optional)</span>
                        </Label>
                        <Input
                            id="reference_number"
                            v-model="form.reference_number"
                            type="text"
                            class="h-11"
                            placeholder="e.g., Stock Count Report #123"
                        />
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
                        Create Adjustment
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
