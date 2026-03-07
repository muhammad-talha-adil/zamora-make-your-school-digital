<template>
    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
        <!-- Serial Number -->
        <td class="px-2 md:px-4 py-3 whitespace-nowrap">
            <div class="text-sm text-gray-600 dark:text-gray-300">{{ index }}</div>
        </td>

        <!-- Student Info -->
        <td class="px-2 md:px-4 py-3 whitespace-nowrap">
            <div class="flex items-center">
                <div class="h-8 w-8 md:h-10 md:w-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center shrink-0 relative">
                    <span class="text-blue-600 dark:text-blue-400 text-xs md:text-sm font-medium">{{ student.name?.charAt(0) || 'S' }}</span>
                    <!-- Existing attendance indicator -->
                    <div v-if="student.has_existing_attendance" class="absolute -top-0.5 -right-0.5 h-2.5 w-2.5 md:h-3 md:w-3 bg-green-500 rounded-full border-2 border-white dark:border-gray-800" title="Already marked"></div>
                </div>
                <div class="ml-2 md:ml-3">
                    <div class="text-xs md:text-sm font-medium text-gray-900 dark:text-white flex items-center gap-1 md:gap-2">
                        {{ student.name }}
                        <span v-if="student.has_existing_attendance" class="px-1 py-0.5 text-xs font-medium rounded bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                            {{ student.existing_attendance?.attendance_status_code }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 hidden sm:block">{{ student.registration_no }}</div>
                    <div v-if="student.current_enrollment" class="text-xs text-gray-400 mt-0.5 hidden md:block">
                        {{ student.current_enrollment.class?.name || '' }} - {{ student.current_enrollment.section?.name || '' }}
                    </div>
                </div>
            </div>
        </td>

        <!-- Admission No -->
        <td class="px-2 md:px-4 py-3 whitespace-nowrap hidden md:table-cell">
            <div class="text-sm text-gray-600 dark:text-gray-300">{{ student.admission_no }}</div>
        </td>

        <!-- Status Selector -->
        <td class="px-2 md:px-4 py-3 whitespace-nowrap">
            <select
                :value="modelValue.attendance_status_id"
                @change="onStatusChange"
                :disabled="disabled"
                class="w-full min-w-[100px] rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm"
                :class="{ 'opacity-50 cursor-not-allowed': disabled }"
            >
                <option value="">Select</option>
                <option v-for="status in props.statuses" :key="status.id" :value="status.id">
                    {{ status.name }} ({{ status.code }})
                </option>
            </select>
        </td>

        <!-- Check In Time -->
        <td class="px-2 md:px-4 py-3 whitespace-nowrap hidden sm:table-cell">
            <input
                :value="modelValue.check_in"
                @input="onCheckInChange"
                type="time"
                :disabled="disabled"
                class="w-full min-w-[80px] rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm"
                :class="{ 'opacity-50 cursor-not-allowed': disabled }"
            />
        </td>

        <!-- Check Out Time -->
        <td class="px-2 md:px-4 py-3 whitespace-nowrap hidden sm:table-cell">
            <input
                :value="modelValue.check_out"
                @input="onCheckOutChange"
                type="time"
                :disabled="disabled"
                class="w-full min-w-[80px] rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm"
                :class="{ 'opacity-50 cursor-not-allowed': disabled }"
            />
        </td>

        <!-- Leave Type (shown when status is Leave) -->
        <td class="px-2 md:px-4 py-3 whitespace-nowrap hidden lg:table-cell">
            <div v-if="isLeaveStatus" class="text-sm">
                <select
                    :value="modelValue.leave_type_id"
                    @change="onLeaveTypeChange"
                    :disabled="disabled"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm"
                    :class="{ 'opacity-50 cursor-not-allowed': disabled }"
                >
                    <option value="">Select</option>
                    <option v-for="leaveType in props.leaveTypes" :key="leaveType.id" :value="leaveType.id">
                        {{ leaveType.name }}
                    </option>
                </select>
            </div>
            <div v-else-if="isOnLeave" class="text-sm">
                <span class="px-2 py-1 text-xs font-medium rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                    {{ leaveReason }}
                </span>
            </div>
            <div v-else class="text-sm text-gray-400">-</div>
        </td>

        <!-- Remarks -->
        <td class="px-2 md:px-4 py-3 whitespace-nowrap hidden lg:table-cell">
            <input
                :value="modelValue.remarks"
                @input="onRemarksChange"
                type="text"
                :disabled="disabled"
                placeholder="Add..."
                class="w-full min-w-[80px] rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 md:px-3 py-1.5 md:py-2 text-xs md:text-sm"
                :class="{ 'opacity-50 cursor-not-allowed': disabled }"
            />
        </td>
    </tr>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Student, AttendanceStatus, StudentAttendanceFormData, StudentLeave, LeaveType } from '@/types/attendance';

interface Props {
    student: Student;
    index: number;
    statuses: AttendanceStatus[];
    leaveTypes: LeaveType[];
    modelValue: StudentAttendanceFormData;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), { disabled: false });

const emit = defineEmits<{ (e: 'update:modelValue', value: StudentAttendanceFormData): void }>();

// Check if current status is Leave (code 'L')
const leaveStatus = computed(() => props.statuses.find((s) => s.code === 'L'));
const isLeaveStatus = computed(() => {
    return leaveStatus.value && props.modelValue.attendance_status_id === leaveStatus.value.id;
});

const isOnLeave = computed(() => !!props.student.student_leaves?.some((leave: StudentLeave) => leave.status === 'approved'));

const leaveReason = computed(() => {
    const approvedLeave = props.student.student_leaves?.find((leave: StudentLeave) => leave.status === 'approved');
    return approvedLeave?.reason || 'Approved Leave';
});

const onStatusChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    emit('update:modelValue', { ...props.modelValue, attendance_status_id: Number(target.value) || 0 });
};

const onLeaveTypeChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    emit('update:modelValue', { ...props.modelValue, leave_type_id: target.value ? Number(target.value) : undefined });
};

const onCheckInChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', { ...props.modelValue, check_in: target.value });
};

const onCheckOutChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', { ...props.modelValue, check_out: target.value });
};

const onRemarksChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', { ...props.modelValue, remarks: target.value });
};
</script>
