<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { alert } from '@/utils';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import axios from 'axios';

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
            <Button @click="router.visit(route('fee.discount-types.create'))">
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
                    <button
                        type="button"
                        :class="['px-2 py-1 text-xs font-medium rounded-full cursor-pointer transition-opacity hover:opacity-80', discountType.is_active ? 'bg-success/10 text-success' : 'bg-muted text-foreground']"
                        :title="discountType.is_active ? 'Click to deactivate' : 'Click to activate'"
                        @click="toggleActiveStatus(discountType)"
                    >
                        {{ discountType.is_active ? 'Active' : 'Inactive' }}
                    </button>
                </div>
                <div class="text-sm text-muted-foreground space-y-1 pt-2 border-t border-border">
                    <div>Default: {{ formatValue(discountType.value_type, discountType.default_value) }}</div>
                    <div>Requires Approval: {{ discountType.requires_approval ? 'Yes' : 'No' }}</div>
                </div>
                <div class="flex gap-2 pt-2">
                    <Button variant="outline" size="sm" @click="router.visit(route('fee.discount-types.edit', discountType.id))">
                        <Icon icon="edit" class="mr-1" />Edit
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
                                <button
                                    type="button"
                                    :class="['px-2 py-1 text-xs font-medium rounded-full cursor-pointer transition-opacity hover:opacity-80', discountType.is_active ? 'bg-success/10 text-success' : 'bg-muted text-foreground']"
                                    :title="discountType.is_active ? 'Click to deactivate' : 'Click to activate'"
                                    @click="toggleActiveStatus(discountType)"
                                >
                                    {{ discountType.is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                <div class="flex flex-wrap gap-2 justify-end">
                                    <Button variant="outline" size="sm" @click="router.visit(route('fee.discount-types.edit', discountType.id))">
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
    </div>
</template>
