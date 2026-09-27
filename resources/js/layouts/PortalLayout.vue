<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
import { Menu, X, User, LogOut, Sun, Moon, ChevronDown, Home, BookOpen, Calendar, Wallet, Settings } from 'lucide-vue-next';
import AppShell from '@/components/layout/AppShell.vue';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/components/ui/sheet';
import { Breadcrumb, BreadcrumbList, BreadcrumbItem, BreadcrumbLink, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Separator } from '@/components/ui/separator';
import { useAppearance } from '@/composables/useAppearance';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

interface ChildOption {
    id: number;
    name: string | null;
    class?: string | null;
    section?: string | null;
    avatar?: string | null;
}

interface Props {
    children?: ChildOption[];
    currentChild?: ChildOption | null;
    breadcrumbs?: BreadcrumbItemType[];
    title?: string;
}

const props = defineProps<Props>();
const { resolvedAppearance, updateAppearance } = useAppearance();

const isMobileMenuOpen = ref(false);
const isChildSwitcherOpen = ref(false);
const isUserMenuOpen = ref(false);

const portalNavItems = [
    { href: route('portal.index'), label: 'Dashboard', icon: Home, exact: true },
    { href: route('portal.fees.index'), label: 'Fees', icon: Wallet },
    { href: route('portal.exams.index'), label: 'Exams', icon: BookOpen },
    { href: route('portal.attendance.index'), label: 'Attendance', icon: Calendar },
];

const currentRoute = computed(() => usePage().url);

const isActive = (href: string, exact = false) => {
    if (exact) return currentRoute.value === href;
    return currentRoute.value.startsWith(href);
};

const switchChild = (childId: number) => {
    const currentParams = new URLSearchParams(window.location.search);
    currentParams.set('student_id', String(childId));
    router.visit(`${window.location.pathname}?${currentParams.toString()}`, { preserveScroll: true });
    isChildSwitcherOpen.value = false;
};

const toggleTheme = () => {
    updateAppearance(resolvedAppearance.value === 'light' ? 'dark' : 'light');
};

const logout = () => {
    router.post(route('logout'));
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
    return () => document.removeEventListener('keydown', handleKeydown);
});

function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
        isMobileMenuOpen.value = false;
        isChildSwitcherOpen.value = false;
        isUserMenuOpen.value = false;
    }
}
</script>

