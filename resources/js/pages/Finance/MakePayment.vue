<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';

interface Purchase {
    id: number;
    purchase_id: string;
    supplier: { name: string };
    total_amount: number;
    paid_amount: number;
}

interface Props {
    campuses?: Array<{ id: number; name: string }>;
    purchases?: Purchase[];
    categories?: Array<{ id: number; name: string }>;
    paymentMethods?: Array<{ id: number; name: string; code: string }>;
}

const props = defineProps<Props>();

// Form data
const form = ref({
    purchase_id: '' as string | number,
    amount: 0,
    payment_method: '',
    category_id: '' as string | number,
    transaction_date: new Date().toISOString().split('T')[0],
    description: '',
});

const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

// Computed
const categories = computed(() => props.categories || []);
const paymentMethods = computed(() => props.paymentMethods || []);

const purchaseOptions = computed(() => (props.purchases || []).map((purchase) => ({
    value: purchase.id,
    label: `${purchase.purchase_id} - ${purchase.supplier?.name} (Due: ${formatMoney(purchase.total_amount - purchase.paid_amount)})`,
})));
const paymentMethodOptions = computed(() => paymentMethods.value.map((method) => ({
    value: method.code,
    label: method.name,
})));
const categoryOptions = computed(() => categories.value.map((cat) => ({
    value: cat.id,
    label: cat.name,
})));

// Breadcrumbs
const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Finance', href: '/finance' },
    { title: 'Make Payment' },
];

// Submit form
const submitForm = async () => {
    errors.value = {};
    isSubmitting.value = true;
    
    try {
        await router.post('/finance/make-payment', {
            purchase_id: form.value.purchase_id,
            amount: form.value.amount,
            payment_method: form.value.payment_method,
            category_id: form.value.category_id,
            transaction_date: form.value.transaction_date,
            description: form.value.description,
        });
    } catch (error: any) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        }
    } finally {
        isSubmitting.value = false;
    }
};

// Format currency
const formatMoney = (amount: number) => {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(amount);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Make Payment" />

        <div class="space-y-6 p-4 md:p-6 max-w-2xl mx-auto">
            <!-- Header -->
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-foreground">
                    Make Payment
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Record a payment made to supplier
                </p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitForm" class="space-y-6 bg-card rounded-lg border border-border p-6">
                <!-- Purchase -->
                <div>
                    <Label for="purchase_id">Select Purchase</Label>
                    <SearchableSelect
                        id="purchase_id"
                        v-model="form.purchase_id"
                        :options="purchaseOptions"
                        placeholder="Select Purchase"
                        class="mt-1"
                    />
                    <p v-if="errors.purchase_id" class="mt-1 text-sm text-destructive">{{ errors.purchase_id[0] }}</p>
                </div>

                <!-- Amount -->
                <div>
                    <Label for="amount">Amount</Label>
                    <Input 
                        id="amount" 
                        v-model.number="form.amount" 
                        type="number" 
                        step="0.01" 
                        min="0"
                        placeholder="Enter amount"
                        required
                    />
                    <p v-if="errors.amount" class="mt-1 text-sm text-destructive">{{ errors.amount[0] }}</p>
                </div>

                <!-- Payment Method -->
                <div>
                    <Label for="payment_method">Payment Method</Label>
                    <SearchableSelect
                        id="payment_method"
                        v-model="form.payment_method"
                        :options="paymentMethodOptions"
                        placeholder="Select Payment Method"
                        class="mt-1"
                    />
                    <p v-if="errors.payment_method" class="mt-1 text-sm text-destructive">{{ errors.payment_method[0] }}</p>
                </div>

                <!-- Category -->
                <div>
                    <Label for="category_id">Category</Label>
                    <SearchableSelect
                        id="category_id"
                        v-model="form.category_id"
                        :options="categoryOptions"
                        placeholder="Select Category"
                        class="mt-1"
                    />
                    <p v-if="errors.category_id" class="mt-1 text-sm text-destructive">{{ errors.category_id[0] }}</p>
                </div>

                <!-- Transaction Date -->
                <div>
                    <Label for="transaction_date">Date</Label>
                    <Input 
                        id="transaction_date" 
                        v-model="form.transaction_date" 
                        type="date" 
                        required
                    />
                    <p v-if="errors.transaction_date" class="mt-1 text-sm text-destructive">{{ errors.transaction_date[0] }}</p>
                </div>

                <!-- Description -->
                <div>
                    <Label for="description">Description (Optional)</Label>
                    <textarea 
                        id="description" 
                        v-model="form.description"
                        class="w-full mt-1 rounded-md border-border"
                        rows="3"
                        placeholder="Add any notes..."
                    ></textarea>
                </div>

                <!-- Buttons -->
                <div class="flex flex-wrap justify-end gap-3">
                    <Button type="button" variant="outline" @click="router.visit('/finance')">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="isSubmitting">
                        {{ isSubmitting ? 'Processing...' : 'Make Payment' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
