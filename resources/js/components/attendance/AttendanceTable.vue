<template>
    <div>
        <!-- Mobile Card View -->
        <div class="block lg:hidden space-y-3">
            <div
                v-for="(attendance, index) in props.attendances.data"
                :key="attendance.id"
                class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3"
            >
                <div class="flex justify-between items-start">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">
                            {{ formatDate(attendance.attendance_date) }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ attendance.campus?.name }}
                        </div>
                    </div>
                    <span
                        :class="[
                            'px-2 py-1 text-xs font-medium rounded-full shrink-0',
                            attendance.is_locked
                                ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                        ]"
                    >
                        {{ attendance.is_locked ? 'Locked' : 'Unlocked' }}
                    </span>
                </div>
                
                <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1 pt-2 border-t border-gray-100 dark:border-gray-700">
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
                <div class="flex gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex-1 text-center">
                        <div class="text-lg font-bold text-green-600">{{ getPresentCount(attendance) }}</div>
                        <div class="text-xs text-gray-500">Present</div>
                    </div>
                    <div class="flex-1 text-center">
                        <div class="text-lg font-bold text-red-600">{{ getAbsentCount(attendance) }}</div>
                        <div class="text-xs text-gray-500">Absent</div>
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
            
            <div v-if="props.attendances.data.length === 0" class="text-center py-8 text-gray-500">
                No attendance records found.
            </div>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden lg:block overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300 w-16">#</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Date</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Campus</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Class / Section</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Stats</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Status</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        <tr v-for="(attendance, index) in props.attendances.data" :key="attendance.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ props.attendances.from + index }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(attendance.attendance_date) }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">{{ attendance.campus?.name }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">{{ attendance.class?.name }}</div>
                                <div class="text-xs text-gray-500">{{ attendance.section?.name }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex gap-2">
                                    <span class="px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">P: {{ getPresentCount(attendance) }}</span>
                                    <span class="px-2 py-1 text-xs font-medium rounded bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">A: {{ getAbsentCount(attendance) }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span :class="['px-2 py-1 text-xs font-medium rounded-full', attendance.is_locked ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400']">
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
            <div class="text-xs md:text-sm text-gray-600">
                Showing {{ props.attendances.from }} to {{ props.attendances.to }} of {{ props.attendances.total }} entries
            </div>
            <div class="flex flex-wrap gap-1">
                <Link v-for="link in props.attendances.links" :key="link.label" :href="link.url || '#'" :class="['px-3 py-2 text-sm rounded-md transition-colors min-h-10 flex items-center justify-center', link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600']" preserve-state v-html="link.label" />
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
