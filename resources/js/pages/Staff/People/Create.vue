<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="New Staff Member" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center md:gap-4">
                <div>
                    <h1 class="text-lg font-bold text-foreground md:text-2xl">New Staff Member</h1>
                    <p class="mt-1 text-xs text-muted-foreground md:text-sm">Add a new staff member to the directory</p>
                </div>
                <Button variant="outline" @click="router.visit(route('staff.people.index'))">
                    <Icon icon="arrow-left" class="mr-1 h-4 w-4" />
                    Back to List
                </Button>
            </div>

            <form @submit.prevent="submitStaff" class="space-y-6">
                <div class="rounded-lg border border-border bg-card p-6">
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
                        <Icon icon="user" class="h-5 w-5 text-primary" />
                        Staff Information
                    </h2>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="name">Name <span class="text-destructive">*</span></Label>
                            <Input id="name" v-model="form.name" placeholder="Staff name" />
                        </div>
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="form.email" type="email" placeholder="Email (optional)" />
                        </div>
                        <div class="space-y-2">
                            <Label for="employee_no">Employee No</Label>
                            <Input id="employee_no" v-model="form.employee_no" placeholder="Auto-generate if empty" />
                        </div>
                        <div class="space-y-2">
                            <Label for="campus_id">Campus</Label>
                            <SearchableSelect id="campus_id" v-model="form.campus_id" :options="campusOptions" placeholder="Select campus" clearable />
                        </div>
                        <div class="space-y-2">
                            <Label for="designation_id">Designation</Label>
                            <SearchableSelect id="designation_id" v-model="form.designation_id" :options="designationOptions" placeholder="Select designation" clearable />
                        </div>
                        <div class="space-y-2">
                            <Label for="employment_type">Employment Type</Label>
                            <select id="employment_type" v-model="form.employment_type" :class="selectClass">
                                <option value="permanent">Permanent</option>
                                <option value="contract">Contract</option>
                                <option value="part_time">Part Time</option>
                                <option value="daily_wage">Daily Wage</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="hire_date">Hire Date</Label>
                            <Input id="hire_date" v-model="form.hire_date" type="date" />
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-border bg-card p-6">
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
                        <Icon icon="dollar-sign" class="h-5 w-5 text-primary" />
                        Salary &amp; Payment
                    </h2>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="basic_salary">Basic Salary</Label>
                            <Input id="basic_salary" v-model="form.basic_salary" type="number" min="0" />
                        </div>
                        <div class="space-y-2">
                            <Label for="allowance_amount">Allowance</Label>
                            <Input id="allowance_amount" v-model="form.allowance_amount" type="number" min="0" />
                        </div>
                        <div class="space-y-2">
                            <Label for="payment_method">Payment Method</Label>
                            <select id="payment_method" v-model="form.payment_method" :class="selectClass">
                                <option value="bank">Bank</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <template v-if="form.payment_method === 'bank'">
                            <div class="space-y-2">
                                <Label for="bank_name">Bank Name</Label>
                                <Input id="bank_name" v-model="form.bank_name" placeholder="Bank name" />
                            </div>
                            <div class="space-y-2">
                                <Label for="account_no">Account No</Label>
                                <Input id="account_no" v-model="form.account_no" placeholder="Account number" />
                            </div>
                        </template>
                    </div>

                    <label class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                        <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-border text-primary" />
                        Staff member is active
                    </label>
                </div>

                <div v-if="documentTypes.length" class="rounded-lg border border-border bg-card p-6">
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
                        <Icon icon="file-text" class="h-5 w-5 text-primary" />
                        Documents
                    </h2>

                    <div class="space-y-4">
                        <div
                            v-for="type in documentTypes"
                            :key="type.id"
                            class="grid grid-cols-1 gap-3 rounded-lg border border-border p-4 md:grid-cols-4"
                        >
                            <div class="space-y-2 md:col-span-1">
                                <Label>
                                    {{ type.name }}
                                    <span v-if="type.is_required" class="text-destructive">*</span>
                                </Label>
                                <input
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    class="w-full text-sm text-foreground"
                                    @change="onDocumentFile(type.id, $event)"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label :for="`issued_on_${type.id}`">Issue Date</Label>
                                <Input :id="`issued_on_${type.id}`" v-model="documents[type.id].issued_on" type="date" />
                            </div>
                            <div class="space-y-2">
                                <Label :for="`expires_on_${type.id}`">Expiry Date</Label>
                                <Input :id="`expires_on_${type.id}`" v-model="documents[type.id].expires_on" type="date" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col justify-end gap-3 pt-4 sm:flex-row">
                    <Button type="button" variant="outline" @click="router.visit(route('staff.people.index'))">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing || !isValid">
                        <Icon v-if="processing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                        <Icon v-else icon="save" class="mr-2 h-4 w-4" />
                        Create Staff
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { useFormValidity } from '@/composables/useFormValidity';
import { alert } from '@/utils';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref } from 'vue';
import { route } from 'ziggy-js';

