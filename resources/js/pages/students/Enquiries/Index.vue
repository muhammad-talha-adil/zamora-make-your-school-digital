<script setup lang="ts">
/**
 * The follow-up list a school actually works from in admission season.
 *
 * A family visits, asks about a class, leaves a phone number — this is where
 * that conversation lives until it either becomes a child on the roll or the
 * family stops answering. "Admit" hands the details to the ordinary New
 * Admission form rather than creating a student here directly.
 */
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { debounce } from 'lodash';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Dialog, DialogClose, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import RowAction from '@/components/tables/RowAction.vue';
import RowActions from '@/components/tables/RowActions.vue';
import { alert } from '@/utils/alert';
import type { BreadcrumbItem } from '@/types';

interface LookupOption {
    id: number;
    name: string;
}

interface EnquiryRow {
    id: number;
    student_name: string;
    phone: string;
    dob: string | null;
    gender_id: number | null;
    guardian_name: string | null;
    email: string | null;
    address: string | null;
    campus_id: number | null;
    class_id: number | null;
    session_id: number | null;
    status: 'open' | 'contacted' | 'visited' | 'admitted' | 'closed';
    notes: string | null;
    follow_up_on: string | null;
    student_id: number | null;
    campus?: LookupOption | null;
    class?: LookupOption | null;
    session?: LookupOption | null;
    handledBy?: { id: number; name: string } | null;
    student?: { id: number; admission_no: string } | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    campuses: LookupOption[];
    classes: LookupOption[];
    sessions: LookupOption[];
    genders: LookupOption[];
    filters?: {
        status?: string;
        due?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Students', href: route('students.index') },
    { title: 'Admission Enquiries', href: route('students.enquiries.page') },
];

const textareaClass = 'min-h-20 w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground';
const selectClass = 'h-11 w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground';

/* ==================== List + filters ==================== */

const loading = ref(false);
const enquiries = ref<EnquiryRow[]>([]);
const pagination = reactive({
    links: [] as PaginationLink[],
    from: 0,
    to: 0,
    total: 0,
});

const filters = reactive({
    status: props.filters?.status ?? '',
    due: props.filters?.due === '1' || props.filters?.due === 'true',
    phone: '',
});

const statusOptions = [
    { value: 'open', label: 'Open' },
    { value: 'contacted', label: 'Contacted' },
    { value: 'visited', label: 'Visited' },
    { value: 'admitted', label: 'Admitted' },
    { value: 'closed', label: 'Closed' },
];

const statusColor = (status: string): string => {
    const colors: Record<string, string> = {
        open: 'bg-warning/10 text-warning',
        contacted: 'bg-primary/10 text-primary',
        visited: 'bg-primary/10 text-primary',
        admitted: 'bg-success/10 text-success',
        closed: 'bg-muted text-foreground',
    };
    return colors[status] || colors.open;
};

const fetchEnquiries = async (url?: string) => {
    loading.value = true;
    try {
        const response = url
            ? await axios.get(url)
            : await axios.get(route('students.enquiries.index'), {
                  params: {
                      status: filters.status || undefined,
                      due: filters.due ? 1 : undefined,
                      phone: filters.phone || undefined,
                  },
              });

        const page = response.data.data;
        enquiries.value = page.data ?? [];
        pagination.links = page.links ?? [];
        pagination.from = page.from ?? 0;
        pagination.to = page.to ?? 0;
        pagination.total = page.total ?? 0;
    } catch {
        alert.error('Failed to load enquiries.');
    } finally {
        loading.value = false;
    }
};

const debouncedFetch = debounce(() => fetchEnquiries(), 300);

watch(() => filters.status, () => fetchEnquiries());
watch(() => filters.due, () => fetchEnquiries());
watch(() => filters.phone, () => debouncedFetch());

onMounted(() => {
    fetchEnquiries();
});

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }
    return new Date(value).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

/* ==================== New / Edit dialog ==================== */

const showFormDialog = ref(false);
const formProcessing = ref(false);
const formErrors = ref<Record<string, string>>({});
const editingEnquiry = ref<EnquiryRow | null>(null);

const enquiryForm = reactive({
    student_name: '',
    phone: '',
    dob: '',
    gender_id: '' as string | number,
    guardian_name: '',
    email: '',
    address: '',
    campus_id: '' as string | number,
    class_id: '' as string | number,
    session_id: '' as string | number,
    follow_up_on: '',
    notes: '',
    status: 'open',
});

const isEditing = computed(() => editingEnquiry.value !== null);

const resetEnquiryForm = () => {
    enquiryForm.student_name = '';
    enquiryForm.phone = '';
    enquiryForm.dob = '';
    enquiryForm.gender_id = '';
    enquiryForm.guardian_name = '';
    enquiryForm.email = '';
    enquiryForm.address = '';
    enquiryForm.campus_id = '';
    enquiryForm.class_id = '';
    enquiryForm.session_id = '';
    enquiryForm.follow_up_on = '';
    enquiryForm.notes = '';
    enquiryForm.status = 'open';
};

