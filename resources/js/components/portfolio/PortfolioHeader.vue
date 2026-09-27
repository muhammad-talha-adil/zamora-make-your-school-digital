<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { SunIcon, MoonIcon, Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline';
import { useAppearance } from '@/composables/useAppearance';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const schoolName = computed(() => page.props.name as string);
const logoPath = computed(() => page.props.school?.logo_path || '/sample-logo.png');
const publicWebsiteEnabled = computed(() => page.props.school?.website_enabled ?? true);

const { resolvedAppearance, updateAppearance } = useAppearance();
const mobileMenuOpen = ref(false);

const toggleTheme = () => {
    updateAppearance(resolvedAppearance.value === 'light' ? 'dark' : 'light');
};

const navigation = [
    { name: 'Home', href: route('home') },
    { name: 'About', href: route('about') },
    { name: 'Academics', href: route('academics') },
    { name: 'Admissions', href: route('admissions') },
    { name: 'Contact', href: route('contact') },
] as const;
</script>

<template>
    <header class="fixed top-0 z-50 w-full border-b border-border bg-card/95 backdrop-blur supports-[backdrop-filter]:bg-card/80 shadow-sm">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap gap-2 items-center justify-between h-16 md:h-20">
                <!-- Logo/Title -->
                <div class="flex items-center">
                    <img :src="logoPath" alt="" class="h-8 w-8 mr-2" aria-hidden="true" />
                    <h1 class="text-xl font-bold text-card-foreground">
                        {{ schoolName }}
                    </h1>
                </div>

                <!-- Desktop Nav -->
                <nav v-if="publicWebsiteEnabled" class="hidden md:flex items-center space-x-8" aria-label="Main navigation">
                    <Link v-for="item in navigation" :key="item.name" :href="item.href"
                          class="text-sm font-medium text-card-foreground transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 rounded-md px-2 py-1">
                        {{ item.name }}
                    </Link>
                </nav>

                <!-- Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Theme Toggle -->
                    <button @click="toggleTheme"
                            class="rounded-md p-2 text-card-foreground transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                            :aria-label="resolvedAppearance === 'light' ? 'Switch to dark mode' : 'Switch to light mode'">
                        <SunIcon v-if="resolvedAppearance === 'light'" class="h-5 w-5" aria-hidden="true" />
                        <MoonIcon v-else class="h-5 w-5" aria-hidden="true" />
                    </button>

                    <!-- Login Button -->
                    <Link :href="route('login')" class="hidden sm:inline-flex btn-primary">
                        Login
                    </Link>

                    <!-- Mobile Menu Button -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen"
                            class="md:hidden rounded-md p-2 text-card-foreground transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                            :aria-label="mobileMenuOpen ? 'Close menu' : 'Open menu'"
                            :aria-expanded="mobileMenuOpen">
                        <Bars3Icon v-if="!mobileMenuOpen" class="h-6 w-6" aria-hidden="true" />
                        <XMarkIcon v-else class="h-6 w-6" aria-hidden="true" />
                    </button>
                </div>
            </div>

            <!-- Mobile Nav -->
            <div v-show="mobileMenuOpen" class="md:hidden py-4 border-t border-border animate-slide-down" role="navigation" aria-label="Mobile navigation">
                <div class="flex flex-col space-y-2">
                    <Link v-for="item in navigation" :key="item.name" :href="item.href"
                          class="text-base font-medium text-card-foreground px-2 py-2 rounded-md hover:bg-accent transition-colors"
                          @click="mobileMenuOpen = false">
                        {{ item.name }}
                    </Link>
                    <Link :href="route('login')" class="btn-primary text-center mt-2" @click="mobileMenuOpen = false">
                        Login
                    </Link>
                </div>
            </div>
        </div>
    </header>
</template>

<style scoped>
@media (prefers-reduced-motion: no-preference) {
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-slide-down { animation: slideDown 0.2s ease-out; }
}
</style>