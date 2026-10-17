<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Edit Exam Paper" />

        <div class="space-y-6 p-4 md:p-6 max-w-3xl">
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-foreground">
                    Edit Exam Paper
                </h1>
                <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                    {{ props.paper.exam?.name }} - {{ props.paper.campus?.name }} - {{ props.paper.class?.name }} {{ props.paper.section?.name }}
                </p>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <div class="bg-card rounded-lg border border-border p-6 space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="subject_id">Subject *</Label>
                            <SearchableSelect
                                id="subject_id"
                                v-model="form.subject_id"
                                :options="subjectOptions"
                                placeholder="Select Subject"
                            />
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

                        <div class="space-y-2">
                            <Label for="subject_role">Subject Role</Label>
                            <SearchableSelect
                                id="subject_role"
                                v-model="form.subject_role"
                                :options="subjectRoleOptions"
                                placeholder="Select role"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-3">
                    <Button type="button" variant="outline" @click="router.visit(route('exam.papers.index-page'))">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing || !isValid">
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
import { computed } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
import { useFormValidity } from '@/composables/useFormValidity';
import type { PaperEditProps } from '@/types/exam';

const props = defineProps<PaperEditProps>();

const subjectOptions = computed(() =>
    props.subjects.map((subject) => ({ value: subject.id, label: subject.name })),
);

const subjectRoleOptions = [
    { value: 'core', label: 'Compulsory' },
    { value: 'elective', label: 'Elective' },
    { value: 'additional', label: 'Additional' },
];

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
    { title: 'Papers', href: '/exams/papers' },
    { title: 'Edit Paper', href: `/exams/papers/${props.paper.id}/edit` },
];

const form = useForm({
    subject_id: props.paper.subject_id,
    subject_role: props.paper.subject_role ?? 'core',
    paper_date: props.paper.paper_date,
    start_time: props.paper.start_time,
    end_time: props.paper.end_time,
    total_marks: props.paper.total_marks,
    passing_marks: props.paper.passing_marks,
});

const { isValid } = useFormValidity(form, ['subject_id', 'paper_date', 'start_time', 'end_time', 'total_marks', 'passing_marks']);

const submitForm = () => {
    form.put(route('exam.papers.update', props.paper.id), {
        onSuccess: () => {
            router.visit(route('exam.papers.index-page'));
        },
    });
};
</script>
