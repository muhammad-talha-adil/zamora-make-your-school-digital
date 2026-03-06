import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export interface Campus {
    id: number;
    name: string;
    code: string;
    address?: string;
    status: string;
    campus_type_id: number;
    campus_type?: { id: number; name: string };
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

export const useCampusesStore = defineStore('campuses', () => {
    const campuses = ref<Campus[]>([]);
    const pagination = ref<PaginationMeta | null>(null);
    const loading = ref(false);
    const filters = ref({
        trashed: false,
        status: '',
        per_page: 10,
    });

    const fetchCampuses = async (page = 1) => {
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

            const response = await axios.get(`/settings/campuses?${params}`);
            campuses.value = response.data.data;
            pagination.value = response.data;
        } catch (error) {
            console.error('Failed to fetch campuses:', error);
        } finally {
            loading.value = false;
        }
    };

    const addCampus = (campus: Campus) => {
        campuses.value.unshift(campus);
        if (pagination.value) {
            pagination.value.total += 1;
            pagination.value.to += 1;
        }
    };

    const updateCampus = (updatedCampus: Campus) => {
        const index = campuses.value.findIndex(c => c.id === updatedCampus.id);
        if (index !== -1) {
            campuses.value[index] = updatedCampus;
        }
    };

    const removeCampus = (campusId: number) => {
        campuses.value = campuses.value.filter(c => c.id !== campusId);
        if (pagination.value) {
            pagination.value.total -= 1;
            pagination.value.to = Math.max(0, pagination.value.to - 1);
        }
    };

    const setFilters = (newFilters: Partial<typeof filters.value>) => {
        filters.value = { ...filters.value, ...newFilters };
        fetchCampuses();
    };

    return {
        campuses: computed(() => campuses.value),
        pagination: computed(() => pagination.value),
        loading: computed(() => loading.value),
        filters: computed(() => filters.value),
        fetchCampuses,
        addCampus,
        updateCampus,
        removeCampus,
        setFilters,
    };
});