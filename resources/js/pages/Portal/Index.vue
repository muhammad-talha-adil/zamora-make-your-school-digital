<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

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
</script>

<template>
    <AppLayout>
        <Head title="Portal" />

        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold">Welcome, {{ props.student.name ?? 'Student' }}</h1>

                <select
                    v-if="props.students.length > 1"
                    :value="props.student.id"
                    class="rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-800"
                    @change="
                        (event) => router.visit(route('portal.index', { student_id: (event.target as HTMLSelectElement).value }))
                    "
                >
                    <option v-for="child in props.students" :key="child.id" :value="child.id">
                        {{ child.name }}
                    </option>
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded border border-gray-200 p-4 dark:border-gray-700">
                    <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Fee balance</h2>
                    <p v-if="props.outstandingVoucher" class="mt-2 text-lg font-semibold">
                        Rs. {{ props.outstandingVoucher.balance_amount.toLocaleString() }}
                        <span class="block text-sm font-normal text-gray-500">Due {{ props.outstandingVoucher.due_date }}</span>
                    </p>
                    <p v-else class="mt-2 text-sm text-gray-500">No outstanding balance</p>
                    <Link :href="route('portal.fees.index')" class="mt-3 inline-block text-sm text-blue-600 hover:underline">
                        View fee vouchers
                    </Link>
                </div>

                <div class="rounded border border-gray-200 p-4 dark:border-gray-700">
                    <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Latest exam result</h2>
                    <p v-if="props.latestResult" class="mt-2 text-lg font-semibold">
                        {{ props.latestResult.exam }}
                        <span class="block text-sm font-normal text-gray-500">
                            {{ props.latestResult.grade ?? props.latestResult.percentage }} ·
                            {{ props.latestResult.result_status }}
                        </span>
                    </p>
                    <p v-else class="mt-2 text-sm text-gray-500">No published result yet</p>
                    <Link :href="route('portal.exams.index')" class="mt-3 inline-block text-sm text-blue-600 hover:underline">
                        View exam results
                    </Link>
                </div>

                <div class="rounded border border-gray-200 p-4 dark:border-gray-700">
                    <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Today's attendance</h2>
                    <p v-if="props.todayAttendance" class="mt-2 text-lg font-semibold">
                        {{ props.todayAttendance.status }}
                    </p>
                    <p v-else class="mt-2 text-sm text-gray-500">Not marked yet</p>
                    <Link :href="route('portal.attendance.index')" class="mt-3 inline-block text-sm text-blue-600 hover:underline">
                        View attendance history
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
