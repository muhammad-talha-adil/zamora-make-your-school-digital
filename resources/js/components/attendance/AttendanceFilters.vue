<template>
    <div class="flex flex-col sm:flex-row gap-2 md:gap-3 flex-wrap">
        <!-- Campus Filter -->
        <select
            v-model="filters.campus_id"
            @change="applyFilters"
            class="w-full sm:w-44 md:w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
        >
            <option value="">All Campuses</option>
            <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                {{ campus.name }}
            </option>
        </select>

        <!-- Session Filter -->
        <select
            v-model="filters.session_id"
            @change="applyFilters"
            class="w-full sm:w-44 md:w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
        >
            <option value="">All Sessions</option>
            <option v-for="session in props.sessions" :key="session.id" :value="session.id">
                {{ session.name }}
            </option>
        </select>

        <!-- Class Filter -->
        <select
            v-model="filters.class_id"
            @change="onClassChange"
            class="w-full sm:w-44 md:w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
        >
            <option value="">All Classes</option>
            <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">
                {{ cls.name }}
            </option>
        </select>

        <!-- Section Filter -->
        <select
            v-model="filters.section_id"
            @change="applyFilters"
            class="w-full sm:w-44 md:w-48 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
        >
            <option value="">All Sections</option>
            <option v-for="section in filteredSections" :key="section.id" :value="section.id">
                {{ section.name }}
            </option>
        </select>

        <!-- Date Filter -->
        <input
            v-model="filters.date"
            type="date"
            @change="applyFilters"
            class="w-full sm:w-44 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
        />

        <!-- Locked Filter -->
        <select
            v-model="filters.locked"
            @change="applyFilters"
            class="w-full sm:w-40 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm min-h-10 md:min-h-11"
        >
            <option value="">All Status</option>
            <option value="1">Locked</option>
            <option value="0">Unlocked</option>
        </select>

        <!-- Reset Button -->
        <Button variant="outline" size="sm" @click="resetFilters" class="min-h-10 md:min-h-11">
            <Icon icon="rotate-ccw" class="mr-1 h-4 w-4" />
            Reset
        </Button>
    </div>
</template>

<script setup lang="ts">
import { reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import type { Campus, Session, SchoolClass, Section, AttendanceFilters } from '@/types/attendance';

interface Props {
    campuses: Campus[];
    sessions: Session[];
    classes: SchoolClass[];
    sections: Section[];
    filters: AttendanceFilters;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:filters', filters: AttendanceFilters): void;
}>();

const filters = reactive<AttendanceFilters>({
    campus_id: props.filters?.campus_id || '',
    session_id: props.filters?.session_id || '',
    class_id: props.filters?.class_id || '',
    section_id: props.filters?.section_id || '',
    date: props.filters?.date || '',
    locked: props.filters?.locked || '',
});

// Filter sections based on selected class
const filteredSections = computed(() => {
    if (!filters.class_id) {
        return props.sections;
    }
    return props.sections.filter(
        (section: Section) => section.class_id === Number(filters.class_id)
    );
});

// Handle class change - reset section
const onClassChange = () => {
    filters.section_id = '';
    applyFilters();
};

const buildQueryString = () => {
    const params = new URLSearchParams();
    if (filters.campus_id) params.append('campus_id', filters.campus_id);
    if (filters.session_id) params.append('session_id', filters.session_id);
    if (filters.class_id) params.append('class_id', filters.class_id);
    if (filters.section_id) params.append('section_id', filters.section_id);
    if (filters.date) params.append('date', filters.date);
    if (filters.locked) params.append('locked', filters.locked);
    return params.toString();
};

const applyFilters = () => {
    router.visit(route('attendance.index') + `?${buildQueryString()}`, {
        preserveState: true,
    });
};

const resetFilters = () => {
    filters.campus_id = '';
    filters.session_id = '';
    filters.class_id = '';
    filters.section_id = '';
    filters.date = '';
    filters.locked = '';
    applyFilters();
};
</script>
