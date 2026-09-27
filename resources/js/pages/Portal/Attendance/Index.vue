<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import TablePagination from '@/components/tables/TablePagination.vue';
import PortalLayout from '@/layouts/PortalLayout.vue';
import Icon from '@/components/Icon.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/utils/format';
import { ref, computed, watch, onMounted } from 'vue';

interface ChildOption {
    id: number;
    name: string | null;
    class?: string | null;
    section?: string | null;
    avatar?: string | null;
}

interface AttendanceRecord {
    id: number;
    check_in: string | null;
    check_out: string | null;
    remarks: string | null;
    attendance?: { attendance_date: string } | null;
    attendanceStatus?: { name: string; code: string } | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
}

interface Props {
    student: ChildOption;
    students: ChildOption[];
    records: Paginated<AttendanceRecord>;
}

const props = defineProps<Props>();

const statusConfig = {
    P: { label: 'Present', variant: 'success' as const, icon: 'check-circle', color: 'text-success' },
    A: { label: 'Absent', variant: 'destructive' as const, icon: 'x-circle', color: 'text-destructive' },
    L: { label: 'Late', variant: 'default' as const, icon: 'clock', color: 'text-primary' },
    LT: { label: 'Left Early', variant: 'warning' as const, icon: 'log-out', color: 'text-warning' },
    HD: { label: 'Holiday', variant: 'secondary' as const, icon: 'calendar-x', color: 'text-muted-foreground' },
} as const;

const getStatusConfig = (code: string | undefined) => {
    return statusConfig[code as keyof typeof statusConfig] ?? { label: code ?? '—', variant: 'secondary' as const, icon: 'help-circle', color: 'text-muted-foreground' };
};

// Calendar state
const currentMonth = ref(new Date());
const calendarDays = ref<(Date | null)[]>([]);
const attendanceMap = ref<Map<string, AttendanceRecord>>(new Map());

const summaryStats = computed(() => {
    const stats = { present: 0, absent: 0, late: 0, leave: 0, holiday: 0 };
    props.records.data.forEach(record => {
        const code = record.attendanceStatus?.code;
        if (code === 'P') stats.present++;
        else if (code === 'A') stats.absent++;
        else if (code === 'L') stats.late++;
        else if (code === 'LT') stats.leave++;
        else if (code === 'HD') stats.holiday++;
    });
    return stats;
});

const monthLabel = computed(() => {
    return currentMonth.value.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
});

const prevMonth = () => {
    currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() - 1, 1);
};

const nextMonth = () => {
    currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + 1, 1);
};

const goToToday = () => {
    currentMonth.value = new Date();
};

const buildCalendar = () => {
    const year = currentMonth.value.getFullYear();
    const month = currentMonth.value.getMonth();
    const firstDay = new Date(year, month, 1).getDay(); // 0 = Sunday
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const days: (Date | null)[] = [];

    // Add leading empty days
    for (let i = 0; i < firstDay; i++) {
        days.push(null);
    }

    // Add days of month
    for (let day = 1; day <= daysInMonth; day++) {
        days.push(new Date(year, month, day));
    }

    calendarDays.value = days;
};

const getDayStatus = (date: Date | null) => {
    if (!date) return null;
    const key = date.toISOString().split('T')[0];
    return attendanceMap.value.get(key)?.attendanceStatus?.code ?? null;
};

const isToday = (date: Date | null) => {
    if (!date) return false;
    const today = new Date();
    return date.getDate() === today.getDate() &&
           date.getMonth() === today.getMonth() &&
           date.getFullYear() === today.getFullYear();
};

const formatDateKey = (date: Date) => {
    return date.toISOString().split('T')[0];
};

const breadcrumbs = computed(() => [
    { title: 'Attendance', href: route('portal.attendance.index') },
]);

const viewMode = ref<'calendar' | 'list'>('calendar');
const listVisible = ref(false);
const calendarVisible = ref(false);

onMounted(() => {
    // Build attendance map
    props.records.data.forEach(record => {
        if (record.attendance?.attendance_date) {
            attendanceMap.value.set(record.attendance.attendance_date, record);
        }
    });
    buildCalendar();

    requestAnimationFrame(() => {
        calendarVisible.value = true;
        setTimeout(() => listVisible.value = true, 60);
    });
});

watch(currentMonth, buildCalendar);

watch(() => props.records.data, () => {
    attendanceMap.value.clear();
    props.records.data.forEach(record => {
        if (record.attendance?.attendance_date) {
            attendanceMap.value.set(record.attendance.attendance_date, record);
        }
    });
});
</script>

