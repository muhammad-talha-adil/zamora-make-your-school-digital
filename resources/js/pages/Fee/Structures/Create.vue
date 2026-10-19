<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { reactive, ref, computed, watch } from 'vue';
import { route } from 'ziggy-js';
import axios from 'axios';
import { alert } from '@/utils';
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
    classes: Array<{ id: number; name: string }>;
    sections: Array<{ id: number; name: string; class_id: number }>;
    months: Array<{ id: number; name: string; month_number: number }>;
    feeHeads: Array<{ id: number; name: string; category: string; default_frequency: string }>;
    feeHeadCategories: Array<{ value: string; label: string; default_frequency: string }>;
    feeHeadFrequencies: Array<{ value: string; label: string }>;
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

const { isValid } = useFormValidity(form, ['title', 'session_id', 'campus_id', 'status', 'class_id']);

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
    category: 'tuition',
    default_frequency: 'monthly',
});
const feeHeadFormErrors = ref<Record<string, string>>({});

// Pre-fill frequency from the category's default whenever the category
// changes, but only while the frequency still matches the previous
// suggestion — so it can still be overridden manually.
const lastSuggestedFeeHeadFrequency = ref<string | null>(null);

watch(
    () => feeHeadForm.category,
    (category) => {
        const suggested = props.feeHeadCategories.find((cat) => cat.value === category)?.default_frequency;
        if (!suggested) return;

        if (feeHeadForm.default_frequency === '' || feeHeadForm.default_frequency === lastSuggestedFeeHeadFrequency.value) {
            feeHeadForm.default_frequency = suggested;
        }

        lastSuggestedFeeHeadFrequency.value = suggested;
    },
);

const openAddFeeHeadModal = () => {
    feeHeadForm.name = '';
    feeHeadForm.category = 'tuition';
    feeHeadForm.default_frequency = 'monthly';
    lastSuggestedFeeHeadFrequency.value = null;
    feeHeadFormErrors.value = {};
    showFeeHeadModal.value = true;
};

