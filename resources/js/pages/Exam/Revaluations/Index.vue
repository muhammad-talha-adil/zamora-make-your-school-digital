<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Revaluation Requests" />

        <div class="p-4 md:p-6">
            <div class="flex flex-wrap gap-2 justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Revaluation Requests</h1>
                <Button @click="showCreateModal = true">
                    <Icon icon="plus" class="h-4 w-4" />
                    New Request
                </Button>
            </div>

            <!-- Filters -->
            <div class="bg-card rounded-lg shadow p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Exam</label>
                        <select
                            v-model="filters.exam_id"
                            class="w-full border rounded px-3 py-2"
                        >
                            <option value="">Select Exam</option>
                            <option v-for="exam in exams" :key="exam.id" :value="exam.id">
                                {{ exam.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <select
                            v-model="filters.status"
                            class="w-full border rounded px-3 py-2"
                        >
                            <option value="">All Statuses</option>
                            <option v-for="option in statusOptions" :key="option" :value="option">
                                {{ statusLabel(option) }}
                            </option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <Button variant="outline" class="w-full" @click="fetchRevaluations">
                            Search
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Revaluations Table -->
            <div v-if="loading" class="text-center py-8">
                <span class="text-muted-foreground">Loading...</span>
            </div>

            <div v-else-if="revaluations.length > 0" class="table-scroll bg-card rounded-lg shadow overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Exam</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Subject</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Current Marks</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-muted-foreground uppercase">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="item in revaluations" :key="item.id" class="hover:bg-accent">
                            <td class="px-4 py-4 whitespace-nowrap text-sm">#{{ item.id }}</td>
                            <td class="px-4 py-4 whitespace-nowrap">{{ studentName(item) }}</td>
                            <td class="px-4 py-4 whitespace-nowrap">{{ examName(item) }}</td>
                            <td class="px-4 py-4 whitespace-nowrap">{{ subjectName(item) }}</td>
                            <td class="px-4 py-4 text-right">{{ item.exam_result_line?.obtained_marks ?? '-' }}</td>
                            <td class="px-4 py-4 text-center">
                                <span
                                    :class="statusBadgeClass(item.status)"
                                    class="px-2 py-1 rounded-full text-xs font-medium uppercase"
                                >
                                    {{ statusLabel(item.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <Button variant="outline" size="sm" :class="tableActionButtonClass.view" @click="viewHistory(item)">
                                        <Icon icon="history" class="h-3.5 w-3.5" />
                                        History
                                    </Button>

                                    <template v-if="props.can.manage">
                                        <Button
                                            v-if="item.status === 'pending'"
                                            variant="outline"
                                            size="sm"
                                            :class="tableActionButtonClass.edit"
                                            @click="reviewRequest(item)"
                                        >
                                            <Icon icon="eye" class="h-3.5 w-3.5" />
                                            Review
                                        </Button>

                                        <template v-if="item.status === 'in_review'">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                :class="tableActionButtonClass.activate"
                                                @click="openApproveModal(item)"
                                            >
                                                <Icon icon="check" class="h-3.5 w-3.5" />
                                                Approve
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                :class="tableActionButtonClass.delete"
                                                @click="openRejectModal(item)"
                                            >
                                                <Icon icon="x" class="h-3.5 w-3.5" />
                                                Reject
                                            </Button>
                                        </template>

                                        <Button
                                            v-if="item.status === 'approved'"
                                            variant="outline"
                                            size="sm"
                                            :class="tableActionButtonClass.activate"
                                            @click="applyChange(item)"
                                        >
                                            <Icon icon="check-check" class="h-3.5 w-3.5" />
                                            Apply Change
                                        </Button>
                                    </template>

                                    <Button
                                        v-if="!props.can.manage && item.status === 'pending' && isOwnRequest(item)"
                                        variant="outline"
                                        size="sm"
                                        :class="tableActionButtonClass.delete"
                                        @click="cancelRequest(item)"
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="text-center py-8 text-muted-foreground">
                No revaluation requests found.
            </div>

            <!-- Create Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-card rounded-lg p-6 w-full max-w-md">
                    <h2 class="text-xl font-bold mb-4">New Revaluation Request</h2>
                    <form @submit.prevent="submitRequest">
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Exam</label>
                            <select v-model="form.exam_id" class="w-full border rounded px-3 py-2" required>
                                <option value="">Select Exam</option>
                                <option v-for="exam in exams" :key="exam.id" :value="exam.id">
                                    {{ exam.name }}
                                </option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Student</label>
                            <select v-model="form.student_id" class="w-full border rounded px-3 py-2" required>
                                <option value="">Select Student</option>
                                <option v-for="student in students" :key="student.id" :value="student.id">
                                    {{ student.name }}
                                </option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Exam Paper ID</label>
                            <input v-model="form.exam_paper_id" type="number" class="w-full border rounded px-3 py-2" required />
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Reason</label>
                            <textarea v-model="form.reason" class="w-full border rounded px-3 py-2" rows="3"></textarea>
                        </div>
                        <div class="flex flex-wrap justify-end gap-2">
                            <Button type="button" variant="outline" @click="showCreateModal = false">Cancel</Button>
                            <Button type="submit" :disabled="submitting">Submit</Button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Approve Modal -->
            <Dialog :open="showApproveModal" @update:open="(open) => !open && closeApproveModal()">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Approve Recheck #{{ approveTarget?.id }}</DialogTitle>
                        <DialogDescription>
                            Enter the corrected mark for {{ studentName(approveTarget) }} in {{ subjectName(approveTarget) }}.
                            Current mark: {{ approveTarget?.exam_result_line?.obtained_marks ?? '-' }} / {{ approveTarget?.exam_result_line?.total_marks_snapshot ?? '-' }}.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitApprove" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="new_marks">Corrected Marks <span class="text-destructive">*</span></Label>
                            <input
                                id="new_marks"
                                v-model="approveForm.new_marks"
                                type="number"
                                step="0.01"
                                min="0"
                                :max="approveTarget?.exam_result_line?.total_marks_snapshot ?? undefined"
                                class="w-full border rounded px-3 py-2"
                                required
                            />
                            <InputError :message="approveErrors.new_marks" />
                        </div>
                        <div class="space-y-2">
                            <Label for="approve_note">Note</Label>
                            <textarea
                                id="approve_note"
                                v-model="approveForm.note"
                                rows="3"
                                class="w-full border rounded px-3 py-2"
                                placeholder="Optional note for the record"
                            ></textarea>
                            <InputError :message="approveErrors.note" />
                        </div>

                        <DialogFooter class="sm:justify-end gap-2">
                            <Button type="button" variant="outline" @click="closeApproveModal">Cancel</Button>
                            <Button type="submit" :disabled="submitting">
                                <Icon v-if="submitting" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                Approve
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Reject Modal -->
            <Dialog :open="showRejectModal" @update:open="(open) => !open && closeRejectModal()">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Reject Recheck #{{ rejectTarget?.id }}</DialogTitle>
                        <DialogDescription>
                            The mark for {{ studentName(rejectTarget) }} in {{ subjectName(rejectTarget) }} will stand unchanged.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitReject" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="reject_note">Reason</Label>
                            <textarea
                                id="reject_note"
                                v-model="rejectForm.note"
                                rows="3"
                                class="w-full border rounded px-3 py-2"
                                placeholder="Why the mark is being upheld"
                            ></textarea>
                            <InputError :message="rejectErrors.note" />
                        </div>

                        <DialogFooter class="sm:justify-end gap-2">
                            <Button type="button" variant="outline" @click="closeRejectModal">Cancel</Button>
                            <Button type="submit" variant="destructive" :disabled="submitting">
                                <Icon v-if="submitting" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                Reject
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- History Modal -->
            <Dialog :open="showHistoryModal" @update:open="(open) => !open && closeHistoryModal()">
                <DialogContent class="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>History for Recheck #{{ historyTarget?.id }}</DialogTitle>
                        <DialogDescription>Everything that has happened to this request, oldest first.</DialogDescription>
                    </DialogHeader>

                    <div v-if="historyLoading" class="text-center py-6 text-muted-foreground">Loading...</div>
                    <ul v-else-if="history.length > 0" class="space-y-3 max-h-96 overflow-y-auto">
                        <li v-for="action in history" :key="action.id" class="border border-border rounded-md p-3 text-sm">
                            <div class="flex flex-wrap justify-between gap-2">
                                <span class="font-medium">{{ action.action_by?.name ?? 'System' }}</span>
                                <span class="text-muted-foreground">{{ formatDate(action.created_at) }}</span>
                            </div>
                            <p class="mt-1">{{ action.note }}</p>
                            <p v-if="action.old_marks !== null || action.new_marks !== null" class="mt-1 text-muted-foreground">
                                {{ action.old_marks ?? '-' }} &rarr; {{ action.new_marks ?? '-' }}
                            </p>
                        </li>
                    </ul>
                    <div v-else class="text-center py-6 text-muted-foreground">No history yet.</div>

                    <DialogFooter class="sm:justify-end">
                        <Button type="button" variant="outline" @click="closeHistoryModal">Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { route } from 'ziggy-js'
import AppLayout from '@/layouts/AppLayout.vue'
import Icon from '@/components/Icon.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { alert } from '@/utils'
import { tableActionButtonClass } from '@/utils/table-actions'
import { formatDate } from '@/utils/date'
import type { AppPageProps, BreadcrumbItem } from '@/types'

interface Exam {
    id: number;
    name: string;
}

interface Student {
    id: number;
    name: string;
}

interface RevaluationItem {
    id: number;
    status: string;
    reason?: string | null;
    requested_by?: { id: number; name: string } | null;
    exam_result_line?: {
        obtained_marks?: number | string | null;
        total_marks_snapshot?: number | string | null;
        exam_paper?: { subject?: { name?: string } | null } | null;
        result_header?: {
            exam?: { name?: string } | null;
            student?: { user?: { name?: string } | null } | null;
        } | null;
    } | null;
}

interface HistoryAction {
    id: number;
    old_marks: number | string | null;
    new_marks: number | string | null;
    note: string | null;
    created_at: string;
    action_by?: { name: string } | null;
}

interface Props {
    exams: Exam[];
    campuses?: Array<{ id: number, name: string }>;
    classes?: Array<{ id: number, name: string }>;
    can: { manage: boolean };
}

const props = defineProps<Props>()

const page = usePage<AppPageProps>()

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
    { title: 'Revaluations', href: '/exams/revaluations' },
]

const statusOptions = ['pending', 'in_review', 'approved', 'rejected', 'applied']

const loading = ref(false)
const submitting = ref(false)
const revaluations = ref<RevaluationItem[]>([])
const showCreateModal = ref(false)
const students = ref<Student[]>([])

const filters = reactive({
    exam_id: '' as string | number,
    status: '' as string,
})

const form = reactive({
    exam_id: '' as string | number,
    student_id: '' as string | number,
    exam_paper_id: '' as string | number,
    reason: '',
})

function statusLabel(status: string): string {
    return status.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

function statusBadgeClass(status: string): string {
    return {
        pending: 'bg-warning/10 text-warning',
        in_review: 'bg-accent text-accent-foreground',
        approved: 'bg-success/10 text-success',
        rejected: 'bg-destructive/10 text-destructive',
        applied: 'bg-primary/10 text-primary',
    }[status] ?? 'bg-muted text-muted-foreground'
}

function studentName(item?: RevaluationItem | null): string {
    return item?.exam_result_line?.result_header?.student?.user?.name ?? '-'
}

function examName(item?: RevaluationItem | null): string {
    return item?.exam_result_line?.result_header?.exam?.name ?? '-'
}

function subjectName(item?: RevaluationItem | null): string {
    return item?.exam_result_line?.exam_paper?.subject?.name ?? '-'
}

function isOwnRequest(item: RevaluationItem): boolean {
    return item.requested_by?.id === page.props.auth.user.id
}

async function fetchRevaluations() {
    loading.value = true
    try {
        const response = await axios.get(route('exam.revaluations.index'), {
            params: {
                exam_id: filters.exam_id || undefined,
                status: filters.status || undefined,
            },
        })
        revaluations.value = response.data.data || []
    } catch (error) {
        console.error('Error fetching revaluations:', error)
        alert.error('Failed to load revaluation requests.')
    } finally {
        loading.value = false
    }
}

async function fetchStudents() {
    try {
        const response = await axios.get(route('students.api.all'))
        students.value = response.data.data || response.data || []
    } catch (error) {
        console.error('Error fetching students:', error)
    }
}

async function submitRequest() {
    submitting.value = true
    try {
        await axios.post(route('exam.revaluations.request'), {
            exam_id: form.exam_id,
            student_id: form.student_id,
            exam_paper_id: form.exam_paper_id,
            reason: form.reason,
        })
        showCreateModal.value = false
        form.exam_id = ''
        form.student_id = ''
        form.exam_paper_id = ''
        form.reason = ''
        fetchRevaluations()
        alert.success('Revaluation request submitted successfully.')
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to submit request.')
    } finally {
        submitting.value = false
    }
}

function cancelRequest(item: RevaluationItem) {
    void item
    alert.error('Cancelling a request is not available yet.')
}

async function reviewRequest(item: RevaluationItem) {
    const result = await alert.confirm(
        `Mark recheck #${item.id} as under review?`,
        'Review Recheck',
        'Mark In Review',
    )
    if (!result.isConfirmed) return

    try {
        await axios.get(route('exam.revaluations.review', item.id))
        alert.success('Recheck marked as under review.')
        fetchRevaluations()
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to update the recheck.')
    }
}

const showApproveModal = ref(false)
const approveTarget = ref<RevaluationItem | null>(null)
const approveForm = reactive({ new_marks: '' as string | number, note: '' })
const approveErrors = ref<Record<string, string>>({})

function openApproveModal(item: RevaluationItem) {
    approveTarget.value = item
    approveForm.new_marks = ''
    approveForm.note = ''
    approveErrors.value = {}
    showApproveModal.value = true
}

function closeApproveModal() {
    showApproveModal.value = false
    approveTarget.value = null
}

async function submitApprove() {
    if (!approveTarget.value) return
    submitting.value = true
    approveErrors.value = {}
    try {
        await axios.patch(route('exam.revaluations.approve', approveTarget.value.id), {
            new_marks: Number(approveForm.new_marks),
            note: approveForm.note || null,
        })
        alert.success('Recheck approved.')
        closeApproveModal()
        fetchRevaluations()
    } catch (error: any) {
        if (error?.response?.status === 422) {
            approveErrors.value = error.response.data.errors
                ? Object.fromEntries(Object.entries(error.response.data.errors).map(([k, v]: [string, any]) => [k, v[0]]))
                : {}
        }
        alert.error(error?.response?.data?.message || 'Failed to approve the recheck.')
    } finally {
        submitting.value = false
    }
}

const showRejectModal = ref(false)
const rejectTarget = ref<RevaluationItem | null>(null)
const rejectForm = reactive({ note: '' })
const rejectErrors = ref<Record<string, string>>({})

function openRejectModal(item: RevaluationItem) {
    rejectTarget.value = item
    rejectForm.note = ''
    rejectErrors.value = {}
    showRejectModal.value = true
}

function closeRejectModal() {
    showRejectModal.value = false
    rejectTarget.value = null
}

async function submitReject() {
    if (!rejectTarget.value) return
    submitting.value = true
    rejectErrors.value = {}
    try {
        await axios.patch(route('exam.revaluations.reject', rejectTarget.value.id), {
            note: rejectForm.note || null,
        })
        alert.success('Recheck rejected.')
        closeRejectModal()
        fetchRevaluations()
    } catch (error: any) {
        if (error?.response?.status === 422) {
            rejectErrors.value = error.response.data.errors
                ? Object.fromEntries(Object.entries(error.response.data.errors).map(([k, v]: [string, any]) => [k, v[0]]))
                : {}
        }
        alert.error(error?.response?.data?.message || 'Failed to reject the recheck.')
    } finally {
        submitting.value = false
    }
}

async function applyChange(item: RevaluationItem) {
    const result = await alert.confirm(
        `Write the corrected mark for recheck #${item.id} onto the result?`,
        'Apply Change',
        'Apply Change',
    )
    if (!result.isConfirmed) return

    try {
        await axios.patch(route('exam.revaluations.apply-change', item.id))
        alert.success('Change applied to the result.')
        fetchRevaluations()
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to apply the change.')
    }
}

const showHistoryModal = ref(false)
const historyTarget = ref<RevaluationItem | null>(null)
const history = ref<HistoryAction[]>([])
const historyLoading = ref(false)

async function viewHistory(item: RevaluationItem) {
    historyTarget.value = item
    showHistoryModal.value = true
    historyLoading.value = true
    try {
        const response = await axios.get(route('exam.revaluations.history', item.id))
        history.value = response.data.data || []
    } catch (error) {
        console.error('Error fetching history:', error)
        history.value = []
    } finally {
        historyLoading.value = false
    }
}

function closeHistoryModal() {
    showHistoryModal.value = false
    historyTarget.value = null
    history.value = []
}

fetchStudents()
fetchRevaluations()
</script>
