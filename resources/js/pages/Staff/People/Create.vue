<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="New Staff Member" />

        <div v-if="createdLogin || created" class="space-y-6 p-4 md:p-6">
            <h1 class="text-lg font-bold text-foreground md:text-2xl">Staff member added</h1>
            <LoginDetails v-if="createdLogin" :login="createdLogin" />
            <p v-else class="text-sm text-muted-foreground">No login was made. You can make one later from their profile.</p>
            <div class="flex flex-wrap gap-3">
                <Button @click="router.visit(route('staff.people.show', created!.id))">Open profile</Button>
                <Button variant="outline" @click="router.visit(route('staff.people.index'))">Back to list</Button>
            </div>
        </div>

        <div v-else class="space-y-6 p-4 md:p-6">
            <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center md:gap-4">
                <div>
                    <h1 class="text-lg font-bold text-foreground md:text-2xl">New Staff Member</h1>
                    <p class="mt-1 text-xs text-muted-foreground md:text-sm">Fill this once. Nothing else is needed.</p>
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
                            <Label for="cnic">CNIC</Label>
                            <Input id="cnic" v-model="form.cnic" placeholder="12345-1234567-1" maxlength="15" />
                        </div>
                        <div class="space-y-2">
                            <Label for="phone">Phone</Label>
                            <PhoneInput id="phone" v-model="form.phone" />
                        </div>
                        <div class="space-y-2">
                            <Label for="photo">Photo</Label>
                            <input id="photo" type="file" accept="image/*" class="w-full text-sm text-foreground" @change="onPhoto" />
                        </div>
                        <div v-if="campuses.length" class="space-y-2">
                            <Label for="campus_id">Campus</Label>
                            <CampusSelect id="campus_id" v-model="form.campus_id" :options="campusOptions" placeholder="Select campus" />
                        </div>
                        <div class="space-y-2">
                            <Label for="employee_no">Employee No</Label>
                            <Input id="employee_no" v-model="form.employee_no" placeholder="Made for you if empty" />
                        </div>
                        <div class="space-y-2">
                            <Label for="designation_id">Position</Label>
                            <SearchableSelect id="designation_id" v-model="form.designation_id" :options="designationOptions" placeholder="Select position" clearable />
                            <div class="flex gap-2">
                                <Input v-model="newDesignation" placeholder="Not in the list? Type a new one" @keydown.enter.prevent="addDesignation" />
                                <Button type="button" variant="outline" :disabled="!newDesignation.trim()" @click="addDesignation">Add</Button>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label for="employment_type">Employment Type</Label>
                            <SearchableSelect
                                id="employment_type"
                                v-model="form.employment_type"
                                :options="employmentTypeOptions"
                                placeholder="Select employment type"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="hire_date">Hire Date</Label>
                            <Input id="hire_date" v-model="form.hire_date" type="date" />
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-border bg-card p-6">
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
                        <Icon icon="key" class="h-5 w-5 text-primary" />
                        Login
                    </h2>
                    <label class="flex items-center gap-2 text-sm text-foreground">
                        <input v-model="form.create_login" type="checkbox" class="h-4 w-4 rounded border-border text-primary" />
                        Let this person sign in
                    </label>
                    <div v-if="form.create_login" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="form.email" type="email" placeholder="Leave empty to make one from the name" />
                        </div>
                        <div v-if="linkedRoleLabel" class="space-y-2">
                            <Label>Access level</Label>
                            <p class="text-sm text-muted-foreground">Set from the position: {{ linkedRoleLabel }}.</p>
                        </div>
                        <div v-else class="space-y-2">
                            <Label for="role">Access level (what this person can see/do)</Label>
                            <SearchableSelect id="role" v-model="form.role" :options="roleOptions" placeholder="Select access level" clearable />
                        </div>
                        <p class="text-xs text-muted-foreground md:col-span-2">A password is made for you and shown once after saving.</p>
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
                            <Input id="basic_salary" v-model="form.basic_salary" type="number" min="0" placeholder="e.g. 50000" />
                        </div>
                        <div class="space-y-2">
                            <Label for="allowance_amount">Allowance</Label>
                            <Input id="allowance_amount" v-model="form.allowance_amount" type="number" min="0" placeholder="e.g. 5000" />
                        </div>
                        <div class="space-y-2">
                            <Label for="payment_method">Payment Method</Label>
                            <SearchableSelect
                                id="payment_method"
                                v-model="form.payment_method"
                                :options="paymentMethodOptions"
                                placeholder="Select payment method"
                            />
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
                        Save
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import CampusSelect from '@/components/ui/CampusSelect.vue';
import PhoneInput from '@/components/ui/PhoneInput.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import LoginDetails from '@/components/Staff/LoginDetails.vue';
import { useFormValidity } from '@/composables/useFormValidity';
import { alert } from '@/utils';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';
import { route } from 'ziggy-js';

