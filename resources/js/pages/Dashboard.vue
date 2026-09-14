<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import LineChart from '@/components/charts/LineChart.vue';
import { formatCurrency, formatDate } from '@/utils/format';

interface ExamPaperSummary {
    id: number;
    exam_name: string | null;
    subject_name: string | null;
    class_name: string | null;
    paper_date: string | null;
}

interface ActivityItem {
    description: string;
    at: string | null;
}

interface FeeTrendPoint {
    date: string;
    total: number;
}

interface Props {
    stats: {
        active_students: number;
        active_staff: number;
    };
    fee_summary: {
        today: number;
        month: number;
    };
    fee_trend: FeeTrendPoint[];
    attendance_today: {
        present: number;
        absent: number;
        has_register: boolean;
    };
    upcoming_exam_papers: ExamPaperSummary[];
    low_stock_count: number;
    recent_activity: ActivityItem[];
}

const props = defineProps<Props>();

const feeTrendPoints = computed(() =>
    props.fee_trend.map((point) => ({
        label: new Date(point.date).toLocaleDateString('en-US', { weekday: 'short' }),
        value: point.total,
    })),
);
</script>

<template>
    <AppLayout>
        <Head title="Dashboard" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Welcome Section -->
            <div class="bg-linear-to-r from-primary to-primary/80 rounded-lg p-6 text-primary-foreground">
                <h1 class="text-2xl md:text-3xl font-bold mb-2">
                    Welcome back, {{ $page.props.auth.user?.name || 'User' }}!
                </h1>
                <p class="text-primary-foreground/80">
                    Here's what's happening with your school today.
                </p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm text-muted-foreground">Active Students</p>
                            <p class="mt-1 text-2xl font-bold text-foreground">{{ props.stats.active_students }}</p>
                        </div>
                        <div class="p-3 bg-primary/10 rounded-lg">
                            <Icon icon="users" class="h-6 w-6 text-primary" />
                        </div>
                    </div>
                </div>
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm text-muted-foreground">Active Staff</p>
                            <p class="mt-1 text-2xl font-bold text-foreground">{{ props.stats.active_staff }}</p>
                        </div>
                        <div class="p-3 bg-primary/10 rounded-lg">
                            <Icon icon="user-check" class="h-6 w-6 text-primary" />
                        </div>
                    </div>
                </div>
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm text-muted-foreground">Fees Collected Today</p>
                            <p class="mt-1 text-2xl font-bold text-success">{{ formatCurrency(props.fee_summary.today) }}</p>
                        </div>
                        <div class="p-3 bg-success/10 rounded-lg">
                            <Icon icon="banknote" class="h-6 w-6 text-success" />
                        </div>
                    </div>
                </div>
                <div class="bg-card rounded-lg border border-border p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm text-muted-foreground">Fees Collected This Month</p>
                            <p class="mt-1 text-2xl font-bold text-primary">{{ formatCurrency(props.fee_summary.month) }}</p>
                        </div>
                        <div class="p-3 bg-primary/10 rounded-lg">
                            <Icon icon="wallet" class="h-6 w-6 text-primary" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Attendance & Inventory -->
                <div class="bg-card rounded-lg border border-border p-4 md:p-6">
                    <h2 class="text-lg font-semibold text-foreground mb-4">Today at a Glance</h2>
                    <div v-if="props.attendance_today.has_register" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Present</span>
                            <span class="font-semibold text-success">{{ props.attendance_today.present }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Absent</span>
                            <span class="font-semibold text-destructive">{{ props.attendance_today.absent }}</span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">No attendance register has been taken yet today.</p>

                    <div class="mt-4 pt-4 border-t border-border flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Low Stock Alerts</span>
                        <span
                            class="font-semibold"
                            :class="props.low_stock_count > 0 ? 'text-warning' : 'text-foreground'"
                        >
                            {{ props.low_stock_count }}
                        </span>
                    </div>
                </div>

                <!-- Fee Trend Chart -->
                <div class="bg-card rounded-lg border border-border p-4 md:p-6">
                    <h2 class="text-lg font-semibold text-foreground mb-4">Fee Collection — Last 7 Days</h2>
                    <LineChart
                        v-if="feeTrendPoints.length > 0"
                        :points="feeTrendPoints"
                        :format-value="(value) => formatCurrency(value)"
                        color-var="--primary"
                    />
                    <p v-else class="text-sm text-muted-foreground">No collections recorded in the last 7 days.</p>
                </div>

                <!-- Upcoming Exams -->
                <div class="bg-card rounded-lg border border-border p-4 md:p-6">
                    <h2 class="text-lg font-semibold text-foreground mb-4">Upcoming Exam Papers</h2>
                    <div v-if="props.upcoming_exam_papers.length" class="space-y-3">
                        <div
                            v-for="paper in props.upcoming_exam_papers"
                            :key="paper.id"
                            class="flex items-center justify-between gap-2"
                        >
                            <div>
                                <p class="text-sm font-medium text-foreground">{{ paper.subject_name || 'Subject' }}</p>
                                <p class="text-xs text-muted-foreground">{{ paper.exam_name }} · {{ paper.class_name || 'All classes' }}</p>
                            </div>
                            <span class="text-xs font-semibold text-muted-foreground">{{ paper.paper_date ? formatDate(paper.paper_date) : '-' }}</span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">No exam papers scheduled in the next two weeks.</p>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-card rounded-lg border border-border p-4 md:p-6">
                <h2 class="text-lg font-semibold text-foreground mb-4">Recent Activity</h2>
                <div v-if="props.recent_activity.length" class="space-y-3">
                    <div v-for="(activity, index) in props.recent_activity" :key="index" class="flex items-center gap-3">
                        <div class="w-2 h-2 bg-primary rounded-full shrink-0"></div>
                        <p class="text-sm text-muted-foreground flex-1">{{ activity.description }}</p>
                        <span class="text-xs text-muted-foreground shrink-0">{{ activity.at ? formatDate(activity.at) : '' }}</span>
                    </div>
                </div>
                <p v-else class="text-sm text-muted-foreground">Nothing to show yet.</p>
            </div>
        </div>
    </AppLayout>
</template>
