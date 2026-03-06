<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Mark Attendance" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">
                        Mark Attendance
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-gray-600 dark:text-gray-400">
                        Record attendance for students
                    </p>
                </div>
            </div>

            <!-- Selection Form -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 md:p-6">
                <!-- Mobile: Stacked filters, Desktop: Horizontal -->
                <div class="flex flex-col gap-4">
                    <!-- Row 1: Campus, Session, Class -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <!-- Campus -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Campus</label>
                            <select v-model="selectedCampusId" @change="onSelectionChange" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm">
                                <option value="">Select Campus</option>
                                <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">{{ campus.name }}</option>
                            </select>
                        </div>

                        <!-- Session -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Session</label>
                            <select v-model="selectedSessionId" @change="onSelectionChange" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm">
                                <option value="">Select Session</option>
                                <option v-for="session in props.sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                            </select>
                        </div>

                        <!-- Class -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Class</label>
                            <select v-model="selectedClassId" @change="onClassChange" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm">
                                <option value="">Select Class</option>
                                <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Row 2: Section, Date, Load Button -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <!-- Section -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Section</label>
                            <select 
                                v-model="selectedSectionId" 
                                :disabled="!selectedClassId"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                                :class="{ 'opacity-50 cursor-not-allowed': !selectedClassId }"
                            >
                                <option value="all">{{ selectedClassId ? 'All Sections' : 'Select Class First' }}</option>
                                <option v-for="section in filteredSections" :key="section.id" :value="section.id">{{ section.name }}</option>
                            </select>
                        </div>

                        <!-- Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                            <input v-model="selectedDate" type="date" @change="onDateChange" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm" />
                        </div>

                        <!-- Load Students Button -->
                        <div class="flex items-end">
                            <Button 
                                @click="loadStudents" 
                                :disabled="!selectedClassId || !selectedSectionId || props.isSunday || !!props.holiday"
                                class="w-full"
                            >
                                <Icon icon="users" class="mr-1" />
                                Load Students
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Bulk Actions and Global Check In/Out -->
                <div class="mt-6 flex flex-col gap-4">
                    <!-- Bulk Action Buttons -->
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" @click="markAllPresent">Mark All Present</Button>
                        <Button variant="outline" size="sm" @click="markAllAbsent">Mark All Absent</Button>
                        <Button variant="outline" size="sm" @click="markAllLeave">Mark All Leave</Button>
                    </div>

                    <!-- Global Check In/Out Times -->
                    <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Global Check In</label>
                            <input 
                                v-model="globalCheckIn" 
                                type="time" 
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                            />
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Global Check Out</label>
                            <input 
                                v-model="globalCheckOut" 
                                type="time" 
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                            />
                        </div>
                        <Button variant="secondary" size="sm" @click="applyGlobalTimes" class="w-full sm:w-auto">Apply Times</Button>
                    </div>
                </div>
            </div>

            <!-- Sunday Warning Message -->
            <div v-if="props.isSunday" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 md:p-6">
                <div class="flex items-start">
                    <Icon icon="calendar-x" class="h-6 w-6 md:h-8 md:w-8 text-amber-500 mr-3 md:mr-4 mt-1" />
                    <div>
                        <h3 class="text-base md:text-lg font-semibold text-amber-800 dark:text-amber-200">
                            Attendance Cannot Be Marked on Sunday
                        </h3>
                        <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                            Sunday is a weekly holiday. Please select another date to mark attendance.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Holiday Warning Message -->
            <div v-else-if="props.holiday" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 md:p-6">
                <div class="flex items-start">
                    <Icon icon="celebration" class="h-6 w-6 md:h-8 md:w-8 text-red-500 mr-3 md:mr-4 mt-1" />
                    <div class="flex-1">
                        <h3 class="text-base md:text-lg font-semibold text-red-800 dark:text-red-200">
                            {{ props.holiday.is_national ? 'National Holiday' : 'Holiday' }}: {{ props.holiday.title }}
                        </h3>
                        <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                            <template v-if="props.holiday.start_date === props.holiday.end_date">
                                This holiday is observed on {{ formatDate(props.holiday.start_date) }}.
                            </template>
                            <template v-else>
                                This holiday is observed from {{ formatDate(props.holiday.start_date) }} to {{ formatDate(props.holiday.end_date) }}.
                            </template>
                            <span v-if="!props.holiday.is_national && props.holiday.campus">
                                <br />Campus: {{ props.holiday.campus }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Students Table -->
            <div v-else-if="students.length > 0" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 w-12 md:w-16">Sr#</th>
                                <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Student</th>
                                <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 hidden md:table-cell">Admission No</th>
                                <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300">Status</th>
                                <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 hidden sm:table-cell">Check In</th>
                                <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 hidden sm:table-cell">Check Out</th>
                                <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 hidden lg:table-cell">Leave Type</th>
                                <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-gray-600 uppercase dark:text-gray-300 hidden lg:table-cell">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            <AttendanceFormRow
                                v-for="(student, index) in students"
                                :key="student.id"
                                :student="student"
                                :index="index + 1"
                                :statuses="props.attendanceStatuses"
                                :leaveTypes="props.leaveTypes"
                                v-model="formData.attendances[index]"
                            />
                        </tbody>
                    </table>
                </div>

                <!-- Submit Button -->
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <Button @click="submitAttendance" :disabled="isSubmitting" class="w-full sm:w-auto">
                        <Icon v-if="isSubmitting" icon="loader" class="mr-1 animate-spin" />
                        {{ isSubmitting ? 'Submitting...' : 'Submit Attendance' }}
                    </Button>
                </div>
            </div>

            <!-- No Students Message -->
            <div v-else-if="selectedClassId && selectedSectionId" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 md:p-8 text-center">
                <Icon icon="users" class="h-10 w-10 md:h-12 md:w-12 text-gray-400 mx-auto mb-3 md:mb-4" />
                <p class="text-sm md:text-base text-gray-600 dark:text-gray-400">No students found for the selected class and section.</p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { reactive, computed, watch, ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import AttendanceFormRow from '@/components/attendance/AttendanceFormRow.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import type { AttendanceCreateProps, StudentAttendanceFormData } from '@/types/attendance';

const props = defineProps<AttendanceCreateProps>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Attendance', href: '/attendance' },
    { title: 'Mark Attendance', href: '/attendance/create' },
];

const isSubmitting = computed(() => (usePage().props as any).processing || false);

const selectedCampusId = ref<number | ''>(props.selectedCampusId || '');
const selectedSessionId = ref<number | ''>(props.selectedSessionId || '');
const selectedClassId = ref<number | ''>(props.selectedClassId || '');
const selectedSectionId = ref<string | number>('all');
const selectedDate = ref<string>(props.selectedDate || '');

// Watch for prop changes and update local state
watch(() => props.selectedSectionId, (newVal) => {
    if (newVal !== null && newVal !== undefined && newVal !== '') {
        selectedSectionId.value = String(newVal);
    } else {
        selectedSectionId.value = 'all';
    }
}, { immediate: true });

watch(() => props.selectedClassId, (newVal) => {
    if (newVal === null || newVal === undefined || newVal === '') {
        selectedClassId.value = '';
    }
}, { immediate: true });

// Global check-in/check-out times
const globalCheckIn = ref<string>('');
const globalCheckOut = ref<string>('');

const formData = reactive({
    attendance_date: props.selectedDate || '',
    campus_id: props.selectedCampusId || 0,
    session_id: props.selectedSessionId || 0,
    class_id: props.selectedClassId || 0,
    section_id: props.selectedSectionId || 0,
    attendances: [] as StudentAttendanceFormData[],
});

const filteredSections = computed(() => {
    if (!selectedClassId.value) return props.sections;
    return props.sections.filter((s) => s.class_id === Number(selectedClassId.value));
});

const students = computed(() => props.students);

watch(students, (newStudents) => {
    formData.attendances = newStudents.map((student) => {
        const existing = student.existing_attendance;
        return {
            student_id: student.id,
            id: existing?.id, // Include existing attendance ID for updates
            attendance_status_id: existing?.attendance_status_id || 0,
            leave_type_id: existing?.leave_type_id,
            check_in: existing?.check_in || '',
            check_out: existing?.check_out || '',
            remarks: existing?.remarks || '',
        };
    });
}, { immediate: true });

const onSelectionChange = () => loadStudents();
const onClassChange = () => { selectedSectionId.value = 'all'; loadStudents(); };
const onDateChange = () => loadStudents();

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const loadStudents = () => {
    if (!selectedClassId.value || !selectedSectionId.value) return;
    // If "all" is selected, pass empty string to load all sections
    const sectionId = selectedSectionId.value === 'all' ? '' : selectedSectionId.value;
    router.visit(route('attendance.create'), {
        data: { 
            campus_id: selectedCampusId.value, 
            session_id: selectedSessionId.value, 
            class_id: selectedClassId.value, 
            section_id: sectionId, 
            date: selectedDate.value 
        },
        preserveState: true,
    });
};

const markAllPresent = () => {
    const presentStatus = props.attendanceStatuses.find((s) => s.code === 'P');
    if (presentStatus) formData.attendances.forEach((a) => { a.attendance_status_id = presentStatus.id; });
};

const markAllAbsent = () => {
    const absentStatus = props.attendanceStatuses.find((s) => s.code === 'A');
    if (absentStatus) formData.attendances.forEach((a) => { a.attendance_status_id = absentStatus.id; });
};

const markAllLeave = () => {
    const leaveStatus = props.attendanceStatuses.find((s) => s.code === 'L');
    if (leaveStatus) formData.attendances.forEach((a) => { a.attendance_status_id = leaveStatus.id; });
};

const applyGlobalTimes = () => {
    if (globalCheckIn.value) {
        formData.attendances.forEach((a) => { a.check_in = globalCheckIn.value; });
    }
    if (globalCheckOut.value) {
        formData.attendances.forEach((a) => { a.check_out = globalCheckOut.value; });
    }
};

const submitAttendance = () => {
    formData.campus_id = Number(selectedCampusId.value) || 0;
    formData.session_id = Number(selectedSessionId.value) || 0;
    formData.class_id = Number(selectedClassId.value) || 0;
    formData.section_id = selectedSectionId.value === 'all' ? 0 : (Number(selectedSectionId.value) || 0);
    router.post(route('attendance.store'), formData, {
        onSuccess: () => router.visit(route('attendance.index')),
    });
};
</script>
