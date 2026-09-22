<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { alert } from '@/utils';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import Icon from '@/components/Icon.vue';
import axios from 'axios';
import StatusToggle from '@/components/tables/StatusToggle.vue';

interface DiscountType {
    id: number;
    name: string;
    code: string;
    value_type: string;
    default_value: number;
    requires_approval: boolean;
    is_active: boolean;
}

interface Props {
    discountTypes: DiscountType[];
}

const props = defineProps<Props>();

const discountTypesData = ref<DiscountType[]>(props.discountTypes);

const showCreateModal = ref(false);
const editingDiscountType = ref<DiscountType | null>(null);
const isSubmitting = ref(false);
const formErrors = ref<Record<string, string>>({});

const getFirstErrorMessage = (value: unknown) => {
    if (Array.isArray(value)) {
        return String(value[0] ?? 'Validation failed.');
    }

    return typeof value === 'string' ? value : 'Validation failed.';
};

const form = ref({
    name: '',
    code: '',
    default_value_type: 'percent',
    default_value: '',
    requires_approval: false,
    is_active: true,
});

const resetForm = () => {
    form.value = {
        name: '',
        code: '',
        default_value_type: 'percent',
        default_value: '',
        requires_approval: false,
        is_active: true,
    };
    formErrors.value = {};
};

const openCreateModal = () => {
    resetForm();
    editingDiscountType.value = null;
    showCreateModal.value = true;
};

const openEditModal = (discountType: DiscountType) => {
    form.value = {
        name: discountType.name,
        code: discountType.code,
        default_value_type: discountType.value_type,
        default_value: String(discountType.default_value),
        requires_approval: discountType.requires_approval,
        is_active: discountType.is_active,
    };
    formErrors.value = {};
    editingDiscountType.value = discountType;
    showCreateModal.value = true;
};

const closeModal = () => {
    showCreateModal.value = false;
    editingDiscountType.value = null;
    resetForm();
};

const validateForm = (): boolean => {
    formErrors.value = {};
    let isValid = true;

    if (!form.value.name.trim()) {
        formErrors.value.name = 'Name is required';
        isValid = false;
    }

    if (!form.value.code.trim()) {
        formErrors.value.code = 'Code is required';
        isValid = false;
    }

    if (!form.value.default_value) {
        formErrors.value.default_value = 'Default value is required';
        isValid = false;
    }

    return isValid;
};

const submitForm = () => {
    if (!validateForm()) {
        return;
    }

    isSubmitting.value = true;

    const data = {
        ...form.value,
        default_value: form.value.default_value ? Number(form.value.default_value) : 0,
    };

    const request = editingDiscountType.value
        ? axios.put(route('fee.discount-types.update', editingDiscountType.value.id), data, {
              headers: { Accept: 'application/json' },
          })
        : axios.post(route('fee.discount-types.store'), data, {
              headers: { Accept: 'application/json' },
          });

    request
        .then(() => {
            alert.success(editingDiscountType.value ? 'Discount type updated successfully!' : 'Discount type created successfully!');
            closeModal();
            fetchDiscountTypes();
        })
        .catch((error) => {
            const errs = error.response?.data?.errors || {};
            formErrors.value = Object.fromEntries(
                Object.entries(errs).map(([key, value]) => [key, getFirstErrorMessage(value)]),
            );
        })
        .finally(() => {
            isSubmitting.value = false;
        });
};

const fetchDiscountTypes = () => {
    // Partial Inertia reload refreshes just this page's `discountTypes` prop
    // in place, without a full navigation — keeps the modal's tab context.
    router.reload({
        only: ['discountTypes'],
        onSuccess: (page) => {
            discountTypesData.value = (page.props.discountTypes as DiscountType[]) ?? discountTypesData.value;
        },
    });
};

const formatValue = (type: string, value: number) => {
    if (type === 'percent') {
        return `${value}%`;
    }
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'PKR' }).format(value);
};

const toggleActiveStatus = (discountType: DiscountType) => {
    const newStatus = !discountType.is_active;
    const actionText = newStatus ? 'activate' : 'deactivate';
    const confirmButtonText = newStatus ? 'Yes, activate it!' : 'Yes, deactivate it!';

    alert
        .confirm(
            `Are you sure you want to ${actionText} "${discountType.name}"?`,
            actionText.charAt(0).toUpperCase() + actionText.slice(1) + ' Discount Type',
            confirmButtonText,
        )
        .then((result) => {
            if (result.isConfirmed) {
                axios.post(route('fee.discount-types.toggle-active', discountType.id), {}, {
                    headers: { Accept: 'application/json' },
                }).then(() => {
                    alert.success(newStatus ? 'Discount type activated successfully.' : 'Discount type deactivated successfully.');
                    const idx = discountTypesData.value.findIndex((d) => d.id === discountType.id);
                    if (idx !== -1) {
                        discountTypesData.value.splice(idx, 1, { ...discountTypesData.value[idx], is_active: newStatus });
                    }
                }).catch(() => {
                    alert.error('Failed to update status. Please try again.');
                });
            }
        });
};

const deleteDiscountType = (discountType: DiscountType) => {
    alert
        .confirm(
            `Are you sure you want to delete "${discountType.name}"? This action cannot be undone.`,
            'Delete Discount Type',
            'Yes, delete it!',
        )
        .then((result) => {
            if (result.isConfirmed) {
                axios.delete(route('fee.discount-types.destroy', discountType.id), {
                    headers: { Accept: 'application/json' },
                }).then(() => {
                    alert.success('Discount type deleted successfully!');
                    discountTypesData.value = discountTypesData.value.filter((d) => d.id !== discountType.id);
                }).catch((error) => {
                    alert.error(error.response?.data?.message || 'Failed to delete discount type. It may be in use.');
                });
            }
        });
};
</script>

