<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { alert } from '@/utils';
import { ref, watch, computed, onMounted } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import { type BreadcrumbItem } from '@/types';
import MenuModal from '@/components/forms/menus/MenuModal.vue';
import MenuTable from '@/components/forms/menus/MenuTable.vue';

interface Props {
    tableMenus: {
        data: Array<{
            id: number;
            title: string;
            type: string;
            order: number;
            is_active: boolean;
        }>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        from: number;
        to: number;
        total: number;
    };
    parentMenus: Array<{
        id: number;
        title: string;
    }>;
}

const props = defineProps<Props>();

const page = usePage();

// Check if user has settings.manage permission
const hasManageMenusPermission = computed(() => {
    const roles = (page.props.auth?.user?.roles as any[]) || [];
    return roles.some((role: any) => 
        role.permissions?.some((perm: any) => 
            perm.key === 'settings.manage'
        )
    );
});

const modalOpen = ref(false);
const modalMode = ref<'create' | 'edit'>('create');
const selectedMenu = ref<any>(null);

// Filter states
const showDeleted = ref(false);
const statusFilter = ref('');
const perPage = ref(10);
const menusData = ref(props.tableMenus.data || []);
const pagination = ref(props.tableMenus);

const filteredParentMenus = computed(() => {
    if (!selectedMenu.value) return props.parentMenus;
    return props.parentMenus.filter(menu => menu.id !== selectedMenu.value.id);
});

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Menu management',
        href: '/settings/menu-settings',
    },
];

const fetchMenus = (pageNum = 1) => {
    const params = new URLSearchParams({
        per_page: perPage.value.toString(),
        page: pageNum.toString(),
    });

    if (showDeleted.value) {
        params.append('trashed', '1');
    } else if (statusFilter.value) {
        params.append('status', statusFilter.value);
    }

    axios.get(`/settings/menus/all?${params}`).then((response) => {
        menusData.value = response.data.data;
        pagination.value = response.data;
    });
};

watch([statusFilter, perPage], () => {
    fetchMenus();
});

// Watch for props changes
watch(() => props.tableMenus, (newMenus) => {
    menusData.value = newMenus.data || [];
    pagination.value = newMenus;
}, { deep: true });

const openCreateModal = () => {
    selectedMenu.value = null;
    modalMode.value = 'create';
    modalOpen.value = true;
};

const openEditModal = (menu: any) => {
    selectedMenu.value = menu;
    modalMode.value = 'edit';
    modalOpen.value = true;
};

const toggleActive = (menu: any) => {
    const action = menu.is_active ? 'inactivate' : 'activate';
    alert
        .confirm(
            `Are you sure you want to ${action} "${menu.title}"?`,
            `${action.charAt(0).toUpperCase() + action.slice(1)} Menu`,
        )
        .then((result) => {
            if (result.isConfirmed) {
                router.patch(`/settings/menus/${menu.id}/${action}`, {}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success(`Menu ${action}d successfully!`);
                        fetchMenus();
                    },
                    onError: () => {
                        alert.error(`Failed to ${action} menu. Please try again.`);
                    },
                });
            }
        });
};

const deleteMenu = (menu: any) => {
    alert
        .confirm(
            `Are you sure you want to delete "${menu.title}"? You can restore it later from deleted menus.`,
            'Delete Menu',
        )
        .then((result) => {
            if (result.isConfirmed) {
                router.delete(`/settings/menus/${menu.id}`, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success('Menu deleted successfully!');
                        fetchMenus();
                    },
                    onError: () => {
                        alert.error('Failed to delete menu. Please try again.');
                    },
                });
            }
        });
};

const restoreMenu = (menu: any) => {
    router.patch(`/settings/menus/${menu.id}/restore`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            alert.success('Menu restored successfully!');
            fetchMenus();
        },
        onError: () => {
            alert.error('Failed to restore menu. Please try again.');
        },
    });
};

const forceDeleteMenu = (menu: any) => {
    alert
        .confirm(
            `Are you sure you want to permanently delete "${menu.title}"? This action cannot be undone.`,
            'Delete Menu',
        )
        .then((result) => {
            if (result.isConfirmed) {
                router.delete(`/settings/menus/${menu.id}/force-delete`, {
                    preserveScroll: true,
                    onSuccess: () => {
                        alert.success('Menu permanently deleted successfully!');
                        fetchMenus();
                    },
                    onError: () => {
                        alert.error('Failed to permanently delete menu. Please try again.');
                    },
                });
            }
        });
};

const toggleDeleted = () => {
    showDeleted.value = !showDeleted.value;
    statusFilter.value = '';
    fetchMenus();
};

// Fetch menus on initial load
onMounted(() => {
    fetchMenus();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Menu management" />

        <SettingsLayout>
            <div class="space-y-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Menu Management
                        </h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Manage navigation menus for the application.
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <select v-model="statusFilter" class="w-32 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm">
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <Button v-if="hasManageMenusPermission" @click="openCreateModal">
                            <Icon icon="plus" class="mr-2" />
                            Add Menu
                        </Button>
                        <Button
                            :variant="showDeleted ? 'ghost' : 'default'"
                            size="sm"
                            @click="toggleDeleted"
                        >
                            <Icon :icon="showDeleted ? 'arrow-left' : 'trash-2'" class="mr-1" />
                            {{ showDeleted ? 'Back' : 'Deleted Menus' }}
                        </Button>
                    </div>
                </div>

                <MenuTable
                    :menus-data="menusData"
                    :pagination="pagination"
                    :show-inactive="showDeleted"
                    :has-manage-menus-permission="hasManageMenusPermission"
                    @edit="openEditModal"
                    @toggle-active="toggleActive"
                    @delete="deleteMenu"
                    @restore="restoreMenu"
                    @force-delete="forceDeleteMenu"
                    @fetch-menus="fetchMenus"
                    @update:per-page="perPage = $event"
                />
            </div>
        </SettingsLayout>

        <MenuModal
            v-if="hasManageMenusPermission"
            v-model:open="modalOpen"
            :mode="modalMode"
            :menu="selectedMenu"
            :parent-menus="modalMode === 'create' ? props.parentMenus : filteredParentMenus"
            @saved="fetchMenus()"
        />
    </AppLayout>
</template>
