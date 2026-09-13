<script setup lang="ts">
import { computed, reactive, ref, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogClose, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import ComboboxInput from '@/components/ui/combobox/ComboboxInput.vue';
import { alert } from '@/utils';
import type { BreadcrumbItem } from '@/types';

interface LeaveTypeOption {
    id: number;
    name: string;
}

interface StudentOption {
    id: number;
    name: string;
    registration_no?: string | null;
}

interface LeaveRow {
    id: number;
    student_id: number;
    leave_type_id: number;
    start_date: string;
    end_date: string;
    description: string;
    status: 'pending' | 'approved' | 'rejected';
    applied_at?: string | null;
    decided_at?: string | null;
    decision_note?: string | null;
    leave_type?: LeaveTypeOption | null;
    student?: { id: number; user?: { id: number; name: string } | null } | null;
    approved_by?: { id: number; name: string } | null;
}

interface Props {
    leaveTypes: LeaveTypeOption[];
    students: StudentOption[];
    canViewPending: boolean;
    canDecide: boolean;
    defaultStudentId: number | null;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Attendance', href: route('attendance.index') },
    { title: 'Leave Applications', href: route('student-leaves.page') },
];

const textareaClass = 'min-h-24 w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground';

const activeTab = ref<'pending' | 'all'>(props.canViewPending ? 'pending' : 'all');
const loading = ref(false);
const leaves = ref<LeaveRow[]>([]);
const selectedStudentId = ref<number | null>(props.defaultStudentId);

const comboboxStudents = computed(() => props.students.map((student) => ({
    id: student.id,
    name: student.registration_no ? `${student.name} (${student.registration_no})` : student.name,
})));

const selectedStudentName = computed(() => props.students.find((student) => student.id === selectedStudentId.value)?.name ?? null);

const fetchPending = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('student-leaves.pending'));
        leaves.value = response.data.leaves ?? [];
    } catch {
        alert.error('Failed to load pending applications.');
    } finally {
        loading.value = false;
    }
};

const fetchAll = async () => {
    if (!selectedStudentId.value) {
        leaves.value = [];
        return;
    }

    loading.value = true;
    try {
        const response = await axios.get(route('student-leaves.index', { student: selectedStudentId.value }));
        leaves.value = response.data.leaves ?? [];
    } catch {
        alert.error('Failed to load leave history.');
    } finally {
        loading.value = false;
    }
};

const refresh = () => {
    if (activeTab.value === 'pending') {
        fetchPending();
    } else {
        fetchAll();
    }
};

const switchTab = (tab: 'pending' | 'all') => {
    activeTab.value = tab;
    refresh();
};

watch(selectedStudentId, () => {
    if (activeTab.value === 'all') {
        fetchAll();
    }
});

onMounted(() => {
    refresh();
});

// Apply dialog
const showApplyDialog = ref(false);
const applyProcessing = ref(false);
const applyErrors = ref<Record<string, string>>({});
const applyForm = reactive({
    student_id: null as number | null,
    leave_type_id: null as number | null,
    start_date: '',
    end_date: '',
    description: '',
});

const openApplyDialog = () => {
    applyForm.student_id = selectedStudentId.value ?? (props.students.length === 1 ? props.students[0].id : null);
    applyForm.leave_type_id = null;
    applyForm.start_date = '';
    applyForm.end_date = '';
    applyForm.description = '';
    applyErrors.value = {};
    showApplyDialog.value = true;
};

const submitApply = async () => {
    if (!applyForm.student_id) {
        applyErrors.value = { student_id: 'Select a student.' };
        return;
    }

    applyProcessing.value = true;
    applyErrors.value = {};

    try {
        await axios.post(route('student-leaves.store', { student: applyForm.student_id }), {
            leave_type_id: applyForm.leave_type_id,
            start_date: applyForm.start_date,
            end_date: applyForm.end_date,
            description: applyForm.description,
        });

        alert.success('Leave application submitted.');
        showApplyDialog.value = false;
        refresh();
    } catch (error: any) {
        if (error?.response?.status === 422) {
            const responseErrors = error.response.data?.errors ?? {};
            applyErrors.value = Object.fromEntries(
                Object.entries(responseErrors).map(([key, value]) => [key, Array.isArray(value) ? value[0] : String(value)]),
            );
            const message = error.response.data?.message;
            if (message) {
                alert.error(message);
            }
        } else {
            alert.error('Failed to submit the application. Please try again.');
        }
    } finally {
        applyProcessing.value = false;
    }
};

// Approve
const approvingId = ref<number | null>(null);

const approveLeave = async (leave: LeaveRow) => {
    const result = await alert.confirm(
        'This stops the register calling those days absence.',
        'Approve this leave application?',
        'Yes, approve it',
    );

    if (!result.isConfirmed) {
        return;
    }

    approvingId.value = leave.id;
    try {
        await axios.post(route('student-leaves.approve', { leave: leave.id }));
        alert.success('Leave application approved.');
        refresh();
    } catch {
        alert.error('Failed to approve the application.');
    } finally {
        approvingId.value = null;
    }
};

