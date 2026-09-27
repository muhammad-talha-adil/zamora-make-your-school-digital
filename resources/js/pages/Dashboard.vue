<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import LineChart from '@/components/charts/LineChart.vue';
import { formatCurrency, formatDate, formatRelativeTime } from '@/utils/format';

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

const isLoading = ref(true);
const chartLoaded = ref(false);

onMounted(() => {
    setTimeout(() => {
        isLoading.value = false;
    }, 300);
});

const statCards = computed(() => [
    {
        label: 'Active Students',
        value: props.stats.active_students,
        icon: 'users',
        iconBg: 'bg-primary/10',
        iconColor: 'text-primary',
        trend: { value: '+2.1%', direction: 'up' as const },
    },
    {
        label: 'Active Staff',
        value: props.stats.active_staff,
        icon: 'user-check',
        iconBg: 'bg-primary/10',
        iconColor: 'text-primary',
        trend: { value: '+1.5%', direction: 'up' as const },
    },
    {
        label: 'Fees Collected Today',
        value: formatCurrency(props.fee_summary.today),
        icon: 'banknote',
        iconBg: 'bg-success/10',
        iconColor: 'text-success',
        trend: { value: '+5.3%', direction: 'up' as const },
    },
    {
        label: 'Fees This Month',
        value: formatCurrency(props.fee_summary.month),
        icon: 'wallet',
        iconBg: 'bg-primary/10',
        iconColor: 'text-primary',
        trend: { value: '+12%', direction: 'up' as const },
    },
]);

const getTrendIcon = (direction: 'up' | 'down' | 'neutral') => {
    switch (direction) {
        case 'up':
            return 'trending-up';
        case 'down':
            return 'trending-down';
        default:
            return 'minus';
    }
};

const getTrendClass = (direction: 'up' | 'down' | 'neutral') => {
    switch (direction) {
        case 'up':
            return 'text-success';
        case 'down':
            return 'text-destructive';
        default:
            return 'text-muted-foreground';
    }
};

const lowStockItems = [
    { name: 'A4 Paper Reams', current: 12, threshold: 20, unit: 'reams' },
    { name: 'Blue Ballpoint Pens', current: 45, threshold: 100, unit: 'pcs' },
    { name: 'Whiteboard Markers', current: 8, threshold: 24, unit: 'pcs' },
    { name: 'Stapler Pins', current: 3, threshold: 10, unit: 'boxes' },
];

const groupedActivity = computed(() => {
    const groups: Record<string, ActivityItem[]> = {};
    for (const activity of props.recent_activity) {
        const date = activity.at ? new Date(activity.at).toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' }) : 'Unknown';
        if (!groups[date]) groups[date] = [];
        groups[date].push(activity);
    }
    return groups;
});
</script>

