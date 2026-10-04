<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthIllustration from '@/components/auth/AuthIllustration.vue';
import SchoolBrandHeader from '@/components/auth/SchoolBrandHeader.vue';
import Icon from '@/components/Icon.vue';

interface School {
    name: string;
    logo_path?: string;
    tagline?: string;
}

type IllustrationType = 'login' | 'register' | 'forgot' | 'reset' | 'verify' | 'confirm' | '2fa';

const props = defineProps<{
    title: string;
    description: string;
    illustration: IllustrationType;
    school?: School;
}>();

// `school` is shared globally via HandleInertiaRequests, but not every auth
// page declares/forwards it as a prop - fall back to the shared page prop so
// the real school name always renders instead of the generic placeholder.
const school = computed<School | undefined>(() => props.school ?? (usePage().props.school as School | undefined));

const illustrationCopy: Record<IllustrationType, { heading: string; description: string }> = {
    login: { heading: 'Welcome Back', description: 'Your school dashboard at a glance' },
    register: { heading: 'Create Account', description: 'Grow with our school management platform' },
    forgot: { heading: 'Forgot Password', description: 'Secure password recovery' },
    reset: { heading: 'Reset Password', description: 'Set your new password securely' },
    verify: { heading: 'Verify Email', description: 'Verify your email to get started' },
    confirm: { heading: 'Confirm Access', description: 'Confirm your identity for sensitive actions' },
    '2fa': { heading: 'Two-Factor Auth', description: 'Two-factor authentication protects your account' },
};

const copy = computed(() => illustrationCopy[props.illustration]);

const badgeIcons: Record<IllustrationType, string[]> = {
    login: ['code', 'bell', 'lock'],
    register: ['users', 'sparkles', 'badge-check'],
    forgot: ['mail', 'key-round', 'shield'],
    reset: ['lock', 'refresh-cw', 'shield-check'],
    verify: ['mail-check', 'badge-check', 'sparkles'],
    confirm: ['shield-check', 'lock', 'check-circle'],
    '2fa': ['smartphone', 'shield', 'lock'],
};

const badges = computed(() => badgeIcons[props.illustration]);

const badgePositions = [
    'left-[6%] top-[12%]',
    'right-[4%] top-[20%]',
    'left-[10%] bottom-[14%]',
];
</script>

<template>
    <div class="min-h-screen bg-background relative">
        <!-- Subtle background pattern -->
        <div class="absolute inset-0 opacity-[0.02] dark:opacity-[0.05]" style="background-image: radial-gradient(circle at 1px 1px, rgba(0,0,0,.15) 1px, transparent 0); background-size: 20px 20px;"></div>

        <div class="relative flex min-h-screen items-center justify-center p-4 lg:p-8">
            <Link
                :href="route('home')"
                class="absolute left-4 top-4 lg:left-6 lg:top-6 z-20 inline-flex items-center gap-1.5 rounded-full border border-black/6 dark:border-white/10 bg-card/70 backdrop-blur px-3 py-1.5 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-card transition-colors shadow-sm"
            >
                <Icon icon="arrow-left" class="h-3.5 w-3.5" />
                Back to website
            </Link>

            <div class="auth-split-layout w-full max-w-6xl min-h-[560px] lg:min-h-[640px] rounded-2xl overflow-hidden bg-card border border-black/6 dark:border-white/10 shadow-2xl relative animate-in fade-in slide-in-from-bottom-4 duration-500 ease-out">
                <div class="grid lg:grid-cols-2 min-h-[560px] lg:min-h-[640px] relative">
                    <!-- Colored Panel: School Branding + Illustration (diagonal cut on lg+) -->
                    <div
                        class="hidden lg:flex lg:flex-col lg:justify-center lg:p-12 relative bg-gradient-to-br from-primary to-primary/70 text-primary-foreground overflow-hidden auth-diagonal-panel"
                    >
                        <div
                            class="pointer-events-none absolute inset-0 opacity-[0.08]"
                            style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 26px 26px;"
                        />
                        <div class="pointer-events-none absolute -bottom-20 -right-16 h-72 w-72 rounded-full bg-white/10 blur-2xl" />

                        <div class="relative z-10 flex flex-col items-center text-center space-y-6 w-full max-w-md mx-auto">
                            <!-- School Branding -->
                            <SchoolBrandHeader :school="school" class="w-full [&_*]:text-primary-foreground" />

                            <h1 class="text-3xl font-bold tracking-tight animate-in fade-in slide-in-from-bottom-2 duration-500 delay-100 ease-out">
                                {{ copy.heading }}
                            </h1>

                            <!-- Dynamic Illustration + floating icon badges -->
                            <div class="relative w-full aspect-square max-w-[300px] animate-in fade-in zoom-in-95 duration-700 delay-150 ease-out">
                                <AuthIllustration :type="props.illustration" class="w-full h-full" />

                                <div
                                    v-for="(icon, index) in badges"
                                    :key="icon"
                                    :class="['absolute flex h-11 w-11 items-center justify-center rounded-xl bg-white/90 dark:bg-card/90 shadow-lg backdrop-blur animate-in fade-in zoom-in-50 duration-500 ease-out', badgePositions[index]]"
                                    :style="{ animationDelay: `${300 + index * 120}ms` }"
                                >
                                    <Icon :icon="icon" class="h-5 w-5 text-primary" />
                                </div>
                            </div>

                            <!-- Illustration Description -->
                            <p class="text-primary-foreground/85 text-base font-medium">
                                {{ copy.description }}
                            </p>
                        </div>
                    </div>

                    <!-- Right Panel: Form -->
                    <div class="flex flex-col justify-center p-6 sm:p-8 lg:p-12 bg-card">
                        <div class="max-w-md mx-auto w-full space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-500 delay-100 ease-out">
                            <!-- Mobile Branding (hidden on lg where left panel takes over) -->
                            <SchoolBrandHeader :school="school" variant="form-header" class="lg:hidden" />

                            <div class="space-y-1.5 text-center lg:text-left">
                                <h2 class="text-2xl font-semibold tracking-tight text-foreground">
                                    {{ props.title }}
                                </h2>
                                <p class="text-sm text-muted-foreground">
                                    {{ props.description }}
                                </p>
                            </div>

                            <slot />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media (min-width: 1024px) {
    .auth-diagonal-panel {
        clip-path: polygon(0 0, 100% 0, 82% 100%, 0 100%);
    }
}
</style>
