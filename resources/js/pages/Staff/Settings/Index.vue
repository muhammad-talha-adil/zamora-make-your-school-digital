<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { reactive, ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { alert } from '@/utils';
import type { BreadcrumbItem } from '@/types';

interface Lookup {
    id: number;
    name: string;
    description?: string | null;
    is_active: boolean;
}

interface Props {
    departments: Lookup[];
    designations: Lookup[];
    documentTypes: Lookup[];
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Staff', href: route('staff.index') },
    { title: 'Settings', href: route('staff.settings.page') },
];

const selectClass = 'w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground';
const textareaClass = 'min-h-20 w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground';

type TabKey = 'departments' | 'designations' | 'documentTypes';
const activeTab = ref<TabKey>('departments');

const tabs: { key: TabKey; label: string }[] = [
    { key: 'departments', label: 'Departments' },
    { key: 'designations', label: 'Designations' },
    { key: 'documentTypes', label: 'Document Types' },
];

const departmentForm = reactive({ id: null as number | null, name: '', description: '', is_active: true });
const designationForm = reactive({ id: null as number | null, name: '', description: '', is_active: true });
const documentTypeForm = reactive({ id: null as number | null, name: '', is_active: true });

const resetDepartmentForm = () => {
    departmentForm.id = null;
    departmentForm.name = '';
    departmentForm.description = '';
    departmentForm.is_active = true;
};

const resetDesignationForm = () => {
    designationForm.id = null;
    designationForm.name = '';
    designationForm.description = '';
    designationForm.is_active = true;
};

const resetDocumentTypeForm = () => {
    documentTypeForm.id = null;
    documentTypeForm.name = '';
    documentTypeForm.is_active = true;
};

const editDepartment = (department: Lookup) => {
    departmentForm.id = department.id;
    departmentForm.name = department.name;
    departmentForm.description = department.description ?? '';
    departmentForm.is_active = department.is_active;
};

const editDesignation = (designation: Lookup) => {
    designationForm.id = designation.id;
    designationForm.name = designation.name;
    designationForm.description = designation.description ?? '';
    designationForm.is_active = designation.is_active;
};

const editDocumentType = (type: Lookup) => {
    documentTypeForm.id = type.id;
    documentTypeForm.name = type.name;
    documentTypeForm.is_active = type.is_active;
};

const submitDepartment = async () => {
    try {
        if (departmentForm.id) {
            await axios.put(route('staff.departments.update', departmentForm.id), departmentForm);
        } else {
            await axios.post(route('staff.departments.store'), departmentForm);
        }
        alert.success('Department saved.');
        resetDepartmentForm();
        router.reload({ only: ['departments'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to save department.');
    }
};

const submitDesignation = async () => {
    try {
        if (designationForm.id) {
            await axios.put(route('staff.designations.update', designationForm.id), designationForm);
        } else {
            await axios.post(route('staff.designations.store'), designationForm);
        }
        alert.success('Designation saved.');
        resetDesignationForm();
        router.reload({ only: ['designations'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to save designation.');
    }
};

const submitDocumentType = async () => {
    try {
        if (documentTypeForm.id) {
            await axios.put(route('staff.document-types.update', documentTypeForm.id), documentTypeForm);
        } else {
            await axios.post(route('staff.document-types.store'), documentTypeForm);
        }
        alert.success('Document type saved.');
        resetDocumentTypeForm();
        router.reload({ only: ['documentTypes'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to save document type.');
    }
};

const deactivateDocumentType = async (type: Lookup) => {
    const result = await alert.confirm(
        `Deactivate "${type.name}"? It stays on any document already filed under it.`,
        'Deactivate Document Type',
        'Deactivate',
    );

    if (!result.isConfirmed) {
        return;
    }

    try {
        await axios.delete(route('staff.document-types.destroy', type.id));
        alert.success('Document type deactivated.');
        router.reload({ only: ['documentTypes'] });
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to deactivate document type.');
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Staff Settings" />

        <div class="space-y-6 p-4 md:p-6">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Staff Settings</h1>
                <p class="mt-1 text-sm text-muted-foreground">Departments, designations and document types the school maintains.</p>
            </div>

            <div class="flex gap-2 border-b border-border">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="px-4 py-2 text-sm font-medium border-b-2 -mb-px"
                    :class="activeTab === tab.key ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- Departments -->
            <div v-if="activeTab === 'departments'" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-foreground">Departments</h2>
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <Input v-model="departmentForm.name" placeholder="Department name" />
                        <textarea v-model="departmentForm.description" :class="textareaClass" placeholder="Description" />
                        <div class="flex gap-2">
                            <Button size="sm" @click="submitDepartment">{{ departmentForm.id ? 'Update' : 'Add' }}</Button>
                            <Button v-if="departmentForm.id" size="sm" variant="outline" @click="resetDepartmentForm">Cancel</Button>
                        </div>
                    </div>
                    <ul class="max-h-72 space-y-1 overflow-y-auto text-sm">
                        <li v-for="d in props.departments" :key="d.id" class="flex items-center justify-between rounded border border-border px-3 py-2">
                            <div>
                                <span class="text-foreground">{{ d.name }}</span>
                                <span v-if="!d.is_active" class="ml-2 text-xs text-muted-foreground">(inactive)</span>
                            </div>
                            <button type="button" class="text-xs text-primary hover:underline" @click="editDepartment(d)">Edit</button>
                        </li>
                        <li v-if="props.departments.length === 0" class="text-center text-muted-foreground py-4">No departments yet.</li>
                    </ul>
                </div>
            </div>

            <!-- Designations -->
            <div v-if="activeTab === 'designations'" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-foreground">Designations</h2>
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <Input v-model="designationForm.name" placeholder="Designation name" />
                        <textarea v-model="designationForm.description" :class="textareaClass" placeholder="Description" />
                        <div class="flex gap-2">
                            <Button size="sm" @click="submitDesignation">{{ designationForm.id ? 'Update' : 'Add' }}</Button>
                            <Button v-if="designationForm.id" size="sm" variant="outline" @click="resetDesignationForm">Cancel</Button>
                        </div>
                    </div>
                    <ul class="max-h-72 space-y-1 overflow-y-auto text-sm">
                        <li v-for="d in props.designations" :key="d.id" class="flex items-center justify-between rounded border border-border px-3 py-2">
                            <div>
                                <span class="text-foreground">{{ d.name }}</span>
                                <span v-if="!d.is_active" class="ml-2 text-xs text-muted-foreground">(inactive)</span>
                            </div>
                            <button type="button" class="text-xs text-primary hover:underline" @click="editDesignation(d)">Edit</button>
                        </li>
                        <li v-if="props.designations.length === 0" class="text-center text-muted-foreground py-4">No designations yet.</li>
                    </ul>
                </div>
            </div>

            <!-- Document Types -->
            <div v-if="activeTab === 'documentTypes'" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-foreground">Document Types</h2>
                <p class="mb-4 text-sm text-muted-foreground">The kinds of paper a school files against a member of staff — CNIC, degree, contract, and so on.</p>
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="space-y-2">
                        <Input v-model="documentTypeForm.name" placeholder="e.g. Experience Letter" />
                        <div class="flex gap-2">
                            <Button size="sm" @click="submitDocumentType">{{ documentTypeForm.id ? 'Update' : 'Add' }}</Button>
                            <Button v-if="documentTypeForm.id" size="sm" variant="outline" @click="resetDocumentTypeForm">Cancel</Button>
                        </div>
                    </div>
                    <ul class="max-h-72 space-y-1 overflow-y-auto text-sm">
                        <li v-for="type in props.documentTypes" :key="type.id" class="flex items-center justify-between rounded border border-border px-3 py-2">
                            <div>
                                <span class="text-foreground">{{ type.name }}</span>
                                <span v-if="!type.is_active" class="ml-2 text-xs text-muted-foreground">(inactive)</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="button" class="text-xs text-primary hover:underline" @click="editDocumentType(type)">Edit</button>
                                <button type="button" class="text-xs text-destructive hover:underline" @click="deactivateDocumentType(type)">
                                    <Icon icon="trash-2" class="h-3 w-3 inline" />
                                    Deactivate
                                </button>
                            </div>
                        </li>
                        <li v-if="props.documentTypes.length === 0" class="text-center text-muted-foreground py-4">No document types yet.</li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
