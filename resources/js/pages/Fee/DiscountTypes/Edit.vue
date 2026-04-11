<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { route } from 'ziggy-js';
import { alert } from '@/utils';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
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
    discountType: DiscountType;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Discount Types', href: '/fee/discount-types' },
    { title: 'Edit', href: `/fee/discount-types/${props.discountType.id}/edit` },
];

const form = reactive({
    name: props.discountType.name,
    code: props.discountType.code,
    default_value_type: props.discountType.value_type,
    default_value: String(props.discountType.default_value),
    requires_approval: props.discountType.requires_approval,
    is_active: props.discountType.is_active,
});

const errors = ref<Record<string, string>>({});
const isSubmitting = ref(false);

const validateForm = () => {
    errors.value = {};
    
    if (!form.name.trim()) {
        errors.value.name = 'Name is required';
    }
    if (!form.code.trim()) {
        errors.value.code = 'Code is required';
    }
    if (!form.default_value) {
        errors.value.default_value = 'Default value is required';
    }
    
    return Object.keys(errors.value).length === 0;
};

const submitForm = () => {
    if (!validateForm()) return;
    
    isSubmitting.value = true;
    
    router.put(route('fee.discount-types.update', props.discountType.id), {
        ...form,
        default_value: form.default_value ? Number(form.default_value) : 0,
    }, {
        onSuccess: () => {
            isSubmitting.value = false;
            alert.success('Discount type updated successfully!');
            router.visit(route('fee.discount-types.index'));
        },
        onError: (err) => {
            isSubmitting.value = false;
            const firstError = Object.values(err)[0];
            alert.error(firstError);
        },
    });
};

const cancel = () => {
    router.visit(route('fee.discount-types.index'));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Edit Discount Type" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Edit Discount Type
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Update discount type details
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="cancel">
                        <Icon icon="x" class="mr-2 h-4 w-4" />
                        Cancel
                    </Button>
                    <Button @click="submitForm" :disabled="isSubmitting">
                        <Icon icon="check" class="mr-2 h-4 w-4" />
                        {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </div>

            <!-- Form -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                <form @submit.prevent="submitForm" class="space-y-6">
                    <!-- Name & Code -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="name">Name <span class="text-red-500">*</span></Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="e.g., Sibling Discount"
                                :class="{ 'border-red-500': errors.name }"
                            />
                            <p v-if="errors.name" class="text-sm text-red-500">{{ errors.name }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="code">Code <span class="text-red-500">*</span></Label>
                            <Input
                                id="code"
                                v-model="form.code"
                                placeholder="e.g., SIB_DISC"
                                :class="{ 'border-red-500': errors.code }"
                            />
                            <p v-if="errors.code" class="text-sm text-red-500">{{ errors.code }}</p>
                        </div>
                    </div>

                    <!-- Default Value -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="default_value_type">Value Type <span class="text-red-500">*</span></Label>
                            <select
                                id="default_value_type"
                                v-model="form.default_value_type"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                            >
                                <option value="percent">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (PKR)</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <Label for="default_value">Default Value <span class="text-red-500">*</span></Label>
                            <Input
                                id="default_value"
                                v-model="form.default_value"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                :class="{ 'border-red-500': errors.default_value }"
                            />
                            <p v-if="errors.default_value" class="text-sm text-red-500">{{ errors.default_value }}</p>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="requires_approval">Requires Approval</Label>
                            <select
                                id="requires_approval"
                                v-model="form.requires_approval"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                            >
                                <option :value="false">No</option>
                                <option :value="true">Yes</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <Label for="is_active">Status</Label>
                            <select
                                id="is_active"
                                v-model="form.is_active"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                            >
                                <option :value="true">Active</option>
                                <option :value="false">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <Button type="button" variant="outline" @click="cancel">
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="isSubmitting">
                            <Icon icon="check" class="mr-2 h-4 w-4" />
                            {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
