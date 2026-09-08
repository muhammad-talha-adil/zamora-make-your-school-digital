<script setup lang="ts">
import ExamTypeForm from '@/components/forms/ExamTypeForm.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { alert } from '@/utils';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, watch } from 'vue';

// Props
interface Props {
    examTypes: any; // Paginated response
}

const props = defineProps<Props>();

// Emits
const showInactive = ref(false);
const statusFilter = ref('');
const perPage = ref(10);
const examTypesData = ref(props.examTypes.data || []);
const pagination = ref(props.examTypes);

const toggleInactive = () => {
    showInactive.value = !showInactive.value;
    fetchExamTypes();
};

const fetchExamTypes = (page = 1) => {
    const params = new URLSearchParams({
        per_page: perPage.value.toString(),
        page: page.toString(),
    });

    if (showInactive.value) {
        params.append('status', 'inactive');
    } else if (statusFilter.value) {
        params.append('status', statusFilter.value);
    }

    axios.get(`/settings/exam-types/all?${params}`).then((response) => {
        examTypesData.value = response.data.data;
        pagination.value = response.data;
    });
};

watch([statusFilter, perPage], () => {
    fetchExamTypes();
});

// Watch for props changes (e.g., after form submissions)
watch(() => props.examTypes, (newExamTypes) => {
    examTypesData.value = newExamTypes.data || [];
    pagination.value = newExamTypes;
}, { deep: true });

const inactivateExamType = (examType: any) => {
    alert
        .confirm(
            `Are you sure you want to deactivate "${examType.name}"?`,
            'Deactivate Exam Type',
        )
        .then((result) => {
            if (result.isConfirmed) {
                router.patch(`/settings/exam-types/${examType.id}/inactivate`, {}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success('Exam type deactivated successfully!');
                        fetchExamTypes();
                    },
                    onError: () => {
                        alert.error(
                            'Failed to deactivate exam type. Please try again.',
                        );
                    },
                });
            }
        });
};

const activateExamType = (examType: any) => {
    router.patch(`/settings/exam-types/${examType.id}/activate`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            alert.success('Exam type activated successfully!');
            fetchExamTypes();
        },
        onError: () => {
            alert.error('Failed to activate exam type. Please try again.');
        },
    });
};

const forceDeleteExamType = (examType: any) => {
    alert
        .confirm(
            `Are you sure you want to permanently delete "${examType.name}"? This action cannot be undone.`,
            'Delete Exam Type',
        )
        .then((result) => {
            if (result.isConfirmed) {
                router.delete(`/settings/exam-types/${examType.id}/force-delete`, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success('Exam type permanently deleted successfully!');
                        fetchExamTypes();
                    },
                    onError: () => {
                        alert.error(
                            'Failed to permanently delete exam type. Please try again.',
                        );
                    },
                });
            }
        });
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap gap-2 justify-between items-center">
            <div class="flex gap-2">
                <select v-model="statusFilter" class="w-32 rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="flex gap-2">
                <ExamTypeForm @saved="fetchExamTypes" />
                <Button
                    :variant="showInactive ? 'ghost' : 'default'"
                    size="sm"
                    @click="toggleInactive"
                >
                    <Icon :icon="showInactive ? 'arrow-left' : 'eye'" class="mr-1" />
                    {{ showInactive ? 'Back' : 'Inactive Exam Types' }}
                </Button>
            </div>
        </div>
        <div class="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted">
                        <tr>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                #
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Name
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Short Name
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Status
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        <tr
                            v-for="(examType, index) in examTypesData"
                            :key="examType.id"
                            class="transition-colors hover:bg-accent"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-muted-foreground">
                                    {{ (index as number) + 1 }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-foreground">
                                    {{ examType.name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-muted-foreground">
                                    {{ examType.short_name || '—' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                        examType.is_active
                                            ? 'bg-success/10 text-success'
                                            : 'bg-destructive/10 text-destructive',
                                    ]"
                                >
                                    {{ examType.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                <div class="flex space-x-2" v-if="!showInactive">
                                    <ExamTypeForm
                                        :exam-type="examType"
                                        trigger="Edit"
                                        variant="outline"
                                        size="sm"
                                        @saved="fetchExamTypes"
                                    >
                                        <Icon icon="edit" class="mr-1" />Edit
                                    </ExamTypeForm>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        @click="inactivateExamType(examType)"
                                    >
                                        <Icon icon="trash" class="mr-1" />Delete
                                    </Button>
                                </div>
                                <div class="flex space-x-2" v-else>
                                    <Button
                                        variant="default"
                                        size="sm"
                                        @click="activateExamType(examType)"
                                    >
                                        <Icon icon="check" class="mr-1" />Activate
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        @click="forceDeleteExamType(examType)"
                                    >
                                        <Icon icon="x" class="mr-1" />Delete
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="flex flex-wrap gap-2 justify-between items-center">
            <div class="flex items-center gap-4">
                <div class="text-sm text-muted-foreground">
                    Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                </div>
                <select v-model="perPage" class="w-20 rounded-md border border-border bg-card text-foreground px-2 py-1 text-sm">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div class="flex gap-1">
                <Button
                    v-for="link in pagination.links"
                    :key="link.label"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    :disabled="!link.url"
                    @click="link.url ? fetchExamTypes(parseInt(link.url.match(/page=(\d+)/)?.[1] || '1')) : null"
                >
                    <span v-html="link.label"></span>
                </Button>
            </div>
        </div>
    </div>
</template>
