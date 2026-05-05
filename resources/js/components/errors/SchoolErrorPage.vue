<script setup lang="ts">
import { computed } from 'vue';
import { route } from 'ziggy-js';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';

interface Props {
    status: number;
}

const props = defineProps<Props>();

const contentByStatus: Record<number, {
    title: string;
    description: string;
    detail: string;
    sceneTitle: string;
    sceneNote: string;
    badgeIcon: string;
    badgeClass: string;
    glowClass: string;
    primaryLabel: string;
    primaryAction: 'home' | 'login' | 'reload';
    secondaryLabel: string;
    secondaryAction: 'back' | 'home' | 'reload';
}> = {
    401: {
        title: 'Please sign in first',
        description: 'This page is only available after you sign in to your school account.',
        detail: 'Go to the login page and sign in, then try again.',
        sceneTitle: 'Reception needs your sign-in',
        sceneNote: 'Your school records are safe. We just need to confirm who is accessing them.',
        badgeIcon: 'badge-alert',
        badgeClass: 'bg-amber-500/15 text-amber-700 ring-amber-500/25 dark:bg-amber-400/15 dark:text-amber-200 dark:ring-amber-400/25',
        glowClass: 'from-amber-500/25 via-orange-500/10 to-transparent dark:from-amber-300/20 dark:via-orange-300/10 dark:to-transparent',
        primaryLabel: 'Go to Login',
        primaryAction: 'login',
        secondaryLabel: 'Go Home',
        secondaryAction: 'home',
    },
    403: {
        title: 'You do not have access to this page',
        description: 'This area is reserved for specific staff roles or permissions.',
        detail: 'If you believe this is a mistake, please contact your school administrator.',
        sceneTitle: 'This room is currently restricted',
        sceneNote: 'Some pages are only available to selected school staff members.',
        badgeIcon: 'shield-ban',
        badgeClass: 'bg-rose-500/15 text-rose-700 ring-rose-500/25 dark:bg-rose-400/15 dark:text-rose-200 dark:ring-rose-400/25',
        glowClass: 'from-rose-500/25 via-pink-500/10 to-transparent dark:from-rose-300/20 dark:via-pink-300/10 dark:to-transparent',
        primaryLabel: 'Go Home',
        primaryAction: 'home',
        secondaryLabel: 'Go Back',
        secondaryAction: 'back',
    },
    404: {
        title: 'We could not find that page',
        description: 'The page may have been moved, renamed, or the address may be incorrect.',
        detail: 'Please return to the dashboard or go back to the previous page.',
        sceneTitle: 'This school hallway is empty',
        sceneNote: 'It looks like the room you wanted is not here anymore.',
        badgeIcon: 'search-x',
        badgeClass: 'bg-sky-500/15 text-sky-700 ring-sky-500/25 dark:bg-sky-400/15 dark:text-sky-200 dark:ring-sky-400/25',
        glowClass: 'from-sky-500/25 via-cyan-500/10 to-transparent dark:from-sky-300/20 dark:via-cyan-300/10 dark:to-transparent',
        primaryLabel: 'Go Home',
        primaryAction: 'home',
        secondaryLabel: 'Go Back',
        secondaryAction: 'back',
    },
    419: {
        title: 'Your session has expired',
        description: 'This usually happens if the page has been open for a while without activity.',
        detail: 'Reload the page and try again. You may need to sign in once more.',
        sceneTitle: 'The attendance bell timed out',
        sceneNote: 'Refreshing the page usually gets everything working again.',
        badgeIcon: 'timer-reset',
        badgeClass: 'bg-yellow-500/15 text-yellow-700 ring-yellow-500/25 dark:bg-yellow-400/15 dark:text-yellow-200 dark:ring-yellow-400/25',
        glowClass: 'from-yellow-500/25 via-amber-500/10 to-transparent dark:from-yellow-300/20 dark:via-amber-300/10 dark:to-transparent',
        primaryLabel: 'Reload Page',
        primaryAction: 'reload',
        secondaryLabel: 'Go Home',
        secondaryAction: 'home',
    },
    429: {
        title: 'Please slow down for a moment',
        description: 'Too many actions were sent in a short time, so the system paused briefly to stay stable.',
        detail: 'Wait a few seconds and try again.',
        sceneTitle: 'The front desk is processing requests',
        sceneNote: 'A short pause will help your next action go through normally.',
        badgeIcon: 'hourglass',
        badgeClass: 'bg-orange-500/15 text-orange-700 ring-orange-500/25 dark:bg-orange-400/15 dark:text-orange-200 dark:ring-orange-400/25',
        glowClass: 'from-orange-500/25 via-amber-500/10 to-transparent dark:from-orange-300/20 dark:via-amber-300/10 dark:to-transparent',
        primaryLabel: 'Try Again',
        primaryAction: 'reload',
        secondaryLabel: 'Go Back',
        secondaryAction: 'back',
    },
    500: {
        title: 'Something went wrong on our side',
        description: 'The system ran into an unexpected issue while opening this page.',
        detail: 'Please try again. If the problem continues, contact your school support team.',
        sceneTitle: 'The office is fixing a temporary problem',
        sceneNote: 'Your data has not been lost. This is usually temporary.',
        badgeIcon: 'wrench',
        badgeClass: 'bg-red-500/15 text-red-700 ring-red-500/25 dark:bg-red-400/15 dark:text-red-200 dark:ring-red-400/25',
        glowClass: 'from-red-500/25 via-rose-500/10 to-transparent dark:from-red-300/20 dark:via-rose-300/10 dark:to-transparent',
        primaryLabel: 'Try Again',
        primaryAction: 'reload',
        secondaryLabel: 'Go Home',
        secondaryAction: 'home',
    },
    503: {
        title: 'The system is temporarily unavailable',
        description: 'We are likely performing an update or brief maintenance.',
        detail: 'Please wait a little while and then try again.',
        sceneTitle: 'The school building is being prepared',
        sceneNote: 'Everything should be back shortly once the update is complete.',
        badgeIcon: 'construction',
        badgeClass: 'bg-slate-500/15 text-slate-700 ring-slate-500/25 dark:bg-slate-300/15 dark:text-slate-100 dark:ring-slate-300/25',
        glowClass: 'from-slate-500/25 via-indigo-500/10 to-transparent dark:from-slate-300/20 dark:via-indigo-300/10 dark:to-transparent',
        primaryLabel: 'Try Again',
        primaryAction: 'reload',
        secondaryLabel: 'Go Home',
        secondaryAction: 'home',
    },
};

