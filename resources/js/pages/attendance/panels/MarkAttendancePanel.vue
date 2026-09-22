<template>
    <div class="space-y-4 md:space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
            <div>
                <h1 class="text-lg md:text-2xl font-bold text-foreground">
                    Mark Attendance
                </h1>
                <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                    Record attendance for students
                </p>
            </div>
        </div>

        <!-- Selection Form -->
        <div class="bg-card rounded-lg border border-border p-4 md:p-6">
            <!-- Mobile: Stacked filters, Desktop: Horizontal -->
            <div class="flex flex-col gap-4">
                <!-- Row 1: Campus, Session, Class -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <!-- Campus -->
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Campus</label>
                        <SearchableSelect v-model="selectedCampusId" :options="campusOptions" placeholder="Select Campus" :clearable="!isCampusLocked" :disabled="isCampusLocked" />
                    </div>

                    <!-- Session -->
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Session</label>
                        <SearchableSelect v-model="selectedSessionId" :options="sessionOptions" placeholder="Select Session" clearable />
                    </div>

                    <!-- Class -->
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Class</label>
                        <SearchableSelect v-model="selectedClassId" :options="classOptions" placeholder="Select Class" clearable @update:modelValue="onSectionReset" />
                    </div>
                </div>

                <!-- Row 2: Section, Date, Load Button -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <!-- Section -->
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Section</label>
                        <SearchableSelect
                            v-model="selectedSectionId"
                            :options="sectionOptions"
                            :disabled="!selectedClassId"
                            :placeholder="selectedClassId ? 'All Sections' : 'Select Class First'"
                        />
                    </div>

                    <!-- Date -->
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Date</label>
                        <input v-model="selectedDate" type="date" class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm" />
                    </div>

                    <!-- Load Students Button & Reset -->
                    <div class="flex items-end gap-2">
                        <Button
                            @click="loadStudents"
                            :disabled="!selectedClassId || props.isSunday || !!props.holiday"
                            class="flex-1"
                        >
                            <Icon icon="users" class="mr-1" />
                            Load Students
                        </Button>
                        <Button
                            variant="outline"
                            @click="resetFilters"
                            class="flex-1"
                            title="Reset Filters"
                        >
                            <Icon icon="rotate-ccw" class="mr-1" />
                            Reset
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
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Global Check In</label>
                        <input
                            v-model="globalCheckIn"
                            type="time"
                            class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm"
                        />
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Global Check Out</label>
                        <input
                            v-model="globalCheckOut"
                            type="time"
                            :disabled="checkoutDisabled"
                            :title="checkoutDisabled ? checkoutDisabledReason : ''"
                            class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm"
                            :class="{ 'opacity-50 cursor-not-allowed': checkoutDisabled }"
                        />
                    </div>
                    <Button variant="secondary" size="sm" @click="applyGlobalTimes" class="w-full sm:w-auto">Apply Times</Button>
                </div>
                <p v-if="props.shiftTiming" class="text-xs text-muted-foreground">
                    Shift "{{ props.shiftTiming.name }}": {{ props.shiftTiming.check_in }}
                    <template v-if="props.shiftTiming.check_out"> – {{ props.shiftTiming.check_out }}</template>
                    <template v-if="checkoutDisabled"> · check-out opens at {{ props.shiftTiming.check_out }}</template>
                </p>
            </div>
        </div>

        <!-- Sunday Warning Message -->
        <div v-if="props.isSunday" class="bg-warning/10 border border-warning/40 rounded-lg p-4 md:p-6">
            <div class="flex items-start">
                <Icon icon="calendar-x" class="h-6 w-6 md:h-8 md:w-8 text-warning mr-3 md:mr-4 mt-1" />
                <div>
                    <h3 class="text-base md:text-lg font-semibold text-warning">
                        Attendance Cannot Be Marked on Sunday
                    </h3>
                    <p class="mt-1 text-sm text-warning">
                        Sunday is a weekly holiday. Please select another date to mark attendance.
                    </p>
                </div>
            </div>
        </div>

        <!-- Holiday Warning Message -->
        <div v-else-if="props.holiday" class="bg-destructive/10 border border-destructive/40 rounded-lg p-4 md:p-6">
            <div class="flex items-start">
                <Icon icon="celebration" class="h-6 w-6 md:h-8 md:w-8 text-destructive mr-3 md:mr-4 mt-1" />
                <div class="flex-1">
                    <h3 class="text-base md:text-lg font-semibold text-destructive">
                        {{ props.holiday.is_national ? 'National Holiday' : 'Holiday' }}: {{ props.holiday.title }}
                    </h3>
                    <p class="mt-1 text-sm text-destructive">
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
        <div v-else-if="students.length > 0" class="bg-card rounded-lg border border-border overflow-hidden">
            <div class="overflow-x-auto -mx-4 md:mx-0">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted">
                        <tr>
                            <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-muted-foreground uppercase w-12 md:w-16">Sr#</th>
                            <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Student</th>
                            <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-muted-foreground uppercase hidden md:table-cell">Admission No</th>
                            <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Status</th>
                            <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-muted-foreground uppercase hidden sm:table-cell">Check In</th>
                            <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-muted-foreground uppercase hidden sm:table-cell">Check Out</th>
                            <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-muted-foreground uppercase hidden lg:table-cell">Leave Type</th>
                            <th class="px-2 md:px-4 py-2 md:py-3 text-left text-xs font-semibold text-muted-foreground uppercase hidden lg:table-cell">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        <AttendanceFormRow
                            v-for="(student, index) in students"
                            :key="student.id"
                            :student="student"
                            :index="index + 1"
                            :statuses="props.attendanceStatuses"
                            :leaveTypes="props.leaveTypes"
                            :checkoutDisabled="checkoutDisabled"
                            :checkoutDisabledReason="checkoutDisabledReason"
                            v-model="formData.attendances[index]"
                        />
                    </tbody>
                </table>
            </div>

            <!-- Submit Button -->
            <div class="p-4 border-t border-border">
                <Button @click="submitAttendance" :disabled="isSubmitting" class="w-full sm:w-auto">
                    <Icon v-if="isSubmitting" icon="loader" class="mr-1 animate-spin" />
                    {{ isSubmitting ? 'Submitting...' : 'Submit Attendance' }}
                </Button>
            </div>
        </div>

        <!-- No Students Message -->
        <div v-else-if="selectedClassId && selectedSectionId" class="bg-card rounded-lg border border-border p-6 md:p-8 text-center">
            <Icon icon="users" class="h-10 w-10 md:h-12 md:w-12 text-muted-foreground mx-auto mb-3 md:mb-4" />
            <p class="text-sm md:text-base text-muted-foreground">No students found for the selected class and section.</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { reactive, computed, watch, ref, onMounted } from 'vue';
