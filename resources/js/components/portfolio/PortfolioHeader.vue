<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { route } from 'ziggy-js';
import Icon from '@/components/Icon.vue';

interface SchoolProps {
    name: string;
    logo_path: string | null;
}

const page = usePage<{ school: SchoolProps | null; name: string }>();
const schoolName = computed(() => page.props.school?.name ?? page.props.name);
const logoPath = computed(() => page.props.school?.logo_path);

const navItems = [
    { label: 'About', href: '#about' },
    { label: 'Academics', href: '#academics' },
    { label: 'Campus Life', href: '#campus-life' },
    { label: 'Admissions', href: '#admissions' },
    { label: 'Contact', href: '#contact' },
];

const scrolled = ref(false);
const mobileOpen = ref(false);

const onScroll = () => {
    scrolled.value = window.scrollY > 16;
};

onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }));
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));

const scrollTo = (hash: string) => {
    mobileOpen.value = false;
    const el = document.querySelector(hash);
    el?.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
                    class="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                    @click="scrollTo(item.href)"
                >
                    {{ item.label }}
                </button>
            </nav>

            <div class="hidden items-center gap-2 lg:flex">
                <Link :href="route('login')" class="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground">
                    Login
                </Link>
                <button type="button" class="btn-primary h-9 px-4 text-sm" @click="scrollTo('#admissions')">
                    Apply Now
                </button>
            </div>

            <button
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-md text-foreground lg:hidden"
                :aria-expanded="mobileOpen"
                aria-label="Toggle menu"
                @click="mobileOpen = !mobileOpen"
            >
                <Icon :icon="mobileOpen ? 'x' : 'menu'" class="h-5 w-5" />
            </button>
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
                        class="rounded-md px-3 py-2.5 text-left text-sm font-medium text-muted-foreground hover:bg-accent hover:text-foreground"
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
