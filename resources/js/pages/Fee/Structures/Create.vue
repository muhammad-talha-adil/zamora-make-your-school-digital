<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { reactive, ref, computed, watch } from 'vue';
import { route } from 'ziggy-js';
import axios from 'axios';
import { alert } from '@/utils';
import SchoolClassForm from '@/components/forms/SchoolClassForm.vue';
import { useFormValidity } from '@/composables/useFormValidity';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { ComboboxInput } from '@/components/ui/combobox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import Icon from '@/components/Icon.vue';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';

interface Props {
    active_session_id?: number;
    sessions: Array<{ id: number; name: string; start_date: string; end_date: string }>;
    classes: Array<{ id: number; name: string; campus_id: number }>;
    sections: Array<{ id: number; name: string; class_id: number }>;
    months: Array<{ id: number; name: string; month_number: number }>;
    feeHeads: Array<{ id: number; name: string; default_frequency: string }>;
    feeHeadFrequencies: Array<{ value: string; label: string }>;
    isSuperAdmin?: boolean;
    campuses?: Array<{ id: number; name: string }>;
}

const props = defineProps<Props>();

// The campus is never a field on this form — it's always whichever campus
// the creator is currently scoped to (their own campus, or the header
// switcher's pick for an owner/developer), matching the Staff module's
// header-derived campus convention. The backend derives the real value
// server-side regardless of what's sent; this is just the display default.
const page = usePage();
const academicScope = computed(() => (page.props as any).academicScope as { campusId: number | null; isCampusRestricted: boolean });

// Local, mutable copy so a fee head added inline via the modal shows up in the
// dropdown immediately without a full page reload.
const feeHeadsList = ref([...props.feeHeads]);

// Same reasoning for a class.
const classesList = ref([...props.classes]);

// Owner/developer only: which campuses' classes are offered below. Starts
// with every campus checked so the class list isn't empty by default.
const selectedCampusIds = ref<number[]>((props.campuses ?? []).map((c) => c.id));
const allCampusesSelected = computed(
    () => selectedCampusIds.value.length === (props.campuses?.length ?? 0) && (props.campuses?.length ?? 0) > 0,
);
const toggleAllCampuses = (checked: boolean) => {
    selectedCampusIds.value = checked ? (props.campuses ?? []).map((c) => c.id) : [];
};

// Non-super-admin's classesList is already scoped server-side to their one
// campus; a super admin's is scoped here to whichever campuses are checked.
const visibleClasses = computed(() =>
    props.isSuperAdmin
        ? classesList.value.filter((c) => selectedCampusIds.value.includes(c.campus_id))
        : classesList.value,
);

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fee Management', href: '/fee/dashboard' },
    { title: 'Fee Structures', href: '/fee/structures' },
    { title: 'Create', href: '/fee/structures/create' },
];

/**
 * When this page is reached from another page that already knows the scope
 * (e.g. a "Set up fee structure" link from the class or section view), the
 * query string carries campus_id/class_id/section_id/session_id so the
 * clerk doesn't have to reselect what the linking page already established.
 */
const queryParams = new URLSearchParams(window.location.search);

/**
 * When this page was reached from the admission form's inline "Create Fee
 * Structure" action, the calling page's URL is carried here so the clerk
 * lands back on the admission form (with its data restored from
 * sessionStorage) instead of the fee structures index (see #65).
 */
const returnTo = queryParams.get('return_to');

const form: {
    title: string | number | null;
    session_id: string;
    campus_id: string;
    class_id: string;
    section_id: string;
    status: string;
    notes: string;
} = reactive({
    title: null,
    session_id: queryParams.get('session_id') || String(props.active_session_id || ''), // Auto-select active session
    campus_id: queryParams.get('campus_id') || (academicScope.value?.campusId ? String(academicScope.value.campusId) : ''),
    class_id: queryParams.get('class_id') || '',
    section_id: queryParams.get('section_id') || '',
    status: 'active',
    notes: '',
});

const errors = ref<Record<string, string>>({});
const isSubmitting = ref(false);

const { isValid: isBaseValid } = useFormValidity(form, ['title', 'session_id', 'campus_id', 'status']);
const isValid = computed(() => isBaseValid.value && selectedClassIds.value.length > 0);

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