import { route } from 'ziggy-js';
import AttendanceFormRow from '@/components/attendance/AttendanceFormRow.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
import type { AttendanceCreateProps, StudentAttendanceFormData } from '@/types/attendance';
import { useCascadingAcademicSelect } from '@/composables/useCascadingAcademicSelect';

const props = defineProps<AttendanceCreateProps>();

const isSubmitting = computed(() => (usePage().props as any).processing || false);

// Campus/session/class/section cascade + campus-lock + session default/persist (#47/#64/#82/#99/#100).
const {
    selectedCampusId,
    selectedClassId,
    selectedSessionId,
    isCampusLocked,
} = useCascadingAcademicSelect({
    campuses: props.campuses,
    classes: props.classes,
    sections: props.sections,
    sessions: props.sessions,
    initialCampusId: props.selectedCampusId || '',
    initialClassId: props.selectedClassId || '',
    initialSessionId: props.selectedSessionId || '',
    sessionStorageKey: 'attendance',
});

const selectedSectionId = ref<string | number>(props.selectedSectionId || 'all');
const selectedDate = ref<string>(props.selectedDate || '');

// Watch for prop changes and update local state
watch(() => props.selectedSectionId, (newVal) => {
    if (newVal !== null && newVal !== undefined && newVal !== '' && newVal !== 'all') {
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

// Global check-in/check-out times, defaulted from the class's shift timing
// when one is set (#103) rather than left blank.
const globalCheckIn = ref<string>(props.shiftTiming?.check_in || '');
const globalCheckOut = ref<string>('');

/**
 * Nobody may record a check-out before the shift's own off time has actually
 * arrived — only meaningful when marking today, since a past date's day is
 * already over and a future date cannot be marked at all (#105).
 */
const checkoutDisabled = computed(() => {
    const timing = props.shiftTiming;
    if (!timing || !timing.check_out) return false;

    const today = new Date().toISOString().split('T')[0];
    if (selectedDate.value !== today) return false;

    const now = new Date();
    const nowHm = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

    return nowHm < timing.check_out;
});

const checkoutDisabledReason = computed(() =>
    props.shiftTiming?.check_out
        ? `Check-out opens at ${props.shiftTiming.check_out}.`
        : ''
);

watch(() => props.shiftTiming, (timing) => {
    if (timing?.check_in && !globalCheckIn.value) {
        globalCheckIn.value = timing.check_in;
    }
}, { immediate: true });

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

const campusOptions = computed(() => props.campuses.map((campus) => ({ value: campus.id, label: campus.name })));
const sessionOptions = computed(() => props.sessions.map((session) => ({ value: session.id, label: session.name })));
const classOptions = computed(() => props.classes.map((cls) => ({ value: cls.id, label: cls.name })));
const sectionOptions = computed(() => {
    if (!selectedClassId.value) return [];
    return [
        { value: 'all', label: 'All Sections' },
        ...filteredSections.value.map((section) => ({ value: section.id, label: section.name })),
    ];
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
            // The class's shift check-in is the sensible default for a row
            // nobody has marked yet, rather than blank (#103).
            check_in: existing?.check_in || props.shiftTiming?.check_in || '',
            check_out: existing?.check_out || '',
            remarks: existing?.remarks || '',
        };
    });
}, { immediate: true });

