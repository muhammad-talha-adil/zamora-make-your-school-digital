<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import Icon from '@/components/Icon.vue';
import { formatCurrency, formatDate } from '@/utils/format';

interface ChildOption {
    id: number;
    name: string | null;
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
</script>

<template>
    <PortalLayout>
        <Head title="My Portal" />

        <div class="space-y-4 md:space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">
                        Welcome, {{ props.student.name ?? 'Student' }}
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                        Here's a quick look at fees, results and attendance.
                    </p>
                </div>

                <label v-if="props.students.length > 1" class="w-full sm:w-auto">
                    <span class="sr-only">Choose child</span>
                    <select
                        :value="props.student.id"
                        class="w-full sm:w-auto rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground"
                        @change="
                            (event) => router.visit(route('portal.index', { student_id: (event.target as HTMLSelectElement).value }))
                        "
                    >
                        <option v-for="child in props.students" :key="child.id" :value="child.id">
                            {{ child.name }}
                        </option>
                    </select>
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Fee balance -->
                <div class="bg-card rounded-lg border border-border p-4 flex flex-col">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm text-muted-foreground">Fee balance</p>
                            <p class="text-2xl font-bold text-foreground mt-1">
                                {{ props.outstandingVoucher ? formatCurrency(props.outstandingVoucher.balance_amount) : formatCurrency(0) }}
                            </p>
                        </div>
                        <div class="p-3 rounded-lg" :class="props.outstandingVoucher ? 'bg-warning/10' : 'bg-success/10'">
                            <Icon icon="wallet" :class="props.outstandingVoucher ? 'text-warning' : 'text-success'" :size="20" />
                        </div>
                    </div>

                    <p v-if="props.outstandingVoucher" class="mt-2 text-xs text-muted-foreground">
                        Due {{ formatDate(props.outstandingVoucher.due_date ?? '') }}
                    </p>
                    <p v-else class="mt-2 text-xs text-muted-foreground">No outstanding balance</p>

                    <Link
                        :href="props.outstandingVoucher ? route('portal.fees.show', props.outstandingVoucher.id) : route('portal.fees.index')"
                        class="mt-auto pt-3 inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                    >
                        {{ props.outstandingVoucher ? 'View Voucher' : 'View Fee Vouchers' }}
                        <Icon icon="arrow-right" :size="14" />
                    </Link>
                </div>

                <!-- Latest exam result -->
                <div class="bg-card rounded-lg border border-border p-4 flex flex-col">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm text-muted-foreground">Latest exam result</p>
                            <p class="text-2xl font-bold text-foreground mt-1">
                                {{ props.latestResult?.grade ?? (props.latestResult?.percentage != null ? `${props.latestResult.percentage}%` : '—') }}
                            </p>
                        </div>
                        <div class="p-3 rounded-lg bg-primary/10">
                            <Icon icon="clipboard-list" class="text-primary" :size="20" />
                        </div>
                    </div>

                    <p v-if="props.latestResult" class="mt-2 text-xs text-muted-foreground truncate" :title="props.latestResult.exam ?? ''">
                        {{ props.latestResult.exam }}
                        <span v-if="props.latestResult.result_status" class="ml-1 px-1.5 py-0.5 rounded-full text-[11px] font-medium" :class="resultBadgeClass">
                            {{ props.latestResult.result_status }}
                        </span>
                    </p>
                    <p v-else class="mt-2 text-xs text-muted-foreground">No published result yet</p>

                    <a
                        v-if="props.latestResult"
                        :href="route('portal.exams.show', props.latestResult.id)"
                        target="_blank"
                        rel="noopener"
                        class="mt-auto pt-3 inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                    >
                        View Full Result
                        <Icon icon="arrow-right" :size="14" />
                    </a>
                    <Link v-else :href="route('portal.exams.index')" class="mt-auto pt-3 inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">
                        View Exam Results
                        <Icon icon="arrow-right" :size="14" />
                    </Link>
                </div>

                <!-- Today's attendance -->
                <div class="bg-card rounded-lg border border-border p-4 flex flex-col">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm text-muted-foreground">Today's attendance</p>
                            <p class="text-2xl font-bold text-foreground mt-1">
                                <span v-if="props.todayAttendance" class="px-2 py-0.5 rounded-full text-base" :class="attendanceBadgeClass">
                                    {{ props.todayAttendance.status }}
                                </span>
                                <span v-else>—</span>
                            </p>
                        </div>
                        <div class="p-3 rounded-lg bg-accent">
                            <Icon icon="calendar-check" class="text-foreground" :size="20" />
                        </div>
                    </div>

                    <p v-if="!props.todayAttendance" class="mt-2 text-xs text-muted-foreground">Not marked yet</p>

                    <Link :href="route('portal.attendance.index')" class="mt-auto pt-3 inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">
                        View Attendance History
                        <Icon icon="arrow-right" :size="14" />
                    </Link>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