const saveFeeHead = async () => {
    feeHeadFormErrors.value = {};
    isSavingFeeHead.value = true;

    try {
        const response = await axios.post(route('fee.heads.store'), {
            name: feeHeadForm.name,
            category: feeHeadForm.category,
            default_frequency: feeHeadForm.default_frequency,
            is_recurring: feeHeadForm.default_frequency !== 'once',
            is_optional: false,
            is_active: true,
        }, { headers: { Accept: 'application/json' } });

        const newFeeHead = response.data.feeHead;
        feeHeadsList.value.push({
            id: newFeeHead.id,
            name: newFeeHead.name,
            category: newFeeHead.category,
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

// Inline "Add Class" modal — same reasoning as the Fee Head one above.
const showClassModal = ref(false);
const isSavingClass = ref(false);
const classForm = reactive({ name: '', description: '' });
const classFormErrors = ref<Record<string, string>>({});

const openAddClassModal = () => {
    classForm.name = '';
    classForm.description = '';
    classFormErrors.value = {};
    showClassModal.value = true;
};

const saveClass = async () => {
    classFormErrors.value = {};
    isSavingClass.value = true;

    try {
        const response = await axios.post('/settings/school-classes', {
            name: classForm.name,
            description: classForm.description || null,
        }, { headers: { Accept: 'application/json' } });

        const newClass = response.data.schoolClass ?? response.data.class;
        classesList.value.push({ id: newClass.id, name: newClass.name });
        form.class_id = String(newClass.id);
        onClassChange();

        showClassModal.value = false;
        alert.success('Class created successfully!');
    } catch (err: unknown) {
        const response = (err as { response?: { data?: { errors?: Record<string, string[]>; message?: string } } }).response;
        if (response?.data?.errors) {
            classFormErrors.value = Object.fromEntries(
                Object.entries(response.data.errors).map(([key, value]) => [key, value[0]])
            );
        } else {
            alert.error(response?.data?.message || 'Failed to create class.');
        }
    } finally {
        isSavingClass.value = false;
    }
};

const sessionOptions = computed(() => props.sessions.map((session) => ({ value: String(session.id), label: session.name })));
const classOptions = computed(() => classesList.value.map((cls) => ({ value: String(cls.id), label: cls.name })));
const sectionOptions = computed(() => filteredSections.value.map((section) => ({ value: String(section.id), label: section.name })));
const feeHeadOptions = computed(() => feeHeadsList.value.map((fh) => ({ value: String(fh.id), label: fh.name })));
const feeHeadCategoryOptions = computed(() => props.feeHeadCategories.map((cat) => ({ value: cat.value, label: cat.label })));
const feeHeadFrequencyOptions = computed(() => props.feeHeadFrequencies.map((freq) => ({ value: freq.value, label: freq.label })));
const statusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

// Filter sections based on selected class
const filteredSections = computed(() => {
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
    if (!form.class_id) {
        errors.value.class_id = 'Class is required';
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
    
    // A structure applies to exactly one section - or to the whole class, for
    // a class that has no sections set up at all.
    const sectionIds: number[] = form.section_id ? [Number(form.section_id)] : [];


    router.post(route('fee.structures.store'), {
        title: String(form.title),
        session_id: form.session_id ? Number(form.session_id) : null,
        campus_id: form.campus_id ? Number(form.campus_id) : null,
        class_id: form.class_id ? Number(form.class_id) : null,
        section_ids: sectionIds,
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
                    <div class="grid gap-4 md:grid-cols-2">
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
                    </div>

                    <!-- Scope Selection -->
                    <div class="grid gap-4 md:grid-cols-2">
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

                    <!-- Class & Section -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <Label for="class">Class <span class="text-destructive">*</span></Label>
                                <button type="button" class="text-xs text-primary hover:underline" @click="openAddClassModal">
                                    + Add Class
                                </button>
                            </div>
                            <SearchableSelect
                                id="class"
                                v-model="form.class_id"
                                :options="classOptions"
                                placeholder="Select Class"
                                :class="errors.class_id ? 'border-destructive' : ''"
                                @update:modelValue="onClassChange"
                            />
                            <p v-if="errors.class_id" class="text-sm text-destructive">{{ errors.class_id }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="section">Section (Optional)</Label>
                            <SearchableSelect
                                id="section"
                                v-model="form.section_id"
                                :options="sectionOptions"
                                placeholder="Select Section"
                                :disabled="!form.class_id || filteredSections.length === 0"
                                clearable
                                :class="errors.section_id ? 'border-destructive' : ''"
                            />
                            <p v-if="errors.section_id" class="text-sm text-destructive">{{ errors.section_id }}</p>
                            <p v-else class="text-xs text-muted-foreground">
                                <span v-if="!form.class_id">Select a class first</span>
                                <span v-else-if="filteredSections.length === 0">This class has no sections</span>
                                <span v-else>Leave empty to apply to the whole class</span>
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

                        <div class="space-y-2">
                            <Label>Frequency</Label>
                            <div class="text-sm text-muted-foreground p-2 bg-muted rounded">
                                Auto-set from Fee Head
                            </div>
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
                                    <div class="text-sm font-medium text-foreground">{{ getFeeHeadName(item.fee_head_id) }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', 
                                        item.frequency === 'monthly' ? 'bg-primary/10 text-primary' :
                                        item.frequency === 'yearly' ? 'bg-primary/10 text-primary' :
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

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="fh-category">Category <span class="text-destructive">*</span></Label>
                            <SearchableSelect id="fh-category" v-model="feeHeadForm.category" :options="feeHeadCategoryOptions" placeholder="Select Category" />
                        </div>

                        <div class="space-y-2">
                            <Label for="fh-frequency">Frequency <span class="text-destructive">*</span></Label>
                            <SearchableSelect id="fh-frequency" v-model="feeHeadForm.default_frequency" :options="feeHeadFrequencyOptions" placeholder="Select Frequency" />
                        </div>
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

        <!-- Add Class Modal -->
        <Dialog v-model:open="showClassModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add Class</DialogTitle>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="cls-name">Name <span class="text-destructive">*</span></Label>
                        <Input id="cls-name" v-model="classForm.name" placeholder='e.g. "10", "Class 10"' :class="{ 'border-destructive': classFormErrors.name }" />
                        <p v-if="classFormErrors.name" class="text-sm text-destructive">{{ classFormErrors.name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="cls-description">Description (optional)</Label>
                        <Input id="cls-description" v-model="classForm.description" placeholder="Enter class description" />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showClassModal = false">Cancel</Button>
                    <Button @click="saveClass" :disabled="isSavingClass || !classForm.name">
                        {{ isSavingClass ? 'Saving...' : 'Save Class' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
