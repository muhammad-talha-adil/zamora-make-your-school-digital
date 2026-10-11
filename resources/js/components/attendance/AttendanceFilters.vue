<template>
    <FilterCard>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 md:gap-3">
            <!-- Campus Filter -->
            <SearchableSelect
                v-model="filters.campus_id"
                :options="campusOptions"
                placeholder="All Campuses"
                clearable
            />

            <!-- Session Filter -->
            <SearchableSelect
                v-model="filters.session_id"
                :options="sessionOptions"
                placeholder="All Sessions"
                clearable
            />

            <!-- Class Filter -->
            <SearchableSelect
                v-model="filters.class_id"
                :options="classOptions"
                placeholder="All Classes"
                clearable
                @update:model-value="onClassChange"
            />

            <!-- Section Filter -->
            <SearchableSelect
                v-model="filters.section_id"
                :options="sectionOptions"
                placeholder="All Sections"
                clearable
            />

            <!-- Date Filter -->
            <input
                v-model="filters.date"
                type="date"
                class="w-full rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm min-h-10 md:min-h-11"
            />

            <!-- Locked Filter -->
            <SearchableSelect
                v-model="filters.locked"
                :options="lockedOptions"
                placeholder="All Status"
                clearable
            />
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
            <!-- Load Button -->
            <Button variant="default" size="sm" @click="applyFilters" class="min-h-10 md:min-h-11 bg-primary hover:bg-primary/90">
                <Icon icon="search" class="mr-1 h-4 w-4" />
                Load
            </Button>

            <!-- Reset Button -->
            <Button variant="outline" size="sm" @click="resetFilters" class="min-h-10 md:min-h-11">
                <Icon icon="rotate-ccw" class="mr-1 h-4 w-4" />
                Reset
            </Button>
        </div>
    </FilterCard>
</template>

<script setup lang="ts">
import { reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import FilterCard from '@/components/FilterCard.vue';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
import type { Campus, Session, SchoolClass, Section, AttendanceFilters } from '@/types/attendance';

interface Props {
    campuses: Campus[];
    sessions: Session[];
    classes: SchoolClass[];
    sections: Section[];
    filters: AttendanceFilters;
}

const props = defineProps<Props>();

const filters = reactive<AttendanceFilters>({
    campus_id: props.filters?.campus_id || '',
    session_id: props.filters?.session_id || '',
    class_id: props.filters?.class_id || '',
    section_id: props.filters?.section_id || '',
    date: props.filters?.date || new Date().toISOString().split('T')[0],
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

const campusOptions = computed(() => props.campuses.map((campus) => ({ value: campus.id, label: campus.name })));
const sessionOptions = computed(() => props.sessions.map((session) => ({ value: session.id, label: session.name })));
const classOptions = computed(() => props.classes.map((cls) => ({ value: cls.id, label: cls.name })));
const sectionOptions = computed(() => filteredSections.value.map((section) => ({ value: section.id, label: section.name })));
const lockedOptions = [
    { value: '1', label: 'Locked' },
    { value: '0', label: 'Unlocked' },
];

// Handle class change - reset section but don't apply filters
const onClassChange = () => {
    filters.section_id = '';
};

const buildQueryString = () => {
    const params = new URLSearchParams();
    params.append('load', '1'); // Mark as loaded
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
    filters.date = new Date().toISOString().split('T')[0];
    filters.locked = '';
    router.visit(route('attendance.index'), {
        preserveState: true,
        replace: true,
    });
};
</script>