const openNewDialog = () => {
    editingEnquiry.value = null;
    resetEnquiryForm();
    formErrors.value = {};
    showFormDialog.value = true;
};

const openEditDialog = (enquiry: EnquiryRow) => {
    editingEnquiry.value = enquiry;
    enquiryForm.student_name = enquiry.student_name;
    enquiryForm.phone = enquiry.phone;
    enquiryForm.dob = enquiry.dob ?? '';
    enquiryForm.gender_id = enquiry.gender_id ?? '';
    enquiryForm.guardian_name = enquiry.guardian_name ?? '';
    enquiryForm.email = enquiry.email ?? '';
    enquiryForm.address = enquiry.address ?? '';
    enquiryForm.campus_id = enquiry.campus_id ?? '';
    enquiryForm.class_id = enquiry.class_id ?? '';
    enquiryForm.session_id = enquiry.session_id ?? '';
    enquiryForm.follow_up_on = enquiry.follow_up_on ?? '';
    enquiryForm.notes = enquiry.notes ?? '';
    enquiryForm.status = enquiry.status;
    formErrors.value = {};
    showFormDialog.value = true;
};

const buildEnquiryPayload = () => {
    const payload: Record<string, unknown> = {
        student_name: enquiryForm.student_name,
        phone: enquiryForm.phone,
        dob: enquiryForm.dob || null,
        gender_id: enquiryForm.gender_id || null,
        guardian_name: enquiryForm.guardian_name || null,
        email: enquiryForm.email || null,
        address: enquiryForm.address || null,
        campus_id: enquiryForm.campus_id || null,
        class_id: enquiryForm.class_id || null,
        session_id: enquiryForm.session_id || null,
        follow_up_on: enquiryForm.follow_up_on || null,
        notes: enquiryForm.notes || null,
    };

    if (isEditing.value) {
        payload.status = enquiryForm.status;
    }

    return payload;
};

const submitEnquiryForm = async () => {
    formProcessing.value = true;
    formErrors.value = {};

    try {
        if (isEditing.value && editingEnquiry.value) {
            await axios.put(route('students.enquiries.update', editingEnquiry.value.id), buildEnquiryPayload());
            alert.success('Enquiry updated.');
        } else {
            await axios.post(route('students.enquiries.store'), buildEnquiryPayload());
            alert.success('Enquiry recorded.');
        }

        showFormDialog.value = false;
        fetchEnquiries();
    } catch (error: any) {
        if (error?.response?.status === 422) {
            const responseErrors = error.response.data?.errors ?? {};
            formErrors.value = Object.fromEntries(
                Object.entries(responseErrors).map(([key, value]) => [key, Array.isArray(value) ? value[0] : String(value)]),
            );
        } else {
            alert.error('Failed to save the enquiry. Please try again.');
        }
    } finally {
        formProcessing.value = false;
    }
};

/* ==================== Delete ==================== */

const deletingId = ref<number | null>(null);

const deleteEnquiry = async (enquiry: EnquiryRow) => {
    const result = await alert.confirm(
        `Remove the enquiry for ${enquiry.student_name}? This cannot be undone.`,
        'Delete this enquiry?',
        'Yes, delete it',
    );

    if (!result.isConfirmed) {
        return;
    }

    deletingId.value = enquiry.id;
    try {
        await axios.delete(route('students.enquiries.destroy', enquiry.id));
        alert.success('Enquiry removed.');
        fetchEnquiries();
    } catch {
        alert.error('Failed to remove the enquiry.');
    } finally {
        deletingId.value = null;
    }
};

/* ==================== Admit ==================== */

const admittingId = ref<number | null>(null);

/**
 * Fetches the pre-fill payload and hands it to the ordinary New Admission
 * form via the query string — the enquiry is never turned into a student
 * here. `enquiry_id` rides along so the admission, once it succeeds, marks
 * this enquiry admitted server-side.
 */