interface Lookup {
    id: number;
    name: string;
}

interface DocumentType {
    id: number;
    name: string;
    is_required: boolean;
}

interface Props {
    designations: (Lookup & { role?: string | null })[];
    roles?: { name: string; label: string | null }[];
    campuses?: Lookup[];
    defaultCampusId?: number | null;
    documentTypes?: DocumentType[];
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Staff', href: route('staff.index') },
    { title: 'Directory', href: route('staff.people.index') },
    { title: 'New Staff Member', href: route('staff.people.create') },
];

const processing = ref(false);
const createdLogin = ref<{ name: string; username: string; email: string; password: string } | null>(null);
const created = ref<{ id: number } | null>(null);
const designationList = ref<(Lookup & { role?: string | null })[]>([...props.designations]);
const newDesignation = ref('');
const photoFile = ref<File | null>(null);

const onPhoto = (event: Event) => {
    photoFile.value = (event.target as HTMLInputElement).files?.[0] ?? null;
};

const addDesignation = async () => {
    const name = newDesignation.value.trim();

    if (!name) {
        return;
    }

    try {
        const response = await axios.post(route('staff.designations.store'), { name });
        designationList.value.push(response.data.designation);
        form.designation_id = String(response.data.designation.id);
        newDesignation.value = '';
    } catch (error: any) {
        alert.error(error?.response?.data?.errors?.name?.[0] || 'Could not add. Please try again.');
    }
};

const employmentTypeOptions = [
    { value: 'permanent', label: 'Permanent' },
    { value: 'contract', label: 'Contract' },
    { value: 'part_time', label: 'Part Time' },
    { value: 'daily_wage', label: 'Daily Wage' },
];

const paymentMethodOptions = [
    { value: 'bank', label: 'Bank' },
    { value: 'cash', label: 'Cash' },
    { value: 'cheque', label: 'Cheque' },
];

const form = reactive({
    name: '',
    email: '',
    employee_no: '',
    designation_id: '',
    employment_type: 'permanent',
    hire_date: '',
    basic_salary: '',
    allowance_amount: '0',
    payment_method: 'bank',
    bank_name: '',
    account_no: '',
    is_active: true,
    cnic: '',
    phone: '',
    campus_id: props.defaultCampusId ? String(props.defaultCampusId) : '',
    create_login: true,
    role: '',
});

const designationOptions = computed(() => designationList.value.map((d) => ({ value: String(d.id), label: d.name })));
const campuses = computed(() => props.campuses ?? []);
const campusOptions = computed(() => campuses.value.map((c) => ({ value: String(c.id), label: c.name })));
const roleOptions = computed(() => (props.roles ?? []).map((r) => ({ value: r.name, label: r.label || r.name })));
const documentTypes = computed(() => props.documentTypes ?? []);

/** The access role the chosen position already carries, if Staff Settings links one. */
const linkedRole = computed(() => designationList.value.find((d) => String(d.id) === form.designation_id)?.role ?? null);
const linkedRoleLabel = computed(() => {
    if (!linkedRole.value) {
        return '';
    }
    const role = (props.roles ?? []).find((r) => r.name === linkedRole.value);
    return role?.label || role?.name || linkedRole.value;
});

watch(linkedRole, (role) => {
    if (role) {
        form.role = '';
    }
});

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

        if (photoFile.value) {
            formData.append('photo', photoFile.value);
        }

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

        const response = await axios.post(route('staff.members.store'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        alert.success('Staff member added.');
        createdLogin.value = response.data.login ?? null;
        created.value = { id: response.data.staff.id };
    } catch (error: any) {
        const errors = error?.response?.data?.errors;
        alert.error((errors && (Object.values(errors).flat()[0] as string)) || error?.response?.data?.message || 'Could not save. Please try again.');
    } finally {
        processing.value = false;
    }
};
</script>
