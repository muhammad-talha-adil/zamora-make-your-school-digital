<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLogo from './AppLogo.vue';
import type { AppPageProps } from '@/types';
import { onMounted, ref, nextTick } from 'vue';

const page = usePage<AppPageProps>();
const sidebarRef = ref<HTMLElement | null>(null);
const focusedIndex = ref(-1);
const menuItems = ref<HTMLElement[]>([]);

function registerMenuItem(el: HTMLElement | null, index: number) {
    if (el) menuItems.value[index] = el;
}

function handleKeydown(event: KeyboardEvent) {
    const items = menuItems.value.filter(Boolean);
    if (items.length === 0) return;

    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            focusedIndex.value = (focusedIndex.value + 1) % items.length;
            items[focusedIndex.value]?.focus();
            break;
        case 'ArrowUp':
            event.preventDefault();
            focusedIndex.value = (focusedIndex.value - 1 + items.length) % items.length;
            items[focusedIndex.value]?.focus();
            break;
        case 'Home':
            event.preventDefault();
            focusedIndex.value = 0;
            items[0]?.focus();
            break;
        case 'End':
            event.preventDefault();
            focusedIndex.value = items.length - 1;
            items[items.length - 1]?.focus();
            break;
        case 'Escape':
            // Close mobile sidebar
            const sidebar = document.querySelector('[data-state="open"]');
            if (sidebar) {
                (sidebar as HTMLElement).dispatchEvent(new CustomEvent('close-sidebar'));
            }
            break;
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
    return () => document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <Sidebar
        ref="sidebarRef"
        collapsible="icon"
        variant="sidebar"
        class="h-full shrink-0 flex flex-col transition-[width] duration-300 ease-out"
    >
        <SidebarHeader class="shrink-0">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')" class="focus-ring">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="flex-1 min-h-0 overflow-y-auto scrollbar-thin" @keydown="handleKeydown">
            <NavMain
                :items="page.props.menus.main"
                :register-menu-item="registerMenuItem"
            />
        </SidebarContent>

        <SidebarFooter class="shrink-0">
            <NavFooter :items="page.props.menus.footer" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <div class="flex-1 min-w-0 min-h-0 overflow-y-auto"><slot /></div>
</template>