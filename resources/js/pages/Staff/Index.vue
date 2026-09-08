<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { alert } from '@/utils';
import { tableActionButtonClass } from '@/utils/table-actions';
import type { BreadcrumbItem } from '@/types';

interface Campus {
    id: number;
    name: string;
}

interface MonthOption {
    id: number;
    name: string;
}

interface Department {
    id: number;
    name: string;
    description?: string | null;
    is_active: boolean;
}

interface Designation {
    id: number;
    name: string;
    description?: string | null;
    is_active: boolean;
}

interface StaffMember {
    id: number;
    employee_no: string;
    campus_id?: number | null;
    department_id?: number | null;
    designation_id?: number | null;
    employment_type: string;
    hire_date?: string | null;
    basic_salary: number | string;
    allowance_amount: number | string;
    deduction_amount: number | string;
    payment_method: string;
    bank_name?: string | null;
    account_no?: string | null;
    is_active: boolean;
    user?: {
        id: number;
        name: string;
        email?: string | null;
        is_active?: boolean;
    } | null;
    campus?: {
        id: number;
        name: string;
    } | null;
    department?: {
        id: number;
        name: string;
    } | null;
    designation?: {
        id: number;
        name: string;
    } | null;
    gross_salary: number | string;
    net_salary: number | string;
}

interface PayrollItem {
    id: number;
    staff_profile_id: number;
    gross_salary: number | string;
    allowance_amount: number | string;
    deduction_amount: number | string;
    net_salary: number | string;
    status: string;
    payment_method?: string | null;
    reference_no?: string | null;
    paid_at?: string | null;
    staff_profile?: StaffMember | null;
}

interface PayrollRun {
    id: number;
    title: string;
    campus_id?: number | null;
    payroll_month_id: number;
    payroll_year: number;
    status: string;
    total_gross: number | string;
    total_deductions: number | string;
    total_net: number | string;
    processed_at?: string | null;
    paid_at?: string | null;
    campus?: {
        id: number;
        name: string;
    } | null;
    month?: {
        id: number;
        name: string;
    } | null;
    items: PayrollItem[];
}

interface Props {
    staffMembers: StaffMember[];
    departments: Department[];
    designations: Designation[];
    campuses: Campus[];
    months: MonthOption[];
    payrollRuns: PayrollRun[];
    summary: {
        active_staff: number;
        monthly_salary: number;
        pending_payroll: number;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Staff', href: '/staff' },
];

const selectClass = 'w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground';
const textareaClass = 'min-h-24 w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground';

const activeTab = ref<'staff' | 'payroll' | 'departments' | 'designations'>('staff');
const expandedPayrollRunId = ref<number | null>(null);
const staffSearch = ref('');
const staffCampusFilter = ref('');

const departmentForm = reactive({
    id: null as number | null,
    name: '',
    description: '',
    is_active: true,
});

const designationForm = reactive({
    id: null as number | null,
    name: '',
    description: '',
    is_active: true,
});

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

const payrollForm = reactive({
    campus_id: '',
    payroll_month_id: props.months.find((month) => month.id === new Date().getMonth() + 1)?.id?.toString() ?? '',
    payroll_year: String(new Date().getFullYear()),
    title: '',
});

const formatMoney = (amount: number | string | null | undefined) => {
    const value = Number(amount || 0);

    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 0,
    }).format(value);
};

const formatDate = (value?: string | null) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleDateString('en-PK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const filteredStaff = computed(() => {
    const query = staffSearch.value.trim().toLowerCase();

    return props.staffMembers.filter((member) => {
        const matchesCampus = !staffCampusFilter.value || String(member.campus_id ?? '') === staffCampusFilter.value;
        const matchesQuery = !query || [
            member.user?.name,
            member.user?.email,
            member.employee_no,
            member.department?.name,
            member.designation?.name,
            member.campus?.name,
        ].some((value) => String(value ?? '').toLowerCase().includes(query));

        return matchesCampus && matchesQuery;
    });
});

const filteredPayrollRuns = computed(() => {
    if (!staffCampusFilter.value) {
        return props.payrollRuns;
    }

    return props.payrollRuns.filter((run) => String(run.campus_id ?? '') === staffCampusFilter.value);
});