// Inline "Add Fee Head" modal - avoids navigating away from this form (#38)
const showFeeHeadModal = ref(false);
const isSavingFeeHead = ref(false);
const feeHeadForm = reactive({
    name: '',
    default_frequency: 'monthly',
});
const feeHeadFormErrors = ref<Record<string, string>>({});

const openAddFeeHeadModal = () => {
    feeHeadForm.name = '';
    feeHeadForm.default_frequency = 'monthly';
    feeHeadFormErrors.value = {};
    showFeeHeadModal.value = true;
};

const saveFeeHead = async () => {
    feeHeadFormErrors.value = {};
    isSavingFeeHead.value = true;

    try {
        const response = await axios.post(route('fee.heads.store'), {
            name: feeHeadForm.name,
            default_frequency: feeHeadForm.default_frequency,
            is_recurring: feeHeadForm.default_frequency !== 'once',
            is_optional: false,
            is_active: true,
        }, { headers: { Accept: 'application/json' } });

        const newFeeHead = response.data.feeHead;
        feeHeadsList.value.push({
            id: newFeeHead.id,
            name: newFeeHead.name,
            default_frequency: newFeeHead.default_frequency,
        });

        // Pre-select the new fee head in the "add item" form
        itemForm.fee_head_id = String(newFeeHead.id);

        showFeeHeadModal.value = false;
        alert.success('Fee head created successfully!');
    } catch (err: unknown) {
        const response = (err as { response?: { data?: { errors?: Record<string, string[]>; message?: string } } }).response;
        if (response?.data?.errors) {
            feeHeadFormErrors.value = Object.fromEntries(
                Object.entries(response.data.errors).map(([key, value]) => [key, value[0]])
            );
        } else {
            alert.error(response?.data?.message || 'Failed to create fee head.');
        }
    } finally {
        isSavingFeeHead.value = false;
    }
};

// "Add Class" reuses the same SchoolClassForm modal as the Classes settings
// page (prefix buttons included) instead of a second, stripped-down copy -
// refetches the class list on save so the new one shows up immediately.
const onClassCreated = async () => {
    const response = await axios.get('/settings/school-classes/all', { params: { per_page: 1000 } });
    classesList.value = (response.data.data ?? []).map((c: { id: number; name: string }) => ({ id: c.id, name: c.name }));
};

const sessionOptions = computed(() => props.sessions.map((session) => ({ value: String(session.id), label: session.name })));
const feeHeadOptions = computed(() => feeHeadsList.value.map((fh) => ({ value: String(fh.id), label: fh.name })));
const feeHeadFrequencyOptions = computed(() => props.feeHeadFrequencies.map((freq) => ({ value: freq.value, label: freq.label })));
const statusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

// Multi-class mode: each checked class shows its own sections, checked by
// default (unchecking one excludes just that section from the batch).
const selectedClassIds = ref<number[]>([]);
const sectionIdsByClass = ref<Record<number, number[]>>({});

const sectionsForClass = (classId: number) => props.sections.filter((s) => s.class_id === classId);

const toggleClass = (classId: number, checked: boolean) => {
    if (checked) {
        selectedClassIds.value.push(classId);
        sectionIdsByClass.value[classId] = sectionsForClass(classId).map((s) => s.id);
    } else {
        selectedClassIds.value = selectedClassIds.value.filter((id) => id !== classId);
        delete sectionIdsByClass.value[classId];
    }
};

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

