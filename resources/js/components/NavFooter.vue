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
                            class="text-muted-foreground hover:text-foreground"
                            as-child
                        >
                            <a
                                :href="toUrl(item.href)"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <Icon :icon="item.icon" :size="24" />
                                <span>{{ item.title }}</span>
                            </a>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem v-else>
                        <SidebarMenuButton
                            class="text-muted-foreground hover:text-foreground"
                        >
                            <Icon :icon="item.icon" :size="24" />
                            <span>{{ item.title }}</span>
                        </SidebarMenuButton>
                        <SidebarMenuSub>
                            <SidebarMenuSubItem v-for="child in item.children" :key="child.title">
                                <SidebarMenuSubButton as-child>
                                    <a :href="toUrl(child.href)">
                                        <Icon :icon="child.icon" :size="24" class="text-muted-foreground" />
                                        <span>{{ child.title }}</span>
                                    </a>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </SidebarMenuItem>
                </template>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
