<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import Icon from '@/components/Icon.vue';

interface Props {
    modelValue?: {
        from?: string | null;
        to?: string | null;
    };
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => ({ from: null, to: null }),
    placeholder: 'Select Date Range',
});

const emit = defineEmits<{
    'update:modelValue': [value: { from: string | null; to: string | null }];
}>();

// State
const isOpen = ref(false);
const startDate = ref<Date | null>(null);
const endDate = ref<Date | null>(null);
const hoverDate = ref<Date | null>(null);
const leftMonth = ref(new Date());
const selecting = ref<'start' | 'end' | null>(null);

// Quick date ranges matching daterangepicker.com
const quickRanges = [
    { label: 'Today', getValue: () => { const today = new Date(); return { from: today, to: today }; } },
    { label: 'Yesterday', getValue: () => { const yesterday = new Date(); yesterday.setDate(yesterday.getDate() - 1); return { from: yesterday, to: yesterday }; } },
    { label: 'Last 7 Days', getValue: () => { const today = new Date(); const start = new Date(); start.setDate(start.getDate() - 6); return { from: start, to: today }; } },
    { label: 'Last 30 Days', getValue: () => { const today = new Date(); const start = new Date(); start.setDate(start.getDate() - 29); return { from: start, to: today }; } },
    { label: 'This Month', getValue: () => { const today = new Date(); const start = new Date(today.getFullYear(), today.getMonth(), 1); return { from: start, to: today }; } },
    { label: 'Last Month', getValue: () => { const today = new Date(); const start = new Date(today.getFullYear(), today.getMonth() - 1, 1); const end = new Date(today.getFullYear(), today.getMonth(), 0); return { from: start, to: end }; } },
    { label: 'This Year', getValue: () => { const today = new Date(); const start = new Date(today.getFullYear(), 0, 1); return { from: start, to: today }; } },
];

const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

const years = computed(() => {
    const currentYear = new Date().getFullYear();
    const years: number[] = [];
    for (let i = currentYear - 10; i <= currentYear + 10; i++) {
        years.push(i);
    }
    return years;
});

// Initialize from modelValue
watch(
    () => props.modelValue,
    (newVal) => {
        if (newVal?.from) {
            startDate.value = new Date(newVal.from);
        } else {
            startDate.value = null;
        }
        if (newVal?.to) {
            endDate.value = new Date(newVal.to);
        } else {
            endDate.value = null;
        }
    },
    { immediate: true }
);

// Computed
const displayValue = computed(() => {
    if (startDate.value && endDate.value) {
        return `${formatDateDisplay(startDate.value)} - ${formatDateDisplay(endDate.value)}`;
    } else if (startDate.value) {
        return `${formatDateDisplay(startDate.value)} - ...`;
    }
    return props.placeholder;
});

const hasValue = computed(() => !!startDate.value || !!endDate.value);

// Date utility functions
function formatDateDisplay(date: Date): string {
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
}

