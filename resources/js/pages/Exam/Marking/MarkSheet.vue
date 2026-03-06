<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Mark Sheet" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div class="space-y-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Exam</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ paper.exam_group?.exam_offering?.exam?.name }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Campus</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ paper.exam_group?.exam_offering?.campus?.name }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Class/Section</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ paper.exam_group?.class?.name }} - {{ paper.exam_group?.section?.name }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Subject</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ paper.subject?.name }}</p>
                    </div>
                </div>
            </div>

            <!-- Mark Entry Form -->
            <form @submit.prevent="submitMarks">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300 w-16">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Student</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300 w-32">Obtained Marks</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300 w-24">Absent</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300 w-24">Exempt</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                <tr v-for="(student, index) in students" :key="student.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ index + 1 }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ student.name }}</div>
                                        <div class="text-xs text-gray-500">{{ student.registration_no }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <Input
                                            v-model="marksForm[student.id].obtained_marks"
                                            type="number"
                                            :min="0"
                                            :max="paper.total_marks"
                                            :disabled="marksForm[student.id].is_absent || marksForm[student.id].is_exempt"
                                            class="w-24"
                                        />
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <input type="checkbox" v-model="marksForm[student.id].is_absent" class="rounded border-gray-300" @change="handleAbsentChange(student.id)" />
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <input type="checkbox" v-model="marksForm[student.id].is_exempt" class="rounded border-gray-300" @change="handleExemptChange(student.id)" />
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <Input v-model="marksForm[student.id].remarks" type="text" placeholder="Optional" class="w-full" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4 flex justify-end gap-3">
                    <Button type="button" variant="outline" @click="router.visit(route('exam.marking.select-page'))">
                        Back
                    </Button>
                    <Button type="submit" :disabled="saving">
                        <Icon icon="save" class="mr-1" />
                        {{ saving ? 'Saving...' : 'Save Marks' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, onMounted } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Icon from '@/components/Icon.vue';
import type { MarkSheetProps } from '@/types/exam';

const props = defineProps<MarkSheetProps>();

const paper = props.paper;
const saving = reactive({ value: false });

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
    { title: 'Marking', href: '/exams/marking' },
    { title: paper.subject?.name || 'Mark Sheet', href: `/exams/papers/${paper.id}/mark-sheet` },
];

interface StudentInfo {
    id: number;
    name: string;
    registration_no: string;
}

const students = reactive<StudentInfo[]>([]);

interface MarksFormData {
    obtained_marks: string;
    is_absent: boolean;
    is_exempt: boolean;
    remarks: string;
}

const marksForm = reactive<Record<number, MarksFormData>>({});

onMounted(() => {
    // Load existing result lines if any
    if (paper.exam_result_lines) {
        paper.exam_result_lines.forEach((line: any) => {
            if (line.student) {
                students.push({
                    id: line.student.id,
                    name: line.student.user?.name || 'Unknown',
                    registration_no: line.student.registration_no || ''
                });
                marksForm[line.student.id] = {
                    obtained_marks: line.obtained_marks?.toString() || '',
                    is_absent: line.is_absent || false,
                    is_exempt: line.is_exempt || false,
                    remarks: line.remarks || ''
                };
            }
        });
    }
});

const handleAbsentChange = (studentId: number) => {
    if (marksForm[studentId].is_absent) {
        marksForm[studentId].is_exempt = false;
        marksForm[studentId].obtained_marks = '';
    }
};

const handleExemptChange = (studentId: number) => {
    if (marksForm[studentId].is_exempt) {
        marksForm[studentId].is_absent = false;
        marksForm[studentId].obtained_marks = '';
    }
};

const submitMarks = () => {
    saving.value = true;
    
    const studentsData = Object.keys(marksForm).map(studentId => ({
        student_id: parseInt(studentId),
        obtained_marks: marksForm[parseInt(studentId)].obtained_marks ? parseFloat(marksForm[parseInt(studentId)].obtained_marks) : null,
        is_absent: marksForm[parseInt(studentId)].is_absent,
        is_exempt: marksForm[parseInt(studentId)].is_exempt,
        remarks: marksForm[parseInt(studentId)].remarks || null,
    }));

    router.post(route('exam.marking.save-bulk', { paperId: paper.id }), {
        students: studentsData,
    }, {
        onFinish: () => {
            saving.value = false;
        },
        onSuccess: () => {
            // Show success message
        },
    });
};
</script>
