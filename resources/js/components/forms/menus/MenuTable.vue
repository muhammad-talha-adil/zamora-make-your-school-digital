<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import Icon from '@/components/Icon.vue';

interface Menu {
    id: number;
    title: string;
    type: string;
    order: number;
    is_active: boolean;
}

interface Pagination {
    data: Menu[];
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    from: number;
    to: number;
    total: number;
    per_page?: number;
}

interface Props {
    menusData: Menu[];
    pagination: Pagination;
    showInactive: boolean;
    hasManageMenusPermission: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'edit', menu: Menu): void;
    (e: 'toggleActive', menu: Menu): void;
    (e: 'restore', menu: Menu): void;
    (e: 'forceDelete', menu: Menu): void;
    (e: 'fetchMenus', page: number): void;
    (e: 'update:perPage', value: number): void;
}>();

const handlePageClick = (link: { url: string | null; label: string; active: boolean }) => {
    if (link.url) {
        const match = link.url.match(/page=(\d+)/);
        const page = match ? parseInt(match[1]) : 1;
        emit('fetchMenus', page);
    }
};

const updatePerPage = (value: number) => {
    emit('update:perPage', value);
};
</script>

<template>
    <div class="space-y-4">
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                #
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                Title
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                Type
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                Order
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-300">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        <tr v-for="(menu, index) in menusData" :key="menu.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ (index as number) + 1 }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ menu.title }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <Badge :variant="menu.type === 'main' ? 'default' : 'secondary'">
                                    {{ menu.type }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ menu.order }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <Badge :variant="menu.is_active ? 'default' : 'destructive'">
                                    {{ menu.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                <div class="flex space-x-2" v-if="!showInactive">
                                    <Button variant="outline" size="sm" title="Edit Menu" @click="emit('edit', menu)">
                                        <Icon icon="edit" class="mr-1" />Edit
                                    </Button>
                                    <Button
                                        :variant="menu.is_active ? 'destructive' : 'default'"
                                        size="sm"
                                        :title="menu.is_active ? 'Inactivate Menu' : 'Activate Menu'"
                                        @click="emit('toggleActive', menu)"
                                    >
                                        <Icon :icon="menu.is_active ? 'eye-off' : 'eye'" class="mr-1" />
                                        {{ menu.is_active ? 'Inactivate' : 'Activate' }}
                                    </Button>
                                </div>
                                <div class="flex space-x-2" v-else>
                                    <Button
                                        variant="default"
                                        size="sm"
                                        @click="emit('restore', menu)"
                                    >
                                        <Icon icon="refresh" class="mr-1" />Restore
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        @click="emit('forceDelete', menu)"
                                    >
                                        <Icon icon="x" class="mr-1" />Delete
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
                <select 
                    :value="pagination.per_page || 10" 
                    @change="updatePerPage(($event.target as any).value)"
                    class="w-20 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-1 text-sm"
                >
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
                    @click="handlePageClick(link)"
                >
                    <span v-html="link.label"></span>
                </Button>
            </div>
        </div>
    </div>
</template>
