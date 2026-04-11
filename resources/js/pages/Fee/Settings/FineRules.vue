<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';

interface FineRule {
    id: number;
    name: string;
    campus_id: number;
    session_id: number;
    class_id: number | null;
    section_id: number | null;
    fee_head_id: number | null;
    grace_days: number;
    fine_type: string;
    fine_value: number;
    effective_from: string;
    effective_to: string | null;
    is_active: boolean;
    campus?: { id: number; name: string };
    session?: { id: number; name: string };
    schoolClass?: { id: number; name: string };
    section?: { id: number; name: string };
    feeHead?: { id: number; name: string };
}

interface Props {
    fineRules: FineRule[];
    campuses: { id: number; name: string }[];
    sessions: { id: number; name: string }[];
    classes: { id: number; name: string }[];
    sections: { id: number; name: string; class_id: number }[];
    feeHeads: { id: number; name: string }[];
    filters?: {
        campus_id?: number;
        session_id?: number;
        class_id?: number;
        is_active?: boolean;
        search?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Settings', href: '/fee/settings' },
    { title: 'Fine Rules', href: '/fee/settings/fine-rules' },
];

const showCreateModal = ref(false);
const editingRule = ref<FineRule | null>(null);
const isSubmitting = ref(false);

// Form errors
const formErrors = ref<Record<string, string>>({});

// Filter states
const filterCampus = ref<string>('');
const filterSession = ref<string>('');
const filterClass = ref<string>('');
const filterActive = ref<string>('');
const searchQuery = ref('');

// Form data
const form = ref({
    name: '',
    campus_id: '',
    session_id: '',
    class_id: '',
    section_id: '',
    fee_head_id: '',
    grace_days: 0,
    fine_type: 'fixed_per_day',
    fine_value: 0,
    effective_from: '',
    effective_to: '',
    is_active: true,
});

// Computed: Filtered sections based on selected class
const filteredSections = computed(() => {
    if (!form.value.class_id) return [];
    return props.sections.filter(s => s.class_id === Number(form.value.class_id));
});

// Check if fine value should show percentage
const showPercentage = computed(() => {
    return form.value.fine_type === 'percent';
});

// Validate form fields
const validateForm = (): boolean => {
    formErrors.value = {};
    let isValid = true;

    // Name validation
    if (!form.value.name.trim()) {
        formErrors.value.name = 'Rule name is required';
        isValid = false;
    } else if (form.value.name.length > 100) {
        formErrors.value.name = 'Rule name must not exceed 100 characters';
        isValid = false;
    }

    // Campus validation
    if (!form.value.campus_id) {
        formErrors.value.campus_id = 'Campus is required';
        isValid = false;
    }

    // Session validation
    if (!form.value.session_id) {
        formErrors.value.session_id = 'Session is required';
        isValid = false;
    }

    // Class-Section validation: if class is selected, section must belong to that class
    if (form.value.class_id && form.value.section_id) {
        const section = props.sections.find(s => s.id === Number(form.value.section_id));
        if (section && section.class_id !== Number(form.value.class_id)) {
            formErrors.value.section_id = 'Section does not belong to the selected class';
            isValid = false;
        }
    }

    // Grace days validation
    if (form.value.grace_days < 0) {
        formErrors.value.grace_days = 'Grace days cannot be negative';
        isValid = false;
    }

    // Fine value validation
    if (form.value.fine_value <= 0) {
        formErrors.value.fine_value = 'Fine value must be greater than 0';
        isValid = false;
    } else if (showPercentage.value && form.value.fine_value > 100) {
        formErrors.value.fine_value = 'Percentage cannot exceed 100%';
        isValid = false;
    }

    // Effective dates validation
    if (!form.value.effective_from) {
        formErrors.value.effective_from = 'Effective from date is required';
        isValid = false;
    }

    if (form.value.effective_to) {
        const fromDate = new Date(form.value.effective_from);
        const toDate = new Date(form.value.effective_to);
        if (toDate < fromDate) {
            formErrors.value.effective_to = 'End date must be after start date';
            isValid = false;
        }
    }

    return isValid;
};

// Watch filter changes and apply
watch([filterCampus, filterSession, filterClass, filterActive, searchQuery], () => {
    const query: Record<string, string> = {};
    
    if (filterCampus.value) query.campus_id = filterCampus.value;
    if (filterSession.value) query.session_id = filterSession.value;
    if (filterClass.value) query.class_id = filterClass.value;
    if (filterActive.value) query.is_active = filterActive.value;
    if (searchQuery.value) query.search = searchQuery.value;
    
    router.get(route('fee.settings.fine-rules'), query, { replace: true });
});

const resetForm = () => {
    form.value = {
        name: '',
        campus_id: props.campuses[0]?.id?.toString() || '',
        session_id: props.sessions[0]?.id?.toString() || '',
        class_id: '',
        section_id: '',
        fee_head_id: '',
        grace_days: 0,
        fine_type: 'fixed_per_day',
        fine_value: 0,
        effective_from: new Date().toISOString().split('T')[0],
        effective_to: '',
        is_active: true,
    };
    formErrors.value = {};
};

const openCreateModal = () => {
    resetForm();
    editingRule.value = null;
    showCreateModal.value = true;
};

const openEditModal = (rule: FineRule) => {
    form.value = {
        name: rule.name,
        campus_id: rule.campus_id?.toString() || '',
        session_id: rule.session_id?.toString() || '',
        class_id: rule.class_id?.toString() || '',
        section_id: rule.section_id?.toString() || '',
        fee_head_id: rule.fee_head_id?.toString() || '',
        grace_days: rule.grace_days,
        fine_type: rule.fine_type,
        fine_value: rule.fine_value,
        effective_from: rule.effective_from,
        effective_to: rule.effective_to || '',
        is_active: rule.is_active,
    };
    formErrors.value = {};
    editingRule.value = rule;
    showCreateModal.value = true;
};

const closeModal = () => {
    showCreateModal.value = false;
    editingRule.value = null;
    resetForm();
};

// Clear section when class changes
watch(() => form.value.class_id, () => {
    form.value.section_id = '';
});

const submitForm = () => {
    // Validate before submission
    if (!validateForm()) {
        return;
    }

    isSubmitting.value = true;
    
    const data = {
        name: form.value.name,
        campus_id: form.value.campus_id ? Number(form.value.campus_id) : null,
        session_id: form.value.session_id ? Number(form.value.session_id) : null,
        class_id: form.value.class_id ? Number(form.value.class_id) : null,
        section_id: form.value.section_id ? Number(form.value.section_id) : null,
        fee_head_id: form.value.fee_head_id ? Number(form.value.fee_head_id) : null,
        grace_days: Number(form.value.grace_days),
        fine_type: form.value.fine_type,
        fine_value: Number(form.value.fine_value),
        effective_from: form.value.effective_from,
        effective_to: form.value.effective_to || null,
        is_active: form.value.is_active,
    };

    if (editingRule.value) {
        router.put(route('fee.settings.fine-rules.update', editingRule.value.id), data, {
            onSuccess: () => {
                isSubmitting.value = false;
                closeModal();
            },
            onError: (errors) => {
                isSubmitting.value = false;
                // Handle server-side errors
                formErrors.value = errors;
            },
        });
    } else {
        router.post(route('fee.settings.fine-rules.store'), data, {
            onSuccess: () => {
                isSubmitting.value = false;
                closeModal();
            },
            onError: (errors) => {
                isSubmitting.value = false;
                // Handle server-side errors
                formErrors.value = errors;
            },
        });
    }
};

const deleteRule = (rule: FineRule) => {
    if (confirm(`Are you sure you want to delete "${rule.name}"?`)) {
        router.delete(route('fee.settings.fine-rules.destroy', rule.id));
    }
};

const toggleStatus = (rule: FineRule) => {
    router.put(route('fee.settings.fine-rules.update', rule.id), {
        ...rule,
        is_active: !rule.is_active,
    });
};

const getFineTypeLabel = (type: string): string => {
    const labels: Record<string, string> = {
        fixed_per_day: 'Fixed Per Day',
        fixed_once: 'Fixed (One Time)',
        percent: 'Percentage',
    };
    return labels[type] || type;
};

const formatDate = (date: string): string => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-PK');
};

