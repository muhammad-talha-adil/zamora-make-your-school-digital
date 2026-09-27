<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/utils/format';

interface ChildOption {
    id: number;
    name: string | null;
    class?: string | null;
    section?: string | null;
    avatar?: string | null;
}

interface OutstandingVoucher {
    id: number;
    voucher_no: string | null;
    balance_amount: number;
    due_date: string | null;
}

interface LatestResult {
    id: number;
    exam: string | null;
    status: string | null;
    result_status: string | null;
    grade: string | null;
    percentage: number | null;
}

interface TodayAttendance {
    status: string | null;
    code: string | null;
    check_in?: string | null;
}

interface Props {
    student: ChildOption;
    students: ChildOption[];
    outstandingVoucher: OutstandingVoucher | null;
    latestResult: LatestResult | null;
    todayAttendance: TodayAttendance | null;
}

const props = defineProps<Props>();

const resultBadgeClass = computed(() => {
    return {
        pass: 'bg-success/10 text-success',
        fail: 'bg-destructive/10 text-destructive',
    }[props.latestResult?.result_status ?? ''] ?? 'bg-muted text-muted-foreground';
});

const attendanceBadgeClass = computed(() => {
    return {
        P: 'bg-success/10 text-success',
        A: 'bg-destructive/10 text-destructive',
        L: 'bg-primary/10 text-primary',
        LT: 'bg-warning/10 text-warning',
        HD: 'bg-muted text-muted-foreground',
    }[props.todayAttendance?.code ?? ''] ?? 'bg-muted text-muted-foreground';
});

const attendanceStatusLabel = computed(() => {
    return {
        P: 'Present',
        A: 'Absent',
        L: 'Late',
        LT: 'Left Early',
        HD: 'Holiday',
    }[props.todayAttendance?.code ?? ''] ?? props.todayAttendance?.status ?? '—';
});

const breadcrumbs = computed(() => [
    { title: 'Dashboard', href: route('portal.index') },
]);

const statCardsVisible = ref(false);

onMounted(() => {
    requestAnimationFrame(() => {
        statCardsVisible.value = true;
    });
});
</script>

