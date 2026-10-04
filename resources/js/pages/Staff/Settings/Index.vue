<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import RowAction from '@/components/tables/RowAction.vue';
import RowActions from '@/components/tables/RowActions.vue';
import StatusToggle from '@/components/tables/StatusToggle.vue';
import TablePagination from '@/components/tables/TablePagination.vue';
import { alert } from '@/utils';
import type { BreadcrumbItem } from '@/types';

interface Lookup {
    id: number;
    name: string;
    description?: string | null;
    role?: string | null;
    is_required?: boolean;
    is_active: boolean;
}

interface RoleOption {
    id: number;
    name: string;
    label?: string | null;
}

interface Props {
    departments: Lookup[];
    designations: Lookup[];
    documentTypes: Lookup[];
    roles: RoleOption[];
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Staff', href: route('staff.index') },
    { title: 'Settings', href: route('staff.settings.page') },
];

type TabKey = 'designations' | 'documentTypes';
const activeTab = ref<TabKey>('designations');

const tabs: { key: TabKey; label: string }[] = [
    { key: 'designations', label: 'Designations' },
    { key: 'documentTypes', label: 'Document Types' },
];

/** Reloads just the lookup props after a create/update/status change. */
const reload = (only: string[]): void => {
    router.reload({ only });
};

// ---------------------------------------------------------------- pagination
const perPageOptions = [
    { id: 10, name: '10' },
    { id: 25, name: '25' },
    { id: 50, name: '50' },
    { id: 100, name: '100' },
];

function usePagedList(source: () => Lookup[]) {
    const page = ref(1);
    const perPage = ref(10);

    const total = computed(() => source().length);
    const lastPage = computed(() => Math.max(1, Math.ceil(total.value / perPage.value)));

    const items = computed(() => {
        const start = (page.value - 1) * perPage.value;
        return source().slice(start, start + perPage.value);
    });

    const pagination = computed(() => {
        const from = total.value === 0 ? 0 : (page.value - 1) * perPage.value + 1;
        const to = Math.min(page.value * perPage.value, total.value);

        return {
            data: items.value,
            from,
            to,
            total: total.value,
            current_page: page.value,
            last_page: lastPage.value,
            per_page: perPage.value,
            links: Array.from({ length: lastPage.value }, (_, i) => ({
                label: String(i + 1),
                url: '#',
                active: i + 1 === page.value,
            })),
        };
    });

    const goToPage = (p: number) => {
        page.value = p;
    };

    return { page, perPage, items, pagination, goToPage };
}

const designationsList = usePagedList(() => props.designations);
const documentTypesList = usePagedList(() => props.documentTypes);

// --------------------------------------------------------------- designation
const showDesignationModal = ref(false);
const editingDesignation = ref<Lookup | null>(null);
const designationForm = ref({ name: '', description: '', role: '' as string | null, is_active: true });
const designationErrors = ref<Record<string, string>>({});

const openCreateDesignation = () => {
    editingDesignation.value = null;
    designationForm.value = { name: '', description: '', role: '', is_active: true };
    designationErrors.value = {};
    showDesignationModal.value = true;
};

const openEditDesignation = (designation: Lookup) => {
    editingDesignation.value = designation;
    designationForm.value = {
        name: designation.name,
        description: designation.description ?? '',
        role: designation.role ?? '',
        is_active: designation.is_active,
    };
    designationErrors.value = {};
    showDesignationModal.value = true;
};

const closeDesignationModal = () => {
    showDesignationModal.value = false;
    editingDesignation.value = null;
};

const submitDesignation = async () => {
    designationErrors.value = {};
    const payload = { ...designationForm.value, role: designationForm.value.role || null };

    try {
        if (editingDesignation.value) {
            await axios.put(route('staff.designations.update', editingDesignation.value.id), payload);
        } else {
            await axios.post(route('staff.designations.store'), payload);
        }
        alert.success('Designation saved.');
        closeDesignationModal();
        reload(['designations']);
    } catch (error: any) {
        if (error?.response?.status === 422) {
            const errs = error.response.data.errors ?? {};
            designationErrors.value = Object.fromEntries(Object.entries(errs).map(([k, v]) => [k, (v as string[])[0]]));
        } else {
            alert.error(error?.response?.data?.message || 'Failed to save designation.');
        }
    }
};

const toggleDesignationStatus = async (designation: Lookup) => {
    const nextActive = !designation.is_active;

    try {
        await axios.put(route('staff.designations.update', designation.id), {
            name: designation.name,
            description: designation.description ?? '',
            role: designation.role ?? null,
            is_active: nextActive,
        });
        alert.success(nextActive ? 'Designation activated.' : 'Designation deactivated.');
        reload(['designations']);
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to update designation status.');
    }
};

// ------------------------------------------------------------- document type
const showDocumentTypeModal = ref(false);
const editingDocumentType = ref<Lookup | null>(null);
const documentTypeForm = ref({ name: '', is_required: false, is_active: true });
const documentTypeErrors = ref<Record<string, string>>({});

