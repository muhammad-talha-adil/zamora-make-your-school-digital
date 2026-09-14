<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, reactive, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { alert } from '@/utils';
import { tableActionButtonClass } from '@/utils/table-actions';
import type { BreadcrumbItem } from '@/types';

interface Lookup {
    id: number;
    name: string;
    description?: string | null;
    is_active: boolean;
}

interface Campus {
    id: number;
    name: string;
}

interface StaffRow {
    id: number;
    employee_no: string;
    campus_id?: number | null;
    department_id?: number | null;
    designation_id?: number | null;
    employment_type: string;
    hire_date?: string | null;
    basic_salary?: number | string;
    allowance_amount?: number | string;
    deduction_amount?: number | string;
    payment_method: string;
    is_active: boolean;
    jobs_count: number;
    user?: { id: number; name: string; email?: string | null } | null;
    campus?: { id: number; name: string } | null;
    department?: { id: number; name: string } | null;
    designation?: { id: number; name: string } | null;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
}

interface Props {
    departments: Lookup[];
    designations: Lookup[];
    campuses: Campus[];
    filters: {
        search?: string;
        campus_id?: string;
        department_id?: string;
        designation_id?: string;
        status?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Staff', href: route('staff.index') },
    { title: 'Directory', href: route('staff.people.index') },
];

const selectClass = 'w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground';
const textareaClass = 'min-h-20 w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground';

const filters = reactive({
    search: props.filters.search ?? '',
    campus_id: props.filters.campus_id ?? '',
    department_id: props.filters.department_id ?? '',
    designation_id: props.filters.designation_id ?? '',
    status: props.filters.status ?? 'active',
});

const staff = ref<Paginated<StaffRow>>({ data: [], current_page: 1, last_page: 1, total: 0 });
const loading = ref(false);
const showCreateForm = ref(false);
const showLookups = ref(false);

const staffForm = reactive({
    id: null as number | null,
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
    deduction_amount: '0',
    payment_method: 'bank',
    bank_name: '',
    account_no: '',
    is_active: true,
});

const departmentForm = reactive({ id: null as number | null, name: '', description: '', is_active: true });
const designationForm = reactive({ id: null as number | null, name: '', description: '', is_active: true });

const formatMoney = (amount: number | string | null | undefined) => {
    if (amount === null || amount === undefined) {
        return '—';
    }

    return new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', minimumFractionDigits: 0 }).format(Number(amount));
};

const loadStaff = async (page = 1) => {
    loading.value = true;

    try {
        const response = await axios.get(route('staff.people.list'), {
            params: { ...filters, page },
        });
        staff.value = response.data.data;
    } finally {
        loading.value = false;
    }
};

let debounceTimer: ReturnType<typeof setTimeout> | undefined;
watch(filters, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadStaff(1), 300);
});

onMounted(() => loadStaff());

const resetStaffForm = () => {
    staffForm.id = null;
    staffForm.name = '';
    staffForm.email = '';
    staffForm.employee_no = '';
    staffForm.campus_id = '';
    staffForm.department_id = '';
    staffForm.designation_id = '';
    staffForm.employment_type = 'permanent';
    staffForm.hire_date = '';
    staffForm.basic_salary = '';
    staffForm.allowance_amount = '0';
    staffForm.deduction_amount = '0';
    staffForm.payment_method = 'bank';
    staffForm.bank_name = '';
    staffForm.account_no = '';
    staffForm.is_active = true;
};

const submitStaff = async () => {
    try {
        const payload = {
            ...staffForm,
            campus_id: staffForm.campus_id || null,
            department_id: staffForm.department_id || null,
            designation_id: staffForm.designation_id || null,
            email: staffForm.email || null,
            employee_no: staffForm.employee_no || null,
        };

        if (staffForm.id) {
            await axios.put(route('staff.members.update', staffForm.id), payload);
            alert.success('Staff member updated successfully.');
        } else {
            await axios.post(route('staff.members.store'), payload);
            alert.success('Staff member created successfully.');
        }

        resetStaffForm();
        showCreateForm.value = false;
        loadStaff(staff.value.current_page);
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to save staff member.');
    }
};

const toggleStaff = async (member: StaffRow) => {
    const result = await alert.confirm(
        `Do you want to ${member.is_active ? 'deactivate' : 'activate'} ${member.user?.name ?? 'this staff member'}?`,
        'Update Staff Status',
        member.is_active ? 'Deactivate' : 'Activate',
    );

    if (!result.isConfirmed) {
        return;
    }

    try {
        await axios.patch(route('staff.members.toggle', member.id));
        alert.success('Staff status updated successfully.');
        loadStaff(staff.value.current_page);
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to update staff status.');
    }
};

