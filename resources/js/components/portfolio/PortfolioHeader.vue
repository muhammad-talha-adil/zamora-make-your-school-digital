<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { SunIcon, MoonIcon } from '@heroicons/vue/24/outline';
import { route } from 'ziggy-js';
import { useAppearance } from '../../composables/useAppearance';
import { usePage } from '@inertiajs/vue3';
import type { AppPageProps } from '@/types';
import { computed } from 'vue';

const page = usePage<AppPageProps>();
const schoolName = computed(() => page.props.name);
const logoPath = computed(() => page.props.school?.logo_path || '/sample-logo.png');

const { resolvedAppearance, updateAppearance } = useAppearance();

const toggleTheme = () => {
    updateAppearance(resolvedAppearance.value === 'light' ? 'dark' : 'light');
};
</script>

<template>
    <header class="fixed top-0 z-50 w-full border-b border-border bg-card shadow-sm">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-4">
                <!-- Logo/Title -->
                <div class="flex items-center">
                    <img :src="logoPath" alt="Logo" class="h-8 w-8 mr-2" />
                    <h1 class="text-xl font-bold text-card-foreground">
                        {{ schoolName }}
                    </h1>
                </div>

                <!-- Desktop Nav -->
                <nav class="hidden space-x-8 md:flex">
                    <Link
                        href="/"
                        class="text-card-foreground transition-colors hover:text-primary"
                    >
                        Home
                    </Link>
                    <Link
                        href="/about"
                        class="text-card-foreground transition-colors hover:text-primary"
                    >
                        About
                    </Link>
                    <Link
                        href="/contact"
                        class="text-card-foreground transition-colors hover:text-primary"
                    >
                        Contact
                    </Link>
                    <Link
                        href="/staff"
                        class="text-card-foreground transition-colors hover:text-primary"
                    >
                        Staff
                    </Link>
                    <Link
                        href="/performance"
                        class="text-card-foreground transition-colors hover:text-primary"
                    >
                        Performance
                    </Link>
                </nav>

                <!-- Theme Toggle and Login Button -->
                <div class="flex items-center space-x-4">
                    <button
                        @click="toggleTheme"
                        class="rounded-md p-2 text-card-foreground transition-colors hover:bg-accent"
                    >
                        <SunIcon
                            v-if="resolvedAppearance === 'light'"
                            class="h-5 w-5"
                        />
                        <MoonIcon v-else class="h-5 w-5" />
                    </button>
                    <Link
                        :href="route('login')"
                        class="rounded-md bg-primary px-4 py-2 text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        Login
                    </Link>
                </div>
            </div>
        </div>
    </header>
</template>
