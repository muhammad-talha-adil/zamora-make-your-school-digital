<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useActiveUrl } from '@/composables/useActiveUrl';
import { type MenuItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';
import { ref } from 'vue';

defineProps<{
    items: MenuItem[];
}>();

const { urlIsActive } = useActiveUrl();

// Accordion behavior - track which sub-menu is open
const openSubmenuId = ref<string | number | null>(null);

// Function to toggle submenu - accordion style
const toggleSubmenu = (event: MouseEvent, id: string | number) => {
    event.preventDefault();
    event.stopPropagation();
    
    if (openSubmenuId.value === id) {
        openSubmenuId.value = null;
    } else {
        openSubmenuId.value = id;
    }
};

const isSubmenuOpen = (id: string | number) => {
    return openSubmenuId.value === id;
};

// Close submenu when clicking on a link in the submenu
const closeSubmenu = () => {
    openSubmenuId.value = null;
};
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel class="text-xs font-semibold text-muted-foreground">Platform</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.id || item.title">
                <!-- Menu item without children -->
                <SidebarMenuItem v-if="!item.children">
                    <SidebarMenuButton
                        as-child
                        :is-active="urlIsActive(item.href)"
                        :tooltip="item.title"
                        class="group bg-transparent hover:bg-accent rounded-md transition-colors duration-150"
                    >
                        <Link :href="item.href" class="flex min-w-0 items-center gap-3">
                            <Icon
                                :icon="item.icon"
                                :size="24"
                                class="shrink-0 transition-transform duration-200 ease-out group-hover:scale-110"
                            />
                            <span class="truncate whitespace-nowrap">{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>

                <!-- Menu item with children (accordion) -->
                <SidebarMenuItem v-else class="relative">
                    <button
                        type="button"
                        :class="[
                            'group flex w-full min-w-0 items-center gap-3 rounded-md px-2 py-2 text-sm font-medium transition-colors duration-150',
                            isSubmenuOpen(item.id || item.title) || urlIsActive(item.href)
                                ? 'bg-muted text-foreground'
                                : 'text-muted-foreground hover:bg-accent',
                        ]"
                        @click="toggleSubmenu($event, item.id || item.title)"
                    >
                        <Icon
                            :icon="item.icon"
                            :size="24"
                            class="shrink-0 transition-transform duration-200 ease-out group-hover:scale-110"
                        />
                        <span class="min-w-0 flex-1 truncate whitespace-nowrap text-left">{{ item.title }}</span>
                        <Icon
                            :icon="isSubmenuOpen(item.id || item.title) ? 'chevron-down' : 'chevron-right'"
                            :size="16"
                            class="shrink-0 transition-transform duration-200"
                        />
                    </button>

                    <!-- Submenu -->
                    <div
                        v-show="isSubmenuOpen(item.id || item.title)"
                        class="mt-1 ml-4 space-y-1 border-l border-sidebar-border pl-2"
                    >
                        <template v-for="child in item.children" :key="child.id || child.title">
                            <Link
                                :href="child.href"
                                :title="child.title"
                                :class="[
                                    'group flex min-w-0 items-center gap-2 rounded-md px-2 py-1.5 text-sm transition-all duration-150',
                                    urlIsActive(child.href)
                                        ? 'bg-muted text-foreground'
                                        : 'text-muted-foreground hover:bg-accent hover:translate-x-0.5',
                                ]"
                                @click="closeSubmenu"
                            >
                                <Icon
                                    :icon="child.icon"
                                    :size="16"
                                    class="shrink-0 transition-transform duration-200 ease-out group-hover:scale-110"
                                />
                                <span class="min-w-0 flex-1 truncate whitespace-nowrap">{{ child.title }}</span>
                            </Link>
                        </template>
                    </div>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>