const submitDepartment = async () => {
    try {
        if (departmentForm.id) {
            await axios.put(route('staff.departments.update', departmentForm.id), departmentForm);
        } else {
            await axios.post(route('staff.departments.store'), departmentForm);
        }
        alert.success('Department saved.');
        departmentForm.id = null;
        departmentForm.name = '';
        departmentForm.description = '';
        departmentForm.is_active = true;
        router.reload({ only: ['departments'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to save department.');
    }
};

const submitDesignation = async () => {
    try {
        if (designationForm.id) {
            await axios.put(route('staff.designations.update', designationForm.id), designationForm);
        } else {
            await axios.post(route('staff.designations.store'), designationForm);
        }
        alert.success('Designation saved.');
        designationForm.id = null;
        designationForm.name = '';
        designationForm.description = '';
        designationForm.is_active = true;
        router.reload({ only: ['designations'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to save designation.');
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Staff Directory" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Staff Directory</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Every record, jobs, campus and department.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('staff.settings.page')" class="inline-flex">
                        <Button variant="outline"><Icon icon="settings" class="h-4 w-4" />Staff Settings</Button>
                    </Link>
                    <Dialog v-model:open="showLookups">
                        <DialogTrigger as-child>
                            <Button variant="outline"><Icon icon="settings" class="h-4 w-4" />Departments &amp; Designations</Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-[640px]">
                            <DialogHeader>
                                <DialogTitle>Departments &amp; Designations</DialogTitle>
                            </DialogHeader>
                            <div class="grid gap-6 md:grid-cols-2">
                                <div>
                                    <h3 class="mb-2 text-sm font-semibold text-foreground">Departments</h3>
                                    <div class="mb-3 space-y-2">
                                        <Input v-model="departmentForm.name" placeholder="Department name" />
                                        <textarea v-model="departmentForm.description" :class="textareaClass" placeholder="Description" />
                                        <div class="flex gap-2">
                                            <Button size="sm" @click="submitDepartment">{{ departmentForm.id ? 'Update' : 'Add' }}</Button>
                                            <Button v-if="departmentForm.id" size="sm" variant="outline" @click="departmentForm.id = null; departmentForm.name = ''; departmentForm.description = ''">Cancel</Button>
                                        </div>
                                    </div>
                                    <ul class="max-h-48 space-y-1 overflow-y-auto text-sm">
                                        <li v-for="d in props.departments" :key="d.id" class="flex items-center justify-between rounded border border-border px-2 py-1.5">
                                            <span>{{ d.name }}</span>
                                            <button type="button" class="text-xs text-primary hover:underline" @click="departmentForm.id = d.id; departmentForm.name = d.name; departmentForm.description = d.description ?? ''; departmentForm.is_active = d.is_active">Edit</button>
                                        </li>
                                    </ul>
                                </div>
                                <div>
                                    <h3 class="mb-2 text-sm font-semibold text-foreground">Designations</h3>
                                    <div class="mb-3 space-y-2">
                                        <Input v-model="designationForm.name" placeholder="Designation name" />
                                        <textarea v-model="designationForm.description" :class="textareaClass" placeholder="Description" />
                                        <div class="flex gap-2">
                                            <Button size="sm" @click="submitDesignation">{{ designationForm.id ? 'Update' : 'Add' }}</Button>
                                            <Button v-if="designationForm.id" size="sm" variant="outline" @click="designationForm.id = null; designationForm.name = ''; designationForm.description = ''">Cancel</Button>
                                        </div>
                                    </div>
                                    <ul class="max-h-48 space-y-1 overflow-y-auto text-sm">
                                        <li v-for="d in props.designations" :key="d.id" class="flex items-center justify-between rounded border border-border px-2 py-1.5">
                                            <span>{{ d.name }}</span>
                                            <button type="button" class="text-xs text-primary hover:underline" @click="designationForm.id = d.id; designationForm.name = d.name; designationForm.description = d.description ?? ''; designationForm.is_active = d.is_active">Edit</button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </DialogContent>
                    </Dialog>
                    <Button @click="resetStaffForm(); showCreateForm = true">
                        <Icon icon="user-plus" class="h-4 w-4" />
                        New Staff Member
                    </Button>
                </div>
            </div>

            <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <div class="xl:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Search</label>
                        <Input v-model="filters.search" placeholder="Name, employee no, CNIC, phone..." />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Campus</label>
                        <select v-model="filters.campus_id" :class="selectClass">
                            <option value="">All Campuses</option>
                            <option v-for="campus in props.campuses" :key="campus.id" :value="String(campus.id)">{{ campus.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Department</label>
                        <select v-model="filters.department_id" :class="selectClass">
                            <option value="">All Departments</option>
                            <option v-for="d in props.departments" :key="d.id" :value="String(d.id)">{{ d.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Status</label>
                        <select v-model="filters.status" :class="selectClass">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="all">All</option>
                        </select>
                    </div>
                </div>
            </div>

            <Dialog v-model:open="showCreateForm">
                <DialogContent class="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>{{ staffForm.id ? 'Edit Staff Member' : 'New Staff Member' }}</DialogTitle>
                    </DialogHeader>
                    <div class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Name</label>
                                <Input v-model="staffForm.name" placeholder="Staff name" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Email</label>
                                <Input v-model="staffForm.email" type="email" placeholder="Email (optional)" />
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Employee No</label>
                                <Input v-model="staffForm.employee_no" placeholder="Auto-generate if empty" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Campus</label>
                                <select v-model="staffForm.campus_id" :class="selectClass">
                                    <option value="">Select campus</option>
                                    <option v-for="campus in props.campuses" :key="campus.id" :value="String(campus.id)">{{ campus.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Department</label>
                                <select v-model="staffForm.department_id" :class="selectClass">
                                    <option value="">Select department</option>
                                    <option v-for="d in props.departments" :key="d.id" :value="String(d.id)">{{ d.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Designation</label>
                                <select v-model="staffForm.designation_id" :class="selectClass">
                                    <option value="">Select designation</option>
                                    <option v-for="d in props.designations" :key="d.id" :value="String(d.id)">{{ d.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Employment Type</label>
                                <select v-model="staffForm.employment_type" :class="selectClass">
                                    <option value="permanent">Permanent</option>
                                    <option value="contract">Contract</option>
                                    <option value="part_time">Part Time</option>
                                    <option value="daily_wage">Daily Wage</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Hire Date</label>
                                <Input v-model="staffForm.hire_date" type="date" />
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Basic Salary</label>
                                <Input v-model="staffForm.basic_salary" type="number" min="0" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Allowance</label>
                                <Input v-model="staffForm.allowance_amount" type="number" min="0" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Deduction</label>
                                <Input v-model="staffForm.deduction_amount" type="number" min="0" />
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Payment Method</label>
                                <select v-model="staffForm.payment_method" :class="selectClass">
                                    <option value="bank">Bank</option>
                                    <option value="cash">Cash</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Bank Name</label>
                                <Input v-model="staffForm.bank_name" placeholder="Bank name" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Account No</label>
                                <Input v-model="staffForm.account_no" placeholder="Account number" />
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-muted-foreground">
                            <input v-model="staffForm.is_active" type="checkbox" class="h-4 w-4 rounded border-border text-primary" />
                            Staff member is active
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <Button @click="submitStaff">
                                <Icon icon="save" class="h-4 w-4" />
                                {{ staffForm.id ? 'Update Staff' : 'Create Staff' }}
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Employee</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Department / Designation</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Campus</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Jobs</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="member in staff.data" :key="member.id" class="hover:bg-accent">
                                <td class="px-4 py-3">
                                    <Link :href="route('staff.people.show', member.id)" class="font-medium text-foreground hover:underline">
                                        {{ member.user?.name || '-' }}
                                    </Link>
                                    <div class="text-xs text-muted-foreground">
                                        {{ member.employee_no }}<span v-if="member.user?.email"> | {{ member.user?.email }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">
                                    <div>{{ member.department?.name || '-' }}</div>
                                    <div class="text-xs text-muted-foreground">{{ member.designation?.name || '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ member.campus?.name || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ member.jobs_count }}</td>
                                <td class="px-4 py-3">
                                    <span :class="member.is_active ? 'bg-success/10 text-success' : 'bg-muted text-muted-foreground'" class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium">
                                        {{ member.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <Button variant="outline" size="sm" :class="tableActionButtonClass.view" @click="router.visit(route('staff.people.show', member.id))">
                                            <Icon icon="eye" class="h-3.5 w-3.5" />
                                            View
                                        </Button>
                                        <Button variant="outline" size="sm" :class="member.is_active ? tableActionButtonClass.deactivate : tableActionButtonClass.activate" @click="toggleStaff(member)">
                                            <Icon :icon="member.is_active ? 'eye-off' : 'eye'" class="h-3.5 w-3.5" />
                                            {{ member.is_active ? 'Deactivate' : 'Activate' }}
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!loading && staff.data.length === 0">
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-muted-foreground">No staff members found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="staff.last_page > 1" class="flex items-center justify-between border-t border-border px-4 py-3 text-sm text-muted-foreground">
                    <span>Page {{ staff.current_page }} of {{ staff.last_page }} ({{ staff.total }} total)</span>
                    <div class="flex gap-2">
                        <Button variant="outline" size="sm" :disabled="staff.current_page <= 1" @click="loadStaff(staff.current_page - 1)">Previous</Button>
                        <Button variant="outline" size="sm" :disabled="staff.current_page >= staff.last_page" @click="loadStaff(staff.current_page + 1)">Next</Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
