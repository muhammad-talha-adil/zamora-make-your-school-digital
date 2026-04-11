<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref, computed, watch, onMounted, nextTick } from 'vue';
import { route } from 'ziggy-js';
import { alert } from '@/utils';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { ComboboxInput } from '@/components/ui/combobox';
import Icon from '@/components/Icon.vue';

interface Props {
    active_session_id?: number;
    sessions: Array<{ id: number; name: string; start_date: string; end_date: string }>;
    campuses: Array<{ id: number; name: string }>;
    classes: Array<{ id: number; name: string }>;
    sections: Array<{ id: number; name: string; class_id: number }>;
    months: Array<{ id: number; name: string; month_number: number }>;
    feeHeads: Array<{ id: number; name: string; category: string; default_frequency: string }>;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Fee Structures', href: '/fee/structures' },
    { title: 'Create', href: '/fee/structures/create' },
];

const form: {
    title: string | number | null;
    session_id: string;
    campus_id: string;
    class_id: string;
    section_id: string;
    effective_from: string;
    effective_to: string;
    status: string;
    notes: string;
} = reactive({
    title: null,
    session_id: String(props.active_session_id || ''), // Auto-select active session
    campus_id: '',
    class_id: '',
    section_id: '',
    effective_from: '',
    effective_to: '',
    status: 'active',
    notes: '',
});

const errors = ref<Record<string, string>>({});
const isSubmitting = ref(false);

// Fee Items - Local state for new items to be created with structure
const feeItems = ref<Array<{
    temp_id: number;
    fee_head_id: number;
    amount: number;
    frequency: string;
}>>([]);

const showItemForm = ref(false);
const itemForm = reactive({
    fee_head_id: '',
    amount: '',
});

const itemErrors = ref<Record<string, string>>({});

// Filter sections based on selected class
const filteredSections = computed(() => {
    if (!form.class_id) return [];
    return props.sections.filter(s => s.class_id === Number(form.class_id));
});

// Get all sections for selected class (for "All Sections" feature)
const allClassSections = computed(() => {
    if (!form.class_id) return [];
    return props.sections.filter(s => s.class_id === Number(form.class_id));
});

// Generate search URL for title combobox based on selected scope
const titleSearchUrl = computed(() => {
    if (!form.session_id || !form.campus_id) return '';
    
    let url = `/fee/structures/search-titles?session_id=${form.session_id}&campus_id=${form.campus_id}`;
    
    if (form.class_id) {
        url += `&class_id=${form.class_id}`;
    }
    if (form.section_id) {
        url += `&section_id=${form.section_id}`;
    }
    
    return url;
});

// Get selected session's start and end dates
const selectedSession = computed(() => {
    if (!form.session_id) return null;
    return props.sessions.find(s => s.id === Number(form.session_id));
});

// Auto-fill effective dates when session changes or on mount
const autoFillDates = () => {
    if (selectedSession.value) {
        form.effective_from = selectedSession.value.start_date;
        form.effective_to = selectedSession.value.end_date;
    }
};

// Watch for session changes
watch(() => form.session_id, (newSessionId) => {
    if (newSessionId && selectedSession.value) {
        form.effective_from = selectedSession.value.start_date;
        form.effective_to = selectedSession.value.end_date;
    }
});

// Auto-fill on page load
onMounted(() => {
    // Use nextTick to ensure the form is fully initialized
    nextTick(() => {
        autoFillDates();
    });
    // Fallback to setTimeout if nextTick doesn't work
    setTimeout(() => {
        if (!form.effective_from && selectedSession.value) {
            autoFillDates();
        }
    }, 100);
});

// Watch for scope changes and clear title when session/campus/class changes
watch([() => form.session_id, () => form.campus_id, () => form.class_id], () => {
    form.title = null;
});

// Calculate totals
const totalMonthly = computed(() => {
    return feeItems.value
        .filter(item => item.frequency === 'monthly')
        .reduce((sum, item) => sum + (item.amount || 0), 0) || 0;
});

