<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Revaluation Requests" />

        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Revaluation Requests</h1>
                <button
                    @click="showCreateModal = true"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                >
                    New Request
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
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
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button
                            @click="fetchRevaluations"
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full"
                        >
                            Search
                        </button>
                    </div>
                </div>
            </div>

            <!-- Revaluations Table -->
            <div v-if="loading" class="text-center py-8">
                <span class="text-gray-500">Loading...</span>
            </div>
            
            <div v-else-if="revaluations.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Original Marks</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Expected Marks</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="item in revaluations" :key="item.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm">#{{ item.id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ item.student?.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ item.exam?.name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ item.subject?.name }}</td>
                            <td class="px-6 py-4 text-right">{{ item.original_marks }}</td>
                            <td class="px-6 py-4 text-right">{{ item.expected_marks }}</td>
                            <td class="px-6 py-4 text-center">
                                <span 
                                    :class="{
                                        'bg-yellow-100 text-yellow-800': item.status === 'pending',
                                        'bg-green-100 text-green-800': item.status === 'approved',
                                        'bg-red-100 text-red-800': item.status === 'rejected',
                                    }"
                                    class="px-2 py-1 rounded text-xs font-medium"
                                >
                                    {{ item.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button
                                    @click="viewDetail(item)"
                                    class="text-blue-600 hover:text-blue-800 text-sm mr-2"
                                >
                                    View
                                </button>
                                <button
                                    v-if="item.status === 'pending'"
                                    @click="cancelRequest(item)"
                                    class="text-red-600 hover:text-red-800 text-sm"
                                >
                                    Cancel
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="text-center py-8 text-gray-500">
                No revaluation requests found.
            </div>

            <!-- Create Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md">
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
                            <label class="block text-sm font-medium mb-1">Expected Marks</label>
                            <input v-model="form.expected_marks" type="number" class="w-full border rounded px-3 py-2" required />
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Reason</label>
                            <textarea v-model="form.reason" class="w-full border rounded px-3 py-2" rows="3"></textarea>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="showCreateModal = false" class="px-4 py-2 border rounded">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

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
    student?: { name: string };
    exam?: { name: string };
    subject?: { name: string };
    original_marks: number;
    expected_marks: number;
    status: string;
}

interface Props {
    exams: Exam[];
    campuses?: Array<{id: number, name: string}>;
    classes?: Array<{id: number, name: string}>;
}

defineProps<Props>()

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
    { title: 'Revaluations', href: '/exams/revaluations' },
]

const loading = ref(false)
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
    expected_marks: '' as string | number,
    reason: '',
})

async function fetchRevaluations() {
    loading.value = true
    try {
        const response = await axios.get('/exam/revaluations/list', {
            params: {
                exam_id: filters.exam_id,
                status: filters.status,
            }
        })
        revaluations.value = response.data.data || []
    } catch (error) {
        console.error('Error fetching revaluations:', error)
    } finally {
        loading.value = false
    }
}

async function fetchStudents() {
    try {
        const response = await axios.get('/students', { params: { limit: 1000 } })
        students.value = response.data.data || []
    } catch (error) {
        console.error('Error fetching students:', error)
    }
}

async function submitRequest() {
    try {
        await axios.post('/exam/revaluations/request', {
            exam_id: form.exam_id,
            student_id: form.student_id,
            exam_paper_id: 1,
            expected_marks: form.expected_marks,
            reason: form.reason,
        })
        showCreateModal.value = false
        fetchRevaluations()
        alert('Revaluation request submitted successfully')
    } catch (error) {
        console.error('Error submitting request:', error)
        alert('Failed to submit request')
    }
}

function viewDetail(item: RevaluationItem) {
    window.location.href = `/exams/revaluations/${item.id}`
}

function cancelRequest(item: RevaluationItem) {
    if (!confirm('Are you sure you want to cancel this request?')) return
    console.log('Cancel request:', item.id)
}

onMounted(() => {
    fetchStudents()
    fetchRevaluations()
})
</script>