const pageContent = computed(() => contentByStatus[props.status] ?? contentByStatus[500]);

const performAction = (action: 'home' | 'login' | 'reload' | 'back') => {
    if (action === 'home') {
        window.location.href = route('home');
        return;
    }

    if (action === 'login') {
        window.location.href = route('login');
        return;
    }

    if (action === 'reload') {
        window.location.reload();
        return;
    }

    window.history.back();
};

const doorLabel = computed(() => {
    if (props.status === 404) return 'Missing Room';
    if (props.status === 403) return 'Restricted';
    if (props.status === 503) return 'Updating';
    return 'Office';
});

const windowsClass = computed(() => {
    if (props.status === 500) return 'fill-red-200 dark:fill-red-300/70';
    if (props.status === 429) return 'fill-orange-200 dark:fill-orange-300/70';
    if (props.status === 419) return 'fill-yellow-200 dark:fill-yellow-300/70';
    return 'fill-sky-200 dark:fill-sky-300/70';
});
</script>

<template>
    <div class="relative min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.12),_transparent_35%),linear-gradient(180deg,_#f6fbff_0%,_#eef4ff_44%,_#f8fafc_100%)] px-4 py-8 text-slate-900 dark:bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.18),_transparent_30%),linear-gradient(180deg,_#08111f_0%,_#0b1324_42%,_#0f172a_100%)] dark:text-slate-100 sm:px-6 lg:px-8">
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute left-[-8rem] top-[-6rem] h-64 w-64 rounded-full bg-sky-400/15 blur-3xl dark:bg-sky-500/10"></div>
            <div :class="['absolute right-[-5rem] top-20 h-72 w-72 rounded-full blur-3xl', pageContent.glowClass]"></div>
            <div class="absolute bottom-[-8rem] left-1/2 h-80 w-80 -translate-x-1/2 rounded-full bg-indigo-400/10 blur-3xl dark:bg-indigo-500/10"></div>
        </div>

        <div class="relative mx-auto flex min-h-[calc(100vh-4rem)] max-w-6xl items-center">
            <div class="grid w-full gap-6 overflow-hidden rounded-[2rem] border border-white/70 bg-white/88 p-6 shadow-[0_30px_80px_rgba(15,23,42,0.14)] backdrop-blur md:grid-cols-[1.05fr_0.95fr] md:p-10 dark:border-white/10 dark:bg-slate-950/70 dark:shadow-[0_30px_90px_rgba(2,8,23,0.55)]">
                <section class="relative overflow-hidden rounded-[1.75rem] border border-slate-200/80 bg-[linear-gradient(180deg,_rgba(255,255,255,0.85)_0%,_rgba(234,246,255,0.85)_100%)] p-6 dark:border-slate-800 dark:bg-[linear-gradient(180deg,_rgba(15,23,42,0.92)_0%,_rgba(14,30,56,0.92)_100%)] md:p-8">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-lg shadow-slate-900/15 dark:bg-white dark:text-slate-900">
                            <Icon icon="school" class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Zamora School Portal</p>
                            <p class="text-base font-semibold text-slate-900 dark:text-white">{{ pageContent.sceneTitle }}</p>
                        </div>
                    </div>

                    <div class="relative isolate">
                        <div class="absolute left-8 right-8 top-8 h-32 rounded-full bg-sky-300/25 blur-3xl dark:bg-sky-400/10"></div>
                        <svg viewBox="0 0 360 250" class="relative z-10 w-full">
                            <defs>
                                <linearGradient id="campus-sky-light" x1="0%" x2="0%" y1="0%" y2="100%">
                                    <stop offset="0%" stop-color="#eef7ff" />
                                    <stop offset="100%" stop-color="#dbeafe" />
                                </linearGradient>
                                <linearGradient id="campus-ground-light" x1="0%" x2="0%" y1="0%" y2="100%">
                                    <stop offset="0%" stop-color="#d9f99d" />
                                    <stop offset="100%" stop-color="#bbf7d0" />
                                </linearGradient>
                                <linearGradient id="campus-building" x1="0%" x2="0%" y1="0%" y2="100%">
                                    <stop offset="0%" stop-color="#ffffff" />
                                    <stop offset="100%" stop-color="#dbe7f5" />
                                </linearGradient>
                            </defs>

                            <rect x="8" y="12" width="344" height="168" rx="28" fill="url(#campus-sky-light)" class="dark:fill-slate-900" />
                            <circle cx="72" cy="54" r="22" fill="#fde68a" class="dark:fill-slate-700" />
                            <path d="M36 186C54 168 84 162 114 170C142 178 161 177 188 166C220 152 250 150 281 160C307 168 329 184 344 206H22C24 198 30 192 36 186Z" fill="url(#campus-ground-light)" class="dark:fill-slate-800" />

                            <rect x="92" y="70" width="176" height="118" rx="16" fill="url(#campus-building)" class="dark:fill-slate-800 dark:stroke-slate-700" stroke="#bfd3ea" />
                            <rect x="142" y="46" width="76" height="34" rx="12" fill="#1d4ed8" class="dark:fill-sky-500" />
                            <text x="180" y="67" text-anchor="middle" font-size="14" font-weight="700" fill="#ffffff">SCHOOL</text>

                            <rect x="110" y="92" width="26" height="26" rx="6" :class="windowsClass" />
                            <rect x="148" y="92" width="26" height="26" rx="6" :class="windowsClass" />
                            <rect x="186" y="92" width="26" height="26" rx="6" :class="windowsClass" />
                            <rect x="224" y="92" width="26" height="26" rx="6" :class="windowsClass" />
                            <rect x="110" y="128" width="26" height="26" rx="6" :class="windowsClass" />
                            <rect x="224" y="128" width="26" height="26" rx="6" :class="windowsClass" />

                            <rect x="156" y="124" width="48" height="64" rx="10" fill="#1e293b" class="dark:fill-slate-700" />
                            <rect x="168" y="138" width="24" height="22" rx="6" fill="#93c5fd" class="dark:fill-sky-300/70" />

                            <path d="M60 198H302" stroke="#94a3b8" stroke-width="6" stroke-linecap="round" class="dark:stroke-slate-700" />
                            <path d="M82 198L106 178" stroke="#94a3b8" stroke-width="4" stroke-linecap="round" class="dark:stroke-slate-700" />
                            <path d="M278 198L254 178" stroke="#94a3b8" stroke-width="4" stroke-linecap="round" class="dark:stroke-slate-700" />

                            <g transform="translate(250 30)">
                                <rect x="0" y="0" width="66" height="66" rx="20" fill="#ffffff" fill-opacity="0.94" class="dark:fill-slate-950" />
                                <rect x="0.5" y="0.5" width="65" height="65" rx="19.5" fill="none" stroke="#cbd5e1" class="dark:stroke-slate-800" />
                                <foreignObject x="15" y="15" width="36" height="36">
                                    <div xmlns="http://www.w3.org/1999/xhtml" class="flex h-full w-full items-center justify-center">
                                        <div :class="['flex h-10 w-10 items-center justify-center rounded-2xl ring-1', pageContent.badgeClass]">
                                            <Icon :icon="pageContent.badgeIcon" class="h-5 w-5" />
                                        </div>
                                    </div>
                                </foreignObject>
                            </g>

                            <g v-if="status === 404">
                                <path d="M216 171C232 168 242 171 252 179" stroke="#0ea5e9" stroke-width="4" stroke-linecap="round" stroke-dasharray="7 7" class="dark:stroke-sky-300" />
                                <circle cx="260" cy="183" r="12" fill="#ffffff" stroke="#0ea5e9" stroke-width="4" class="dark:fill-slate-900 dark:stroke-sky-300" />
                                <path d="M268 191L279 202" stroke="#0ea5e9" stroke-width="4" stroke-linecap="round" class="dark:stroke-sky-300" />
                            </g>

                            <g v-if="status === 403">
                                <rect x="152" y="144" width="56" height="10" rx="5" fill="#fb7185" class="dark:fill-rose-300" />
                            </g>

                            <g v-if="status === 419">
                                <circle cx="63" cy="120" r="18" fill="#ffffff" stroke="#f59e0b" stroke-width="4" class="dark:fill-slate-900 dark:stroke-yellow-300" />
                                <path d="M63 109V121L71 126" stroke="#f59e0b" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" class="dark:stroke-yellow-300" />
                            </g>

                            <g v-if="status === 429">
                                <path d="M46 144H80" stroke="#f97316" stroke-width="6" stroke-linecap="round" class="dark:stroke-orange-300" />
                                <path d="M52 130H74" stroke="#f97316" stroke-width="6" stroke-linecap="round" class="dark:stroke-orange-300" />
                                <path d="M58 116H68" stroke="#f97316" stroke-width="6" stroke-linecap="round" class="dark:stroke-orange-300" />
                            </g>

                            <g v-if="status === 500 || status === 503">
                                <path d="M286 134L304 116" stroke="#ef4444" stroke-width="4" stroke-linecap="round" class="dark:stroke-red-300" />
                                <path d="M300 134L318 116" stroke="#ef4444" stroke-width="4" stroke-linecap="round" class="dark:stroke-red-300" />
                                <path v-if="status === 503" d="M280 154H322" stroke="#64748b" stroke-width="6" stroke-linecap="round" stroke-dasharray="7 7" class="dark:stroke-slate-500" />
                            </g>

                            <text x="180" y="215" text-anchor="middle" font-size="14" font-weight="700" fill="#334155" class="dark:fill-slate-200">{{ doorLabel }}</text>
                        </svg>
                    </div>

                    <div class="mt-6 rounded-3xl border border-white/70 bg-white/70 p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ pageContent.sceneNote }}</p>
                    </div>
                </section>

                <section class="flex flex-col justify-center rounded-[1.75rem] border border-slate-200/70 bg-white/80 p-6 dark:border-slate-800 dark:bg-slate-950/50 md:p-8">
                    <div :class="['mb-6 inline-flex w-fit items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold ring-1', pageContent.badgeClass]">
                        <Icon :icon="pageContent.badgeIcon" class="h-4 w-4" />
                        Error {{ status }}
                    </div>

                    <h1 class="text-4xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-5xl">
                        {{ pageContent.title }}
                    </h1>

                    <p class="mt-4 text-lg leading-8 text-slate-600 dark:text-slate-300">
                        {{ pageContent.description }}
                    </p>

                    <div class="mt-6 rounded-3xl border border-slate-200/80 bg-slate-50/90 p-5 dark:border-slate-800 dark:bg-slate-900/70">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900">
                                <Icon icon="info" class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-base font-semibold text-slate-900 dark:text-white">What you can do next</p>
                                <p class="mt-1 text-sm leading-7 text-slate-600 dark:text-slate-300">
                                    {{ pageContent.detail }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <Button
                            size="lg"
                            class="min-w-[12rem] rounded-2xl bg-slate-950 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-100"
                            @click="performAction(pageContent.primaryAction)"
                        >
                            <Icon
                                :icon="pageContent.primaryAction === 'reload' ? 'refresh-cw' : pageContent.primaryAction === 'login' ? 'log-in' : 'home'"
                                class="h-4 w-4"
                            />
                            {{ pageContent.primaryLabel }}
                        </Button>

                        <Button
                            size="lg"
                            variant="outline"
                            class="min-w-[12rem] rounded-2xl border-slate-300 bg-white/80 dark:border-slate-700 dark:bg-slate-900/50"
                            @click="performAction(pageContent.secondaryAction)"
                        >
                            <Icon
                                :icon="pageContent.secondaryAction === 'back' ? 'arrow-left' : pageContent.secondaryAction === 'reload' ? 'refresh-cw' : 'home'"
                                class="h-4 w-4"
                            />
                            {{ pageContent.secondaryLabel }}
                        </Button>
                    </div>

                    <div class="mt-8 grid gap-3 rounded-3xl border border-slate-200/70 bg-slate-50/80 p-4 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-300 sm:grid-cols-2">
                        <div class="flex items-start gap-3">
                            <Icon icon="shield-check" class="mt-0.5 h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                            <span>Your school data remains protected.</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <Icon icon="life-buoy" class="mt-0.5 h-4 w-4 text-sky-600 dark:text-sky-400" />
                            <span>If the issue continues, please contact school support.</span>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