<template>
<AppShell>
    <!-- Mobile Sidebar Sheet -->
    <Sheet v-model:open="isMobileMenuOpen">
        <SheetContent class="w-72 p-0" side="left">
            <SheetHeader class="px-4 py-3 border-b">
                <SheetTitle class="text-lg">Menu</SheetTitle>
                <SheetDescription class="text-xs">Navigate the portal</SheetDescription>
            </SheetHeader>
            <nav class="p-3 space-y-1">
                <Link
                    v-for="item in portalNavItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    :class="isActive(item.href, item.exact) ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent hover:text-foreground'"
                >
                    <item.icon :class="['w-5 h-5', isActive(item.href, item.exact) ? 'text-primary-foreground' : '']" />
                    {{ item.label }}
                </Link>
                <Separator />
                <button
                    @click="logout"
                    class="flex w-full items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-destructive hover:bg-accent transition-colors"
                >
                    <LogOut class="w-5 h-5" />
                    Logout
                </button>
            </nav>
        </SheetContent>
    </Sheet>

    <!-- Child Switcher Popover (Desktop) -->
    <DropdownMenu v-if="props.children && props.children.length > 1" v-model:open="isChildSwitcherOpen">
        <DropdownMenuTrigger as-child>
            <button
                class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-foreground hover:bg-accent transition-colors focus-ring"
                aria-label="Switch child"
            >
                <Avatar class="h-8 w-8">
                    <AvatarImage :src="props.currentChild?.avatar" alt="" />
                    <AvatarFallback class="text-xs">
                        {{ props.currentChild?.name?.charAt(0).toUpperCase() ?? '?' }}
                    </AvatarFallback>
                </Avatar>
                <span class="hidden sm:inline-block max-w-[140px] truncate">{{ props.currentChild?.name ?? 'Select child' }}</span>
                <ChevronDown class="w-4 h-4 text-muted-foreground" />
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-56 min-w-[220px] p-2 animate-popover-in" align="end" side="bottom" side-offset="4">
            <div class="px-2 py-1 text-xs font-semibold text-muted-foreground uppercase tracking-wider">Your Children</div>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                v-for="child in props.children"
                :key="child.id"
                :class="props.currentChild?.id === child.id ? 'bg-accent' : ''"
                @click="switchChild(child.id)"
                class="flex items-center gap-3 px-2 py-2.5 text-sm rounded-md focus-ring"
            >
                <Avatar class="h-7 w-7 shrink-0">
                    <AvatarImage :src="child.avatar" alt="" />
                    <AvatarFallback class="text-xs">{{ child.name?.charAt(0).toUpperCase() ?? '?' }}</AvatarFallback>
                </Avatar>
                <div class="flex-1 min-w-0">
                    <p class="font-medium truncate">{{ child.name }}</p>
                    <p v-if="child.class || child.section" class="text-xs text-muted-foreground truncate">
                        {{ [child.class, child.section].filter(Boolean).join(' · ') }}
                    </p>
                </div>
                <span v-if="props.currentChild?.id === child.id" class="text-primary text-xs font-medium">Current</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>

    <!-- Mobile Child Switcher (Sheet) -->
    <Sheet v-if="props.children && props.children.length > 1" v-model:open="isChildSwitcherOpen">
        <SheetContent class="w-full max-w-sm p-0 animate-popover-in" side="bottom">
            <SheetHeader class="px-4 py-3 border-b">
                <SheetTitle class="text-lg">Switch Child</SheetTitle>
                <SheetDescription class="text-xs">Select a child to view their portal</SheetDescription>
            </SheetHeader>
            <div class="p-2 space-y-1 max-h-60 overflow-y-auto">
                <button
                    v-for="child in props.children"
                    :key="child.id"
                    @click="switchChild(child.id)"
                    :class="[
                        'flex items-center gap-3 w-full px-3 py-3 text-left rounded-lg transition-colors',
                        props.currentChild?.id === child.id ? 'bg-accent text-foreground' : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                    ]"
                >
                    <Avatar class="h-9 w-9 shrink-0">
                        <AvatarImage :src="child.avatar" alt="" />
                        <AvatarFallback class="text-sm">{{ child.name?.charAt(0).toUpperCase() ?? '?' }}</AvatarFallback>
                    </Avatar>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium truncate">{{ child.name }}</p>
                        <p v-if="child.class || child.section" class="text-xs truncate" :class="props.currentChild?.id === child.id ? 'text-primary-foreground/70' : 'text-muted-foreground'">
                            {{ [child.class, child.section].filter(Boolean).join(' · ') }}
                        </p>
                    </div>
                    <span v-if="props.currentChild?.id === child.id" class="text-primary text-sm font-medium">Current</span>
                </button>
            </div>
        </SheetContent>
    </Sheet>

    <!-- Header -->
    <header class="sticky top-0 z-40 shadow-sm border-b bg-background/80 backdrop-blur-sm" :style="{ borderColor: 'var(--border)' }">
        <div class="flex h-14 items-center justify-between px-3 sm:px-4 gap-3">
            <!-- Left: Hamburger + Logo/Brand -->
            <div class="flex items-center gap-3 shrink-0">
                <button
                    @click="isMobileMenuOpen = true"
                    class="md:hidden p-2 rounded-lg hover:bg-accent transition-colors focus-ring"
                    aria-label="Open menu"
                >
                    <Menu class="w-5 h-5" />
                </button>
                <Link :href="route('portal.index')" class="flex items-center gap-2 shrink-0" aria-label="Portal Home">
                    <div class="h-8 w-8 rounded-lg bg-primary flex items-center justify-center">
                        <BookOpen class="w-5 h-5 text-primary-foreground" />
                    </div>
                    <span class="hidden sm:inline font-semibold text-lg text-foreground">Portal</span>
                </Link>
            </div>

            <!-- Center: Breadcrumbs -->
            <nav v-if="props.breadcrumbs && props.breadcrumbs.length > 0" class="hidden md:flex items-center gap-1.5 flex-1 px-4" aria-label="Breadcrumb">
                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>
                            <BreadcrumbLink :href="route('portal.index')" class="text-sm text-muted-foreground hover:text-foreground transition-colors">
                                Home
                            </BreadcrumbLink>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator :class="['w-4 h-4 text-muted-foreground']" />
                        <BreadcrumbItem v-for="(crumb, index) in props.breadcrumbs" :key="index">
                            <BreadcrumbLink
                                v-if="crumb.href && index < props.breadcrumbs.length - 1"
                                :href="crumb.href"
                                class="text-sm text-muted-foreground hover:text-foreground transition-colors"
                            >
                                {{ crumb.title }}
                            </BreadcrumbLink>
                            <BreadcrumbPage v-else class="text-sm font-medium text-foreground truncate max-w-[200px]">
                                {{ crumb.title }}
                            </BreadcrumbPage>
                            <BreadcrumbSeparator v-if="index < props.breadcrumbs.length - 1" :class="['w-4 h-4 text-muted-foreground']" />
                        </BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </nav>

            <!-- Right: Child Switcher + Theme + User Menu -->
            <div class="flex items-center gap-2 shrink-0">
                <!-- Child Switcher (Mobile trigger) -->
                <button
                    v-if="props.children && props.children.length > 1"
                    @click="isChildSwitcherOpen = true"
                    class="md:hidden flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-foreground hover:bg-accent transition-colors focus-ring"
                    aria-label="Switch child"
                >
                    <Avatar class="h-7 w-7">
                        <AvatarImage :src="props.currentChild?.avatar" alt="" />
                        <AvatarFallback class="text-xs">{{ props.currentChild?.name?.charAt(0).toUpperCase() ?? '?' }}</AvatarFallback>
                    </Avatar>
                    <span class="max-w-[100px] truncate">{{ props.currentChild?.name }}</span>
                </button>

                <!-- Theme Toggle -->
                <button
                    @click="toggleTheme"
                    class="p-2 rounded-lg hover:bg-accent transition-colors focus-ring"
                    aria-label="Toggle theme"
                >
                    <Sun v-if="resolvedAppearance === 'light'" class="w-5 h-5 text-muted-foreground" />
                    <Moon v-else class="w-5 h-5 text-muted-foreground" />
                </button>

                <!-- User Menu -->
                <DropdownMenu v-model:open="isUserMenuOpen">
                    <DropdownMenuTrigger as-child>
                        <button class="flex items-center gap-2 rounded-lg px-3 py-1.5 hover:bg-accent transition-colors focus-ring" aria-label="User menu">
                            <Avatar class="h-8 w-8">
                                <AvatarImage :src="usePage().props.auth?.user?.avatar" alt="" />
                                <AvatarFallback class="text-xs font-medium">
                                    {{ usePage().props.auth?.user?.name?.charAt(0).toUpperCase() ?? 'U' }}
                                </AvatarFallback>
                            </Avatar>
                            <span class="hidden sm:inline-block max-w-[120px] truncate font-medium text-sm">
                                {{ usePage().props.auth?.user?.name }}
                            </span>
                            <ChevronDown class="w-4 h-4 text-muted-foreground hidden sm:inline" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent class="w-48 min-w-[180px] p-1 animate-popover-in" align="end" side="bottom" side-offset="4">
                        <div class="px-3 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider border-b">Account</div>
                        <DropdownMenuItem @click="logout" class="flex items-center gap-2 px-2 py-2 text-sm text-destructive focus-ring">
                            <LogOut class="w-4 h-4" />
                            Logout
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto animate-fade-slide-up" :style="{ backgroundColor: 'var(--background)', color: 'var(--foreground)' }">
        <div class="p-4 sm:p-6">
            <slot />
        </div>
    </main>
