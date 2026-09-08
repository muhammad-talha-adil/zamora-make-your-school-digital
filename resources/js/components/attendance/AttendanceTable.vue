<template>
    <div>
        <!-- Mobile Card View -->
        <div class="block lg:hidden space-y-3">
            <div
                v-for="attendance in props.attendances.data"
                :key="attendance.id"
                class="bg-card rounded-lg border border-border p-4 space-y-3"
            >
                <div class="flex flex-wrap gap-2 justify-between items-start">
                    <div>
                        <div class="font-medium text-foreground">
                            {{ formatDate(attendance.attendance_date) }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ attendance.campus?.name }}
                        </div>
                    </div>
                    <span
                        :class="[
                            'px-2 py-1 text-xs font-medium rounded-full shrink-0',
                            attendance.is_locked
                                ? 'bg-destructive/10 text-destructive'
                                : 'bg-success/10 text-success'
                        ]"
                    >
                        {{ attendance.is_locked ? 'Locked' : 'Unlocked' }}
                    </span>
                </div>
                
                <div class="text-sm text-muted-foreground space-y-1 pt-2 border-t border-border">
                    <div class="flex items-center gap-2">
                        <Icon icon="book" class="h-4 w-4" />
                        <span>{{ attendance.class?.name }} - {{ attendance.section?.name }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Icon icon="calendar" class="h-4 w-4" />
                        <span>{{ attendance.session?.name }}</span>
                    </div>
                </div>

                <!-- Stats -->
                <div class="flex gap-2 pt-2 border-t border-border">
                    <div class="flex-1 text-center">
                        <div class="text-lg font-bold text-success">{{ getPresentCount(attendance) }}</div>
                        <div class="text-xs text-muted-foreground">Present</div>
                    </div>
                    <div class="flex-1 text-center">
                        <div class="text-lg font-bold text-destructive">{{ getAbsentCount(attendance) }}</div>
                        <div class="text-xs text-muted-foreground">Absent</div>
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <Button variant="outline" size="sm" @click="viewAttendance(attendance)" class="flex-1">
                        <Icon icon="eye" class="mr-1 h-3 w-3" />View
                    </Button>
                    <Button 
                        v-if="!attendance.is_locked" 
                        variant="outline" 
                        size="sm" 
                        @click="editAttendance(attendance)"
                        class="flex-1"
                    >
                        <Icon icon="edit" class="mr-1 h-3 w-3" />Edit
                    </Button>
                </div>
            </div>
            
            <div v-if="props.attendances.data.length === 0" class="text-center py-8 text-muted-foreground">
                No attendance records found.
            </div>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden lg:block overflow-hidden rounded-lg border border-border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase w-16">#</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Date</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Campus</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Class / Section</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Stats</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Status</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        <tr v-for="(attendance, index) in props.attendances.data" :key="attendance.id" class="transition-colors hover:bg-accent">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-foreground">
                                {{ props.attendances.from + index }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-foreground">{{ formatDate(attendance.attendance_date) }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-muted-foreground">{{ attendance.campus?.name }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-muted-foreground">{{ attendance.class?.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ attendance.section?.name }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex gap-2">
                                    <span class="px-2 py-1 text-xs font-medium rounded bg-success/10 text-success">P: {{ getPresentCount(attendance) }}</span>
                                    <span class="px-2 py-1 text-xs font-medium rounded bg-destructive/10 text-destructive">A: {{ getAbsentCount(attendance) }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span :class="['px-2 py-1 text-xs font-medium rounded-full', attendance.is_locked ? 'bg-destructive/10 text-destructive' : 'bg-success/10 text-success']">
                                    {{ attendance.is_locked ? 'Locked' : 'Unlocked' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                <div class="flex flex-wrap gap-2 justify-end">
                                    <Button variant="outline" size="sm" @click="viewAttendance(attendance)" class="min-h-8">
                                        <Icon icon="eye" class="mr-1 h-3 w-3" />View
                                    </Button>
                                    <Button v-if="!attendance.is_locked" variant="outline" size="sm" @click="editAttendance(attendance)" class="min-h-8">
                                        <Icon icon="edit" class="mr-1 h-3 w-3" />Edit
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="props.attendances.links" class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mt-4">
            <div class="text-xs md:text-sm text-muted-foreground">
                Showing {{ props.attendances.from }} to {{ props.attendances.to }} of {{ props.attendances.total }} entries
            </div>
            <div class="flex flex-wrap gap-1">
                <Link v-for="link in props.attendances.links" :key="link.label" :href="link.url || '#'" :class="['px-3 py-2 text-sm rounded-md transition-colors min-h-10 flex items-center justify-center', link.active ? 'bg-primary text-primary-foreground' : 'bg-card text-muted-foreground hover:bg-accent border border-border']" preserve-state>
                    <span v-html="link.label"></span>
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import type { Attendance, PaginatedData } from '@/types/attendance';

interface Props {
    attendances: PaginatedData<Attendance>;
}

const props = defineProps<Props>();

const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
};

// Pre-computed statistics for each attendance record
const attendanceStats = computed(() => {
    const stats: Record<number, { present: number; absent: number }> = {};
    
    props.attendances.data.forEach((attendance) => {
        const students = attendance.attendance_students || [];
        stats[attendance.id] = {
            present: students.filter((s: { attendance_status?: { code?: string } }) => s.attendance_status?.code === 'P').length,
            absent: students.filter((s: { attendance_status?: { code?: string } }) => s.attendance_status?.code === 'A').length,
        };
    });
    
    return stats;
});

const getPresentCount = (attendance: Attendance): number => {
    return attendanceStats.value[attendance.id]?.present || 0;
};

const getAbsentCount = (attendance: Attendance): number => {
    return attendanceStats.value[attendance.id]?.absent || 0;
};

const viewAttendance = (attendance: Attendance) => {
    router.visit(route('attendance.show', { attendance: attendance.id }));
};

const editAttendance = (attendance: Attendance) => {
    router.visit(route('attendance.edit', { attendance: attendance.id }));
};
</script>
