<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useActiveUrl } from '@/composables/useActiveUrl';
import { getInitials } from '@/composables/useInitials';
import { toUrl } from '@/lib/utils';
import type { BreadcrumbItem, NavItem } from '@/types';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Link, usePage, router } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Menu, Search, Sun, Moon, Bell, X, Command, Loader2 } from 'lucide-vue-next';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);
const { urlIsActive } = useActiveUrl();
const { resolvedAppearance, updateAppearance } = useAppearance();

const isSearchOpen = ref(false);
const searchQuery = ref('');
const searchResults = ref<NavItem[]>([]);
const showNotifications = ref(false);
const notifications = ref<Array<{id: number, title: string, message: string, time: string, read: boolean}>>([
    { id: 1, title: 'New fee payment', message: 'John Doe paid $500 for Term 1', time: '2 min ago', read: false },
    { id: 2, title: 'Low stock alert', message: 'A4 Paper Reams below threshold', time: '1 hour ago', read: false },
    { id: 3, title: 'Exam scheduled', message: 'Mathematics exam for Grade 10', time: '3 hours ago', read: true },
    { id: 4, title: 'New student enrolled', message: 'Jane Smith joined Grade 5', time: 'Yesterday', read: true },
]);

const unreadCount = computed(() => notifications.value.filter(n => !n.read).length);

function activeItemStyles(url: NonNullable<InertiaLinkProps['href']>) {
    return urlIsActive(url)
        ? 'text-foreground'
        : '';
}

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
        icon: LayoutGrid,
    },
];

const rightNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];

function handleSearchKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
        isSearchOpen.value = false;
        searchQuery.value = '';
    }
}

function handleGlobalKeydown(event: KeyboardEvent) {
    if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
        event.preventDefault();
        isSearchOpen.value = true;
        nextTick(() => {
            const input = document.getElementById('command-palette-input');
            input?.focus();
        });
    }
}

function performSearch(query: string) {
    if (!query.trim()) {
        searchResults.value = [];
        return;
    }

    const allItems: NavItem[] = [
        ...mainNavItems,
        { title: 'Students', href: route('students.index'), icon: 'users' },
        { title: 'Staff', href: route('staff.index'), icon: 'user-check' },
        { title: 'Fees', href: route('fees.index'), icon: 'banknote' },
        { title: 'Attendance', href: route('attendance.index'), icon: 'calendar-check' },
        { title: 'Exams', href: route('exams.index'), icon: 'file-text' },
        { title: 'Inventory', href: route('inventory.index'), icon: 'package' },
        { title: 'Settings', href: route('settings.index'), icon: 'settings' },
    ];

    const lowerQuery = query.toLowerCase();
    searchResults.value = allItems.filter(item =>
        item.title.toLowerCase().includes(lowerQuery)
    ).slice(0, 8);
}

function navigateToResult(item: NavItem) {
    router.visit(item.href);
    isSearchOpen.value = false;
    searchQuery.value = '';
}

function markAllRead() {
    notifications.value = notifications.value.map(n => ({ ...n, read: true }));
}

function markAsRead(id: number) {
    const notification = notifications.value.find(n => n.id === id);
    if (notification) notification.read = true;
}

function toggleTheme() {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
}

onMounted(() => {
    document.addEventListener('keydown', handleGlobalKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleGlobalKeydown);
});