// Reject dialog
const showRejectDialog = ref(false);
const rejectProcessing = ref(false);
const rejectErrors = ref<Record<string, string>>({});
const rejectTarget = ref<LeaveRow | null>(null);
const rejectForm = reactive({ reason: '' });

const openRejectDialog = (leave: LeaveRow) => {
    rejectTarget.value = leave;
    rejectForm.reason = '';
    rejectErrors.value = {};
    showRejectDialog.value = true;
};

const submitReject = async () => {
    if (!rejectTarget.value) {
        return;
    }

    rejectProcessing.value = true;
    rejectErrors.value = {};

    try {
        await axios.post(route('student-leaves.reject', { leave: rejectTarget.value.id }), {
            reason: rejectForm.reason,
        });

        alert.success('Leave application rejected.');
        showRejectDialog.value = false;
        refresh();
    } catch (error: any) {
        if (error?.response?.status === 422) {
            const responseErrors = error.response.data?.errors ?? {};
            rejectErrors.value = Object.fromEntries(
                Object.entries(responseErrors).map(([key, value]) => [key, Array.isArray(value) ? value[0] : String(value)]),
            );
        } else {
            alert.error('Failed to reject the application.');
        }
    } finally {
        rejectProcessing.value = false;
    }
};

const daysCount = (leave: LeaveRow): number => {
    const from = new Date(leave.start_date);
    const to = new Date(leave.end_date);
    return Math.round((to.getTime() - from.getTime()) / 86400000) + 1;
};

