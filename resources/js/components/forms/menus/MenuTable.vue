<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import Icon from '@/components/Icon.vue';
import TablePagination from '@/components/tables/TablePagination.vue';
import { tableActionButtonClass } from '@/utils/table-actions';
import StatusToggle from '@/components/tables/StatusToggle.vue';

interface Menu {
    id: number;
    title: string;
    type: string;
    order: number;
    is_active: boolean;
    parent_id?: number | null;
    parent_title?: string | null;
    parent_hierarchy_label?: string | null;
    hierarchy_label?: string;
    deleted_at?: string | null;
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

defineProps<{
    menusData: Menu[];
    pagination: Pagination;
    showInactive: boolean;
    hasManageMenusPermission: boolean;
}>();

const emit = defineEmits<{
    (e: 'edit', menu: Menu): void;
    (e: 'toggleActive', menu: Menu): void;
    (e: 'delete', menu: Menu): void;
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
    emit('update:perPage', Number(value));
};
</script>

<template>
    <div class="space-y-4">
        <div class="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                #
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Title
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Type
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Parent Menu
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Order
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        <tr v-for="(menu, index) in menusData" :key="menu.id" class="transition-colors hover:bg-accent">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-muted-foreground">
                                    {{ pagination.from ? pagination.from + index : (index as number) + 1 }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-foreground">
                                    {{ menu.title }}
                                </div>
                                <div v-if="menu.hierarchy_label && menu.hierarchy_label !== menu.title" class="text-xs text-muted-foreground">
                                    {{ menu.hierarchy_label }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <Badge :variant="menu.type === 'main' ? 'default' : 'secondary'">
                                    {{ menu.type }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-muted-foreground">
                                    {{ menu.parent_title || 'Top Level' }}
                                </div>
                                <div
                                    v-if="menu.parent_hierarchy_label && menu.parent_hierarchy_label !== menu.parent_title"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ menu.parent_hierarchy_label }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="inline-flex min-w-14 items-center justify-center rounded-md bg-muted px-3 py-1 text-sm font-semibold text-foreground">
                                    {{ menu.order }}
                                </div>
                                <div class="mt-2 text-xs font-medium text-muted-foreground">
                                    {{ menu.parent_title ? 'Sibling order' : 'Top level' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <StatusToggle
                                    :active="menu.is_active"
                                    :disabled="!hasManageMenusPermission || showInactive"
                                    @toggle="hasManageMenusPermission && !showInactive && emit('toggleActive', menu)"
                                />
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                <div class="flex flex-wrap justify-end gap-2" v-if="!showInactive && hasManageMenusPermission">
                                    <Button variant="outline" size="sm" :class="tableActionButtonClass.edit" title="Edit Menu" @click="emit('edit', menu)">
                                        <Icon icon="edit" class="mr-1" />Edit
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        :class="tableActionButtonClass.delete"
                                        title="Delete Menu"
                                        @click="emit('delete', menu)"
                                    >
                                        <Icon icon="trash-2" class="mr-1" />Delete
                                    </Button>
                                </div>
                                <div class="flex flex-wrap justify-end gap-2" v-else-if="showInactive && hasManageMenusPermission">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        :class="tableActionButtonClass.restore"
                                        @click="emit('restore', menu)"
                                    >
                                        <Icon icon="refresh" class="mr-1" />Restore
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        :class="tableActionButtonClass.delete"
                                        @click="emit('forceDelete', menu)"
                                    >
                                        <Icon icon="x" class="mr-1" />Delete
                                    </Button>
                                </div>
                                <span v-else class="text-xs text-muted-foreground">&mdash;</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <TablePagination
            :pagination="pagination"
            :per-page="pagination.per_page || 10"
            @update:per-page="updatePerPage"
            @page="(page) => emit('fetchMenus', page)"
        />
    </div>
</template>
