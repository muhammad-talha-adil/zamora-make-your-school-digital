<script setup lang="ts">
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import GradeTab from './GradeTab.vue'
import ExamTypesTable from '@/components/tables/ExamTypesTable.vue'

interface GradeSystem {
  id: number
  name: string
  is_active: boolean
  is_default: boolean
  rounding_mode: string
  precision: number
  grade_system_items: GradeSystemItem[]
}

interface GradeSystemItem {
  id: number
  grade_letter: string
  min_percentage: number
  max_percentage: number
  grade_point: number
  sort_order: number
}

interface Props {
  gradeSystems: GradeSystem[]
  examTypes: any
}

defineProps<Props>()

const activeTab = ref('grade-scales')

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
    { title: 'Settings', href: '/exams/settings' },
]
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Exam Settings" />

        <div class="p-6">
            <div class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Exam Settings
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Manage exam settings and grade systems.
                    </p>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-8 overflow-x-auto">
                        <button
                            @click="activeTab = 'grade-scales'"
                            :class="[
                                activeTab === 'grade-scales'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            Grade Scales
                        </button>
                        <button
                            @click="activeTab = 'exam-types'"
                            :class="[
                                activeTab === 'exam-types'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                                'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                            ]"
                        >
                            Exam Types
                        </button>
                    </nav>
                </div>

                <!-- Grade Scales Tab -->
                <div v-if="activeTab === 'grade-scales'">
                    <GradeTab :grade-systems="gradeSystems" />
                </div>

                <!-- Exam Types Tab -->
                <div v-if="activeTab === 'exam-types'">
                    <ExamTypesTable :exam-types="examTypes" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
