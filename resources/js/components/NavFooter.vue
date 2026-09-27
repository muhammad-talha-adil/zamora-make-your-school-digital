<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { toUrl } from '@/lib/utils';
import { type MenuItem } from '@/types';
import Icon from '@/components/Icon.vue';
import { Link } from '@inertiajs/vue3';

interface Props {
    items: MenuItem[];
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <SidebarGroup
        :class="`group-data-[collapsible=icon]:p-0 ${$props.class || ''}`"
    >
        <SidebarGroupContent>
            <SidebarMenu>
                <template v-for="item in items" :key="item.title">
                    <SidebarMenuItem v-if="!item.children">
                        <SidebarMenuButton
                            class="text-muted-foreground hover:text-foreground transition-colors"
                            as-child
                        >
                            <Link :href="toUrl(item.href)" class="flex items-center gap-3">
                                <Icon :icon="item.icon" :size="24" class="shrink-0" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem v-else>
                        <SidebarMenuButton
                            class="text-muted-foreground hover:text-foreground transition-colors"
                        >
                            <Icon :icon="item.icon" :size="24" class="shrink-0" />
                            <span>{{ item.title }}</span>
                        </SidebarMenuButton>
                        <SidebarMenuSub>
                            <SidebarMenuSubItem v-for="child in item.children" :key="child.title">
                                <SidebarMenuSubButton as-child>
                                    <Link :href="toUrl(child.href)">
                                        <Icon :icon="child.icon" :size="24" class="text-muted-foreground shrink-0" />
                                        <span>{{ child.title }}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </SidebarMenuItem>
                </template>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>