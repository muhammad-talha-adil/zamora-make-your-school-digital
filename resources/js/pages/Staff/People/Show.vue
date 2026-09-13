<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { alert } from '@/utils';
import type { BreadcrumbItem } from '@/types';

interface Lookup {
    id: number;
    name: string;
}

interface Job {
    id: number;
    is_primary: boolean;
    started_on?: string | null;
    ended_on?: string | null;
    notes?: string | null;
    designation?: Lookup | null;
    department?: Lookup | null;
    campus?: Lookup | null;
}

interface ClassAssignment {
    id: number;
    is_class_teacher: boolean;
    periods_per_week?: number | null;
    schoolClass?: Lookup | null;
    section?: Lookup | null;
    subject?: Lookup | null;
    session?: Lookup | null;
}

interface Qualification {
    id: number;
    title: string;
    institution?: string | null;
    year_completed?: number | null;
    grade?: string | null;
}

interface StaffDocumentRow {
    id: number;
    kind: string;
    title: string;
    reference_no?: string | null;
    issued_on?: string | null;
    expires_on?: string | null;
    path?: string | null;
    uploadedBy?: { name: string } | null;
}

interface SubjectCapability {
    id: number;
    is_primary: boolean;
    subject?: Lookup | null;
}

interface EmploymentPeriod {
    id: number;
    joined_on?: string | null;
    left_on?: string | null;
    leaving_reason?: string | null;
}

interface StaffDetail {
    id: number;
    employee_no: string;
    cnic?: string | null;
    phone?: string | null;
    dob?: string | null;
    blood_group?: string | null;
    address?: string | null;
    emergency_contact_name?: string | null;
    emergency_contact_phone?: string | null;
    emergency_contact_relation?: string | null;
    employment_type: string;
    hire_date?: string | null;
    basic_salary?: number | string;
    allowance_amount?: number | string;
    deduction_amount?: number | string;
    payment_method: string;
    is_active: boolean;
    user?: { id: number; name: string; email?: string | null; username?: string | null; is_active?: boolean } | null;
    campus?: Lookup | null;
    department?: Lookup | null;
    designation?: Lookup | null;
    gender?: Lookup | null;
    qualifications: Qualification[];
    subjects: SubjectCapability[];
    documents: StaffDocumentRow[];
    employmentPeriods: EmploymentPeriod[];
}

