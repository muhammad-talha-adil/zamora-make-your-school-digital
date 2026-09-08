<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Student Details" />

        <div class="space-y-6 p-4 md:p-6 max-w-5xl mx-auto">
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
                <div class="flex gap-2">
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
                    <div class="bg-card rounded-lg border border-border p-6 text-center">
                        <!-- Student Photo -->
                        <div class="mb-4">
                            <div v-if="student?.image" class="h-32 w-32 mx-auto rounded-full overflow-hidden border-4 border-border">
                                <img
                                    :src="student.image"
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

                        <!-- Status Badge -->
                        <div class="mt-4">
                            <span
                                :class="[
                                    'px-3 py-1 text-sm font-medium rounded-full',
                                    student?.student_status?.name === 'Active'
                                        ? 'bg-success/10 text-success'
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
                                    <Icon icon="calendar" class="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <p class="text-xs text-muted-foreground">Date of Birth</p>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ formatDate(student?.dob) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Icon icon="user" class="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <p class="text-xs text-muted-foreground">Gender</p>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ student?.gender?.name || '-' }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="student?.b_form" class="flex items-center gap-3">
                                    <Icon icon="id-card" class="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <p class="text-xs text-muted-foreground">B-Form</p>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ student?.b_form }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Icon icon="building" class="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <p class="text-xs text-muted-foreground">Campus</p>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ currentEnrollment?.campus?.name || '-' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Icon icon="book" class="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <p class="text-xs text-muted-foreground">Class / Section</p>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ currentEnrollment?.class?.name || '-' }} - {{ currentEnrollment?.section?.name || '-' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Icon icon="calendar" class="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <p class="text-xs text-muted-foreground">Academic Session</p>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ currentEnrollment?.session?.name || '-' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Icon icon="dollar-sign" class="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <p class="text-xs text-muted-foreground">Monthly Fee</p>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ formatCurrency(currentEnrollment?.monthly_fee) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Icon icon="dollar-sign" class="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <p class="text-xs text-muted-foreground">Annual Fee</p>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ formatCurrency(currentEnrollment?.annual_fee) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Icon icon="calendar" class="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <p class="text-xs text-muted-foreground">Admission Date</p>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ formatDate(student?.admission_date) }}
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
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';

interface GuardianData {
    id: number;
    name: string;
    phone: string;
    email: string;
    cnic: string;
    pivot?: {
        relation_id: number;
    };
}

interface Props {
    student: {
        id: number;
        admission_no: string;
        registration_no: string;
        dob: string;
        gender_id: number;
        b_form: string;
        campus_id: number;
        session_id: number;
        class_id: number;
        section_id: number;
        student_status_id: number;
        admission_date: string;
        description: string;
        image: string | null;
        user?: {
            name: string;
        };
        campus?: {
            name: string;
        };
        class?: {
            name: string;
        };
        section?: {
            name: string;
        };
        gender?: {
            name: string;
        };
        session?: {
            name: string;
        };
        student_status?: {
            name: string;
        };
        guardians?: GuardianData[];
        enrollment_records?: Array<{
            id: number;
            campus?: {
                name: string;
            };
            class?: {
                name: string;
            };
            section?: {
                name: string;
            };
            session?: {
                name: string;
            };
            monthly_fee: number;
            annual_fee: number;
            admission_date: string;
            leave_date: string | null;
        }>;
    };
    relations: Array<{
        id: number;
        name: string;
    }>;
}

const props = defineProps<Props>();

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
        const relation = props.relations.find(r => r.id === relationId);
        return relation?.name?.toLowerCase() === relationName;
    });
};

// Get other guardian (not father or mother)
const otherGuardian = computed(() => {
    const guardians = props.student?.guardians;
    if (!guardians) return undefined;
    
    return guardians.find(g => {
        const relationId = g.pivot?.relation_id;
        const relation = props.relations.find(r => r.id === relationId);
        const relationName = relation?.name?.toLowerCase() || '';
        return relationName !== 'father' && relationName !== 'mother';
    });
});

// Get relation name for a guardian
const getGuardianRelation = (guardian: GuardianData): string => {
    const relationId = guardian.pivot?.relation_id;
    const relation = props.relations.find(r => r.id === relationId);
    return relation?.name || '-';
};
</script>
