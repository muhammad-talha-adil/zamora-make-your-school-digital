<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
import { alert } from '@/utils';
import type { BreadcrumbItem } from '@/types';
import StatusToggle from '@/components/tables/StatusToggle.vue';
import RowAction from '@/components/tables/RowAction.vue';
import RowActions from '@/components/tables/RowActions.vue';

interface Lookup {
    id: number;
    name: string;
    description?: string | null;
    role?: string | null;
    is_active: boolean;
}

interface RoleOption {
    id: number;
    name: string;
    label?: string | null;
}

interface Campus {
    id: number;
    name: string;
}

interface StaffRow {
    id: number;
    employee_no: string;
    campus_id?: number | null;
    designation_id?: number | null;
    employment_type: string;
    hire_date?: string | null;
    basic_salary?: number | string;
    allowance_amount?: number | string;
    deduction_amount?: number | string;
    payment_method: string;
    is_active: boolean;
    jobs_count: number;
    user?: { id: number; name: string; email?: string | null } | null;
    campus?: { id: number; name: string } | null;
    designation?: { id: number; name: string } | null;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    per_page?: number;
}

interface Props {
    designations: Lookup[];
    campuses: Campus[];
    roles: RoleOption[];
    filters: {
        search?: string;
        campus_id?: string;
        designation_id?: string;
        status?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Staff', href: route('staff.index') },
    { title: 'Directory', href: route('staff.people.index') },
];

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
    { value: 'all', label: 'All' },
];
const filters = reactive({
    search: props.filters.search ?? '',
    campus_id: props.filters.campus_id ?? '',
    designation_id: props.filters.designation_id ?? '',
    status: props.filters.status ?? 'active',
});

const staff = ref<Paginated<StaffRow>>({ data: [], current_page: 1, last_page: 1, total: 0 });
const loading = ref(false);

const campusOptions = computed(() => props.campuses.map((campus) => ({ value: String(campus.id), label: campus.name })));

const loadStaff = async (page = 1) => {
    loading.value = true;

    try {
        const response = await axios.get(route('staff.people.list'), {
            params: { ...filters, page },
        });
        staff.value = response.data.data;
    } finally {
        loading.value = false;
    }
};

let debounceTimer: ReturnType<typeof setTimeout> | undefined;
watch(filters, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadStaff(1), 300);
});

onMounted(() => loadStaff());


const toggleStaff = async (member: StaffRow) => {
    // Activate/deactivate is a sensitive action: the confirm dialog also
    // collects the acting user's password, which `RequiresPasswordConfirmation`
    // re-verifies server-side before the toggle is applied.
    const result = await alert.confirmWithPassword(
        `Do you want to ${member.is_active ? 'deactivate' : 'activate'} ${member.user?.name ?? 'this staff member'}?`,
        'Update Staff Status',
        member.is_active ? 'Deactivate' : 'Activate',
    );

    if (!result.isConfirmed) {
        return;
    }

    try {
        await axios.patch(route('staff.members.toggle', member.id), { password: result.password });
        alert.success('Staff status updated successfully.');
        loadStaff(staff.value.current_page);
    } catch (error: any) {
        alert.error(error?.response?.data?.errors?.password?.[0] || error?.response?.data?.message || 'Failed to update staff status.');
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Staff Directory" />

        <div class="space-y-6 p-4 md:p-6">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Staff Directory</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Every record, jobs and campus.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('staff.settings.page')" class="inline-flex">
                        <Button variant="outline"><Icon icon="settings" class="h-4 w-4" />Staff Settings</Button>
                    </Link>
                    <Link :href="route('staff.people.create')">
                        <Button>
                            <Icon icon="user-plus" class="h-4 w-4" />
                            New Staff Member
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="xl:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Search</label>
                        <Input v-model="filters.search" placeholder="Name, employee no, CNIC, phone..." />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Campus</label>
                        <SearchableSelect v-model="filters.campus_id" :options="campusOptions" placeholder="All Campuses" clearable />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-muted-foreground">Status</label>
                        <SearchableSelect v-model="filters.status" :options="statusOptions" placeholder="Select status" />
                    </div>
                </div>
            </div>


            <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Employee</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Designation</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Campus</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Jobs</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr v-for="(member, memberIndex) in staff.data" :key="member.id" class="hover:bg-accent">
                                <td class="px-4 py-3 text-sm text-muted-foreground">
                                    {{ (staff.current_page - 1) * (staff.per_page || staff.data.length) + memberIndex + 1 }}
                                </td>
                                <td class="px-4 py-3">
                                    <Link :href="route('staff.people.show', member.id)" class="font-medium text-foreground hover:underline">
                                        {{ member.user?.name || '-' }}
                                    </Link>
                                    <div class="text-xs text-muted-foreground">
                                        {{ member.employee_no }}<span v-if="member.user?.email"> | {{ member.user?.email }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ member.designation?.name || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ member.campus?.name || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ member.jobs_count }}</td>
                                <td class="px-4 py-3">
                                    <StatusToggle :active="member.is_active" @toggle="toggleStaff(member)" />
                                </td>
                                <td class="px-4 py-3">
                                    <RowActions>
                                        <RowAction kind="view" @click="router.visit(route('staff.people.show', member.id))" />
                                    </RowActions>
                                </td>
                            </tr>
                            <tr v-if="!loading && staff.data.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-muted-foreground">No staff members found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="staff.last_page > 1" class="flex items-center justify-between border-t border-border px-4 py-3 text-sm text-muted-foreground">
                    <span>Page {{ staff.current_page }} of {{ staff.last_page }} ({{ staff.total }} total)</span>
                    <div class="flex gap-2">
                        <Button variant="outline" size="sm" :disabled="staff.current_page <= 1" @click="loadStaff(staff.current_page - 1)">Previous</Button>
                        <Button variant="outline" size="sm" :disabled="staff.current_page >= staff.last_page" @click="loadStaff(staff.current_page + 1)">Next</Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
