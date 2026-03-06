<script setup lang="ts">
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import axios from 'axios';
import { ref, watch } from 'vue';

// Props
interface Props {
    initialHolidays?: any;
}

const props = withDefaults(defineProps<Props>(), {});

// Emits
const emit = defineEmits<{
    back: [];
}>();

const perPage = ref(10);
const holidaysData = ref(props.initialHolidays?.data || []);
const pagination = ref(props.initialHolidays || { data: [], links: [], from: 0, to: 0, total: 0 });

const fetchHolidays = (page = 1) => {
    const params = new URLSearchParams({
        per_page: perPage.value.toString(),
        page: page.toString(),
    });

    axios.get(`/attendance/settings/past-holidays?${params}`).then((response) => {
        holidaysData.value = response.data.data;
        pagination.value = response.data;
    });
};

watch(() => props.initialHolidays, (newHolidays) => {
    if (newHolidays) {
        holidaysData.value = newHolidays.data || [];
        pagination.value = newHolidays;
    }
}, { deep: true });

watch(perPage, () => {
    fetchHolidays();
});

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
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
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" @click="emit('back')">
                    <Icon icon="arrow-left" class="mr-1" />
                    Back to Current Holidays
                </Button>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white ml-2">
                    Past Holidays
                </h3>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">Show:</span>
                <select v-model="perPage" class="w-20 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-1 text-sm">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
            <div class="flex items-center">
                <Icon icon="info" class="h-5 w-5 text-blue-500 mr-2" />
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    These holidays have already passed and cannot be edited or deleted.
                </p>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                #
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                Title
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                Start Date
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                End Date
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                Days
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                Type
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                Campus
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        <tr
                            v-for="(holiday, index) in holidaysData"
                            :key="holiday.id"
                            class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ (index as number) + 1 }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ holiday.title }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ formatDate(holiday.start_date) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ formatDate(holiday.end_date) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ getDaysCount(holiday.start_date, holiday.end_date) }} day(s)
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                        holiday.is_national
                                            ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
                                            : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    ]"
                                >
                                    {{ holiday.is_national ? 'National' : 'Campus' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ holiday.is_national ? 'All' : (holiday.campus?.name || '—') }}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-between items-center">
            <div class="text-sm text-gray-600">
                Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
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