// Watch for scope changes and clear title when session/campus/class changes
watch([() => form.session_id, () => form.campus_id, () => form.class_id, () => form.section_id], () => {
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
    if (selectedClassIds.value.length === 0) {
        errors.value.class_id = 'Pick at least one class';
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
    if (itemForm.fee_head_id && feeItems.value.some((item) => item.fee_head_id === Number(itemForm.fee_head_id))) {
        itemErrors.value.fee_head_id = 'This fee head has already been added';
    }
    
    return Object.keys(itemErrors.value).length === 0;
};

const addItem = () => {
    if (!validateItemForm()) return;
    
    // Get the fee head to auto-fill frequency
    const feeHead = feeHeadsList.value.find(fh => fh.id === Number(itemForm.fee_head_id));
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
    const fh = feeHeadsList.value.find(f => f.id === id);
    return fh ? fh.name : 'Unknown';
};

// A fee head's color is derived from its own id, not stored anywhere - so
// every fee head (including ones created after this was written) gets a
// consistent color automatically, with no setup step.
const feeHeadColorClasses = [
    'bg-primary/10 text-primary',
    'bg-success/10 text-success',
    'bg-warning/10 text-warning',
    'bg-destructive/10 text-destructive',
    'bg-info/10 text-info',
    'bg-purple-500/10 text-purple-500',
    'bg-pink-500/10 text-pink-500',
    'bg-teal-500/10 text-teal-500',
];
const getFeeHeadColor = (id: number) => feeHeadColorClasses[id % feeHeadColorClasses.length];



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
    
    const classes = selectedClassIds.value.map((classId) => ({
        class_id: classId,
        section_ids: sectionIdsByClass.value[classId] ?? [],
    }));

    router.post(route('fee.structures.store'), {
        title: String(form.title),
        session_id: form.session_id ? Number(form.session_id) : null,
        campus_id: form.campus_id ? Number(form.campus_id) : null,
        classes,
        status: form.status,
        notes: form.notes,
        items: feeItems.value,
    }, {
        onSuccess: () => {
            isSubmitting.value = false;
            alert.success('Fee structure created successfully!');
            router.visit(returnTo || route('fee.structures.index'));
        },
        onError: (err) => {
            isSubmitting.value = false;
            errors.value = err as Record<string, string>;
            const firstError = Object.values(err)[0];
            alert.error(firstError);
        },
    });
};

const cancel = () => {
    router.visit(returnTo || route('fee.structures.index'));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Create Fee Structure" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">
                        Create Fee Structure
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                        Create a new fee structure for students
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="cancel">
                        <Icon icon="x" class="mr-2 h-4 w-4" />
                        Cancel
                    </Button>
                    <Button @click="submitForm" :disabled="isSubmitting || !isValid">
                        <Icon icon="check" class="mr-2 h-4 w-4" />
                        {{ isSubmitting ? 'Creating...' : 'Create Structure' }}
                    </Button>
                </div>
            </div>

            <!-- Basic Information Form -->
            <div class="bg-card rounded-lg border border-border p-4 md:p-6">
                <form @submit.prevent="submitForm" class="space-y-6">
                    <!-- Basic Information -->
                    <div class="space-y-2">
                        <Label for="title">Title <span class="text-destructive">*</span></Label>
                        <ComboboxInput
                            id="title"
                            v-model="form.title"
                            :placeholder="'e.g. Class 1 Annual Fee Structure'"
                            :search-url="titleSearchUrl"
                            :value-type="'name'"
                            display-key="title"
                            classMinWidth="w-full"
                            @update:modelValue="onTitleChange"
                        />
                        <p class="text-xs text-muted-foreground">
                            A short, descriptive name for this fee structure (e.g. "Class 1 Annual Fee Structure", "Grade 5 Term 1 Fees").
                        </p>
                        <p v-if="errors.title" class="text-sm text-destructive">{{ errors.title }}</p>
                    </div>

                    <!-- Status & Session -->
                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                        <div class="space-y-2">
                            <Label for="status">Status <span class="text-destructive">*</span></Label>
                            <SearchableSelect
                                id="status"
                                v-model="form.status"
                                :options="statusOptions"
                                :class="{ 'border-destructive': errors.status }"
                            />
                            <p v-if="errors.status" class="text-sm text-destructive">{{ errors.status }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="session">Session <span class="text-destructive">*</span></Label>
                            <SearchableSelect
                                id="session"
                                v-model="form.session_id"
                                :options="sessionOptions"
                                placeholder="Select Session"
                                :class="errors.session_id ? 'border-destructive' : ''"
                            />
                            <p v-if="errors.session_id" class="text-sm text-destructive">{{ errors.session_id }}</p>
                        </div>
                    </div>

                    <!-- Campuses (owner/developer only) -->
                    <div v-if="isSuperAdmin" class="space-y-2">
                        <Label>Campuses <span class="text-destructive">*</span></Label>
                        <div class="flex items-center gap-2 border-b border-border pb-2">
                            <input
                                id="fs_campus_all"
                                type="checkbox"
                                :checked="allCampusesSelected"
                                class="rounded border-border text-primary focus:ring-primary"
                                @change="toggleAllCampuses(($event.target as HTMLInputElement).checked)"
                            />
                            <Label for="fs_campus_all" class="font-normal">All Campuses</Label>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <label
                                v-for="campus in campuses"
                                :key="campus.id"
                                class="flex cursor-pointer items-center gap-1.5 rounded-full border border-border bg-background px-3 py-1 text-xs has-[:checked]:border-primary has-[:checked]:bg-primary/10 has-[:checked]:text-primary"
                            >
                                <input
                                    v-model="selectedCampusIds"
                                    type="checkbox"
                                    :value="campus.id"
                                    class="h-3.5 w-3.5 rounded border-border text-primary focus:ring-primary"
                                />
                                {{ campus.name }}
                            </label>
                        </div>
                        <p class="text-xs text-muted-foreground">Determines which campuses' classes are offered below.</p>
                    </div>

                    <!-- Class & Section -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <Label>Classes <span class="text-destructive">*</span></Label>
                            <SchoolClassForm trigger="Add Class" variant="link" size="sm" @saved="onClassCreated" />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div
                                v-for="cls in visibleClasses"
                                :key="cls.id"
                                class="rounded-lg border transition-colors"
                                :class="selectedClassIds.includes(cls.id) ? 'border-primary bg-primary/5' : 'border-border bg-card'"
                            >
                                <label class="flex cursor-pointer items-center gap-3 px-4 py-3">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-border text-primary focus:ring-primary"
                                        :checked="selectedClassIds.includes(cls.id)"
                                        @change="toggleClass(cls.id, ($event.target as HTMLInputElement).checked)"
                                    />
                                    <span class="text-sm font-medium">{{ cls.name }}</span>
                                </label>
                                <div v-if="selectedClassIds.includes(cls.id)" class="flex flex-wrap gap-2 px-4 pb-3 pl-11">
                                    <span v-if="sectionsForClass(cls.id).length === 0" class="text-xs text-muted-foreground">
                                        No sections - applies to the whole class.
                                    </span>
                                    <label
                                        v-for="sec in sectionsForClass(cls.id)"
                                        :key="sec.id"
                                        class="flex cursor-pointer items-center gap-1.5 rounded-full border border-border bg-background px-3 py-1 text-xs has-[:checked]:border-primary has-[:checked]:bg-primary/10 has-[:checked]:text-primary"
                                    >
                                        <input
                                            type="checkbox"
                                            class="h-3.5 w-3.5 rounded border-border text-primary focus:ring-primary"
                                            :checked="(sectionIdsByClass[cls.id] ?? []).includes(sec.id)"
                                            @change="($event.target as HTMLInputElement).checked
                                                ? sectionIdsByClass[cls.id].push(sec.id)
                                                : (sectionIdsByClass[cls.id] = sectionIdsByClass[cls.id].filter((id) => id !== sec.id))"
                                        />
                                        {{ sec.name }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <p v-if="errors.class_id" class="text-sm text-destructive">{{ errors.class_id }}</p>
                        <p v-else class="text-xs text-muted-foreground">Unchecking a section applies the structure to the rest of that class only.</p>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-2">
                        <Label for="notes">Notes (Optional)</Label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm"
                            placeholder="Any additional notes about this fee structure..."
                        ></textarea>
                    </div>
                </form>
            </div>

            <!-- Fee Items Section -->
            <div class="bg-card rounded-lg border border-border p-4 md:p-6">
                <div class="flex flex-wrap gap-2 justify-between items-center mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-foreground">Fee Items</h2>
                        <p class="text-sm text-muted-foreground">Add fee items to this structure (e.g., Tuition, Transport)</p>
                    </div>
                    <Button @click="openAddItemForm" v-if="!showItemForm">
                        <Icon icon="plus" class="mr-2 h-4 w-4" />
                        Add Fee Item
                    </Button>
                </div>

                <!-- Add Item Form -->
                <div v-if="showItemForm" class="mb-6 p-4 bg-muted rounded-lg">
                    <h3 class="font-semibold mb-4">Add New Fee Item</h3>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <Label>Fee Head <span class="text-destructive">*</span></Label>
                                <button type="button" class="text-xs text-primary hover:underline" @click="openAddFeeHeadModal">
                                    + Add Fee Head
                                </button>
                            </div>
                            <SearchableSelect
                                v-model="itemForm.fee_head_id"
                                :options="feeHeadOptions"
                                placeholder="Select Fee Head"
                                :class="itemErrors.fee_head_id ? 'border-destructive' : ''"
                            />
                            <p v-if="itemErrors.fee_head_id" class="text-sm text-destructive">{{ itemErrors.fee_head_id }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label>Amount <span class="text-destructive">*</span></Label>
                            <Input
                                v-model="itemForm.amount"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                                :class="{ 'border-destructive': itemErrors.amount }"
                            />
                            <p v-if="itemErrors.amount" class="text-sm text-destructive">{{ itemErrors.amount }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 mt-4">
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
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">
                                    Fee Head
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">
                                    Frequency
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-muted-foreground uppercase">
                                    Amount
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-muted-foreground uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="item in feeItems" :key="item.temp_id" class="transition-colors hover:bg-accent">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getFeeHeadColor(item.fee_head_id)]">
                                        {{ getFeeHeadName(item.fee_head_id) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full',
                                        item.frequency === 'monthly' ? 'bg-primary/10 text-primary' :
                                        item.frequency === 'yearly' ? 'bg-warning/10 text-warning' :
                                        'bg-success/10 text-success'
                                    ]">
                                        {{ getFrequencyLabel(item.frequency) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium whitespace-nowrap text-right">
                                    <div class="text-foreground">{{ formatCurrency(item.amount) }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <Button variant="ghost" size="sm" @click="removeItem(item.temp_id)">
                                            <Icon icon="trash-2" class="h-4 w-4 text-destructive" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-muted">
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-sm font-semibold text-foreground text-right">
                                    Monthly Total
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-foreground text-right">
                                    {{ formatCurrency(totalMonthly) }}
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-sm font-semibold text-foreground text-right">
                                    Yearly Total
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-foreground text-right">
                                    {{ formatCurrency(totalYearly) }}
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-sm font-semibold text-foreground text-right">
                                    One Time Total
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-foreground text-right">
                                    {{ formatCurrency(totalOneTime) }}
                                </td>
                                <td></td>
                            </tr>
                            <tr class="border-t-2 border-border">
                                <td colspan="2" class="px-4 py-3 text-sm font-bold text-foreground text-right">
                                    Grand Total
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-lg text-foreground text-right">
                                    {{ formatCurrency(grandTotal) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-8 text-muted-foreground">
                    <Icon icon="inbox" class="h-12 w-12 mx-auto mb-2 opacity-50" />
                    <p>No fee items added yet.</p>
                    <p class="text-sm">Click "Add Fee Item" to add fees like Tuition, Transport, etc.</p>
                </div>
            </div>
        </div>

        <!-- Add Fee Head Modal (#38) -->
        <Dialog v-model:open="showFeeHeadModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add Fee Head</DialogTitle>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="fh-name">Name <span class="text-destructive">*</span></Label>
                        <Input id="fh-name" v-model="feeHeadForm.name" placeholder="e.g. Tuition Fee" :class="{ 'border-destructive': feeHeadFormErrors.name }" />
                        <p v-if="feeHeadFormErrors.name" class="text-sm text-destructive">{{ feeHeadFormErrors.name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="fh-frequency">Frequency <span class="text-destructive">*</span></Label>
                        <SearchableSelect id="fh-frequency" v-model="feeHeadForm.default_frequency" :options="feeHeadFrequencyOptions" placeholder="Select Frequency" />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showFeeHeadModal = false">Cancel</Button>
                    <Button @click="saveFeeHead" :disabled="isSavingFeeHead || !feeHeadForm.name">
                        {{ isSavingFeeHead ? 'Saving...' : 'Save Fee Head' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
