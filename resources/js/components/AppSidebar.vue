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

const page = usePage<AppPageProps>();
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar" class="h-full shrink-0 flex flex-col">
        <SidebarHeader class="shrink-0">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="flex-1 min-h-0 overflow-y-auto scrollbar-thin">
            <NavMain :items="page.props.menus.main" />
        </SidebarContent>

        <SidebarFooter class="shrink-0">
            <NavFooter :items="page.props.menus.footer" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <div class="flex-1 min-w-0 min-h-0 overflow-y-auto"><slot /></div>
</template>

