<script setup lang="ts">
import { alert } from '@/utils';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

// Components
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import { Button, type ButtonVariants } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

// Props
interface Props {
    supplier?: {
        id: number;
        campus_id: number;
        name: string;
        contact_person?: string;
        phone?: string;
        email?: string;
        address?: string;
        tax_number?: string;
        opening_balance?: number;
        is_active?: boolean;
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
    trigger: 'Add Supplier',
    variant: 'default',
    size: 'default',
    open: undefined,
});

// Emits
const emit = defineEmits<{
    saved: [supplier?: { id: number; name: string }];
    'update:open': [value: boolean];
}>();

// Form data
const form = ref({
    campus_id: null as string | null,
    name: '',
    contact_person: '',
    phone: '',
    email: '',
    address: '',
    tax_number: '',
    opening_balance: 0,
    is_active: true,
    original_name: '', // Track original name for edit validation
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const nameChecking = ref(false);
const nameExists = ref(false);
let checkTimeout: ReturnType<typeof setTimeout> | null = null;

// Modal states
const internalOpen = ref(false);
const dialogOpen = computed({
    get: () => (props.open !== undefined ? props.open : internalOpen.value),
    set: (value) => {
        if (props.open !== undefined) {
            emit('update:open', value);
        } else {
            internalOpen.value = value;
        }
    },
});

// Computed campuses list with "All" option
const campusesWithAll = computed(() => [
    { id: 'all', name: 'All Campuses' },
    ...props.campuses,
]);

// Track if we're in edit mode
// const isEditing = computed(() => !!props.supplier);

// Watch for supplier prop changes
watch(
    () => props.supplier,
    (newSupplier) => {
        if (newSupplier) {
            form.value.campus_id = newSupplier.campus_id.toString();
            form.value.name = newSupplier.name;
            form.value.contact_person = newSupplier.contact_person || '';
            form.value.phone = newSupplier.phone || '';
            form.value.email = newSupplier.email || '';
            form.value.address = newSupplier.address || '';
            form.value.tax_number = newSupplier.tax_number || '';
            form.value.opening_balance = newSupplier.opening_balance || 0;
            form.value.is_active = newSupplier.is_active ?? true;
            form.value.original_name = newSupplier.name;
        }
    },
    { immediate: true },
);

// Watch for campuses prop changes
watch(
    () => props.campuses,
    (newCampuses) => {
        if (
            newCampuses.length > 0 &&
            !props.supplier &&
            form.value.campus_id === null
        ) {
            form.value.campus_id = newCampuses[0]?.id.toString() || null;
        }
    },
    { immediate: true },
);

// Watch for name changes to check duplicates
watch(
    () => form.value.name,
    () => {
        checkDuplicateName();
    },
);

// Watch for campus changes
watch(
    () => form.value.campus_id,
    () => {
        // Re-check name when campus changes
        if (form.value.name) {
            checkDuplicateName();
        }
    },
);

// Methods
// Check if name exists (including "All Campuses" conflict check)
const checkDuplicateName = async () => {
    // Clear previous timeout
    if (checkTimeout) {
        clearTimeout(checkTimeout);
        checkTimeout = null;
    }

    // Reset name exists status
    nameExists.value = false;

    // Don't check if name is empty
    if (!form.value.name || form.value.name.trim() === '') {
        return;
    }

    // Don't check if name hasn't changed (for edit mode)
    if (props.supplier && form.value.name === form.value.original_name) {
        return;
    }

    // Debounce the check
    checkTimeout = setTimeout(async () => {
        nameChecking.value = true;

        try {
            const response = await axios.get('/inventory/suppliers/check-name-exists', {
                params: {
                    name: form.value.name,
                    campus_id: form.value.campus_id,
                    exclude_id: props.supplier?.id,
                },
            });

            if (response.data.exists) {
                nameExists.value = true;
                errors.value.name = response.data.message || 'This supplier name already exists';
            } else {
                nameExists.value = false;
                delete errors.value.name;
            }
        } catch (err: any) {
            console.error('Error checking supplier name:', err);
        } finally {
            nameChecking.value = false;
        }
    }, 500);
};

const submit = () => {
    // Check for duplicate name before submitting
    if (nameExists.value) {
        alert.error('This supplier name already exists. Please use a different name.');
        return;
    }

    processing.value = true;
    errors.value = {};

    if (props.supplier) {
        // Update
        axios.put(`/inventory/suppliers/${props.supplier.id}`, {
                campus_id: form.value.campus_id,
                name: form.value.name,
                contact_person: form.value.contact_person,
                phone: form.value.phone,
                email: form.value.email,
                address: form.value.address,
                tax_number: form.value.tax_number,
                opening_balance: form.value.opening_balance,
                is_active: form.value.is_active,
            })
            .then(() => {
                alert.success('Supplier updated successfully!');
                closeModal();
                emit('saved', {
                    id: props.supplier!.id,
                    name: form.value.name,
                });
            })
            .catch((err) => {
                if (err.response?.data?.errors) {
                    errors.value = err.response.data.errors;
                    const errorValues = Object.values(
                        err.response.data.errors,
                    ) as string[];
                    const firstError = errorValues[0] || 'An error occurred';
                    alert.error(firstError);
                } else if (err.response?.data?.message) {
                    alert.error(err.response.data.message);
                } else {
                    alert.error(
                        'Failed to update supplier. Please check the errors.',
                    );
                }
            })
            .finally(() => {
                processing.value = false;
            });
    } else {
        // Create
        axios.post('/inventory/suppliers', {
                campus_id: form.value.campus_id,
                name: form.value.name,
                contact_person: form.value.contact_person,
                phone: form.value.phone,
                email: form.value.email,
                address: form.value.address,
                tax_number: form.value.tax_number,
                opening_balance: form.value.opening_balance,
                is_active: form.value.is_active,
            })
            .then((response) => {
                alert.success('Supplier created successfully!');
                closeModal();
                emit('saved', {
                    id: response.data.supplier?.id || response.data.id,
                    name: form.value.name,
                });
            })
            .catch((err) => {
                if (err.response?.data?.errors) {
                    errors.value = err.response.data.errors;
                    const errorValues = Object.values(
                        err.response.data.errors,
                    ) as string[];
                    const firstError = errorValues[0] || 'An error occurred';
                    alert.error(firstError);
                } else if (err.response?.data?.message) {
                    alert.error(err.response.data.message);
                } else {
                    alert.error(
                        'Failed to create supplier. Please check the errors.',
                    );
                }
            })
            .finally(() => {
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
        campus_id: props.campuses[0]?.id.toString() || null,
        name: '',
        contact_person: '',
        phone: '',
        email: '',
        address: '',
        tax_number: '',
        opening_balance: 0,
        is_active: true,
        original_name: '',
    };
    errors.value = {};
};
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogTrigger as-child>
            <Button :variant="props.variant" :size="props.size">
                <Icon v-if="props.supplier" icon="edit" class="mr-1" />
                <Icon v-else icon="plus" class="mr-1" />
                {{ trigger }}
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <div class="rounded-lg bg-primary/10 p-2">
                        <Icon
                            :icon="supplier ? 'edit' : 'plus'"
                            class="h-5 w-5 text-primary"
                        />
                    </div>
                    {{ supplier ? 'Edit Supplier' : 'Add New Supplier' }}
                </DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Form Card -->
                <div class="space-y-4 rounded-lg border bg-card p-5">
                    <!-- Supplier Name -->
                    <div class="space-y-2">
                        <Label for="name" class="flex items-center gap-2">
                            <div class="rounded bg-muted p-1">
                                <Icon
                                    icon="user"
                                    class="h-3.5 w-3.5 text-muted-foreground"
                                />
                            </div>
                            Supplier Name <span class="text-red-500">*</span>
                        </Label>
                        <div class="relative">
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Enter supplier name"
                                required
                                class="h-11 pl-10"
                                :class="{
                                    'border-red-500 focus:border-red-500': nameExists,
                                }"
                            />
                            <Icon
                                icon="user"
                                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Icon
                                v-if="nameChecking"
                                icon="loader"
                                class="absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 animate-spin text-muted-foreground"
                            />
                            <Icon
                                v-else-if="nameExists"
                                icon="alert-circle"
                                class="absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-red-500"
                            />
                        </div>
                        <InputError :message="errors.name" />
                    </div>

                    <!-- Campus -->
                    <div class="space-y-2">
                        <Label for="campus" class="flex items-center gap-2">
                            <div class="rounded bg-muted p-1">
                                <Icon
                                    icon="building"
                                    class="h-3.5 w-3.5 text-muted-foreground"
                                />
                            </div>
                            Campus <span class="text-red-500">*</span>
                        </Label>
                        <select
                            id="campus"
                            v-model="form.campus_id"
                            class="h-11 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            required
                        >
                            <option
                                v-for="campus in campusesWithAll"
                                :key="campus.id"
                                :value="campus.id"
                            >
                                {{ campus.name }}
                            </option>
                        </select>
                        <InputError :message="errors.campus_id" />
                    </div>

                    <!-- Contact Person & Phone in one row -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Contact Person -->
                        <div class="space-y-2">
                            <Label
                                for="contact_person"
                                class="flex items-center gap-2"
                            >
                                <div class="rounded bg-muted p-1">
                                    <Icon
                                        icon="user-check"
                                        class="h-3.5 w-3.5 text-muted-foreground"
                                    />
                                </div>
                                Contact Person
                            </Label>
                            <div class="relative">
                                <Input
                                    id="contact_person"
                                    v-model="form.contact_person"
                                    placeholder="Enter contact person name"
                                    class="h-11 pl-10"
                                />
                                <Icon
                                    icon="user-check"
                                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                                />
                            </div>
                            <InputError :message="errors.contact_person" />
                        </div>

                        <!-- Phone -->
                        <div class="space-y-2">
                            <Label for="phone" class="flex items-center gap-2">
                                <div class="rounded bg-muted p-1">
                                    <Icon
                                        icon="phone"
                                        class="h-3.5 w-3.5 text-muted-foreground"
                                    />
                                </div>
                                Phone
                            </Label>
                            <div class="relative">
                                <Input
                                    id="phone"
                                    v-model="form.phone"
                                    placeholder="Enter phone number"
                                    class="h-11 pl-10"
                                />
                                <Icon
                                    icon="phone"
                                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                                />
                            </div>
                            <InputError :message="errors.phone" />
                        </div>
                    </div>

                    <!-- Email & Address in one row -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Email -->
                        <div class="space-y-2">
                            <Label for="email" class="flex items-center gap-2">
                                <div class="rounded bg-muted p-1">
                                    <Icon
                                        icon="mail"
                                        class="h-3.5 w-3.5 text-muted-foreground"
                                    />
                                </div>
                                Email
                            </Label>
                            <div class="relative">
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="Enter email address"
                                    class="h-11 pl-10"
                                />
                                <Icon
                                    icon="mail"
                                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                                />
                            </div>
                            <InputError :message="errors.email" />
                        </div>

                        <!-- Address -->
                        <div class="space-y-2">
                            <Label
                                for="address"
                                class="flex items-center gap-2"
                            >
                                <div class="rounded bg-muted p-1">
                                    <Icon
                                        icon="map-pin"
                                        class="h-3.5 w-3.5 text-muted-foreground"
                                    />
                                </div>
                                Address
                            </Label>
                            <div class="relative">
                                <Input
                                    id="address"
                                    v-model="form.address"
                                    placeholder="Enter address"
                                    class="h-11 pl-10"
                                />
                                <Icon
                                    icon="map-pin"
                                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                                />
                            </div>
                            <InputError :message="errors.address" />
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-2">
                    <DialogClose as-child>
                        <Button
                            type="button"
                            variant="outline"
                            @click="resetForm"
                            class="h-10"
                        >
                            <Icon icon="x" class="mr-2 h-4 w-4" />
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button type="submit" :disabled="processing" class="h-10">
                        <Icon
                            v-if="processing"
                            icon="loader"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        <Icon
                            v-else
                            :icon="supplier ? 'check' : 'plus'"
                            class="mr-2 h-4 w-4"
                        />
                        {{ supplier ? 'Update Supplier' : 'Create Supplier' }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
