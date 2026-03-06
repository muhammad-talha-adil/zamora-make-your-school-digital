<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { alert } from '@/utils';
import axios from 'axios';

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

// Props
interface Props {
    inventoryType?: {
        id: number;
        name: string;
        campus_id: number;
    };
    campuses?: Array<{
        id: number;
        name: string;
    }>;
    trigger?: string;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    open?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    campuses: () => [],
    trigger: 'Add Type',
    variant: 'default',
    size: 'default',
    open: undefined,
});

// Emits
const emit = defineEmits<{
    saved: [];
    'update:open': [value: boolean];
}>();

// Form data
const form = ref({
    name: '',
    campus_id: null as string | null,
    original_name: '', // Track original name for edit validation
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const nameExistsError = ref('');
const checkingName = ref(false);

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

// Computed campuses list with "All" option
const campusesWithAll = computed(() => [
    { id: 'all', name: 'All Campuses' },
    ...props.campuses
]);

// Track if name was actually changed by user
const isEditing = computed(() => !!props.inventoryType);
const hasNameChanged = computed(() => {
    if (!isEditing.value) return true; // Always validate for new types
    return form.value.name.trim().toLowerCase() !== form.value.original_name.trim().toLowerCase();
});

// Watch for inventoryType prop changes
watch(() => props.inventoryType, (newType) => {
    if (newType) {
        form.value.name = newType.name;
        // Convert null (all campuses) to 'all' for the form
        form.value.campus_id = newType.campus_id === null ? 'all' : newType.campus_id.toString();
        form.value.original_name = newType.name; // Store original name
    }
}, { immediate: true });

// Watch for campuses prop changes
watch(() => props.campuses, (newCampuses) => {
    if (newCampuses.length > 0 && !props.inventoryType && form.value.campus_id === null) {
        // Default to 'all' for new inventory types
        form.value.campus_id = 'all';
    }
}, { immediate: true });

// Watch for name changes to validate duplicates (only if name actually changed)
watch(() => form.value.name, (newName) => {
    if (!hasNameChanged.value) {
        // Name hasn't changed from original - no need to validate
        nameExistsError.value = '';
        return;
    }
    
    if (newName && newName.trim()) {
        checkDuplicateName();
    } else {
        nameExistsError.value = '';
    }
});

// Also watch campus_id changes to re-validate if name was changed
watch(() => form.value.campus_id, () => {
    if (hasNameChanged.value && form.value.name && form.value.name.trim()) {
        checkDuplicateName();
    }
});

const checkDuplicateName = async () => {
    if (!form.value.name || !form.value.campus_id) return;
    
    checkingName.value = true;
    nameExistsError.value = '';
    
    try {
        // For 'all' campuses, send null to backend so it checks all campuses
        const campusIdParam = form.value.campus_id === 'all' ? null : form.value.campus_id;
        
        const response = await axios.get(route('inventory.types.check-name-exists'), {
            params: {
                name: form.value.name,
                campus_id: campusIdParam,
                exclude_id: props.inventoryType?.id || undefined,
                original_name: form.value.original_name // Send original name for backend comparison
            }
        });
        
        if (response.data.exists) {
            // Determine the error message based on the conflict type
            if (response.data.is_all_campus_conflict) {
                nameExistsError.value = 'This type already exists for All Campuses. Cannot create duplicate for specific campus.';
            } else {
                nameExistsError.value = 'This type already exists';
            }
        }
    } catch {
        // Ignore validation errors during checking
    } finally {
        checkingName.value = false;
    }
};

// Methods
const submit = () => {
    processing.value = true;
    errors.value = {};
    nameExistsError.value = '';

    // Final validation before submit
    if (nameExistsError.value) {
        processing.value = false;
        alert.error('Please fix the validation errors before submitting.');
        return;
    }

    if (props.inventoryType) {
        // Update using axios
        axios.put(`/inventory/types/${props.inventoryType.id}`, {
            name: form.value.name,
            campus_id: form.value.campus_id,
        }).then(() => {
            alert.success('Inventory type updated successfully!');
            closeModal();
            emit('saved');
        }).catch((err) => {
            if (err.response?.data?.errors) {
                errors.value = err.response.data.errors;
                const firstError = Object.values(err.response.data.errors)[0];
                alert.error(firstError);
            } else {
                alert.error('Failed to update inventory type. Please try again.');
            }
        }).finally(() => {
            processing.value = false;
        });
    } else {
        // Create using axios
        axios.post('/inventory/types', {
            name: form.value.name,
            campus_id: form.value.campus_id
        }).then(() => {
            alert.success('Inventory type created successfully!');
            closeModal();
            emit('saved');
        }).catch((err) => {
            if (err.response?.data?.errors) {
                errors.value = err.response.data.errors;
                const firstError = Object.values(err.response.data.errors)[0];
                alert.error(firstError);
            } else {
                alert.error('Failed to create inventory type. Please try again.');
            }
        }).finally(() => {
            processing.value = false;
        });
    }
};

const closeModal = () => {
    dialogOpen.value = false;
    resetForm();
};

const resetForm = () => {
    form.value = {
        name: '',
        campus_id: 'all', // Default to 'all campuses' for new types
        original_name: '',
    };
    errors.value = {};
    nameExistsError.value = '';
};
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogTrigger as-child>
            <Button :variant="props.variant" :size="props.size">
                <Icon v-if="props.inventoryType" icon="edit" class="mr-1" />
                <Icon v-else icon="plus" class="mr-1" />
                {{ trigger }}
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <Icon :icon="inventoryType ? 'edit' : 'plus'" class="h-5 w-5 text-primary" />
                    </div>
                    {{ inventoryType ? 'Edit Inventory Type' : 'Add New Inventory Type' }}
                </DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Form Card -->
                <div class="bg-card rounded-lg border p-5 space-y-4">
                    <!-- Inventory Type Name -->
                    <div class="space-y-2">
                        <Label for="name" class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="tag" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Type Name <span class="text-red-500">*</span>
                        </Label>
                        <div class="relative">
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Enter inventory type name"
                                required
                                class="pl-10 h-11"
                                :class="{ 'border-red-500': nameExistsError }"
                            />
                            <Icon icon="tag" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        </div>
                        <InputError :message="errors.name" />
                        <p v-if="nameExistsError" class="text-sm text-red-500 mt-1">
                            {{ nameExistsError }}
                        </p>
                        <p v-else-if="checkingName" class="text-sm text-muted-foreground mt-1">
                            Checking...
                        </p>
                    </div>

                    <!-- Campus -->
                    <div class="space-y-2">
                        <Label for="campus" class="flex items-center gap-2">
                            <div class="p-1 bg-muted rounded">
                                <Icon icon="building" class="h-3.5 w-3.5 text-muted-foreground" />
                            </div>
                            Campus <span class="text-red-500">*</span>
                        </Label>
                        <select
                            id="campus"
                            v-model="form.campus_id"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm h-11"
                            required
                        >
                            <option v-for="campus in campusesWithAll" :key="campus.id" :value="campus.id">
                                {{ campus.name }}
                            </option>
                        </select>
                        <InputError :message="errors.campus_id" />
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
                    <Button type="submit" :disabled="processing || !!nameExistsError" class="h-10">
                        <Icon v-if="processing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                        <Icon v-else :icon="inventoryType ? 'check' : 'plus'" class="mr-2 h-4 w-4" />
                        {{ inventoryType ? 'Update Type' : 'Create Type' }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