const reloadPage = (only: string[] = ['staffMembers', 'departments', 'designations', 'payrollRuns', 'summary']) => {
    router.reload({
        only,
        preserveScroll: true,
        preserveState: true,
    });
};

const resetDepartmentForm = () => {
    departmentForm.id = null;
    departmentForm.name = '';
    departmentForm.description = '';
    departmentForm.is_active = true;
};

const resetDesignationForm = () => {
    designationForm.id = null;
    designationForm.name = '';
    designationForm.description = '';
    designationForm.is_active = true;
};

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

const editDepartment = (department: Department) => {
    activeTab.value = 'departments';
    departmentForm.id = department.id;
    departmentForm.name = department.name;
    departmentForm.description = department.description ?? '';
    departmentForm.is_active = department.is_active;
};

const editDesignation = (designation: Designation) => {
    activeTab.value = 'designations';
    designationForm.id = designation.id;
    designationForm.name = designation.name;
    designationForm.description = designation.description ?? '';
    designationForm.is_active = designation.is_active;
};

const editStaff = (member: StaffMember) => {
    activeTab.value = 'staff';
    staffForm.id = member.id;
    staffForm.name = member.user?.name ?? '';
    staffForm.email = member.user?.email ?? '';
    staffForm.employee_no = member.employee_no;
    staffForm.campus_id = member.campus_id ? String(member.campus_id) : '';
    staffForm.department_id = member.department_id ? String(member.department_id) : '';
    staffForm.designation_id = member.designation_id ? String(member.designation_id) : '';
    staffForm.employment_type = member.employment_type;
    staffForm.hire_date = member.hire_date ?? '';
    staffForm.basic_salary = String(member.basic_salary ?? '');
    staffForm.allowance_amount = String(member.allowance_amount ?? 0);
    staffForm.deduction_amount = String(member.deduction_amount ?? 0);
    staffForm.payment_method = member.payment_method;
    staffForm.bank_name = member.bank_name ?? '';
    staffForm.account_no = member.account_no ?? '';
    staffForm.is_active = member.is_active;
};

const submitDepartment = async () => {
    try {
        if (departmentForm.id) {
            await axios.put(route('staff.departments.update', departmentForm.id), departmentForm);
            alert.success('Department updated successfully.');
        } else {
            await axios.post(route('staff.departments.store'), departmentForm);
            alert.success('Department created successfully.');
        }

        resetDepartmentForm();
        reloadPage(['departments']);
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to save department.');
    }
};

const submitDesignation = async () => {
    try {
        if (designationForm.id) {
            await axios.put(route('staff.designations.update', designationForm.id), designationForm);
            alert.success('Designation updated successfully.');
        } else {
            await axios.post(route('staff.designations.store'), designationForm);
            alert.success('Designation created successfully.');
        }

        resetDesignationForm();
        reloadPage(['designations']);
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to save designation.');
    }
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
            bank_name: staffForm.bank_name || null,
            account_no: staffForm.account_no || null,
        };

        if (staffForm.id) {
            await axios.put(route('staff.members.update', staffForm.id), payload);
            alert.success('Staff member updated successfully.');
        } else {
            await axios.post(route('staff.members.store'), payload);
            alert.success('Staff member created successfully.');
        }

        resetStaffForm();
        reloadPage(['staffMembers', 'summary']);
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to save staff member.');
    }
};

const toggleStaff = async (member: StaffMember) => {
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
        reloadPage(['staffMembers', 'summary']);
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to update staff status.');
    }
};

const generatePayroll = async () => {
    try {
        await axios.post(route('staff.payroll.generate'), {
            ...payrollForm,
            campus_id: payrollForm.campus_id || null,
        });
        alert.success('Payroll generated successfully.');
        reloadPage(['payrollRuns', 'summary']);
        activeTab.value = 'payroll';
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to generate payroll.');
    }
};

