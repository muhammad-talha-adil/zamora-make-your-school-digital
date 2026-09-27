<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useAppearance } from '@/composables/useAppearance';
import AuthFormWrapper from '@/components/auth/AuthFormWrapper.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import Icon from '@/components/Icon.vue';
import { ClipboardDocumentCheckIcon, ShieldCheckIcon, BuildingLibraryIcon } from '@heroicons/vue/24/outline';

interface School {
    name: string;
    logo_path?: string;
    slogan?: string;
}

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
    school?: School;
}>();

const { canResetPassword, canRegister } = props;

// `school` is shared globally via HandleInertiaRequests, but fall back to the
// shared page prop so the real school name always renders.
const school = computed<School | undefined>(() => props.school ?? (usePage().props.school as School | undefined));
const schoolName = computed(() => school.value?.name || 'School Management System');
const schoolLogo = computed(() => school.value?.logo_path || '/sample-logo.png');

const { resolvedAppearance, updateAppearance } = useAppearance();
const toggleTheme = () => updateAppearance(resolvedAppearance.value === 'light' ? 'dark' : 'light');

const email = ref('');
const password = ref('');
const remember = ref(false);
const showPassword = ref(false);

const loginRoute = () => route('login');
const forgotRoute = () => route('password.request');

const highlights = [
    {
        icon: ClipboardDocumentCheckIcon,
        title: 'Everything in one place',
        description: 'Attendance, exams, and fees, tracked together instead of scattered across registers.',
    },
    {
        icon: ShieldCheckIcon,
        title: 'Private by design',
        description: 'Student and staff records stay visible only to the people who need them.',
    },
    {
        icon: BuildingLibraryIcon,
        title: 'Built for our campus',
        description: 'Configured around how this school actually runs its day, not a generic template.',
    },
];
</script>

<template>
    <div class="grid min-h-[100dvh] w-full bg-background lg:grid-cols-2">
        <Head title="Log in" />

        <!-- Left: brand panel with a subtle geometric pattern, no external image dependency -->
        <div class="relative hidden overflow-hidden bg-gradient-to-br from-primary via-primary to-primary/80 p-10 text-primary-foreground lg:flex lg:flex-col lg:justify-between">
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.08]"
                style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 28px 28px;"
            />
            <div class="pointer-events-none absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-white/10 blur-2xl" />

            <div class="relative z-10 flex items-center gap-3">
                <img :src="schoolLogo" :alt="schoolName + ' logo'" class="h-10 w-10 rounded-full border border-white/30 object-cover shadow-sm" />
                <span class="text-base font-semibold">{{ schoolName }}</span>
            </div>

            <div class="relative z-10 max-w-md">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-primary-foreground/70">
                    {{ school?.slogan || 'School management, simplified' }}
                </p>
                <h1 class="mb-4 text-4xl font-bold leading-tight tracking-tight">
                    Everything your school needs, in one dashboard.
                </h1>
                <p class="text-primary-foreground/80">
                    Sign in to manage attendance, exams, fees, and staff from a single account.
                </p>
            </div>

            <div class="relative z-10 space-y-5">
                <div v-for="item in highlights" :key="item.title" class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/15">
                        <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold">{{ item.title }}</p>
                        <p class="text-sm text-primary-foreground/75">{{ item.description }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: sign-in panel -->
        <div class="relative flex flex-col items-center justify-center px-4 py-16 sm:px-8">
            <div class="absolute right-4 top-4 flex items-center gap-2 sm:right-6 sm:top-6">
                <Link :href="route('home')" class="hidden items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground lg:inline-flex">
                    <Icon icon="arrow-left" class="h-3.5 w-3.5" />
                    Back to website
                </Link>
                <button
                    type="button"
                    @click="toggleTheme"
                    class="rounded-md p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                    :aria-label="resolvedAppearance === 'light' ? 'Switch to dark mode' : 'Switch to light mode'"
                >
                    <Icon :icon="resolvedAppearance === 'light' ? 'sun' : 'moon'" class="h-4 w-4" />
                </button>
            </div>

            <Link :href="route('home')" class="mb-8 inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground lg:hidden">
                <Icon icon="arrow-left" class="h-3.5 w-3.5" />
                Back to website
            </Link>

            <div class="w-full max-w-sm">
                <div class="mb-6 flex items-center gap-3 lg:hidden">
                    <img :src="schoolLogo" :alt="schoolName + ' logo'" class="h-9 w-9 rounded-full border border-border object-cover shadow-sm" />
                    <span class="text-sm font-semibold text-foreground">{{ schoolName }}</span>
                </div>

                <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-primary">Welcome back</p>
                <h2 class="mb-2 text-3xl font-semibold tracking-tight text-foreground">Log in</h2>
                <p class="mb-8 text-sm text-muted-foreground">Sign in to access your dashboard.</p>

                <div v-if="status" class="mb-4 rounded-lg border border-border bg-muted px-3 py-2 text-center text-sm font-medium text-foreground">
                    {{ status }}
                </div>

                <AuthFormWrapper
                    :action="loginRoute()"
                    method="post"
                    :reset-on-success="['password']"
                    submit-label="Log in"
                    class="space-y-5"
                    v-slot="{ errors }"
                >
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="email" class="text-sm font-medium">Email address</Label>
                            <Input
                                id="email"
                                v-model="email"
                                type="email"
                                name="email"
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="email"
                                placeholder="Enter your email"
                                class="h-11"
                                :class="{ 'aria-invalid:border-destructive': errors.email }"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="space-y-2">
                            <Label for="password" class="text-sm font-medium">Password</Label>
                            <div class="relative">
                                <Input
                                    id="password"
                                    v-model="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    name="password"
                                    required
                                    :tabindex="2"
                                    autocomplete="current-password"
                                    placeholder="Enter your password"
                                    class="h-11 pr-10"
                                    :class="{ 'aria-invalid:border-destructive': errors.password }"
                                />
                                <button
                                    type="button"
                                    class="absolute right-0 top-0 flex h-full items-center px-3 text-muted-foreground transition-colors hover:text-foreground"
                                    @click="showPassword = !showPassword"
                                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                    tabindex="-1"
                                >
                                    <Icon :icon="showPassword ? 'eye-off' : 'eye'" class="h-4 w-4" />
                                </button>
                            </div>
                            <InputError :message="errors.password" />
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <Checkbox id="remember" v-model="remember" name="remember" :tabindex="3" />
                                <Label for="remember" class="cursor-pointer text-sm text-muted-foreground">Remember me</Label>
                            </div>
                            <TextLink v-if="canResetPassword" :href="forgotRoute()" class="text-sm font-medium" :tabindex="4">
                                Forgot password?
                            </TextLink>
                        </div>
                    </div>
                </AuthFormWrapper>

                <div v-if="canRegister" class="mt-6 text-center text-sm text-muted-foreground">
                    Don't have an account?
                    <TextLink :href="route('register')" :tabindex="5" class="ml-1">Sign up</TextLink>
                </div>
            </div>
        </div>
    </div>
</template>
