<script setup lang="ts">
import HolidayForm from '@/components/forms/HolidayForm.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { alert } from '@/utils';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, watch } from 'vue';

// Props
interface Props {
    holidays: any; // Paginated response
    campuses: Array<{
        id: number;
        name: string;
    }>;
}

const props = defineProps<Props>();

// Emits
const emit = defineEmits<{
    saved: [];
    showPast: [];
}>();

const showInactive = ref(false);
const statusFilter = ref('');
const perPage = ref(10);
const holidaysData = ref(props.holidays.data || []);
const pagination = ref(props.holidays);

const toggleInactive = () => {
    showInactive.value = !showInactive.value;
    fetchHolidays();
};

const fetchHolidays = (page = 1) => {
    const params = new URLSearchParams({
        per_page: perPage.value.toString(),
        page: page.toString(),
    });

    if (showInactive.value) {
        params.append('status', 'inactive');
    } else if (statusFilter.value) {
        params.append('status', statusFilter.value);
    }

    axios.get(`/attendance/settings/holidays?${params}`).then((response) => {
        holidaysData.value = response.data.data;
        pagination.value = response.data;
    });
};

watch([statusFilter, perPage], () => {
    fetchHolidays();
});

// Watch for props changes (e.g., after form submissions)
watch(() => props.holidays, (newHolidays) => {
    holidaysData.value = newHolidays.data || [];
    pagination.value = newHolidays;
}, { deep: true });

// Handle saved event from HolidayForm
const handleSaved = () => {
    fetchHolidays();
    emit('saved');
};

const deleteHoliday = (holiday: any) => {
    alert
        .confirm(
            `Are you sure you want to delete "${holiday.title}"?`,
            'Delete Holiday',
        )
        .then((result) => {
            if (result.isConfirmed) {
                router.delete(`/attendance/settings/holidays/${holiday.id}`, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success('Holiday deleted successfully!');
                        fetchHolidays();
                    },
                    onError: () => {
                        alert.error(
                            'Failed to delete holiday. Please try again.',
                        );
                    },
                });
            }
        });
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const isHolidayPast = (holiday: any) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const endDate = new Date(holiday.end_date);
    return endDate < today;
};

const getDaysCount = (start: string, end: string) => {
    const startDate = new Date(start);
    const endDate = new Date(end);
    const diffTime = Math.abs(endDate.getTime() - startDate.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
    return diffDays;
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
                <Button
                    variant="outline"
                    size="sm"
                    @click="emit('showPast')"
                >
                    <Icon icon="history" class="mr-1" />
                    Past Holidays
                </Button>
                <HolidayForm
                    :campuses="campuses"
                    @saved="handleSaved"
                />
                <Button
                    :variant="showInactive ? 'ghost' : 'default'"
                    size="sm"
                    @click="toggleInactive"
                >
                    <Icon :icon="showInactive ? 'arrow-left' : 'eye'" class="mr-1" />
                    {{ showInactive ? 'Back' : 'Inactive' }}
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
                                Title
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Start Date
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                End Date
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Days
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Type
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Campus
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
                            v-for="(holiday, index) in holidaysData"
                            :key="holiday.id"
                            class="transition-colors hover:bg-accent"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-muted-foreground">
                                    {{ (index as number) + 1 }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-foreground">
                                    {{ holiday.title }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-muted-foreground">
                                    {{ formatDate(holiday.start_date) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-muted-foreground">
                                    {{ formatDate(holiday.end_date) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-muted-foreground">
                                    {{ getDaysCount(holiday.start_date, holiday.end_date) }} day(s)
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                        holiday.is_national
                                            ? 'bg-primary/10 text-primary'
                                            : 'bg-primary/10 text-primary',
                                    ]"
                                >
                                    {{ holiday.is_national ? 'National' : 'Campus' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-muted-foreground">
                                    {{ holiday.is_national ? 'All' : (holiday.campus?.name || '—') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                <div v-if="isHolidayPast(holiday)" class="flex items-center text-muted-foreground">
                                    <Icon icon="lock" class="mr-1 h-4 w-4" />
                                    <span class="text-sm">Read Only</span>
                                </div>
                                <div v-else class="flex space-x-2">
                                    <HolidayForm
                                        :holiday="holiday"
                                        :campuses="campuses"
                                        trigger="Edit"
                                        variant="outline"
                                        size="sm"
                                        @saved="handleSaved"
                                    >
                                        <Icon icon="edit" class="mr-1" />Edit
                                    </HolidayForm>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        @click="deleteHoliday(holiday)"
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
                    :key="link.label"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    :disabled="!link.url"
                    @click="link.url ? fetchHolidays(parseInt(link.url.match(/page=(\d+)/)?.[1] || '1')) : null"
                >
                    <span v-html="link.label"></span>
                </Button>
            </div>
        </div>
    </div>
</template>
