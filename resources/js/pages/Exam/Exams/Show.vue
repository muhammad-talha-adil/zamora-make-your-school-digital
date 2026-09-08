<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Exam Details" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">
                        {{ exam.name }}
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                        Exam Details and Configuration
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="router.visit(route('exam.edit-page', exam.id))">
                        <Icon icon="edit" class="mr-1" />
                        Edit
                    </Button>
                    <Button variant="outline" @click="router.visit(route('exam.index-page'))">
                        <Icon icon="arrow-left" class="mr-1" />
                        Back
                    </Button>
                </div>
            </div>

            <!-- Exam Info -->
            <div class="bg-card rounded-lg border border-border p-6">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div class="space-y-1">
                        <p class="text-sm text-muted-foreground">Exam Type</p>
                        <p class="font-medium text-foreground">{{ exam.exam_type?.name }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm text-muted-foreground">Session</p>
                        <p class="font-medium text-foreground">{{ exam.session?.name }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm text-muted-foreground">Status</p>
                        <span
                            :class="[
                                'px-2 py-1 text-xs font-medium rounded-full',
                                exam.status === 'active' ? 'bg-success/10 text-success' :
                                exam.status === 'completed' ? 'bg-primary/10 text-primary' :
                                exam.status === 'cancelled' ? 'bg-destructive/10 text-destructive' :
                                'bg-muted text-foreground'
                            ]"
                        >
                            {{ exam.status }}
                        </span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm text-muted-foreground">Start Date</p>
                        <p class="font-medium text-foreground">{{ exam.start_date }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm text-muted-foreground">End Date</p>
                        <p class="font-medium text-foreground">{{ exam.end_date }}</p>
                    </div>
                </div>
            </div>

            <!-- Offerings Summary -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-lg font-semibold text-foreground mb-4">Offerings</h2>
                <div v-if="exam.exam_offerings && exam.exam_offerings.length > 0" class="space-y-3">
                    <div
                        v-for="offering in exam.exam_offerings"
                        :key="offering.id"
                        class="flex flex-wrap gap-2 items-center justify-between p-3 bg-muted rounded-lg"
                    >
                        <div class="flex items-center gap-3">
                            <Icon icon="building" class="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="font-medium text-foreground">{{ offering.campus?.name }}</p>
                                <p class="text-sm text-muted-foreground">{{ offering.exam_groups?.length || 0 }} Groups</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span v-if="offering.is_published" class="px-2 py-1 text-xs bg-success/10 text-success rounded-full">Published</span>
                            <span v-if="offering.is_locked" class="px-2 py-1 text-xs bg-destructive/10 text-destructive rounded-full">Locked</span>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-muted-foreground">
                    No offerings created yet.
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import type { ExamShowProps } from '@/types/exam';

const props = defineProps<ExamShowProps>();

const exam = props.exam;

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Exams', href: '/exams' },
    { title: exam.name, href: `/exams/${exam.id}` },
];
</script>
