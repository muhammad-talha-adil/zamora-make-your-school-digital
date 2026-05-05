<template>
    <div class="space-y-6">
        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Assign Subjects to Class Sections
            </h3>
            
            <div class="grid gap-4 md:grid-cols-3">
                <!-- Campus Filter -->
                <div class="space-y-2">
                    <Label for="campus-filter">Campus</Label>
                    <select
                        id="campus-filter"
                        v-model="filters.campus_id"
                        @change="onCampusChange"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                    >
                        <option value="">Select Campus</option>
                        <option v-for="campus in campuses" :key="campus.id" :value="campus.id">
                            {{ campus.name }}
                        </option>
                    </select>
                </div>

                <!-- Class Filter -->
                <div class="space-y-2">
                    <Label for="class-filter">Class</Label>
                    <select
                        id="class-filter"
                        v-model="filters.class_id"
                        @change="onClassChange"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                    >
                        <option value="">Select Class</option>
                        <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                            {{ cls.name }}
                        </option>
                    </select>
                </div>

                <!-- Section Filter -->
                <div class="space-y-2">
                    <Label for="section-filter">Section</Label>
                    <select
                        id="section-filter"
                        v-model="filters.section_id"
                        @change="onSectionChange"
                        :disabled="!filters.class_id"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm"
                    >
                        <option value="">All Sections</option>
                        <option v-for="section in filteredSections" :key="section.id" :value="section.id">
                            {{ section.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Load Button -->
            <div class="mt-4">
                <Button 
                    @click="loadAssignedSubjects" 
                    :disabled="!filters.class_id || loading"
                    variant="secondary"
                >
                    <Icon icon="search" class="mr-1" />
                    {{ loading ? 'Loading...' : 'Load Subjects' }}
                </Button>
            </div>
        </div>

        <!-- Subjects Selection -->
        <div v-if="showSubjects" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Select Subjects
                </h3>
                <div class="flex gap-2">
                    <Button variant="outline" size="sm" @click="selectAll">
                        Select All
                    </Button>
                    <Button variant="outline" size="sm" @click="deselectAll">
                        Deselect All
                    </Button>
                </div>
            </div>

            <!-- Subject Checkboxes -->
            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                <label
                    v-for="subject in subjects"
                    :key="subject.id"
                    class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors"
                    :class="{ 'bg-primary/10 border-primary': selectedSubjects.includes(subject.id) }"
                >
                    <input
                        type="checkbox"
                        :value="subject.id"
                        v-model="selectedSubjects"
                        class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary"
                    />
                    <div class="flex-1">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ subject.name }}
                        </div>
                        <div v-if="subject.short_name" class="text-xs text-gray-500 dark:text-gray-400">
                            {{ subject.short_name }}
                        </div>
                    </div>
                </label>
            </div>

            <!-- No subjects message -->
            <div v-if="subjects.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                No subjects available. Please add subjects first.
            </div>

            <!-- Save Button -->
            <div class="mt-6 flex justify-end">
                <Button 
                    @click="saveAssignments" 
                    :disabled="saving || !hasChanges"
                >
                    <Icon icon="save" class="mr-1" />
                    {{ saving ? 'Saving...' : 'Save Assignments' }}
                </Button>
            </div>
        </div>

        <!-- No class selected message -->
        <div v-else-if="!showSubjects && hasLoaded" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 text-center">
            <p class="text-gray-500 dark:text-gray-400">
                Please select a class to manage subjects.
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import Icon from '@/components/Icon.vue';
import { alert } from '@/utils';
import axios from 'axios';

interface Campus {
    id: number;
    name: string;
}

interface SchoolClass {
    id: number;
    name: string;
}

interface Section {
    id: number;
    name: string;
    class_id: number;
}

interface Subject {
    id: number;
    name: string;
    short_name: string | null;
}

// State
const campuses = ref<Campus[]>([]);
const classes = ref<SchoolClass[]>([]);
const sections = ref<Section[]>([]);
const subjects = ref<Subject[]>([]);

const filters = reactive({
    campus_id: '',
    class_id: '',
    section_id: '',
});

const loading = ref(false);
const saving = ref(false);
const showSubjects = ref(false);
const hasLoaded = ref(false);

const selectedSubjects = ref<number[]>([]);
const originalSubjects = ref<number[]>([]);

// Computed
const filteredSections = computed(() => {
    if (!filters.class_id) return [];
    return sections.value.filter(s => s.class_id === Number(filters.class_id));
});

const hasChanges = computed(() => {
    if (!showSubjects.value) return false;
    
    const selectedSet = new Set(selectedSubjects.value);
    const originalSet = new Set(originalSubjects.value);
    
    if (selectedSet.size !== originalSet.size) return true;
    
    for (const item of selectedSet) {
        if (!originalSet.has(item)) return true;
    }
    
    return false;
});

// Methods
const loadInitialData = async () => {
    try {
        const response = await fetch(route('class-subjects.index'), {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        
        campuses.value = data.campuses || [];
        classes.value = data.classes || [];
        sections.value = data.sections || [];
        subjects.value = data.subjects || [];
    } catch (error) {
        console.error('Error loading data:', error);
    }
};

const onCampusChange = () => {
    // Campus filter doesn't directly affect sections in this implementation
    // but kept for future use
};

const onClassChange = () => {
    filters.section_id = '';
    showSubjects.value = false;
    hasLoaded.value = false;
    selectedSubjects.value = [];
    originalSubjects.value = [];
};

const onSectionChange = () => {
    showSubjects.value = false;
    hasLoaded.value = false;
    selectedSubjects.value = [];
    originalSubjects.value = [];
    // When section_id is empty string, it means "All Sections"
};

const loadAssignedSubjects = async () => {
    if (!filters.class_id) return;

    loading.value = true;
    hasLoaded.value = true;
    showSubjects.value = true;

    // Check if "All Sections" is selected (empty string means all)
    const isAllSectionsSelected = filters.section_id === '';

    try {
        const params = new URLSearchParams({
            class_id: filters.class_id,
            section_id: filters.section_id || '',
            is_all_sections: isAllSectionsSelected ? '1' : '0',
        });

        const response = await fetch(`${route('class-subjects.assigned')}?${params}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();

        selectedSubjects.value = data.assigned_subjects || [];
        originalSubjects.value = [...selectedSubjects.value];
    } catch (error) {
        console.error('Error loading assigned subjects:', error);
    } finally {
        loading.value = false;
    }
};

const selectAll = () => {
    selectedSubjects.value = subjects.value.map(s => s.id);
};

const deselectAll = () => {
    selectedSubjects.value = [];
};

const saveAssignments = () => {
    const payload = {
        class_id: filters.class_id,
        section_id: filters.section_id === '' ? null : (filters.section_id || null),
        subject_ids: selectedSubjects.value,
        is_all_sections: filters.section_id === '',
    };

    saving.value = true;

    axios.post(route('class-subjects.store'), payload, {
        headers: { Accept: 'application/json' },
    }).then(() => {
            originalSubjects.value = [...selectedSubjects.value];
            alert.success('Subjects assigned successfully!');
            return loadAssignedSubjects();
        }).catch((error) => {
            const errors = error.response?.data?.errors;
            const errorMessage = errors
                ? Object.values(errors).flat().join(', ')
                : (error.response?.data?.message || 'Unknown error');

            alert.error('Failed to save: ' + errorMessage);
        }).finally(() => {
            saving.value = false;
        });
};

// Load initial data on mount
loadInitialData();
</script>