const totalYearly = computed(() => {
    return feeItems.value
        .filter(item => item.frequency === 'yearly')
        .reduce((sum, item) => sum + (item.amount || 0), 0) || 0;
});

const totalOneTime = computed(() => {
    return feeItems.value
        .filter(item => item.frequency === 'once')
        .reduce((sum, item) => sum + (item.amount || 0), 0) || 0;
});

const grandTotal = computed(() => totalMonthly.value + totalYearly.value + totalOneTime.value);

// Reset section when class changes
const onClassChange = () => {
    form.section_id = '';
};

// Handle title change from ComboboxInput
const onTitleChange = (value: string | number | null) => {
    form.title = value;
    // Clear error when title changes
    if (errors.value.title) {
        delete errors.value.title;
    }
};

const validateForm = () => {
    errors.value = {};
    
    if (!form.title) {
        errors.value.title = 'Title is required';
    }
    if (!form.session_id) {
        errors.value.session_id = 'Session is required';
    }
    if (!form.campus_id) {
        errors.value.campus_id = 'Campus is required';
    }
    if (!form.status) {
        errors.value.status = 'Status is required';
    }
    
    return Object.keys(errors.value).length === 0;
};

// Item form methods
const openAddItemForm = () => {
    itemForm.fee_head_id = '';
    itemForm.amount = '';
    itemErrors.value = {};
    showItemForm.value = true;
};

const closeItemForm = () => {
    showItemForm.value = false;
};

const validateItemForm = () => {
    itemErrors.value = {};
    
    if (!itemForm.fee_head_id) {
        itemErrors.value.fee_head_id = 'Fee head is required';
    }
    if (!itemForm.amount || Number(itemForm.amount) <= 0) {
        itemErrors.value.amount = 'Valid amount is required';
    }
    
    return Object.keys(itemErrors.value).length === 0;
};

const addItem = () => {
    if (!validateItemForm()) return;
    
    // Get the fee head to auto-fill frequency
    const feeHead = props.feeHeads.find(fh => fh.id === Number(itemForm.fee_head_id));
    const frequency = feeHead?.default_frequency || 'monthly';
    
    feeItems.value.push({
        temp_id: Date.now(),
        fee_head_id: Number(itemForm.fee_head_id),
        amount: Number(itemForm.amount),
        frequency: frequency,
    });
    
    closeItemForm();
};

const removeItem = (tempId: number) => {
    feeItems.value = feeItems.value.filter(item => item.temp_id !== tempId);
};

const getFeeHeadName = (id: number) => {
    const fh = props.feeHeads.find(f => f.id === id);
    return fh ? fh.name : 'Unknown';
};



const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'PKR',
    }).format(amount);
};

const getFrequencyLabel = (freq: string) => {
    const labels: Record<string, string> = {
        'monthly': 'Monthly',
        'yearly': 'Yearly',
        'once': 'One Time'
    };
    return labels[freq] || freq;
};

const submitForm = () => {
    if (!validateForm()) return;
    
    isSubmitting.value = true;
    
    // Determine section_ids based on selection
    let sectionIds: number[] = [];
    
    if (form.section_id === 'all') {
        // User selected "All Sections" from dropdown - apply to all sections of the class
        if (form.class_id && allClassSections.value.length > 0) {
            sectionIds = allClassSections.value.map(s => s.id);
        }
    } else if (form.section_id) {
        // Apply to single section
        sectionIds = [Number(form.section_id)];
    }
    // If no class selected or no section selected, sectionIds remains empty (applies to all)
    
    router.post(route('fee.structures.store'), {
        title: String(form.title),
        session_id: form.session_id ? Number(form.session_id) : null,
        campus_id: form.campus_id ? Number(form.campus_id) : null,
        class_id: form.class_id ? Number(form.class_id) : null,
        section_ids: sectionIds,
        effective_from: form.effective_from,
        effective_to: form.effective_to || null,
        status: form.status,
        notes: form.notes,
        items: feeItems.value,
    }, {
        onSuccess: () => {
            isSubmitting.value = false;
            alert.success('Fee structure created successfully!');
            router.visit(route('fee.structures.index'));
        },
        onError: (err) => {
            isSubmitting.value = false;
            const firstError = Object.values(err)[0];
            alert.error(firstError);
        },
    });
};

