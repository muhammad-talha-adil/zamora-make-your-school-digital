<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { update } from '@/actions/App/Http/Controllers/Settings/SubscriptionController';
import { type BreadcrumbItem } from '@/types';

interface Subscription {
    status: 'demo' | 'active' | 'suspended' | 'expired';
    block_reason: 'subscription_expired' | 'domain_expiring' | 'hosting_expiring' | 'other' | null;
    block_reason_note: string | null;
    demo_expires_at: string | null;
    subscription_expires_at: string | null;
    notes: string | null;
}

interface Props {
    subscription: Subscription;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Subscription',
        href: '/settings/subscription',
    },
];

/**
 * Datetime columns come back as full timestamps; `<input type="date">` only
 * accepts the date portion.
 */
const toDateInput = (value: string | null): string => (value ? value.slice(0, 10) : '');

const form = useForm({
    status: props.subscription.status,
    block_reason: props.subscription.block_reason ?? '',
    block_reason_note: props.subscription.block_reason_note ?? '',
    demo_expires_at: toDateInput(props.subscription.demo_expires_at),
    subscription_expires_at: toDateInput(props.subscription.subscription_expires_at),
    notes: props.subscription.notes ?? '',
});

const submit = () => {
    form.patch(update().url, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Subscription" />

        <SettingsLayout>
            <div class="space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <HeadingSmall
                        title="Subscription"
                        description="Control demo/subscription status and access lockout for this installation."
                    />
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-destructive/10 px-2.5 py-0.5 text-xs font-medium text-destructive">
                        <Icon icon="shield-alert" class="h-3.5 w-3.5" />
                        Developer Only
                    </span>
                </div>

                <form @submit.prevent="submit" class="max-w-2xl space-y-6">
                    <div class="grid gap-2">
                        <Label for="status">Status</Label>
                        <select
                            id="status"
                            v-model="form.status"
                            class="w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-primary"
                        >
                            <option value="demo">Demo</option>
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                            <option value="expired">Expired</option>
                        </select>
                        <p v-if="form.errors.status" class="text-sm text-destructive">{{ form.errors.status }}</p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="demo_expires_at">Demo expires at</Label>
                        <input
                            id="demo_expires_at"
                            v-model="form.demo_expires_at"
                            type="date"
                            class="w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-primary"
                        />
                        <p class="text-xs text-muted-foreground">Leave blank for no expiry (lifetime demo). Only applies while status is "Demo".</p>
                        <p v-if="form.errors.demo_expires_at" class="text-sm text-destructive">{{ form.errors.demo_expires_at }}</p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="subscription_expires_at">Subscription expires at</Label>
                        <input
                            id="subscription_expires_at"
                            v-model="form.subscription_expires_at"
                            type="date"
                            class="w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-primary"
                        />
                        <p class="text-xs text-muted-foreground">Leave blank for no expiry (lifetime subscription). Only applies while status is "Active".</p>
                        <p v-if="form.errors.subscription_expires_at" class="text-sm text-destructive">{{ form.errors.subscription_expires_at }}</p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="block_reason">Block reason</Label>
                        <select
                            id="block_reason"
                            v-model="form.block_reason"
                            class="w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-primary"
                        >
                            <option value="">None</option>
                            <option value="subscription_expired">Subscription expired</option>
                            <option value="domain_expiring">Domain expiring</option>
                            <option value="hosting_expiring">Hosting expiring</option>
                            <option value="other">Other</option>
                        </select>
                        <p class="text-xs text-muted-foreground">Shown to blocked users on the locked-out page, explaining why access is paused.</p>
                        <p v-if="form.errors.block_reason" class="text-sm text-destructive">{{ form.errors.block_reason }}</p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="block_reason_note">Block reason note</Label>
                        <textarea
                            id="block_reason_note"
                            v-model="form.block_reason_note"
                            rows="2"
                            class="w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-primary"
                            placeholder="Optional detail shown when block reason is 'Other', or extra context for any reason."
                        ></textarea>
                        <p v-if="form.errors.block_reason_note" class="text-sm text-destructive">{{ form.errors.block_reason_note }}</p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="notes">Internal notes</Label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="4"
                            class="w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-primary"
                            placeholder="Developer-only notes about this installation's subscription."
                        ></textarea>
                        <p v-if="form.errors.notes" class="text-sm text-destructive">{{ form.errors.notes }}</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">Save subscription</Button>
                        <p v-show="form.recentlySuccessful" class="text-sm text-muted-foreground">Saved.</p>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
