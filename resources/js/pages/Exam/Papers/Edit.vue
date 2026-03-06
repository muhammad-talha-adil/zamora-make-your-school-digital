<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Edit Exam Paper" />

        <div class="space-y-6 p-4 md:p-6 max-w-3xl">
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                    Edit Exam Paper
                </h1>
                <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                    Update exam paper details
                </p>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="exam_group_id">Group *</Label>
                            <select
                                id="exam_group_id"
                                v-model="form.exam_group_id"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                            >
                                <option value="">Select Group</option>
                                <option v-for="group in props.groups" :key="group.id" :value="group.id">
                                    {{ group.exam_offering?.exam?.name }} - {{ group.class?.name }} {{ group.section?.name }}
                                </option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <Label for="subject_id">Subject *</Label>
                            <select
                                id="subject_id"
                                v-model="form.subject_id"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                            >
                                <option value="">Select Subject</option>
                                <option v-for="subject in props.subjects" :key="subject.id" :value="subject.id">
                                    {{ subject.name }}
                                </option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <Label for="paper_date">Date *</Label>
                            <Input id="paper_date" v-model="form.paper_date" type="date" />
                        </div>

                        <div class="space-y-2">
                            <Label for="start_time">Start Time *</Label>
                            <Input id="start_time" v-model="form.start_time" type="time" />
                        </div>

                        <div class="space-y-2">
                            <Label for="end_time">End Time *</Label>
                            <Input id="end_time" v-model="form.end_time" type="time" />
                        </div>

                        <div class="space-y-2">
                            <Label for="total_marks">Total Marks *</Label>
                            <Input id="total_marks" v-model="form.total_marks" type="number" />
                        </div>

                        <div class="space-y-2">
                            <Label for="passing_marks">Passing Marks *</Label>
                            <Input id="passing_marks" v-model="form.passing_marks" type="number" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Button type="button" variant="outline" @click="router.visit(route('exam.papers.index-page'))">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        Update Paper
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { PaperEditProps } from '@/types/exam';

const props = defineProps<PaperEditProps>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
    { title: 'Papers', href: '/exams/papers' },
    { title: 'Edit Paper', href: `/exams/papers/${props.paper.id}/edit` },
];

const form = useForm({
    exam_group_id: props.paper.exam_group_id,
    subject_id: props.paper.subject_id,
    paper_date: props.paper.paper_date,
    start_time: props.paper.start_time,
    end_time: props.paper.end_time,
    total_marks: props.paper.total_marks,
    passing_marks: props.paper.passing_marks,
});

const submitForm = () => {
    form.put(route('exam.papers.update', props.paper.id), {
        onSuccess: () => {
            router.visit(route('exam.papers.index-page'));
        },
    });
};
</script>
