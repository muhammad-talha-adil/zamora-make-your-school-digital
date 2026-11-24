<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import { Input } from '@/components/ui/input';
import SearchableSelect from '@/components/ui/searchable-select/SearchableSelect.vue';
import TransportField from '@/components/transport/TransportField.vue';
import TransportFormDialog from '@/components/transport/TransportFormDialog.vue';
import TransportListHeader from '@/components/transport/TransportListHeader.vue';
import { useClearErrorsOnEdit } from '@/composables/useFormValidity';
import { formatMoney, useTransportForm } from '@/composables/useTransportForm';
import { alert } from '@/utils';
import type { BreadcrumbItem } from '@/types';

interface ChargeRow {
    id: number;
    student?: string | null;
    registration_no?: string | null;
    title: string;
    month_id: number;
    year: number;
    amount: number;
    status: string;
}

const props = defineProps<{ months: { id: number; name: string }[]; charges: ChargeRow[] }>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Students', href: '/students' },
    { title: 'Transport Monthly Dues', href: '/students/transport-dues' },
];

const search = ref('');
const dialogOpen = ref(false);
const { fieldErrors, submitting, validateRequired, save, axios } = useTransportForm();

const form = reactive({
    month_id: String(new Date().getMonth() + 1),
    year: String(new Date().getFullYear()),
    due_date: '',
});
useClearErrorsOnEdit(form, fieldErrors);

const monthOptions = computed(() => props.months.map((month) => ({ value: String(month.id), label: month.name })));
const monthName = (id: number) => props.months.find((month) => month.id === id)?.name ?? String(id);

const filtered = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.charges.filter(
        (charge) => !query || [charge.student, charge.registration_no, charge.title].some((value) => String(value ?? '').toLowerCase().includes(query)),
    );
});

const submit = async () => {
    if (await validateRequired(form, ['month_id', 'year'], { month_id: 'Month', year: 'Year' })) {
        return;
    }

    let message = '';
    const saved = await save(async () => {
        const response = await axios.post(route('transport.generate-dues'), { ...form, due_date: form.due_date || null });
        message = response.data?.message ?? 'Transport dues generated.';
    }, 'Could not generate dues.');

    if (saved) {
        alert.success(message);
        dialogOpen.value = false;
        router.reload({ only: ['charges'], preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Transport Monthly Dues" />

        <div class="space-y-6 p-4 md:p-6">
            <TransportListHeader
                v-model:search="search"
                title="Transport Monthly Dues"
                description="Monthly transport fee for each assigned student."
                add-label="Generate Dues"
                search-placeholder="Search by student or route..."
                @add="dialogOpen = true"
            />

            <div class="overflow-x-auto rounded-2xl border border-border bg-card shadow-sm">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Student</th>
                            <th class="px-4 py-3 font-medium">Due</th>
                            <th class="px-4 py-3 font-medium">Month</th>
                            <th class="px-4 py-3 text-right font-medium">Amount</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="charge in filtered" :key="charge.id" class="border-t border-border">
                            <td class="px-4 py-3 text-foreground">
                                <span class="font-medium">{{ charge.student || '-' }}</span>
                                <span class="block text-xs text-muted-foreground">{{ charge.registration_no }}</span>
                            </td>
                            <td class="px-4 py-3 text-foreground">{{ charge.title }}</td>
                            <td class="px-4 py-3 text-foreground">{{ monthName(charge.month_id) }} {{ charge.year }}</td>
                            <td class="px-4 py-3 text-right text-foreground">{{ formatMoney(charge.amount) }}</td>
                            <td class="px-4 py-3 capitalize text-foreground">{{ charge.status }}</td>
                        </tr>
                        <tr v-if="filtered.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">No transport dues yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <TransportFormDialog v-model:open="dialogOpen" title="Generate Dues" submit-label="Generate" :submitting="submitting" @submit="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <TransportField label="Month" required :error="fieldErrors.month_id">
                    <SearchableSelect v-model="form.month_id" :options="monthOptions" placeholder="Pick a month" />
                </TransportField>
                <TransportField label="Year" required :error="fieldErrors.year">
                    <Input v-model="form.year" type="number" />
                </TransportField>
                <TransportField label="Due date" :error="fieldErrors.due_date">
                    <Input v-model="form.due_date" type="date" />
                </TransportField>
            </div>
        </TransportFormDialog>
    </AppLayout>
</template>
