<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Edit Exam" />

        <div class="space-y-6 p-4 md:p-6 max-w-3xl">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">
                        Edit Exam
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                        Update exam configuration
                    </p>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <div class="bg-card rounded-lg border border-border p-6 space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="name">Exam Name *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Enter exam name"
                                :class="{ 'border-destructive': form.errors.name }"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="exam_type_id">Exam Type *</Label>
                            <select
                                id="exam_type_id"
                                v-model="form.exam_type_id"
                                class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm"
                                :class="{ 'border-destructive': form.errors.exam_type_id }"
                            >
                                <option value="">Select Exam Type</option>
                                <option v-for="type in props.examTypes" :key="type.id" :value="type.id">
                                    {{ type.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.exam_type_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="session_id">Session *</Label>
                            <select
                                id="session_id"
                                v-model="form.session_id"
                                class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm"
                                :class="{ 'border-destructive': form.errors.session_id }"
                            >
                                <option value="">Select Session</option>
                                <option v-for="session in props.sessions" :key="session.id" :value="session.id">
                                    {{ session.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.session_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="status">Status</Label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm"
                            >
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <Label for="start_date">Start Date *</Label>
                            <Input
                                id="start_date"
                                v-model="form.start_date"
                                type="date"
                                :class="{ 'border-destructive': form.errors.start_date }"
                            />
                            <InputError :message="form.errors.start_date" />
                        </div>

                        <div class="space-y-2">
                            <Label for="end_date">End Date *</Label>
                            <Input
                                id="end_date"
                                v-model="form.end_date"
                                type="date"
                                :class="{ 'border-destructive': form.errors.end_date }"
                            />
                            <InputError :message="form.errors.end_date" />
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-3">
                    <Button type="button" variant="outline" @click="router.visit(route('exam.index-page'))">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        Update Exam
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
import InputError from '@/components/InputError.vue';
import type { ExamEditProps } from '@/types/exam';

const props = defineProps<ExamEditProps>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
    { title: 'Edit Exam', href: `/exams/${props.exam.id}/edit` },
];

const form = useForm({
    name: props.exam.name,
    exam_type_id: props.exam.exam_type_id,
    session_id: props.exam.session_id,
    start_date: props.exam.start_date,
    end_date: props.exam.end_date,
    status: props.exam.status,
});

const submitForm = () => {
    form.put(route('exam.update', props.exam.id), {
        onSuccess: () => {
            router.visit(route('exam.index-page'));
        },
    });
};
</script>