const admitEnquiry = async (enquiry: EnquiryRow) => {
    admittingId.value = enquiry.id;
    try {
        const response = await axios.get(route('students.enquiries.prefill', enquiry.id));
        const data: Record<string, unknown> = response.data.data;

        const params = new URLSearchParams();
        Object.entries(data).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                params.append(key, String(value));
            }
        });

        router.visit(`${route('students.create')}?${params.toString()}`);
    } catch {
        alert.error('Failed to load the enquiry details.');
        admittingId.value = null;
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Admission Enquiries" />

        <div class="space-y-4 md:space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 md:gap-4">
                <div>
                    <h1 class="text-lg md:text-2xl font-bold text-foreground">Admission Enquiries</h1>
                    <p class="mt-1 text-xs md:text-sm text-muted-foreground">
                        Families who have asked about a place, and who is due to be rung today.
                    </p>
                </div>
                <Button @click="openNewDialog">
                    <Icon icon="plus" class="mr-1" />
                    New Enquiry
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-2 md:gap-3 flex-wrap items-start sm:items-center" role="search" aria-label="Enquiry filters">
                <div class="w-full sm:w-44">
                    <Label for="filter-status" class="sr-only">Filter by status</Label>
                    <select id="filter-status" v-model="filters.status" :class="selectClass">
                        <option value="">All Statuses</option>
                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <div class="w-full sm:w-64">
                    <Label for="filter-phone" class="sr-only">Search by phone</Label>
                    <Input id="filter-phone" v-model="filters.phone" type="text" placeholder="Search by phone..." />
                </div>

                <label class="flex items-center gap-2 text-sm text-foreground select-none">
                    <Switch v-model:checked="filters.due" />
                    Due for follow-up today
                </label>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="text-center py-8">
                <Icon icon="loader" class="mx-auto h-6 w-6 animate-spin text-muted-foreground" />
            </div>

            <template v-else>
                <!-- Empty state -->
                <div v-if="enquiries.length === 0" class="bg-card rounded-lg border border-border p-8 text-center text-muted-foreground">
                    <Icon icon="help-circle" class="h-10 w-10 mx-auto mb-3 text-muted-foreground" />
                    No enquiries found.
                </div>

                <template v-else>
                    <!-- Mobile Card View -->
                    <div class="block lg:hidden space-y-3">
                        <div
                            v-for="enquiry in enquiries"
                            :key="enquiry.id"
                            class="bg-card rounded-lg border border-border p-4 space-y-3"
                        >
                            <div class="flex flex-wrap gap-2 justify-between items-start">
                                <div>
                                    <div class="font-medium text-foreground">{{ enquiry.student_name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ enquiry.phone }}</div>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium rounded-full shrink-0" :class="statusColor(enquiry.status)">
                                    {{ enquiry.status }}
                                </span>
                            </div>

                            <div class="text-sm text-muted-foreground space-y-1 pt-2 border-t border-border">
                                <div class="flex items-center gap-2">
                                    <Icon icon="building" class="h-4 w-4" />
                                    <span>{{ enquiry.campus?.name || 'Not placed yet' }} — {{ enquiry.class?.name || 'N/A' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Icon icon="calendar" class="h-4 w-4" />
                                    <span>Follow up: {{ formatDate(enquiry.follow_up_on) }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Icon icon="user" class="h-4 w-4" />
                                    <span>Handled by: {{ enquiry.handledBy?.name ?? '—' }}</span>
                                </div>
                            </div>

                            <div class="flex gap-2 pt-2">
                                <Button
                                    v-if="!enquiry.student_id"
                                    size="sm"
                                    class="flex-1"
                                    :disabled="admittingId === enquiry.id"
                                    @click="admitEnquiry(enquiry)"
                                >
                                    <Icon icon="user-check" class="mr-1 h-3 w-3" />Admit
                                </Button>
                                <Button variant="outline" size="sm" class="flex-1" @click="openEditDialog(enquiry)">
                                    <Icon icon="pencil" class="mr-1 h-3 w-3" />Edit
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="flex-1"
                                    :disabled="deletingId === enquiry.id"
                                    @click="deleteEnquiry(enquiry)"
                                >
                                    <Icon icon="trash-2" class="mr-1 h-3 w-3" />Delete
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden lg:block overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-border">
                                <thead class="bg-muted">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Child</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Phone</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Campus / Class</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Follow-up</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Status</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Handled By</th>
                                        <th scope="col" class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border bg-card">
                                    <tr v-for="enquiry in enquiries" :key="enquiry.id" class="transition-colors hover:bg-accent">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-foreground">
                                            {{ enquiry.student_name }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ enquiry.phone }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm text-muted-foreground">{{ enquiry.campus?.name || 'Not placed yet' }}</div>
                                            <div class="text-xs text-muted-foreground">{{ enquiry.class?.name || 'N/A' }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ formatDate(enquiry.follow_up_on) }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full" :class="statusColor(enquiry.status)">
                                                {{ enquiry.status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-muted-foreground">{{ enquiry.handledBy?.name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                            <RowActions>
                                                <RowAction
                                                    v-if="!enquiry.student_id"
                                                    kind="approve"
                                                    icon="user-check"
                                                    label="Admit"
                                                    :disabled="admittingId === enquiry.id"
                                                    @click="admitEnquiry(enquiry)"
                                                />
                                                <RowAction kind="edit" @click="openEditDialog(enquiry)" />
                                                <RowAction kind="delete" :disabled="deletingId === enquiry.id" @click="deleteEnquiry(enquiry)" />
                                            </RowActions>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="pagination.links.length > 3" class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div class="text-xs md:text-sm text-muted-foreground">
                            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <button
                                v-for="link in pagination.links"
                                :key="link.label"
                                type="button"
                                :disabled="!link.url"
                                :class="[
                                    'px-3 py-2 text-sm rounded-md transition-colors min-h-10 flex items-center justify-center',
                                    link.active
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-card text-muted-foreground hover:bg-accent border border-border disabled:opacity-40',
                                ]"
                                @click="link.url && fetchEnquiries(link.url)"
                            >
                                <span v-html="link.label"></span>
                            </button>
                        </div>
                    </div>
                </template>
            </template>
        </div>

        <!-- New / Edit Enquiry Dialog -->
        <Dialog v-model:open="showFormDialog">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ isEditing ? 'Edit Enquiry' : 'New Enquiry' }}</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitEnquiryForm" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="student_name">Child's Name <span class="text-destructive">*</span></Label>
                            <Input id="student_name" v-model="enquiryForm.student_name" type="text" placeholder="Enter child's name" required />
                            <InputError :message="formErrors.student_name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="phone">Phone <span class="text-destructive">*</span></Label>
                            <Input id="phone" v-model="enquiryForm.phone" type="text" placeholder="03001234567" required />
                            <InputError :message="formErrors.phone" />
                        </div>

                        <div class="space-y-2">
                            <Label for="dob">Date of Birth</Label>
                            <Input id="dob" v-model="enquiryForm.dob" type="date" />
                            <InputError :message="formErrors.dob" />
                        </div>

                        <div class="space-y-2">
                            <Label for="gender_id">Gender</Label>
                            <select id="gender_id" v-model="enquiryForm.gender_id" :class="selectClass">
                                <option value="">Select Gender</option>
                                <option v-for="gender in props.genders" :key="gender.id" :value="gender.id">
                                    {{ gender.name }}
                                </option>
                            </select>
                            <InputError :message="formErrors.gender_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="guardian_name">Guardian's Name</Label>
                            <Input id="guardian_name" v-model="enquiryForm.guardian_name" type="text" placeholder="Enter guardian's name" />
                            <InputError :message="formErrors.guardian_name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="enquiryForm.email" type="email" placeholder="Enter email address" />
                            <InputError :message="formErrors.email" />
                        </div>

                        <div class="space-y-2">
                            <Label for="campus_id">Intended Campus</Label>
                            <select id="campus_id" v-model="enquiryForm.campus_id" :class="selectClass">
                                <option value="">Not decided yet</option>
                                <option v-for="campus in props.campuses" :key="campus.id" :value="campus.id">
                                    {{ campus.name }}
                                </option>
                            </select>
                            <InputError :message="formErrors.campus_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="class_id">Intended Class</Label>
                            <select id="class_id" v-model="enquiryForm.class_id" :class="selectClass">
                                <option value="">Not decided yet</option>
                                <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">
                                    {{ cls.name }}
                                </option>
                            </select>
                            <InputError :message="formErrors.class_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="session_id">Intended Session</Label>
                            <select id="session_id" v-model="enquiryForm.session_id" :class="selectClass">
                                <option value="">Not decided yet</option>
                                <option v-for="session in props.sessions" :key="session.id" :value="session.id">
                                    {{ session.name }}
                                </option>
                            </select>
                            <InputError :message="formErrors.session_id" />
                        </div>

                        <div class="space-y-2">
                            <Label for="follow_up_on">Follow-up Date</Label>
                            <Input id="follow_up_on" v-model="enquiryForm.follow_up_on" type="date" />
                            <InputError :message="formErrors.follow_up_on" />
                        </div>

                        <div v-if="isEditing" class="space-y-2">
                            <Label for="status">Status</Label>
                            <select id="status" v-model="enquiryForm.status" :class="selectClass">
                                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <InputError :message="formErrors.status" />
                        </div>

                        <div class="col-span-full space-y-2">
                            <Label for="address">Address</Label>
                            <Input id="address" v-model="enquiryForm.address" type="text" placeholder="Enter address" />
                            <InputError :message="formErrors.address" />
                        </div>

                        <div class="col-span-full space-y-2">
                            <Label for="notes">Notes</Label>
                            <textarea
                                id="notes"
                                v-model="enquiryForm.notes"
                                :class="textareaClass"
                                rows="3"
                                placeholder="What did the family ask about? What was promised?"
                            />
                            <InputError :message="formErrors.notes" />
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 pt-2">
                        <DialogClose as-child>
                            <Button type="button" variant="outline">Cancel</Button>
                        </DialogClose>
                        <Button type="submit" :disabled="formProcessing">
                            <Icon v-if="formProcessing" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                            {{ isEditing ? 'Save Changes' : 'Record Enquiry' }}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
