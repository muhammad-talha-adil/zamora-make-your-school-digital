<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';

interface PaymentMethod {
    id: number;
    name: string;
    code: string;
    is_active: boolean;
}

interface Props {
    paymentMethods?: PaymentMethod[];
}

const props = defineProps<Props>();

// Form state
const showForm = ref(false);
const editingMethod = ref<PaymentMethod | null>(null);
const form = ref({
    name: '',
    code: '',
    is_active: true,
});

const paymentMethods = computed(() => props.paymentMethods || []);

// Breadcrumbs
const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Finance', href: '/finance' },
    { title: 'Payment Methods' },
];

// Open form for new method
const openForm = () => {
    editingMethod.value = null;
    form.value = { name: '', code: '', is_active: true };
    showForm.value = true;
};

// Open form for editing
const editMethod = (method: PaymentMethod) => {
    editingMethod.value = method;
    form.value = { 
        name: method.name, 
        code: method.code, 
        is_active: method.is_active 
    };
    showForm.value = true;
};

// Submit form
const submitForm = async () => {
    if (editingMethod.value) {
        await router.put(`/finance/payment-methods/${editingMethod.value.id}`, form.value);
    } else {
        await router.post('/finance/payment-methods', form.value);
    }
    showForm.value = false;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Payment Methods" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
                        Payment Methods
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Manage payment methods for transactions
                    </p>
                </div>
                <Button @click="openForm()">Add Payment Method</Button>
            </div>

            <!-- Payment Methods Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div 
                    v-for="method in paymentMethods" 
                    :key="method.id"
                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold">{{ method.name }}</h3>
                            <p class="text-sm text-gray-500">{{ method.code }}</p>
                        </div>
                        <div class="flex gap-2">
                            <button @click="editMethod(method)" class="text-blue-600 hover:text-blue-800 text-sm">Edit</button>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span :class="method.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="px-2 py-1 text-xs rounded-full">
                            {{ method.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
                <p v-if="paymentMethods.length === 0" class="col-span-full text-center text-gray-500">No payment methods found</p>
            </div>

            <!-- Add/Edit Form Modal -->
            <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md">
                    <h3 class="text-lg font-semibold mb-4">
                        {{ editingMethod ? 'Edit Payment Method' : 'Add Payment Method' }}
                    </h3>
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div>
                            <Label>Name</Label>
                            <Input v-model="form.name" required />
                        </div>
                        <div>
                            <Label>Code</Label>
                            <Input v-model="form.code" required placeholder="e.g., cash, bank_transfer" />
                        </div>
                        <div class="flex gap-2">
                            <Button type="submit">Save</Button>
                            <Button type="button" variant="outline" @click="showForm = false">Cancel</Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