<template>
    <AppLayout>
        <Head title="Dashboard" />

        <div class="space-y-6 p-4 md:p-6 lg:p-8">
            <!-- Welcome Section -->
            <div class="bg-gradient-to-r from-primary to-primary/80 rounded-xl p-6 md:p-8 text-primary-foreground shadow-sm animate-fade-in">
                <div class="max-w-4xl">
                    <h1 class="text-2xl md:text-3xl font-bold tracking-tight mb-2">
                        Welcome back, {{ $page.props.auth.user?.name || 'User' }}!
                    </h1>
                    <p class="text-sm md:text-base text-primary-foreground/80">
                        Here's what's happening with your school today.
                    </p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" role="region" aria-label="Key metrics">
                <div
                    v-for="card in statCards"
                    :key="card.label"
                    class="bg-card rounded-xl border border-border p-5 shadow-sm hover:-translate-y-1 transition-transform duration-150 ease-out hover:shadow-md focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2"
                    tabindex="0"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-muted-foreground truncate">{{ card.label }}</p>
                            <div class="mt-1 flex items-baseline gap-2">
                                <p class="text-2xl md:text-3xl font-bold text-foreground" v-if="!isLoading">{{ card.value }}</p>
                                <div v-else class="h-8 w-32 animate-pulse bg-muted rounded" />
                            </div>
                            <div class="mt-2 flex items-center gap-1.5">
                                <Icon
                                    :icon="getTrendIcon(card.trend.direction)"
                                    :class="getTrendClass(card.trend.direction)"
                                    class="h-4 w-4"
                                />
                                <span :class="getTrendClass(card.trend.direction)" class="text-sm font-medium">
                                    {{ card.trend.value }}
                                </span>
                                <span class="text-xs text-muted-foreground">vs last period</span>
                            </div>
                        </div>
                        <div :class="['p-3 rounded-lg', card.iconBg]">
                            <Icon :icon="card.icon" :class="card.iconColor" class="h-6 w-6" />
                        </div>
                    </div>

                    <!-- Skeleton loader -->
                    <div v-if="isLoading" class="mt-4 space-y-2 animate-pulse">
                        <div class="h-4 w-3/4 bg-muted rounded" />
                        <div class="h-3 w-1/2 bg-muted rounded" />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Attendance & Inventory -->
                <div class="bg-card rounded-xl border border-border p-4 md:p-6 shadow-sm animate-fade-in">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-foreground">Today at a Glance</h2>
                        <span class="text-xs text-muted-foreground">Updated just now</span>
                    </div>
                    <div v-if="props.attendance_today.has_register" class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-success/10 rounded-lg">
                                    <Icon icon="user-check" class="h-5 w-5 text-success" />
                                </div>
                                <span class="text-sm text-muted-foreground">Present</span>
                            </div>
                            <span class="font-semibold text-success text-lg">{{ props.attendance_today.present }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-destructive/10 rounded-lg">
                                    <Icon icon="user-x" class="h-5 w-5 text-destructive" />
                                </div>
                                <span class="text-sm text-muted-foreground">Absent</span>
                            </div>
                            <span class="font-semibold text-destructive text-lg">{{ props.attendance_today.absent }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-info/10 rounded-lg">
                                    <Icon icon="clipboard-list" class="h-5 w-5 text-info" />
                                </div>
                                <span class="text-sm text-muted-foreground">Total</span>
                            </div>
                            <span class="font-semibold text-foreground text-lg">{{ props.attendance_today.present + props.attendance_today.absent }}</span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground py-4 text-center">No attendance register has been taken yet today.</p>

                    <div class="mt-4 pt-4 border-t border-border">
                        <div class="flex items-center justify-between p-3 rounded-lg"
                             :class="props.low_stock_count > 0 ? 'bg-warning/10' : 'bg-success/10'">
                            <div class="flex items-center gap-3">
                                <div :class="props.low_stock_count > 0 ? 'p-2 bg-warning/10 rounded-lg' : 'p-2 bg-success/10 rounded-lg'">
                                    <Icon
                                        :icon="props.low_stock_count > 0 ? 'alert-triangle' : 'check-circle'"
                                        :class="props.low_stock_count > 0 ? 'h-5 w-5 text-warning' : 'h-5 w-5 text-success'"
                                    />
                                </div>
                                <span class="text-sm text-muted-foreground">Low Stock Alerts</span>
                            </div>
                            <span
                                class="font-semibold text-lg"
                                :class="props.low_stock_count > 0 ? 'text-warning' : 'text-success'"
                            >
                                {{ props.low_stock_count }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Fee Trend Chart -->
                <div class="bg-card rounded-xl border border-border p-4 md:p-6 shadow-sm animate-fade-in" style="animation-delay: 100ms">
                    <h2 class="text-lg font-semibold text-foreground mb-4">Fee Collection — Last 7 Days</h2>
                    <div class="relative" style="height: 220px;">
                        <LineChart
                            v-if="feeTrendPoints.length > 0 && !isLoading"
                            :points="feeTrendPoints"
                            :format-value="(value) => formatCurrency(value)"
                            color-var="--primary"
                            ref="chartRef"
                        />
                        <div v-else-if="isLoading" class="absolute inset-0 flex items-center justify-center">
                            <div class="space-y-3 w-3/4">
                                <div class="h-4 w-full animate-pulse bg-muted rounded" />
                                <div class="h-32 w-full animate-pulse bg-muted rounded" />
                            </div>
                        </div>
                        <p v-else class="text-sm text-muted-foreground text-center py-8">No collections recorded in the last 7 days.</p>
                    </div>
                </div>

                <!-- Upcoming Exams -->
                <div class="bg-card rounded-xl border border-border p-4 md:p-6 shadow-sm animate-fade-in" style="animation-delay: 200ms">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-foreground">Upcoming Exam Papers</h2>
                        <a href="#" class="text-sm text-primary hover:underline">View all</a>
                    </div>
                    <div v-if="props.upcoming_exam_papers.length" class="space-y-2">
                        <div
                            v-for="paper in props.upcoming_exam_papers"
                            :key="paper.id"
                            class="group flex items-center justify-between gap-3 p-3 rounded-lg bg-muted/30 hover:bg-muted/50 transition-colors duration-150"
                        >
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-foreground truncate">{{ paper.subject_name || 'Subject' }}</p>
                                <p class="text-xs text-muted-foreground truncate">{{ paper.exam_name }} · {{ paper.class_name || 'All classes' }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                    :class="new Date(paper.paper_date || '') < new Date() ? 'bg-destructive/10 text-destructive' : 'bg-primary/10 text-primary'"
                                >
                                    {{ paper.paper_date ? formatDate(paper.paper_date) : 'TBD' }}
                                </span>
                                <Icon icon="chevron-right" class="h-4 w-4 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-10 text-center">
                        <div class="p-3 bg-muted rounded-full mb-3">
                            <Icon icon="calendar-x" class="h-8 w-8 text-muted-foreground" />
                        </div>
                        <p class="text-sm text-muted-foreground">No exam papers scheduled in the next two weeks.</p>
                        <a href="#" class="mt-2 text-sm text-primary hover:underline">Schedule an exam</a>
                    </div>
                </div>
            </div>

            <!-- Low Stock Detail -->
            <div v-if="lowStockItems.length" class="bg-card rounded-xl border border-border shadow-sm animate-fade-in" style="animation-delay: 300ms">
                <div class="p-4 md:p-6 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">Low Stock Items</h2>
                    <span class="text-sm text-muted-foreground">{{ lowStockItems.length }} items need attention</span>
                </div>
                <div class="divide-y divide-border">
                    <div
                        v-for="item in lowStockItems"
                        :key="item.name"
                        class="p-4 md:p-6 flex items-center justify-between gap-4 hover:bg-muted/30 transition-colors"
                    >
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-foreground truncate">{{ item.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ item.current }} / {{ item.threshold }} {{ item.unit }} remaining</p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-warning/10 text-warning"
                            >
                                Low
                            </span>
                            <button
                                class="btn-outline text-sm px-3 py-1.5"
                                @click.prevent="console.log('Restock:', item.name)"
                            >
                                Restock
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-card rounded-xl border border-border shadow-sm animate-fade-in" style="animation-delay: 400ms">
                <div class="p-4 md:p-6 border-b border-border flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">Recent Activity</h2>
                    <a href="#" class="text-sm text-primary hover:underline">View all</a>
                </div>
                <div v-if="props.recent_activity.length" class="divide-y divide-border">
                    <template v-for="(activities, date) in groupedActivity" :key="date">
                        <div class="px-4 md:px-6 py-3 bg-muted/30 border-b border-border/50">
                            <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">{{ date }}</span>
                        </div>
                        <div
                            v-for="(activity, index) in activities"
                            :key="index"
                            class="px-4 md:px-6 py-3 flex items-start gap-3 hover:bg-muted/30 transition-colors"
                        >
                            <div class="shrink-0 w-2 h-2 mt-2 bg-primary rounded-full" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-muted-foreground">{{ activity.description }}</p>
                            </div>
                            <span class="text-xs text-muted-foreground shrink-0 ml-2">{{ activity.at ? formatRelativeTime(activity.at) : '' }}</span>
                        </div>
                    </template>
                    <div class="px-4 md:px-6 py-3 text-center">
                        <button class="btn-ghost text-sm">Load more</button>
                    </div>
                </div>
                <div v-else class="p-4 md:p-6 text-center">
                    <div class="p-3 bg-muted rounded-full w-fit mx-auto mb-3">
                        <Icon icon="clock" class="h-8 w-8 text-muted-foreground" />
                    </div>
                    <p class="text-sm text-muted-foreground">Nothing to show yet.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>