<template>
<PortalLayout :breadcrumbs="breadcrumbs" :current-child="props.student" :children="props.students">
    <template #default>
        <Head title="Dashboard" />
        <div class="space-y-6 animate-fade-slide-up">
            <!-- Welcome Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 animate-fade-slide-up stagger-1">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-foreground">
                        Welcome back, {{ props.student.name ?? 'Student' }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Here's a quick look at your fees, results, and attendance.
                    </p>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" v-show="statCardsVisible">
                <!-- Fee Balance Card -->
                <Card class="card-interactive animate-fade-slide-up stagger-1 overflow-hidden">
                    <CardHeader class="pb-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <CardTitle class="text-sm font-medium text-muted-foreground">Fee Balance</CardTitle>
                                <p class="text-3xl font-bold text-foreground mt-1">
                                    {{ props.outstandingVoucher ? formatCurrency(props.outstandingVoucher.balance_amount) : formatCurrency(0) }}
                                </p>
                            </div>
                            <div class="p-3 rounded-xl shrink-0" :class="props.outstandingVoucher ? 'bg-warning/10' : 'bg-success/10'">
                                <Icon :icon="props.outstandingVoucher ? 'alert-triangle' : 'check-circle'" :size="24" :class="props.outstandingVoucher ? 'text-warning' : 'text-success'" />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-0 space-y-3">
                        <p v-if="props.outstandingVoucher" class="text-sm text-muted-foreground flex items-center gap-1">
                            <Icon icon="calendar" :size="14" />
                            Due {{ formatDate(props.outstandingVoucher.due_date ?? '') }}
                        </p>
                        <p v-else class="text-sm text-success flex items-center gap-1">
                            <Icon icon="check-circle" :size="14" />
                            No outstanding balance
                        </p>

                        <Link
                            :href="props.outstandingVoucher ? route('portal.fees.show', props.outstandingVoucher.id) : route('portal.fees.index')"
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline w-full justify-center py-2"
                        >
                            {{ props.outstandingVoucher ? 'View Voucher' : 'View All Fee Vouchers' }}
                            <Icon icon="arrow-right" :size="14" />
                        </Link>
                    </CardContent>
                </Card>

                <!-- Latest Exam Result Card -->
                <Card class="card-interactive animate-fade-slide-up stagger-2 overflow-hidden">
                    <CardHeader class="pb-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <CardTitle class="text-sm font-medium text-muted-foreground">Latest Exam Result</CardTitle>
                                <p class="text-3xl font-bold text-foreground mt-1 truncate">
                                    {{ props.latestResult?.grade ?? (props.latestResult?.percentage != null ? `${props.latestResult.percentage}%` : '—') }}
                                </p>
                            </div>
                            <div class="p-3 rounded-xl bg-primary/10 shrink-0">
                                <Icon icon="award" class="text-primary" :size="24" />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-0 space-y-3">
                        <div v-if="props.latestResult" class="flex flex-wrap items-center gap-2 text-sm">
                            <p class="text-muted-foreground truncate flex-1 min-w-[120px]">{{ props.latestResult.exam }}</p>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap" :class="resultBadgeClass">
                                {{ props.latestResult.result_status ?? 'Published' }}
                            </span>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">No published result yet</p>

                        <a
                            v-if="props.latestResult"
                            :href="route('portal.exams.show', props.latestResult.id)"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline w-full justify-center py-2"
                        >
                            View Full Result
                            <Icon icon="arrow-right" :size="14" />
                        </a>
                        <Link v-else :href="route('portal.exams.index')" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline w-full justify-center py-2">
                            View All Results
                            <Icon icon="arrow-right" :size="14" />
                        </Link>
                    </CardContent>
                </Card>

                <!-- Today's Attendance Card -->
                <Card class="card-interactive animate-fade-slide-up stagger-3 overflow-hidden">
                    <CardHeader class="pb-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <CardTitle class="text-sm font-medium text-muted-foreground">Today's Attendance</CardTitle>
                                <p class="text-3xl font-bold text-foreground mt-1">
                                    <span v-if="props.todayAttendance" class="px-3 py-1 rounded-full text-base" :class="attendanceBadgeClass">
                                        {{ attendanceStatusLabel }}
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </p>
                            </div>
                            <div class="p-3 rounded-xl bg-accent shrink-0">
                                <Icon icon="calendar-check" class="text-foreground" :size="24" />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-0 space-y-3">
                        <p v-if="props.todayAttendance?.check_in" class="text-sm text-muted-foreground flex items-center gap-1">
                            <Icon icon="clock" :size="14" />
                            Checked in at {{ props.todayAttendance.check_in }}
                        </p>
                        <p v-else-if="!props.todayAttendance" class="text-sm text-muted-foreground">Not marked yet</p>

                        <Link :href="route('portal.attendance.index')" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline w-full justify-center py-2">
                            View Attendance History
                            <Icon icon="arrow-right" :size="14" />
                        </Link>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State Illustrations (when no data) -->
            <div v-if="!props.outstandingVoucher && !props.latestResult && !props.todayAttendance" class="animate-fade-slide-up stagger-4">
                <Card class="bg-muted/50 border-dashed">
                    <CardContent class="py-12 text-center">
                        <Icon icon="sparkles" class="h-12 w-12 mx-auto text-muted-foreground/50 mb-4" />
                        <h3 class="text-lg font-semibold text-foreground mb-1">All caught up!</h3>
                        <p class="text-sm text-muted-foreground mb-6">No outstanding fees, no new results, and attendance not marked yet.</p>
                        <div class="flex flex-col sm:flex-row gap-3 justify-center">
                            <Link :href="route('portal.fees.index')" class="btn-outline">View Fees</Link>
                            <Link :href="route('portal.exams.index')" class="btn-outline">View Exams</Link>
                            <Link :href="route('portal.attendance.index')" class="btn-outline">View Attendance</Link>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Actions -->
            <div class="animate-fade-slide-up stagger-5">
                <h2 class="text-lg font-semibold text-foreground mb-3">Quick Actions</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <Link :href="route('portal.fees.index')" class="btn-outline h-auto py-4 flex flex-col items-center gap-2 text-center">
                        <Icon icon="wallet" :size="24" />
                        <span class="font-medium">View All Fees</span>
                        <span class="text-xs text-muted-foreground">Vouchers & payments</span>
                    </Link>
                    <Link :href="route('portal.exams.index')" class="btn-outline h-auto py-4 flex flex-col items-center gap-2 text-center">
                        <Icon icon="clipboard-list" :size="24" />
                        <span class="font-medium">View All Results</span>
                        <span class="text-xs text-muted-foreground">Exam reports</span>
                    </Link>
                    <Link :href="route('portal.attendance.index')" class="btn-outline h-auto py-4 flex flex-col items-center gap-2 text-center">
                        <Icon icon="calendar" :size="24" />
                        <span class="font-medium">Attendance History</span>
                        <span class="text-xs text-muted-foreground">Day-by-day view</span>
                    </Link>
                    <Link :href="route('portal.leave.create')" class="btn-outline h-auto py-4 flex flex-col items-center gap-2 text-center">
                        <Icon icon="file-text" :size="24" />
                        <span class="font-medium">Apply for Leave</span>
                        <span class="text-xs text-muted-foreground">Submit request</span>
                    </Link>
                </div>
            </div>
        </div>
    </template>
</PortalLayout>
</template>