const markPayrollPaid = async (item: PayrollItem) => {
    if (item.status === 'paid') {
        return;
    }

    const result = await alert.confirm(
        `Mark salary payment as paid for ${item.staff_profile?.user?.name ?? 'this staff member'}?`,
        'Mark Salary Paid',
        'Mark Paid',
    );

    if (!result.isConfirmed) {
        return;
    }

    try {
        await axios.post(route('staff.payroll.items.pay', item.id), {
            payment_method: item.payment_method || 'bank',
            reference_no: item.reference_no || null,
        });
        alert.success('Salary payment marked successfully.');
        reloadPage(['payrollRuns', 'summary']);
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to mark salary as paid.');
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Staff Management" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Staff Management</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Manage staff records, departments, designations, and payroll with finance integration.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" @click="activeTab = 'staff'">
                        <Icon icon="users" class="h-4 w-4" />
                        Staff
                    </Button>
                    <Button variant="outline" @click="activeTab = 'payroll'">
                        <Icon icon="wallet" class="h-4 w-4" />
                        Payroll
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <p class="text-sm text-muted-foreground">Active Staff</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ props.summary.active_staff }}</p>
                </div>
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <p class="text-sm text-muted-foreground">Monthly Salary Cost</p>
                    <p class="mt-2 text-2xl font-bold text-primary">{{ formatMoney(props.summary.monthly_salary) }}</p>
                </div>
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <p class="text-sm text-muted-foreground">Pending Payroll Payable</p>
                    <p class="mt-2 text-2xl font-bold text-destructive">{{ formatMoney(props.summary.pending_payroll) }}</p>
                </div>
            </div>

            <div class="border-b border-border">
                <nav class="-mb-px grid grid-cols-2 gap-x-4 gap-y-1 md:grid-cols-4">
                    <button
                        type="button"
                        @click="activeTab = 'staff'"
                        :class="[
                            activeTab === 'staff' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                            'border-b-2 px-2 py-3 text-sm font-medium'
                        ]"
                    >
                        Staff
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'payroll'"
                        :class="[
                            activeTab === 'payroll' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                            'border-b-2 px-2 py-3 text-sm font-medium'
                        ]"
                    >
                        Payroll
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'departments'"
                        :class="[
                            activeTab === 'departments' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                            'border-b-2 px-2 py-3 text-sm font-medium'
                        ]"
                    >
                        Departments
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'designations'"
                        :class="[
                            activeTab === 'designations' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                            'border-b-2 px-2 py-3 text-sm font-medium'
                        ]"
                    >
                        Designations
                    </button>
                </nav>
            </div>

            <div v-if="activeTab === 'staff'" class="grid gap-6 xl:grid-cols-[420px_minmax(0,1fr)]">
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex flex-wrap gap-2 items-center justify-between">
                        <h2 class="text-lg font-semibold text-foreground">
                            {{ staffForm.id ? 'Edit Staff Member' : 'Create Staff Member' }}
                        </h2>
                        <Button v-if="staffForm.id" variant="outline" size="sm" @click="resetStaffForm">Reset</Button>
                    </div>

                    <div class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Name</label>
                                <Input v-model="staffForm.name" placeholder="Staff name" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Email</label>
                                <Input v-model="staffForm.email" type="email" placeholder="Email (optional)" />
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
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

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Department</label>
                                <select v-model="staffForm.department_id" :class="selectClass">
                                    <option value="">Select department</option>
                                    <option v-for="department in props.departments" :key="department.id" :value="String(department.id)">{{ department.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Designation</label>
                                <select v-model="staffForm.designation_id" :class="selectClass">
                                    <option value="">Select designation</option>
                                    <option v-for="designation in props.designations" :key="designation.id" :value="String(designation.id)">{{ designation.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
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

                        <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-1">
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

                        <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-1">
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
                            <Button variant="outline" @click="resetStaffForm">Clear</Button>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Campus</label>
                                <select v-model="staffCampusFilter" :class="selectClass">
                                    <option value="">All Campuses</option>
                                    <option v-for="campus in props.campuses" :key="campus.id" :value="String(campus.id)">{{ campus.name }}</option>
                                </select>
                            </div>
                            <div class="xl:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Search</label>
                                <Input v-model="staffSearch" placeholder="Search by name, employee no, department, campus..." />
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-border">
                                <thead class="bg-muted">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Employee</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Department / Designation</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Campus</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Salary</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border bg-card">
                                    <tr v-for="member in filteredStaff" :key="member.id" class="hover:bg-accent">
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-foreground">{{ member.user?.name || '-' }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ member.employee_no }}<span v-if="member.user?.email"> | {{ member.user?.email }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground">
                                            <div>{{ member.department?.name || '-' }}</div>
                                            <div class="text-xs text-muted-foreground">{{ member.designation?.name || '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground">{{ member.campus?.name || '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground">
                                            <div>Gross: {{ formatMoney(member.gross_salary) }}</div>
                                            <div class="text-xs text-primary">Net: {{ formatMoney(member.net_salary) }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span :class="member.is_active ? 'bg-success/10 text-success' : 'bg-muted text-muted-foreground'" class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium">
                                                {{ member.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <Button variant="outline" size="sm" :class="tableActionButtonClass.edit" @click="editStaff(member)">
                                                    <Icon icon="square-pen" class="h-3.5 w-3.5" />
                                                    Edit
                                                </Button>
                                                <Button variant="outline" size="sm" :class="member.is_active ? tableActionButtonClass.deactivate : tableActionButtonClass.activate" @click="toggleStaff(member)">
                                                    <Icon :icon="member.is_active ? 'eye-off' : 'eye'" class="h-3.5 w-3.5" />
                                                    {{ member.is_active ? 'Inactive' : 'Active' }}
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredStaff.length === 0">
                                        <td colspan="6" class="px-4 py-10 text-center text-sm text-muted-foreground">No staff members found.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'payroll'" class="space-y-6">
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-muted-foreground">Campus</label>
                            <select v-model="payrollForm.campus_id" :class="selectClass">
                                <option value="">All Campuses</option>
                                <option v-for="campus in props.campuses" :key="campus.id" :value="String(campus.id)">{{ campus.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-muted-foreground">Month</label>
                            <select v-model="payrollForm.payroll_month_id" :class="selectClass">
                                <option value="">Select month</option>
                                <option v-for="month in props.months" :key="month.id" :value="String(month.id)">{{ month.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-muted-foreground">Year</label>
                            <Input v-model="payrollForm.payroll_year" type="number" min="2020" max="2100" />
                        </div>
                        <div class="xl:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-muted-foreground">Title</label>
                            <Input v-model="payrollForm.title" placeholder="Optional payroll title" />
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <Button @click="generatePayroll">
                            <Icon icon="wallet" class="h-4 w-4" />
                            Generate Payroll
                        </Button>
                        <p class="self-center text-sm text-muted-foreground">
                            Generating payroll will post salary expense and salary payable into finance journals.
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="run in filteredPayrollRuns"
                        :key="run.id"
                        class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm"
                    >
                        <div class="flex flex-col gap-4 border-b border-border px-5 py-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-foreground">{{ run.title }}</h3>
                                <p class="text-sm text-muted-foreground">
                                    {{ run.month?.name || 'Month' }} {{ run.payroll_year }} | {{ run.campus?.name || 'All Campuses' }}
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 text-sm">
                                <span :class="run.status === 'paid' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'" class="inline-flex rounded-full px-2.5 py-1 font-medium uppercase">
                                    {{ run.status }}
                                </span>
                                <span class="font-medium text-muted-foreground">Net: {{ formatMoney(run.total_net) }}</span>
                                <Button variant="outline" size="sm" @click="expandedPayrollRunId = expandedPayrollRunId === run.id ? null : run.id">
                                    {{ expandedPayrollRunId === run.id ? 'Hide Items' : 'Show Items' }}
                                </Button>
                            </div>
                        </div>

                        <div class="grid gap-4 border-b border-border px-5 py-4 text-sm md:grid-cols-3">
                            <div>Gross: <span class="font-medium text-foreground">{{ formatMoney(run.total_gross) }}</span></div>
                            <div>Deductions: <span class="font-medium text-destructive">{{ formatMoney(run.total_deductions) }}</span></div>
                            <div>Processed: <span class="font-medium text-foreground">{{ formatDate(run.processed_at) }}</span></div>
                        </div>

                        <div v-if="expandedPayrollRunId === run.id" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-border">
                                <thead class="bg-muted">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Staff</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Gross</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Allowance</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Deduction</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Net</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border bg-card">
                                    <tr v-for="item in run.items" :key="item.id" class="hover:bg-accent">
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-foreground">{{ item.staff_profile?.user?.name || '-' }}</div>
                                            <div class="text-xs text-muted-foreground">{{ item.staff_profile?.employee_no || '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground">{{ formatMoney(item.gross_salary) }}</td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground">{{ formatMoney(item.allowance_amount) }}</td>
                                        <td class="px-4 py-3 text-sm text-destructive">{{ formatMoney(item.deduction_amount) }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-primary">{{ formatMoney(item.net_salary) }}</td>
                                        <td class="px-4 py-3">
                                            <span :class="item.status === 'paid' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'" class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium uppercase">
                                                {{ item.status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    :class="item.status === 'paid' ? tableActionButtonClass.view : tableActionButtonClass.activate"
                                                    :disabled="item.status === 'paid'"
                                                    @click="markPayrollPaid(item)"
                                                >
                                                    <Icon :icon="item.status === 'paid' ? 'check' : 'wallet'" class="h-3.5 w-3.5" />
                                                    {{ item.status === 'paid' ? 'Paid' : 'Mark Paid' }}
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="filteredPayrollRuns.length === 0" class="rounded-2xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                        No payroll runs found yet.
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'departments'" class="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-foreground">{{ departmentForm.id ? 'Edit Department' : 'Create Department' }}</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-muted-foreground">Name</label>
                            <Input v-model="departmentForm.name" placeholder="Department name" />
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-muted-foreground">Description</label>
                            <textarea v-model="departmentForm.description" :class="textareaClass" placeholder="Description (optional)" />
                        </div>
                        <label class="flex items-center gap-2 text-sm text-muted-foreground">
                            <input v-model="departmentForm.is_active" type="checkbox" class="h-4 w-4 rounded border-border text-primary" />
                            Active department
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <Button @click="submitDepartment">{{ departmentForm.id ? 'Update Department' : 'Create Department' }}</Button>
                            <Button variant="outline" @click="resetDepartmentForm">Clear</Button>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Description</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-for="department in props.departments" :key="department.id">
                                    <td class="px-4 py-3 font-medium text-foreground">{{ department.name }}</td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">{{ department.description || '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="department.is_active ? 'bg-success/10 text-success' : 'bg-muted text-muted-foreground'" class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium">
                                            {{ department.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <Button variant="outline" size="sm" :class="tableActionButtonClass.edit" @click="editDepartment(department)">
                                                <Icon icon="square-pen" class="h-3.5 w-3.5" />
                                                Edit
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'designations'" class="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-foreground">{{ designationForm.id ? 'Edit Designation' : 'Create Designation' }}</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-muted-foreground">Name</label>
                            <Input v-model="designationForm.name" placeholder="Designation name" />
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-muted-foreground">Description</label>
                            <textarea v-model="designationForm.description" :class="textareaClass" placeholder="Description (optional)" />
                        </div>
                        <label class="flex items-center gap-2 text-sm text-muted-foreground">
                            <input v-model="designationForm.is_active" type="checkbox" class="h-4 w-4 rounded border-border text-primary" />
                            Active designation
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <Button @click="submitDesignation">{{ designationForm.id ? 'Update Designation' : 'Create Designation' }}</Button>
                            <Button variant="outline" @click="resetDesignationForm">Clear</Button>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Description</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-for="designation in props.designations" :key="designation.id">
                                    <td class="px-4 py-3 font-medium text-foreground">{{ designation.name }}</td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">{{ designation.description || '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="designation.is_active ? 'bg-success/10 text-success' : 'bg-muted text-muted-foreground'" class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium">
                                            {{ designation.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <Button variant="outline" size="sm" :class="tableActionButtonClass.edit" @click="editDesignation(designation)">
                                                <Icon icon="square-pen" class="h-3.5 w-3.5" />
                                                Edit
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