const formatDate = (value: string): string => {
    return new Date(value).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const statusClass = (status: LeaveRow['status']): string => {
    return {
        pending: 'bg-warning/10 text-warning',
        approved: 'bg-success/10 text-success',
        rejected: 'bg-destructive/10 text-destructive',
    }[status];
};

const studentLabel = (leave: LeaveRow): string => {
    return leave.student?.user?.name ?? selectedStudentName.value ?? `Student #${leave.student_id}`;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Leave Applications" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">Leave Applications</h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                        Apply for a child's leave, and decide on the applications waiting for an answer.
                    </p>
                </div>
                <Button @click="openApplyDialog">
                    <Icon icon="plus" class="mr-1" />
                    New Application
                </Button>
            </div>

            <!-- Tabs -->
            <div class="flex flex-wrap gap-2 border-b border-border">
                <button
                    v-if="props.canViewPending"
                    type="button"
                    class="px-3 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
                    :class="activeTab === 'pending' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    @click="switchTab('pending')"
                >
                    Pending
                </button>
                <button
                    type="button"
                    class="px-3 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
                    :class="activeTab === 'all' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    @click="switchTab('all')"
                >
                    All Applications
                </button>
            </div>

            <!-- Student picker for the "All" tab -->
            <div v-if="activeTab === 'all' && props.students.length > 1" class="max-w-xs">
                <Label for="student-picker">Student</Label>
                <ComboboxInput
                    id="student-picker"
                    v-model="selectedStudentId"
                    placeholder="Search a student..."
                    :initial-items="comboboxStudents"
                    class="mt-1"
                />
            </div>
            <div v-else-if="activeTab === 'all' && selectedStudentName" class="text-sm text-muted-foreground">
                Showing leave history for <span class="font-medium text-foreground">{{ selectedStudentName }}</span>.
            </div>

            <!-- Loading -->
            <div v-if="loading" class="text-center py-8">
                <Icon icon="loader" class="mx-auto h-6 w-6 animate-spin text-muted-foreground" />
            </div>

            <template v-else>
                <!-- Empty state -->
                <div
                    v-if="leaves.length === 0"
                    class="bg-card rounded-lg border border-border p-8 text-center text-muted-foreground"
                >
                    <Icon icon="calendar-x" class="h-10 w-10 mx-auto mb-3 text-muted-foreground" />
                    <template v-if="activeTab === 'all' && !selectedStudentId">
                        Select a student to see their leave history.
                    </template>
                    <template v-else>
                        No leave applications found.
                    </template>
                </div>

                <template v-else>
                    <!-- Mobile Card View -->
                    <div class="block lg:hidden space-y-3">
                        <div
                            v-for="leave in leaves"
                            :key="leave.id"
                            class="bg-card rounded-lg border border-border p-4 space-y-3"
                        >
                            <div class="flex flex-wrap gap-2 justify-between items-start">
                                <div>
                                    <div class="font-medium text-foreground">{{ studentLabel(leave) }}</div>
                                    <div class="text-xs text-muted-foreground">{{ leave.leave_type?.name }}</div>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium rounded-full shrink-0" :class="statusClass(leave.status)">
                                    {{ leave.status }}
                                </span>
                            </div>

                            <div class="text-sm text-muted-foreground space-y-1 pt-2 border-t border-border">
                                <div class="flex items-center gap-2">
                                    <Icon icon="calendar" class="h-4 w-4" />
                                    <span>{{ formatDate(leave.start_date) }} - {{ formatDate(leave.end_date) }} ({{ daysCount(leave) }} day{{ daysCount(leave) > 1 ? 's' : '' }})</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <Icon icon="message-square" class="h-4 w-4 mt-0.5" />
                                    <span>{{ leave.description }}</span>
                                </div>
                                <div v-if="leave.status !== 'pending' && leave.decision_note" class="flex items-start gap-2">
                                    <Icon icon="info" class="h-4 w-4 mt-0.5" />
                                    <span>{{ leave.decision_note }}</span>
                                </div>
                            </div>

                            <div v-if="leave.status === 'pending' && props.canDecide" class="flex gap-2 pt-2">
                                <Button size="sm" class="flex-1" :disabled="approvingId === leave.id" @click="approveLeave(leave)">
                                    <Icon icon="check" class="mr-1 h-3 w-3" />Approve
                                </Button>
                                <Button variant="outline" size="sm" class="flex-1" @click="openRejectDialog(leave)">
                                    <Icon icon="x" class="mr-1 h-3 w-3" />Reject
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden lg:block overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-border">
                                <thead class="bg-muted">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Student</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Leave Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Dates</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Reason</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Status</th>
                                        <th v-if="props.canDecide" class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border bg-card">
                                    <tr v-for="leave in leaves" :key="leave.id" class="transition-colors hover:bg-accent">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-foreground">{{ studentLabel(leave) }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ leave.leave_type?.name }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">
                                            {{ formatDate(leave.start_date) }} - {{ formatDate(leave.end_date) }}
                                            <span class="text-xs">({{ daysCount(leave) }}d)</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground max-w-xs truncate" :title="leave.description">{{ leave.description }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full" :class="statusClass(leave.status)">
                                                {{ leave.status }}
                                            </span>
                                        </td>
                                        <td v-if="props.canDecide" class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                            <div v-if="leave.status === 'pending'" class="flex flex-wrap gap-2 justify-end">
                                                <Button size="sm" class="min-h-8" :disabled="approvingId === leave.id" @click="approveLeave(leave)">
                                                    <Icon icon="check" class="mr-1 h-3 w-3" />Approve
                                                </Button>
                                                <Button variant="outline" size="sm" class="min-h-8" @click="openRejectDialog(leave)">
                                                    <Icon icon="x" class="mr-1 h-3 w-3" />Reject
                                                </Button>
                                            </div>
                                            <span v-else class="text-xs text-muted-foreground">&mdash;</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </template>
        </div>

        <!-- New Application Dialog -->
        <Dialog v-model:open="showApplyDialog">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>New Leave Application</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitApply" class="space-y-4">
                    <div v-if="props.students.length > 1" class="space-y-2">
                        <Label for="apply-student">Student</Label>
                        <ComboboxInput
                            id="apply-student"
                            v-model="applyForm.student_id"
                            placeholder="Search a student..."
                            :initial-items="comboboxStudents"
                        />
                        <InputError :message="applyErrors.student_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="leave_type_id">Leave Type</Label>
                        <ComboboxInput
                            id="leave_type_id"
                            v-model="applyForm.leave_type_id"
                            placeholder="Search leave types..."
                            :initial-items="props.leaveTypes"
                        />
                        <InputError :message="applyErrors.leave_type_id" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="start_date">Start Date</Label>
                            <Input id="start_date" v-model="applyForm.start_date" type="date" />
                            <InputError :message="applyErrors.start_date" />
                        </div>
                        <div class="space-y-2">
                            <Label for="end_date">End Date</Label>
                            <Input id="end_date" v-model="applyForm.end_date" type="date" />
                            <InputError :message="applyErrors.end_date" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="description">Reason</Label>
                        <textarea
                            id="description"
                            v-model="applyForm.description"
                            :class="textareaClass"
                            rows="3"
                            placeholder="Say why the leave is needed"
                        />
                        <InputError :message="applyErrors.description" />
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 pt-2">
                        <DialogClose as-child>
                            <Button type="button" variant="outline">Cancel</Button>
                        </DialogClose>
                        <Button type="submit" :disabled="applyProcessing">
                            <Icon v-if="applyProcessing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                            Submit Application
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Reject Dialog -->
        <Dialog v-model:open="showRejectDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Reject Leave Application</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitReject" class="space-y-4">
                    <p class="text-sm text-muted-foreground">
                        Say why the application is refused. The family sees this reason.
                    </p>

                    <div class="space-y-2">
                        <Label for="reason">Reason</Label>
                        <textarea
                            id="reason"
                            v-model="rejectForm.reason"
                            :class="textareaClass"
                            rows="3"
                            placeholder="Explain the decision"
                        />
                        <InputError :message="rejectErrors.reason" />
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 pt-2">
                        <DialogClose as-child>
                            <Button type="button" variant="outline">Cancel</Button>
                        </DialogClose>
                        <Button type="submit" variant="destructive" :disabled="rejectProcessing">
                            <Icon v-if="rejectProcessing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                            Reject Application
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
