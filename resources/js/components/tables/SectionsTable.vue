<script setup lang="ts">
import SectionForm from '@/components/forms/SectionForm.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { alert } from '@/utils';
import { tableActionButtonClass } from '@/utils/table-actions';
import axios from 'axios';
import { ref, watch } from 'vue';

const stateButtonClass = 'min-h-8 border-warning/40 text-warning hover:bg-warning/20 hover:text-warning';
interface Props {
    sections: any;
    schoolClasses?: { id: number; name: string }[];
}

const props = defineProps<Props>();
const EMPTY_VALUE = '-';

const statusFilter = ref('');
const classFilter = ref('');
const perPage = ref(10);
const sectionsData = ref(props.sections.data || []);
const pagination = ref(props.sections);

const fetchSections = (page = pagination.value?.current_page || 1) => {
    const params = new URLSearchParams({
        per_page: perPage.value.toString(),
        page: page.toString(),
    });

    if (statusFilter.value) {
        params.append('status', statusFilter.value);
    }

    if (classFilter.value) {
        params.append('class_id', classFilter.value);
    }

    axios.get(`/settings/sections/all?${params}`).then((response) => {
        sectionsData.value = response.data.data;
        pagination.value = response.data;
    });
};

const getRowNumber = (index: number) =>
    ((pagination.value?.from || 1) - 1) + index + 1;

const getPageFromUrl = (url: string | null) => {
    if (!url) {
        return null;
    }

    const page = new URL(url, window.location.origin).searchParams.get('page');
    return page ? Number.parseInt(page, 10) : 1;
};

watch([statusFilter, classFilter, perPage], () => {
    fetchSections(1);
});

watch(
    () => props.sections,
    (newSections) => {
        sectionsData.value = newSections.data || [];
        pagination.value = newSections;
    },
    { deep: true },
);

const inactivateSection = (section: any) => {
    alert
        .confirm(
            `Are you sure you want to deactivate "${section.name}"?`,
            'Deactivate Section',
            'Yes, deactivate it!',
        )
        .then((result) => {
            if (result.isConfirmed) {
                axios.patch(`/settings/sections/${section.id}/inactivate`, {}, {
                    headers: { Accept: 'application/json' },
                }).then(() => {
                    alert.success('Section deactivated successfully!');
                    fetchSections(pagination.value?.current_page || 1);
                }).catch(() => {
                    alert.error('Failed to deactivate section. Please try again.');
                });
            }
        });
};

const activateSection = (section: any) => {
    axios.patch(`/settings/sections/${section.id}/activate`, {}, {
        headers: { Accept: 'application/json' },
    }).then(() => {
        alert.success('Section activated successfully!');
        fetchSections(pagination.value?.current_page || 1);
    }).catch(() => {
        alert.error('Failed to activate section. Please try again.');
    });
};

const deleteSection = (section: any) => {
    alert.confirm(
        `Are you sure you want to delete "${section.name}"?`,
        'Delete Section',
    ).then((result) => {
        if (result.isConfirmed) {
            axios.delete(`/settings/sections/${section.id}`, {
                headers: { Accept: 'application/json' },
            }).then(() => {
                alert.success('Section deleted successfully!');
                fetchSections(pagination.value?.current_page || 1);
            }).catch(() => {
                alert.error('Failed to delete section. Please try again.');
            });
        }
    });
};

const handleSaved = () => {
    fetchSections(pagination.value?.current_page || 1);
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap gap-2 justify-between items-center">
            <div class="flex gap-2">
                <select v-model="classFilter" class="rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm min-h-10 w-40">
                    <option value="">All Classes</option>
                    <option v-for="schoolClass in schoolClasses" :key="schoolClass.id" :value="schoolClass.id">
                        {{ schoolClass.name }}
                    </option>
                </select>
                <select v-model="statusFilter" class="rounded-md border border-border bg-card text-foreground px-3 py-2 text-sm min-h-10 w-32">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="flex gap-2">
                <SectionForm :school-classes="schoolClasses" @saved="handleSaved" />
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
                                Section Name
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Class
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Code
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
                            v-for="(section, index) in sectionsData"
                            :key="section.id"
                            class="transition-colors hover:bg-accent"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-muted-foreground">
                                    {{ getRowNumber(index as number) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-foreground">
                                    {{ section.name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-muted-foreground">
                                    {{ section.school_class?.name || EMPTY_VALUE }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-muted-foreground">
                                    {{ section.code || EMPTY_VALUE }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs truncate text-sm text-muted-foreground">
                                    {{ section.description || EMPTY_VALUE }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                        section.is_active
                                            ? 'bg-success/10 text-success'
                                            : 'bg-destructive/10 text-destructive',
                                    ]"
                                >
                                    {{ section.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <SectionForm
                                        :section="section"
                                        :school-classes="schoolClasses"
                                        trigger="Edit"
                                        variant="outline"
                                        size="sm"
                                        class="contents"
                                        @saved="handleSaved"
                                    >
                                        <Icon icon="edit" class="mr-1" />Edit
                                    </SectionForm>
                                    <Button
                                        v-if="section.is_active"
                                        variant="outline"
                                        size="sm"
                                        :class="stateButtonClass"
                                        @click="inactivateSection(section)"
                                    >
                                        <Icon icon="pause" class="mr-1" />Inactive
                                    </Button>
                                    <Button
                                        v-else
                                        variant="outline"
                                        size="sm"
                                        :class="section.is_active ? tableActionButtonClass.deactivate : tableActionButtonClass.activate"
                                        @click="activateSection(section)"
                                    >
                                        <Icon icon="check" class="mr-1" />Activate
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        :class="tableActionButtonClass.delete"
                                        @click="deleteSection(section)"
                                    >
                                        <Icon icon="trash" class="mr-1" />Delete
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
                    :key="`${link.label}-${link.url || 'disabled'}`"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    :disabled="!link.url"
                    @click="link.url ? fetchSections(getPageFromUrl(link.url) || 1) : null"
                >
                    <span v-html="link.label"></span>
                </Button>
            </div>
        </div>
    </div>
</template>
