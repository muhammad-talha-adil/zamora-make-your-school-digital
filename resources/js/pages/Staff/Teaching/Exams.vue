<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';

interface UpcomingPaper {
    id: number;
    exam: string | null;
    subject: string | null;
    class: string | null;
    section: string | null;
    paper_date: string | null;
    start_time: string | null;
    end_time: string | null;
}

interface Props {
    papers: UpcomingPaper[];
}

const props = defineProps<Props>();

const formatDate = (date: string | null): string => {
    if (!date) {
        return '—';
    }

    return new Date(date).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
};

const classLabel = (paper: UpcomingPaper): string => {
    return [paper.class, paper.section].filter(Boolean).join(' - ') || 'All classes';
};
</script>

<template>
    <AppLayout>
        <Head title="My Upcoming Exams" />

        <div class="space-y-4 md:space-y-6">
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-foreground">Upcoming Exam Papers</h1>
                <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                    The timetabled papers for the classes you teach.
                </p>
            </div>

            <div v-if="props.papers.length === 0" class="bg-card rounded-lg border border-border p-8 text-center text-muted-foreground">
                <Icon icon="clipboard-list" class="h-10 w-10 mx-auto mb-3 text-muted-foreground" />
                No upcoming papers have been timetabled for your classes.
            </div>

            <div v-else class="hidden lg:block overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Subject</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Exam</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="paper in props.papers" :key="paper.id" class="transition-colors hover:bg-accent">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-foreground">{{ paper.subject }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ classLabel(paper) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ paper.exam }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ formatDate(paper.paper_date) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ paper.start_time }} – {{ paper.end_time }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="props.papers.length > 0" class="block lg:hidden space-y-3">
                <div v-for="paper in props.papers" :key="paper.id" class="bg-card rounded-lg border border-border p-4 space-y-2">
                    <div class="flex justify-between items-start gap-2">
                        <div class="font-medium text-foreground">{{ paper.subject }}</div>
                        <div class="text-xs text-muted-foreground">{{ classLabel(paper) }}</div>
                    </div>
                    <div class="text-xs text-muted-foreground">{{ paper.exam }}</div>
                    <div class="text-sm text-muted-foreground">{{ formatDate(paper.paper_date) }}, {{ paper.start_time }} – {{ paper.end_time }}</div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
