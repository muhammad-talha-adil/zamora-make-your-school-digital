<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/components/Icon.vue';

interface Props {
    blockReason: 'subscription_expired' | 'domain_expiring' | 'hosting_expiring' | 'other' | null;
    blockReasonNote?: string | null;
    supportContact: string;
}

const props = defineProps<Props>();

const content = computed(() => {
    switch (props.blockReason) {
        case 'subscription_expired':
            return {
                title: 'This subscription has expired',
                description: 'Access to this school’s portal has been paused because the subscription period has ended.',
            };
        case 'domain_expiring':
            return {
                title: 'This installation’s domain is expiring',
                description: 'Access has been paused because the domain for this installation needs to be renewed.',
            };
        case 'hosting_expiring':
            return {
                title: 'Hosting renewal is due',
                description: 'Access has been paused because the hosting for this installation needs to be renewed.',
            };
        default:
            return {
                title: 'This portal is temporarily unavailable',
                description: 'Access has been paused for this installation.',
            };
    }
});
</script>

<template>
    <Head title="Access Unavailable" />

    <div class="scene relative flex min-h-screen items-center justify-center overflow-hidden bg-background px-4 py-8 text-foreground sm:px-6 lg:px-8">
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute left-[-8rem] top-[-6rem] h-64 w-64 rounded-full bg-warning/15 blur-3xl"></div>
            <div class="absolute right-[-5rem] top-20 h-72 w-72 rounded-full bg-destructive/15 blur-3xl"></div>
        </div>

        <div class="relative w-full max-w-lg rounded-3xl border border-border/70 bg-card/90 p-8 text-center shadow-lg backdrop-blur">
            <div class="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-warning/15 text-warning ring-1 ring-warning/25">
                <Icon icon="lock" class="h-7 w-7" />
            </div>

            <h1 class="text-2xl font-bold tracking-tight text-foreground">
                {{ content.title }}
            </h1>

            <p class="mt-3 text-base leading-7 text-muted-foreground">
                {{ content.description }}
            </p>

            <p v-if="blockReason === 'other' && blockReasonNote" class="mt-3 text-sm leading-6 text-muted-foreground">
                {{ blockReasonNote }}
            </p>

            <div class="mt-6 rounded-2xl border border-border/70 bg-muted/80 p-4 text-sm text-muted-foreground">
                <div class="flex items-start justify-center gap-2">
                    <Icon icon="life-buoy" class="mt-0.5 h-4 w-4 text-info" />
                    <span>Please contact {{ supportContact }} to restore access.</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.scene {
    background-image:
        radial-gradient(circle at top, color-mix(in srgb, var(--warning) 12%, transparent), transparent 35%),
        linear-gradient(180deg, color-mix(in srgb, var(--warning) 4%, var(--background)) 0%, var(--background) 55%);
}
</style>
