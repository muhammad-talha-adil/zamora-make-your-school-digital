<script setup lang="ts">
import axios from 'axios';
import { alert } from '@/utils';
import { ref, watch, computed } from 'vue';

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
    campus_id: number;
    inventory_type_id: number;
    name: string;
    description?: string;
    is_active?: boolean;
}

interface Props {
    inventoryItem?: Partial<InventoryItem> | null;
    campuses: Array<{
        id: number;
        name: string;
    }>;
    inventoryTypes: Array<{
        id: number;
        name: string;
    }>;
    trigger?: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    open?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    trigger: 'Add Item',
    variant: 'default',
    size: 'default',
    campuses: () => [],
    inventoryTypes: () => [],
    open: undefined,
    inventoryItem: undefined,
});

// Emits
const emit = defineEmits<{
    saved: [];
    'update:open': [value: boolean];
}>();

// Form data
const form = ref<{
    campus_id: string | number;
    inventory_type_id: string | number;
    name: string;
    description: string;
    is_active: boolean;
}>({
    campus_id: props.inventoryItem?.campus_id || props.campuses[0]?.id || '',
    inventory_type_id: props.inventoryItem?.inventory_type_id || '',
    name: props.inventoryItem?.name || '',
    description: props.inventoryItem?.description || '',
    is_active: props.inventoryItem?.is_active ?? true,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

// Computed
const isEditing = computed(() => {
    return props.inventoryItem && props.inventoryItem.id !== undefined;
});

// Filter inventory types based on selected campus
const filteredInventoryTypes = computed(() => {
    if (!form.value.campus_id) {
        return props.inventoryTypes;
    }
    return props.inventoryTypes.filter((type: any) => {
        // Show types that are for the selected campus or for all campuses (null)
        return type.campus_id === form.value.campus_id || type.campus_id === null;
    });
});

// Watch for campus changes to reset inventory type
watch(() => form.value.campus_id, () => {
    if (form.value.inventory_type_id) {
        // Check if the selected type is still valid for the new campus
        const validType = filteredInventoryTypes.value.find((t: any) => t.id === form.value.inventory_type_id);
        if (!validType) {
            form.value.inventory_type_id = '';
        }
    }
});

// Modal states
const internalOpen = ref(false);
const dialogOpen = computed({
    get: () => props.open !== undefined ? props.open : internalOpen.value,
    set: (value) => {
        if (props.open !== undefined) {
            emit('update:open', value);
        } else {
            internalOpen.value = value;
        }
    }
});

// Methods
const resetForm = () => {
    form.value = {
        campus_id: props.campuses[0]?.id || '',
        inventory_type_id: '',
        name: '',
        description: '',
        is_active: true,
    };
    errors.value = {};
};

const closeModal = () => {
    dialogOpen.value = false;
    resetForm();
};

const submitForm = () => {
    processing.value = true;
    errors.value = {};

    const formData = {
        campus_id: form.value.campus_id,
        inventory_type_id: form.value.inventory_type_id,
        name: form.value.name,
        description: form.value.description,
        is_active: form.value.is_active ? 1 : 0,
    };

    if (isEditing.value && props.inventoryItem?.id) {
        // Update
        axios.put(`/inventory/items/${props.inventoryItem.id}`, formData)
            .then(() => {
                alert.success('Inventory item updated successfully!');
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
                    alert.error('Failed to update item. Please check the errors.');
                }
            })
            .finally(() => {
                processing.value = false;
            });
    } else {
        // Create
        axios.post('/inventory/items', formData)
            .then(() => {
                alert.success('Inventory item created successfully!');
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
                    alert.error('Failed to create item. Please check the errors.');
                }
            })
            .finally(() => {
                processing.value = false;
            });
    }
};
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogTrigger as-child>
            <Button :variant="props.variant" :size="props.size">
                <Icon v-if="isEditing" icon="edit" class="mr-1" />
                <Icon v-else icon="plus" class="mr-1" />
                {{ trigger }}
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <Icon :icon="isEditing ? 'edit' : 'plus'" class="h-5 w-5 text-primary" />
                    </div>
                    {{ isEditing ? 'Edit Inventory Item' : 'Add New Inventory Item' }}
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

                    <!-- Inventory Type -->
                    <div class="space-y-2">
                        <Label for="inventory_type_id" class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="tag" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Inventory Type <span class="text-red-500">*</span>
                        </Label>
                        <select
                            id="inventory_type_id"
                            v-model="form.inventory_type_id"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm h-11"
                            :class="{ 'border-red-500': errors.inventory_type_id }"
                            :disabled="!form.campus_id"
                            required
                        >
                            <option value="">{{ form.campus_id ? 'Select Type' : 'Select Campus First' }}</option>
                            <option v-for="type in filteredInventoryTypes" :key="type.id" :value="type.id">
                                {{ type.name }}<span v-if="type.campus_id === null" class="text-xs text-gray-400"> (All Campuses)</span>
                            </option>
                        </select>
                        <InputError :message="errors.inventory_type_id" />
                    </div>

                    <!-- Item Name -->
                    <div class="space-y-2">
                        <Label for="name" class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="box" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Item Name <span class="text-red-500">*</span>
                        </Label>
                        <div class="relative">
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="e.g., School Uniform - Size 28"
                                class="pl-10 h-11"
                                :class="{ 'border-red-500': errors.name }"
                                required
                            />
                            <Icon icon="box" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        </div>
                        <InputError :message="errors.name" />
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <Label for="description" class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="align-left" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Description <span class="text-sm text-muted-foreground font-normal">(Optional)</span>
                        </Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 min-h-20"
                            placeholder="Item description..."
                        ></textarea>
                    </div>

                    <!-- Active Checkbox -->
                    <div v-if="isEditing" class="flex items-center space-x-2">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-gray-300 text-primary focus:ring-primary w-5 h-5"
                        />
                        <Label for="is_active">Active</Label>
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
                        <Icon v-else :icon="isEditing ? 'check' : 'plus'" class="mr-2 h-4 w-4" />
                        {{ isEditing ? 'Update Item' : 'Create Item' }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
