import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export interface Student {
    id: number;
    user?: {
        name: string;
        email: string;
    };
    admission_no: string;
    status: string;
    admission_date?: string;
    campus_id: number;
    class_id?: number;
    date_of_birth?: string;
    cnic?: string;
    gender_id?: number;
    religion_id?: number;
    session_id?: number;
    section_id?: number;
    class?: { id: number; name: string };
    section?: { id: number; name: string };
    session?: { id: number; name: string };
    primary_guardian?: { name: string; contact: string };
}

export interface PaginationMeta {
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export const useStudentsStore = defineStore('students', () => {
    const students = ref<Student[]>([]);
    const pagination = ref<PaginationMeta | null>(null);
    const loading = ref(false);
    const filters = ref({
        trashed: false,
        status: '',
        class_id: '',
        per_page: 10,
    });

    const fetchStudents = async (page = 1) => {
        loading.value = true;
        try {
            const params = new URLSearchParams({
                trashed: filters.value.trashed ? '1' : '0',
                per_page: filters.value.per_page.toString(),
                page: page.toString(),
            });

            if (filters.value.status) {
                params.append('status', filters.value.status);
            }
            if (filters.value.class_id) {
                params.append('class_id', filters.value.class_id);
            }

            const response = await axios.get(`/students?${params}`);
            students.value = response.data.data;
            pagination.value = response.data;
        } catch (error) {
            console.error('Failed to fetch students:', error);
        } finally {
            loading.value = false;
        }
    };

    const addStudent = (student: Student) => {
        students.value.unshift(student);
        if (pagination.value) {
            pagination.value.total += 1;
            pagination.value.to += 1;
        }
    };

    const updateStudent = (updatedStudent: Student) => {
        const index = students.value.findIndex(s => s.id === updatedStudent.id);
        if (index !== -1) {
            students.value[index] = updatedStudent;
        }
    };

    const removeStudent = (studentId: number) => {
        students.value = students.value.filter(s => s.id !== studentId);
        if (pagination.value) {
            pagination.value.total -= 1;
            pagination.value.to = Math.max(0, pagination.value.to - 1);
        }
    };

    const setFilters = (newFilters: Partial<typeof filters.value>) => {
        filters.value = { ...filters.value, ...newFilters };
        fetchStudents();
    };

    const setInitialData = (initialStudents: Student[], initialPagination: PaginationMeta | null) => {
        students.value = initialStudents;
        pagination.value = initialPagination;
    };

    return {
        students: computed(() => students.value),
        pagination: computed(() => pagination.value),
        loading: computed(() => loading.value),
        filters: computed(() => filters.value),
        fetchStudents,
        addStudent,
        updateStudent,
        removeStudent,
        setFilters,
        setInitialData,
    };
});