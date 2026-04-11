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

interface Props {
    categories: Array<{ value: string; label: string }>;
    frequencies: Array<{ value: string; label: string }>;
    nextOrder: number;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Fee Heads', href: '/fee/heads' },
    { title: 'Create', href: '/fee/heads/create' },
];

const form = reactive({
    name: '',
    code: '',
    description: '',
    category: 'tuition',
    default_frequency: 'monthly',
    is_recurring: true,
    is_active: true,
    is_optional: false,
    sort_order: props.nextOrder,
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
    if (!form.category) {
        errors.value.category = 'Category is required';
    }
    if (!form.default_frequency) {
        errors.value.default_frequency = 'Frequency is required';
    }
    
    return Object.keys(errors.value).length === 0;
};

const submitForm = () => {
    if (!validateForm()) return;
    
    isSubmitting.value = true;
    
    router.post(route('fee.heads.store'), {
        ...form,
        sort_order: form.sort_order ? Number(form.sort_order) : 0,
    }, {
        onSuccess: () => {
            isSubmitting.value = false;
            alert.success('Fee head created successfully!');
            router.visit(route('fee.heads.index'));
        },
        onError: (err) => {
            isSubmitting.value = false;
            const firstError = Object.values(err)[0];
            alert.error(firstError);
        },
    });
};

const cancel = () => {
    router.visit(route('fee.heads.index'));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Create Fee Head" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Create Fee Head
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Create a new fee head category
                    </p>
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
                                placeholder="e.g., Tuition Fee"
                                :class="{ 'border-red-500': errors.name }"
                            />
                            <p v-if="errors.name" class="text-sm text-red-500">{{ errors.name }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="code">Code <span class="text-red-500">*</span></Label>
                            <Input
                                id="code"
                                v-model="form.code"
                                placeholder="e.g., TF"
                                :class="{ 'border-red-500': errors.code }"
                            />
                            <p v-if="errors.code" class="text-sm text-red-500">{{ errors.code }}</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <Label for="description">Description</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                            placeholder="Description of this fee head..."
                        ></textarea>
                    </div>

                    <!-- Category & Frequency -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="category">Category <span class="text-red-500">*</span></Label>
                            <select
                                id="category"
                                v-model="form.category"
                                :class="['w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm', { 'border-red-500': errors.category }]"
                            >
                                <option v-for="cat in props.categories" :key="cat.value" :value="cat.value">
                                    {{ cat.label }}
                                </option>
                            </select>
                            <p v-if="errors.category" class="text-sm text-red-500">{{ errors.category }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="frequency">Frequency <span class="text-red-500">*</span></Label>
                            <select
                                id="frequency"
                                v-model="form.default_frequency"
                                :class="['w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm', { 'border-red-500': errors.default_frequency }]"
                            >
                                <option v-for="freq in props.frequencies" :key="freq.value" :value="freq.value">
                                    {{ freq.label }}
                                </option>
                            </select>
                            <p v-if="errors.default_frequency" class="text-sm text-red-500">{{ errors.default_frequency }}</p>
                        </div>
                    </div>

                    <!-- Order & Status -->
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="order">Display Order</Label>
                            <Input
                                id="order"
                                v-model="form.sort_order"
                                type="number"
                                min="1"
                                :placeholder="String(nextOrder)"
                            />
                            <p class="text-xs text-gray-500 mt-1">Next available: {{ nextOrder }}</p>
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

                        <div class="space-y-2">
                            <Label for="is_optional">Is Optional</Label>
                            <select
                                id="is_optional"
                                v-model="form.is_optional"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                            >
                                <option :value="false">Required</option>
                                <option :value="true">Optional</option>
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
                            {{ isSubmitting ? 'Creating...' : 'Create Fee Head' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