interface Props {
    staff: StaffDetail;
    jobs: Job[];
    jobHistory: Job[];
    classes: ClassAssignment[];
    serviceMonths: number;
    departments: Lookup[];
    designations: Lookup[];
    campuses: Lookup[];
    genders: Lookup[];
    subjectOptions: Lookup[];
    classOptions: Lookup[];
    sessions: Lookup[];
    can: {
        edit: boolean;
        viewSalary: boolean;
        manageSalary: boolean;
        viewAttendance: boolean;
        markAttendance: boolean;
        applyForLeave: boolean;
        decideLeave: boolean;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Staff', href: route('staff.index') },
    { title: 'Directory', href: route('staff.people.index') },
    { title: props.staff.user?.name ?? 'Profile', href: route('staff.people.show', props.staff.id) },
];

const selectClass = 'w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground';

type TabKey = 'personal' | 'jobs' | 'documents' | 'teaching' | 'attendance' | 'leave' | 'salary';
const activeTab = ref<TabKey>('personal');

const tabs: { key: TabKey; label: string; show: boolean }[] = [
    { key: 'personal', label: 'Personal', show: true },
    { key: 'jobs', label: 'Jobs', show: true },
    { key: 'documents', label: 'Documents', show: true },
    { key: 'teaching', label: 'Teaching', show: true },
    { key: 'attendance', label: 'Attendance', show: props.can.viewAttendance },
    { key: 'leave', label: 'Leave', show: props.can.viewAttendance || props.can.applyForLeave },
    { key: 'salary', label: 'Salary', show: props.can.viewSalary },
];

const formatMoney = (amount: number | string | null | undefined) => {
    if (amount === null || amount === undefined) {
        return '—';
    }
    return new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', minimumFractionDigits: 0 }).format(Number(amount));
};

const formatDate = (value?: string | null) => {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('en-PK', { year: 'numeric', month: 'short', day: 'numeric' });
};

/* --------------------------------------------------------------- personal */

const personalForm = reactive({
    cnic: props.staff.cnic ?? '',
    phone: props.staff.phone ?? '',
    dob: props.staff.dob ?? '',
    gender_id: props.staff.gender?.id ? String(props.staff.gender.id) : '',
    blood_group: props.staff.blood_group ?? '',
    address: props.staff.address ?? '',
    emergency_contact_name: props.staff.emergency_contact_name ?? '',
    emergency_contact_phone: props.staff.emergency_contact_phone ?? '',
    emergency_contact_relation: props.staff.emergency_contact_relation ?? '',
});

const savePersonal = async () => {
    try {
        await axios.put(route('staff.people.personal', props.staff.id), personalForm);
        alert.success('Details saved.');
        router.reload({ only: ['staff'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to save details.');
    }
};

/* ------------------------------------------------------------ leave/rejoin */

const showLeaveDialog = ref(false);
const showRejoinDialog = ref(false);
const employmentSaving = ref(false);

const leaveEmploymentForm = reactive({ left_on: new Date().toISOString().slice(0, 10), leaving_reason: '', notes: '' });
const rejoinEmploymentForm = reactive({ joined_on: new Date().toISOString().slice(0, 10), campus_id: props.staff.campus?.id ? String(props.staff.campus.id) : '' });

const markAsLeft = async () => {
    employmentSaving.value = true;
    try {
        await axios.post(route('staff.people.leave', props.staff.id), leaveEmploymentForm);
        alert.success('Staff member marked as left.');
        showLeaveDialog.value = false;
        router.reload({ only: ['staff'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to record leaving.');
    } finally {
        employmentSaving.value = false;
    }
};

const rejoinStaff = async () => {
    employmentSaving.value = true;
    try {
        await axios.post(route('staff.people.rejoin', props.staff.id), rejoinEmploymentForm);
        alert.success('Staff member rejoined.');
        showRejoinDialog.value = false;
        router.reload({ only: ['staff'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to record rejoining.');
    } finally {
        employmentSaving.value = false;
    }
};

/* -------------------------------------------------------------------- jobs */

const jobForm = reactive({
    designation_id: '',
    department_id: '',
    campus_id: '',
    is_primary: false,
    started_on: '',
    notes: '',
});

const addJob = async () => {
    try {
        await axios.post(route('staff.people.jobs.add', props.staff.id), jobForm);
        alert.success('Job added.');
        jobForm.designation_id = '';
        jobForm.department_id = '';
        jobForm.campus_id = '';
        jobForm.is_primary = false;
        jobForm.started_on = '';
        jobForm.notes = '';
        router.reload({ only: ['jobs', 'jobHistory'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to add job.');
    }
};

const makePrimary = async (job: Job) => {
    try {
        await axios.patch(route('staff.jobs.primary', job.id));
        alert.success('Primary job changed.');
        router.reload({ only: ['jobs'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to change primary job.');
    }
};

const endJob = async (job: Job) => {
    const result = await alert.confirm('End this job?', 'End Job', 'End Job');
    if (!result.isConfirmed) return;

    try {
        await axios.patch(route('staff.jobs.end', job.id), {});
        alert.success('Job ended.');
        router.reload({ only: ['jobs', 'jobHistory'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to end job.');
    }
};

/* --------------------------------------------------------- qualifications */

const qualificationForm = reactive({ title: '', institution: '', year_completed: '', grade: '' });

const addQualification = async () => {
    try {
        await axios.post(route('staff.people.qualifications.add', props.staff.id), qualificationForm);
        alert.success('Qualification added.');
        qualificationForm.title = '';
        qualificationForm.institution = '';
        qualificationForm.year_completed = '';
        qualificationForm.grade = '';
        router.reload({ only: ['staff'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to add qualification.');
    }
};

const removeQualification = async (id: number) => {
    const result = await alert.confirm('Remove this qualification?', 'Remove Qualification', 'Remove');
    if (!result.isConfirmed) return;

    await axios.delete(route('staff.qualifications.remove', id));
    router.reload({ only: ['staff'] });
};

/* -------------------------------------------------------------- documents */

const documentForm = reactive({ kind: '', title: '', reference_no: '', issued_on: '', expires_on: '', file: null as File | null });

const onDocumentFile = (event: Event) => {
    documentForm.file = (event.target as HTMLInputElement).files?.[0] ?? null;
};

const addDocument = async () => {
    try {
        const payload = new FormData();
        payload.append('kind', documentForm.kind);
        payload.append('title', documentForm.title);
        if (documentForm.reference_no) payload.append('reference_no', documentForm.reference_no);
        if (documentForm.issued_on) payload.append('issued_on', documentForm.issued_on);
        if (documentForm.expires_on) payload.append('expires_on', documentForm.expires_on);
        if (documentForm.file) payload.append('file', documentForm.file);

        await axios.post(route('staff.people.documents.add', props.staff.id), payload, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        alert.success('Document filed.');
        documentForm.kind = '';
        documentForm.title = '';
        documentForm.reference_no = '';
        documentForm.issued_on = '';
        documentForm.expires_on = '';
        documentForm.file = null;
        router.reload({ only: ['staff'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to file document.');
    }
};

const removeDocument = async (id: number) => {
    const result = await alert.confirm('Remove this document?', 'Remove Document', 'Remove');
    if (!result.isConfirmed) return;

    await axios.delete(route('staff.documents.remove', id));
    router.reload({ only: ['staff'] });
};

/* --------------------------------------------------------------- teaching */

const classForm = reactive({
    session_id: props.sessions[0]?.id ? String(props.sessions[0].id) : '',
    class_id: '',
    section_id: '',
    subject_id: '',
    is_class_teacher: false,
    periods_per_week: '',
});

const assignClass = async () => {
    try {
        await axios.post(route('staff.teaching.assign', props.staff.id), classForm);
        alert.success('Class assigned.');
        classForm.class_id = '';
        classForm.section_id = '';
        classForm.subject_id = '';
        classForm.is_class_teacher = false;
        classForm.periods_per_week = '';
        router.reload({ only: ['classes'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to assign class.');
    }
};

const unassignClass = async (assignment: ClassAssignment) => {
    const result = await alert.confirm('Take this class away?', 'Unassign Class', 'Unassign');
    if (!result.isConfirmed) return;

    await axios.delete(route('staff.teaching.unassign', assignment.id));
    router.reload({ only: ['classes'] });
};

const subjectCapabilityId = ref('');

const addSubjectCapability = async () => {
    if (!subjectCapabilityId.value) return;

    try {
        await axios.post(route('staff.teaching.subjects.add', props.staff.id), { subject_id: subjectCapabilityId.value });
        alert.success('Subject added.');
        subjectCapabilityId.value = '';
        router.reload({ only: ['staff'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to add subject.');
    }
};

const removeSubjectCapability = async (subjectId: number) => {
    await axios.delete(route('staff.teaching.subjects.remove', { staffProfile: props.staff.id, subjectId }));
    router.reload({ only: ['staff'] });
};

/* ------------------------------------------------------------ attendance */

interface AttendanceRow {
    id: number;
    attendance_date: string;
    check_in_at?: string | null;
    check_out_at?: string | null;
    minutes_late: number;
    status?: { name: string; code: string } | null;
}

const attendanceRows = ref<AttendanceRow[]>([]);
const attendanceLoaded = ref(false);
const attendanceSummary = ref<Record<string, number> | null>(null);
const attendanceStatuses = ref<{ id: number; name: string; code: string }[]>([]);

const markForm = reactive({
    attendance_date: new Date().toISOString().slice(0, 10),
    attendance_status_id: '',
    check_in_at: '',
    check_out_at: '',
    remarks: '',
});

const loadAttendance = async () => {
    const [rows, statuses] = await Promise.all([
        axios.get(route('staff.attendance.index', props.staff.id)),
        attendanceStatuses.value.length ? Promise.resolve(null) : axios.get(route('staff.attendance.statuses')),
    ]);

    attendanceRows.value = rows.data.data;
    if (statuses) attendanceStatuses.value = statuses.data.data;
    attendanceLoaded.value = true;
};

const markAttendance = async () => {
    try {
        await axios.post(route('staff.attendance.store', props.staff.id), markForm);
        alert.success('Attendance marked.');
        loadAttendance();
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to mark attendance.');
    }
};

const loadAttendanceSummary = async () => {
    const now = new Date();
    const response = await axios.get(route('staff.attendance.summary', props.staff.id), {
        params: { from_month: now.getMonth() + 1, to_month: now.getMonth() + 1, year: now.getFullYear() },
    });
    attendanceSummary.value = response.data.data;
};

/* ------------------------------------------------------------------ leave */

interface LeaveRow {
    id: number;
    from_date: string;
    to_date: string;
    days: number | string;
    status: string;
    reason?: string | null;
    leaveType?: { name: string } | null;
}

const leaveRows = ref<LeaveRow[]>([]);
const leaveLoaded = ref(false);
const leaveTypes = ref<{ id: number; name: string; days_per_year: number | null }[]>([]);
const leaveBalances = ref<{ leave_type: { id: number; name: string; code: string }; remaining: number | null }[]>([]);

const leaveForm = reactive({ staff_leave_type_id: '', from_date: '', to_date: '', reason: '' });

const loadLeave = async () => {
    const [rows, types, balances] = await Promise.all([
        axios.get(route('staff.leaves.index', props.staff.id)),
        leaveTypes.value.length ? Promise.resolve(null) : axios.get(route('staff.leaves.types')),
        axios.get(route('staff.leaves.balance', props.staff.id), { params: { year: new Date().getFullYear() } }),
    ]);

    leaveRows.value = rows.data.data;
    if (types) leaveTypes.value = types.data.data;
    leaveBalances.value = balances.data.data;
    leaveLoaded.value = true;
};

const applyLeave = async () => {
    try {
        await axios.post(route('staff.leaves.store', props.staff.id), leaveForm);
        alert.success('Leave application submitted.');
        leaveForm.staff_leave_type_id = '';
        leaveForm.from_date = '';
        leaveForm.to_date = '';
        leaveForm.reason = '';
        loadLeave();
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to submit leave application.');
    }
};

const decideLeave = async (leave: LeaveRow, approve: boolean) => {
    try {
        await axios.patch(route('staff.leaves.decide', leave.id), { approve });
        alert.success(approve ? 'Leave approved.' : 'Leave rejected.');
        loadLeave();
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to decide on leave.');
    }
};

const cancelLeave = async (leave: LeaveRow) => {
    const result = await alert.confirm('Cancel this application?', 'Cancel Leave', 'Cancel Application');
    if (!result.isConfirmed) return;

    await axios.patch(route('staff.leaves.cancel', leave.id));
    loadLeave();
};

/* ----------------------------------------------------------------- salary */

interface SalaryComponent {
    id: number;
    amount: number | string;
    effective_from: string;
    effective_to?: string | null;
    salaryHead?: { id: number; name: string; type: string } | null;
}

const salaryComponents = ref<SalaryComponent[]>([]);
const salaryHeads = ref<{ id: number; name: string; type: string }[]>([]);
const salaryGross = ref<number | null>(null);
const salaryDeductions = ref<number | null>(null);
const salaryLoaded = ref(false);

const componentForm = reactive({ salary_head_id: '', amount: '', effective_from: new Date().toISOString().slice(0, 10) });

const loadSalary = async () => {
    const response = await axios.get(route('staff.salary.index', props.staff.id));
    salaryComponents.value = response.data.data;
    salaryHeads.value = response.data.heads;
    salaryGross.value = response.data.gross;
    salaryDeductions.value = response.data.deductions;
    salaryLoaded.value = true;
};

const setComponent = async () => {
    try {
        await axios.post(route('staff.salary.store', props.staff.id), componentForm);
        alert.success('Salary component set.');
        componentForm.salary_head_id = '';
        componentForm.amount = '';
        loadSalary();
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to set salary component.');
    }
};

const endComponent = async (component: SalaryComponent) => {
    const result = await alert.confirm('End this component from today?', 'End Component', 'End');
    if (!result.isConfirmed) return;

    await axios.patch(route('staff.salary.end', component.id));
    loadSalary();
};

const switchTab = (tab: TabKey) => {
    activeTab.value = tab;

    if (tab === 'attendance' && !attendanceLoaded.value) {
        loadAttendance();
        loadAttendanceSummary();
    } else if (tab === 'leave' && !leaveLoaded.value) {
        loadLeave();
    } else if (tab === 'salary' && !salaryLoaded.value && props.can.viewSalary) {
        loadSalary();
    }
};

const activeJobsCount = computed(() => props.jobs.length);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="props.staff.user?.name ?? 'Staff Profile'" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">{{ props.staff.user?.name || 'Staff Member' }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ props.staff.employee_no }} · {{ props.staff.designation?.name || 'No designation' }} · {{ props.staff.campus?.name || 'School-wide' }}
                        · {{ activeJobsCount }} active job(s) · {{ props.serviceMonths }} month(s) of service
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span :class="props.staff.is_active ? 'bg-success/10 text-success' : 'bg-muted text-muted-foreground'" class="inline-flex rounded-full px-3 py-1 text-sm font-medium">
                        {{ props.staff.is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <Button v-if="props.can.edit && props.staff.is_active" variant="outline" size="sm" @click="showLeaveDialog = true">
                        Mark as Left
                    </Button>
                    <Button v-else-if="props.can.edit" variant="outline" size="sm" @click="showRejoinDialog = true">
                        Rejoin
                    </Button>
                </div>
            </div>

            <Dialog v-model:open="showLeaveDialog">
                <DialogContent class="sm:max-w-[480px]">
                    <form @submit.prevent="markAsLeft" class="space-y-4">
                        <DialogHeader>
                            <DialogTitle>Mark as Left</DialogTitle>
                            <DialogDescription>
                                Records {{ props.staff.user?.name || 'this staff member' }} as having left. Their jobs and class assignments will be ended.
                            </DialogDescription>
                        </DialogHeader>
                        <div class="space-y-3">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Left On</label>
                                <Input v-model="leaveEmploymentForm.left_on" type="date" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Reason</label>
                                <Input v-model="leaveEmploymentForm.leaving_reason" placeholder="e.g. resigned, terminated" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Notes</label>
                                <Input v-model="leaveEmploymentForm.notes" placeholder="Optional notes" />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" @click="showLeaveDialog = false">Cancel</Button>
                            <Button type="submit" variant="destructive" :disabled="employmentSaving">
                                {{ employmentSaving ? 'Saving...' : 'Mark as Left' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="showRejoinDialog">
                <DialogContent class="sm:max-w-[480px]">
                    <form @submit.prevent="rejoinStaff" class="space-y-4">
                        <DialogHeader>
                            <DialogTitle>Rejoin</DialogTitle>
                            <DialogDescription>
                                Records {{ props.staff.user?.name || 'this staff member' }} as taken back on.
                            </DialogDescription>
                        </DialogHeader>
                        <div class="space-y-3">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Joined On</label>
                                <Input v-model="rejoinEmploymentForm.joined_on" type="date" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Campus</label>
                                <select v-model="rejoinEmploymentForm.campus_id" :class="selectClass">
                                    <option value="">School-wide</option>
                                    <option v-for="c in props.campuses" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                                </select>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" @click="showRejoinDialog = false">Cancel</Button>
                            <Button type="submit" :disabled="employmentSaving">
                                {{ employmentSaving ? 'Saving...' : 'Rejoin' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <div class="border-b border-border">
                <nav class="-mb-px flex flex-wrap gap-x-4 gap-y-1">
                    <button
                        v-for="tab in tabs.filter((t) => t.show)"
                        :key="tab.key"
                        type="button"
                        @click="switchTab(tab.key)"
                        :class="[
                            activeTab === tab.key ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
                            'border-b-2 px-2 py-3 text-sm font-medium',
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <!-- Personal -->
            <div v-if="activeTab === 'personal'" class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-foreground">Personal Details</h2>
                    <div class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">CNIC</label>
                                <Input v-model="personalForm.cnic" placeholder="00000-0000000-0" :disabled="!props.can.edit" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Phone</label>
                                <Input v-model="personalForm.phone" :disabled="!props.can.edit" />
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Date of Birth</label>
                                <Input v-model="personalForm.dob" type="date" :disabled="!props.can.edit" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Gender</label>
                                <select v-model="personalForm.gender_id" :class="selectClass" :disabled="!props.can.edit">
                                    <option value="">Select</option>
                                    <option v-for="g in props.genders" :key="g.id" :value="String(g.id)">{{ g.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-muted-foreground">Blood Group</label>
                            <Input v-model="personalForm.blood_group" placeholder="e.g. O+" :disabled="!props.can.edit" />
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-muted-foreground">Address</label>
                            <Input v-model="personalForm.address" :disabled="!props.can.edit" />
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Emergency Contact</label>
                                <Input v-model="personalForm.emergency_contact_name" :disabled="!props.can.edit" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Phone</label>
                                <Input v-model="personalForm.emergency_contact_phone" :disabled="!props.can.edit" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-muted-foreground">Relation</label>
                                <Input v-model="personalForm.emergency_contact_relation" :disabled="!props.can.edit" />
                            </div>
                        </div>
                        <Button v-if="props.can.edit" @click="savePersonal">
                            <Icon icon="save" class="h-4 w-4" />
                            Save Details
                        </Button>
                    </div>
                </div>

                <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-foreground">Qualifications</h2>
                    <ul class="mb-4 space-y-2">
                        <li v-for="q in props.staff.qualifications" :key="q.id" class="flex items-center justify-between rounded border border-border px-3 py-2 text-sm">
                            <div>
                                <div class="font-medium text-foreground">{{ q.title }}</div>
                                <div class="text-xs text-muted-foreground">{{ q.institution || '-' }} · {{ q.year_completed || '-' }} · {{ q.grade || '-' }}</div>
                            </div>
                            <button v-if="props.can.edit" type="button" class="text-xs text-destructive hover:underline" @click="removeQualification(q.id)">Remove</button>
                        </li>
                        <li v-if="props.staff.qualifications.length === 0" class="text-sm text-muted-foreground">No qualifications on file.</li>
                    </ul>
                    <div v-if="props.can.edit" class="grid gap-2 md:grid-cols-2">
                        <Input v-model="qualificationForm.title" placeholder="Title (e.g. M.Sc Physics)" />
                        <Input v-model="qualificationForm.institution" placeholder="Institution" />
                        <Input v-model="qualificationForm.year_completed" type="number" placeholder="Year" />
                        <Input v-model="qualificationForm.grade" placeholder="Grade" />
                        <Button size="sm" class="md:col-span-2" @click="addQualification">Add Qualification</Button>
                    </div>
                </div>
            </div>

            <!-- Jobs -->
            <div v-if="activeTab === 'jobs'" class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
                <div v-if="props.can.edit" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-foreground">Give a Job</h2>
                    <div class="space-y-3">
                        <select v-model="jobForm.designation_id" :class="selectClass">
                            <option value="">Select designation</option>
                            <option v-for="d in props.designations" :key="d.id" :value="String(d.id)">{{ d.name }}</option>
                        </select>
                        <select v-model="jobForm.department_id" :class="selectClass">
                            <option value="">Select department</option>
                            <option v-for="d in props.departments" :key="d.id" :value="String(d.id)">{{ d.name }}</option>
                        </select>
                        <select v-model="jobForm.campus_id" :class="selectClass">
                            <option value="">School-wide</option>
                            <option v-for="c in props.campuses" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                        </select>
                        <Input v-model="jobForm.started_on" type="date" />
                        <label class="flex items-center gap-2 text-sm text-muted-foreground">
                            <input v-model="jobForm.is_primary" type="checkbox" class="h-4 w-4 rounded border-border text-primary" />
                            Make this the primary job
                        </label>
                        <Button @click="addJob">Add Job</Button>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                        <div class="border-b border-border px-5 py-3"><h3 class="font-semibold text-foreground">Current Jobs</h3></div>
                        <table class="min-w-full divide-y divide-border">
                            <tbody class="divide-y divide-border">
                                <tr v-for="job in props.jobs" :key="job.id">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-foreground">{{ job.designation?.name || '-' }}
                                            <span v-if="job.is_primary" class="ml-2 rounded-full bg-primary/10 px-2 py-0.5 text-xs text-primary">Primary</span>
                                        </div>
                                        <div class="text-xs text-muted-foreground">{{ job.department?.name || '-' }} · {{ job.campus?.name || 'School-wide' }} · since {{ formatDate(job.started_on) }}</div>
                                    </td>
                                    <td v-if="props.can.edit" class="px-4 py-3 text-right">
                                        <Button v-if="!job.is_primary" variant="outline" size="sm" @click="makePrimary(job)">Make Primary</Button>
                                        <Button variant="outline" size="sm" class="ml-2" @click="endJob(job)">End</Button>
                                    </td>
                                </tr>
                                <tr v-if="props.jobs.length === 0"><td class="px-4 py-6 text-center text-sm text-muted-foreground">No active jobs.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="props.jobHistory.length > 0" class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                        <div class="border-b border-border px-5 py-3"><h3 class="font-semibold text-foreground">Job History</h3></div>
                        <table class="min-w-full divide-y divide-border">
                            <tbody class="divide-y divide-border">
                                <tr v-for="job in props.jobHistory" :key="job.id">
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ job.designation?.name || '-' }} · {{ formatDate(job.started_on) }} - {{ formatDate(job.ended_on) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Documents -->
            <div v-if="activeTab === 'documents'" class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
                <div v-if="props.can.edit" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-foreground">File a Document</h2>
                    <div class="space-y-3">
                        <Input v-model="documentForm.kind" placeholder="Kind (e.g. contract, cnic, police-verification)" />
                        <Input v-model="documentForm.title" placeholder="Title" />
                        <Input v-model="documentForm.reference_no" placeholder="Reference no (optional)" />
                        <Input v-model="documentForm.issued_on" type="date" placeholder="Issued on" />
                        <Input v-model="documentForm.expires_on" type="date" placeholder="Expires on" />
                        <input type="file" accept=".pdf,.jpg,.jpeg,.png" class="text-sm" @change="onDocumentFile" />
                        <Button @click="addDocument">File Document</Button>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">Kind</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">Expires</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="doc in props.staff.documents" :key="doc.id">
                                <td class="px-4 py-3 text-sm text-foreground">{{ doc.title }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ doc.kind }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ formatDate(doc.expires_on) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button v-if="props.can.edit" type="button" class="text-xs text-destructive hover:underline" @click="removeDocument(doc.id)">Remove</button>
                                </td>
                            </tr>
                            <tr v-if="props.staff.documents.length === 0"><td colspan="4" class="px-4 py-6 text-center text-sm text-muted-foreground">No documents on file.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Teaching -->
            <div v-if="activeTab === 'teaching'" class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
                <div class="space-y-6">
                    <div v-if="props.can.edit" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-foreground">Assign a Class</h2>
                        <div class="space-y-3">
                            <select v-model="classForm.session_id" :class="selectClass">
                                <option value="">Select session</option>
                                <option v-for="s in props.sessions" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                            </select>
                            <select v-model="classForm.class_id" :class="selectClass">
                                <option value="">Select class</option>
                                <option v-for="c in props.classOptions" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                            </select>
                            <select v-model="classForm.subject_id" :class="selectClass">
                                <option value="">Select subject (optional)</option>
                                <option v-for="s in props.subjectOptions" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                            </select>
                            <Input v-model="classForm.periods_per_week" type="number" placeholder="Periods per week" />
                            <label class="flex items-center gap-2 text-sm text-muted-foreground">
                                <input v-model="classForm.is_class_teacher" type="checkbox" class="h-4 w-4 rounded border-border text-primary" />
                                Class teacher for this section
                            </label>
                            <Button @click="assignClass">Assign</Button>
                        </div>
                    </div>

                    <div v-if="props.can.edit" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-foreground">Subjects They May Teach</h2>
                        <div class="mb-3 flex gap-2">
                            <select v-model="subjectCapabilityId" :class="selectClass">
                                <option value="">Select subject</option>
                                <option v-for="s in props.subjectOptions" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                            </select>
                            <Button size="sm" @click="addSubjectCapability">Add</Button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="cap in props.staff.subjects" :key="cap.id" class="inline-flex items-center gap-1 rounded-full bg-muted px-3 py-1 text-xs">
                                {{ cap.subject?.name || '-' }}
                                <button type="button" class="text-destructive" @click="removeSubjectCapability(cap.subject?.id ?? 0)">×</button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                    <div class="border-b border-border px-5 py-3"><h3 class="font-semibold text-foreground">Current Timetable</h3></div>
                    <table class="min-w-full divide-y divide-border">
                        <tbody class="divide-y divide-border">
                            <tr v-for="assignment in props.classes" :key="assignment.id">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-foreground">
                                        {{ assignment.schoolClass?.name || '-' }} <span v-if="assignment.section">- {{ assignment.section.name }}</span>
                                        <span v-if="assignment.is_class_teacher" class="ml-2 rounded-full bg-primary/10 px-2 py-0.5 text-xs text-primary">Class Teacher</span>
                                    </div>
                                    <div class="text-xs text-muted-foreground">{{ assignment.subject?.name || 'All subjects' }} · {{ assignment.session?.name }}</div>
                                </td>
                                <td v-if="props.can.edit" class="px-4 py-3 text-right">
                                    <button type="button" class="text-xs text-destructive hover:underline" @click="unassignClass(assignment)">Unassign</button>
                                </td>
                            </tr>
                            <tr v-if="props.classes.length === 0"><td class="px-4 py-6 text-center text-sm text-muted-foreground">No classes assigned.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Attendance -->
            <div v-if="activeTab === 'attendance'" class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
                <div class="space-y-6">
                    <div v-if="attendanceSummary" class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-border bg-card p-4 text-center">
                            <p class="text-xs text-muted-foreground">Present</p>
                            <p class="text-xl font-bold text-success">{{ attendanceSummary.present }}</p>
                        </div>
                        <div class="rounded-xl border border-border bg-card p-4 text-center">
                            <p class="text-xs text-muted-foreground">Absent</p>
                            <p class="text-xl font-bold text-destructive">{{ attendanceSummary.absent }}</p>
                        </div>
                        <div class="rounded-xl border border-border bg-card p-4 text-center">
                            <p class="text-xs text-muted-foreground">Late</p>
                            <p class="text-xl font-bold text-warning">{{ attendanceSummary.late }}</p>
                        </div>
                        <div class="rounded-xl border border-border bg-card p-4 text-center">
                            <p class="text-xs text-muted-foreground">Expected Days</p>
                            <p class="text-xl font-bold text-foreground">{{ attendanceSummary.expected_days }}</p>
                        </div>
                    </div>

                    <div v-if="props.can.markAttendance" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-foreground">Mark Attendance</h2>
                        <div class="space-y-3">
                            <Input v-model="markForm.attendance_date" type="date" />
                            <select v-model="markForm.attendance_status_id" :class="selectClass">
                                <option value="">Select status</option>
                                <option v-for="s in attendanceStatuses" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                            </select>
                            <Input v-model="markForm.check_in_at" type="time" placeholder="Check-in" />
                            <Input v-model="markForm.check_out_at" type="time" placeholder="Check-out" />
                            <Input v-model="markForm.remarks" placeholder="Remarks (optional)" />
                            <Button @click="markAttendance">Save</Button>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">Check-in / out</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">Late (min)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="row in attendanceRows" :key="row.id">
                                <td class="px-4 py-3 text-sm text-foreground">{{ formatDate(row.attendance_date) }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ row.status?.name || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ row.check_in_at || '-' }} / {{ row.check_out_at || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ row.minutes_late }}</td>
                            </tr>
                            <tr v-if="attendanceRows.length === 0"><td colspan="4" class="px-4 py-6 text-center text-sm text-muted-foreground">No attendance recorded yet.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Leave -->
            <div v-if="activeTab === 'leave'" class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
                <div class="space-y-6">
                    <div v-if="leaveBalances.length > 0" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 class="mb-3 text-lg font-semibold text-foreground">Balance This Year</h2>
                        <ul class="space-y-1 text-sm">
                            <li v-for="b in leaveBalances" :key="b.leave_type.id" class="flex justify-between">
                                <span class="text-muted-foreground">{{ b.leave_type.name }}</span>
                                <span class="font-medium text-foreground">{{ b.remaining === null ? 'Unlimited' : b.remaining }}</span>
                            </li>
                        </ul>
                    </div>

                    <div v-if="props.can.applyForLeave" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-foreground">Apply for Leave</h2>
                        <div class="space-y-3">
                            <select v-model="leaveForm.staff_leave_type_id" :class="selectClass">
                                <option value="">Select leave type</option>
                                <option v-for="t in leaveTypes" :key="t.id" :value="String(t.id)">{{ t.name }}</option>
                            </select>
                            <Input v-model="leaveForm.from_date" type="date" />
                            <Input v-model="leaveForm.to_date" type="date" />
                            <Input v-model="leaveForm.reason" placeholder="Reason (optional)" />
                            <Button @click="applyLeave">Submit Application</Button>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">Dates</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">Days</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="leave in leaveRows" :key="leave.id">
                                <td class="px-4 py-3 text-sm text-foreground">{{ leave.leaveType?.name || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ formatDate(leave.from_date) }} - {{ formatDate(leave.to_date) }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ leave.days }}</td>
                                <td class="px-4 py-3">
                                    <span :class="{
                                        'bg-warning/10 text-warning': leave.status === 'pending',
                                        'bg-success/10 text-success': leave.status === 'approved',
                                        'bg-destructive/10 text-destructive': leave.status === 'rejected',
                                        'bg-muted text-muted-foreground': leave.status === 'cancelled',
                                    }" class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize">{{ leave.status }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <template v-if="leave.status === 'pending'">
                                        <Button v-if="props.can.decideLeave" variant="outline" size="sm" @click="decideLeave(leave, true)">Approve</Button>
                                        <Button v-if="props.can.decideLeave" variant="outline" size="sm" class="ml-2" @click="decideLeave(leave, false)">Reject</Button>
                                        <button v-if="props.can.applyForLeave" type="button" class="ml-2 text-xs text-destructive hover:underline" @click="cancelLeave(leave)">Cancel</button>
                                    </template>
                                </td>
                            </tr>
                            <tr v-if="leaveRows.length === 0"><td colspan="5" class="px-4 py-6 text-center text-sm text-muted-foreground">No leave applications yet.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Salary -->
            <div v-if="activeTab === 'salary'" class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
                <div class="space-y-6">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-border bg-card p-4 text-center">
                            <p class="text-xs text-muted-foreground">Gross</p>
                            <p class="text-lg font-bold text-primary">{{ formatMoney(salaryGross) }}</p>
                        </div>
                        <div class="rounded-xl border border-border bg-card p-4 text-center">
                            <p class="text-xs text-muted-foreground">Deductions</p>
                            <p class="text-lg font-bold text-destructive">{{ formatMoney(salaryDeductions) }}</p>
                        </div>
                    </div>

                    <div v-if="props.can.manageSalary" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-foreground">Set a Component</h2>
                        <p class="mb-3 text-xs text-muted-foreground">A raise closes the old figure rather than editing it.</p>
                        <div class="space-y-3">
                            <select v-model="componentForm.salary_head_id" :class="selectClass">
                                <option value="">Select salary head</option>
                                <option v-for="h in salaryHeads" :key="h.id" :value="String(h.id)">{{ h.name }} ({{ h.type }})</option>
                            </select>
                            <Input v-model="componentForm.amount" type="number" min="0" placeholder="Amount" />
                            <Input v-model="componentForm.effective_from" type="date" />
                            <Button @click="setComponent">Set Component</Button>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">Head</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted-foreground">Effective From</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="component in salaryComponents" :key="component.id">
                                <td class="px-4 py-3 text-sm text-foreground">{{ component.salaryHead?.name || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ formatMoney(component.amount) }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ formatDate(component.effective_from) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button v-if="props.can.manageSalary" type="button" class="text-xs text-destructive hover:underline" @click="endComponent(component)">End</button>
                                </td>
                            </tr>
                            <tr v-if="salaryComponents.length === 0"><td colspan="4" class="px-4 py-6 text-center text-sm text-muted-foreground">No named components — using the base salary and lump allowance/deduction.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