<template>
<PortalLayout :breadcrumbs="breadcrumbs" :current-child="props.student" :children="props.students">
    <template #default>
        <Head title="Attendance History" />
        <div class="space-y-6 animate-fade-slide-up">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-foreground">Attendance History</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Day-by-day attendance for {{ props.student.name }}.
                    </p>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 animate-fade-slide-up stagger-1">
                <Card class="card-interactive">
                    <CardContent class="py-4 text-center">
                        <p class="text-2xl font-bold text-success">{{ summaryStats.present }}</p>
                        <p class="text-xs text-muted-foreground">Present</p>
                    </CardContent>
                </Card>
                <Card class="card-interactive">
                    <CardContent class="py-4 text-center">
                        <p class="text-2xl font-bold text-destructive">{{ summaryStats.absent }}</p>
                        <p class="text-xs text-muted-foreground">Absent</p>
                    </CardContent>
                </Card>
                <Card class="card-interactive">
                    <CardContent class="py-4 text-center">
                        <p class="text-2xl font-bold text-primary">{{ summaryStats.late }}</p>
                        <p class="text-xs text-muted-foreground">Late</p>
                    </CardContent>
                </Card>
                <Card class="card-interactive">
                    <CardContent class="py-4 text-center">
                        <p class="text-2xl font-bold text-warning">{{ summaryStats.leave }}</p>
                        <p class="text-xs text-muted-foreground">Left Early</p>
                    </CardContent>
                </Card>
                <Card class="card-interactive">
                    <CardContent class="py-4 text-center">
                        <p class="text-2xl font-bold text-muted-foreground">{{ summaryStats.holiday }}</p>
                        <p class="text-xs text-muted-foreground">Holidays</p>
                    </CardContent>
                </Card>
            </div>

            <!-- View Toggle & Month Navigation -->
            <Card v-show="calendarVisible" class="animate-fade-slide-up stagger-2 overflow-hidden">
                <CardHeader class="pb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <CardTitle class="text-lg">Calendar View</CardTitle>
                    <div class="flex items-center gap-2 flex-wrap">
                        <Button variant="outline" size="sm" @click="prevMonth" class="no-print">
                            <Icon icon="chevron-left" :size="16" />
                        </Button>
                        <span class="font-medium text-foreground min-w-[160px] text-center">{{ monthLabel }}</span>
                        <Button variant="outline" size="sm" @click="nextMonth" class="no-print">
                            <Icon icon="chevron-right" :size="16" />
                        </Button>
                        <Button variant="ghost" size="sm" @click="goToToday" class="no-print hidden sm:inline-flex">
                            Today
                        </Button>
                        <div class="flex gap-2 border-l border-border pl-2 ml-2 hidden sm:flex">
                            <Button
                                variant="outline"
                                size="sm"
                                :class="viewMode === 'calendar' ? 'bg-primary text-primary-foreground' : ''"
                                @click="viewMode = 'calendar'"
                            >
                                Calendar
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                :class="viewMode === 'list' ? 'bg-primary text-primary-foreground' : ''"
                                @click="viewMode = 'list'"
                            >
                                List
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="pt-0">
                    <!-- Calendar Grid -->
                    <div v-show="viewMode === 'calendar'" class="animate-fade-slide-up">
                        <div class="grid grid-cols-7 gap-0.5 p-2 text-center text-xs font-medium text-muted-foreground">
                            <div>Sun</div>
                            <div>Mon</div>
                            <div>Tue</div>
                            <div>Wed</div>
                            <div>Thu</div>
                            <div>Fri</div>
                            <div>Sat</div>
                        </div>
                        <div class="grid grid-cols-7 gap-0.5 px-2 pb-2">
                            <template v-for="(day, index) in calendarDays" :key="index">
                                <div
                                    v-if="day"
                                    :class="[
                                        'aspect-square rounded-lg flex flex-col items-center justify-center transition-all duration-100 calendar-day',
                                        isToday(day) ? 'ring-2 ring-primary relative' : '',
                                        getDayStatus(day) === 'P' ? 'bg-success/10' : '',
                                        getDayStatus(day) === 'A' ? 'bg-destructive/10' : '',
                                        getDayStatus(day) === 'L' ? 'bg-primary/10' : '',
                                        getDayStatus(day) === 'LT' ? 'bg-warning/10' : '',
                                        getDayStatus(day) === 'HD' ? 'bg-muted/50' : '',
                                        !getDayStatus(day) && !isToday(day) ? 'bg-muted/30' : '',
                                    ]"
                                >
                                    <span :class="[
                                        'font-medium',
                                        isToday(day) ? 'text-primary' : 'text-foreground',
                                        getDayStatus(day) === 'A' ? 'text-destructive' : '',
                                        getDayStatus(day) === 'P' ? 'text-success' : '',
                                    ]">
                                        {{ day.getDate() }}
                                    </span>
                                    <span v-if="getDayStatus(day)" class="text-[10px] font-medium" :class="getStatusConfig(getDayStatus(day)).color">
                                        {{ getStatusConfig(getDayStatus(day)).label.charAt(0) }}
                                    </span>
                                    <span v-else-if="isToday(day)" class="text-[10px] font-medium text-primary">Today</span>
                                </div>
                                <div v-else class="aspect-square" />
                            </template>
                        </div>
                        <!-- Legend -->
                        <div class="flex flex-wrap gap-4 mt-4 pt-4 border-t text-xs text-muted-foreground">
                            <div class="flex items-center gap-1.5" v-for="[code, config] of Object.entries(statusConfig)" :key="code">
                                <span class="w-3 h-3 rounded" :class="['bg-' + config.variant + '/20']" />
                                <span>{{ config.label }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded ring-2 ring-primary" />
                                <span>Today</span>
                            </div>
                        </div>
                    </div>

                    <!-- List View (Desktop) -->
                    <div v-show="viewMode === 'list'" class="animate-fade-slide-up overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Check In</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Check Out</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr
                                    v-for="record in props.records.data"
                                    :key="record.id"
                                    class="transition-colors hover:bg-accent/50"
                                >
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-foreground">
                                        {{ record.attendance?.attendance_date ? formatDate(record.attendance.attendance_date) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <Badge :variant="getStatusConfig(record.attendanceStatus?.code).variant" class="gap-1">
                                            <Icon :icon="getStatusConfig(record.attendanceStatus?.code).icon" :size="11" />
                                            {{ getStatusConfig(record.attendanceStatus?.code).label }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ record.check_in ?? '—' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ record.check_out ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground max-w-xs truncate" :title="record.remarks ?? ''">{{ record.remarks ?? '—' }}</td>
                                </tr>
                                <tr v-if="props.records.data.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-muted-foreground">No attendance recorded yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Mobile List View -->
            <div v-show="listVisible" class="block lg:hidden space-y-3 animate-fade-slide-up stagger-3">
                <Card
                    v-for="record in props.records.data"
                    :key="record.id"
                    class="card-interactive overflow-hidden"
                >
                    <CardContent class="p-4 space-y-3">
                        <div class="flex flex-wrap gap-2 justify-between items-start">
                            <div class="font-medium text-foreground">
                                {{ record.attendance?.attendance_date ? formatDate(record.attendance.attendance_date) : '—' }}
                            </div>
                            <Badge :variant="getStatusConfig(record.attendanceStatus?.code).variant" class="gap-1 shrink-0">
                                <Icon :icon="getStatusConfig(record.attendanceStatus?.code).icon" :size="12" />
                                {{ getStatusConfig(record.attendanceStatus?.code).label }}
                            </Badge>
                        </div>

                        <div v-if="record.check_in || record.check_out || record.remarks" class="text-sm text-muted-foreground space-y-1 pt-2 border-t">
                            <div v-if="record.check_in || record.check_out" class="flex items-center justify-between">
                                <span>Check in / out</span>
                                <span class="text-foreground">{{ record.check_in ?? '—' }} / {{ record.check_out ?? '—' }}</span>
                            </div>
                            <div v-if="record.remarks" class="flex items-start gap-2">
                                <Icon icon="message-square" class="h-4 w-4 mt-0.5" />
                                <span>{{ record.remarks }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-if="props.records.data.length === 0" v-show="listVisible" class="animate-fade-slide-up stagger-2">
                <Card class="bg-muted/50 border-dashed">
                    <CardContent class="py-16 text-center">
                        <Icon icon="calendar-check" class="h-16 w-16 mx-auto text-muted-foreground/50 mb-4" />
                        <h3 class="text-lg font-semibold text-foreground mb-1">No attendance recorded yet</h3>
                        <p class="text-sm text-muted-foreground">
                            Attendance records will appear here once they are marked.
                        </p>
                    </CardContent>
                </Card>
            </div>

            <TablePagination
                v-if="props.records.data.length > 0"
                :pagination="props.records"
                :show-per-page-selector="false"
                use-links
                class="animate-fade-slide-up stagger-4"
            />
        </div>
    </template>
</PortalLayout>
</template>