const cancel = () => {
    router.visit(route('fee.structures.index'));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Create Fee Structure" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Create Fee Structure
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Create a new fee structure for students
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="cancel">
                        <Icon icon="x" class="mr-2 h-4 w-4" />
                        Cancel
                    </Button>
                    <Button @click="submitForm" :disabled="isSubmitting">
                        <Icon icon="check" class="mr-2 h-4 w-4" />
                        {{ isSubmitting ? 'Creating...' : 'Create Structure' }}
                    </Button>
                </div>
            </div>

            <!-- Basic Information Form -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                <form @submit.prevent="submitForm" class="space-y-6">
                    <!-- Basic Information -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="title">Title <span class="text-red-500">*</span></Label>
                            <ComboboxInput
                                id="title"
                                v-model="form.title"
                                :placeholder="'Search or enter title...'"
                                :search-url="titleSearchUrl"
                                :value-type="'name'"
                                display-key="title"
                                classMinWidth="w-full"
                                @update:modelValue="onTitleChange"
                            />
                            <p v-if="errors.title" class="text-sm text-red-500">{{ errors.title }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="status">Status <span class="text-red-500">*</span></Label>
                            <select
                                id="status"
                                v-model="form.status"
                                :class="['w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm', { 'border-red-500': errors.status }]"
                            >
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <p v-if="errors.status" class="text-sm text-red-500">{{ errors.status }}</p>
                        </div>
                    </div>

                    <!-- Scope Selection -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="session">Session <span class="text-red-500">*</span></Label>
                            <select
                                id="session"
                                v-model="form.session_id"
                                :class="['w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm', { 'border-red-500': errors.session_id }]"
                            >
                                <option value="">Select Session</option>
                                <option v-for="session in props.sessions" :key="session.id" :value="session.id">
                                    {{ session.name }}
                                </option>
                            </select>
                            <p v-if="errors.session_id" class="text-sm text-red-500">{{ errors.session_id }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="campus">Campus <span class="text-red-500">*</span></Label>
                            <select
                                id="campus"
                                v-model="form.campus_id"
                                :class="['w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm', { 'border-red-500': errors.campus_id }]"
                            >
                                <option value="">Select Campus</option>
                                <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                                    {{ campus.name }}
                                </option>
                            </select>
                            <p v-if="errors.campus_id" class="text-sm text-red-500">{{ errors.campus_id }}</p>
                        </div>
                    </div>

                    <!-- Class & Section -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="class">Class (Optional)</Label>
                            <select
                                id="class"
                                v-model="form.class_id"
                                @change="onClassChange"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                            >
                                <option value="">Select Class</option>
                                <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">
                                    {{ cls.name }}
                                </option>
                            </select>
                            <p class="text-xs text-gray-500">Leave empty to apply to all classes</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="section">Section (Optional)</Label>
                            <select
                                id="section"
                                v-model="form.section_id"
                                :disabled="!form.class_id"
                                :class="['w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm', { 'opacity-50': !form.class_id, 'border-red-500': errors.section_id }]"
                            >
                                <option value="">Select Section</option>
                                <option v-if="form.class_id && filteredSections.length > 0" value="all">All Sections ({{ filteredSections.length }})</option>
                                <option v-for="section in filteredSections" :key="section.id" :value="section.id">
                                    {{ section.name }}
                                </option>
                            </select>
                            <p class="text-xs text-gray-500">
                                <span v-if="form.section_id === 'all'">Applied to all sections of this class</span>
                                <span v-else-if="form.section_id">Applied to single section</span>
                                <span v-else>Select a section or "All Sections" to apply to all</span>
                            </p>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-2">
                        <Label for="notes">Notes (Optional)</Label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                            placeholder="Any additional notes about this fee structure..."
                        ></textarea>
                    </div>
                </form>
            </div>

            <!-- Fee Items Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Fee Items</h2>
                        <p class="text-sm text-gray-500">Add fee items to this structure (e.g., Tuition, Transport)</p>
                    </div>
                    <Button @click="openAddItemForm" v-if="!showItemForm">
                        <Icon icon="plus" class="mr-2 h-4 w-4" />
                        Add Fee Item
                    </Button>
                </div>

                <!-- Add Item Form -->
                <div v-if="showItemForm" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <h3 class="font-semibold mb-4">Add New Fee Item</h3>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div class="space-y-2">
                            <Label>Fee Head <span class="text-red-500">*</span></Label>
                            <select
                                v-model="itemForm.fee_head_id"
                                :class="['w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm', { 'border-red-500': itemErrors.fee_head_id }]"
                            >
                                <option value="">Select Fee Head</option>
                                <option v-for="fh in props.feeHeads" :key="fh.id" :value="fh.id">
                                    {{ fh.name }}
                                </option>
                            </select>
                            <p v-if="itemErrors.fee_head_id" class="text-sm text-red-500">{{ itemErrors.fee_head_id }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label>Amount <span class="text-red-500">*</span></Label>
                            <Input
                                v-model="itemForm.amount"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                                :class="{ 'border-red-500': itemErrors.amount }"
                            />
                            <p v-if="itemErrors.amount" class="text-sm text-red-500">{{ itemErrors.amount }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label>Frequency</Label>
                            <div class="text-sm text-gray-600 dark:text-gray-400 p-2 bg-gray-100 dark:bg-gray-600 rounded">
                                Auto-set from Fee Head
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-4">
                        <Button variant="outline" @click="closeItemForm">
                            Cancel
                        </Button>
                        <Button @click="addItem">
                            Add Item
                        </Button>
                    </div>
                </div>

                <!-- Items Table -->
                <div v-if="feeItems.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Fee Head
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Frequency
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Amount
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            <tr v-for="item in feeItems" :key="item.temp_id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ getFeeHeadName(item.fee_head_id) }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', 
                                        item.frequency === 'monthly' ? 'bg-blue-100 text-blue-800' :
                                        item.frequency === 'yearly' ? 'bg-purple-100 text-purple-800' :
                                        'bg-green-100 text-green-800'
                                    ]">
                                        {{ getFrequencyLabel(item.frequency) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap text-right">
                                    <div class="text-gray-900 dark:text-white">{{ formatCurrency(item.amount) }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button variant="ghost" size="sm" @click="removeItem(item.temp_id)">
                                            <Icon icon="trash-2" class="h-4 w-4 text-red-500" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white text-right">
                                    Monthly Total
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white text-right">
                                    {{ formatCurrency(totalMonthly) }}
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white text-right">
                                    Yearly Total
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white text-right">
                                    {{ formatCurrency(totalYearly) }}
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white text-right">
                                    One Time Total
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white text-right">
                                    {{ formatCurrency(totalOneTime) }}
                                </td>
                                <td></td>
                            </tr>
                            <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                                <td colspan="2" class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white text-right">
                                    Grand Total
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-lg text-gray-900 dark:text-white text-right">
                                    {{ formatCurrency(grandTotal) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-8 text-gray-500">
                    <Icon icon="inbox" class="h-12 w-12 mx-auto mb-2 opacity-50" />
                    <p>No fee items added yet.</p>
                    <p class="text-sm">Click "Add Fee Item" to add fees like Tuition, Transport, etc.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