function formatDateISO(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function getDaysInMonth(year: number, month: number): number {
    return new Date(year, month + 1, 0).getDate();
}

function getFirstDayOfMonth(year: number, month: number): number {
    return new Date(year, month, 1).getDay();
}

function isSameDay(date1: Date, date2: Date | null): boolean {
    if (!date2) return false;
    return date1.getFullYear() === date2.getFullYear() &&
           date1.getMonth() === date2.getMonth() &&
           date1.getDate() === date2.getDate();
}

function isDateInRange(date: Date): boolean {
    if (!startDate.value || !endDate.value) return false;
    const time = date.getTime();
    return time > startDate.value.getTime() && time < endDate.value.getTime();
}

function isDateInHoverRange(date: Date): boolean {
    if (!startDate.value || !hoverDate.value || !selecting.value) return false;
    const minDate = selecting.value === 'start' ? startDate.value : hoverDate.value;
    const maxDate = selecting.value === 'start' ? hoverDate.value : startDate.value;
    if (!minDate || !maxDate) return false;
    const time = date.getTime();
    return time > Math.min(minDate.getTime(), maxDate.getTime()) && 
           time < Math.max(minDate.getTime(), maxDate.getTime());
}

function generateCalendarDays(year: number, month: number) {
    const daysInMonth = getDaysInMonth(year, month);
    const firstDay = getFirstDayOfMonth(year, month);
    const days: (Date | null)[] = [];
    
    for (let i = 0; i < firstDay; i++) {
        days.push(null);
    }
    
    for (let day = 1; day <= daysInMonth; day++) {
        days.push(new Date(year, month, day));
    }
    
    return days;
}

const leftCalendarDays = computed(() => {
    const year = leftMonth.value.getFullYear();
    const month = leftMonth.value.getMonth();
    return generateCalendarDays(year, month);
});

const rightCalendarDays = computed(() => {
    const year = leftMonth.value.getFullYear();
    const month = leftMonth.value.getMonth();
    const nextMonth = month === 11 ? 0 : month + 1;
    const nextYear = month === 11 ? year + 1 : year;
    return generateCalendarDays(nextYear, nextMonth);
});

const leftMonthIndex = computed(() => leftMonth.value.getMonth());
const leftYear = computed(() => leftMonth.value.getFullYear());

const rightMonthIndex = computed(() => {
    const month = leftMonth.value.getMonth();
    return month === 11 ? 0 : month + 1;
});

const rightYear = computed(() => {
    const month = leftMonth.value.getMonth();
    return month === 11 ? leftMonth.value.getFullYear() + 1 : leftMonth.value.getFullYear();
});

// Methods
const prevMonth = () => {
    const year = leftMonth.value.getFullYear();
    const month = leftMonth.value.getMonth();
    leftMonth.value = new Date(year, month - 1, 1);
};

const nextMonth = () => {
    const year = leftMonth.value.getFullYear();
    const month = leftMonth.value.getMonth();
    leftMonth.value = new Date(year, month + 1, 1);
};

const setLeftMonth = (monthIndex: number, year: number) => {
    leftMonth.value = new Date(year, monthIndex, 1);
};

const selectDate = (date: Date | null) => {
    if (!date) return;
    
    if (!startDate.value || (startDate.value && endDate.value)) {
        startDate.value = date;
        endDate.value = null;
        selecting.value = 'start';
    } else if (date < startDate.value) {
        startDate.value = date;
        endDate.value = null;
        selecting.value = 'start';
    } else {
        endDate.value = date;
        selecting.value = null;
        emitChange();
    }
};

const handleHover = (date: Date | null) => {
    if (startDate.value && !endDate.value) {
        hoverDate.value = date;
    }
};

const selectQuickRange = (range: { getValue: () => { from: Date; to: Date } }) => {
    const { from, to } = range.getValue();
    startDate.value = from;
    endDate.value = to;
    emitChange();
    isOpen.value = false;
};

const emitChange = () => {
    emit('update:modelValue', {
        from: startDate.value ? formatDateISO(startDate.value) : null,
        to: endDate.value ? formatDateISO(endDate.value) : null,
    });
};

const clearRange = () => {
    startDate.value = null;
    endDate.value = null;
    hoverDate.value = null;
    selecting.value = null;
    emitChange();
};

const applyRange = () => {
    if (startDate.value && endDate.value) {
        emitChange();
        isOpen.value = false;
    }
};

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value && startDate.value) {
        leftMonth.value = new Date(startDate.value.getFullYear(), startDate.value.getMonth(), 1);
    } else if (isOpen.value) {
        leftMonth.value = new Date();
    }
};

// Close on outside click
const handleOutsideClick = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.drp-container')) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);
});

onUnmounted(() => {
    document.removeEventListener('click', handleOutsideClick);
});
</script>