// Load students on initial page load if URL params are present
onMounted(() => {
    if (props.students && props.students.length > 0) {
        // Students already loaded from backend, just ensure formData is populated
        formData.attendances = props.students.map((student) => {
            const existing = student.existing_attendance;
            return {
                student_id: student.id,
                id: existing?.id,
                attendance_status_id: existing?.attendance_status_id || 0,
                leave_type_id: existing?.leave_type_id,
                check_in: existing?.check_in || props.shiftTiming?.check_in || '',
                check_out: existing?.check_out || '',
                remarks: existing?.remarks || '',
            };
        });
    }
});

const onSectionReset = () => {
    selectedSectionId.value = 'all';
};

const resetFilters = () => {
    // Get current date in YYYY-MM-DD format
    const today = new Date().toISOString().split('T')[0];

    selectedCampusId.value = '';
    selectedSessionId.value = '';
    selectedClassId.value = '';
    selectedSectionId.value = 'all';
    selectedDate.value = today;

    // Navigate to attendance create page without query params
    router.visit(route('attendance.create'), {
        method: 'get',
        preserveState: true,
        replace: true,
    });
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const loadStudents = () => {
    if (!selectedClassId.value) return;

    // If "all" is selected, pass empty string to load all sections
    const sectionId = selectedSectionId.value === 'all' ? '' : selectedSectionId.value;

    // Use router.visit for proper page update with new data
    router.visit(route('attendance.create'), {
        method: 'get',
        data: {
            campus_id: selectedCampusId.value,
            session_id: selectedSessionId.value,
            class_id: selectedClassId.value,
            section_id: sectionId,
            date: selectedDate.value
        },
        preserveState: true,
        replace: true,
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
    if (globalCheckOut.value && !checkoutDisabled.value) {
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