// Fine type options
const fineTypeOptions = [
    { value: 'fixed_per_day', label: 'Fixed Amount Per Day' },
    { value: 'fixed_once', label: 'Fixed Amount (One Time)' },
    { value: 'percent', label: 'Percentage of Amount' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Fine Rules" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
                        Fine Rules
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Configure late fee payment fines
                    </p>
                </div>
                <Button @click="openCreateModal">
                    Add Fine Rule
                </Button>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="space-y-2">
                        <Label for="filter-campus">Campus</Label>
                        <select 
                            id="filter-campus"
                            v-model="filterCampus"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
                        >
                            <option value="">All Campuses</option>
                            <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id.toString()">
                                {{ campus.name }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="filter-session">Session</Label>
                        <select 
                            id="filter-session"
                            v-model="filterSession"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
                        >
                            <option value="">All Sessions</option>
                            <option v-for="session in props.sessions" :key="session.id" :value="session.id.toString()">
                                {{ session.name }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="filter-class">Class</Label>
                        <select 
                            id="filter-class"
                            v-model="filterClass"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
                        >
                            <option value="">All Classes</option>
                            <option v-for="cls in props.classes" :key="cls.id" :value="cls.id.toString()">
                                {{ cls.name }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="filter-status">Status</Label>
                        <select 
                            id="filter-status"
                            v-model="filterActive"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
                        >
                            <option value="">All Status</option>
                            <option value="true">Active</option>
                            <option value="false">Inactive</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="search">Search</Label>
                        <Input 
                            id="search" 
                            v-model="searchQuery" 
                            placeholder="Search rules..." 
                        />
                    </div>
                </div>
            </div>

            <!-- Fine Rules Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="text-left py-3 px-4 text-gray-500 dark:text-gray-400">Name</th>
                                <th class="text-left py-3 px-4 text-gray-500 dark:text-gray-400">Scope</th>
                                <th class="text-left py-3 px-4 text-gray-500 dark:text-gray-400">Grace Days</th>
                                <th class="text-left py-3 px-4 text-gray-500 dark:text-gray-400">Fine Type</th>
                                <th class="text-right py-3 px-4 text-gray-500 dark:text-gray-400">Value</th>
                                <th class="text-left py-3 px-4 text-gray-500 dark:text-gray-400">Effective From</th>
                                <th class="text-left py-3 px-4 text-gray-500 dark:text-gray-400">Fee Head</th>
                                <th class="text-center py-3 px-4 text-gray-500 dark:text-gray-400">Status</th>
                                <th class="text-center py-3 px-4 text-gray-500 dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="rule in props.fineRules" :key="rule.id" class="border-t border-gray-200 dark:border-gray-700">
                                <td class="py-3 px-4 text-gray-900 dark:text-white font-medium">{{ rule.name }}</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-300">
                                    <div class="text-xs">
                                        <div>{{ rule.campus?.name || 'All' }}</div>
                                        <div v-if="rule.schoolClass" class="text-gray-500">{{ rule.schoolClass.name }}</div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ rule.grace_days }} days</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ getFineTypeLabel(rule.fine_type) }}</td>
                                <td class="py-3 px-4 text-right text-gray-900 dark:text-white">
                                    {{ rule.fine_value }}<span v-if="rule.fine_type === 'percent'">%</span>
                                </td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-300">
                                    {{ formatDate(rule.effective_from) }}
                                    <span v-if="rule.effective_to" class="text-xs text-gray-500"> - {{ formatDate(rule.effective_to) }}</span>
                                </td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-300">
                                    {{ rule.feeHead?.name || 'All Fees' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button 
                                        @click="toggleStatus(rule)"
                                        :class="['inline-flex items-center px-2 py-1 rounded-full text-xs font-medium cursor-pointer hover:opacity-80', rule.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200']"
                                    >
                                        {{ rule.is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <Button variant="outline" size="sm" @click="openEditModal(rule)">
                                            Edit
                                        </Button>
                                        <Button variant="destructive" size="sm" @click="deleteRule(rule)">
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="props.fineRules.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
                        No fine rules configured yet. Click "Add Fine Rule" to create one.
                    </div>
                </div>
            </div>

            <!-- Create/Edit Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        {{ editingRule ? 'Edit Fine Rule' : 'Add Fine Rule' }}
                    </h2>
                    <form @submit.prevent="submitForm" class="space-y-4">
                        <!-- Rule Name -->
                        <div class="space-y-2">
                            <Label for="name">Rule Name *</Label>
                            <Input 
                                id="name" 
                                v-model="form.name" 
                                required 
                                placeholder="e.g. Late Fee Fine for Monthly Tuition" 
                                :class="formErrors.name ? 'border-red-500' : ''"
                            />
                            <p v-if="formErrors.name" class="text-xs text-red-500">{{ formErrors.name }}</p>
                        </div>

                        <!-- Campus & Session -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="campus_id">Campus *</Label>
                                <select 
                                    id="campus_id"
                                    v-model="form.campus_id"
                                    required
                                    :class="['w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white', formErrors.campus_id ? 'border-red-500' : '']"
                                >
                                    <option value="">Select Campus</option>
                                    <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id.toString()">
                                        {{ campus.name }}
                                    </option>
                                </select>
                                <p v-if="formErrors.campus_id" class="text-xs text-red-500">{{ formErrors.campus_id }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="session_id">Session *</Label>
                                <select 
                                    id="session_id"
                                    v-model="form.session_id"
                                    required
                                    :class="['w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white', formErrors.session_id ? 'border-red-500' : '']"
                                >
                                    <option value="">Select Session</option>
                                    <option v-for="session in props.sessions" :key="session.id" :value="session.id.toString()">
                                        {{ session.name }}
                                    </option>
                                </select>
                                <p v-if="formErrors.session_id" class="text-xs text-red-500">{{ formErrors.session_id }}</p>
                            </div>
                        </div>

                        <!-- Class & Section (Optional) -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="class_id">Class (Optional)</Label>
                                <select 
                                    id="class_id"
                                    v-model="form.class_id"
                                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
                                >
                                    <option value="">All Classes</option>
                                    <option v-for="cls in props.classes" :key="cls.id" :value="cls.id.toString()">
                                        {{ cls.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <Label for="section_id">Section (Optional)</Label>
                                <select 
                                    id="section_id"
                                    v-model="form.section_id"
                                    :disabled="!form.class_id"
                                    :class="['w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white', formErrors.section_id ? 'border-red-500' : '']"
                                >
                                    <option value="">All Sections</option>
                                    <option v-for="section in filteredSections" :key="section.id" :value="section.id.toString()">
                                        {{ section.name }}
                                    </option>
                                </select>
                                <p v-if="formErrors.section_id" class="text-xs text-red-500">{{ formErrors.section_id }}</p>
                            </div>
                        </div>

                        <!-- Fee Head (Optional) -->
                        <div class="space-y-2">
                            <Label for="fee_head_id">Apply to Fee Head (Optional)</Label>
                            <select 
                                id="fee_head_id"
                                v-model="form.fee_head_id"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
                            >
                                <option value="">All Fee Heads</option>
                                <option v-for="fh in props.feeHeads" :key="fh.id" :value="fh.id.toString()">
                                    {{ fh.name }}
                                </option>
                            </select>
                            <p class="text-xs text-gray-500">Leave empty to apply to all fee types</p>
                        </div>

                        <!-- Grace Days & Fine Type -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="grace_days">Grace Days *</Label>
                                <Input 
                                    id="grace_days" 
                                    v-model="form.grace_days" 
                                    type="number" 
                                    min="0" 
                                    required 
                                    :class="formErrors.grace_days ? 'border-red-500' : ''"
                                />
                                <p class="text-xs text-gray-500">Days before fine applies</p>
                                <p v-if="formErrors.grace_days" class="text-xs text-red-500">{{ formErrors.grace_days }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="fine_type">Fine Type *</Label>
                                <select 
                                    id="fine_type"
                                    v-model="form.fine_type"
                                    required
                                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white"
                                >
                                    <option v-for="opt in fineTypeOptions" :key="opt.value" :value="opt.value">
                                        {{ opt.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Fine Value -->
                        <div class="space-y-2">
                            <Label for="fine_value">
                                Fine Value {{ showPercentage ? '(%)' : '' }} *
                            </Label>
                            <Input 
                                id="fine_value" 
                                v-model="form.fine_value" 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                :max="showPercentage ? 100 : undefined"
                                required 
                                :class="formErrors.fine_value ? 'border-red-500' : ''"
                            />
                            <p class="text-xs text-gray-500">
                                <span v-if="showPercentage">Enter percentage (0-100)</span>
                                <span v-else>Enter fixed amount in PKR</span>
                            </p>
                            <p v-if="formErrors.fine_value" class="text-xs text-red-500">{{ formErrors.fine_value }}</p>
                        </div>

                        <!-- Effective Dates -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="effective_from">Effective From *</Label>
                                <Input 
                                    id="effective_from" 
                                    v-model="form.effective_from" 
                                    type="date" 
                                    required 
                                    :class="formErrors.effective_from ? 'border-red-500' : ''"
                                />
                                <p v-if="formErrors.effective_from" class="text-xs text-red-500">{{ formErrors.effective_from }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="effective_to">Effective To (Optional)</Label>
                                <Input 
                                    id="effective_to" 
                                    v-model="form.effective_to" 
                                    type="date"
                                    :class="formErrors.effective_to ? 'border-red-500' : ''"
                                />
                                <p v-if="formErrors.effective_to" class="text-xs text-red-500">{{ formErrors.effective_to }}</p>
                            </div>
                        </div>

                        <!-- Active Status -->
                        <div class="flex items-center gap-2">
                            <input 
                                type="checkbox" 
                                id="is_active" 
                                v-model="form.is_active" 
                                class="rounded border-gray-300 dark:border-gray-600"
                            />
                            <Label for="is_active" class="text-sm font-normal">Active</Label>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end gap-3 pt-4">
                            <Button type="button" variant="outline" @click="closeModal">Cancel</Button>
                            <Button type="submit" :disabled="isSubmitting">
                                {{ isSubmitting ? 'Saving...' : (editingRule ? 'Update' : 'Create') }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
