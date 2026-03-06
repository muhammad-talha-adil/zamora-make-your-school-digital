<template>
    <div class="marking-grid-container">
        <!-- Collapsible Header -->
        <div 
            class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-t-lg border border-gray-200 dark:border-gray-700 cursor-pointer"
            @click="toggleExpanded"
        >
            <div class="flex items-center gap-2">
                <Icon 
                    :icon="isExpanded ? 'chevron-up' : 'chevron-down'" 
                    class="w-5 h-5 transition-transform duration-200"
                />
                <span class="font-medium text-gray-900 dark:text-white">
                    {{ isExpanded ? 'Hide' : 'Show' }} Marking Grid
                </span>
                <span v-if="studentsCount > 0" class="text-sm text-gray-500 dark:text-gray-400">
                    ({{ studentsCount }} students)
                </span>
            </div>
            <div v-if="isExpanded && hasChanges" class="text-sm text-amber-600 dark:text-amber-400">
                <Icon icon="alert-circle" class="inline w-4 h-4 mr-1" />
                Unsaved changes
            </div>
        </div>

        <!-- Expanded Grid Content -->
        <div v-show="isExpanded" class="border-x border-b border-gray-200 dark:border-gray-700 rounded-b-lg overflow-hidden">
            <!-- Error Message -->
            <div v-if="error" class="bg-red-50 dark:bg-red-900/30 border-b border-red-200 dark:border-red-700 p-4">
                <div class="flex items-center gap-2 text-red-800 dark:text-red-200">
                    <Icon icon="alert-triangle" class="w-5 h-5" />
                    <span class="font-medium">{{ error }}</span>
                </div>
            </div>

            <!-- Locked Warning -->
            <div v-else-if="isLocked" class="bg-yellow-50 dark:bg-yellow-900/30 border-b border-yellow-200 dark:border-yellow-700 p-4">
                <div class="flex items-center gap-2 text-yellow-800 dark:text-yellow-200">
                    <Icon icon="lock" class="w-5 h-5" />
                    <span class="font-medium">This exam is locked. Marks cannot be edited.</span>
                </div>
            </div>

            <!-- Filters Summary -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4">
                <div class="flex flex-wrap gap-3 md:gap-6 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Exam:</span>
                        <span class="font-medium ml-1 text-gray-900 dark:text-white">{{ selectedExam?.name || 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Class:</span>
                        <span class="font-medium ml-1 text-gray-900 dark:text-white">{{ selectedClass?.name || 'N/A' }}</span>
                    </div>
                    <div v-if="filters.section_id">
                        <span class="text-gray-500 dark:text-gray-400">Section:</span>
                        <span class="font-medium ml-1 text-gray-900 dark:text-white">{{ selectedSection?.name || 'N/A' }}</span>
                    </div>
                    <div v-else>
                        <span class="text-gray-500 dark:text-gray-400">Section:</span>
                        <span class="font-medium ml-1 text-gray-900 dark:text-white">All Sections</span>
                    </div>
                    <div v-if="gradeSystems.length > 0">
                        <span class="text-gray-500 dark:text-gray-400">Grade System:</span>
                        <select
                            v-model="gradeSystemId"
                            @change="onGradeSystemChange"
                            class="ml-1 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option :value="null">Select Grade System</option>
                            <option v-for="gs in gradeSystems" :key="gs.id" :value="gs.id">
                                {{ gs.name }}<span v-if="gs.is_default"> (Default)</span>
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Student Search -->
                <div v-if="students.length > 0" class="mt-4 flex items-center gap-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Search Student:</span>
                    <div class="w-64">
                        <ComboboxInput
                            v-model="searchQuery"
                            placeholder="Search by student name..."
                            :search-url="studentSearchUrl"
                        />
                    </div>
                    <Button 
                        v-if="searchQuery" 
                        variant="ghost" 
                        size="sm"
                        @click="clearSearch"
                    >
                        <Icon icon="x" class="w-4 h-4" />
                    </Button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="text-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-4 text-gray-500 dark:text-gray-400">Loading marking data...</p>
            </div>

            <!-- No Papers Error State -->
            <div v-else-if="!loading && !error && papers.length === 0" class="text-center py-12">
                <Icon icon="file-x" class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                <p class="text-gray-500 dark:text-gray-400">
                    No examination papers have been registered for this class and section.
                </p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">
                    Please configure papers in the Exam Papers section first.
                </p>
            </div>

            <!-- Marking Grid -->
            <div v-else-if="!loading && !error && filteredStudents.length > 0" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-2 md:px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase min-w-[50px] md:min-w-[60px]">
                                Sr#
                            </th>
                            <th class="px-2 md:px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase sticky left-0 bg-gray-50 dark:bg-gray-800 min-w-[160px] md:min-w-[200px] z-10">
                                Student
                            </th>
                            <th class="px-2 md:px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase min-w-[120px] md:min-w-[150px]">
                                Campus / Class / Section
                            </th>
                            <th 
                                v-for="paper in papers" 
                                :key="paper.id"
                                class="px-1 md:px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase min-w-[100px] md:min-w-[140px]"
                            >
                                <div class="text-xs md:text-sm">{{ paper.subject }}</div>
                                <div class="text-[10px] text-gray-400 dark:text-gray-500">
                                    ({{ paper.total_marks }} / {{ paper.passing_marks }})
                                </div>
                            </th>
                            <th class="px-2 md:px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase min-w-[70px] md:min-w-[90px]">
                                Total
                            </th>
                            <th class="px-2 md:px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase min-w-[50px] md:min-w-[60px]">
                                %
                            </th>
                            <th class="px-2 md:px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase min-w-[50px] md:min-w-[60px]">
                                Grade
                            </th>
                            <th class="px-2 md:px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase min-w-[60px] md:min-w-[80px]">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="(student, index) in filteredStudents" :key="student.student.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-2 md:px-3 py-2 md:py-3 text-center text-sm text-gray-900 dark:text-white">
                                {{ index + 1 }}
                            </td>
                            <td class="px-2 md:px-3 py-2 md:py-3 text-sm font-medium text-gray-900 dark:text-white sticky left-0 bg-white dark:bg-gray-900 min-w-[160px] md:min-w-[200px] z-10">
                                <div class="truncate max-w-[150px] md:max-w-none">{{ student.student.name }}</div>
                            </td>
                            <td class="px-2 md:px-3 py-2 md:py-3 text-xs md:text-sm text-gray-500 dark:text-gray-400">
                                <div class="hidden md:block">{{ student.student.campus }}</div>
                                <div>{{ student.student.class }} - {{ student.student.section }}</div>
                            </td>
                            <td 
                                v-for="paper in papers" 
                                :key="paper.id"
                                class="px-1 md:px-2 py-2"
                            >
                                <div class="relative">
                                    <input
                                        type="number"
                                        :value="getMarks(student, paper.id)?.obtained ?? ''"
                                        @input="updateMarks(student, paper.id, $event, paper.total_marks)"
                                        :disabled="isLocked"
                                        :max="paper.total_marks"
                                        min="0"
                                        step="0.5"
                                        class="w-full text-center border rounded px-1 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        :class="{
                                            'bg-gray-100 dark:bg-gray-800 text-gray-500 cursor-not-allowed': isLocked,
                                            'bg-white dark:bg-gray-700 text-gray-900 dark:text-white': !isLocked,
                                            'border-red-500 dark:border-red-500 ring-1 ring-red-500': getFieldError(student.student.id, paper.id),
                                            'border-gray-300 dark:border-gray-600': !getFieldError(student.student.id, paper.id)
                                        }"
                                        placeholder="-"
                                    />
                                    <div v-if="getFieldError(student.student.id, paper.id)" class="text-[10px] text-red-500 mt-0.5 text-center">
                                        {{ getFieldError(student.student.id, paper.id) }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-2 md:px-3 py-2 md:py-3 text-right text-sm font-medium text-gray-900 dark:text-white">
                                {{ student.total_obtained }} / {{ totalMaxMarks }}
                            </td>
                            <td class="px-2 md:px-3 py-2 md:py-3 text-right text-sm text-gray-900 dark:text-white">
                                {{ student.percentage }}%
                            </td>
                            <td class="px-2 md:px-3 py-2 md:py-3 text-center">
                                <span 
                                    class="px-2 py-0.5 md:py-1 rounded text-xs font-medium"
                                    :class="getGradeClass(student.grade)"
                                >
                                    {{ student.grade }}
                                </span>
                            </td>
                            <td class="px-2 md:px-3 py-2 md:py-3 text-center">
                                <Button 
                                    size="sm" 
                                    @click="saveRow(student)"
                                    :disabled="isLocked || !student.hasChanges || hasStudentErrors(student)"
                                    variant="outline"
                                    class="text-xs"
                                >
                                    <Icon icon="save" class="w-3 h-3 mr-1" />
                                    Save
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else-if="!loading && !error && filteredStudents.length === 0 && students.length > 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                <Icon icon="search-x" class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                <p>No students found matching your search.</p>
            </div>

            <div v-else-if="!loading && !error" class="text-center py-12 text-gray-500 dark:text-gray-400">
                No students found for the selected criteria.
            </div>

            <!-- Action Bar -->
            <div v-if="filteredStudents.length > 0" class="p-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Showing {{ filteredStudents.length }} of {{ students.length }} students
                </div>
                <div class="flex gap-2">
                    <Button 
                        @click="refreshData" 
                        variant="outline"
                        :disabled="loading"
                    >
                        <Icon icon="refresh-cw" class="w-4 h-4 mr-1" />
                        Refresh
                    </Button>
                    <Button 
                        @click="saveBulk" 
                        :disabled="!hasChanges || isLocked"
                        variant="default"
                    >
                        <Icon icon="save" class="w-4 h-4 mr-1" />
                        Save All Marks
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { ref, computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import ComboboxInput from '@/components/ui/combobox/ComboboxInput.vue';
import { alert } from '@/utils';

interface Paper {
    id: number;
    subject: string;
    total_marks: number;
    passing_marks: number;
    scope_type: string;
}

interface StudentMarks {
    obtained: number | null;
    is_absent: boolean;
}

interface StudentData {
    student: {
        id: number;
        name: string;
        campus: string;
        class: string;
        section: string;
    };
    enrollment_id: number | null;
    marks: Record<number, StudentMarks>;
    total_obtained: number;
    total_max_marks: number;
    percentage: number;
    grade: string;
    hasChanges?: boolean;
}

interface GridData {
    papers: Paper[];
    students: StudentData[];
    is_locked: boolean;
    total_max_marks: number;
    error?: string;
}

interface Props {
    exams: Array<{id: number; name: string}>;
    campuses: Array<{id: number; name: string}>;
    classes: Array<{id: number; name: string}>;
    sections: Array<{id: number; name: string; class_id: number}>;
    gradeSystems: Array<{id: number; name: string; is_default: boolean}>;
    filters: {
        exam_id: number | null;
        campus_id: number | null;
        class_id: number | null;
        section_id: number | null;
        grade_system_id: number | null;
    };
}

interface Emits {
    (e: 'save-complete'): void;
    (e: 'filters-reset'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const isExpanded = ref(true);
const loading = ref(false);
const error = ref<string | null>(null);
const papers = ref<Paper[]>([]);
const students = ref<StudentData[]>([]);
const isLocked = ref(false);
const totalMaxMarks = ref(0);
const fieldErrors = ref<Record<string, string>>({});
const gradeSystemId = ref<number | null>(props.filters.grade_system_id ?? null);
const searchQuery = ref<number | string | null>(null);

const selectedExam = computed(() => 
    props.exams.find(e => e.id === props.filters.exam_id)
);

const selectedClass = computed(() => 
    props.classes.find(c => c.id === props.filters.class_id)
);

const selectedSection = computed(() => 
    props.sections.find(s => s.id === props.filters.section_id)
);

const gradeSystems = computed(() => props.gradeSystems || []);

const studentsCount = computed(() => students.value.length);

const studentSearchUrl = computed(() => {
    if (!props.filters.exam_id || !props.filters.class_id) return '';
    return `/exams/marking/search-students?exam_id=${props.filters.exam_id}&class_id=${props.filters.class_id}${props.filters.section_id ? '&section_id=' + props.filters.section_id : ''}`;
});

const filteredStudents = computed(() => {
    if (!searchQuery.value) {
        return students.value;
    }
    const query = String(searchQuery.value).toLowerCase();
    return students.value.filter(s => 
        s.student.name.toLowerCase().includes(query)
    );
});

const hasChanges = computed(() => {
    return students.value.some(s => s.hasChanges);
});

function toggleExpanded() {
    isExpanded.value = !isExpanded.value;
}

function getMarks(student: StudentData, paperId: number): StudentMarks | undefined {
    return student.marks[paperId];
}

function getFieldError(studentId: number, paperId: number): string | undefined {
    return fieldErrors.value[`${studentId}-${paperId}`];
}

function setFieldError(studentId: number, paperId: number, errorMsg: string | null) {
    const key = `${studentId}-${paperId}`;
    if (errorMsg) {
        fieldErrors.value[key] = errorMsg;
    } else {
        delete fieldErrors.value[key];
    }
}

function hasStudentErrors(student: StudentData): boolean {
    for (const paper of papers.value) {
        if (getFieldError(student.student.id, paper.id)) {
            return true;
        }
    }
    return false;
}

function getGradeClass(grade: string): string {
    if (grade === 'N/A' || !grade) {
        return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
    }
    const goodGrades = ['A+', 'A', 'A-', 'B+', 'B'];
    if (goodGrades.includes(grade)) {
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    }
    const avgGrades = ['B-', 'C+', 'C'];
    if (avgGrades.includes(grade)) {
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    }
    return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
}

function calculateGrade(percentage: number): string {
    if (percentage === 0) {
        return 'N/A';
    }
    if (percentage >= 90) return 'A+';
    if (percentage >= 80) return 'A';
    if (percentage >= 75) return 'A-';
    if (percentage >= 70) return 'B+';
    if (percentage >= 65) return 'B';
    if (percentage >= 60) return 'B-';
    if (percentage >= 55) return 'C+';
    if (percentage >= 50) return 'C';
    if (percentage >= 40) return 'D';
    return 'F+';
}

function updateMarks(student: StudentData, paperId: number, event: Event, maxMarks: number) {
    if (isLocked.value) return;
    
    const target = event.target as HTMLInputElement;
    const valueStr = target.value;
    // Convert to number, not string concatenation
    const value = valueStr === '' ? null : parseFloat(valueStr);
    
    setFieldError(student.student.id, paperId, null);
    
    if (value !== null && !isNaN(value)) {
        if (value < 0) {
            setFieldError(student.student.id, paperId, 'Cannot be negative');
            return;
        }
        if (value > maxMarks) {
            setFieldError(student.student.id, paperId, `Max: ${maxMarks}`);
            return;
        }
    }
    
    if (!student.marks[paperId]) {
        student.marks[paperId] = { obtained: null, is_absent: false };
    }
    // Ensure we store as number, not string
    student.marks[paperId].obtained = value;
    student.hasChanges = true;
    
    recalculateStudent(student);
}

function recalculateStudent(student: StudentData) {
    let totalObtained = 0;
    
    for (const paper of papers.value) {
        const mark = student.marks[paper.id];
        if (mark && mark.obtained !== null && !mark.is_absent) {
            const obtainedValue = Number(mark.obtained);
            if (!isNaN(obtainedValue)) {
                totalObtained += obtainedValue;
            }
        }
    }
    
    student.total_obtained = totalObtained;
    student.total_max_marks = totalMaxMarks.value;
    student.percentage = totalMaxMarks.value > 0 ? Math.round((totalObtained / totalMaxMarks.value) * 100 * 100) / 100 : 0;
    student.grade = calculateGrade(student.percentage);
}

async function loadGridData() {
    if (!props.filters.exam_id || !props.filters.class_id) {
        return;
    }

    loading.value = true;
    error.value = null;
    fieldErrors.value = {};
    
    try {
        const params = new URLSearchParams();
        if (props.filters.exam_id) params.append('exam_id', String(props.filters.exam_id));
        if (props.filters.campus_id) params.append('campus_id', String(props.filters.campus_id));
        if (props.filters.class_id) params.append('class_id', String(props.filters.class_id));
        if (props.filters.section_id) params.append('section_id', String(props.filters.section_id));
        if (gradeSystemId.value) params.append('grade_system_id', String(gradeSystemId.value));
        
        const response = await axios.get(`/exams/marking/grid-data?${params.toString()}`);
        const data: GridData = response.data;
        
        // Check for error response
        if (data.error) {
            error.value = data.error;
            papers.value = [];
            students.value = [];
            totalMaxMarks.value = 0;
            isLocked.value = false;
            return;
        }
        
        papers.value = data.papers;
        totalMaxMarks.value = data.total_max_marks || 0;
        
        students.value = data.students.map((s: StudentData) => {
            let totalObtained = 0;
            for (const paper of papers.value) {
                const mark = s.marks[paper.id];
                // Ensure obtained is converted to number from potential string
                if (mark) {
                    if (mark.obtained !== null) {
                        const obtainedValue = Number(mark.obtained);
                        if (!isNaN(obtainedValue)) {
                            mark.obtained = obtainedValue;
                            if (!mark.is_absent) {
                                totalObtained += obtainedValue;
                            }
                        }
                    }
                }
            }
            const percentage = totalMaxMarks.value > 0 ? Math.round((totalObtained / totalMaxMarks.value) * 100 * 100) / 100 : 0;
            
            return {
                ...s,
                total_obtained: totalObtained,
                total_max_marks: totalMaxMarks.value,
                percentage: percentage,
                grade: calculateGrade(percentage),
                hasChanges: false
            };
        });
        
        isLocked.value = data.is_locked;
    } catch (err: any) {
        console.error('Error loading grid:', err);
        const message = err.response?.data?.error || 'Failed to load marking data';
        error.value = message;
        papers.value = [];
        students.value = [];
        totalMaxMarks.value = 0;
    } finally {
        loading.value = false;
    }
}

async function saveRow(student: StudentData) {
    if (isLocked.value) return;
    
    if (hasStudentErrors(student)) {
        alert.error('Please fix validation errors before saving');
        return;
    }
    
    try {
        await axios.post('/exams/marking/save-row', {
            exam_id: props.filters.exam_id,
            student_id: student.student.id,
            enrollment_id: student.enrollment_id,
            marks: student.marks,
        });
        
        student.hasChanges = false;
        alert.success('Marks saved successfully');
    } catch (err: any) {
        console.error('Error saving row:', err);
        const message = err.response?.data?.error || err.response?.data?.message || 'Failed to save marks';
        alert.error(message);
    }
}

async function saveBulk() {
    if (isLocked.value || !hasChanges.value) return;
    
    const errorKeys = Object.keys(fieldErrors.value);
    if (errorKeys.length > 0) {
        alert.error('Please fix validation errors before saving');
        return;
    }
    
    try {
        const studentsWithChanges = students.value
            .filter(s => s.hasChanges)
            .map(s => ({
                student_id: s.student.id,
                enrollment_id: s.enrollment_id,
                marks: s.marks,
            }));
        
        await axios.post('/exams/marking/save-bulk', {
            exam_id: props.filters.exam_id,
            students: studentsWithChanges,
        });
        
        students.value.forEach(s => s.hasChanges = false);
        alert.success('All marks saved successfully');
        
        // Emit save complete event
        emit('save-complete');
        
        // Emit filters reset event
        emit('filters-reset');
    } catch (err: any) {
        console.error('Error saving bulk:', err);
        const message = err.response?.data?.error || err.response?.data?.message || 'Failed to save marks';
        alert.error(message);
    }
}

function onGradeSystemChange() {
    loadGridData();
}

function clearSearch() {
    searchQuery.value = null;
}

function refreshData() {
    loadGridData();
}

// Watch for filter changes to reload data
watch(() => props.filters, () => {
    if (props.filters.exam_id && props.filters.class_id) {
        loadGridData();
    }
}, { deep: true });

// Initial load
if (props.filters.exam_id && props.filters.class_id) {
    loadGridData();
}
</script>