<template>
    <div class="space-y-4 md:space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
            <div>
                <h2 class="text-lg font-semibold text-foreground">Discount Types</h2>
                <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                    Manage discount types for student fees
                </p>
            </div>
            <Button @click="openCreateModal">
                <Icon icon="plus" class="mr-2 h-4 w-4" />
                Create Discount Type
            </Button>
        </div>

        <!-- Mobile Card View -->
        <div class="block lg:hidden space-y-3">
            <div
                v-for="discountType in discountTypesData"
                :key="discountType.id"
                class="bg-card rounded-lg border border-border p-4 space-y-2"
            >
                <div class="flex flex-wrap gap-2 justify-between items-start">
                    <div>
                        <div class="font-medium text-foreground">{{ discountType.name }}</div>
                        <div class="text-xs text-muted-foreground">Code: {{ discountType.code }}</div>
                    </div>
                    <StatusToggle :active="discountType.is_active" @toggle="toggleActiveStatus(discountType)" />
                </div>
                <div class="text-sm text-muted-foreground space-y-1 pt-2 border-t border-border">
                    <div>Default: {{ formatValue(discountType.value_type, discountType.default_value) }}</div>
                    <div>Requires Approval: {{ discountType.requires_approval ? 'Yes' : 'No' }}</div>
                </div>
                <div class="flex gap-2 pt-2">
                    <Button variant="outline" size="sm" @click="openEditModal(discountType)">
                        <Icon icon="edit" class="mr-1" />Edit
                    </Button>
                    <Button variant="destructive" size="sm" @click="deleteDiscountType(discountType)" class="flex-1">
                        <Icon icon="trash-2" class="mr-1" />Delete
                    </Button>
                </div>
            </div>
            <div v-if="discountTypesData.length === 0" class="text-center py-8 text-muted-foreground">
                No discount types found.
            </div>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden lg:block overflow-hidden rounded-lg border border-border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Sr#</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Code</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Name</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Default Value</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Requires Approval</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Status</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-muted-foreground uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        <tr v-for="(discountType, index) in discountTypesData" :key="discountType.id" class="transition-colors hover:bg-accent">
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-foreground">{{ index + 1 }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-foreground">{{ discountType.code }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-foreground">{{ discountType.name }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-muted-foreground">
                                    {{ formatValue(discountType.value_type, discountType.default_value) }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="['px-2 py-1 text-xs font-medium rounded-full', discountType.requires_approval ? 'bg-warning/10 text-warning' : 'bg-success/10 text-success']">
                                    {{ discountType.requires_approval ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <StatusToggle :active="discountType.is_active" @toggle="toggleActiveStatus(discountType)" />
                            </td>
                            <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                <div class="flex flex-wrap gap-2 justify-end">
                                    <Button variant="outline" size="sm" @click="openEditModal(discountType)">
                                        <Icon icon="edit" class="mr-1 h-3 w-3" />Edit
                                    </Button>
                                    <Button variant="destructive" size="sm" @click="deleteDiscountType(discountType)">
                                        <Icon icon="trash-2" class="mr-1 h-3 w-3" />Delete
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Dialog v-model:open="showCreateModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editingDiscountType ? 'Edit Discount Type' : 'Create Discount Type' }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submitForm" class="space-y-4">
                    <!-- Name & Code -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="dt-name">Name *</Label>
                            <Input
                                id="dt-name"
                                v-model="form.name"
                                placeholder="e.g., Sibling Discount"
                                :class="formErrors.name ? 'border-destructive' : ''"
                            />
                            <p v-if="formErrors.name" class="text-xs text-destructive">{{ formErrors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="dt-code">Code *</Label>
                            <Input
                                id="dt-code"
                                v-model="form.code"
                                placeholder="e.g., SIB_DISC"
                                :class="formErrors.code ? 'border-destructive' : ''"
                            />
                            <p v-if="formErrors.code" class="text-xs text-destructive">{{ formErrors.code }}</p>
                        </div>
                    </div>

                    <!-- Default Value -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="dt-value-type">Value Type *</Label>
                            <select
                                id="dt-value-type"
                                v-model="form.default_value_type"
                                class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm"
                            >
                                <option value="percent">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (PKR)</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="dt-value">Default Value *</Label>
                            <Input
                                id="dt-value"
                                v-model="form.default_value"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                :class="formErrors.default_value ? 'border-destructive' : ''"
                            />
                            <p v-if="formErrors.default_value" class="text-xs text-destructive">{{ formErrors.default_value }}</p>
                        </div>
                    </div>

                    <!-- Approval & Status -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="dt-requires-approval">Requires Approval</Label>
                            <select
                                id="dt-requires-approval"
                                v-model="form.requires_approval"
                                class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm"
                            >
                                <option :value="false">No</option>
                                <option :value="true">Yes</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="dt-active">Status</Label>
                            <select
                                id="dt-active"
                                v-model="form.is_active"
                                class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm"
                            >
                                <option :value="true">Active</option>
                                <option :value="false">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="closeModal">Cancel</Button>
                        <Button type="submit" :disabled="isSubmitting">
                            {{ isSubmitting ? 'Saving...' : (editingDiscountType ? 'Save Changes' : 'Create') }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
