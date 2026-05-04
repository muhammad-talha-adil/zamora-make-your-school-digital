<script setup lang="ts">
import AcademicSessionForm from '@/components/forms/AcademicSessionForm.vue';
import { Button } from '@/components/ui';
import Icon from '@/components/Icon.vue';
import { alert, formatDate } from '@/utils';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, watch } from 'vue';

// Props
interface Props {
    sessions: any; // Paginated response
}

const props = defineProps<Props>();

// Emits
// const emit = defineEmits<{
//     saved: [];
// }>();

const showTrashed = ref(false);
const statusFilter = ref('');
const perPage = ref(10);
const sessionsData = ref(props.sessions.data || []);
const pagination = ref(props.sessions);

const toggleTrashed = () => {
    showTrashed.value = !showTrashed.value;
    fetchSessions();
};

const fetchSessions = (page = 1) => {
    const params = new URLSearchParams({
        trashed: showTrashed.value ? '1' : '0',
        per_page: perPage.value.toString(),
        page: page.toString(),
    });

    if (statusFilter.value) {
        params.append('status', statusFilter.value);
    }

    axios.get(`/settings/sessions?${params}`).then((response) => {
        sessionsData.value = response.data.data;
        pagination.value = response.data;
    });
};

watch([statusFilter, perPage], () => {
    fetchSessions();
});

// Watch for props changes (e.g., after form submissions)
watch(() => props.sessions, (newSessions) => {
    sessionsData.value = newSessions.data || [];
    pagination.value = newSessions;
}, { deep: true });

const deleteSession = (session: any) => {
    alert
        .confirm(
            `Are you sure you want to delete "${session.name}"? This action cannot be undone.`,
            'Delete Session',
        )
        .then((result) => {
            if (result.isConfirmed) {
                router.delete(`/settings/sessions/${session.id}`, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success('Session deleted successfully!');
                        fetchSessions();
                    },
                    onError: () => {
                        alert.error(
                            'Failed to delete session. Please try again.',
                        );
                    },
                });
            }
        });
};

const restoreSession = (session: any) => {
    router.patch(`/settings/sessions/${session.id}/restore`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            alert.success('Session restored successfully!');
            fetchSessions();
        },
        onError: () => {
            alert.error('Failed to restore session. Please try again.');
        },
    });
};

const forceDeleteSession = (session: any) => {
    alert
        .confirm(
            `Are you sure you want to permanently delete "${session.name}"? This action cannot be undone.`,
            'Permanently Delete Session',
        )
        .then((result) => {
            if (result.isConfirmed) {
                router.delete(`/settings/sessions/${session.id}/force`, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success('Session permanently deleted successfully!');
                        fetchSessions();
                    },
                    onError: () => {
                        alert.error(
                            'Failed to permanently delete session. Please try again.',
                        );
                    },
                });
            }
        });
};

const activateSession = (session: any) => {
    // Find the currently active session
    const activeSession = sessionsData.value.find((s: any) => s.is_active === true);
    
    let confirmMessage = `Are you sure you want to activate "${session.name}"?`;
    let confirmTitle = 'Activate Session';
    
    if (activeSession && activeSession.id !== session.id) {
        confirmMessage = `Activating "${session.name}" will automatically deactivate "${activeSession.name}".\n\nDo you want to continue?`;
        confirmTitle = 'Switch Active Session';
    }
    
    alert
        .confirm(confirmMessage, confirmTitle)
        .then((result) => {
            if (result.isConfirmed) {
                router.patch(`/settings/sessions/${session.id}/activate`, {}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success(`"${session.name}" is now the active session!`);
                        fetchSessions();
                    },
                    onError: () => {
                        alert.error('Failed to activate session. Please try again.');
                    },
                });
            }
        });
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <div class="flex gap-2">
                <select v-model="statusFilter" class="w-32 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="flex gap-2">
                <AcademicSessionForm @saved="fetchSessions" />
                <Button
                    :variant="showTrashed ? 'ghost' : 'default'"
                    size="sm"
                    @click="toggleTrashed"
                >
                    <Icon :icon="showTrashed ? 'arrow-left' : 'eye'" class="mr-1" />
                    {{ showTrashed ? 'Back' : 'Deleted Sessions' }}
                </Button>
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
                                Session Name
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                Years
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                Duration
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                Status
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        <tr
                            v-for="(session, index) in sessionsData"
                            :key="session.id"
                            class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ (index as number) + 1 }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ session.name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ session.start_year }}-{{ session.end_year }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ formatDate(session.start_date) }} to {{ formatDate(session.end_date) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                        session.is_active
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                            : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    ]"
                                >
                                    {{ session.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                <div class="flex space-x-2" v-if="!showTrashed">
                                    <AcademicSessionForm
                                        :session="session"
                                        trigger="Edit"
                                        variant="outline"
                                        size="sm"
                                        @saved="fetchSessions"
                                    >
                                        <Icon icon="edit" class="mr-1" />Edit
                                    </AcademicSessionForm>
                                    <Button
                                        v-if="!session.is_active"
                                        variant="default"
                                        size="sm"
                                        @click="activateSession(session)"
                                    >
                                        <Icon icon="check-circle" class="mr-1" />Activate
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        @click="deleteSession(session)"
                                    >
                                        <Icon icon="trash" class="mr-1" />Delete
                                    </Button>
                                </div>
                                <div class="flex space-x-2" v-else>
                                    <Button
                                        variant="default"
                                        size="sm"
                                        @click="restoreSession(session)"
                                    >
                                        <Icon icon="refresh" class="mr-1" />Restore
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        @click="forceDeleteSession(session)"
                                    >
                                        <Icon icon="x" class="mr-1" />Force Delete
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                <div class="text-sm text-gray-600">
                    Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                </div>
                <select v-model="perPage" class="w-20 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-1 text-sm">
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
                    @click="link.url ? fetchSessions(parseInt(link.url.match(/page=(\d+)/)?.[1] || '1')) : null"
                >
                    <span v-html="link.label"></span>
                </Button>
            </div>
        </div>
    </div>
</template>
