<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { route } from 'ziggy-js';
import Icon from '@/components/Icon.vue';
import { useAppearance } from '@/composables/useAppearance';
import { useLenis } from '@/composables/useLenis';

const { resolvedAppearance, updateAppearance } = useAppearance();
const toggleTheme = () => updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');

interface SchoolProps {
    name: string;
    logo_path: string | null;
}

const page = usePage<{
    school: SchoolProps | null;
    name: string;
    public_staff?: unknown[];
    public_campuses?: unknown[];
}>();
const schoolName = computed(() => page.props.school?.name ?? page.props.name);
const logoPath = computed(() => page.props.school?.logo_path);

/** Staff/Campuses only appear in the nav when their homepage section actually renders (StaffSection/CampusesSection each skip themselves when there's no data), so the link never points at a missing anchor. */
const navItems = computed(() => [
    { label: 'About', href: '#about' },
    ...(page.props.public_staff?.length ? [{ label: 'Staff', href: '#staff' }] : []),
    ...(page.props.public_campuses?.length ? [{ label: 'Campuses', href: '#campuses' }] : []),
    { label: 'Admissions', href: '#admissions' },
    { label: 'Contact', href: '#contact' },
]);

const scrolled = ref(false);
const mobileOpen = ref(false);
/** Which nav section is currently in view, driven by IntersectionObserver below (never a scroll listener). */
const activeHref = ref<string | null>(null);

const onScroll = () => {
    scrolled.value = window.scrollY > 16;
};

let sectionObserver: IntersectionObserver | null = null;

onMounted(() => {
    window.addEventListener('scroll', onScroll, { passive: true });

    const sections = navItems.value
        .map((item) => document.querySelector<HTMLElement>(item.href))
        .filter((el): el is HTMLElement => el !== null);

    sectionObserver = new IntersectionObserver(
        (entries) => {
            const visible = entries.filter((entry) => entry.isIntersecting);
            if (visible.length) {
                activeHref.value = `#${visible[0].target.id}`;
            }
        },
        { rootMargin: '-40% 0px -55% 0px' },
    );

    sections.forEach((section) => sectionObserver?.observe(section));
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
    sectionObserver?.disconnect();
});

const { scrollTo: lenisScrollTo } = useLenis();

const scrollTo = (hash: string) => {
    mobileOpen.value = false;
    lenisScrollTo(hash);
};
</script>

<template>
    <header
        class="sticky top-0 z-50 w-full border-b transition-[background-color,border-color,box-shadow] duration-300"
        :class="scrolled ? 'border-border bg-background/90 shadow-sm backdrop-blur-md' : 'border-transparent bg-background/60 backdrop-blur-sm'"
    >
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <Link href="/" class="flex min-w-0 items-center gap-2.5">
                <img v-if="logoPath" :src="logoPath" :alt="schoolName" class="h-9 w-9 shrink-0 rounded-md object-cover" />
                <div v-else class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary text-sm font-bold text-primary-foreground">
                    {{ schoolName?.charAt(0) }}
                </div>
                <span class="truncate text-base font-semibold text-foreground">{{ schoolName }}</span>
            </Link>

            <nav class="hidden items-center gap-1 lg:flex">
                <button
                    v-for="item in navItems"
                    :key="item.href"
                    type="button"
                    class="nav-jump relative rounded-md px-3 py-2 text-sm font-medium transition-colors hover:text-foreground"
                    :class="activeHref === item.href ? 'text-foreground' : 'text-muted-foreground'"
                    @click="scrollTo(item.href)"
                >
                    {{ item.label }}
                    <span
                        class="absolute inset-x-3 -bottom-px h-0.5 scale-x-0 rounded-full bg-primary transition-transform duration-300"
                        :class="activeHref === item.href ? 'scale-x-100' : ''"
                    />
                </button>
            </nav>

            <div class="hidden items-center gap-2 lg:flex">
                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                    :aria-label="resolvedAppearance === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
                    @click="toggleTheme"
                >
                    <Icon :icon="resolvedAppearance === 'dark' ? 'sun' : 'moon'" class="h-4.5 w-4.5" />
                </button>
                <Link :href="route('login')" class="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground">
                    Login
                </Link>
                <button type="button" class="btn-primary h-9 px-4 text-sm" @click="scrollTo('#admissions')">
                    Apply Now
                </button>
            </div>

            <div class="flex items-center gap-1 lg:hidden">
                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-md text-muted-foreground"
                    :aria-label="resolvedAppearance === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
                    @click="toggleTheme"
                >
                    <Icon :icon="resolvedAppearance === 'dark' ? 'sun' : 'moon'" class="h-4.5 w-4.5" />
                </button>
                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-md text-foreground"
                    :aria-expanded="mobileOpen"
                    aria-label="Toggle menu"
                    @click="mobileOpen = !mobileOpen"
                >
                    <Icon :icon="mobileOpen ? 'x' : 'menu'" class="h-5 w-5" />
                </button>
            </div>
        </div>

        <div
            class="grid overflow-hidden border-t border-border bg-background transition-[grid-template-rows] duration-300 ease-out lg:hidden"
            :style="{ gridTemplateRows: mobileOpen ? '1fr' : '0fr' }"
        >
            <div class="min-h-0">
                <nav class="flex flex-col gap-1 px-4 py-3">
                    <button
                        v-for="item in navItems"
                        :key="item.href"
                        type="button"
                        class="rounded-md px-3 py-2.5 text-left text-sm font-medium transition-colors hover:bg-accent hover:text-foreground"
                        :class="activeHref === item.href ? 'bg-accent text-foreground' : 'text-muted-foreground'"
                        @click="scrollTo(item.href)"
                    >
                        {{ item.label }}
                    </button>
                    <div class="mt-2 flex items-center gap-2 border-t border-border pt-3">
                        <Link :href="route('login')" class="btn-outline h-9 flex-1 text-sm">Login</Link>
                        <button type="button" class="btn-primary h-9 flex-1 text-sm" @click="scrollTo('#admissions')">Apply Now</button>
                    </div>
                </nav>
            </div>
        </div>
    </header>
</template>

<style scoped>
/* Small playful bounce on hover, feedback that the label is clickable. Transform/opacity only (GPU-cheap). */
.nav-jump:hover {
    animation: nav-jump 0.4s ease-in-out;
}

@keyframes nav-jump {
    0%,
    100% {
        transform: translateY(0);
    }
    30% {
        transform: translateY(-3px);
    }
    60% {
        transform: translateY(0.5px);
    }
}

@media (prefers-reduced-motion: reduce) {
    .nav-jump:hover {
        animation: none;
    }
}
</style>
