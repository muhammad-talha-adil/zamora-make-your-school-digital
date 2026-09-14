<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

interface ChildOption {
    id: number;
    name: string | null;
}

interface Voucher {
    id: number;
    voucher_no: string | null;
    voucher_year: number;
    status: string;
    net_amount: string | number;
    paid_amount: string | number;
    balance_amount: string | number;
    due_date: string | null;
    voucher_month?: { name: string } | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}

interface Props {
    student: ChildOption;
    students: ChildOption[];
    vouchers: Paginated<Voucher>;
}

const props = defineProps<Props>();
</script>

<template>
    <AppLayout>
        <Head title="My Fee Vouchers" />

        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold">Fee Vouchers — {{ props.student.name }}</h1>

                <select
                    v-if="props.students.length > 1"
                    :value="props.student.id"
                    class="rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-800"
                    @change="
                        (event) =>
                            router.visit(route('portal.fees.index', { student_id: (event.target as HTMLSelectElement).value }))
                    "
                >
                    <option v-for="child in props.students" :key="child.id" :value="child.id">
                        {{ child.name }}
                    </option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="p-2">Voucher #</th>
                            <th class="p-2">Month/Year</th>
                            <th class="p-2">Status</th>
                            <th class="p-2">Net</th>
                            <th class="p-2">Paid</th>
                            <th class="p-2">Balance</th>
                            <th class="p-2">Due date</th>
                            <th class="p-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="voucher in props.vouchers.data" :key="voucher.id" class="border-b border-gray-100 dark:border-gray-800">
                            <td class="p-2">{{ voucher.voucher_no }}</td>
                            <td class="p-2">{{ voucher.voucher_month?.name }} {{ voucher.voucher_year }}</td>
                            <td class="p-2">{{ voucher.status }}</td>
                            <td class="p-2">{{ voucher.net_amount }}</td>
                            <td class="p-2">{{ voucher.paid_amount }}</td>
                            <td class="p-2">{{ voucher.balance_amount }}</td>
                            <td class="p-2">{{ voucher.due_date }}</td>
                            <td class="p-2">
                                <Link :href="route('portal.fees.show', voucher.id)" class="text-blue-600 hover:underline">View</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p v-if="props.vouchers.data.length === 0" class="py-8 text-center text-gray-500">No fee vouchers found.</p>
            </div>

            <div v-if="props.vouchers.links.length" class="flex flex-wrap gap-1">
                <template v-for="link in props.vouchers.links" :key="`${link.label}-${link.url ?? 'disabled'}`">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded px-3 py-1 text-sm"
                        :class="link.active ? 'bg-blue-600 text-white' : 'border border-gray-300 dark:border-gray-700'"
                        v-html="link.label"
                    />
                    <span v-else class="rounded px-3 py-1 text-sm text-gray-400" v-html="link.label" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
