Fee<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';
import ComboboxInput from '@/components/ui/combobox/ComboboxInput.vue';

interface Student {
    id: number;
    name: string;
    registration_number: string;
    display_text?: string;
}

interface Voucher {
    id: number;
    voucher_no: string;
    voucher_month: string;
    voucher_year: number;
    net_amount: number;
    paid_amount: number;
    balance_amount: number;
    status: string;
    balance_by_head: Record<string, number>;
}

interface Props {
    campuses?: Array<{ id: number; name: string }>;
    classes?: Array<{ id: number; name: string }>;
    sections?: Array<{ id: number; name: string; class_id: number }>;
    categories?: Array<{ id: number; name: string; code: string }>;
    paymentMethods?: Array<{ id: number; name: string; code: string }>;
}

const props = defineProps<Props>();

// Reactive state
const selectedStudent = ref<Student | null>(null);
const studentVouchers = ref<Voucher[]>([]);
const balanceBreakdown = ref<Record<string, number>>({});
const showStudentSearch = ref(false);
const isLoadingDetails = ref(false);

// Form data
const form = ref({
    payment_type: 'student',
    campus_id: '' as string | number,
    class_id: '' as string | number,
    section_id: '' as string | number,
    student_id: '' as string | number,
    voucher_id: '' as string | number,
    amount: 0,
    payment_method: '',
    category_id: '' as string | number,
    transaction_date: new Date().toISOString().split('T')[0],
    description: '',
    payer_name: '',
});

const isSubmitting = ref(false);
const errors = ref<Record<string, string[]>>({});

// Computed
const categories = computed(() => props.categories || []);
const paymentMethods = computed(() => props.paymentMethods || []);

// Filtered sections based on selected class
const filteredSections = computed(() => {
    if (!form.value.class_id) return [];
    return props.sections?.filter(s => s.class_id === form.value.class_id) || [];
});

// Calculate total previous balance
const totalPreviousBalance = computed(() => {
    return Object.values(balanceBreakdown.value).reduce((sum, val) => sum + val, 0);
});

// Breadcrumbs
const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Finance', href: '/finance' },
    { title: 'Receive Payment', href: '/finance/receive-payment' },
];

// Build search URL for ComboboxInput
const studentSearchUrl = computed(() => {
    if (!form.value.campus_id || !form.value.class_id) return '';
    let url = `/finance/receive-payment/students?campus_id=${form.value.campus_id}&class_id=${form.value.class_id}`;
    if (form.value.section_id) {
        url += `&section_id=${form.value.section_id}`;
    }
    return url;
});

// Handle student selection from ComboboxInput
const handleStudentSelect = async (studentId: number | string | null) => {
    form.value.student_id = studentId as number;
    if (!studentId) {
        selectedStudent.value = null;
        studentVouchers.value = [];
        balanceBreakdown.value = {};
        return;
    }
    
    await fetchStudentDetails();
};

// Fetch student details when student is selected
const fetchStudentDetails = async () => {
    if (!form.value.student_id) {
        selectedStudent.value = null;
        studentVouchers.value = [];
        balanceBreakdown.value = {};
        return;
    }

    isLoadingDetails.value = true;
    try {
        const response = await fetch(
            `/finance/receive-payment/student-details?student_id=${form.value.student_id}`,
            {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }
        );
        const data = await response.json();
        selectedStudent.value = data.student;
        studentVouchers.value = data.vouchers;
        balanceBreakdown.value = data.previousBalances;
    } catch (error) {
        console.error('Failed to fetch student details:', error);
    } finally {
        isLoadingDetails.value = false;
    }
};

// Reset student-related data when filters change
const resetStudentData = () => {
    form.value.student_id = '';
    form.value.voucher_id = '';
    selectedStudent.value = null;
    studentVouchers.value = [];
    balanceBreakdown.value = {};
    showStudentSearch.value = false;
};

// Watch for filter changes
watch(() => form.value.campus_id, () => {
    form.value.class_id = '';
    form.value.section_id = '';
    resetStudentData();
});

watch(() => form.value.class_id, () => {
    form.value.section_id = '';
    resetStudentData();
});

