<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { alert } from '@/utils';

defineProps<{
    login: { name: string; username: string; email: string; password: string };
}>();

const copy = async (login: { username: string; email: string; password: string }) => {
    try {
        await navigator.clipboard.writeText(`Username: ${login.username}\nEmail: ${login.email}\nPassword: ${login.password}`);
        alert.success('Copied.');
    } catch {
        alert.error('Could not copy. Please write it down.');
    }
};
</script>

<template>
    <div class="rounded-xl border bg-card p-5 text-card-foreground shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold">Login for {{ login.name }}</h3>
                <p class="mt-1 text-sm text-muted-foreground">Write the password down now. It will not be shown again.</p>
            </div>
            <Button type="button" variant="outline" size="sm" @click="copy(login)">
                <Icon icon="copy" class="mr-1 h-4 w-4" />
                Copy
            </Button>
        </div>
        <dl class="mt-4 divide-y rounded-lg border text-sm">
            <div class="flex items-center justify-between gap-4 px-3 py-2">
                <dt class="text-muted-foreground">Username</dt>
                <dd class="font-mono font-medium break-all">{{ login.username }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 px-3 py-2">
                <dt class="text-muted-foreground">Email</dt>
                <dd class="font-mono font-medium break-all">{{ login.email }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 bg-muted/50 px-3 py-2">
                <dt class="text-muted-foreground">Password</dt>
                <dd class="font-mono text-base font-bold select-all">{{ login.password }}</dd>
            </div>
        </dl>
    </div>
</template>