const openCreateDocumentType = () => {
    editingDocumentType.value = null;
    documentTypeForm.value = { name: '', is_required: false, is_active: true };
    documentTypeErrors.value = {};
    showDocumentTypeModal.value = true;
};

const openEditDocumentType = (type: Lookup) => {
    editingDocumentType.value = type;
    documentTypeForm.value = { name: type.name, is_required: type.is_required ?? false, is_active: type.is_active };
    documentTypeErrors.value = {};
    showDocumentTypeModal.value = true;
};

const closeDocumentTypeModal = () => {
    showDocumentTypeModal.value = false;
    editingDocumentType.value = null;
};

const submitDocumentType = async () => {
    documentTypeErrors.value = {};

    try {
        if (editingDocumentType.value) {
            await axios.put(route('staff.document-types.update', editingDocumentType.value.id), documentTypeForm.value);
        } else {
            await axios.post(route('staff.document-types.store'), documentTypeForm.value);
        }
        alert.success('Document type saved.');
        closeDocumentTypeModal();
        reload(['documentTypes']);
    } catch (error: any) {
        if (error?.response?.status === 422) {
            const errs = error.response.data.errors ?? {};
            documentTypeErrors.value = Object.fromEntries(Object.entries(errs).map(([k, v]) => [k, (v as string[])[0]]));
        } else {
            alert.error(error?.response?.data?.message || 'Failed to save document type.');
        }
    }
};

