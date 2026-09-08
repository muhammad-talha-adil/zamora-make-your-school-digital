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
        badgeClass: 'bg-warning/15 text-warning ring-warning/25',
        glowClass: 'from-warning/25 via-warning/10 to-transparent dark:to-transparent',
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
        badgeClass: 'bg-destructive/15 text-destructive ring-destructive/25',
        glowClass: 'from-destructive/25 via-destructive/10 to-transparent',
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
        badgeClass: 'bg-info/15 text-info ring-info/25',
        glowClass: 'from-info/25 via-info/10 to-transparent dark:to-transparent',
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
        badgeClass: 'bg-warning/15 text-warning ring-warning/25',
        glowClass: 'from-warning/25 via-warning/10 to-transparent dark:to-transparent',
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
        badgeClass: 'bg-warning/15 text-warning ring-warning/25',
        glowClass: 'from-warning/25 via-warning/10 to-transparent dark:to-transparent',
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
        badgeClass: 'bg-destructive/15 text-destructive ring-destructive/25',
        glowClass: 'from-destructive/25 via-destructive/10 to-transparent dark:to-transparent',
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
        badgeClass: 'bg-muted/15 text-muted-foreground ring-ring/25',
        glowClass: 'from-muted/25 via-primary/10 to-transparent dark:to-transparent',
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
    if (props.status === 500) return 'fill-destructive';
    if (props.status === 429) return 'fill-warning';
    if (props.status === 419) return 'fill-warning';
    return 'fill-info';
});
</script>