interface Lookup {
    id: number;
    name: string;
}

interface Campus {
    id: number;
    name: string;
}

interface DocumentType {
    id: number;
    name: string;
    is_required: boolean;
}

interface Props {
    departments: Lookup[];
    designations: Lookup[];
    campuses: Campus[];
    documentTypes?: DocumentType[];
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Staff', href: route('staff.index') },
    { title: 'Directory', href: route('staff.people.index') },
    { title: 'New Staff Member', href: route('staff.people.create') },
];

const selectClass = 'h-11 w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground';

const processing = ref(false);

const form = reactive({
    name: '',
    email: '',
    employee_no: '',
    campus_id: '',
    department_id: '',
    designation_id: '',
    employment_type: 'permanent',
    hire_date: '',
    basic_salary: '',
    allowance_amount: '0',
    payment_method: 'bank',
    bank_name: '',
    account_no: '',
    is_active: true,
});

const campusOptions = computed(() => props.campuses.map((campus) => ({ value: String(campus.id), label: campus.name })));
const designationOptions = computed(() => props.designations.map((d) => ({ value: String(d.id), label: d.name })));
const documentTypes = computed(() => props.documentTypes ?? []);

interface DocumentEntry {
    file: File | null;
    issued_on: string;
    expires_on: string;
}

const documents = reactive<Record<number, DocumentEntry>>({});
documentTypes.value.forEach((type) => {
    documents[type.id] = { file: null, issued_on: '', expires_on: '' };
});

const onDocumentFile = (typeId: number, event: Event) => {
    const target = event.target as HTMLInputElement;
    documents[typeId].file = target.files?.[0] ?? null;
};

const { isValid } = useFormValidity(form, ['name']);

const submitStaff = async () => {
    processing.value = true;

    try {
        const formData = new FormData();

        Object.entries({
            ...form,
            campus_id: form.campus_id || '',
            department_id: form.department_id || '',
            designation_id: form.designation_id || '',
            email: form.email || '',
            employee_no: form.employee_no || '',
        }).forEach(([key, value]) => {
            // Laravel's `boolean` rule accepts 1/0/"1"/"0"/true/false, not the
            // "true"/"false" strings String(value) would produce for checkboxes.
            if (typeof value === 'boolean') {
                formData.append(key, value ? '1' : '0');
            } else {
                formData.append(key, value === null || value === undefined ? '' : String(value));
            }
        });

        documentTypes.value.forEach((type, index) => {
            const entry = documents[type.id];

            if (!entry.file && !entry.issued_on && !entry.expires_on) {
                return;
            }

            formData.append(`documents[${index}][kind]`, type.name);
            formData.append(`documents[${index}][title]`, type.name);

            if (entry.file) {
                formData.append(`documents[${index}][file]`, entry.file);
            }
            if (entry.issued_on) {
                formData.append(`documents[${index}][issued_on]`, entry.issued_on);
            }
            if (entry.expires_on) {
                formData.append(`documents[${index}][expires_on]`, entry.expires_on);
            }
        });

        await axios.post(route('staff.members.store'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        alert.success('Staff member created successfully.');
        router.visit(route('staff.people.index'));
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to save staff member.');
    } finally {
        processing.value = false;
    }
};
</script>
