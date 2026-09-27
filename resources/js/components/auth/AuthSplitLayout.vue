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

const illustrationDescriptions: Record<IllustrationType, string> = {
    login: 'Your school dashboard at a glance',
    register: 'Grow with our school management platform',
    forgot: 'Secure password recovery',
    reset: 'Set your new password securely',
    verify: 'Verify your email to get started',
    confirm: 'Confirm your identity for sensitive actions',
    '2fa': 'Two-factor authentication protects your account',
};
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

            <div class="auth-split-layout w-full max-w-6xl min-h-[560px] lg:min-h-[640px] rounded-2xl overflow-hidden bg-card/50 backdrop-blur-[24px] border border-black/6 dark:border-white/10 shadow-2xl relative animate-in fade-in slide-in-from-bottom-4 duration-500 ease-out" style="backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);">
                <!-- Subtle radial highlight for light mode -->
                <div class="absolute inset-0 bg-gradient-radial from-card/10 via-transparent to-transparent rounded-2xl pointer-events-none"></div>

                <div class="grid lg:grid-cols-2 min-h-[560px] lg:min-h-[640px] relative">
                    <!-- Left Panel: School Branding + Illustration -->
                    <div class="hidden lg:flex lg:flex-col lg:justify-center lg:p-12 relative bg-primary/5 dark:bg-primary/10">
                        <!-- Vertical Divider (Soft Gradient) -->
                        <div class="absolute right-0 top-0 bottom-0 w-px bg-gradient-to-b from-transparent via-black/6 to-transparent"></div>

                        <div class="relative z-10 flex flex-col items-center text-center space-y-8 w-full max-w-md mx-auto">
                            <!-- School Branding -->
                            <SchoolBrandHeader :school="school" class="w-full" />

                            <!-- Dynamic Illustration -->
                            <div class="relative w-full aspect-square max-w-[320px] animate-in fade-in zoom-in-95 duration-700 delay-150 ease-out">
                                <AuthIllustration :type="props.illustration" class="w-full h-full" />
                            </div>

                            <!-- Illustration Description -->
                            <p class="text-muted-foreground text-lg font-medium">
                                {{ illustrationDescriptions[props.illustration] }}
                            </p>
                        </div>
                    </div>

                    <!-- Right Panel: Form -->
                    <div class="flex flex-col justify-center p-6 sm:p-8 lg:p-12 bg-card/35">
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