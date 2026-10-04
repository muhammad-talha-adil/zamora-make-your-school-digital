<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Student Details" />

        <div class="space-y-6 p-4 md:p-6 max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">
                        Student Details
                    </h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                        View complete student and guardian information
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" @click="router.visit(route('students.print', student?.id))">
                        <Icon icon="printer" class="mr-1" />
                        Print
                    </Button>
                    <Button variant="outline" @click="router.visit(route('students.id-cards', { student_ids: [student?.id] }))">
                        <Icon icon="id-card" class="mr-1" />
                        ID Card
                    </Button>
                    <Button variant="outline" @click="router.visit(route('students.edit', student?.id))">
                        <Icon icon="edit" class="mr-1" />
                        Edit
                    </Button>
                    <Button variant="outline" @click="router.visit(route('students.index'))">
                        <Icon icon="arrow-left" class="mr-1" />
                        Back to List
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Student Profile Card -->
                <div class="lg:col-span-1">
                    <div class="bg-card rounded-lg border border-border p-6 text-center sticky top-4">
                        <!-- Student Photo -->
                        <div class="mb-4">
                            <div v-if="student?.image_url" class="h-32 w-32 mx-auto rounded-full overflow-hidden border-4 border-border">
                                <img
                                    :src="student.image_url"
                                    alt="Student Photo"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div v-else class="h-32 w-32 mx-auto rounded-full bg-primary/10 flex items-center justify-center border-4 border-border">
                                <span class="text-4xl font-bold text-primary">
                                    {{ student?.user?.name?.charAt(0) || 'S' }}
                                </span>
                            </div>
                        </div>

                        <!-- Student Name -->
                        <h2 class="text-xl font-bold text-foreground">
                            {{ student?.user?.name || 'N/A' }}
                        </h2>
                        <p class="text-muted-foreground text-sm mt-1">
                            {{ student?.registration_no }}
                        </p>
                        <p class="text-muted-foreground text-xs mt-0.5">
                            Adm No: {{ student?.admission_no || '-' }}
                        </p>

                        <!-- Status Badge -->
                        <div class="mt-4">
                            <span
                                :class="[
                                    'px-3 py-1 text-sm font-medium rounded-full',
                                    student?.student_status?.name === 'Active'
                                        ? 'bg-success/10 text-success dark:bg-success/20'
                                        : 'bg-muted text-foreground'
                                ]"
                            >
                                {{ student?.student_status?.name || 'Unknown' }}
                            </span>
                        </div>

                        <!-- Quick Info -->
                        <div class="mt-6 pt-6 border-t border-border text-left">
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <Icon icon="calendar" class="h-5 w-5 text-muted-foreground shrink-0" />
                                    <div>
                                        <p class="text-xs text-muted-foreground">Date of Birth</p>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ formatDate(student?.dob) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Icon icon="user" class="h-5 w-5 text-muted-foreground shrink-0" />
                                    <div>
                                        <p class="text-xs text-muted-foreground">Gender</p>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ student?.gender?.name || '-' }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="student?.b_form" class="flex items-center gap-3">
                                    <Icon icon="id-card" class="h-5 w-5 text-muted-foreground shrink-0" />
                                    <div>
                                        <p class="text-xs text-muted-foreground">B-Form</p>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ student?.b_form }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Icon icon="building" class="h-5 w-5 text-muted-foreground shrink-0" />
                                    <div>
                                        <p class="text-xs text-muted-foreground">Campus</p>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ currentEnrollment?.campus?.name || '-' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Icon icon="book" class="h-5 w-5 text-muted-foreground shrink-0" />
                                    <div>
                                        <p class="text-xs text-muted-foreground">Class / Section</p>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ currentEnrollment?.class?.name || '-' }} - {{ currentEnrollment?.section?.name || '-' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Icon icon="calendar" class="h-5 w-5 text-muted-foreground shrink-0" />
                                    <div>
                                        <p class="text-xs text-muted-foreground">Academic Session</p>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ currentEnrollment?.session?.name || '-' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Icon icon="dollar-sign" class="h-5 w-5 text-muted-foreground shrink-0" />
                                    <div>
                                        <p class="text-xs text-muted-foreground">Monthly Fee</p>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ formatCurrency(currentEnrollment?.monthly_fee) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Icon icon="dollar-sign" class="h-5 w-5 text-muted-foreground shrink-0" />
                                    <div>
                                        <p class="text-xs text-muted-foreground">Annual Fee</p>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ formatCurrency(currentEnrollment?.annual_fee) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Icon icon="calendar" class="h-5 w-5 text-muted-foreground shrink-0" />
                                    <div>
                                        <p class="text-xs text-muted-foreground">Admission Date</p>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ formatDate(currentEnrollment?.admission_date || student?.admission_date) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details Cards -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Father Information Card -->
                    <div class="bg-card rounded-lg border border-border p-6">
                        <h3 class="text-lg font-semibold text-foreground mb-4 flex items-center gap-2">
                            <Icon icon="user-check" class="h-5 w-5 text-primary" />
                            Father Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-muted-foreground">Name</p>
                                <p class="text-sm font-medium text-foreground">{{ getGuardianByRelation('father')?.name || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Phone</p>
                                <p class="text-sm font-medium text-foreground">{{ getGuardianByRelation('father')?.phone || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Email</p>
                                <p class="text-sm font-medium text-foreground">{{ getGuardianByRelation('father')?.email || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">CNIC</p>
                                <p class="text-sm font-medium text-foreground">{{ getGuardianByRelation('father')?.cnic || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Mother Information Card -->
                    <div class="bg-card rounded-lg border border-border p-6">
                        <h3 class="text-lg font-semibold text-foreground mb-4 flex items-center gap-2">
                            <Icon icon="user-plus" class="h-5 w-5 text-primary" />
                            Mother Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-muted-foreground">Name</p>
                                <p class="text-sm font-medium text-foreground">{{ getGuardianByRelation('mother')?.name || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Phone</p>
                                <p class="text-sm font-medium text-foreground">{{ getGuardianByRelation('mother')?.phone || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Email</p>
                                <p class="text-sm font-medium text-foreground">{{ getGuardianByRelation('mother')?.email || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">CNIC</p>
                                <p class="text-sm font-medium text-foreground">{{ getGuardianByRelation('mother')?.cnic || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Other Guardian Card -->
                    <div v-if="otherGuardian" class="bg-card rounded-lg border border-border p-6">
                        <h3 class="text-lg font-semibold text-foreground mb-4 flex items-center gap-2">
                            <Icon icon="users" class="h-5 w-5 text-primary" />
                            Other Guardian
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-muted-foreground">Name</p>
                                <p class="text-sm font-medium text-foreground">{{ otherGuardian.name || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Relation</p>
                                <p class="text-sm font-medium text-foreground">{{ getGuardianRelation(otherGuardian) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Phone</p>
                                <p class="text-sm font-medium text-foreground">{{ otherGuardian.phone || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Email</p>
                                <p class="text-sm font-medium text-foreground">{{ otherGuardian.email || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">CNIC</p>
                                <p class="text-sm font-medium text-foreground">{{ otherGuardian.cnic || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Enrollment History Card -->
                    <div v-if="student?.enrollment_records?.length" class="bg-card rounded-lg border border-border p-6">
                        <h3 class="text-lg font-semibold text-foreground mb-4 flex items-center gap-2">
                            <Icon icon="history" class="h-5 w-5 text-primary" />
                            Enrollment History
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs text-muted-foreground border-b border-border">
                                        <th class="pb-2 pr-4 font-medium">Session</th>
                                        <th class="pb-2 pr-4 font-medium">Class / Section</th>
                                        <th class="pb-2 pr-4 font-medium">Admitted</th>
                                        <th class="pb-2 font-medium">Left</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="record in student.enrollment_records"
                                        :key="record.id"
                                        class="border-b border-border last:border-0"
                                    >
                                        <td class="py-2 pr-4 text-foreground">{{ record.session?.name || '-' }}</td>
                                        <td class="py-2 pr-4 text-foreground">{{ record.class?.name || '-' }} - {{ record.section?.name || '-' }}</td>
                                        <td class="py-2 pr-4 text-foreground">{{ formatDate(record.admission_date) }}</td>
                                        <td class="py-2 text-foreground">{{ record.leave_date ? formatDate(record.leave_date) : '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Documents Card -->
                    <div class="bg-card rounded-lg border border-border p-6">
                        <h3 class="text-lg font-semibold text-foreground mb-4 flex items-center gap-2">
                            <Icon icon="file-text" class="h-5 w-5 text-primary" />
                            Documents
                        </h3>

                        <div v-if="can?.manageDocuments" class="mb-6 grid gap-3 rounded-lg border border-border p-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-muted-foreground">Document Type</label>
                                <select v-model="documentForm.student_document_type_id" class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground">
                                    <option :value="null">Select type</option>
                                    <option v-for="type in documentTypes" :key="type.id" :value="type.id">
                                        {{ type.name }}<span v-if="type.is_required"> (required)</span>
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label for="document_file" class="mb-1 block text-xs font-medium text-muted-foreground">File</label>
                                <input id="document_file" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm" @change="onDocumentFile" />
                            </div>
                            <div>
                                <label for="document_issue_date" class="mb-1 block text-xs font-medium text-muted-foreground">Issue Date</label>
                                <input id="document_issue_date" v-model="documentForm.issue_date" type="date" class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground" />
                            </div>
                            <div>
                                <label for="document_expiry_date" class="mb-1 block text-xs font-medium text-muted-foreground">Expiry Date</label>
                                <input id="document_expiry_date" v-model="documentForm.expiry_date" type="date" class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground" />
                            </div>
                            <div class="md:col-span-2">
                                <Button size="sm" @click="addDocument">File Document</Button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs text-muted-foreground border-b border-border">
                                        <th class="pb-2 pr-4 font-medium">Type</th>
                                        <th class="pb-2 pr-4 font-medium">Issue Date</th>
                                        <th class="pb-2 pr-4 font-medium">Expiry Date</th>
                                        <th class="pb-2 pr-4 font-medium">File</th>
                                        <th v-if="can?.manageDocuments" class="pb-2 font-medium text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="doc in student?.documents" :key="doc.id" class="border-b border-border last:border-0">
                                        <td class="py-2 pr-4 text-foreground">{{ doc.documentType?.name || '-' }}</td>
                                        <td class="py-2 pr-4 text-foreground">{{ formatDate(doc.issue_date) }}</td>
                                        <td class="py-2 pr-4 text-foreground">{{ doc.expiry_date ? formatDate(doc.expiry_date) : '—' }}</td>
                                        <td class="py-2 pr-4">
                                            <a v-if="doc.path" :href="`/storage/${doc.path}`" target="_blank" class="text-primary hover:underline">View</a>
                                            <span v-else class="text-muted-foreground">—</span>
                                        </td>
                                        <td v-if="can?.manageDocuments" class="py-2 text-right">
                                            <button type="button" class="text-xs text-destructive hover:underline" @click="removeDocument(doc.id)">Remove</button>
                                        </td>
                                    </tr>
                                    <tr v-if="!student?.documents?.length">
                                        <td :colspan="can?.manageDocuments ? 5 : 4" class="py-6 text-center text-muted-foreground">No documents on file.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Description Card -->
                    <div v-if="student?.description" class="bg-card rounded-lg border border-border p-6">
                        <h3 class="text-lg font-semibold text-foreground mb-4 flex items-center gap-2">
                            <Icon icon="file-text" class="h-5 w-5 text-primary" />
                            Description
                        </h3>
                        <p class="text-sm text-muted-foreground whitespace-pre-wrap">
                            {{ student.description }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { alert } from '@/utils';

interface GuardianData {
    id: number;
    name: string;
    phone: string;
    email: string;
    cnic: string;
    pivot?: {
        relation_id: number;
        is_primary?: boolean;
    };
}

interface EnrollmentRecord {
    id: number;
    campus?: { name: string } | null;
    class?: { name: string } | null;
    section?: { name: string } | null;
    session?: { name: string } | null;
    monthly_fee: number;
    annual_fee: number;
    admission_date: string | null;
    leave_date: string | null;
}

interface DocumentType {
    id: number;
    name: string;
    is_required: boolean;
}

interface StudentDocumentRow {
    id: number;
    student_document_type_id: number;
    documentType?: { id: number; name: string } | null;
    issue_date?: string | null;
    expiry_date?: string | null;
    path?: string | null;
    uploadedBy?: { name: string } | null;
}

interface Props {
    student: {
        id: number;
        admission_no: string;
        registration_no: string;
        dob: string;
        gender_id: number;
        b_form: string;
        student_status_id: number;
        admission_date: string;
        description: string;
        image: string | null;
        image_url?: string | null;
        user?: {
            name: string;
        };
        gender?: {
            name: string;
        };
        student_status?: {
            name: string;
        };
        guardians?: GuardianData[];
        enrollment_records?: EnrollmentRecord[];
        documents?: StudentDocumentRow[];
    };
    relations: Array<{
        id: number;
        name: string;
    }>;
    documentTypes?: DocumentType[];
    can?: {
        manageDocuments: boolean;
    };
}

const props = defineProps<Props>();
const documentTypes = computed(() => props.documentTypes ?? []);
const can = computed(() => props.can);

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Students',
        href: '/students',
    },
    {
        title: 'Student Details',
        href: `/students/${props.student?.id}`,
    },
];

// Helper to format dates
const formatDate = (dateString: string | undefined | null): string => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-PK', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

// Helper to format currency
const formatCurrency = (amount: number | undefined | null): string => {
    if (amount === undefined || amount === null) return '-';
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
    }).format(amount);
};

// Get current enrollment (active or most recent)
const currentEnrollment = computed(() => {
    const records = props.student?.enrollment_records;
    if (!records || records.length === 0) return null;

    // First try to find active enrollment (no leave_date)
    const activeEnrollment = records.find(r => r.leave_date === null);
    if (activeEnrollment) return activeEnrollment;

    // Otherwise return most recent
    return records[0];
});

// Helper to get guardian by relation name
const getGuardianByRelation = (relationName: string): GuardianData | undefined => {
    const guardians = props.student?.guardians;
    if (!guardians) return undefined;

    return guardians.find(g => {
        const relationId = g.pivot?.relation_id;
        const relation = props.relations?.find(r => r.id === relationId);
        return relation?.name?.toLowerCase() === relationName;
    });
};

// Get other guardian (not father or mother)
const otherGuardian = computed(() => {
    const guardians = props.student?.guardians;
    if (!guardians) return undefined;

    return guardians.find(g => {
        const relationId = g.pivot?.relation_id;
        const relation = props.relations?.find(r => r.id === relationId);
        const relationName = relation?.name?.toLowerCase() || '';
        return relationName !== 'father' && relationName !== 'mother';
    });
});

// Get relation name for a guardian
const getGuardianRelation = (guardian: GuardianData): string => {
    const relationId = guardian.pivot?.relation_id;
    const relation = props.relations?.find(r => r.id === relationId);
    return relation?.name || '-';
};

// Documents
const documentForm = reactive({
    student_document_type_id: null as number | null,
    issue_date: '',
    expiry_date: '',
    file: null as File | null,
});

const onDocumentFile = (event: Event) => {
    const target = event.target as HTMLInputElement;
    documentForm.file = target.files?.[0] ?? null;
};

const resetDocumentForm = () => {
    documentForm.student_document_type_id = null;
    documentForm.issue_date = '';
    documentForm.expiry_date = '';
    documentForm.file = null;
};

const addDocument = async () => {
    if (!props.student?.id || !documentForm.student_document_type_id) {
        alert('Please select a document type.');
        return;
    }

    const formData = new FormData();
    formData.append('student_document_type_id', String(documentForm.student_document_type_id));

    if (documentForm.issue_date) {
        formData.append('issue_date', documentForm.issue_date);
    }
    if (documentForm.expiry_date) {
        formData.append('expiry_date', documentForm.expiry_date);
    }
    if (documentForm.file) {
        formData.append('file', documentForm.file);
    }

    try {
        await axios.post(route('students.documents.store', props.student.id), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        resetDocumentForm();
        router.reload({ only: ['student'] });
    } catch (error: any) {
        alert(error?.response?.data?.message || 'Failed to file document.');
    }
};

const removeDocument = async (documentId: number) => {
    if (!window.confirm('Remove this document?')) {
        return;
    }

    try {
        await axios.delete(route('students.documents.destroy', documentId));
        router.reload({ only: ['student'] });
    } catch (error: any) {
        alert(error?.response?.data?.message || 'Failed to remove document.');
    }
};
</script>
