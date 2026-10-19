<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref, computed } from 'vue';
import { route } from 'ziggy-js';
import { alert } from '@/utils';
import { useFormValidity } from '@/composables/useFormValidity';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import Icon from '@/components/Icon.vue';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';

interface Props {
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
    description: '',
    default_frequency: 'monthly',
    is_recurring: true,
    is_active: true,
    is_optional: false,
    sort_order: props.nextOrder,
});

const errors = ref<Record<string, string>>({});
const isSubmitting = ref(false);

const frequencyOptions = computed(() => props.frequencies.map((freq) => ({ value: freq.value, label: freq.label })));
const activeOptions = [
    { value: 'true', label: 'Active' },
    { value: 'false', label: 'Inactive' },
];
const optionalOptions = [
    { value: 'false', label: 'Required' },
    { value: 'true', label: 'Optional' },
];
const formActiveModel = computed({
    get: () => String(form.is_active),
    set: (val: string) => { form.is_active = val === 'true'; },
});
const formOptionalModel = computed({
    get: () => String(form.is_optional),
    set: (val: string) => { form.is_optional = val === 'true'; },
});

const { isValid } = useFormValidity(form, ['name', 'default_frequency']);

const validateForm = () => {
    errors.value = {};

    if (!form.name.trim()) {
        errors.value.name = 'Name is required';
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
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">
                        Create Fee Head
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                        Create a new fee head category
                    </p>
                </div>
            </div>

            <!-- Form -->
            <div class="bg-card rounded-lg border border-border p-4 md:p-6">
                <form @submit.prevent="submitForm" class="space-y-6">
                    <!-- Name -->
                    <div class="space-y-2">
                        <Label for="name">Name <span class="text-destructive">*</span></Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="e.g., Tuition Fee"
                            :class="{ 'border-destructive': errors.name }"
                        />
                        <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <Label for="description">Description</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm"
                            placeholder="Description of this fee head..."
                        ></textarea>
                    </div>

                    <!-- Frequency -->
                    <div class="space-y-2">
                        <Label for="frequency">Frequency <span class="text-destructive">*</span></Label>
                        <SearchableSelect
                            id="frequency"
                            v-model="form.default_frequency"
                            :options="frequencyOptions"
                            placeholder="Select frequency"
                            :class="{ 'border-destructive': errors.default_frequency }"
                        />
                        <p v-if="errors.default_frequency" class="text-sm text-destructive">{{ errors.default_frequency }}</p>
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
                            <p class="text-xs text-muted-foreground mt-1">Next available: {{ nextOrder }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="is_active">Status</Label>
                            <SearchableSelect
                                id="is_active"
                                v-model="formActiveModel"
                                :options="activeOptions"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="is_optional">Is Optional</Label>
                            <SearchableSelect
                                id="is_optional"
                                v-model="formOptionalModel"
                                :options="optionalOptions"
                            />
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex flex-wrap justify-end gap-3 pt-4 border-t border-border">
                        <Button type="button" variant="outline" @click="cancel">
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="isSubmitting || !isValid">
                            <Icon icon="check" class="mr-2 h-4 w-4" />
                            {{ isSubmitting ? 'Creating...' : 'Create Fee Head' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
