<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { alert } from '@/utils';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';

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

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Discount Types', href: '/fee/discount-types' },
];

const formatValue = (type: string, value: number) => {
    if (type === 'percent') {
        return `${value}%`;
    }
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'PKR' }).format(value);
};

const deleteDiscountType = (discountType: DiscountType) => {
    if (!confirm(`Are you sure you want to delete "${discountType.name}"? This action cannot be undone.`)) {
        return;
    }
    
    router.delete(route('fee.discount-types.destroy', discountType.id), {
        onSuccess: () => {
            // Successfully deleted
            discountTypesData.value = discountTypesData.value.filter(d => d.id !== discountType.id);
        },
        onError: () => {
            alert.error('Failed to delete discount type. It may be in use.');
        }
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Discount Types" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Discount Types
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
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
                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ discountType.name }}</div>
                            <div class="text-xs text-gray-500">Code: {{ discountType.code }}</div>
                        </div>
                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', discountType.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400']">
                            {{ discountType.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1 pt-2 border-t border-gray-100 dark:border-gray-700">
                        <div>Default: {{ formatValue(discountType.value_type, discountType.default_value) }}</div>
                        <div>Requires Approval: {{ discountType.requires_approval ? 'Yes' : 'No' }}</div>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <Button variant="outline" size="sm" @click="router.visit(route('fee.discount-types.edit', discountType.id))">
                            <Icon icon="edit" class="mr-1" />Edit
                        </Button>
                    </div>
                </div>
                <div v-if="discountTypesData.length === 0" class="text-center py-8 text-gray-500">
                    No discount types found.
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden lg:block overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Sr#
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Code
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Name
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Default Value
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Requires Approval
                                </th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Status
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="(discountType, index) in discountTypesData" :key="discountType.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ index + 1 }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ discountType.code }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ discountType.name }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ formatValue(discountType.value_type, discountType.default_value) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', discountType.requires_approval ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400']">
                                        {{ discountType.requires_approval ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', discountType.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400']">
                                        {{ discountType.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                    <div class="flex gap-2 justify-end">
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
    </AppLayout>
</template>