<template>
    <div class="scene relative min-h-screen overflow-hidden bg-background px-4 py-8 text-foreground sm:px-6 lg:px-8">
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute left-[-8rem] top-[-6rem] h-64 w-64 rounded-full bg-info/15 blur-3xl"></div>
            <div :class="['absolute right-[-5rem] top-20 h-72 w-72 rounded-full blur-3xl', pageContent.glowClass]"></div>
            <div class="absolute bottom-[-8rem] left-1/2 h-80 w-80 -translate-x-1/2 rounded-full bg-primary/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto flex min-h-[calc(100vh-4rem)] max-w-6xl items-center">
            <div class="panel grid w-full gap-6 overflow-hidden rounded-4xl border border-border/70 bg-card/90 p-6 backdrop-blur md:grid-cols-[1.05fr_0.95fr] md:p-10">
                <section class="sheet relative overflow-hidden rounded-3xl border border-border/80 p-6 md:p-8">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-lg">
                            <Icon icon="school" class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-muted-foreground">Zamora School Portal</p>
                            <p class="text-base font-semibold text-foreground">{{ pageContent.sceneTitle }}</p>
                        </div>
                    </div>

                    <div class="relative isolate">
                        <div class="absolute left-8 right-8 top-8 h-32 rounded-full bg-info/25 blur-3xl"></div>
                        <svg viewBox="0 0 360 250" class="relative z-10 w-full">
                            <defs>
                                <linearGradient id="campus-sky-light" x1="0%" x2="0%" y1="0%" y2="100%">
                                    <stop offset="0%" stop-color="color-mix(in srgb, var(--info) 10%, var(--card))" />
                                    <stop offset="100%" stop-color="color-mix(in srgb, var(--info) 22%, var(--card))" />
                                </linearGradient>
                                <linearGradient id="campus-ground-light" x1="0%" x2="0%" y1="0%" y2="100%">
                                    <stop offset="0%" stop-color="color-mix(in srgb, var(--success) 28%, var(--card))" />
                                    <stop offset="100%" stop-color="color-mix(in srgb, var(--success) 18%, var(--card))" />
                                </linearGradient>
                                <linearGradient id="campus-building" x1="0%" x2="0%" y1="0%" y2="100%">
                                    <stop offset="0%" stop-color="var(--card)" />
                                    <stop offset="100%" stop-color="color-mix(in srgb, var(--info) 14%, var(--card))" />
                                </linearGradient>
                            </defs>

                            <rect x="8" y="12" width="344" height="168" rx="28" fill="url(#campus-sky-light)" class="" />
                            <circle cx="72" cy="54" r="22" fill="color-mix(in srgb, var(--warning) 55%, var(--card))" class="" />
                            <path d="M36 186C54 168 84 162 114 170C142 178 161 177 188 166C220 152 250 150 281 160C307 168 329 184 344 206H22C24 198 30 192 36 186Z" fill="url(#campus-ground-light)" class="" />

                            <rect x="92" y="70" width="176" height="118" rx="16" fill="url(#campus-building)" class="" stroke="var(--border)" />
                            <rect x="142" y="46" width="76" height="34" rx="12" fill="var(--primary)" class="" />
                            <text x="180" y="67" text-anchor="middle" font-size="14" font-weight="700" fill="var(--primary-foreground)">SCHOOL</text>

                            <rect x="110" y="92" width="26" height="26" rx="6" :class="windowsClass" />
                            <rect x="148" y="92" width="26" height="26" rx="6" :class="windowsClass" />
                            <rect x="186" y="92" width="26" height="26" rx="6" :class="windowsClass" />
                            <rect x="224" y="92" width="26" height="26" rx="6" :class="windowsClass" />
                            <rect x="110" y="128" width="26" height="26" rx="6" :class="windowsClass" />
                            <rect x="224" y="128" width="26" height="26" rx="6" :class="windowsClass" />

                            <rect x="156" y="124" width="48" height="64" rx="10" fill="color-mix(in srgb, var(--foreground) 80%, var(--card))" class="" />
                            <rect x="168" y="138" width="24" height="22" rx="6" fill="color-mix(in srgb, var(--info) 45%, var(--card))" class="" />

                            <path d="M60 198H302" stroke="var(--border)" stroke-width="6" stroke-linecap="round" class="" />
                            <path d="M82 198L106 178" stroke="var(--border)" stroke-width="4" stroke-linecap="round" class="" />
                            <path d="M278 198L254 178" stroke="var(--border)" stroke-width="4" stroke-linecap="round" class="" />

                            <g transform="translate(250 30)">
                                <rect x="0" y="0" width="66" height="66" rx="20" fill="var(--card)" fill-opacity="0.94" class="" />
                                <rect x="0.5" y="0.5" width="65" height="65" rx="19.5" fill="none" stroke="var(--border)" class="" />
                                <foreignObject x="15" y="15" width="36" height="36">
                                    <div xmlns="http://www.w3.org/1999/xhtml" class="flex h-full w-full items-center justify-center">
                                        <div :class="['flex h-10 w-10 items-center justify-center rounded-2xl ring-1', pageContent.badgeClass]">
                                            <Icon :icon="pageContent.badgeIcon" class="h-5 w-5" />
                                        </div>
                                    </div>
                                </foreignObject>
                            </g>

                            <g v-if="status === 404">
                                <path d="M216 171C232 168 242 171 252 179" stroke="var(--info)" stroke-width="4" stroke-linecap="round" stroke-dasharray="7 7" class="" />
                                <circle cx="260" cy="183" r="12" fill="var(--card)" stroke="var(--info)" stroke-width="4" class="" />
                                <path d="M268 191L279 202" stroke="var(--info)" stroke-width="4" stroke-linecap="round" class="" />
                            </g>

                            <g v-if="status === 403">
                                <rect x="152" y="144" width="56" height="10" rx="5" fill="var(--destructive)" class="" />
                            </g>

                            <g v-if="status === 419">
                                <circle cx="63" cy="120" r="18" fill="var(--card)" stroke="var(--warning)" stroke-width="4" class="" />
                                <path d="M63 109V121L71 126" stroke="var(--warning)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" class="" />
                            </g>

                            <g v-if="status === 429">
                                <path d="M46 144H80" stroke="var(--warning)" stroke-width="6" stroke-linecap="round" class="" />
                                <path d="M52 130H74" stroke="var(--warning)" stroke-width="6" stroke-linecap="round" class="" />
                                <path d="M58 116H68" stroke="var(--warning)" stroke-width="6" stroke-linecap="round" class="" />
                            </g>

                            <g v-if="status === 500 || status === 503">
                                <path d="M286 134L304 116" stroke="var(--destructive)" stroke-width="4" stroke-linecap="round" class="" />
                                <path d="M300 134L318 116" stroke="var(--destructive)" stroke-width="4" stroke-linecap="round" class="" />
                                <path v-if="status === 503" d="M280 154H322" stroke="var(--muted-foreground)" stroke-width="6" stroke-linecap="round" stroke-dasharray="7 7" class="" />
                            </g>

                            <text x="180" y="215" text-anchor="middle" font-size="14" font-weight="700" fill="var(--foreground)" class="">{{ doorLabel }}</text>
                        </svg>
                    </div>

                    <div class="mt-6 rounded-3xl border border-white/70 bg-card/70 p-4 shadow-sm">
                        <p class="text-sm font-semibold text-foreground">{{ pageContent.sceneNote }}</p>
                    </div>
                </section>

                <section class="flex flex-col justify-center rounded-[1.75rem] border border-border/70 bg-card/80 p-6 md:p-8">
                    <div :class="['mb-6 inline-flex w-fit items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold ring-1', pageContent.badgeClass]">
                        <Icon :icon="pageContent.badgeIcon" class="h-4 w-4" />
                        Error {{ status }}
                    </div>

                    <h1 class="text-4xl font-bold tracking-tight text-foreground sm:text-5xl">
                        {{ pageContent.title }}
                    </h1>

                    <p class="mt-4 text-lg leading-8 text-muted-foreground">
                        {{ pageContent.description }}
                    </p>

                    <div class="mt-6 rounded-3xl border border-border/80 bg-muted/90 p-5">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-card text-card-foreground">
                                <Icon icon="info" class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-base font-semibold text-foreground">What you can do next</p>
                                <p class="mt-1 text-sm leading-7 text-muted-foreground">
                                    {{ pageContent.detail }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <Button
                            size="lg"
                            class="min-w-[12rem] rounded-2xl bg-card text-card-foreground hover:bg-card"
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
                            class="min-w-[12rem] rounded-2xl border-border bg-card/80"
                            @click="performAction(pageContent.secondaryAction)"
                        >
                            <Icon
                                :icon="pageContent.secondaryAction === 'back' ? 'arrow-left' : pageContent.secondaryAction === 'reload' ? 'refresh-cw' : 'home'"
                                class="h-4 w-4"
                            />
                            {{ pageContent.secondaryLabel }}
                        </Button>
                    </div>

                    <div class="mt-8 grid gap-3 rounded-3xl border border-border/70 bg-muted/80 p-4 text-sm text-muted-foreground sm:grid-cols-2">
                        <div class="flex items-start gap-3">
                            <Icon icon="shield-check" class="mt-0.5 h-4 w-4 text-success" />
                            <span>Your school data remains protected.</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <Icon icon="life-buoy" class="mt-0.5 h-4 w-4 text-info" />
                            <span>If the issue continues, please contact school support.</span>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<style scoped>
/*
  The decorative washes were fixed hex gradients, so this page kept a blue-grey
  cast whatever palette was selected. Mixing them from the theme tokens lets
  the page follow the palette in both modes.
*/
.scene {
    background-image:
        radial-gradient(circle at top, color-mix(in srgb, var(--info) 12%, transparent), transparent 35%),
        linear-gradient(180deg, color-mix(in srgb, var(--info) 4%, var(--background)) 0%, var(--background) 55%);
}

.panel {
    box-shadow: 0 30px 80px color-mix(in srgb, var(--foreground) 14%, transparent);
}

.sheet {
    background-image: linear-gradient(
        180deg,
        color-mix(in srgb, var(--card) 92%, transparent) 0%,
        color-mix(in srgb, var(--info) 8%, var(--card)) 100%
    );
}
</style>