watch(() => form.value.section_id, () => {
    resetStudentData();
});

// Submit form
const submitForm = async () => {
    errors.value = {};
    isSubmitting.value = true;
    
    try {
        const formData = new FormData();
        formData.append('payment_type', String(form.value.payment_type));
        formData.append('campus_id', String(form.value.campus_id));
        formData.append('amount', String(form.value.amount));
        formData.append('payment_method', String(form.value.payment_method));
        formData.append('transaction_date', form.value.transaction_date);
        if (form.value.description) {
            formData.append('description', form.value.description);
        }

        if (form.value.payment_type === 'student') {
            formData.append('student_id', String(form.value.student_id));
            formData.append('voucher_id', String(form.value.voucher_id));
        } else {
            formData.append('category_id', String(form.value.category_id));
            formData.append('payer_name', form.value.payer_name);
        }

        await router.post('/finance/receive-payment', formData);
    } catch (error: unknown) {
        if (error && typeof error === 'object' && 'response' in error) {
            const err = error as { response?: { data?: { errors?: Record<string, string[]> } } };
            if (err.response?.data?.errors) {
                errors.value = err.response.data.errors;
            }
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
        <Head title="Receive Payment" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                    Receive Payment
                </h1>
                <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                    Record a payment received from student or other sources
                </p>
            </div>

            <!-- Form -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <form @submit.prevent="submitForm" class="space-y-6">
                    
                    <!-- Payment Type Radio Buttons -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <Label class="mb-2 block">Payment From *</Label>
                            <div class="flex gap-6 mt-2">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input 
                                        type="radio" 
                                        v-model="form.payment_type" 
                                        value="student"
                                        class="w-4 h-4 text-blue-600"
                                    />
                                    <span class="text-sm font-medium">Student</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input 
                                        type="radio" 
                                        v-model="form.payment_type" 
                                        value="other"
                                        class="w-4 h-4 text-blue-600"
                                    />
                                    <span class="text-sm font-medium">Other</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Campus, Class, Section Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <Label for="campus_id">Campus *</Label>
                            <select 
                                id="campus_id" 
                                v-model="form.campus_id"
                                required
                                class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                            >
                                <option value="">Select Campus</option>
                                <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                                    {{ campus.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <Label for="class_id">Class *</Label>
                            <select 
                                id="class_id" 
                                v-model="form.class_id"
                                required
                                class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                                :disabled="!form.campus_id"
                            >
                                <option value="">Select Class</option>
                                <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">
                                    {{ cls.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <Label for="section_id">Section</Label>
                            <select 
                                id="section_id" 
                                v-model="form.section_id"
                                class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                                :disabled="!form.class_id"
                            >
                                <option value="">All Sections</option>
                                <option v-for="section in filteredSections" :key="section.id" :value="section.id">
                                    {{ section.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Load Students Button / Student Search -->
                    <div v-if="form.payment_type === 'student' && form.class_id">
                        <div v-if="!showStudentSearch">
                            <Button type="button" @click="showStudentSearch = true">
                                <Icon icon="search" class="mr-2 h-4 w-4" />
                                Load Students
                            </Button>
                        </div>

                        <!-- Student Search Combobox -->
                        <div v-if="showStudentSearch">
                            <Label>Search Student *</Label>
                            <div class="mt-1">
                                <ComboboxInput
                                    :model-value="form.student_id"
                                    :search-url="studentSearchUrl"
                                    placeholder="Search students by name or registration number..."
                                    display-key="display_text"
                                    @update:model-value="handleStudentSelect"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Loading indicator -->
                    <div v-if="isLoadingDetails" class="flex items-center justify-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <Icon icon="loader" class="h-5 w-5 animate-spin text-gray-400 mr-2" />
                        <span class="text-gray-500 dark:text-gray-400">Loading student details...</span>
                    </div>

                    <!-- Previous Balance Breakdown -->
                    <div v-if="form.payment_type === 'student' && form.student_id && !isLoadingDetails && Object.keys(balanceBreakdown).length > 0" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <Icon icon="alert-triangle" class="h-5 w-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" />
                            <div class="flex-1">
                                <p class="font-medium text-yellow-800 dark:text-yellow-200 mb-2">Previous Outstanding Balances</p>
                                <div class="space-y-1">
                                    <div v-for="(amount, head) in balanceBreakdown" :key="head" class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">{{ head }}:</span>
                                        <span class="font-medium text-red-600">{{ formatMoney(amount) }}</span>
                                    </div>
                                </div>
                                <div class="mt-3 pt-2 border-t border-yellow-300 dark:border-yellow-700 flex justify-between font-medium">
                                    <span>Total Outstanding:</span>
                                    <span class="text-red-600">{{ formatMoney(totalPreviousBalance) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Voucher Selection -->
                    <div v-if="form.payment_type === 'student' && form.student_id && !isLoadingDetails && studentVouchers.length > 0">
                        <Label for="voucher_id">Select Voucher to Pay *</Label>
                        <select 
                            id="voucher_id" 
                            v-model="form.voucher_id"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                        >
                            <option value="">Select Voucher</option>
                            <option v-for="voucher in studentVouchers" :key="voucher.id" :value="voucher.id">
                                {{ voucher.voucher_no }} - {{ voucher.voucher_month }} {{ voucher.voucher_year }} 
                                (Balance: {{ formatMoney(voucher.balance_amount) }})
                            </option>
                        </select>
                        <p v-if="errors.voucher_id && errors.voucher_id[0]" class="mt-1 text-sm text-red-600">{{ errors.voucher_id[0] }}</p>
                    </div>

                    <!-- Payer Name (for Other payment type) -->
                    <div v-if="form.payment_type === 'other'">
                        <Label for="payer_name">Payer Name *</Label>
                        <input 
                            id="payer_name" 
                            v-model="form.payer_name" 
                            type="text"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                            placeholder="Enter payer name"
                        />
                    </div>

                    <!-- Amount -->
                    <div>
                        <Label for="amount">Amount (Rs.) *</Label>
                        <input 
                            id="amount" 
                            v-model.number="form.amount" 
                            type="number" 
                            step="0.01" 
                            min="0"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                            placeholder="Enter amount"
                        />
                        <p v-if="errors.amount && errors.amount[0]" class="mt-1 text-sm text-red-600">{{ errors.amount[0] }}</p>
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <Label for="payment_method">Payment Method *</Label>
                        <select 
                            id="payment_method" 
                            v-model="form.payment_method"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                        >
                            <option value="">Select Payment Method</option>
                            <option v-for="method in paymentMethods" :key="method.id" :value="method.code">
                                {{ method.name }}
                            </option>
                        </select>
                        <p v-if="errors.payment_method && errors.payment_method[0]" class="mt-1 text-sm text-red-600">{{ errors.payment_method[0] }}</p>
                    </div>

                    <!-- Category (only for Other payment type) -->
                    <div v-if="form.payment_type === 'other'">
                        <Label for="category_id">Category *</Label>
                        <select 
                            id="category_id" 
                            v-model="form.category_id"
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                        >
                            <option value="">Select Category</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                        <p v-if="errors.category_id && errors.category_id[0]" class="mt-1 text-sm text-red-600">{{ errors.category_id[0] }}</p>
                    </div>

                    <!-- Transaction Date -->
                    <div>
                        <Label for="transaction_date">Date *</Label>
                        <input 
                            id="transaction_date" 
                            v-model="form.transaction_date" 
                            type="date" 
                            required
                            class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                        />
                        <p v-if="errors.transaction_date && errors.transaction_date[0]" class="mt-1 text-sm text-red-600">{{ errors.transaction_date[0] }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <Label for="description">Description (Optional)</Label>
                        <textarea 
                            id="description" 
                            v-model="form.description"
                            class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"
                            rows="2"
                            placeholder="Add any notes..."
                        ></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <Button type="button" variant="outline" @click="router.visit('/finance')">
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="isSubmitting">
                            <Icon v-if="isSubmitting" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                            <Icon v-else icon="credit-card" class="mr-2 h-4 w-4" />
                            {{ isSubmitting ? 'Processing...' : 'Receive Payment' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
