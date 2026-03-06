<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Exam Dashboard" />

        <div class="p-6">
            <h1 class="text-2xl font-bold mb-6">Exam Dashboard</h1>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <!-- Total Exams -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-gray-500 dark:text-gray-400 text-sm">Total Exams</div>
                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ stats?.total_exams || 0 }}</div>
            </div>
            
            <!-- Draft -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-gray-500 dark:text-gray-400 text-sm">Draft</div>
                <div class="text-3xl font-bold text-gray-600 dark:text-gray-300">{{ stats?.exams_by_status?.draft || 0 }}</div>
            </div>
            
            <!-- Active -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-gray-500 dark:text-gray-400 text-sm">Active</div>
                <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ stats?.exams_by_status?.active || 0 }}</div>
            </div>
            
            <!-- Completed -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-gray-500 dark:text-gray-400 text-sm">Completed</div>
                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ stats?.exams_by_status?.completed || 0 }}</div>
            </div>
            </div>
            
            <!-- Latest Exam -->
            <div v-if="stats?.latest_exam" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Latest Exam</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                <div class="text-gray-500 dark:text-gray-400 text-sm">Name</div>
                <div class="font-medium">{{ stats.latest_exam.name }}</div>
                </div>
                <div>
                <div class="text-gray-500 dark:text-gray-400 text-sm">Status</div>
                <div class="font-medium capitalize">{{ stats.latest_exam.status }}</div>
                </div>
                <div>
                <div class="text-gray-500 dark:text-gray-400 text-sm">Start Date</div>
                <div class="font-medium">{{ stats.latest_exam.start_date }}</div>
                </div>
                <div>
                <div class="text-gray-500 dark:text-gray-400 text-sm">End Date</div>
                <div class="font-medium">{{ stats.latest_exam.end_date }}</div>
                </div>
            </div>
            </div>
            
            <!-- Marking Progress -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Marking Progress</h2>
            
            <!-- Exam Selector -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Exam</label>
                <select 
                v-model="selectedExamId"
                class="w-full md:w-64 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm"
                >
                <option :value="null">Select an exam</option>
                <option v-for="exam in exams" :key="exam.id" :value="exam.id">
                    {{ exam.name }}
                </option>
                </select>
            </div>
            
            <div v-if="stats?.marking_progress" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="text-blue-600 dark:text-blue-400 text-sm">Total Registered</div>
                <div class="text-2xl font-bold">{{ stats.marking_progress.total_registered }}</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                <div class="text-green-600 dark:text-green-400 text-sm">Marked</div>
                <div class="text-2xl font-bold">{{ stats.marking_progress.marked }}</div>
                </div>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                <div class="text-yellow-600 dark:text-yellow-400 text-sm">Pending</div>
                <div class="text-2xl font-bold">{{ stats.marking_progress.pending }}</div>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                <div class="text-purple-600 dark:text-purple-400 text-sm">Progress</div>
                <div class="text-2xl font-bold">{{ stats.marking_progress.percentage }}%</div>
                </div>
            </div>
            
            <div v-else class="text-gray-500 dark:text-gray-400">
                Select an exam to view marking progress.
            </div>
            </div>
            
            <!-- Grade Distribution -->
            <div v-if="stats?.grade_distribution?.length" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Grade Distribution</h2>
            <div class="flex flex-wrap gap-2">
                <div 
                v-for="item in stats.grade_distribution" 
                :key="item.grade"
                class="bg-gray-100 dark:bg-gray-700 rounded-lg px-4 py-2"
                >
                <span class="font-semibold">{{ item.grade }}</span>
                <span class="text-gray-500 dark:text-gray-400 ml-2">{{ item.count }}</span>
                </div>
            </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

interface Exam {
  id: number
  name: string
  status: string
  start_date: string
  end_date: string
}

interface Stats {
  total_exams: number
  latest_exam: Exam | null
  exams_by_status: Record<string, number>
  marking_progress: {
    total_registered: number
    marked: number
    pending: number
    percentage: number
  } | null
  grade_distribution: Array<{
    grade: string
    count: number
  }>
  active_grade_system: any
}

const stats = ref<Stats | null>(null)
const loading = ref(false)
const selectedExamId = ref<number | null>(null)
const exams = ref<Exam[]>([])

const loadStats = () => {
  loading.value = true
  const params = selectedExamId.value ? { exam_id: selectedExamId.value } : {}
  
  router.get(route('exam.dashboard.stats'), params, {
    preserveScroll: true,
    onSuccess: (page: any) => {
      stats.value = page.props.stats
      loading.value = false
    },
    onError: () => {
      loading.value = false
    }
  })
}

const loadExams = () => {
  router.get(route('exam.index-page'), {}, {
    preserveScroll: true,
    onSuccess: (page: any) => {
      exams.value = page.props.exams || []
    }
  })
}

onMounted(() => {
  loadStats()
  loadExams()
})

watch(selectedExamId, () => {
  loadStats()
})

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
    { title: 'Dashboard', href: '/exams/dashboard' },
]
</script>