</AppShell>
</template>

<style scoped>
/* Portal-specific animations */
@keyframes fade-slide-up {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-slide-up {
    animation: fade-slide-up var(--duration-normal) var(--ease-out) both;
}

@keyframes popover-in {
    from {
        opacity: 0;
        transform: translateY(-4px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.animate-popover-in {
    animation: popover-in var(--duration-fast) var(--ease-out) both;
}

/* Stagger delays */
.stagger-1 { animation-delay: 60ms; }
.stagger-2 { animation-delay: 120ms; }
.stagger-3 { animation-delay: 180ms; }
.stagger-4 { animation-delay: 240ms; }
.stagger-5 { animation-delay: 300ms; }

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    .animate-fade-slide-up,
    .animate-popover-in {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }
}

/* Focus visible styles for dropdown content */
[data-radix-dropdown-menu-content] {
    animation: popover-in var(--duration-fast) var(--ease-out) both;
}

/* Card hover lift */
.card-interactive {
    transition: transform var(--duration-fast) var(--ease-out), box-shadow var(--duration-fast) var(--ease-out);
}
.card-interactive:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}

/* Calendar day hover */
.calendar-day {
    transition: all var(--duration-instant) var(--ease-out);
    z-index: 10;
}
.calendar-day:hover {
    transform: scale(1.02);
    box-shadow: var(--shadow-md);
}

/* Cross-fade for tab switching */
.tab-content {
    animation: fade-in var(--duration-fast) var(--ease-out);
}

/* Skeleton shimmer */
@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

.skeleton-shimmer {
    background: linear-gradient(90deg, var(--muted) 25%, var(--accent) 50%, var(--muted) 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

@media (prefers-reduced-motion: reduce) {
    .skeleton-shimmer {
        animation: none;
    }
}
</style>