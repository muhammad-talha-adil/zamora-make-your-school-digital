<script setup lang="ts">
import BulkSubjectForm from '@/components/forms/BulkSubjectForm.vue';
import SubjectForm from '@/components/forms/SubjectForm.vue';
import RowAction from '@/components/tables/RowAction.vue';
import RowActions from '@/components/tables/RowActions.vue';
import TablePagination from '@/components/tables/TablePagination.vue';
import { alert } from '@/utils';
import StatusToggle from '@/components/tables/StatusToggle.vue';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

interface Props {
    subjects: any;
    isSuperAdmin?: boolean;
    campuses?: Array<{ id: number; name: string }>;
}

const props = withDefaults(defineProps<Props>(), {
    isSuperAdmin: false,
    campuses: () => [],
});
const EMPTY_VALUE = '-';

const statusFilter = ref('');
const perPage = ref(10);
const subjectsData = ref(props.subjects?.data || []);
const pagination = ref(props.subjects || { data: [], links: [], from: 0, to: 0, total: 0 });

const statusOptions = [
    { id: '', name: 'All' },
    { id: 'active', name: 'Active' },
    { id: 'inactive', name: 'Inactive' },
];

const statusSelectOptions = computed(() => statusOptions.map((option) => ({ value: option.id, label: option.name })));

const perPageOptions = [
    { id: 10, name: '10' },
    { id: 25, name: '25' },
    { id: 50, name: '50' },
    { id: 100, name: '100' },
];

const fetchSubjects = (page = pagination.value?.current_page || 1) => {
    const params = new URLSearchParams({
        per_page: perPage.value.toString(),
        page: page.toString(),
    });

    if (statusFilter.value) {
        params.append('status', statusFilter.value);
    }

    axios.get(`${route('subjects.all')}?${params}`).then((response) => {
        subjectsData.value = response.data.data;
        pagination.value = response.data;
    });
};

const getRowNumber = (index: number) =>
    ((pagination.value?.from || 1) - 1) + index + 1;


watch([statusFilter, perPage], () => {
    fetchSubjects(1);
});

watch(
    () => props.subjects,
    (newSubjects) => {
        subjectsData.value = newSubjects.data || [];
        pagination.value = newSubjects;
    },
    { deep: true },
);

const handleSaved = () => {
    fetchSubjects(pagination.value?.current_page || 1);
};

const deleteSubject = (subject: any) => {
    alert.confirm(
        `Are you sure you want to delete "${subject.name}"?`,
        'Delete Subject',
    ).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('subjects.destroy', subject.id), {
                headers: { Accept: 'application/json' },
            }).then(() => {
                alert.success('Subject deleted successfully!');
                fetchSubjects(pagination.value?.current_page || 1);
            }).catch((error) => {
                alert.error(error.response?.data?.message || 'Failed to delete subject. Please try again.');
            });
        }
    });
};

const activateSubject = (subject: any) => {
    axios.patch(route('subjects.activate', subject.id), {}, {
        headers: { Accept: 'application/json' },
    }).then(() => {
        alert.success('Subject activated successfully!');
        fetchSubjects(pagination.value?.current_page || 1);
    }).catch((error) => {
        alert.error(error.response?.data?.message || 'Failed to activate subject. Please try again.');
    });
};

const inactivateSubject = (subject: any) => {
    alert.confirm(
        `Are you sure you want to deactivate "${subject.name}"?`,
        'Deactivate Subject',
        'Yes, deactivate it!',
    ).then((result) => {
        if (result.isConfirmed) {
            axios.patch(route('subjects.inactivate', subject.id), {}, {
                headers: { Accept: 'application/json' },
            }).then(() => {
                alert.success('Subject deactivated successfully!');
                fetchSubjects(pagination.value?.current_page || 1);
            }).catch((error) => {
                alert.error(error.response?.data?.message || 'Failed to deactivate subject. Please try again.');
            });
        }
    });
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap gap-2 justify-between items-center">
            <div class="flex gap-2">
                <SearchableSelect v-model="statusFilter" :options="statusSelectOptions" placeholder="All" class="w-32" />
            </div>
            <div class="flex gap-2">
                <BulkSubjectForm :is-super-admin="isSuperAdmin" :campuses="campuses" @saved="handleSaved" />
                <SubjectForm :is-super-admin="isSuperAdmin" :campuses="campuses" @saved="handleSaved" />
            </div>
        </div>
        <div class="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                #
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Subject Name
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Description
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        <tr
                            v-for="(subject, index) in subjectsData"
                            :key="subject.id"
                            class="transition-colors hover:bg-accent"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-muted-foreground">
                                    {{ getRowNumber(index as number) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-foreground">
                                    {{ subject.name }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs truncate text-sm text-muted-foreground">
                                    {{ subject.description || EMPTY_VALUE }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <StatusToggle
                                    :active="subject.is_active"
                                    @toggle="subject.is_active ? inactivateSubject(subject) : activateSubject(subject)"
                                />
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                <RowActions>
                                    <SubjectForm
                                        :subject="subject"
                                        :is-super-admin="isSuperAdmin"
                                        :campuses="campuses"
                                        trigger="Edit"
                                        class="contents"
                                        @saved="handleSaved"
                                    />
                                    <RowAction kind="delete" @click="deleteSubject(subject)" />
                                </RowActions>
                            </td>
                        </tr>
                        <tr v-if="subjectsData.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-muted-foreground">No subjects found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <TablePagination
            v-model:per-page="perPage"
            :pagination="pagination"
            :per-page-options="perPageOptions"
            @page="fetchSubjects"
        />
    </div>
</template>