<template>
    <div class="drp-container relative w-full sm:w-auto">
        <!-- Input Trigger -->
        <div 
            class="drp-trigger cursor-pointer"
            @click.stop="toggleDropdown"
        >
            <div class="flex items-center gap-2 border border-gray-300 rounded-md bg-white px-3 py-1.5 min-w-[240px]">
                <Icon icon="calendar" class="w-4 h-4 text-gray-500" />
                <input
                    type="text"
                    class="flex-1 border-none outline-none text-sm text-gray-700 bg-transparent cursor-pointer"
                    :value="displayValue"
                    :placeholder="placeholder"
                    readonly
                />
                <button 
                    v-if="hasValue" 
                    class="text-gray-400 hover:text-gray-600"
                    @click.stop="clearRange"
                >
                    <Icon icon="x" class="w-3 h-3" />
                </button>
            </div>
        </div>

        <!-- Dropdown -->
        <div 
            v-if="isOpen"
            class="absolute z-[999999] mt-1 bg-white border border-gray-300 rounded-lg shadow-lg"
            style="min-width: 636px;"
            @click.stop
        >
            <!-- Main Content - Full Width Background -->
            <div class="flex">
                <!-- Left sidebar with ranges -->
                <div class="w-[150px] p-2.5 border-r border-gray-200 bg-white">
                    <div class="text-xs font-bold text-gray-500 uppercase mb-2 pl-2">Select</div>
                    <div class="flex flex-col">
                        <button
                            v-for="range in quickRanges"
                            :key="range.label"
                            class="w-full text-left px-2 py-1.5 text-sm text-gray-700 rounded hover:bg-gray-100"
                            @click="selectQuickRange(range)"
                        >
                            {{ range.label }}
                        </button>
                    </div>
                </div>
                
                <!-- Calendars -->
                <div class="flex-1 p-2.5 bg-white">
                    <div class="flex gap-2">
                        <!-- Left Calendar -->
                        <div class="flex-1">
                            <!-- Calendar Header -->
                            <div class="flex items-center justify-between mb-2">
                                <button
                                    class="p-1 hover:bg-gray-100 rounded"
                                    @click="prevMonth"
                                >
                                    <Icon icon="chevron-left" class="w-4 h-4 text-gray-600" />
                                </button>
                                <div class="flex gap-1">
                                    <select 
                                        class="text-sm font-medium text-gray-700 bg-transparent border border-gray-300 rounded px-1 py-0.5 cursor-pointer"
                                        :value="leftMonthIndex"
                                        @change="setLeftMonth(Number(($event.target as HTMLSelectElement).value), leftYear)"
                                    >
                                        <option v-for="(month, idx) in months" :key="month" :value="idx">
                                            {{ month }}
                                        </option>
                                    </select>
                                    <select 
                                        class="text-sm font-medium text-gray-700 bg-transparent border border-gray-300 rounded px-1 py-0.5 cursor-pointer"
                                        :value="leftYear"
                                        @change="setLeftMonth(leftMonthIndex, Number(($event.target as HTMLSelectElement).value))"
                                    >
                                        <option v-for="year in years" :key="year" :value="year">
                                            {{ year }}
                                        </option>
                                    </select>
                                </div>
                                <div></div>
                            </div>
                            
                            <!-- Days of week -->
                            <div class="grid grid-cols-7 mb-1">
                                <span v-for="day in ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']" :key="day" class="text-center text-xs font-bold text-gray-500 py-1">
                                    {{ day }}
                                </span>
                            </div>
                            
                            <!-- Days -->
                            <div class="grid grid-cols-7">
                                <template v-for="(day, index) in leftCalendarDays" :key="'left-' + index">
                                    <button
                                        v-if="day"
                                        class="text-sm rounded transition-colors flex items-center justify-center font-normal w-7 h-7 mx-auto"
                                        :class="{
                                            'bg-blue-600 text-white hover:bg-blue-700': isSameDay(day, startDate) || isSameDay(day, endDate),
                                            'bg-blue-100 text-gray-900': isDateInRange(day) || isDateInHoverRange(day),
                                            'hover:bg-gray-100 text-gray-700': !isSameDay(day, startDate) && !isSameDay(day, endDate),
                                        }"
                                        @click="selectDate(day)"
                                        @mouseenter="handleHover(day)"
                                        @mouseleave="handleHover(null)"
                                    >
                                        {{ day.getDate() }}
                                    </button>
                                    <div v-else class="w-7 h-7"></div>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Right Calendar -->
                        <div class="flex-1">
                            <!-- Calendar Header -->
                            <div class="flex items-center justify-between mb-2">
                                <div></div>
                                <div class="flex gap-1">
                                    <select 
                                        class="text-sm font-medium text-gray-700 bg-transparent border border-gray-300 rounded px-1 py-0.5 cursor-pointer"
                                        :value="rightMonthIndex"
                                        @change="setLeftMonth(Number(($event.target as HTMLSelectElement).value), rightYear)"
                                    >
                                        <option v-for="(month, idx) in months" :key="month" :value="idx">
                                            {{ month }}
                                        </option>
                                    </select>
                                    <select 
                                        class="text-sm font-medium text-gray-700 bg-transparent border border-gray-300 rounded px-1 py-0.5 cursor-pointer"
                                        :value="rightYear"
                                        @change="setLeftMonth(rightMonthIndex === 11 ? 0 : rightMonthIndex, Number(($event.target as HTMLSelectElement).value))"
                                    >
                                        <option v-for="year in years" :key="year" :value="year">
                                            {{ year }}
                                        </option>
                                    </select>
                                </div>
                                <button
                                    class="p-1 hover:bg-gray-100 rounded"
                                    @click="nextMonth"
                                >
                                    <Icon icon="chevron-right" class="w-4 h-4 text-gray-600" />
                                </button>
                            </div>
                            
                            <!-- Days of week -->
                            <div class="grid grid-cols-7 mb-1">
                                <span v-for="day in ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']" :key="day" class="text-center text-xs font-bold text-gray-500 py-1">
                                    {{ day }}
                                </span>
                            </div>
                            
                            <!-- Days -->
                            <div class="grid grid-cols-7">
                                <template v-for="(day, index) in rightCalendarDays" :key="'right-' + index">
                                    <button
                                        v-if="day"
                                        class="text-sm rounded transition-colors flex items-center justify-center font-normal w-7 h-7 mx-auto"
                                        :class="{
                                            'bg-blue-600 text-white hover:bg-blue-700': isSameDay(day, startDate) || isSameDay(day, endDate),
                                            'bg-blue-100 text-gray-900': isDateInRange(day) || isDateInHoverRange(day),
                                            'hover:bg-gray-100 text-gray-700': !isSameDay(day, startDate) && !isSameDay(day, endDate),
                                        }"
                                        @click="selectDate(day)"
                                        @mouseenter="handleHover(day)"
                                        @mouseleave="handleHover(null)"
                                    >
                                        {{ day.getDate() }}
                                    </button>
                                    <div v-else class="w-7 h-7"></div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div v-if="hasValue" class="flex items-center justify-between p-2.5 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                <div class="text-sm text-gray-700">
                    <span v-if="startDate">{{ formatDateDisplay(startDate) }}</span>
                    <span v-if="startDate && endDate"> - </span>
                    <span v-if="endDate">{{ formatDateDisplay(endDate) }}</span>
                </div>
                <div class="flex gap-2">
                    <button 
                        class="px-3 py-1.5 text-sm rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-100"
                        @click="clearRange"
                    >
                        Clear
                    </button>
                    <button 
                        class="px-3 py-1.5 text-sm rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="!startDate || !endDate"
                        @click="applyRange"
                    >
                        Apply
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