watch(searchQuery, (val) => {
    performSearch(val);
});
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80">
            <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="mr-2 h-9 w-9"
                                aria-label="Open navigation menu"
                            >
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" class="w-[300px] p-6 animate-slide-in-from-left">
                            <SheetTitle class="sr-only">Navigation Menu</SheetTitle>
                            <SheetHeader class="flex justify-start text-left">
                                <AppLogoIcon class="size-6 fill-current text-foreground" />
                            </SheetHeader>
                            <div class="flex h-full flex-1 flex-col justify-between space-y-4 py-6">
                                <nav class="-mx-3 space-y-1">
                                    <Link
                                        v-for="item in mainNavItems"
                                        :key="item.title"
                                        :href="item.href"
                                        class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent transition-colors"
                                        :class="activeItemStyles(item.href)"
                                    >
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="h-5 w-5"
                                        />
                                        {{ item.title }}
                                    </Link>
                                </nav>
                                <div class="flex flex-col space-y-4">
                                    <a
                                        v-for="item in rightNavItems"
                                        :key="item.title"
                                        :href="toUrl(item.href)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center space-x-2 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
                                    >
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="h-5 w-5"
                                        />
                                        <span>{{ item.title }}</span>
                                    </a>
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="route('dashboard')" class="flex items-center gap-x-2" aria-label="Go to dashboard">
                    <AppLogo />
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <nav class="ml-10 flex h-full items-stretch" role="navigation" aria-label="Main navigation">
                        <ul class="flex h-full items-stretch space-x-1">
                            <li
                                v-for="(item, index) in mainNavItems"
                                :key="index"
                                class="relative flex h-full items-center"
                            >
                                <Link
                                    :class="[
                                        'h-9 cursor-pointer px-3 rounded-md transition-colors duration-150 flex items-center gap-2',
                                        activeItemStyles(item.href),
                                        'hover:bg-accent',
                                    ]"
                                    :href="item.href"
                                    :aria-current="urlIsActive(item.href) ? 'page' : undefined"
                                >
                                    <component
                                        v-if="item.icon"
                                        :is="item.icon"
                                        class="mr-2 h-4 w-4"
                                    />
                                    {{ item.title }}
                                </Link>
                                <div
                                    v-if="urlIsActive(item.href)"
                                    class="absolute bottom-0 left-1/2 h-0.5 w-4 -translate-x-1/2 bg-primary rounded-full animate-scale-in"
                                ></div>
                            </li>
                        </ul>
                    </nav>
                </div>

                <div class="ml-auto flex items-center space-x-2">
                    <!-- Command Palette Search -->
                    <div class="relative">
                        <TooltipProvider :delay-duration="0">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-9 w-9"
                                        @click="isSearchOpen = true"
                                        aria-label="Search (⌘K)"
                                    >
                                        <Search class="size-5 opacity-80 hover:opacity-100 transition-opacity" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent side="bottom" align="center">
                                    <p>Search (⌘K)</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>

                        <!-- Command Palette -->
                        <div v-if="isSearchOpen" class="fixed inset-0 z-50 flex items-start justify-center pt-20">
                            <div class="absolute inset-0 bg-background/80 backdrop-blur-sm" @click="isSearchOpen = false" />
                            <div class="relative w-full max-w-2xl bg-card border border-border rounded-xl shadow-xl overflow-hidden animate-scale-in">
                                <div class="p-4 border-b border-border">
                                    <div class="relative">
                                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                        <kbd class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted-foreground bg-muted px-1.5 py-0.5 rounded">
                                            ⌘K
                                        </kbd>
                                        <input
                                            id="command-palette-input"
                                            type="text"
                                            v-model="searchQuery"
                                            @keydown="handleSearchKeydown"
                                            placeholder="Search pages, actions..."
                                            class="w-full bg-transparent py-2 pl-10 pr-12 text-sm outline-none placeholder:text-muted-foreground"
                                            autocomplete="off"
                                        />
                                    </div>
                                </div>
                                <div class="max-h-96 overflow-y-auto">
                                    <div v-if="searchResults.length" class="p-2">
                                        <div class="px-3 py-1 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Results</div>
                                        <div class="space-y-1">
                                            <button
                                                v-for="result in searchResults"
                                                :key="result.title"
                                                @click="navigateToResult(result)"
                                                class="w-full flex items-center gap-3 px-3 py-2 text-sm text-left rounded-md hover:bg-accent transition-colors focus-ring"
                                            >
                                                <component
                                                    v-if="result.icon"
                                                    :is="typeof result.icon === 'string' ? null : result.icon"
                                                    class="h-4 w-4 text-muted-foreground"
                                                />
                                                <Icon
                                                    v-else-if="typeof result.icon === 'string'"
                                                    :icon="result.icon"
                                                    class="h-4 w-4 text-muted-foreground"
                                                />
                                                <span>{{ result.title }}</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div v-else-if="searchQuery" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                        <Loader2 class="h-6 w-6 animate-spin-slow mx-auto mb-2" />
                                        <p>No results for "{{ searchQuery }}"</p>
                                    </div>
                                    <div v-else class="px-4 py-8 text-center text-sm text-muted-foreground">
                                        <Command class="h-8 w-8 mx-auto mb-2 opacity-50" />
                                        <p>Press ⌘K to search...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="relative">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="relative h-9 w-9"
                                                aria-label="Notifications"
                                            >
                                                <Bell class="size-5 opacity-80 hover:opacity-100 transition-opacity" />
                                                <span
                                                    v-if="unreadCount > 0"
                                                    class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-[10px] font-medium text-destructive-foreground animate-scale-in"
                                                >
                                                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                                                </span>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent side="bottom" align="end">
                                            <p>Notifications</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-80 max-h-96 overflow-hidden">
                                <div class="flex items-center justify-between p-3 border-b border-border">
                                    <h3 class="font-semibold text-sm">Notifications</h3>
                                    <button
                                        v-if="unreadCount > 0"
                                        @click="markAllRead"
                                        class="text-xs text-primary hover:underline"
                                    >
                                        Mark all read
                                    </button>
                                </div>
                                <div class="max-h-[320px] overflow-y-auto">
                                    <div v-if="notifications.length" class="space-y-1 p-2">
                                        <div
                                            v-for="notification in notifications"
                                            :key="notification.id"
                                            :class="[
                                                'p-3 rounded-lg hover:bg-accent transition-colors',
                                                !notification.read ? 'bg-muted/50' : '',
                                            ]"
                                        >
                                            <div class="flex items-start gap-3">
                                                <div
                                                    v-if="!notification.read"
                                                    class="mt-1.5 w-2 h-2 bg-primary rounded-full shrink-0"
                                                />
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-foreground">{{ notification.title }}</p>
                                                    <p class="text-xs text-muted-foreground truncate">{{ notification.message }}</p>
                                                </div>
                                                <span class="text-xs text-muted-foreground shrink-0">{{ notification.time }}</span>
                                            </div>
                                            <button
                                                v-if="!notification.read"
                                                @click="markAsRead(notification.id)"
                                                class="mt-2 text-xs text-primary hover:underline"
                                            >
                                                Mark as read
                                            </button>
                                        </div>
                                    </div>
                                    <div v-else class="p-6 text-center text-sm text-muted-foreground">
                                        <Bell class="h-8 w-8 mx-auto mb-2 opacity-50" />
                                        <p>No notifications</p>
                                    </div>
                                </div>
                                <div class="p-3 border-t border-border">
                                    <a href="#" class="text-sm text-primary hover:underline block text-center">View all notifications</a>
                                </div>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <!-- Theme Toggle -->
                    <TooltipProvider :delay-duration="0">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-9 w-9"
                                    @click="toggleTheme"
                                    :aria-label="resolvedAppearance === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
                                >
                                    <span class="relative size-5">
                                        <Sun
                                            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 size-5 transition-all duration-300 ease-out"
                                            :class="resolvedAppearance === 'dark' ? 'rotate-90 scale-0 opacity-0' : 'rotate-0 scale-100 opacity-100'"
                                        />
                                        <Moon
                                            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 size-5 transition-all duration-300 ease-out"
                                            :class="resolvedAppearance === 'dark' ? 'rotate-0 scale-100 opacity-100' : '-rotate-90 scale-0 opacity-0'"
                                        />
                                    </span>
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="bottom" align="center">
                                <p>{{ resolvedAppearance === 'dark' ? 'Light mode' : 'Dark mode' }}</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>

                    <!-- User Menu -->
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar class="size-8 overflow-hidden rounded-full">
                                    <AvatarImage
                                        v-if="auth.user.avatar"
                                        :src="auth.user.avatar"
                                        :alt="auth.user.name"
                                    />
                                    <AvatarFallback class="rounded-lg bg-muted font-semibold text-muted-foreground">
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <div
            v-if="props.breadcrumbs.length > 1"
            class="flex w-full border-b border-sidebar-border/70"
        >
            <div
                class="mx-auto flex h-12 w-full items-center justify-start px-4 text-muted-foreground md:max-w-7xl"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>
</template>