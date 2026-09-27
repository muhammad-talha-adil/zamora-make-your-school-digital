<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import type { User } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Settings, User, Shield } from 'lucide-vue-next';

interface Props {
    user: User;
}

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();
</script>

<template>
    <div class="space-y-1">
        <DropdownMenuLabel class="p-1">
            <div class="flex items-center gap-3 px-1 py-2">
                <UserInfo :user="user" :show-email="true" />
            </div>
        </DropdownMenuLabel>
        <DropdownMenuSeparator class="my-1" />
        <DropdownMenuGroup>
            <DropdownMenuItem :as-child="true">
                <Link class="flex items-center gap-2 w-full cursor-pointer" :href="route('profile.edit')" prefetch>
                    <User class="h-4 w-4" />
                    <span>Profile</span>
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem :as-child="true">
                <Link class="flex items-center gap-2 w-full cursor-pointer" :href="route('settings.index')" prefetch>
                    <Settings class="h-4 w-4" />
                    <span>Settings</span>
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem v-if="user.roles?.includes('admin')" :as-child="true">
                <Link class="flex items-center gap-2 w-full cursor-pointer" :href="route('admin.index')" prefetch>
                    <Shield class="h-4 w-4" />
                    <span>Admin Panel</span>
                </Link>
            </DropdownMenuItem>
        </DropdownMenuGroup>
        <DropdownMenuSeparator class="my-1" />
        <DropdownMenuItem :as-child="true">
            <Link
                class="flex items-center gap-2 w-full cursor-pointer text-destructive focus:text-destructive"
                :href="route('logout')"
                @click="handleLogout"
                as="button"
                data-test="logout-button"
            >
                <LogOut class="h-4 w-4" />
                <span>Log out</span>
            </Link>
        </DropdownMenuItem>
    </div>
</template>