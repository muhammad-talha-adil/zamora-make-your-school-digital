<template>
    <div class="space-y-6">
        <!-- Filters -->
        <div class="bg-card rounded-lg border border-border p-6">
            <h3 class="text-lg font-semibold text-foreground mb-4">
                Assign Subjects to Class Sections
            </h3>
            
            <div class="grid gap-4 md:grid-cols-3">
                <!-- Campus Filter -->
                <div class="space-y-2">
                    <Label for="campus-filter">Campus</Label>
                    <SearchableSelect
                        id="campus-filter"
                        v-model="filters.campus_id"
                        :options="campusOptions"
                        placeholder="Select Campus"
                        clearable
                        :class="filters.campus_id ? 'border-green-500 focus:ring-green-500' : 'border-border'"
                    />
                </div>

                <!-- Class Filter -->
                <div class="space-y-2">
                    <Label for="class-filter">Class</Label>
                    <SearchableSelect
                        id="class-filter"
                        v-model="filters.class_id"
                        :options="classOptions"
                        placeholder="Select Class"
                        clearable
                        :class="filters.class_id ? 'border-green-500 focus:ring-green-500' : 'border-border'"
                    />
                </div>

                <!-- Section Filter -->
                <div class="space-y-2">
                    <Label for="section-filter">
                        Section
                        <span v-if="filters.class_id && classHasSections" class="text-destructive">*</span>
                    </Label>
                    <SearchableSelect
                        id="section-filter"
                        v-model="filters.section_id"
                        :options="sectionOptions"
                        :placeholder="classHasSections ? 'Select Section' : 'All Sections'"
                        :disabled="!filters.class_id"
                        clearable
                        :class="filters.section_id ? 'border-green-500 focus:ring-green-500' : 'border-border'"
                    />
                    <p v-if="filters.class_id && classHasSections && !filters.section_id" class="text-xs text-muted-foreground">
                        Select a section to load its subjects.
                    </p>
                </div>
            </div>

            <!-- Load Button -->
            <div class="mt-4">
                <Button
                    @click="loadAssignedSubjects"
                    :disabled="!canLoadSubjects || loading"
                    variant="secondary"
                >
                    <Icon icon="search" class="mr-1"  />
                    {{ loading ? 'Loading...' : 'Load Subjects' }}
                </Button>
            </div>
        </div>

        <!-- Subjects Selection -->
        <div v-if="showSubjects" class="bg-card rounded-lg border border-border p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                <h3 class="text-lg font-semibold text-foreground">
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
                    class="flex items-center gap-3 p-3 rounded-lg border border-border hover:bg-accent cursor-pointer transition-colors"
                    :class="{ 'bg-primary/10 border-primary': selectedSubjects.includes(subject.id) }"
                >
                    <input
                        type="checkbox"
                        :value="subject.id"
                        v-model="selectedSubjects"
                        class="w-4 h-4 text-primary rounded border-border focus:ring-primary"
                    />
                    <div class="flex-1">
                        <div class="text-sm font-medium text-foreground">
                            {{ subject.name }}
                        </div>
                        <div v-if="subject.short_name" class="text-xs text-muted-foreground">
                            {{ subject.short_name }}
                        </div>
                    </div>
                </label>
            </div>

            <!-- No subjects message -->
            <div v-if="subjects.length === 0" class="text-center py-8 text-muted-foreground">
                No subjects available. Please add subjects first.
            </div>

            <!-- Save Button -->
            <div class="mt-6 flex flex-wrap gap-2 justify-end">
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
        <div v-else-if="!showSubjects && hasLoaded" class="bg-card rounded-lg border border-border p-6 text-center">
            <p class="text-muted-foreground">
                Please select a class to manage subjects.
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
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

const campusOptions = computed(() => campuses.value.map((campus) => ({ value: campus.id, label: campus.name })));
const classOptions = computed(() => classes.value.map((cls) => ({ value: cls.id, label: cls.name })));
const sectionOptions = computed(() => filteredSections.value.map((section) => ({ value: section.id, label: section.name })));

// Whether the selected class has any sections at all. When it doesn't,
// "Load Subjects" only needs the class (issue #26); when it does, a section
// must be picked too, since subjects can be assigned per section.
const classHasSections = computed(() => {
    if (!filters.class_id) return false;
    return sections.value.some(s => s.class_id === Number(filters.class_id));
});

const canLoadSubjects = computed(() => {
    if (!filters.class_id) return false;
    return !classHasSections.value || !!filters.section_id;
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

// Campus filter doesn't directly affect sections in this implementation
// but kept for future use
watch(() => filters.campus_id, () => {});

watch(() => filters.class_id, () => {
    filters.section_id = '';
    showSubjects.value = false;
    hasLoaded.value = false;
    selectedSubjects.value = [];
    originalSubjects.value = [];
});

watch(() => filters.section_id, () => {
    showSubjects.value = false;
    hasLoaded.value = false;
    selectedSubjects.value = [];
    originalSubjects.value = [];
    // When section_id is empty string, it means "All Sections"
});

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
