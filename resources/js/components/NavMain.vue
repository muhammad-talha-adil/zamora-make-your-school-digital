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
        <SidebarGroupLabel class="text-xs font-semibold text-slate-500 dark:text-slate-400">Platform</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.id || item.title">
                <!-- Menu item without children -->
                <SidebarMenuItem v-if="!item.children">
                    <SidebarMenuButton
                        as-child
                        :is-active="urlIsActive(item.href)"
                        :tooltip="item.title"
                        class="bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800 rounded-md transition-colors"
                    >
                        <Link :href="item.href" class="flex items-center gap-3">
                            <Icon :icon="item.icon" :size="24" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                
                <!-- Menu item with children (accordion) -->
                <SidebarMenuItem v-else class="relative">
                    <button
                        type="button"
                        :class="[
                            'w-full flex items-center gap-3 px-2 py-2 rounded-md text-sm font-medium transition-colors',
                            isSubmenuOpen(item.id || item.title) || urlIsActive(item.href)
                                ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100'
                                : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800',
                        ]"
                        @click="toggleSubmenu($event, item.id || item.title)"
                    >
                        <Icon :icon="item.icon" :size="24" />
                        <span class="flex-1 text-left">{{ item.title }}</span>
                        <Icon
                            :icon="isSubmenuOpen(item.id || item.title) ? 'chevron-down' : 'chevron-right'"
                            :size="16"
                            class="transition-transform duration-200"
                        />
                    </button>
                    
                    <!-- Submenu -->
                    <div 
                        v-show="isSubmenuOpen(item.id || item.title)"
                        class="mt-1 ml-4 space-y-1"
                    >
                        <template v-for="child in item.children" :key="child.id || child.title">
                            <Link 
                                :href="child.href" 
                                :class="[
                                    'flex items-center gap-2 px-2 py-1.5 rounded-md text-sm transition-colors',
                                    urlIsActive(child.href)
                                        ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100'
                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
                                ]"
                                @click="closeSubmenu"
                            >
                                <Icon :icon="child.icon" :size="16" />
                                <span>{{ child.title }}</span>
                            </Link>
                        </template>
                    </div>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>