const activateDocumentType = async (type: Lookup) => {
    try {
        await axios.put(route('staff.document-types.update', type.id), {
            name: type.name,
            is_required: type.is_required ?? false,
            is_active: true,
        });
        alert.success('Document type activated.');
        reload(['documentTypes']);
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to activate document type.');
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
        reload(['documentTypes']);
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
                <p class="mt-1 text-sm text-muted-foreground">Designations and document types the school maintains.</p>
            </div>

            <div class="flex gap-2 border-b border-border">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="border-b-2 -mb-px px-4 py-2 text-sm font-medium"
                    :class="activeTab === tab.key ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- Designations -->
            <div v-if="activeTab === 'designations'" class="space-y-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-foreground">Designations</h2>
                        <p class="mt-1 text-xs text-muted-foreground">The job titles staff are assigned, optionally tied to a system role.</p>
                    </div>
                    <Button @click="openCreateDesignation">
                        <Icon icon="plus" class="mr-2 h-4 w-4" />
                        Add Designation
                    </Button>
                </div>

                <!-- Mobile Card View -->
                <div class="block lg:hidden space-y-3">
                    <div
                        v-for="designation in designationsList.items.value"
                        :key="designation.id"
                        class="bg-card rounded-lg border border-border p-4 space-y-2"
                    >
                        <div class="flex flex-wrap gap-2 justify-between items-start">
                            <div>
                                <div class="font-medium text-foreground">{{ designation.name }}</div>
                                <div v-if="designation.role" class="text-xs text-muted-foreground">Role: {{ designation.role }}</div>
                            </div>
                            <StatusToggle :active="designation.is_active" @toggle="toggleDesignationStatus(designation)" />
                        </div>
                        <p v-if="designation.description" class="text-sm text-muted-foreground pt-2 border-t border-border">
                            {{ designation.description }}
                        </p>
                        <div class="flex justify-end pt-2">
                            <RowActions>
                                <RowAction kind="edit" @click="openEditDesignation(designation)" />
                            </RowActions>
                        </div>
                    </div>
                    <div v-if="designationsList.items.value.length === 0" class="text-center py-8 text-muted-foreground">
                        No designations yet.
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">#</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Name</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Description</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">System Role</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Status</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-muted-foreground uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-for="(designation, index) in designationsList.items.value" :key="designation.id" class="transition-colors hover:bg-accent">
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ (designationsList.pagination.value.from || 1) + index }}
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-foreground">{{ designation.name }}</td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground max-w-xs truncate">{{ designation.description || '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">{{ designation.role || '—' }}</td>
                                    <td class="px-4 py-3">
                                        <StatusToggle :active="designation.is_active" @toggle="toggleDesignationStatus(designation)" />
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <RowActions>
                                            <RowAction kind="edit" @click="openEditDesignation(designation)" />
                                        </RowActions>
                                    </td>
                                </tr>
                                <tr v-if="designationsList.items.value.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">No designations yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <TablePagination
                    v-model:per-page="designationsList.perPage.value"
                    :pagination="designationsList.pagination.value"
                    :per-page-options="perPageOptions"
                    @page="designationsList.goToPage"
                />
            </div>

            <!-- Document Types -->
            <div v-if="activeTab === 'documentTypes'" class="space-y-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-foreground">Document Types</h2>
                        <p class="mt-1 text-xs text-muted-foreground">
                            The kinds of paper a school files against a member of staff — CNIC, degree, contract, and so on.
                        </p>
                    </div>
                    <Button @click="openCreateDocumentType">
                        <Icon icon="plus" class="mr-2 h-4 w-4" />
                        Add Document Type
                    </Button>
                </div>

                <!-- Mobile Card View -->
                <div class="block lg:hidden space-y-3">
                    <div
                        v-for="type in documentTypesList.items.value"
                        :key="type.id"
                        class="bg-card rounded-lg border border-border p-4 space-y-2"
                    >
                        <div class="flex flex-wrap gap-2 justify-between items-start">
                            <div>
                                <div class="font-medium text-foreground">{{ type.name }}</div>
                                <span :class="['text-xs font-medium', type.is_required ? 'text-warning' : 'text-muted-foreground']">
                                    {{ type.is_required ? 'Required' : 'Optional' }}
                                </span>
                            </div>
                            <StatusToggle
                                :active="type.is_active"
                                @toggle="type.is_active ? deactivateDocumentType(type) : activateDocumentType(type)"
                            />
                        </div>
                        <div class="flex justify-end pt-2">
                            <RowActions>
                                <RowAction kind="edit" @click="openEditDocumentType(type)" />
                                <RowAction kind="delete" @click="deactivateDocumentType(type)" />
                            </RowActions>
                        </div>
                    </div>
                    <div v-if="documentTypesList.items.value.length === 0" class="text-center py-8 text-muted-foreground">
                        No document types yet.
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">#</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Name</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Required</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Status</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-muted-foreground uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border bg-card">
                                <tr v-for="(type, index) in documentTypesList.items.value" :key="type.id" class="transition-colors hover:bg-accent">
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ (documentTypesList.pagination.value.from || 1) + index }}
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-foreground">{{ type.name }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', type.is_required ? 'bg-warning/10 text-warning' : 'bg-muted text-muted-foreground']">
                                            {{ type.is_required ? 'Required' : 'Optional' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <StatusToggle
                                            :active="type.is_active"
                                            @toggle="type.is_active ? deactivateDocumentType(type) : activateDocumentType(type)"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <RowActions>
                                            <RowAction kind="edit" @click="openEditDocumentType(type)" />
                                            <RowAction kind="delete" @click="deactivateDocumentType(type)" />
                                        </RowActions>
                                    </td>
                                </tr>
                                <tr v-if="documentTypesList.items.value.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">No document types yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <TablePagination
                    v-model:per-page="documentTypesList.perPage.value"
                    :pagination="documentTypesList.pagination.value"
                    :per-page-options="perPageOptions"
                    @page="documentTypesList.goToPage"
                />
            </div>
        </div>

        <!-- Designation Create/Edit Modal -->
        <Dialog v-model:open="showDesignationModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editingDesignation ? 'Edit Designation' : 'Add Designation' }}</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitDesignation">
                    <div class="space-y-2">
                        <Label for="designation-name">Name <span class="text-destructive">*</span></Label>
                        <Input id="designation-name" v-model="designationForm.name" placeholder="Designation name" :class="designationErrors.name ? 'border-destructive' : ''" />
                        <p v-if="designationErrors.name" class="text-xs text-destructive">{{ designationErrors.name }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="designation-description">Description</Label>
                        <textarea
                            id="designation-description"
                            v-model="designationForm.description"
                            class="min-h-20 w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground"
                            placeholder="Description"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="designation-role">System Role</Label>
                        <select id="designation-role" v-model="designationForm.role" class="w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground">
                            <option :value="''">— none —</option>
                            <option v-for="r in props.roles" :key="r.id" :value="r.name">{{ r.label || r.name }}</option>
                        </select>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="closeDesignationModal">Cancel</Button>
                        <Button type="submit" :disabled="!designationForm.name.trim()">{{ editingDesignation ? 'Save Changes' : 'Add' }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Document Type Create/Edit Modal -->
        <Dialog v-model:open="showDocumentTypeModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editingDocumentType ? 'Edit Document Type' : 'Add Document Type' }}</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="submitDocumentType">
                    <div class="space-y-2">
                        <Label for="document-type-name">Name <span class="text-destructive">*</span></Label>
                        <Input id="document-type-name" v-model="documentTypeForm.name" placeholder="e.g. Experience Letter" :class="documentTypeErrors.name ? 'border-destructive' : ''" />
                        <p v-if="documentTypeErrors.name" class="text-xs text-destructive">{{ documentTypeErrors.name }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="document-type-required">Required</Label>
                        <select id="document-type-required" v-model="documentTypeForm.is_required" class="w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground">
                            <option :value="false">Optional</option>
                            <option :value="true">Required</option>
                        </select>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="closeDocumentTypeModal">Cancel</Button>
                        <Button type="submit" :disabled="!documentTypeForm.name.trim()">{{ editingDocumentType ? 'Save Changes' : 'Add' }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
